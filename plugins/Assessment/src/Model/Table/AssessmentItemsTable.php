<?php
namespace Assessment\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Collection\Collection;
use Cake\Validation\Validator;
use Cake\View\Helper\UrlHelper;
use Cake\Routing\Router;
use App\Model\Traits\OptionsTrait;
use App\Model\Traits\HtmlTrait;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\MessagesTrait;
use Cake\Utility\Text;
use Cake\Datasource\ResultSetInterface;

use App\Model\Table\AppTable;

class AssessmentItemsTable extends AppTable
{

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('Assessments', ['className' => 'Assessment.Assessments']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
        $this->belongsToMany('AssessmentPeriods', [
            'className' => 'Assessment.AssessmentPeriods',
            'joinTable' => 'assessment_items_grading_types',
            'foreignKey' => ['assessment_id', 'education_subject_id'],
            'bindingKey' => ['assessment_id', 'education_subject_id'],
            'targetForeignKey' => 'assessment_period_id',
            'through' => 'Assessment.AssessmentItemsGradingTypes',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->belongsToMany('AssessmentGradingTypes', [
            'className' => 'Assessment.AssessmentGradingTypes',
            'joinTable' => 'assessment_items_grading_types',
            'foreignKey' => ['assessment_id', 'education_subject_id'],
            'bindingKey' => ['assessment_id', 'education_subject_id'],
            'targetForeignKey' => 'assessment_grading_type_id',
            'through' => 'Assessment.AssessmentItemsGradingTypes',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Results' => ['index', 'view']
        ]);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Restful.Model.isAuthorized'] = ['callable' => 'isAuthorized', 'priority' => 1];
        return $events;
    }

    public function isAuthorized(Event $event, $scope, $action, $extra)
    {
        if ($action == 'index' || $action == 'view') {
            // check for the user permission to view here
            $event->stopPropagation();
            return true;
        }
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        $validator
            ->add('weight', 'ruleIsDecimal', [
                'rule' => ['decimal', null],
            ])
            ->add('weight', 'ruleWeightRange', [
                'rule'  => ['range', 0, 2],
                'last' => true
            ]);
        return $validator;
    }

    public function populateAssessmentItemsArray($gradeId)
    {
        $EducationGradesSubjects = TableRegistry::get('Education.EducationGradesSubjects');
        $gradeSubjects = $EducationGradesSubjects->find()
            ->contain('EducationSubjects')
            ->where([$EducationGradesSubjects->aliasField('education_grade_id') => $gradeId])
            ->order(['order'])
            ->toArray();

        $assessmentItems = [];
        foreach ($gradeSubjects as $key => $gradeSubject) {
            if (!empty($gradeSubject->education_subject)) {
                $assessmentItems[] = [
                    'education_subject_id' => $gradeSubject->education_subject->id,
                    'education_subject' => $gradeSubject->education_subject,
                    'weight' => '0.00'
                ];
            }
        }
        return $assessmentItems;
    }


    public function findStaffSubjects(Query $query, array $options)
    {
        if (isset($options['class_id']) && isset($options['staff_id'])) {
            $classId = $options['class_id'];
            $staffId = $options['staff_id'];
            $query->where([
                    // For subject teachers
                    'EXISTS (
                        SELECT 1
                        FROM institution_subjects InstitutionSubjects
                        INNER JOIN institution_class_subjects InstitutionClassSubjects
                            ON InstitutionClassSubjects.institution_class_id = '.$classId.'
                            AND InstitutionClassSubjects.institution_subject_id = InstitutionSubjects.id
                        INNER JOIN institution_subject_staff InstitutionSubjectStaff
                            ON InstitutionSubjectStaff.institution_subject_id = InstitutionSubjects.id
                            AND InstitutionSubjectStaff.staff_id = '.$staffId.'
                        WHERE InstitutionSubjects.education_subject_id = ' . $this->aliasField('education_subject_id') .')'
                ]);

            return $query;
        }
    }

    //Pocor-5758 copy of findStaffSubjects
    public function findCopyStaffSubjects(Query $query, array $options)
    {
        $url = $_SERVER['HTTP_REFERER'];
        $queryString = parse_url($url);
        $name = $queryString['query'];
        $domain = substr($name, strpos($name, "="));
        $test = base64_decode($domain);
        $variable = substr($test, 0, strpos($test, "}"));
        $newVaridable = $variable . "}";
        $data = json_decode($newVaridable);
        $institutionId =  $data->institution_id;
        $academinPeriod = $data->academic_period_id;
        $ClassSubjects = TableRegistry::get('Institution.InstitutionClassSubjects');
        $InstitutionSubjects = TableRegistry::get('Institution.InstitutionSubjects');
        $educationSubject = TableRegistry::get('Education.EducationSubjects');
        $assessmentId = $options['assessment_id'];
        $classId = $options['class_id'];
        $staffSubject = TableRegistry::get('Institution.InstitutionSubjectStaff');
        
        if (isset($options['class_id']) && isset($options['staff_id'])) {
            $classId = $options['class_id'];
            $staffId = $options['staff_id'];
            $query
                    //->contain('EducationSubjects.InstitutionSubjects')
                    ->innerJoin([$ClassSubjects->alias() => $ClassSubjects->table()], [
                        $ClassSubjects->aliasField('institution_class_id') => $classId
                    ])
                    ->leftJoin([$InstitutionSubjects->alias() => $InstitutionSubjects->table()], [
                        $InstitutionSubjects->aliasField('id = ') . $ClassSubjects->aliasField('institution_subject_id'),
                        $InstitutionSubjects->aliasField('education_subject_id = ') . $this->aliasField('education_subject_id'),
                    ])
                    ->leftJoin(
                        [$staffSubject->alias() => $staffSubject->table()],
                        [
                            $staffSubject->aliasField('institution_subject_id = ') . $ClassSubjects->aliasField('institution_subject_id'),
                        ])
                    ->select([
                        $this->aliasField('education_subject_id'),
                        $this->aliasField('id'),
                        $this->aliasField('assessment_id'),
                        $this->aliasField('weight'),
                        $this->aliasField('classification'),
                        $InstitutionSubjects->aliasField('education_subject_id'),
                        $InstitutionSubjects->aliasField('id'),
                        $InstitutionSubjects->aliasField('name'),
                        $educationSubject->aliasField('id'),
                    ])
                    ->where([
                        $this->aliasField('assessment_id') => $assessmentId,
                        $InstitutionSubjects->aliasField('institution_id') => $institutionId,
                        $InstitutionSubjects->aliasField('academic_period_id') => $academinPeriod,
                        $staffSubject->aliasField('staff_id') => $staffId,
                    ]);
                
                return $query;
            
        }
    }

    public function findAssessmentItemsInClass(Query $query, array $options)
    {
        $ClassSubjects = TableRegistry::get('Institution.InstitutionClassSubjects');
        $InstitutionSubjects = TableRegistry::get('Institution.InstitutionSubjects');
        $assessmentId = $options['assessment_id'];
        $classId = $options['class_id'];

        $query
            ->contain('EducationSubjects')
            ->innerJoin([$ClassSubjects->alias() => $ClassSubjects->table()], [
                $ClassSubjects->aliasField('institution_class_id') => $classId
            ])
            ->innerJoin([$InstitutionSubjects->alias() => $InstitutionSubjects->table()], [
                $InstitutionSubjects->aliasField('id = ') . $ClassSubjects->aliasField('institution_subject_id'),
                $InstitutionSubjects->aliasField('education_subject_id = ') . $this->aliasField('education_subject_id'),
            ])
            ->where([$this->aliasField('assessment_id') => $assessmentId])
            ->order(['EducationSubjects.order', 'EducationSubjects.code', 'EducationSubjects.name']);

        return $query;
    }

    public function findSubjectNewTab(Query $query, array $options)
    {   
        $loggedInUserId = $options['user']['id'];
        $institutionId =  $options['institution_id'];
        $academinPeriod = $options['academic_period_id'];
        $ClassSubjects = TableRegistry::get('Institution.InstitutionClassSubjects');
        $InstitutionSubjects = TableRegistry::get('Institution.InstitutionSubjects');
        $educationSubject = TableRegistry::get('Education.EducationSubjects');
        $Assessments = TableRegistry::get('Assessment.Assessments');
        $assessmentId = $options['assessment_id'];
        $classId = $options['class_id'];
        $staffSubject = TableRegistry::get('Institution.InstitutionSubjectStaff');
                
        $query
                    ->contain('EducationSubjects')
                    /*POCOR-6183 Starts*/
                    ->leftJoin([$Assessments->alias() => $Assessments->table()], [
                        $Assessments->aliasField('id = ') . $this->aliasField('assessment_id')
                    ])
                    /*POCOR-6183 Ends*/
                    ->innerJoin([$ClassSubjects->alias() => $ClassSubjects->table()], [
                        $ClassSubjects->aliasField('institution_class_id') => $classId
                    ])
                    ->leftJoin([$InstitutionSubjects->alias() => $InstitutionSubjects->table()], [
                        $InstitutionSubjects->aliasField('id = ') . $ClassSubjects->aliasField('institution_subject_id'),
                        $InstitutionSubjects->aliasField('education_subject_id = ') . $this->aliasField('education_subject_id'),
                        $InstitutionSubjects->aliasField('education_grade_id = ') . $Assessments->aliasField('education_grade_id') //POCOR-6183
                    ])
                    ->select([
                        $this->aliasField('education_subject_id'),
                        $this->aliasField('id'),
                        $this->aliasField('assessment_id'),
                        $this->aliasField('weight'),
                        $this->aliasField('classification'),
                        $InstitutionSubjects->aliasField('education_subject_id'),
                        $InstitutionSubjects->aliasField('id'),
                        $InstitutionSubjects->aliasField('name'),
                        $educationSubject->aliasField('id')
                    ])
                    ->where([
                        $this->aliasField('assessment_id') => $assessmentId,
                        $InstitutionSubjects->aliasField('institution_id') => $institutionId,
                        $InstitutionSubjects->aliasField('academic_period_id') => $academinPeriod,
                    ])
                   ->order(['EducationSubjects.order', 'EducationSubjects.code', 'EducationSubjects.name']);
            //POCOR-5999 starts
            $query
                ->formatResults(function (ResultSetInterface $results) use($staffSubject, $loggedInUserId) {
                    return $results->map(function ($row) use($staffSubject, $loggedInUserId) {
                        $row['education_subject_id'] = $row->education_subject_id;
                        $row['id'] = $row->id;
                        $row['assessment_id'] = $row->assessment_id;
                        $row['weight'] = $row->weight;
                        $row['classification'] = $row->classification;
                        $row['education_subject'] = [
                            'id' => $row->education_subject->id,
                            'code_name' => " - "
                        ];
                        $row['InstitutionSubjects'] = [
                            'education_subject_id' => $row->InstitutionSubjects['education_subject_id'],
                            'id' => $row->InstitutionSubjects['id'],
                            'name' => $row->InstitutionSubjects['name']
                        ];
                    $subjectId = $row->InstitutionSubjects['id'];
                    $data = $staffSubject->find()
                            ->where([
                                $staffSubject->aliasField('staff_id') => $loggedInUserId,
                                $staffSubject->aliasField('institution_subject_id') => $subjectId
                            ])
                            ->toArray();
                    //checking whether logged in user is admin or not
                    $UsersTable = TableRegistry::get('User.Users');
                    $users = $UsersTable->find()->where([$UsersTable->aliasField('id') => $loggedInUserId])->first();
                    //POCOR-7432 start
                    $SecurityGroupUsersTable=TableRegistry::get('security_group_users');
                    $SecurityRoleFunTable=TableRegistry::get('security_role_functions');
                    $securityGroupUserData=$SecurityGroupUsersTable->find('all')->where([$SecurityGroupUsersTable->aliasField('security_user_id') => $users->id])->first();
                    if($securityGroupUserData){
                    $SecurityRoleFunData=$SecurityRoleFunTable->find('all')
                                                         ->select(['edit'=>$SecurityRoleFunTable->aliasField('_edit')])
                                                         ->where(
                                                                [
                                                                    $SecurityRoleFunTable->aliasField('security_role_id')=>$securityGroupUserData->security_role_id,
                                                                    $SecurityRoleFunTable->aliasField('security_function_id')=>1015,
                                                                ]
                                                          )
                                                         ->first();
                                                                }
                    //POCOR-7432 end

                    if (!empty($users) && $users->super_admin == 1) {
                        $row['is_editable'] = 1;
                    } else {
                        if (!empty($data)) {
                            $row['is_editable'] = 1;
                        } else {
                            //POCOR-7432 start
                            if($SecurityRoleFunData->edit==1){
                                $row['is_editable'] = 1;
                            }
                            else{
                                $row['is_editable'] = 0;
                            }
                             //POCOR-7432 end
                           
                        }
                    }

                    return $row;
                });
            });
        //POCOR-5999 ends
        return $query;
    }

    public function getSubjects($assessmentId)
    {
        $subjectList = $this
            ->find()
            ->innerJoinWith('EducationSubjects')
            ->where([$this->aliasField('assessment_id') => $assessmentId])
            ->select([
                'assessment_item_id' => $this->aliasField('id'),
                'education_subject_name' => 'EducationSubjects.name',
                'subject_id' => $this->aliasField('education_subject_id'),
                'subject_weight' => $this->aliasField('weight'),
            ])
            ->order(['EducationSubjects.order'])
            ->hydrate(false)
            ->toArray();
        return $subjectList;
    }

    public function getAssessmentItemSubjects($assessmentId)
    {
        $subjectList = $this
            ->find()
            ->matching('EducationSubjects')
            ->where([$this->aliasField('assessment_id') => $assessmentId])
            ->select([
                'assessment_item_id' => $this->aliasField('id'),
                'education_subject_id' => 'EducationSubjects.id',
                'education_subject_name' => $this->find()->func()->concat([
                    'EducationSubjects.code' => 'literal',
                    " - ",
                    'EducationSubjects.name' => 'literal'
                ])
            ])
            ->order(['EducationSubjects.order'])
            ->hydrate(false)
            ->toArray();
        return $subjectList;
    }

    public function afterDelete()
    {
        // delete all AssessmentItemsGradingTypes by education_subject_id and assessment_id
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
       $entity['assessment_items'] = array();
    }
    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
       $entity['assessment_items'] = array();
    }
    
}
