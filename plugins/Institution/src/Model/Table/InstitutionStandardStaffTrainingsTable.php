<?php

namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\Log\Log;

/**
 * Get the Staff Training details in excel file with specific tabs
 * @author Anand Malvi <anand.malvi@mail.valuecoders.com>
 * @ticket POCOR-6548
 */
class InstitutionStandardStaffTrainingsTable extends AppTable
{
    private $_type = [];

    public function initialize(array $config)
    {
        $this->table('security_users');
        parent::initialize($config);

        // Behaviours
        $this->addBehavior('Excel', [
            'excludes' => [],
            'pages' => false,
            'autoFields' => false
        ]);
        $this->addBehavior('Report.ReportList');

        $this->_type = [
            'CATALOGUE' => __('Course Catalogue'),
            'NEED' => __('Need Category'),
        ];
    }

    public function beforeAction(Event $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('feature', ['select' => false]);
        $this->ControllerAction->field('format');
        $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);

        $controllerName = $this->controller->name;
        $institutions_crumb = __('Institutions');
        $parent_crumb       = __('Statistics');
		$reportName         = __('Standard');
        
        //# START: Crumb
        $this->Navigation->removeCrumb($this->getHeader($this->alias));
        $this->Navigation->addCrumb($institutions_crumb . ' ' . $parent_crumb);
        //# END: Crumb
        $this->controller->set('contentHeader', __($institutions_crumb) . ' ' . $parent_crumb . ' - ' . $reportName);
    }

    public function addBeforeAction(Event $event)
    {
        $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);
    }

    public function onUpdateFieldFormat(Event $event, array $attr, $action, Request $request)
    {
        $session = $this->request->session();
        $institution_id = $session->read('Institution.Institutions.id');
        $request->data[$this->alias()]['current_institution_id'] = $institution_id;
        $request->data[$this->alias()]['institution_id'] = $institution_id;
        if ($action == 'add') {
            $attr['value'] = 'xlsx';
            $attr['attr']['value'] = 'Excel';
            $attr['type'] = 'readonly';
            return $attr;
        }
    }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request)
    {
        $options = $options = $this->controller->getInstitutionStatisticStandardReportFeature();
        $attr['options'] = $options;
        $attr['onChangeReload'] = true;
        if (!(isset($this->request->data[$this->alias()]['feature']))) {
            $option = $attr['options'];
            reset($option);
            $this->request->data[$this->alias()]['feature'] = key($option);
        }
        return $attr;
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if (isset($request->data[$this->alias()]['feature'])) {
            $feature                = $this->request->data[$this->alias()]['feature'];
            $AcademicPeriodTable    = TableRegistry::get('AcademicPeriod.AcademicPeriods');
            $academicPeriodOptions  = $AcademicPeriodTable->getYearList();
            $currentPeriod          = $AcademicPeriodTable->getCurrent();
            $attr['options']        = $academicPeriodOptions;
            $attr['type']           = 'select';
            $attr['select']         = false;
            $attr['onChangeReload'] = true;
            if (empty($request->data[$this->alias()]['academic_period_id'])) {
                $request->data[$this->alias()]['academic_period_id'] = $currentPeriod;
            }
            return $attr;
        }
    }

    public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets)
    {
        $sheet_tabs = [
            'StaffTrainingNeeds',
            'StaffTrainingApplications',
            'StaffTrainingResults',
            'StaffTrainings'
        ];
        foreach($sheet_tabs as $sheet_tab_name) {  
            $sheets[] = [
                'sheetData'   => ['student_tabs_type' => $sheet_tab_name],
                'name'        => $sheet_tab_name,
                'table'       => $this,
                'query'       => $this->find(),
                'orientation' => 'landscape'
            ];
        }
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData           = json_decode($settings['process']['params']);
        $sheetData             = $settings['sheet']['sheetData'];
        $sheet_tab_name        = $sheetData['student_tabs_type'];
        $academicPeriodId      = $requestData->academic_period_id;
        $institutionId         = $requestData->institution_id;
        $selectable            = [];
        $group_by              = [];

        // START: JOINs
        $join = [
            'Institution' => [
                'type' => 'inner',
                'table' => 'institutions',
                'conditions' => [
                    'Institution.id = ' . $institutionId
                ]
            ],
            'AcademicPeriod' => [
                'type' => 'inner',
                'table' => 'academic_periods',
                'conditions' => [
                    'AcademicPeriod.id = ' . $academicPeriodId
                ]
            ],
        ];

        if ( $sheet_tab_name == 'StaffTrainingNeeds' ) {
            $selectable['needs_status'] = 'WorkflowSteps.name';
            $selectable['needs_first_name'] = 'Assignee.first_name';
            $selectable['needs_last_name'] = 'Assignee.last_name';
            $selectable['needs_type'] = 'StaffTrainingNeeds.type';
            $selectable['needs_training_course_code'] = 'TrainingCourses.code';
            $selectable['needs_training_course_name'] = 'TrainingCourses.name';
            $selectable['needs_training_category_name'] = 'TrainingNeedCategories.name';
            $join['StaffTrainingNeeds'] = [
                'type' => 'inner',
                'table' => 'staff_training_needs',
                'conditions' => [
                    'StaffTrainingNeeds.staff_id = ' . $this->aliasField('id')
                ],
            ];
            $join['WorkflowSteps'] = [
                'type' => 'inner',
                'table' => 'workflow_steps',
                'conditions' => [
                    'WorkflowSteps.id = StaffTrainingNeeds.status_id'
                ],
            ];
            $join['Assignee'] = [
                'type' => 'left',
                'table' => 'security_users',
                'conditions' => [
                    'Assignee.id = StaffTrainingNeeds.assignee_id'
                ],
            ];
            $join['TrainingCourses'] = [
                'type' => 'left',
                'table' => 'training_courses',
                'conditions' => [
                    'TrainingCourses.id = StaffTrainingNeeds.training_course_id'
                ],
            ];
            $join['TrainingNeedCategories'] = [
                'type' => 'left',
                'table' => 'training_need_categories',
                'conditions' => [
                    'TrainingNeedCategories.id = StaffTrainingNeeds.training_need_category_id'
                ],
            ];

        } else if ( $sheet_tab_name == 'StaffTrainingApplications' ) {
            $selectable['application_status'] = 'WorkflowSteps.name';
            $selectable['application_course_name'] = 'TrainingCourses.name';
            $selectable['application_level_name'] = 'TrainingLevels.name';
            $selectable['application_field_of_study_name'] = 'TrainingFieldOfStudies.name';
            $selectable['application_credit_hours'] = 'TrainingCourses.credit_hours';
            $selectable['application_training_session'] = 'TrainingSessions.name';
            $join['StaffTrainingApplications'] = [
                'type' => 'inner',
                'table' => 'staff_training_applications',
                'conditions' => [
                    'StaffTrainingApplications.staff_id = ' . $this->aliasField('id'),
                    'StaffTrainingApplications.institution_id = Institution.id'
                ],
            ];
            $join['WorkflowSteps'] = [
                'type' => 'inner',
                'table' => 'workflow_steps',
                'conditions' => [
                    'WorkflowSteps.id = StaffTrainingApplications.status_id'
                ],
            ];
            $join['TrainingSessions'] = [
                'type' => 'left',
                'table' => 'training_sessions',
                'conditions' => [
                    'TrainingSessions.id = StaffTrainingApplications.training_session_id'
                ],
            ];
            $join['TrainingCourses'] = [
                'type' => 'left',
                'table' => 'training_courses',
                'conditions' => [
                    'TrainingCourses.id = TrainingSessions.training_course_id'
                ],
            ];
            $join['TrainingLevels'] = [
                'type' => 'left',
                'table' => 'training_levels',
                'conditions' => [
                    'TrainingLevels.id = TrainingCourses.training_level_id'
                ],
            ];
            $join['TrainingFieldOfStudies'] = [
                'type' => 'left',
                'table' => 'training_field_of_studies',
                'conditions' => [
                    'TrainingFieldOfStudies.id = TrainingCourses.training_field_of_study_id'
                ],
            ];

        } else if ( $sheet_tab_name == 'StaffTrainingResults' ) {
            $selectable['result_training_session'] = 'TrainingSessions.name';
            $selectable['result_status_name'] = 'WorkflowSteps.name';
            $selectable['result_training_course'] = 'TrainingCourses.name';
            $selectable['result_training_provider'] = 'TrainingProviders.name';
            $selectable['result_training_result_type'] = 'TrainingResultTypes.name';
            $selectable['result_result'] = 'StaffTrainingResults.result';
            $join['StaffTrainingResults'] = [
                'type' => 'inner',
                'table' => 'training_session_trainee_results',
                'conditions' => [
                    'StaffTrainingResults.trainee_id = ' . $this->aliasField('id'),
                ],
            ];
            $join['TrainingSessions'] = [
                'type' => 'left',
                'table' => 'training_sessions',
                'conditions' => [
                    'TrainingSessions.id = StaffTrainingResults.training_session_id'
                ],
            ];
            $join['TrainingSessionResults'] = [
                'type' => 'left',
                'table' => 'training_session_results',
                'conditions' => [
                    'TrainingSessionResults.training_session_id = TrainingSessions.id',
                ],
            ];
            $join['WorkflowSteps'] = [
                'type' => 'left',
                'table' => 'workflow_steps',
                'conditions' => [
                    'WorkflowSteps.id = TrainingSessionResults.status_id',
                ],
            ];
            $join['TrainingCourses'] = [
                'type' => 'left',
                'table' => 'training_courses',
                'conditions' => [
                    'TrainingCourses.id = TrainingSessions.training_course_id',
                ],
            ];
            $join['TrainingProviders'] = [
                'type' => 'left',
                'table' => 'training_providers',
                'conditions' => [
                    'TrainingProviders.id = TrainingSessions.training_provider_id',
                ],
            ];
            $join['TrainingResultTypes'] = [
                'type' => 'left',
                'table' => 'training_result_types',
                'conditions' => [
                    'TrainingResultTypes.id = StaffTrainingResults.training_result_type_id',
                ],
            ];

        } else if ( $sheet_tab_name == 'StaffTrainings' ) {
            $selectable['training_code'] = 'StaffTrainings.code';
            $selectable['training_name'] = 'StaffTrainings.name';
            $selectable['training_credit_hours'] = 'StaffTrainings.credit_hours';
            $selectable['training_completed_date'] = 'StaffTrainings.completed_date';
            $selectable['training_staff_training_category'] = 'StaffTrainingCategories.name';
            $selectable['training_field_of_study'] = 'TrainingFieldStudies.name';
            $join['StaffTrainings'] = [
                'type' => 'inner',
                'table' => 'staff_trainings',
                'conditions' => [
                    'StaffTrainings.staff_id = ' . $this->aliasField('id'),
                ],
            ];
            $join['TrainingFieldStudies'] = [
                'type' => 'left',
                'table' => 'training_field_of_studies',
                'conditions' => [
                    'TrainingFieldStudies.id = StaffTrainings.training_field_of_study_id',
                ],
            ];
            $join['StaffTrainingCategories'] = [
                'type' => 'left',
                'table' => 'staff_training_categories',
                'conditions' => [
                    'StaffTrainingCategories.id = StaffTrainings.staff_training_category_id',
                ],
            ]; 
        }

        $query->join($join);
        // END: JOINs
        
        // START : Selectable fields
        $selectable['institution_code']    = 'Institution.code';
        $selectable['institution_name']    = 'Institution.name';
        $selectable['openemis_no']         = $this->aliasField('openemis_no');
        $selectable['security_user_id']    = $this->aliasField('id');
        $selectable['first_name']          = $this->aliasField('first_name');
        $selectable['last_name']           = $this->aliasField('last_name');
        $selectable['academic_year_name']  = 'AcademicPeriod.name';
        $query->select($selectable);
        // END : Selectable fields

        $query->where([
            'AcademicPeriod.id' => $academicPeriodId,
            'Institution.id' => $institutionId,
            $this->aliasField('is_staff') => 1,
        ]);
        
        $query->group($group_by)->order([$this->aliasField('first_name'), $this->aliasField('last_name')]);

        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) use ($sheet_tab_name)
        {
            return $results->map(function ($row) use ($sheet_tab_name)
            {
                if ( $sheet_tab_name == 'StaffTrainingNeeds' ) {
                    $row['needs_type']            = ($row['needs_type'] == 'CATALOGUE') ? $this->_type['CATALOGUE'] : $this->_type['NEED'];
                    $row['needs_asssignee_name']  = $row['needs_first_name'] . ' ' .  $row['needs_last_name'];
                    $row['needs_training_course'] = $row['needs_training_course_code'] . ' - ' . $row['needs_training_course_name'];
                }
                $row['security_user_full_name'] = $row['first_name'] . ' ' .  $row['last_name'];
                return $row;
            });
        });
    }

    /**
     * Generate the all Header for sheet tab wise
     */
    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $IdentityType         = TableRegistry::get('FieldOption.IdentityTypes');
        $identity             = $IdentityType->getDefaultEntity();
        $settings['identity'] = $identity;
        $sheetData            = $settings['sheet']['sheetData'];
        $sheet_tab_name       = $sheetData['student_tabs_type'];
        $extraField           = [];

        if ( $sheet_tab_name == 'StaffTrainingNeeds' ) {
            $extraField = $this->getStaffTrainingNeedsTabFields($extraField);
        } else if ( $sheet_tab_name == 'StaffTrainingApplications' ) {
            $extraField = $this->getStaffTrainingApplicationsTabFields($extraField);
        } else if ( $sheet_tab_name == 'StaffTrainingResults' ) {
            $extraField = $this->getStaffTrainingResultsTabFields($extraField);
        } else if ( $sheet_tab_name == 'StaffTrainings' ) {
            $extraField = $this->getStaffTrainingsTabFields($extraField);
        }

        $fields->exchangeArray($extraField);
    }

    private function getStaffTrainingsTabFields($extraField)
    {
        $extraField[] = [
            'key'   => 'training_code',
            'field' => 'training_code',
            'type'  => 'string',
            'label' => __('Course Code'),
        ];
        $extraField[] = [
            'key'   => 'training_name',
            'field' => 'training_name',
            'type'  => 'string',
            'label' => __('Course Name'),
        ];
        $extraField[] = [
            'key'   => 'openemis_no',
            'field' => 'openemis_no',
            'type'  => 'string',
            'label' => __('OpenEMIS ID'),
        ];
        $extraField[] = [
            'key'   => 'security_user_full_name',
            'field' => 'security_user_full_name',
            'type'  => 'string',
            'label' => __('Name'),
        ];
        $extraField[] = [
            'key'   => 'training_credit_hours',
            'field' => 'training_credit_hours',
            'type'  => 'string',
            'label' => __('Credit Hours'),
        ];
        $extraField[] = [
            'key'   => 'training_completed_date',
            'field' => 'training_completed_date',
            'type'  => 'string',
            'label' => __('Completed Date'),
        ];
        $extraField[] = [
            'key'   => 'training_staff_training_category',
            'field' => 'training_staff_training_category',
            'type'  => 'string',
            'label' => __('Staff Training Category'),
        ];
        $extraField[] = [
            'key'   => 'training_field_of_study',
            'field' => 'training_field_of_study',
            'type'  => 'string',
            'label' => __('Training Field Of Study'),
        ];
        return $extraField;
    }

    private function getStaffTrainingResultsTabFields($extraField)
    {
        $extraField = $this->commonFields($extraField);
        $extraField[] = [
            'key'   => 'result_status_name',
            'field' => 'result_status_name',
            'type'  => 'string',
            'label' => __('Status'),
        ];
        $extraField[] = [
            'key'   => 'openemis_no',
            'field' => 'openemis_no',
            'type'  => 'string',
            'label' => __('OpenEMIS ID'),
        ];
        $extraField[] = [
            'key'   => 'security_user_full_name',
            'field' => 'security_user_full_name',
            'type'  => 'string',
            'label' => __('Name'),
        ];
        $extraField[] = [
            'key'   => 'result_training_course',
            'field' => 'result_training_course',
            'type'  => 'string',
            'label' => __('Training Course'),
        ];
        $extraField[] = [
            'key'   => 'result_training_provider',
            'field' => 'result_training_provider',
            'type'  => 'string',
            'label' => __('Training Provider'),
        ];
        $extraField[] = [
            'key'   => 'result_training_session',
            'field' => 'result_training_session',
            'type'  => 'string',
            'label' => __('Training Session'),
        ];
        $extraField[] = [
            'key'   => 'result_training_result_type',
            'field' => 'result_training_result_type',
            'type'  => 'string',
            'label' => __('Training Result Type'),
        ];
        $extraField[] = [
            'key'   => 'result_result',
            'field' => 'result_result',
            'type'  => 'string',
            'label' => __('Result'),
        ];
        return $extraField;
    }

    private function getStaffTrainingApplicationsTabFields($extraField)
    {
        $extraField = $this->commonFields($extraField);
        $extraField[] = [
            'key'   => 'application_status',
            'field' => 'application_status',
            'type'  => 'string',
            'label' => __('Status'),
        ];
        $extraField[] = [
            'key'   => 'application_course_name',
            'field' => 'application_course_name',
            'type'  => 'string',
            'label' => __('Course'),
        ];
        $extraField[] = [
            'key'   => 'openemis_no',
            'field' => 'openemis_no',
            'type'  => 'string',
            'label' => __('OpenEMIS ID'),
        ];
        $extraField[] = [
            'key'   => 'security_user_full_name',
            'field' => 'security_user_full_name',
            'type'  => 'string',
            'label' => __('Name'),
        ];
        $extraField[] = [
            'key'   => 'application_level_name',
            'field' => 'application_level_name',
            'type'  => 'string',
            'label' => __('Training Level'),
        ];
        $extraField[] = [
            'key'   => 'application_field_of_study_name',
            'field' => 'application_field_of_study_name',
            'type'  => 'string',
            'label' => __('Field Of Study'),
        ];
        $extraField[] = [
            'key'   => 'application_credit_hours',
            'field' => 'application_credit_hours',
            'type'  => 'string',
            'label' => __('Credit Hours'),
        ];
        $extraField[] = [
            'key'   => 'application_training_session',
            'field' => 'application_training_session',
            'type'  => 'string',
            'label' => __('Training Session'),
        ];
        return $extraField;
    }

    private function getStaffTrainingNeedsTabFields($extraField)
    {
        $extraField = $this->commonFields($extraField);
        $extraField[] = [
            'key'   => 'needs_status',
            'field' => 'needs_status',
            'type'  => 'string',
            'label' => __('Status'),
        ];
        $extraField[] = [
            'key'   => 'needs_asssignee_name',
            'field' => 'needs_asssignee_name',
            'type'  => 'string',
            'label' => __('Assignee'),
        ];
        $extraField[] = [
            'key'   => 'openemis_no',
            'field' => 'openemis_no',
            'type'  => 'string',
            'label' => __('OpenEMIS ID'),
        ];
        $extraField[] = [
            'key'   => 'security_user_full_name',
            'field' => 'security_user_full_name',
            'type'  => 'string',
            'label' => __('Name'),
        ];
        $extraField[] = [
            'key'   => 'needs_type',
            'field' => 'needs_type',
            'type'  => 'string',
            'label' => __('Type'),
        ];
        $extraField[] = [
            'key'   => 'needs_training_course',
            'field' => 'needs_training_course',
            'type'  => 'string',
            'label' => __('Training Course'),
        ];
        $extraField[] = [
            'key'   => 'needs_training_category_name',
            'field' => 'needs_training_category_name',
            'type'  => 'string',
            'label' => __('Training Need Category'),
        ];
        return $extraField;
    }

    private function commonFields($extraField)
    {
        $extraField[] = [
            'key'   => 'institution_code',
            'field' => 'institution_code',
            'type'  => 'string',
            'label' => __('Institution Code'),
        ];
        $extraField[] = [
            'key'   => 'institution_name',
            'field' => 'institution_name',
            'type'  => 'string',
            'label' => __('Institution Name'),
        ];
        return $extraField;
    }
}
