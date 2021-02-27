<?php
namespace Student\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\ORM\ResultSet;
use Cake\Network\Request;

use App\Model\Table\ControllerActionTable;

class InstitutionAssociationStudentTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id', 'joinType' => 'INNER']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('InstitutionAssociations', ['className' => 'Institution.InstitutionAssociations', 'foreignKey' => 'institution_association_id']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
        $this->hasMany('InstitutionStudents', ['className' => 'Institution.Students']);
        $this->belongsTo('Genders', ['className' => 'User.Genders']);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'AssociationStudent' => ['index','add','edit'],
        ]);
        $this->toggle('add', false);
        $this->toggle('search', false);
        $this->toggle('edit', false);
        $this->toggle('view', false);
        $this->toggle('remove', false);
    }
    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->fields['security_user_id']['visible'] = false;
        $this->setFieldOrder('academic_period_id','name','education_grade_id','student_status_id');    
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'institution_association_id') {
            return __('Name');
        }
        return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
    }


    private function setupTabElements()
    {
        $options['type'] = 'student';
        $tabElements = $this->controller->getAcademicTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'Associations');
    }

    public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $this->setupTabElements();
    }

    public function getMaleCountByAssociations($associationId)
    {
        $gender_id = 1; // male
        $count = $this
            ->find()
            ->contain('Users')
            ->matching('StudentStatuses', function ($q) {
                return $q->where(['StudentStatuses.code NOT IN' => ['TRANSFERRED', 'WITHDRAWN']]);
            })
            ->where([$this->Users->aliasField('gender_id') => $gender_id])
            ->where([$this->aliasField('institution_association_id') => $associationId])
            ->count()
        ;
        return $count;
    }

    public function getFemaleCountByAssociations($associationId)
    {
        $gender_id = 2; // female
        $count = $this
            ->find()
            ->contain('Users')
            ->matching('StudentStatuses', function ($q) {
                return $q->where(['StudentStatuses.code NOT IN' => ['TRANSFERRED', 'WITHDRAWN']]);
            })
            ->where([$this->Users->aliasField('gender_id') => $gender_id])
            ->where([$this->aliasField('institution_association_id') => $associationId])
            ->count()
        ;
        return $count;
    }

    // public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    // {
    //     if($entity->isNew() || $entity->dirty('student_status_id')) {
    //         $id = $entity->institution_association_id;
    //         $countMale = $this->getMaleCountByAssociations($id);
    //         $countFemale = $this->getFemaleCountByAssociations($id);
    //         $this->InstitutionAssociations->updateAll(['total_male_students' => $countMale, 'total_female_students' => $countFemale], ['id' => $id]);
    //     }
    // }

    // public function afterDelete(Event $event, Entity $entity, ArrayObject $options)
    // {   
    //     $id = $entity->institution_association_id;
    //     $countMale = $this->getMaleCountByAssociations($id);
    //     $countFemale = $this->getFemaleCountByAssociations($id);
    //     $this->InstitutionAssociations->updateAll(['total_male_students' => $countMale, 'total_female_students' => $countFemale], ['id' => $id]);
    // }

    public function findInstitutionStudentsNotInAssociation(Query $query, array $options)
    {
        
        $educationGradeIds = null;
        $academicPeriodId = null;
        $institutionClassId = null;
        $institutionId = null;
        $enrolledStatus = TableRegistry::get('Student.StudentStatuses')->findByCode('CURRENT')->first()->id;

        if (array_key_exists('institution_association_id', $options)) {
            $institutionClassId = $options['institution_association_id'];
            $institutionClassRecord = TableRegistry::get('Institution.InstitutionAssociations')->get($institutionClassId, ['contain' => ['EducationGrades']])->toArray();
            $academicPeriodId = $institutionClassRecord['academic_period_id'];
            $institutionId = $institutionClassRecord['institution_id'];
            $educationGradeIds = array_column($institutionClassRecord['education_grades'], 'id');
            if (empty($educationGradeIds)) {
                return $query->where(['1 = 0']);
            }
        }
       
            return $query
            ->contain('InstitutionAssociations')
            ->matching('Users', function ($q) {
                return $q->select(['Users.openemis_no',
                    'Users.first_name',
                    'Users.middle_name',
                    'Users.third_name',
                    'Users.last_name',
                    'Users.preferred_name']);
            })
            ->matching('Users.Genders')
            ->matching('StudentStatuses')
            ->where([
                //$this->aliasField('institution_association_id').' = ' => $institutionClassId,
                $this->aliasField('education_grade_id') => $educationGradeId,
                $this->aliasField('academic_period_id') => $academicPeriodId
            ])
            ->order(['Users.first_name', 'Users.last_name']) // POCOR-2547 sort list of staff and student by name
            ->formatResults(function ($results) {
                $resultArr = [];
                foreach ($results as $result) {
                    $resultArr[] = [
                        'openemis_no' => $result->_matchingData['Users']->openemis_no,
                        'name' => $result->_matchingData['Users']->name,
                        'gender' => __($result->_matchingData['Genders']->name),
                        'gender_id' => $result->_matchingData['Genders']->id,
                        'student_status' => __($result->_matchingData['StudentStatuses']->name),
                        'security_user_id' => $result->security_user_id,
                        'institution_association_id' => $result->institution_association_id,
                        'education_grade_id' => $result->education_grade_id,
                        'academic_period_id' => $result->academic_period_id,
                        'institution_id' => $result->institution_id,
                        'student_status_id' => $result->student_status_id,
                       // 'institution_class' => $result->institution_class->name
                    ];
                }

                return $resultArr;
            });
    }

    public function findInstitutionStudent(Query $query, array $options)
    {
        $academicPeriodId = null;
        $institutionId = null;
        $enrolledStatus = TableRegistry::get('Student.StudentStatuses')->findByCode('CURRENT')->first()->id;
   
             return $query
            ->contain('Users')
            ->matching('Users', function ($q) {
                return $q->select(['Users.openemis_no',
                    'Users.first_name',
                    'Users.middle_name',
                    'Users.third_name',
                    'Users.last_name',
                    'Users.preferred_name']);
            })
            ->matching('Users.Genders')
            ->matching('StudentStatuses')
            ->matching('EducationGrades')
            // ->where([
            //     $this->aliasField('education_grade_id') => $educationGradeId,
            //     $this->aliasField('academic_period_id') => $academicPeriodId
            // ])
            ->order(['Users.first_name', 'Users.last_name']) // POCOR-2547 sort list of staff and student by name
            ->formatResults(function ($results) {
                $resultArr = [];
                foreach ($results as $result) {
                    $resultArr[] = [
                        'openemis_no' => $result->_matchingData['Users']->openemis_no,
                        'name' => $result->_matchingData['Users']->name,
                        'gender' => __($result->_matchingData['Genders']->name),
                        'gender_id' => $result->_matchingData['Genders']->id,
                        'student_status' => __($result->_matchingData['StudentStatuses']->name),
                        'security_user_id' => $result->security_user_id,
                        'education_grade_id' => $result->education_grade_id,
                        'education_grade_name' => __($result->_matchingData['EducationGrades']->name),
                        'academic_period_id' => $result->academic_period_id,
                        'institution_id' => $result->institution_id,
                        'student_status_id' => $result->student_status_id,
                    ];
                }

                return $resultArr;
            });
    }     
}
