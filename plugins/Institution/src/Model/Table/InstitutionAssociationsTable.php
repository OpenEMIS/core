<?php
namespace Institution\Model\Table;

use ArrayObject;
use stdClass;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Utility\Text;
use Cake\Validation\Validator;
use Cake\Collection\Collection;
use Cake\I18n\Date;
use Cake\Log\Log;
use Cake\Routing\Router;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\MessagesTrait;

class InstitutionAssociationsTable extends ControllerActionTable
{
    use MessagesTrait;

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->hasMany('AssociationStudent', ['className' => 'Student.InstitutionAssociationStudent', 'saveStrategy' => 'replace', 'cascadeCallbacks' => true]);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        //$this->hasMany('InstitutionAssociationStaff', ['className' => 'Staff.InstitutionAssociationStaff', 'saveStrategy' => 'replace', 'foreignKey' => 'institution_association_id']);
        $this->belongsToMany('EducationGrades', [
            'className' => 'Education.EducationGrades',
            'joinTable' => 'student_mark_type_status_grades',
            'foreignKey' => 'student_mark_type_status_id',
            'targetForeignKey' => 'education_grade_id'
        ]);
        $this->hasMany('AssociationStaff', ['className' => 'Institution.InstitutionAssociationStaff', 'saveStrategy' => 'replace', 'foreignKey' => 'institution_association_id']);
        // $this->belongsToMany('AssociationStaff', [
        //     'className' => 'User.Users',
        //     'through' => 'Institution.InstitutionAssociationStaff',
        //     'foreignKey' => 'institution_association_id',
        //     'targetForeignKey' => 'security_user_id',
        //     'dependent' => true
        // ]);
        
