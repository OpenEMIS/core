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
 * Get the Staff special needs details in excel file with specific tabs
 * @author Anand Malvi <anand.malvi@mail.valuecoders.com>
 * @ticket POCOR-6524
 */
class InstitutionStandardStaffSpecialNeedsTable extends AppTable
{
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
            'SpecialNeedsReferrals',
            'SpecialNeedsAssessments',
            'SpecialNeedsServices',
            'SpecialNeedsDevices',
            'SpecialNeedsPlans',
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

        if ( $sheet_tab_name == 'SpecialNeedsReferrals' ) {
            $selectable['referrals_date'] = 'SpecialNeedsReferrals.date';
            $selectable['referrals_file_name'] = 'SpecialNeedsReferrals.file_name';
            $selectable['referrals_comment'] = 'SpecialNeedsReferrals.comment';
            $selectable['referrer_first_name'] = 'ReferralsSecurityUsers.first_name';
            $selectable['referrer_last_name'] = 'ReferralsSecurityUsers.last_name';
            $selectable['referrer_special_needs_referrer_types_name'] = 'SpecialNeedsReferrerTypes.name';
            $selectable['referrer_reason_type'] = 'SpecialNeedsTypes.name';
            $join['SpecialNeedsReferrals'] = [
                'type' => 'inner',
                'table' => 'user_special_needs_referrals',
                'conditions' => [
                    'SpecialNeedsReferrals.security_user_id = ' . $this->aliasField('id'),
                    'SpecialNeedsReferrals.academic_period_id = AcademicPeriod.id',
                ],
            ];
            $join['ReferralsSecurityUsers'] = [
                'type' => 'left',
                'table' => 'security_users',
                'conditions' => [
                    'ReferralsSecurityUsers.id = SpecialNeedsReferrals.referrer_id',
                ],
            ];
            $join['SpecialNeedsReferrerTypes'] = [
                'type' => 'left',
                'table' => 'special_needs_referrer_types',
                'conditions' => [
                    'SpecialNeedsReferrerTypes.id = SpecialNeedsReferrals.special_needs_referrer_type_id',
                ],
            ];
            $join['SpecialNeedsTypes'] = [
                'type' => 'left',
                'table' => 'special_need_types',
                'conditions' => [
                    'SpecialNeedsTypes.id = SpecialNeedsReferrals.reason_type_id',
                ],
            ];
        
        } else if ( $sheet_tab_name == 'SpecialNeedsAssessments' ) {
            $selectable['special_needs_assessments_date'] = 'SpecialNeedsAssessments.date';
            $selectable['special_needs_assessments_file_name'] = 'SpecialNeedsAssessments.file_name';
            $selectable['special_needs_assessments_comment'] = 'SpecialNeedsAssessments.comment';
            $selectable['special_needs_assessments_special_need_type_name'] = 'SpecialNeedsTypesAssessment.name';
            $selectable['special_needs_assessments_special_difficulty_name'] = 'SpecialNeedsDifficulties.name';
            $join['SpecialNeedsAssessments'] = [
                'type' => 'inner',
                'table' => 'user_special_needs_assessments',
                'conditions' => [
                    'SpecialNeedsAssessments.security_user_id = ' . $this->aliasField('id')
                ],
            ];
            $join['SpecialNeedsTypesAssessment'] = [
                'type' => 'left',
                'table' => 'special_need_types',
                'conditions' => [
                    'SpecialNeedsTypesAssessment.id = SpecialNeedsAssessments.special_need_type_id',
                ],
            ];
            $join['SpecialNeedsDifficulties'] = [
                'type' => 'left',
                'table' => 'special_need_difficulties',
                'conditions' => [
                    'SpecialNeedsDifficulties.id = SpecialNeedsAssessments.special_need_difficulty_id',
                ],
            ];

        } else if ( $sheet_tab_name == 'SpecialNeedsServices' ) {
            $selectable['service_organization'] = 'SpecialNeedsServices.organization';
            $selectable['service_description'] = 'SpecialNeedsServices.description';
            $selectable['service_comment'] = 'SpecialNeedsServices.comment';
            $selectable['service_file_name'] = 'SpecialNeedsServices.file_name';
            $selectable['service_type_name'] = 'SpecialNeedsServiceTypes.name';
            $join['SpecialNeedsServices'] = [
                'type' => 'inner',
                'table' => 'user_special_needs_services',
                'conditions' => [
                    'SpecialNeedsServices.security_user_id = ' . $this->aliasField('id')
                ],
            ];
            $join['SpecialNeedsServiceTypes'] = [
                'type' => 'left',
                'table' => 'special_needs_service_types',
                'conditions' => [
                    'SpecialNeedsServiceTypes.id = SpecialNeedsServices.special_needs_service_type_id',
                ],
            ];

        } else if ( $sheet_tab_name == 'SpecialNeedsDevices' ) {
            $selectable['devices_comment'] = 'SpecialNeedsDevices.comment';
            $selectable['devices_type_name'] = 'SpecialNeedsDeviceTypes.name';
            $join['SpecialNeedsDevices'] = [
                'type' => 'inner',
                'table' => 'user_special_needs_devices',
                'conditions' => [
                    'SpecialNeedsDevices.security_user_id = ' . $this->aliasField('id')
                ],
            ];
            $join['SpecialNeedsDeviceTypes'] = [
                'type' => 'left',
                'table' => 'special_needs_device_types',
                'conditions' => [
                    'SpecialNeedsDeviceTypes.id = SpecialNeedsDevices.special_needs_device_type_id',
                ],
            ];

        } else if ( $sheet_tab_name == 'SpecialNeedsPlans' ) {
            $selectable['plans_name'] = 'SpecialNeedsPlans.plan_name';
            $selectable['plans_comment'] = 'SpecialNeedsPlans.comment';
            $selectable['plans_file_name'] = 'SpecialNeedsPlans.file_name';
            $join['SpecialNeedsPlans'] = [
                'type' => 'inner',
                'table' => 'user_special_needs_plans',
                'conditions' => [
                    'SpecialNeedsPlans.security_user_id = ' . $this->aliasField('id')
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
                if ( $sheet_tab_name == 'SpecialNeedsReferrals' ) {
                    $row['referrer_full_name'] = $row['referrer_first_name'] . ' ' .  $row['referrer_last_name'];
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

        if ( $sheet_tab_name == 'SpecialNeedsReferrals' ) {
            $extraField = $this->getSpecialNeedsReferralsTabFields($extraField);
        } else if ( $sheet_tab_name == 'SpecialNeedsAssessments' ) {
            $extraField = $this->getSpecialNeedsAssessmentsTabFields($extraField);
        } else if ( $sheet_tab_name == 'SpecialNeedsServices' ) {
            $extraField = $this->getSpecialNeedsServicesTabFields($extraField);
        } else if ( $sheet_tab_name == 'SpecialNeedsDevices' ) {
            $extraField = $this->getSpecialNeedsDevicesTabFields($extraField);
        } else if ( $sheet_tab_name == 'SpecialNeedsPlans' ) {
            $extraField = $this->getSpecialNeedsPlansTabFields($extraField);
        }

        $fields->exchangeArray($extraField);
    }

    private function getSpecialNeedsServicesTabFields($extraField)
    {
        $extraField = $this->commonFields($extraField);
        $extraField[] = [
            'key'   => 'SpecialNeedsServices.organization',
            'field' => 'service_organization',
            'type'  => 'string',
            'label' => __('Organization'),
        ];
        $extraField[] = [
            'key'   => 'SpecialNeedsServices.description',
            'field' => 'service_description',
            'type'  => 'string',
            'label' => __('Description'),
        ];
        $extraField[] = [
            'key'   => 'SpecialNeedsServices.comment',
            'field' => 'service_comment',
            'type'  => 'string',
            'label' => __('Comment'),
        ];
        $extraField[] = [
            'key'   => 'SpecialNeedsServices.file_name',
            'field' => 'service_file_name',
            'type'  => 'string',
            'label' => __('File Name'),
        ];
        $extraField[] = [
            'key'   => 'SpecialNeedsServiceTypes.name',
            'field' => 'service_type_name',
            'type'  => 'string',
            'label' => __('Special Needs Service Type'),
        ];
        return $extraField;
    }

    private function getSpecialNeedsDevicesTabFields($extraField)
    {
        $extraField = $this->commonFields($extraField);
        $extraField[] = [
            'key'   => 'SpecialNeedsDevices.comment',
            'field' => 'devices_comment',
            'type'  => 'string',
            'label' => __('Comment'),
        ];
        $extraField[] = [
            'key'   => 'SpecialNeedsDeviceTypes.name',
            'field' => 'devices_type_name',
            'type'  => 'string',
            'label' => __('Special Needs Device Type'),
        ];
        return $extraField;
    }

    private function getSpecialNeedsPlansTabFields($extraField)
    {
        $extraField = $this->commonFields($extraField);
        $extraField[] = [
            'key'   => 'SpecialNeedsPlans.plan_name',
            'field' => 'plans_name',
            'type'  => 'string',
            'label' => __('Plan Name'),
        ];
        $extraField[] = [
            'key'   => 'SpecialNeedsPlans.comment',
            'field' => 'plans_comment',
            'type'  => 'string',
            'label' => __('Comment'),
        ];
        $extraField[] = [
            'key'   => 'SpecialNeedsPlans.file_name',
            'field' => 'plans_file_name',
            'type'  => 'string',
            'label' => __('File Name'),
        ];
        return $extraField;
    }

    private function getSpecialNeedsAssessmentsTabFields($extraField)
    {
        $extraField = $this->commonFields($extraField);
        $extraField[] = [
            'key'   => 'SpecialNeedsAssessments.date',
            'field' => 'special_needs_assessments_date',
            'type'  => 'date',
            'label' => __('Date'),
        ];
        $extraField[] = [
            'key'   => 'SpecialNeedsAssessments.file_name',
            'field' => 'special_needs_assessments_file_name',
            'type'  => 'string',
            'label' => __('File Name'),
        ];
        $extraField[] = [
            'key'   => 'SpecialNeedsAssessments.comment',
            'field' => 'special_needs_assessments_comment',
            'type'  => 'string',
            'label' => __('Comment'),
        ];
        $extraField[] = [
            'key'   => 'SpecialNeedsTypesAssessment.name',
            'field' => 'special_needs_assessments_special_need_type_name',
            'type'  => 'string',
            'label' => __('Special Need Type'),
        ];
        $extraField[] = [
            'key'   => 'SpecialNeedsDifficulties.name',
            'field' => 'special_needs_assessments_special_difficulty_name',
            'type'  => 'string',
            'label' => __('Special Need Difficulty'),
        ];
        return $extraField;
    }

    private function getSpecialNeedsReferralsTabFields($extraField)
    {
        $extraField = $this->commonFields($extraField);
        $extraField[] = [
            'key'   => 'referrals_date',
            'field' => 'referrals_date',
            'type'  => 'string',
            'label' => __('Date'),
        ];
        $extraField[] = [
            'key'   => 'referrals_file_name',
            'field' => 'referrals_file_name',
            'type'  => 'string',
            'label' => __('File Name'),
        ];
        $extraField[] = [
            'key'   => 'referrals_comment',
            'field' => 'referrals_comment',
            'type'  => 'string',
            'label' => __('Comment'),
        ];
        $extraField[] = [
            'key'   => 'referrer_full_name',
            'field' => 'referrer_full_name',
            'type'  => 'string',
            'label' => __('Referrer'),
        ];
        $extraField[] = [
            'key'   => 'referrer_special_needs_referrer_types_name',
            'field' => 'referrer_special_needs_referrer_types_name',
            'type'  => 'string',
            'label' => __('Special Needs Referrer Type'),
        ];
        $extraField[] = [
            'key'   => 'referrer_reason_type',
            'field' => 'referrer_reason_type',
            'type'  => 'string',
            'label' => __('Reason Type'),
        ];
        return $extraField;
    }

    private function commonFields($extraField)
    {
        $extraField[] = [
            'key'   => 'academic_year_name',
            'field' => 'academic_year_name',
            'type'  => 'string',
            'label' => __('Academic Period'),
        ];
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
            'label' => __('Staff'),
        ];
        return $extraField;
    }
}
