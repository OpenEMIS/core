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
 * Get the Student Health details in excel file with specific tabs
 * @author Anand Malvi <anand.malvi@mail.valuecoders.com>
 */
class StudentHealthsTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('security_users');
        parent::initialize($config);

        // Behaviours
        $this->addBehavior('Excel', [
            'excludes' => ['is_student', 'photo_name', 'is_staff', 'is_guardian',  'super_admin', 'status'],
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
        $options = $this->controller->getInstitutionStatisticStandardReportFeature();
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

    /**
     * Generating the tabs of sheet
     */
    public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets)
    {
        $sheet_tabs = [
            'Overview',
            'Allergies',
            'Consultations',
            'Families',
            'Histories',
            'Vaccinations',
            'Medications',
            'Tests',
            'UserBodyMasses',
            'UserInsurances'
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
        $group_by[]            = $this->aliasField('openemis_no');
        //$group_by[]            = 'InstitutionStudent.student_status_id';

        // START: JOINs & dynamically fields
        $join = [
            'InstitutionStudent' => [
                'type' => 'inner',
                'table' => 'institution_students',
                'conditions' => ['InstitutionStudent.student_id = ' . $this->aliasField('id')],
            ],
            'Institution' => [
                'type' => 'inner',
                'table' => 'institutions',
                'conditions' => ['Institution.id = InstitutionStudent.institution_id']
            ],
            'AcademicPeriod' => [
                'type' => 'inner',
                'table' => 'academic_periods',
                'conditions' => ['AcademicPeriod.id = InstitutionStudent.academic_period_id']
            ],
        ];

        if ( $sheet_tab_name == 'Overview' ) {
            // $group_by = [];
            // $group_by[] = 'Healths.security_user_id';
            $selectable['overview_blood_type']       = 'Healths.blood_type';
            $selectable['overview_doctor_name']      = 'Healths.doctor_name';
            $selectable['overview_doctor_contact']   = 'Healths.doctor_contact';
            $selectable['overview_medical_facility'] = 'Healths.medical_facility';
            $selectable['overview_health_insurance'] = 'Healths.health_insurance';
            $selectable['overview_health_file_name'] = 'Healths.file_name';
            $join['Healths'] = [
                'type' => 'inner',
                'table' => 'user_healths',
                'conditions' => ['Healths.security_user_id = ' . $this->aliasField('id')],
            ];

        } else if ( $sheet_tab_name == 'Allergies' ) {
            // $group_by[] = 'Allergies.id';
            $selectable['allergies_description'] = 'Allergies.description';
            $selectable['allergies_severe']      = 'Allergies.severe';
            $selectable['allergies_comment']     = 'Allergies.comment';
            $selectable['allergies_type_name']   = 'AllergyTypes.name';
            $selectable['allergies_file_name']   = 'Allergies.file_name';
            $join['Allergies'] = [
                'type' => 'inner',
                'table' => 'user_health_allergies',
                'conditions' => ['Allergies.security_user_id = ' . $this->aliasField('id')],
            ];
            $join['AllergyTypes'] = [
                'type' => 'inner',
                'table' => 'health_allergy_types',
                'conditions' => ['AllergyTypes.id = Allergies.health_allergy_type_id'],
            ];

        } else if ( $sheet_tab_name == 'Consultations' ) {
            // $group_by[] = 'Consultations.id';
            $selectable['consultations_date']        = 'Consultations.date';
            $selectable['consultations_description'] = 'Consultations.description';
            $selectable['consultations_treatment']   = 'Consultations.treatment';
            $selectable['consultations_type_name']   = 'ConsultationTypes.name';
            $selectable['consultations_file_name']   = 'Consultations.file_name';
            $join['Consultations'] = [
                'type' => 'inner',
                'table' => 'user_health_consultations',
                'conditions' => ['Consultations.security_user_id = ' . $this->aliasField('id')],
            ];
            $join['ConsultationTypes'] = [
                'type' => 'inner',
                'table' => 'health_consultation_types',
                'conditions' => ['ConsultationTypes.id = Consultations.health_consultation_type_id'],
            ];

        } else if ( $sheet_tab_name == 'Families' ) {
            // $group_by[] = 'Families.id';
            $selectable['families_current']           = 'Families.current';
            $selectable['families_comment']           = 'Families.comment';
            $selectable['families_relationship_name'] = 'Relationships.name';
            $selectable['families_condition_name']    = 'Conditions.name';
            $selectable['families_file_name']         = 'Families.file_name';
            $join['Families'] = [
                'type' => 'inner',
                'table' => 'user_health_families',
                'conditions' => ['Families.security_user_id = ' . $this->aliasField('id')],
            ];
            $join['Relationships'] = [
                'type' => 'inner',
                'table' => 'health_relationships',
                'conditions' => ['Relationships.id = Families.health_relationship_id'],
            ];
            $join['Conditions'] = [
                'type' => 'inner',
                'table' => 'health_conditions',
                'conditions' => ['Conditions.id = Families.health_condition_id'],
            ];

        } else if ( $sheet_tab_name == 'Histories' ) {
            // $group_by[] = 'Histories.id';
            $selectable['histories_current'] = 'Histories.current';
            $selectable['histories_comment'] = 'Histories.comment';
            $selectable['histories_health_name'] = 'Conditions.name';
            $selectable['histories_file_name'] = 'Histories.file_name';
            $join['Histories'] = [
                'type' => 'inner',
                'table' => 'user_health_histories',
                'conditions' => ['Histories.security_user_id = ' . $this->aliasField('id')],
            ];
            $join['Conditions'] = [
                'type' => 'inner',
                'table' => 'health_conditions',
                'conditions' => ['Conditions.id = Histories.health_condition_id'],
            ];

        } else if ( $sheet_tab_name == 'Vaccinations' ) {
            // $group_by[] = 'Immunizations.id';
            $selectable['immunizations_date']      = 'Immunizations.date';
            $selectable['immunizations_type_name'] = 'ImmunizationTypes.name';
            $selectable['immunizations_comment']   = 'Immunizations.comment';
            $selectable['immunizations_file_name'] = 'Immunizations.file_name';
            $join['Immunizations'] = [
                'type' => 'inner',
                'table' => 'user_health_immunizations',
                'conditions' => ['Immunizations.security_user_id = ' . $this->aliasField('id')],
            ];
            $join['ImmunizationTypes'] = [
                'type' => 'inner',
                'table' => 'health_immunization_types',
                'conditions' => ['ImmunizationTypes.id = Immunizations.health_immunization_type_id'],
            ];

        } else if ( $sheet_tab_name == 'Medications' ) {
            // $group_by[] = 'Medications.id';
            $selectable['medications_name']      = 'Medications.name';
            $selectable['medications_dosage']      = 'Medications.dosage';
            $selectable['medications_start_date']      = 'Medications.start_date';
            $selectable['medications_end_date']      = 'Medications.end_date';
            $selectable['medications_file_name']      = 'Medications.file_name';
            $join['Medications'] = [
                'type' => 'inner',
                'table' => 'user_health_medications',
                'conditions' => ['Medications.security_user_id = ' . $this->aliasField('id')],
            ];

        } else if ( $sheet_tab_name == 'Tests' ) {
            // $group_by[] = 'Tests.id';
            $selectable['tests_date']      = 'Tests.date';
            $selectable['tests_result']    = 'Tests.result';
            $selectable['tests_comment']   = 'Tests.comment';
            $selectable['tests_type_name'] = 'TestTypes.name';
            $selectable['tests_file_name'] = 'Tests.file_name';
            $join['Tests'] = [
                'type' => 'inner',
                'table' => 'user_health_tests',
                'conditions' => ['Tests.security_user_id = ' . $this->aliasField('id')],
            ];
            $join['TestTypes'] = [
                'type' => 'inner',
                'table' => 'health_test_types',
                'conditions' => ['TestTypes.id = Tests.health_test_type_id'],
            ];

        } else if ( $sheet_tab_name == 'UserBodyMasses' ) {
            // $group_by[] = 'UserBodyMasses.id';
            $selectable['masses_date']            = 'UserBodyMasses.date';
            $selectable['masses_height']          = 'UserBodyMasses.height';
            $selectable['masses_weight']          = 'UserBodyMasses.weight';
            $selectable['masses_body_mass_index'] = 'UserBodyMasses.body_mass_index';
            $join['UserBodyMasses'] = [
                'type' => 'inner',
                'table' => 'user_body_masses',
                'conditions' => ['UserBodyMasses.security_user_id = ' . $this->aliasField('id')],
            ];

        } else if ( $sheet_tab_name == 'UserInsurances' ) {
            // $group_by[] = 'UserInsurances.id';
            $selectable['user_insurances_start_date']    = 'UserInsurances.start_date';
            $selectable['user_insurances_end_date']      = 'UserInsurances.end_date';
            $selectable['user_insurances_provider_name'] = 'InsuranceProviders.name';
            $selectable['user_insurances_type_name']     = 'InsuranceTypes.name';
            $join['UserInsurances'] = [
                'type' => 'inner',
                'table' => 'user_insurances',
                'conditions' => ['UserInsurances.security_user_id = ' . $this->aliasField('id')],
            ];
            $join['InsuranceProviders'] = [
                'type' => 'inner',
                'table' => 'insurance_providers',
                'conditions' => ['InsuranceProviders.id = UserInsurances.insurance_provider_id'],
            ];
            $join['InsuranceTypes'] = [
                'type' => 'inner',
                'table' => 'insurance_types',
                'conditions' => ['InsuranceTypes.id = UserInsurances.insurance_type_id'],
            ];
        }

        $query->join($join);
        // END: JOINs & dynamically fields

        // START : General Selectable fields
        $selectable['institution_code']    = 'Institution.code';
        $selectable['institution_name']    = 'Institution.name';
        $selectable['openemis_no']         = $this->aliasField('openemis_no');
        $selectable['student_id']          = $this->aliasField('id');
        $selectable['first_name']          = $this->aliasField('first_name');
        $selectable['last_name']           = $this->aliasField('last_name');
        $selectable['academic_year_name']  = 'AcademicPeriod.name';
        $query->select($selectable);
        // END : General Selectable fields

        $query->where([
            'InstitutionStudent.academic_period_id' => $academicPeriodId,
            'InstitutionStudent.institution_id'     => $institutionId,
            ('InstitutionStudent.student_status_id IS NOT')     => 4,//POCOR-6709
            $this->aliasField('is_student')         => 1,
        ]);
        
        $query->group($group_by)->order([$this->aliasField('first_name'), $this->aliasField('last_name')]);
        
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) use ($sheet_tab_name)
        {
            return $results->map(function ($row) use ($sheet_tab_name)
            {
                // Tab wise required filed dynamically generated
                if ( $sheet_tab_name == 'Overview' ) {
                    $row['overview_health_insurance_value'] = ( $row['overview_health_insurance'] == 1 ) ? 'Yes' : 'No';
                } else if ( $sheet_tab_name == 'Allergies' ) {
                    $row['allergies_severe_value'] = ( $row['allergies_severe'] == 1 ) ? 'Yes' : 'No';
                } else if ( $sheet_tab_name == 'Families' ) {
                    $row['families_current_value'] = ( $row['families_current'] == 1 ) ? 'Yes' : 'No';
                } else if ( $sheet_tab_name == 'Histories' ) {
                    $row['histories_current_value'] = ( $row['histories_current'] == 1 ) ? 'Yes' : 'No';
                }

                // Common logically created fields
                $row['student_full_name']          = $row['first_name'] . ' ' .  $row['last_name'];
                $row['institution_name_code']      = $row['institution_code'] . ' ' .  $row['institution_name'];
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

        if ( $sheet_tab_name == 'Overview' ) {
            $extraField = $this->getOverviewTabFields($extraField);
        } else if ( $sheet_tab_name == 'Allergies' ) {
            $extraField = $this->getAllergiesTabFields($extraField);
        } else if ( $sheet_tab_name == 'Consultations' ) {
            $extraField = $this->getConsultationsTabFields($extraField);
        } else if ( $sheet_tab_name == 'Families' ) {
            $extraField = $this->getFamiliesTabFields($extraField);
        } else if ( $sheet_tab_name == 'Histories' ) {
            $extraField = $this->getHistoriesTabFields($extraField);
        } else if ( $sheet_tab_name == 'Vaccinations' ) {
            $extraField = $this->getVaccinationsTabFields($extraField);
        } else if ( $sheet_tab_name == 'Medications' ) {
            $extraField = $this->getMedicationsTabFields($extraField);
        } else if ( $sheet_tab_name == 'Tests' ) {
            $extraField = $this->getTestsTabFields($extraField);
        } else if ( $sheet_tab_name == 'UserBodyMasses' ) {
            $extraField = $this->getUserBodyMassesTabFields($extraField);
        } else if ( $sheet_tab_name == 'UserInsurances' ) {
            $extraField = $this->getUserInsurancesTabFields($extraField);
        }
        
        $fields->exchangeArray($extraField);
    }

    private function getUserInsurancesTabFields($extraField)
    {
        $extraField = $this->commonFields($extraField);
        $extraField[] = [
            'key'   => 'user_insurances_start_date',
            'field' => 'user_insurances_start_date',
            'type'  => 'date',
            'label' => __('Start Date'),
        ];
        $extraField[] = [
            'key'   => 'user_insurances_end_date',
            'field' => 'user_insurances_end_date',
            'type'  => 'date',
            'label' => __('End Date'),
        ];
        $extraField[] = [
            'key'   => 'user_insurances_provider_name',
            'field' => 'user_insurances_provider_name',
            'type'  => 'string',
            'label' => __('Provider'),
        ];
        $extraField[] = [
            'key'   => 'user_insurances_type_name',
            'field' => 'user_insurances_type_name',
            'type'  => 'string',
            'label' => __('Type'),
        ];
        return $extraField;
    }

    private function getUserBodyMassesTabFields($extraField)
    {
        $extraField = $this->commonFields($extraField);
        $extraField[] = [
            'key'   => 'masses_date',
            'field' => 'masses_date',
            'type'  => 'date',
            'label' => __('Date'),
        ];
        $extraField[] = [
            'key'   => 'masses_height',
            'field' => 'masses_height',
            'type'  => 'string',
            'label' => __('Height'),
        ];
        $extraField[] = [
            'key'   => 'masses_weight',
            'field' => 'masses_weight',
            'type'  => 'string',
            'label' => __('Weight'),
        ];
        $extraField[] = [
            'key'   => 'masses_body_mass_index',
            'field' => 'masses_body_mass_index',
            'type'  => 'string',
            'label' => __('Body Mass Index'),
        ];
        return $extraField;
    }

    private function getTestsTabFields($extraField)
    {
        $extraField = $this->commonFields($extraField);
        $extraField[] = [
            'key'   => 'tests_date',
            'field' => 'tests_date',
            'type'  => 'date',
            'label' => __('Date'),
        ];
        $extraField[] = [
            'key'   => 'tests_result',
            'field' => 'tests_result',
            'type'  => 'string',
            'label' => __('Result'),
        ];
        $extraField[] = [
            'key'   => 'tests_comment',
            'field' => 'tests_comment',
            'type'  => 'string',
            'label' => __('Comment'),
        ];
        $extraField[] = [
            'key'   => 'tests_type_name',
            'field' => 'tests_type_name',
            'type'  => 'string',
            'label' => __('Health Test Type'),
        ];
        $extraField[] = [
            'key'   => 'tests_file_name',
            'field' => 'tests_file_name',
            'type'  => 'string',
            'label' => __('File Name'),
        ];
        return $extraField;
    }

    private function getMedicationsTabFields($extraField)
    {
        $extraField = $this->commonFields($extraField);
        $extraField[] = [
            'key'   => 'medications_name',
            'field' => 'medications_name',
            'type'  => 'string',
            'label' => __('Name'),
        ];
        $extraField[] = [
            'key'   => 'medications_dosage',
            'field' => 'medications_dosage',
            'type'  => 'string',
            'label' => __('Dosage'),
        ];
        $extraField[] = [
            'key'   => 'medications_start_date',
            'field' => 'medications_start_date',
            'type'  => 'date',
            'label' => __('Start Date'),
        ];
        $extraField[] = [
            'key'   => 'medications_end_date',
            'field' => 'medications_end_date',
            'type'  => 'date',
            'label' => __('End Date'),
        ];
        $extraField[] = [
            'key'   => 'medications_file_name',
            'field' => 'medications_file_name',
            'type'  => 'string',
            'label' => __('File Name'),
        ];			
        return $extraField;
    }

    private function getVaccinationsTabFields($extraField)
    {
        $extraField = $this->commonFields($extraField);
        $extraField[] = [
            'key'   => 'immunizations_date',
            'field' => 'immunizations_date',
            'type'  => 'date',
            'label' => __('Date'),
        ];
        $extraField[] = [
            'key'   => 'immunizations_type_name',
            'field' => 'immunizations_type_name',
            'type'  => 'string',
            'label' => __('Vaccination Type'),
        ];
        $extraField[] = [
            'key'   => 'immunizations_comment',
            'field' => 'immunizations_comment',
            'type'  => 'string',
            'label' => __('Comment'),
        ];
        $extraField[] = [
            'key'   => 'immunizations_file_name',
            'field' => 'immunizations_file_name',
            'type'  => 'string',
            'label' => __('File Name'),
        ];		
        return $extraField;
    }

    private function getHistoriesTabFields($extraField)
    {
        $extraField = $this->commonFields($extraField);
        $extraField[] = [
            'key'   => 'histories_current_value',
            'field' => 'histories_current_value',
            'type'  => 'string',
            'label' => __('Current'),
        ];
        $extraField[] = [
            'key'   => 'histories_comment',
            'field' => 'histories_comment',
            'type'  => 'string',
            'label' => __('Comment'),
        ];
        $extraField[] = [
            'key'   => 'histories_health_name',
            'field' => 'histories_health_name',
            'type'  => 'string',
            'label' => __('Health Condition'),
        ];
        $extraField[] = [
            'key'   => 'histories_file_name',
            'field' => 'histories_file_name',
            'type'  => 'string',
            'label' => __('File Name'),
        ];
        return $extraField;
    }

    private function getFamiliesTabFields($extraField)
    {
        $extraField = $this->commonFields($extraField);
        $extraField[] = [
            'key'   => 'families_current_value',
            'field' => 'families_current_value',
            'type'  => 'string',
            'label' => __('Current'),
        ];
        $extraField[] = [
            'key'   => 'families_comment',
            'field' => 'families_comment',
            'type'  => 'string',
            'label' => __('Comment'),
        ];
        $extraField[] = [
            'key'   => 'families_relationship_name',
            'field' => 'families_relationship_name',
            'type'  => 'string',
            'label' => __('Health Relationship'),
        ];
        $extraField[] = [
            'key'   => 'families_condition_name',
            'field' => 'families_condition_name',
            'type'  => 'string',
            'label' => __('Health Condition'),
        ];
        $extraField[] = [
            'key'   => 'families_file_name',
            'field' => 'families_file_name',
            'type'  => 'string',
            'label' => __('File Name'),
        ];
        return $extraField;
    }

    private function getConsultationsTabFields($extraField)
    {
        $extraField = $this->commonFields($extraField);
        $extraField[] = [
            'key'   => 'consultations_date',
            'field' => 'consultations_date',
            'type'  => 'date',
            'label' => __('Date'),
        ];
        $extraField[] = [
            'key'   => 'consultations_description',
            'field' => 'consultations_description',
            'type'  => 'string',
            'label' => __('Description'),
        ];
        $extraField[] = [
            'key'   => 'consultations_treatment',
            'field' => 'consultations_treatment',
            'type'  => 'string',
            'label' => __('Treatment'),
        ];
        $extraField[] = [
            'key'   => 'consultations_type_name',
            'field' => 'consultations_type_name',
            'type'  => 'string',
            'label' => __('Health Consultation Type'),
        ];
        $extraField[] = [
            'key'   => 'consultations_file_name',
            'field' => 'consultations_file_name',
            'type'  => 'string',
            'label' => __('File Name'),
        ];
        return $extraField;
    }

    private function getAllergiesTabFields($extraField)
    {
        $extraField = $this->commonFields($extraField);

        $extraField[] = [
            'key'   => 'allergies_description',
            'field' => 'allergies_description',
            'type'  => 'string',
            'label' => __('Description'),
        ];
        $extraField[] = [
            'key'   => 'allergies_severe_value',
            'field' => 'allergies_severe_value',
            'type'  => 'string',
            'label' => __('Severe'),
        ];
        $extraField[] = [
            'key'   => 'allergies_comment',
            'field' => 'allergies_comment',
            'type'  => 'string',
            'label' => __('Comment'),
        ];
        $extraField[] = [
            'key'   => 'allergies_type_name',
            'field' => 'allergies_type_name',
            'type'  => 'string',
            'label' => __('Health Allergy Type'),
        ];
        $extraField[] = [
            'key'   => 'allergies_file_name',
            'field' => 'allergies_file_name',
            'type'  => 'string',
            'label' => __('File Name'),
        ];
        return $extraField;
    }

    private function getOverviewTabFields($extraField)
    {
        $extraField = $this->commonFields($extraField);
        $extraField[] = [
            'key'   => 'overview_blood_type',
            'field' => 'overview_blood_type',
            'type'  => 'string',
            'label' => __('Blood Type'),
        ];
        $extraField[] = [
            'key'   => 'overview_doctor_name',
            'field' => 'overview_doctor_name',
            'type'  => 'string',
            'label' => __('Doctor Name'),
        ];
        $extraField[] = [
            'key'   => 'overview_doctor_contact',
            'field' => 'overview_doctor_contact',
            'type'  => 'string',
            'label' => __('Doctor Contact'),
        ];
        $extraField[] = [
            'key'   => 'overview_medical_facility',
            'field' => 'overview_medical_facility',
            'type'  => 'string',
            'label' => __('Medical Facility'),
        ];
        $extraField[] = [
            'key'   => 'overview_health_insurance_value',
            'field' => 'overview_health_insurance_value',
            'type'  => 'string',
            'label' => __('Health Insurance'),
        ];
        $extraField[] = [
            'key'   => 'overview_health_file_name',
            'field' => 'overview_health_file_name',
            'type'  => 'string',
            'label' => __('File Name'),
        ];
        return $extraField;
    }

    private function commonFields($extraField)
    {
        $extraField[] = [
            'key'   => 'AcademicPeriod.name',
            'field' => 'academic_year_name',
            'type'  => 'string',
            'label' => __('Academic Period'),
        ];
        $extraField[] = [
            'key'   => '',
            'field' => 'institution_name_code',
            'type'  => 'string',
            'label' => __('Institution'),
        ];
        $extraField[] = [
            'key'   => 'InstitutionStandards.openemis_no',
            'field' => 'openemis_no',
            'type'  => 'string',
            'label' => __('OpenEMIS ID'),
        ];
        $extraField[] = [
            'key'   => '',
            'field' => 'student_full_name',
            'type'  => 'string',
            'label' => __('Student'),
        ];
        return $extraField;
    }
}