        //$this->hasMany('AssociationStaff', ['className' => 'Institution.InstitutionAssociationStaff', 'saveStrategy' => 'replace', 'cascadeCallbacks' => true]);
        $this->addBehavior('AssociationExcel', ['excludes' => ['security_group_id'], 'pages' => ['view']]);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'AssociationStudent' => ['index','add','view', 'edit'],
        ]);

    }
    
    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
         
         switch ($field) {
            case 'association_staff':
                return __('Staff');
            case 'total_male_students': 
                return __('Male Students');
            case 'total_female_students':
                return __('Female Students');
            case 'total_students':
                return __('Total Students');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->controllerAction = $extra['indexButtons']['view']['url']['action'];
        $query = $this->request->query;
        $this->field('modified_user_id', ['visible' => false]);
        $this->field('modified', ['visible' => false]);
        $this->field('created_user_id', ['visible' => false]);
       // $this->field('code', ['visible' => false]);
        $this->field('created', ['visible' => false]);
        $this->field('total_male_students', ['visible' => ['index'=>true]]);
        $this->field('total_female_students', ['visible' => ['index'=>true]]);
        $this->field('total_students', ['type' => 'integer', 'visible' => ['index' => true]]);
        $this->field('academic_period_id', ['type' => 'select', 'visible' => ['view' => true, 'edit' => true]]);

        $this->field('students', [
            'label' => '',
            'override' => true,
            'type' => 'element',
            'element' => 'Institution.Associations/students',
            'data' => [
                'students' => [],
                'studentOptions' => []
            ],
            'visible' => ['view' => true, 'edit' => true]
        ]);
        $this->field('association_staff');
        $this->setFieldOrder([
            'name','association_staff'
        ]);
        $this->setFieldOrder([
            'name', 'association_staff','total_male_students', 'total_female_students', 'total_students'
        ]);
    }
    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        if ($data->offsetExists('AssociationStudent') && empty($data['AssociationStudent'])) { //only utilize save by association when class student empty.
            $data['association_student'] = [];
            $data['total_male_students'] = 0;
            $data['total_female_students'] = 0;
            $data->offsetUnset('AssociationStudent');
        }
    }

    /******************************************************************************************************************
    **
    ** delete action methods
    **
    ******************************************************************************************************************/

    public function deleteAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        
        if(!empty($this->controllerAction) && ($this->controllerAction == 'Associations')) {
            // Delete Students related to associations 
            $existingStudents = $this->AssociationStudent
                ->find('all')
                ->select([
                    'id', 'security_user_id', 'institution_association_id', 'education_grade_id', 'academic_period_id','student_status_id'
                ])
                ->where([
                    $this->AssociationStudent->aliasField('institution_association_id') => $entity->id
                ])
                ->toArray();
            if ($existingStudents && !empty($existingStudents)) {
                foreach ($existingStudents as $key => $StudentEntity) {    
                     $this->AssociationStudent->delete($StudentEntity);
                }
                $countMale = $this->AssociationStudent->getMaleCountByAssociations($entity->id);
                $countFemale = $this->AssociationStudent->getFemaleCountByAssociations($entity->id);
                $this->updateAll(['total_male_students' => $countMale, 'total_female_students' => $countFemale], ['id' => $id]); 
            }

            // Delete Staff related to associations 
             $existingStaffs = $this->AssociationStaff
                ->find('all')
                ->select([
                    'id', 'security_user_id', 'institution_association_id'
                ])
                ->where([
                    $this->AssociationStaff->aliasField('institution_association_id') => $entity->id
                ])
                ->toArray();
            if ($existingStaffs && !empty($existingStaffs)) {
                foreach ($existingStaffs as $key => $StaffEntity) {    
                     $this->AssociationStaff->delete($StaffEntity);
                } 
            }
                
        }
    }
    /******************************************************************************************************************
    **
    ** index action methods
    **
    ******************************************************************************************************************/
    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $query = $this->request->query;
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();
        $institutionId = $extra['institution_id'];
        $selectedAcademicPeriodId = $this->queryString('academic_period_id', $academicPeriodOptions);
       
        $this->advancedSelectOptions($academicPeriodOptions, $selectedAcademicPeriodId);
        $extra['selectedAcademicPeriodId'] = $selectedAcademicPeriodId;
        $extra['elements']['control'] = [
            'name' => 'Institution.Associations/controls',
            'data' => [
                'academicPeriodOptions'=>$academicPeriodOptions,
                'selectedAcademicPeriod'=>$selectedAcademicPeriodId
            ],
            'options' => [],
            'order' => 3
        ];
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $sortable = !is_null($this->request->query('sort')) ? true : false;

        $query
            // ->find('byGrades', [
            //     'education_grade_id' => $extra['selectedEducationGradeId'],
            // ])
            ->select([
                'id',
                'name',
                'total_male_students',
                'total_female_students',
                'institution_id',
                'academic_period_id',
                'modified_user_id',
                'modified',
                'created_user_id',
                'created',
                //'education_stage_order' => $query->func()->min('EducationStages.order')
            ])
            ->contain([
                'AssociationStaff.Users',
            ])
            ->where([$this->aliasField('academic_period_id') => $extra['selectedAcademicPeriodId']])
            ->group([$this->aliasField('id')]);

        if (!$sortable) {
            $query
                ->order([
                    $this->aliasField('name') => 'ASC'
                ]);
        }
    }

    public function onGetTotalStudents(Event $event, Entity $entity)
    {
        return $entity->total_male_students + $entity->total_female_students;
    }

    public function onGetAssociationStaff(Event $event, Entity $entity)
    {        
        if ($this->action == 'view') {
            if ($entity->has('association_staff') && !empty($entity->association_staff)) {
                $staffList = [];
                foreach ($entity->association_staff as $staffVal) {
                        $staffLink = $event->subject()->Html->link($staffVal->user->name_with_id, [
                            'plugin' => 'Institution',
                            'controller' => 'Institutions',
                            'action' => 'StaffUser',
                            'view',
                            $this->paramsEncode(['id' => $staffVal->id])
                        ]);

                        $staffList[] = $staffLink;
                } 
                return implode(', ', $staffList);
            } else {
                return $this->getMessage($this->aliasField('noTeacherAssigned'));
            }
        } else {
            if ($entity->has('association_staff') && !empty($entity->association_staff)) {
                $staffList = [];
              
                foreach ($entity->association_staff as $staffVal) {
                    $staffList[] = $staffVal->user->name_with_id;
                }
                return implode(', ', $staffList);
            } else {
                return $this->getMessage($this->aliasField('noTeacherAssigned'));
            }
        }
    }
   /******************************************************************************************************************
    **
    ** view action methods
    **
    ******************************************************************************************************************/
    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        if ($extra['selectedAcademicPeriodId'] == -1) {
            return $this->controller->redirect([
                'plugin' => $this->controller->plugin,
                'controller' => $this->controller->name,
                'action' => 'Classes'
            ]);
        }

        $query = $this->request->query;
        if (array_key_exists('academic_period_id', $query) || array_key_exists('education_grade_id', $query)) {
            $action = $this->url('view');
            if (array_key_exists('academic_period_id', $query)) {
                unset($action['academic_period_id']);
            }
            if (array_key_exists('education_grade_id', $query)) {
                unset($action['education_grade_id']);
            }
            //$this->controller->redirect($action);
        }
        $this->setFieldOrder([
            'academic_period_id', 'name','association_staff', 'students'
        ]);
    }

    public function viewBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $extra['selectedGrade'] = -1;
        $extra['selectedStatus'] = -1;
        $extra['selectedGender'] = -1;
        if (array_key_exists('queryString', $this->request->query)) {
            $queryString = $this->paramsDecode($this->request->query['queryString']);

            if (!empty($queryString) && array_key_exists('grade', $queryString)) {
                $extra['selectedGrade'] = $queryString['grade'];
            }

            if (!empty($queryString) && array_key_exists('status', $queryString)) {
                $extra['selectedStatus'] = $queryString['status'];
            }


            if (!empty($queryString) && array_key_exists('gender', $queryString)) {
                $extra['selectedGender'] = $queryString['gender'];
            }

            if (!empty($queryString) && array_key_exists('sort', $queryString)) {
                $extra['sort'] = $queryString['sort'];
            }

            if (!empty($queryString) && array_key_exists('direction', $queryString)) {
                $extra['direction'] = $queryString['direction'];
            }
        }

        $sortConditions = '';
        if (!empty($extra['sort'])) {
            if ($extra['sort'] == 'name') {
                $sortConditions = 'Users.first_name ' .  $extra['direction'];
            } elseif ($extra['sort'] == 'openemis_no') {
                $sortConditions = 'Users.openemis_no ' .  $extra['direction'];
            }
        }

        if ($sortConditions) {
            $query->contain([
                'AcademicPeriods',
                'EducationGrades',
                'AssociationStaff.Users',
                'AssociationStudent' => [
                    'Users.Genders',
                    'EducationGrades',
                    'StudentStatuses',
                    'sort' => [$sortConditions]
                ],
            ]);
        } else {
            $query->contain([
                'AcademicPeriods',
                'EducationGrades',
                'AssociationStaff.Users',
                'AssociationStudent' => [
                    'Users.Genders',
                    'EducationGrades',
                    'StudentStatuses'
                ],
            ]);
        }
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
       
        //generate student filter.
        $params = $this->getQueryString();
        $baseUrl = $this->url($this->action, true);

        $this->fields['students']['data']['baseUrl'] = $baseUrl;
        $this->fields['students']['data']['params'] = $params;

        $gradeOptions = [];
        $statusOptions = [];
        $genderOptions = [];

        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $configureStudentName = $ConfigItems->value("configure_student_name");

        foreach ($entity->association_student as $key => $value) {
            if (!empty($value->education_grade)) { //grade filter
                $gradeOptions[$value->education_grade->id]['name'] = $value->education_grade->name;
                $gradeOptions[$value->education_grade->id]['order'] = $value->education_grade->order;

                $params['grade'] = $value->education_grade->id;
                $params['status'] = $extra['selectedStatus']; //maintain current status selection
                $params['gender'] = $extra['selectedGender'];
                $url = $this->setQueryString($baseUrl, $params);

                $gradeOptions[$value->education_grade->id]['url'] = $url;
            }

            if (!empty($value->student_status)) { //status filter
                $statusOptions[$value->student_status->id]['name'] = $value->student_status->name;
                $statusOptions[$value->student_status->id]['order'] = $value->student_status->id;

                $params['grade'] = $extra['selectedGrade']; //maintain current grade selection
                $params['status'] = $value->student_status->id;
                $params['gender'] = $extra['selectedGender'];
                $url = $this->setQueryString($baseUrl, $params);

                $statusOptions[$value->student_status->id]['url'] = $url;
            }

            if (!empty($value->user) && !empty($value->user->gender)) { //gender filter
                $genderOptions[$value->user->gender->id]['name'] = $value->user->gender->name;
                $genderOptions[$value->user->gender->id]['order'] = $value->user->gender->id;

                $params['grade'] = $extra['selectedGrade']; //maintain current grade selection
                $params['status'] = $extra['selectedStatus'];
                $params['gender'] = $value->user->gender->id;
                $url = $this->setQueryString($baseUrl, $params);

                $genderOptions[$value->user->gender->id]['url'] = $url;
            }

            //if student does not fullfil the filter, then unset from array
            if ($extra['selectedGrade'] != -1 && $value->education_grade->id != $extra['selectedGrade']) {
                unset($entity->association_student[$key]);
            }

            if ($extra['selectedStatus'] != -1 && $value->student_status->id != $extra['selectedStatus']) {
                unset($entity->association_student[$key]);
            }

            if ($extra['selectedGender'] != -1 && $value->user->gender->id != $extra['selectedGender']) {
                unset($entity->association_student[$key]);
            }
        }

        //for all grades / no option
        $gradeOptions[-1]['name'] = count($gradeOptions) > 0 ? '-- ' . __('All Grades') . ' --' : '-- ' . __('No Options') . ' --';
        $gradeOptions[-1]['id'] = -1;
        $gradeOptions[-1]['order'] = 0;

        $params['grade'] = -1;
        $params['status'] = $extra['selectedStatus']; //maintain current status selection
        $params['gender'] = $extra['selectedGender'];
        $url = $this->setQueryString($baseUrl, $params);

        $gradeOptions[-1]['url'] = $url;

        //order array by 'order' key
        uasort($gradeOptions, function ($a, $b) {
            return $a['order']-$b['order'];
        });

        //for all statuses option
        $statusOptions[-1]['name'] = count($statusOptions) > 0 ? '-- ' . __('All Statuses') . ' --' : '-- ' . __('No Options') . ' --';
        $statusOptions[-1]['id'] = -1;
        $statusOptions[-1]['order'] = 0;

        $params['grade'] = $extra['selectedGrade']; //maintain current grade selection
        $params['status'] = -1;
        $params['gender'] = $extra['selectedGender'];
        $url = $this->setQueryString($baseUrl, $params);

        $statusOptions[-1]['url'] = $url;

        //order array by 'order' key
        uasort($statusOptions, function ($a, $b) {
            return $a['order']-$b['order'];
        });

        //for all gender option
        $genderOptions[-1]['name'] = count($genderOptions) > 0 ? '-- ' . __('All Genders') . ' --' : '-- ' . __('No Options') . ' --';
        $genderOptions[-1]['id'] = -1;
        $genderOptions[-1]['order'] = 0;

        $params['grade'] = $extra['selectedGrade']; //maintain current grade selection
        $params['status'] = $extra['selectedStatus'];
        $params['gender'] = -1;
        $url = $this->setQueryString($baseUrl, $params);

        $genderOptions[-1]['url'] = $url;

        //order array by 'order' key
        uasort($genderOptions, function ($a, $b) {
            return $a['order']-$b['order'];
        });

        //set option and selected filter value
        $this->fields['students']['data']['filter']['education_grades']['options'] = $gradeOptions;
        $this->fields['students']['data']['filter']['education_grades']['selected'] = $extra['selectedGrade'];

        $this->fields['students']['data']['filter']['student_status']['options'] = $statusOptions;
        $this->fields['students']['data']['filter']['student_status']['selected'] = $extra['selectedStatus'];

        $this->fields['students']['data']['filter']['genders']['options'] = $genderOptions;
        $this->fields['students']['data']['filter']['genders']['selected'] = $extra['selectedGender'];
        $this->fields['students']['data']['configure_student_name'] = $configureStudentName;

        $this->fields['education_grades']['data']['grades'] = $entity->education_grades;

        $this->fields['students']['data']['students'] = $entity->association_student;

        $academicPeriodOptions = $this->getAcademicPeriodOptions($entity->institution_id);
    }

