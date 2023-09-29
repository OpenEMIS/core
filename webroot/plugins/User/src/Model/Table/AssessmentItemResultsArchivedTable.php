<?php
namespace User\Model\Table;

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
    }

    public function beforeAction(Event $event, ArrayObject $extra) {
        $this->field('assessment_grading_option_id', ['visible' => false]);
        $this->field('education_grade_id', ['visible' => false]);
        $this->field('created_user_id', ['visible' => false]);
        $this->field('created', ['visible' => false]);
        // $this->field('old_value', ['visible' => false]);
        // $this->field('new_value', ['visible' => false]);
        // $this->field('created', ['visible' => false]);
        // $this->field('created_user_id', ['visible' => false]);
        // $this->field('academic_period_id', ['visible' => false]);
        // $this->field('model', ['visible' => false]);

        $this->setFieldOrder(['institution_id', 'academic_period_id', 'assessment_id', 'assessment_period_id','education_subject_id','marks']);
        $toolbarButtons = $extra['toolbarButtons'];
        $extra['toolbarButtons']['back'] = [
            'url' => [
                'plugin' => 'Student',
                'controller' => 'Students',
                'action' => 'Results',
                '0' => 'index',
            ],
            'type' => 'button',
            'label' => '<i class="fa kd-back"></i>',
            'attr' => [
                'class' => 'btn btn-xs btn-default',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'title' => __('Back')
            ]
        ];
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        // Setup period options
        $InstitutionStaffAttendances = TableRegistry::get('Staff.InstitutionStaffAttendances');
        $AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');

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

        $periodOptions = $AcademicPeriod->getArchivedYearList($archived_academic_period_arr);
        if (empty($this->request->query['academic_period_id'])) {
            $this->request->query['academic_period_id'] = $AcademicPeriod->getCurrent();
        }
        $selectedPeriod = $this->request->query['academic_period_id'];
        $selectedassessment = $this->request->query['assessment_id'];
        $selectedAssessmentPeriods = $this->request->query['assessment_period_id'];
        $selectedSubject = $this->request->query['education_subject_id'];

        $this->request->query['academic_period_id'] = $selectedPeriod;
        $this->request->query['assessment_id'] = $selectedassessment;
        $this->request->query['assessment_period_id'] = $selectedAssessmentPeriods;
        $this->request->query['education_subject_id'] = $selectedSubject;
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
            // echo "<pre>";print_r($this->request->query);die;
            if(empty($selectedAssessmentPeriods) && empty($selectedassessment) && empty($selectedSubject)){
                $conditions = [
                    $this->aliasField('academic_period_id') => $selectedPeriod,
                    $this->aliasField('institution_id') => $institutionId,
                    ];
            }
            else if($selectedAssessmentPeriods == '-1' && $selectedassessment == "-1" && $selectedSubject == '-1'){
                $conditions = [
                    $this->aliasField('academic_period_id') => $selectedPeriod,
                    $this->aliasField('institution_id') => $institutionId,
                    ];
            }
            else if($selectedAssessmentPeriods == '-1' && $selectedassessment == "-1" && empty($selectedSubject)){
                $conditions = [
                    $this->aliasField('academic_period_id') => $selectedPeriod,
                    $this->aliasField('institution_id') => $institutionId,
                    ];
            }
            else if(empty($selectedAssessmentPeriods) && empty($selectedSubject)){
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
                if(empty($selectedSubject)){
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
                }else if($selectedSubject == '-1'){
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
                        $this->aliasField('education_subject_id') => $selectedSubject,
                        ];
                }
            }else if(empty($selectedassessment)){
                $conditions = [
                    $this->aliasField('academic_period_id') => $selectedPeriod,
                    $this->aliasField('institution_id') => $institutionId,
                    ];
            }else if(!empty($selectedassessment) && $selectedassessment == '-1'){
                if(empty($selectedAssessmentPeriods) && empty($selectedassessment) && $selectedSubject == '-1'){
                    $conditions = [
                        $this->aliasField('academic_period_id') => $selectedPeriod,
                        $this->aliasField('institution_id') => $institutionId,
                        ];
                }
                else if(empty($selectedSubject)){
                    $conditions = [
                        $this->aliasField('academic_period_id') => $selectedPeriod,
                        $this->aliasField('institution_id') => $institutionId,
                        $this->aliasField('assessment_period_id') => $selectedAssessmentPeriods,
                        ];
                }else if($selectedSubject == '-1'){
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
                }elseif ($selectedSubject != '-1'){
                    $conditions = [
                        $this->aliasField('academic_period_id') => $selectedPeriod,
                        $this->aliasField('institution_id') => $institutionId,
                        $this->aliasField('education_subject_id') => $selectedSubject,
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
            $subjectOptions = $EducationSubjects
                ->find('list')
                ->find('visible')
                ->where($subjectConditions)
                ->order([
                    $EducationSubjects->aliasField('order') => 'ASC'
                ])
                ->toArray();
            $subjectOptions = ['-1' => __('All Subjects')] + $subjectOptions;
            $this->advancedSelectOptions($subjectOptions, $selectedSubject);
            $this->controller->set(compact('subjectOptions', 'selectedSubject'));
            
            
            // $InstitutionSubjects = TableRegistry::get('Institution.InstitutionSubjects');
            $query
                ->find('all')
                ->where($conditions);

            $extra['elements']['controls'] = ['name' => 'Institution.Attendance/controls', 'data' => [], 'options' => [], 'order' => 1];
            $extra['elements']['controls'] = ['name' => 'Institution.Assessment/controls', 'data' => [], 'options' => [], 'order' => 1];
        }
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'created_user_id') {
            return __('Last Modified By');
        } else if ($field == 'created') {
            return  __('Last Modified On');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}
