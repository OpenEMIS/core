<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\I18n\Date;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Validation\Validator;

use App\Model\Traits\OptionsTrait;
use App\Model\Table\ControllerActionTable;

use Page\Traits\EncodingTrait;

class AssessmentItemResultsArchivedTable extends ControllerActionTable 
{
    private $allDayOptions = [];
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('Users',       ['className' => 'User.Users', 'foreignKey'=>'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Assessments', ['className' => 'Assessment.Assessments']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
        $this->belongsTo('AssessmentPeriods', ['className' => 'Assessment.AssessmentPeriods']);
        $this->addBehavior('AcademicPeriod.AcademicPeriod');
        $this->addBehavior('AcademicPeriod.Period');
        $this->addBehavior('Activity');

        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
        $this->toggle('view', false);
        $this->toggle('search', false);
        ini_set("memory_limit", "2048M");
        $this->addBehavior('Excel', [
            'excludes' => ['id'],
            'autoFields' => false
        ]);
    }

    public function beforeAction(Event $event, ArrayObject $extra) {
        $this->field('assessment_grading_option_id', ['visible' => false]);
        $this->field('education_grade_id', ['visible' => false]);
        $this->field('student_id', ['visible' => false]);
        $this->field('created_user_id', ['visible' => false]);
        $this->field('created', ['visible' => false]);
        $this->field('openemis_no', ['sort' => ['field' => 'Users.openemis_no']]);
        $this->field('class');
        $this->field('name');

        $this->setFieldOrder(['institution_id', 'academic_period_id', 'assessment_id', 'assessment_period_id','class','education_subject_id','openemis_no','name', 'marks']);
        $toolbarButtons = $extra['toolbarButtons'];
        // $extra['toolbarButtons']['back'] = [
        //     'url' => [
        //         'plugin' => 'Student',
        //         'controller' => 'Students',
        //         'action' => 'Results',
        //         '0' => 'index',
        //     ],
        //     'type' => 'button',
        //     'label' => '<i class="fa kd-back"></i>',
        //     'attr' => [
        //         'class' => 'btn btn-xs btn-default',
        //         'data-toggle' => 'tooltip',
        //         'data-placement' => 'bottom',
        //         'escape' => false,
        //         'title' => __('Back')
        //     ]
        // ];
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        // Setup period options
        $InstitutionStaffAttendances = TableRegistry::get('Staff.InstitutionStaffAttendances');
        $AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $InstitutionClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
        $InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');

        $institutionId = $this->Session->read('Institution.Institutions.id');
        if ($this->request->query('user_id') !== null) {
            $staffId = $this->request->query('user_id');
            $this->Session->write('Staff.Staff.id', $staffId);
        } else {
            $staffId = $this->Session->read('Staff.Staff.id');
        }
        
        $academic_period_result = $this->find('all', array(
            'fields'=>'academic_period_id',
            'group' => 'academic_period_id'
        ));
        if(!empty($academic_period_result)){
            foreach($academic_period_result AS $academic_period_data){
                $archived_academic_period_arr[] = $academic_period_data['academic_period_id'];
            }
        }
        //POCOR-6799[START]
        if(!empty($archived_academic_period_arr)){
            $periodOptions = $AcademicPeriod->getArchivedYearList($archived_academic_period_arr);
        }
        //POCOR-6799[END]
        if (empty($this->request->query['academic_period_id'])) {
            $this->request->query['academic_period_id'] = $AcademicPeriod->getCurrent();
        }
        $selectedPeriod = $this->request->query['academic_period_id'];
        $selectedassessment = $this->request->query['assessment_id'];
        $selectedAssessmentPeriods = $this->request->query['assessment_period_id'];
        $selectedClassId = $this->request->query['institution_class_id'];

        $this->request->query['academic_period_id'] = $selectedPeriod;
        $this->request->query['assessment_id'] = $selectedassessment;
        $this->request->query['assessment_period_id'] = $selectedAssessmentPeriods;
        $this->request->query['institution_class_id'] = $selectedClassId;
        $this->advancedSelectOptions($periodOptions, $selectedPeriod);

        if ($selectedPeriod != 0) {
            $todayDate = date("Y-m-d");
            $this->controller->set(compact('periodOptions', 'selectedPeriod'));

            //toolbar filter
            //Assessment[Start]
            $Assessments = TableRegistry::get('Assessment.Assessments');
            $assessmentOptions = $Assessments
                ->find('list')
                ->where([$Assessments->aliasField('academic_period_id') => $selectedPeriod])
                ->toArray();
            $assessmentOptions = ['-1' => __('All Assessments')] + $assessmentOptions;
            $this->advancedSelectOptions($assessmentOptions, $selectedassessment);
            $this->controller->set(compact('assessmentOptions', 'selectedAssessment'));
            //Assessment[End]
            if(empty($selectedAssessmentPeriods) && empty($selectedassessment) && empty($selectedClassId)){
                $conditions = [
                    $this->aliasField('academic_period_id') => $selectedPeriod,
                    $this->aliasField('institution_id') => $institutionId,
                    ];
            }
            else if($selectedAssessmentPeriods == '-1' && $selectedassessment == "-1" && $selectedClassId == '-1'){
                $conditions = [
                    $this->aliasField('academic_period_id') => $selectedPeriod,
                    $this->aliasField('institution_id') => $institutionId,
                    ];
            }
            else if($selectedAssessmentPeriods == '-1' && $selectedassessment == "-1" && empty($selectedClassId)){
                $conditions = [
                    $this->aliasField('academic_period_id') => $selectedPeriod,
                    $this->aliasField('institution_id') => $institutionId,
                    ];
            }
            else if(empty($selectedAssessmentPeriods) && empty($selectedClassId)){
                if($selectedassessment == '-1'){
                    $conditions = [
                        $this->aliasField('academic_period_id') => $selectedPeriod,
                        $this->aliasField('institution_id') => $institutionId,
                        ];

                }else if(!empty($selectedassessment)){
                    $conditions = [
                        $this->aliasField('academic_period_id') => $selectedPeriod,
                        $this->aliasField('institution_id') => $institutionId,
                        $this->aliasField('assessment_id') => $selectedassessment,
                        ];
                }else{
                    $conditions = [
                        $this->aliasField('academic_period_id') => $selectedPeriod,
                        $this->aliasField('institution_id') => $institutionId,
                        ];
                }
            }else if(!empty($selectedAssessmentPeriods) && $selectedAssessmentPeriods == '-1'){
                if(empty($selectedassessment)){
                    $conditions = [
                        $this->aliasField('academic_period_id') => $selectedPeriod,
                        $this->aliasField('institution_id') => $institutionId,
                        ];
                }else{
                    $conditions = [
                        $this->aliasField('academic_period_id') => $selectedPeriod,
                        $this->aliasField('institution_id') => $institutionId,
                        $this->aliasField('assessment_id') => $selectedassessment,
                        ];
                }
            }else if(!empty($selectedAssessmentPeriods) && $selectedAssessmentPeriods != '-1'){
                if(empty($selectedClassId)){
                    if($selectedassessment == '-1'){
                        $conditions = [
                            $this->aliasField('academic_period_id') => $selectedPeriod,
                            $this->aliasField('institution_id') => $institutionId,
                            $this->aliasField('assessment_period_id') => $selectedAssessmentPeriods,
                            ];
                    }else{
                        $conditions = [
                            $this->aliasField('academic_period_id') => $selectedPeriod,
                            $this->aliasField('institution_id') => $institutionId,
                            $this->aliasField('assessment_id') => $selectedassessment,
                            $this->aliasField('assessment_period_id') => $selectedAssessmentPeriods,
                            ];
                    }
                }else if($selectedClassId == '-1'){
                    $conditions = [
                        $this->aliasField('academic_period_id') => $selectedPeriod,
                        $this->aliasField('institution_id') => $institutionId,
                        $this->aliasField('assessment_period_id') => $selectedAssessmentPeriods,
                        ];
                }else{
                    $conditions = [
                        $this->aliasField('academic_period_id') => $selectedPeriod,
                        $this->aliasField('institution_id') => $institutionId,
                        $this->aliasField('assessment_period_id') => $selectedAssessmentPeriods,
                        $InstitutionClassStudents->aliasField('institution_class_id') => $selectedClassId,
                        ];
                }
            }else if(empty($selectedassessment)){
                $conditions = [
                    $this->aliasField('academic_period_id') => $selectedPeriod,
                    $this->aliasField('institution_id') => $institutionId,
                    ];
            }else if(!empty($selectedassessment) && $selectedassessment == '-1'){
                if(empty($selectedAssessmentPeriods) && empty($selectedassessment) && $selectedClassId == '-1'){
                    $conditions = [
                        $this->aliasField('academic_period_id') => $selectedPeriod,
                        $this->aliasField('institution_id') => $institutionId,
                        ];
                }
                else if(empty($selectedClassId)){
                    $conditions = [
                        $this->aliasField('academic_period_id') => $selectedPeriod,
                        $this->aliasField('institution_id') => $institutionId,
                        $this->aliasField('assessment_period_id') => $selectedAssessmentPeriods,
                        ];
                }else if($selectedClassId == '-1'){
                    if(empty($selectedAssessmentPeriods) && $selectedassessment == '-1'){
                        $conditions = [
                            $this->aliasField('academic_period_id') => $selectedPeriod,
                            $this->aliasField('institution_id') => $institutionId,
                            ];
                    }else{
                        $conditions = [
                            $this->aliasField('academic_period_id') => $selectedPeriod,
                            $this->aliasField('institution_id') => $institutionId,
                            $this->aliasField('assessment_period_id') => $selectedAssessmentPeriods,
                            ];
                    }
                }elseif ($selectedClassId != '-1'){
                    $conditions = [
                        $this->aliasField('academic_period_id') => $selectedPeriod,
                        $this->aliasField('institution_id') => $institutionId,
                        $InstitutionClassStudents->aliasField('institution_class_id') => $selectedClassId,
                        ];
                }else{
                    $conditions = [
                        $this->aliasField('academic_period_id') => $selectedPeriod,
                        $this->aliasField('institution_id') => $institutionId,
                        ];
                }
            }
            $AssessmentPeriods = TableRegistry::get('Assessment.AssessmentPeriods');
            if($selectedassessment != '-1'){
                $AssessmentPeriodsconditions = [
                    $AssessmentPeriods->aliasField('assessment_id') => $selectedPeriod
                ];
            }else{
                $AssessmentPeriodsconditions = [];
            }
            $AssessmentPeriodsOptions = $AssessmentPeriods
                ->find('list')
                ->where($AssessmentPeriodsconditions)
                ->toArray();
            $AssessmentPeriodsOptions = ['-1' => __('All Assessment Periods')] + $AssessmentPeriodsOptions;
            $this->advancedSelectOptions($AssessmentPeriodsOptions, $selectedAssessmentPeriods);
            $this->controller->set(compact('AssessmentPeriodsOptions', 'selectedAssessmentPeriods'));

            $EducationSubjects = TableRegistry::get('Education.EducationSubjects');
            // $subjectOptions = $EducationSubjects
            //     ->find('list')
            //     ->find('visible')
            //     ->where($subjectConditions)
            //     ->order([
            //         $EducationSubjects->aliasField('order') => 'ASC'
            //     ])
            //     ->toArray();
            // $subjectOptions = ['-1' => __('All Subjects')] + $subjectOptions;
            // $this->advancedSelectOptions($subjectOptions, $selectedSubject);
            // $this->controller->set(compact('subjectOptions', 'selectedSubject'));
            $Classes = TableRegistry::get('Institution.InstitutionClasses');
            $selectedAcademicPeriodId = $params['academic_period_id'];

            $classOptions = $Classes->getClassOptions($selectedPeriod, $institutionId);
            if (!empty($classOptions)) {
                $classOptions = [0 => 'All Classes'] + $classOptions;
            }
            $selectedClassId = $this->queryString('institution_class_id', $classOptions);
            $this->advancedSelectOptions($classOptions, $selectedClassId);
            $this->controller->set(compact('classOptions', 'selectedClassId'));
            
            $ArchivedUser = TableRegistry::get('User.Users');
            $query
            ->select([
                'academic_period_id' => $this->aliasField('academic_period_id'),
                'assessment_id' => $this->aliasField('assessment_id'),
                'assessment_period_id' => $this->aliasField('assessment_period_id'),
                'education_subject_id' => $this->aliasField('education_subject_id'),
                'institution_class_id' => $InstitutionClassStudents->aliasField('institution_class_id'),
                'marks' => $this->aliasField('marks'),
                'class' => $InstitutionClasses->aliasField('name'),
                'name' => $ArchivedUser->find()->func()->concat([
                    'Users.first_name' => 'literal',
                    " ",
                    'Users.last_name' => 'literal'
                ]),
                'openemis_no' => $ArchivedUser->aliasField('openemis_no')
                ])
                ->innerJoin(
                    [$InstitutionClassStudents->alias() => $InstitutionClassStudents->table()], [
                        $this->aliasField('student_id = ') . $InstitutionClassStudents->aliasField('student_id')
                    ]
                )
                ->innerJoin(
                    [$InstitutionClasses->alias() => $InstitutionClasses->table()], [
                        $InstitutionClassStudents->aliasField('institution_class_id = ') . $InstitutionClasses->aliasField('id'),
                        'AND' => [
                            $this->aliasField('education_grade_id = ') . $InstitutionClassStudents->aliasField('education_grade_id'),
                            $this->aliasField('academic_period_id = ') . $InstitutionClassStudents->aliasField('academic_period_id')
                        ]
                    ]
                )
                ->where($conditions);

            $extra['elements']['controls'] = ['name' => 'Institution.Attendance/controls', 'data' => [], 'options' => [], 'order' => 1];
            $extra['elements']['controls'] = ['name' => 'Institution.Assessment/controls', 'data' => [], 'options' => [], 'order' => 1];
        }
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {
                
                $UserData = TableRegistry::get('User.Users');
                $UserDataRow = $UserData
                            ->find()
                            ->where([$UserData->alias('id')=>$row->student_id])
                            ->first();

                $firstName = $this->Auth->user('first_name');
                $lastName = $this->Auth->user('last_name');
                $name = $UserDataRow->first_name . " " . $UserDataRow->last_name;
                $row['name'] = $name;
                $row['openemis_no'] = $UserDataRow->openemis_no;
                return $row;
            });
        });
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $newFields = [];

        $newFields[] = [
            'key' => '',
            'field' => 'academic_period_id',
            'type' => 'integer',
            'label' => 'Academic Period',
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'assessment_id',
            'type' => 'integer',
            'label' => 'Assessment',
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'assessment_period_id',
            'type' => 'integer',
            'label' => 'Assessment Period',
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'education_subject_id',
            'type' => 'integer',
            'label' => 'Subject',
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => 'OpenEMIS ID',
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'name',
            'type' => 'integer',
            'label' => 'Name',
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'marks',
            'type' => 'string',
            'label' => 'Mark'
        ];

        // $newFields[] = [
        //     'key' => 'Users.date_of_birth',
        //     'field' => 'dob',
        //     'type' => 'date',
        //     'label' => '',
        // ];

        // $newFields[] = [
        //     'key' => 'Examinations.education_grade',
        //     'field' => 'education_grade',
        //     'type' => 'string',
        //     'label' => '',
        // ];

        // $newFields[] = [
        //     'key' => 'InstitutionExaminationStudents.institution_id',
        //     'field' => 'institution_id',
        //     'type' => 'integer',
        //     'label' => '',
        // ];

        $fields->exchangeArray($newFields);
    }

    public function onGetOpenemisNo(Event $event, Entity $entity)
    {
        return $entity->user->openemis_no;
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'education_subject_id') {
            return __('Subject');
        } else if ($field == 'name') {
            return  __('Name');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}