/******************************************************************************************************************
    **
    ** add action methods
    **
    ******************************************************************************************************************/

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {    
          
        if ($entity->isNew()) {
                // POCOR-5435 ->Webhook Feature class (create)
                if ($entity->has('associationStudents') && !empty($entity->associationStudents)) {
                $newStudents = [];
                //decode string sent through form
                foreach ($entity->associationStudents as $item) {
                    $student = json_decode($this->urlsafeB64Decode($item), true);
                    $student['institution_association_id'] = $entity->id;
                    $newStudents[$student['security_user_id']] = $student;
                }
                                      
                foreach ($newStudents as $key => $student) {
                    $newClassStudentEntity = $this->AssociationStudent->newEntity($student);
                    $this->AssociationStudent->save($newClassStudentEntity);
                }
            }
        } else if (empty($entity->associationStudents)) {
                $institutionAssociationId = $entity->id;
                $existingStudents = $this->AssociationStudent
                    ->find('all')
                    ->select([
                        'id', 'security_user_id', 'institution_association_id', 'education_grade_id', 'academic_period_id','student_status_id'
                    ])
                    ->matching('StudentStatuses', function ($q) {
                        return $q->where(['StudentStatuses.code NOT IN' => ['TRANSFERRED', 'WITHDRAWN']]);
                    })
                    ->where([
                        $this->AssociationStudent->aliasField('institution_association_id') => $institutionAssociationId
                    ])
                    ->toArray();

                foreach ($existingStudents as $key => $classStudentEntity) {
                    if (!array_key_exists($classStudentEntity->security_user_id, $newStudents)) { // if current student does not exists in the new list of students
                        $this->AssociationStudent->delete($classStudentEntity);
                    } else { // if student exists, then remove from the array to get the new student records to be added
                        unset($newStudents[$classStudentEntity->security_user_id]);
                    }
                }
            }   
        else {
             if ($entity->has('associationStudents') && !empty($entity->associationStudents)) {
                $newStudents = [];
                //decode string sent through form
                foreach ($entity->associationStudents as $item) {
                    $student = json_decode($this->urlsafeB64Decode($item), true);
                    $student['institution_association_id'] = $entity->id;
                    $newStudents[$student['security_user_id']] = $student;
                }
                 $institutionAssociationId = $entity->id;

                $existingStudents = $this->AssociationStudent
                    ->find('all')
                    ->select([
                        'id', 'security_user_id', 'institution_association_id', 'education_grade_id', 'academic_period_id','student_status_id'
                    ])
                    ->matching('StudentStatuses', function ($q) {
                        return $q->where(['StudentStatuses.code NOT IN' => ['TRANSFERRED', 'WITHDRAWN']]);
                    })
                    ->where([
                        $this->AssociationStudent->aliasField('institution_association_id') => $institutionAssociationId
                    ])
                    ->toArray();

                foreach ($existingStudents as $key => $classStudentEntity) {
                    if (!array_key_exists($classStudentEntity->security_user_id, $newStudents)) { // if current student does not exists in the new list of students
                        $this->AssociationStudent->delete($classStudentEntity);
                    } else { // if student exists, then remove from the array to get the new student records to be added
                        unset($newStudents[$classStudentEntity->security_user_id]);
                    }
                }                                 
                foreach ($newStudents as $key => $student) {     
                    $newClassStudentEntity = $this->AssociationStudent->newEntity($student);
                    $this->AssociationStudent->save($newClassStudentEntity);
                }
            }
        }
    }
    
    public function afterSaveCommit(Event $event, Entity $entity, ArrayObject $options)
    {
        $id = $entity->id;
        $countMale = $this->AssociationStudent->getMaleCountByAssociations($id);
        $countFemale = $this->AssociationStudent->getFemaleCountByAssociations($id);
        $this->updateAll(['total_male_students' => $countMale, 'total_female_students' => $countFemale], ['id' => $id]);
    }

    private function getAcademicPeriodOptions($institutionId)
    {
        $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
        $conditions = [$InstitutionGrades->aliasField('institution_id') => $institutionId];
        return $InstitutionGrades->getAcademicPeriodOptions($this->Alert, $conditions);
    }
    /**
     * Get Associations Details
     */
    public function findAssociationDetails(Query $query, array $options)
    {
         return $query
            ->find('translateItem')
            ->contain([
                'AssociationStudent' => [
                    'sort' => ['Users.first_name', 'Users.last_name']
                ],
                'AssociationStudent.StudentStatuses' => function ($q) {
                    return $q->where([('StudentStatuses.code NOT IN ') => ['TRANSFERRED', 'WITHDRAWN']]);
                },
                'AssociationStudent.Users.Genders',
                'AssociationStudent.EducationGrades',
                'AcademicPeriods',
                'AssociationStaff.Users'
            ]);
    }

    public function findTranslateItem(Query $query, array $options)
    {
        return $query
            ->formatResults(function ($results) {
                $arrResults = $results->toArray();
                foreach ($arrResults as &$value) {
                    if (isset($value['association_student']) && is_array($value['association_student'])) {
                        foreach ($value['association_student'] as $student) {
                            $student['student_status']['name'] = __($student['student_status']['name']);
                        }
                    }
                }
                return $arrResults;
            });
    }
  
}
