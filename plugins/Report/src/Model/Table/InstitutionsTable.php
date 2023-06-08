<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use Cake\I18n\Time;
use Cake\ORM\TableRegistry;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use Institution\Model\Table\InstitutionsTable as Institutions;
use Report\Model\Table\InstitutionPositionsTable as InstitutionPositions;

class InstitutionsTable extends AppTable
{
    use OptionsTrait;
    private $classificationOptions = [];

    // filter
    const NO_FILTER = 0;
    const NO_STUDENT = 1;
    const NO_STAFF = 2;

    public function initialize(array $config)
    {
        $this->table('institutions');
        parent::initialize($config);

        $this->belongsTo('Localities', ['className' => 'Institution.Localities', 'foreignKey' => 'institution_locality_id']);
        $this->belongsTo('Types', ['className' => 'Institution.Types', 'foreignKey' => 'institution_type_id']);
        $this->belongsTo('Ownerships', ['className' => 'Institution.Ownerships', 'foreignKey' => 'institution_ownership_id']);
        $this->belongsTo('Statuses', ['className' => 'Institution.Statuses', 'foreignKey' => 'institution_status_id']);
        $this->belongsTo('Sectors', ['className' => 'Institution.Sectors', 'foreignKey' => 'institution_sector_id']);
        $this->belongsTo('Providers', ['className' => 'Institution.Providers', 'foreignKey' => 'institution_provider_id']);
        $this->belongsTo('Genders', ['className' => 'Institution.Genders', 'foreignKey' => 'institution_gender_id']);
        $this->belongsTo('AreaLevels', ['className' => 'AreaLevel.AreaLevels']);
        $this->belongsTo('Areas', ['className' => 'Area.Areas']);
        $this->belongsTo('AreaAdministratives', ['className' => 'Area.AreaAdministratives']);

        $this->addBehavior('Excel', ['excludes' => ['security_group_id', 'logo_name'], 'pages' => false]);
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Report.CustomFieldList', [
            'model' => 'Institution.Institutions',
            'formFilterClass' => ['className' => 'InstitutionCustomField.InstitutionCustomFormsFilters'],
            'fieldValueClass' => ['className' => 'InstitutionCustomField.InstitutionCustomFieldValues', 'foreignKey' => 'institution_id', 'dependent' => true, 'cascadeCallbacks' => true],
            'tableCellClass' => ['className' => 'InstitutionCustomField.InstitutionCustomTableCells', 'foreignKey' => 'institution_id', 'dependent' => true, 'cascadeCallbacks' => true, 'saveStrategy' => 'replace']
        ]);
        $this->addBehavior('Report.InstitutionSecurity');

        $this->shiftTypes = $this->getSelectOptions('Shifts.types'); //get from options trait

        $this->classificationOptions = [
            Institutions::ACADEMIC => 'Academic Institution',
            Institutions::NON_ACADEMIC => 'Non-Academic Institution'
        ];

    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        // validation for attendance marked record feature
        $validator
            ->add('report_start_date', [
                'ruleCompareDate' => [
                    'rule' => ['compareDate', 'report_end_date', true],
                    'on' => function ($context) {
                        $feature = $context['data']['feature'];
                        return in_array($feature, ['Report.ClassAttendanceNotMarkedRecords', 'Report.InstitutionCases', 'Report.StudentAttendanceSummary',
                            'Report.ClassAttendanceMarkedSummaryReport']);
                    }
                ],
                'ruleInAcademicPeriod' => [
                    'rule' => ['inAcademicPeriod', 'academic_period_id', []],
                    'on' => function ($context) {
                        $feature = $context['data']['feature'];
                        return in_array($feature, ['Report.ClassAttendanceNotMarkedRecords', 'Report.InstitutionCases' , 'Report.StudentAttendanceSummary']);
                    },
                    'message' => __('Report Start Date should be later than Academic Period Start Date')
                ],
            ]);

        $validator
            ->add('report_end_date', [
                'ruleInAcademicPeriod' => [
                    'rule' => ['inAcademicPeriod', 'academic_period_id', []],
                    'on' => function ($context) {
                        $feature = $context['data']['feature'];
                        return in_array($feature, ['Report.ClassAttendanceNotMarkedRecords', 'Report.InstitutionCases', 'Report.StudentAttendanceSummary']);
                    },
                    'message' => __('Report End Date should be earlier than Academic Period End Date')
                ],
                'ruleForOneMonthDate' => [
                    'rule' => ['forOneMonthDate'],
                    'on' => function ($context) {
                        $feature = $context['data']['feature'];
                        return in_array($feature, ['Report.StudentAttendanceSummary']);
                    },
                    'message' => __('Date range should be one month only')
                ]
            ]);

        /*POCOR-6333 starts*/
        $feature = $this->request->data[$this->alias()]['feature'];
        if (in_array($feature, ['Report.Institutions','Report.StudentAbsencesPerDays'])) {
            $validator = $validator
                    ->notEmpty('area_level_id')
                    ->notEmpty('area_education_id');
        }
        /*POCOR-6333 ends*/
      if (in_array($feature, ['Report.WashReports','Report.StudentAbsencesPerDaysTable'])) {
            $validator = $validator
                    ->notEmpty('institution_id');
        }
        

        return $validator;
    }

    public function validationSubjectsClasses(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        $validator = $validator
            ->notEmpty('academic_period_id')
            ->notEmpty('institution_id')
            ->notEmpty('education_grade_id');
        return $validator;
    }

    public function validationSubjects(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        $validator = $validator
            ->notEmpty('institution_type_id')
            ->notEmpty('institution_id');
        return $validator;
    }

    public function validationBodyMasses(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        $validator = $validator
            //->notEmpty('institution_type_id')
            ->notEmpty('institution_id');
        return $validator;
    }

    public function validationStudents(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        $validator = $validator
            ->notEmpty('education_programme_id')
            ->notEmpty('status');
        return $validator;
    }

    public function validationStaff(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        $validator = $validator
            ->notEmpty('type')
            ->notEmpty('status');
        return $validator;
    }

    public function validationStudentAttendanceSummary(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        $validator = $validator
            ->notEmpty('institution_type_id')
            ->notEmpty('institution_id')
            ->notEmpty('education_grade_id');
        return $validator;
    }

    public function validationStaffAttendances(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        $validator = $validator
           // ->notEmpty('area_level_id')
            //->notEmpty('area_education_id')
            ->notEmpty('institution_id');
        return $validator;
    }

    public function validationInstitutionInfrastructures(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        $validator = $validator
            //->notEmpty('institution_type_id')
            ->notEmpty('institution_id')
            ->notEmpty('infrastructure_level');
        return $validator;
    }

    public function validationGuardians(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        $validator = $validator
            ->notEmpty('institution_type_id')
            ->notEmpty('institution_id');
        return $validator;
    }


    public function validationInfrastructureNeeds(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        $validator = $validator->notEmpty('institution_id');
        return $validator;
    }

    //POCOR-5762 starts
    public function validationStaffLeave(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        $validator = $validator->notEmpty('institution_id');
        return $validator;
    }//POCOR-5762 ends

    public function beforeAction(Event $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('feature', ['select' => false]);
        $this->ControllerAction->field('format');
    }

    public function onGetReportName(Event $event, ArrayObject $data)
    {
        return __('Overview');
    }

    public function addBeforeAction(Event $event)
    {
        $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);

        $this->ControllerAction->field('area_level_id', ['type' => 'hidden']);
        $this->ControllerAction->field('area_education_id', ['type' => 'hidden']);
        $this->ControllerAction->field('institution_filter', ['type' => 'hidden']);
        $this->ControllerAction->field('position_filter', ['type' => 'hidden']);
        $this->ControllerAction->field('teaching_filter', ['type' => 'hidden']);//POCOR-6614
        // $this->ControllerAction->field('license', ['type' => 'hidden']);
        $this->ControllerAction->field('type', ['type' => 'hidden']);
        $this->ControllerAction->field('status', ['type' => 'hidden']);
        $this->ControllerAction->field('module', ['type' => 'hidden']);
        $this->ControllerAction->field('from_date',['type'=>'hidden']);
        $this->ControllerAction->field('to_date',['type'=>'hidden']);

        $this->ControllerAction->field('institution_type_id', ['type' => 'hidden']);
        $this->ControllerAction->field('institution_id', ['type' => 'hidden']);
        $this->ControllerAction->field('education_programme_id', ['type' => 'hidden']);
        $this->ControllerAction->field('education_grade_id', ['type' => 'hidden']);
        $this->ControllerAction->field('report_start_date', ['type' => 'hidden']);
        $this->ControllerAction->field('report_end_date', ['type' => 'hidden']);
        $this->ControllerAction->field('attendance_type', ['type' => 'hidden', 'label' => 'Type']);
        //$this->ControllerAction->field('periods', ['type' => 'hidden']);
        $this->ControllerAction->field('subjects', ['type' => 'hidden']);
        $this->ControllerAction->field('wash_type', ['type' => 'hidden']);
        $this->ControllerAction->field('education_subject_id', ['type' => 'hidden']);
        $this->ControllerAction->field('education_grade_id', ['type' => 'hidden']);
        $this->ControllerAction->field('infrastructure_level', ['type' => 'hidden']);
        $this->ControllerAction->field('infrastructure_type', ['type' => 'hidden']);
        //POCOR-5762 starts
        $this->ControllerAction->field('position', ['type' => 'hidden']);
        $this->ControllerAction->field('leave_type', ['type' => 'hidden']);
        $this->ControllerAction->field('workflow_status', ['type' => 'hidden']);
        //POCOR-5762 ends
        $this->ControllerAction->field('education_level_id', ['type' => 'hidden']);
        $this->ControllerAction->field('position_status', ['type' => 'hidden']);
    }

    public function addBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        if ($data[$this->alias()]['feature'] == 'Report.InstitutionSubjectsClasses') {
            $options['validate'] = 'subjectsClasses';
        }
        if ($data[$this->alias()]['feature'] == 'Report.InstitutionSubjects') {
            $options['validate'] = 'subjects';
        } elseif ($data[$this->alias()]['feature'] == 'Report.InstitutionStudents') {
            $options['validate'] = 'students';
        } elseif ($data[$this->alias()]['feature'] == 'Report.InstitutionStaff') {
            $options['validate'] = 'staff';
        } elseif ($data[$this->alias()]['feature'] == 'Report.StudentAttendanceSummary') {
            $options['validate'] = 'studentAttendanceSummary';
        } elseif ($data[$this->alias()]['feature'] == 'Report.StaffAttendances') {
            $options['validate'] = 'staffAttendances';
        } elseif ($data[$this->alias()]['feature'] == 'Report.BodyMasses') {
            $options['validate'] = 'bodyMasses';
        } elseif ($data[$this->alias()]['feature'] == 'Report.Guardians') {
            $options['validate'] = 'guardians';
        } elseif ($data[$this->alias()]['feature'] == 'Report.InstitutionInfrastructures') {
            $options['validate'] = 'institutionInfrastructures';
        } elseif ($data[$this->alias()]['feature'] == 'Report.InfrastructureNeeds') {
            $options['validate'] = 'infrastructureNeeds';
        } elseif ($data[$this->alias()]['feature'] == 'Report.StaffLeave') { //POCOR-5762
            $options['validate'] = 'StaffLeave';
        }
        elseif ($data[$this->alias()]['feature'] == 'Report.StudentAbsencesPerDays') { //POCOR-7276
            $options['validate'] = 'StudentAbsencesPerDays';
        }

    }

    public function addAfterAction(Event $event, Entity $entity)
    {
        if ($entity->has('feature')) { 
            $feature = $entity->feature;

            $fieldsOrder = ['feature'];
            switch ($feature) {
                /*POCOR-6176 Starts*/
                case 'Report.Institutions':
                    $fieldsOrder[] = 'area_level_id';
                    $fieldsOrder[] = 'area_education_id';
                    $fieldsOrder[] = 'institution_filter';
                    $fieldsOrder[] = 'format';
                    break;
                case 'Report.InstitutionAssociations':
                case 'Report.InstitutionProgrammes':
                case 'Report.InstitutionClasses':
                    $fieldsOrder[] = 'academic_period_id';  /*POCOR-6637 :: START*/
                    $fieldsOrder[] = 'area_level_id';   
                    $fieldsOrder[] = 'area_education_id';   
                    $fieldsOrder[] = 'institution_id';  
                    $fieldsOrder[] = 'education_grade_id';  
                    $fieldsOrder[] = 'format';
                    break;  /*POCOR-6637 :: END*/
                case 'Report.StudentAbsences':
                case 'Report.StudentWithdrawalReport':
                case 'Report.InstitutionSummaryReport':
                case 'Report.BodyMasses':
                case 'Report.StaffAttendances':
                case 'Report.StaffTransfers':
                case 'Report.SpecialNeedsFacilities':
                case 'Report.InstitutionCommittees':
            //Start:POCOR-4570
                case 'Report.Uis':
                    $fieldsOrder[] = 'academic_period_id';
                    $fieldsOrder[] = 'format';
                case 'Report.Uis2':
                    $fieldsOrder[] = 'academic_period_id';
                    $fieldsOrder[] = 'format';
                case 'Report.Uis3':
                    $fieldsOrder[] = 'academic_period_id';
                    $fieldsOrder[] = 'format';
                case 'Report.Uis4':
                    $fieldsOrder[] = 'academic_period_id';
                    $fieldsOrder[] = 'format';
                case 'Report.Uis5':
                    $fieldsOrder[] = 'academic_period_id';
                    $fieldsOrder[] = 'format';    
                    //END:POCOR-4570

                case 'Report.InstitutionPositionsSummaries': //POCOR-6952
                    $fieldsOrder[] = 'academic_period_id';
                    $fieldsOrder[] = 'area_level_id';
                    $fieldsOrder[] = 'area_education_id';
                    $fieldsOrder[] = 'institution_id';
                    $fieldsOrder[] = 'position_status';
                    $fieldsOrder[] = 'format';
                    break;
                case 'Report.StaffAttendances': //POCOR-5181
                    $fieldsOrder[] = 'academic_period_id';
                    $fieldsOrder[] = 'area_level_id';
                    $fieldsOrder[] = 'area_education_id';
                    $fieldsOrder[] = 'institution_id';
                    $fieldsOrder[] = 'start_date';
                    $fieldsOrder[] = 'end_date';
                    $fieldsOrder[] = 'format';
                    break;    
                case 'Report.InfrastructureNeeds':
                    $fieldsOrder[] = 'academic_period_id';
                    $fieldsOrder[] = 'area_level_id';
                    $fieldsOrder[] = 'area_education_id';
                    $fieldsOrder[] = 'institution_id';
                    $fieldsOrder[] = 'format';
                    break;
                case 'Report.InstitutionPositions':
                    $fieldsOrder[] = 'academic_period_id';
                    $fieldsOrder[] = 'area_level_id';
                    $fieldsOrder[] = 'area_education_id';
                    $fieldsOrder[] = 'position_filter';
                    $fieldsOrder[] = 'teaching_filter';//POCOR-6614
                    $fieldsOrder[] = 'status'; //POCOR-6869
                    $fieldsOrder[] = 'institution_id';
                    $fieldsOrder[] = 'format';
                    break;
                case 'Report.InstitutionSubjects':
                    $fieldsOrder[] = 'academic_period_id';
                    $fieldsOrder[] = 'area_level_id';
                    $fieldsOrder[] = 'area_education_id';
                    $fieldsOrder[] = 'institution_type_id';
                    $fieldsOrder[] = 'institution_id';
                    $fieldsOrder[] = 'education_programme_id';
                    $fieldsOrder[] = 'education_subject_id';
                    $fieldsOrder[] = 'format';
                    break;
                case 'Report.InstitutionStudents':
                    $fieldsOrder[] = 'academic_period_id';
                    $fieldsOrder[] = 'area_level_id';
                    $fieldsOrder[] = 'area_education_id';
                    $fieldsOrder[] = 'status';
                    $fieldsOrder[] = 'institution_id';
                    $fieldsOrder[] = 'education_level_id';
                    $fieldsOrder[] = 'education_programme_id';
                    $fieldsOrder[] = 'format';
                    break;
                case 'Report.InstitutionStaff':
                    $fieldsOrder[] = 'academic_period_id';
                    $fieldsOrder[] = 'area_level_id';
                    $fieldsOrder[] = 'area_education_id';
                    $fieldsOrder[] = 'institution_id';
                    $fieldsOrder[] = 'type';
                    $fieldsOrder[] = 'status';
                    $fieldsOrder[] = 'format';
                    break;
                case 'Report.StudentAttendanceSummary':
                    $fieldsOrder[] = 'academic_period_id';
                    $fieldsOrder[] = 'area_level_id';
                    $fieldsOrder[] = 'area_education_id';
                    $fieldsOrder[] = 'institution_type_id';
                    $fieldsOrder[] = 'institution_id';
                    $fieldsOrder[] = 'education_grade_id';
                    $fieldsOrder[] = 'report_start_date';
                    $fieldsOrder[] = 'report_end_date';
                    $fieldsOrder[] = 'format';
                    break;
                case 'Report.StaffLeave':
                    $fieldsOrder[] = 'area_level_id';
                    $fieldsOrder[] = 'area_education_id';
                    $fieldsOrder[] = 'institution_id';
                    $fieldsOrder[] = 'report_start_date';
                    $fieldsOrder[] = 'report_end_date';
                    $fieldsOrder[] = 'position';
                    $fieldsOrder[] = 'leave_type';
                    $fieldsOrder[] = 'workflow_status';
                    $fieldsOrder[] = 'format';
                    break;
                case 'Report.InstitutionCases':
                    $fieldsOrder[] = 'academic_period_id';
                    $fieldsOrder[] = 'area_level_id';
                    $fieldsOrder[] = 'area_education_id';
                    $fieldsOrder[] = 'institution_id';
                    $fieldsOrder[] = 'module';
                    $fieldsOrder[] = 'report_start_date';
                    $fieldsOrder[] = 'report_end_date';
                    $fieldsOrder[] = 'format';
                    break;
                case 'Report.ClassAttendanceNotMarkedRecords':
                    $fieldsOrder[] = 'academic_period_id';
                    $fieldsOrder[] = 'area_level_id';
                    $fieldsOrder[] = 'area_education_id';
                    $fieldsOrder[] = 'institution_id';
                    $fieldsOrder[] = 'education_grade_id';
                    $fieldsOrder[] = 'report_start_date';
                    $fieldsOrder[] = 'report_end_date';
                    $fieldsOrder[] = 'format';
                    break;
                case 'Report.WashReports':
                    $fieldsOrder[] = 'academic_period_id';
                    $fieldsOrder[] = 'area_level_id';
                    $fieldsOrder[] = 'area_education_id';
                    $fieldsOrder[] = 'institution_id';
                    $fieldsOrder[] = 'wash_type';
                    $fieldsOrder[] = 'format';
                    break;
                case 'Report.Guardians':
                    $fieldsOrder[] = 'academic_period_id';
                    $fieldsOrder[] = 'area_level_id';
                    $fieldsOrder[] = 'area_education_id';
                    $fieldsOrder[] = 'institution_type_id';
                    $fieldsOrder[] = 'institution_id';
                    $fieldsOrder[] = 'format';
                    break;
                case 'Report.Income':
                case 'Report.Expenditure':
                    $fieldsOrder[] = 'academic_period_id';
                    $fieldsOrder[] = 'area_level_id';
                    $fieldsOrder[] = 'area_education_id';
                    $fieldsOrder[] = 'institution_id';
                    $fieldsOrder[] = 'from_date';
                    $fieldsOrder[] = 'to_date';
                    $fieldsOrder[] = 'format';
                    break;
                /*POCOR-6176 Ends*/
                case 'Report.ClassAttendanceMarkedSummaryReport':
                    $fieldsOrder[] = 'academic_period_id';
                    $fieldsOrder[] = 'area_level_id';
                    $fieldsOrder[] = 'area_education_id';
                    $fieldsOrder[] = 'institution_id';
                    $fieldsOrder[] = 'education_grade_id';
                    $fieldsOrder[] = 'report_start_date';
                    $fieldsOrder[] = 'report_end_date';
                    $fieldsOrder[] = 'periods';
                    $fieldsOrder[] = 'format';
                    break;

                case 'Report.InstitutionInfrastructures':
                    $fieldsOrder[] = 'academic_period_id';
                    $fieldsOrder[] = 'area_level_id';
                    $fieldsOrder[] = 'area_education_id';
                    $fieldsOrder[] = 'institution_id';
                    $fieldsOrder[] = 'infrastructure_level';
                    $fieldsOrder[] = 'format';
                    break;
                case 'Report.InstitutionClasses':
                    $fieldsOrder[] = 'academic_period_id';
                    $fieldsOrder[] = 'area_level_id';
                    $fieldsOrder[] = 'area_education_id';
                    $fieldsOrder[] = 'institution_type_id';
                    $fieldsOrder[] = 'institution_id';
                    $fieldsOrder[] = 'education_grade_id';
                    $fieldsOrder[] = 'format';
                    break;
                case 'Report.StudentAbsencesPerDays': //POCOR-7276
                    $fieldsOrder[] = 'academic_period_id';
                    $fieldsOrder[] = 'area_level_id';
                    $fieldsOrder[] = 'area_education_id';
                    $fieldsOrder[] = 'institution_id';
                    $fieldsOrder[] = 'attendance_type';
                    $fieldsOrder[] = 'format';
                break;
                default:
                    break;
            }

            $this->ControllerAction->setFieldOrder($fieldsOrder);
        }else{  //POCOR-6637::Start
            $fieldsOrder = ['feature'];
            $fieldsOrder[] = 'area_level_id';
            $fieldsOrder[] = 'area_education_id';
            $fieldsOrder[] = 'institution_filter';
            $fieldsOrder[] = 'format';
            $this->ControllerAction->setFieldOrder($fieldsOrder);
        }//POCOR-6637::END
    }

    public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets)
    {

        $requestData = json_decode($settings['process']['params']);
        $feature = $requestData->feature;
        $filter = $requestData->institution_filter;
        if ($feature == 'Report.Institutions' && $filter != self::NO_FILTER) {
            $sheets[] = [
                'name' => $this->alias(),
                'table' => $this,
                'query' => $this->find(),
            ];
            // Stop the customfieldlist behavior onExcelBeforeStart function
            $event->stopPropagation();
        }
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $requestData = json_decode($settings['process']['params']);
        $feature = $requestData->feature;
        $filter = $requestData->institution_filter;

        $cloneFields = $fields->getArrayCopy();
        $newFields = [];
        foreach ($cloneFields as $key => $value) {
            $newFields[] = $value;
            if ($value['field'] == 'classification') {
                $newFields[] = [
                    'key' => 'Areas.code',
                    'field' => 'area_code',
                    'type' => 'string',
                    'label' => __('Area Education Code')
                ];
            }

            if ($value['field'] == 'area_id') {
                $newFields[] = [
                    'key' => 'AreaAdministratives.code',
                    'field' => 'area_administrative_code',
                    'type' => 'string',
                    'label' => __('Area Administrative Code')
                ];
            }
            

        }

        $fields->exchangeArray($newFields);

        if ($feature == 'Report.Institutions' && $filter != self::NO_FILTER) {
            // Stop the customfieldlist behavior onExcelUpdateFields function
            $includedFields = ['name', 'alternative_name', 'code', 'area_code', 'area_id', 'area_administrative_code', 'area_administrative_id'];
            foreach ($newFields as $key => $value) {
                if (!in_array($value['field'], $includedFields)) {
                    unset($newFields[$key]);
                }
            }
            $filter = $requestData->institution_filter;
            if($filter==2){
                $newFields[] = [
                        'key' => 'institutions.institution_status_id',
                        'field' => 'institution_status',
                        'type' => 'integer',
                        'label' => __('Institutions Status')
                    ];
            }
            $fields->exchangeArray($newFields);
            $event->stopPropagation();
        }
    }

    public function onExcelGetShiftType(Event $event, Entity $entity)
    {

        if (isset($this->shiftTypes[$entity->shift_type])) {
            return __($this->shiftTypes[$entity->shift_type]);
        } else {
            return '';
        }
    }


    public function onExcelGetClassification(Event $event, Entity $entity)
    {
        return __($this->classificationOptions[$entity->classification]);
    }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $attr['options'] = $this->controller->getFeatureOptions($this->alias());
            $attr['onChangeReload'] = true;
            if (!(isset($this->request->data[$this->alias()]['feature']))) {
                $option = $attr['options'];
                reset($option);
                $this->request->data[$this->alias()]['feature'] = key($option);
            }
            return $attr;
        }
    }

    public function onUpdateFieldInstitutionFilter(Event $event, array $attr, $action, Request $request)
    {
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            if ($feature == 'Report.Institutions') {
                $option[self::NO_FILTER] = __('All Institutions');
                $option[self::NO_STUDENT] = __('Institutions with No Students');
                $option[self::NO_STAFF] = __('Institutions with No Staff');
                $attr['type'] = 'select';
                $attr['options'] = $option;
                $attr['onChangeReload'] = true;
                return $attr;
            } else {
                $attr['value'] = self::NO_FILTER;
            }
        }
    }

    public function onUpdateFieldWashType(Event $event, array $attr, $action, Request $request){
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            if (in_array($feature, ['Report.WashReports'])) {
                $options = [
                    'All' => __('All'),   //POCOR-6732
                    'Water' => __('Water'),
                    'Sanitation' => __('Sanitation'),
                    'Hygiene' => __('Hygiene'),
                    'Waste' => __('Waste'),
                    'Sewage' => __('Sewage'),
                ];
                $attr['type'] = 'select';
                $attr['select'] = false;
                $attr['options'] = $options;
            } else {
                $attr['value'] = self::NO_FILTER;
            }
            return $attr;
        }
    }
    public function onUpdateFieldPositionFilter(Event $event, array $attr, $action, Request $request)
    {
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            if (in_array($feature, ['Report.InstitutionPositions'])) {
                $options = [
                    InstitutionPositions::ALL_POSITION => __('All Positions'),
                    InstitutionPositions::POSITION_WITH_STAFF => __('Positions with Staff')
                ];
                $attr['type'] = 'select';
                $attr['select'] = false;
                $attr['options'] = $options;
            } else {
                $attr['value'] = self::NO_FILTER;
            }
            return $attr;
        }
    }

    /**
    * @POCOR-6614
    * Add teaching status filer
    */
    public function onUpdateFieldTeachingFilter(Event $event, array $attr, $action, Request $request)
    {
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            if (in_array($feature, ['Report.InstitutionPositions'])) {
                $options = [
                    InstitutionPositions::ALL_STAFF => __('All Staff'),//POCOR-6850
                    InstitutionPositions::TEACHING => __('Teaching'),
                    InstitutionPositions::NON_TEACHING => __('Non Teaching')
                ];
                $attr['type'] = 'select';
                $attr['select'] = false;
                $attr['options'] = $options;
            } else {
                $attr['value'] = self::NO_FILTER;
            }
            return $attr;
        }
    }

    public function onUpdateFieldLicense(Event $event, array $attr, $action, Request $request)
    {
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            if (in_array($feature, ['Report.InstitutionStaff'])) {
                // need to find all types
                $typeOptions = [];
                $typeOptions[0] = __('All Licenses');

                $Types = TableRegistry::get('FieldOption.LicenseTypes');
                $typeData = $Types->getList();
                foreach ($typeData as $key => $value) {
                    $typeOptions[$key] = $value;
                }

                $attr['type'] = 'select';
                $attr['options'] = $typeOptions;
                return $attr;
            } else {
                $attr['value'] = self::NO_FILTER;
            }
        }
    }

    public function onUpdateFieldModule(Event $event, array $attr, $action, Request $request)
    {
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            if (in_array($feature, ['Report.InstitutionCases'])) {
                $WorkflowRules = TableRegistry::get('Workflow.WorkflowRules');
                $featureOptions = $WorkflowRules->getFeatureOptions();

                $attr['type'] = 'select';
                $attr['options'] = $featureOptions;
                $attr['select'] = false;
                return $attr;
            } else {
                $attr['value'] = self::NO_FILTER;
            }
        }
    }

    public function onUpdateFieldType(Event $event, array $attr, $action, Request $request)
    {
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            if (in_array($feature, ['Report.InstitutionStaff'])) {
                // need to find all types
                $typeOptions = [];
                $typeOptions[0] = __('All Types');

                $Types = TableRegistry::get('Staff.StaffTypes');
                $typeData = $Types->getList();
                foreach ($typeData as $key => $value) {
                    $typeOptions[$key] = $value;
                }

                $attr['type'] = 'select';
                $attr['select'] = false;
                $attr['options'] = $typeOptions;
                return $attr;
            } else {
                $attr['value'] = self::NO_FILTER;
            }
        }
    }

    public function onUpdateFieldStatus(Event $event, array $attr, $action, Request $request)
    {
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            if (in_array($feature,
                        [
                            'Report.InstitutionStudents',
                            'Report.InstitutionStudentEnrollments',
                            'Report.InstitutionStaff',
                            'Report.InstitutionPositions'  // POCOR-6869
                        ])
                ) {



                // need to find all status
                $statusOptions = [];

                switch ($feature) {
                    case 'Report.InstitutionStudents':
                    case 'Report.InstitutionStudentEnrollments':
                        $Statuses = TableRegistry::get('Student.StudentStatuses');
                        $statusData = $Statuses->find()->select(['id', 'name'])->toArray();
                        foreach ($statusData as $key => $value) {
                            $statusOptions[$value->id] = $value->name;
                        }
                        break;

                    case 'Report.InstitutionStaff':
                        $Statuses = TableRegistry::get('Staff.StaffStatuses');
                        $statusData = $Statuses->getList();
                        foreach ($statusData as $key => $value) {
                            $statusOptions[$key] = $value;
                        }
                        break;


                        case 'Report.InstitutionPositionsSummaries':
                            $Statuses = TableRegistry::get('Staff.StaffStatuses');
                            $statusData = $Statuses->getList();
                            foreach ($statusData as $key => $value) {
                                $statusOptions[$key] = $value;
                            }
                            break;
    
                    //Start POCOR-6869
                    case 'Report.InstitutionPositions':
                        $Workflows = TableRegistry::get('Workflow.Workflows');
                        $Statuses = TableRegistry::get('Workflow.WorkflowSteps');
                        $workflowData = $Workflows->find()->select(['id', 'name'])
                                        ->where([$Workflows->aliasField('name LIKE') => 'Positions'])
                                        ->first();
                        $statusData = $Statuses->find()->select(['id', 'name'])
                                      ->where([$Statuses->aliasField('workflow_id') => $workflowData->id])  
                                      ->toArray();
                        foreach ($statusData as $key => $value) {
                            $statusOptions[$value->id] = $value->name;
                        }
                        break;
                    //End POCOR-6869

                    default:
                        return [];
                        break;
                }

                $attr['type'] = 'select';
                $attr['options'] = $statusOptions;
                $attr['attr']['required'] = true;
                return $attr;
            } else {
                $attr['value'] = self::NO_FILTER;
            }
        }
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {

        if (isset($request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];

            if ((in_array($feature,
                         ['Report.InstitutionStudents',
                          'Report.InstitutionSubjectsClasses',
                          'Report.StudentAbsences',
                          'Report.InstitutionCases',
                          'Report.ClassAttendanceNotMarkedRecords',
                          'Report.InstitutionSubjects',
                          'Report.StudentAttendanceSummary',
                          'Report.StaffAttendances',
                          'Report.BodyMasses',

                          'Report.InstitutionSpecialNeedsStudents',
                          'Report.InstitutionStudentsWithSpecialNeeds',
                          'Report.WashReports',
                          'Report.InstitutionClasses',
                          'Report.StudentWithdrawalReport',
                          'Report.InstitutionCommittees',
                          'Report.ClassAttendanceMarkedSummaryReport',
                          'Report.Income',
                          'Report.Expenditure',
                          'Report.InstitutionInfrastructures',
                          'Report.InstitutionAssociations',
                          'Report.InstitutionPositions',
                          'Report.InstitutionProgrammes',
                             'Report.InstitutionStaff',
                             'Report.InstitutionSummaryReport',
                             'Report.StaffTransfers',
                             'Report.Guardians',
                             'Report.SpecialNeedsFacilities',
                             'Report.InfrastructureNeeds',
                             //Start:POCOR-4570
                             'Report.Uis',
                             'Report.Uis2',
                             'Report.Uis3',
                             'Report.Uis4',
                             'Report.Uis5',
                             //END:POCOR-4570
                             'Report.InstitutionPositionsSummaries',
                             'Report.StudentAbsencesPerDays', //POCOR-7276


                         ]
                    )) ||((in_array($feature, ['Report.Institutions']) && !empty($request->data[$this->alias()]['institution_filter']) && $request->data[$this->alias()]['institution_filter'] == self::NO_STUDENT))) {

                $AcademicPeriodTable = TableRegistry::get('AcademicPeriod.AcademicPeriods');
                $academicPeriodOptions = $AcademicPeriodTable->getYearList();
                $currentPeriod = $AcademicPeriodTable->getCurrent();
                $attr['options'] = $academicPeriodOptions;
                $attr['type'] = 'select';
                $attr['select'] = false;
                $attr['onChangeReload'] = true;
                if (empty($request->data[$this->alias()]['academic_period_id'])) {
                    $request->data[$this->alias()]['academic_period_id'] = $currentPeriod;
                }
                return $attr;
            }
        }
    }
    public function onUpdateFieldAreaLevelId(Event $event, array $attr, $action, Request $request)
    {
        if (isset($request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];

            if ((in_array($feature, ['Report.Institutions',
                'Report.InstitutionAssociations',
                'Report.InstitutionPositions',
                'Report.InstitutionProgrammes',
                'Report.InstitutionClasses',
                'Report.InstitutionSubjects',
                'Report.InstitutionStudents',
                'Report.InstitutionStaff',
                'Report.StudentAbsences',
                'Report.StudentAttendanceSummary',
                'Report.StudentWithdrawalReport',
                'Report.InstitutionSummaryReport',
                'Report.BodyMasses',
                'Report.StaffAttendances',
                'Report.StaffLeave',
                'Report.StaffTransfers',
                'Report.InstitutionCases',
                'Report.ClassAttendanceNotMarkedRecords',
                'Report.WashReports',
                'Report.Guardians',
                'Report.InstitutionInfrastructures',
                'Report.SpecialNeedsFacilities',
                'Report.InstitutionCommittees',
                'Report.ClassAttendanceMarkedSummaryReport',
                'Report.InfrastructureNeeds',
                'Report.Income',
                'Report.Expenditure',
                'Report.InstitutionPositionsSummaries',
                'Report.StudentAbsencesPerDays' //POCOR-7276
            ]))) {
                $Areas = TableRegistry::get('AreaLevel.AreaLevels');
                $entity = $attr['entity'];

                if ($action == 'add') {
                    $areaOptions = $Areas
                        ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
                        ->order([$Areas->aliasField('level')]);

                    $attr['type'] = 'chosenSelect';
                    $attr['attr']['multiple'] = false;
                    $attr['select'] = true;
                    if($feature == "Report.InstitutionSummaryReport"){ 
                        $attr['options'] = ['' => '-- ' . _('Select') . ' --'] + $areaOptions->toArray();
                    }else{
                        $attr['options'] = ['' => '-- ' . _('Select') . ' --', '-1' => _('All Areas Level')] + $areaOptions->toArray();
                    }
                    
                    $attr['onChangeReload'] = true;
                } else {
                    $attr['type'] = 'hidden';
                }
            }
        }
        return $attr;
    }

    public function onUpdateFieldAreaEducationId(Event $event, array $attr, $action, Request $request)
    {
        if (isset($request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            $areaLevelId = $this->request->data[$this->alias()]['area_level_id'];//POCOR-6333
            if ((in_array($feature,
                [
                    'Report.Institutions',
                    'Report.InstitutionAssociations',
                    'Report.InstitutionPositions',
                    'Report.InstitutionProgrammes',
                    'Report.InstitutionClasses',
                    'Report.InstitutionSubjects',
                    'Report.InstitutionStudents',
                    'Report.InstitutionStaff',
                    'Report.StudentAbsences',
                    'Report.StudentAttendanceSummary',
                    'Report.StudentWithdrawalReport',
                    'Report.InstitutionSummaryReport',
                    'Report.BodyMasses',
                    'Report.StaffAttendances',
                    'Report.StaffLeave',
                    'Report.StaffTransfers',
                    'Report.InstitutionCases',
                    'Report.ClassAttendanceNotMarkedRecords',
                    'Report.WashReports',
                    'Report.Guardians',
                    'Report.InstitutionInfrastructures',
                    'Report.SpecialNeedsFacilities',
                    'Report.InstitutionCommittees',
                    'Report.ClassAttendanceMarkedSummaryReport',
                    'Report.InfrastructureNeeds',
                    'Report.Income',
                    'Report.Expenditure',
                    'Report.InstitutionPositionsSummaries',
                    'Report.StudentAbsencesPerDays' //POCOR-7276
                ]))) {
                $Areas = TableRegistry::get('Area.Areas');
                $entity = $attr['entity'];

                if ($action == 'add') {
                    $where = [];
                    if ($areaLevelId != -1) {
                        $where[$Areas->aliasField('area_level_id')] = $areaLevelId;
                    }
                    $areas = $Areas
                        ->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
                        ->where([$where])
                        ->order([$Areas->aliasField('order')]);
                    $areaOptions = $areas->toArray();
                    $attr['type'] = 'chosenSelect';
                    $attr['attr']['multiple'] = false;
                    $attr['select'] = true;
                    /*POCOR-6333 starts*/
                    if (count($areaOptions) > 1) {
                        $attr['options'] = ['' => '-- ' . _('Select') . ' --', '-1' => _('All Areas')] + $areaOptions;
                    } else {
                        $attr['options'] = ['' => '-- ' . _('Select') . ' --'] + $areaOptions;
                    }
                    /*POCOR-6333 ends*/
                    $attr['onChangeReload'] = true;
                } else {
                    $attr['type'] = 'hidden';
                }
            }
        }
        return $attr;
    }

    public function onUpdateFieldEducationProgrammeId(Event $event, array $attr, $action, Request $request)
    {
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            $institutionId = $this->request->data[$this->alias()]['institution_id'];
            $educationlevelId = $this->request->data[$this->alias()]['education_level_id'];
            if (in_array($feature,
                        [
                            'Report.InstitutionStudents',
                            'Report.InstitutionSubjects'
                        ])
                ) {

                $academicPeriodId = $this->request->data[$this->alias()]['academic_period_id'];
                $EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');
                /*POCOR-6337 starts*/
                $EducationGrades = TableRegistry::get('Education.EducationGrades');
                $EducationCycles = TableRegistry::get('Education.EducationCycles');
                $EducationLevel = TableRegistry::get('Education.EducationLevels');
                $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
                $condition = [];
                if($feature =='Report.InstitutionSubjects'){
                    if ($institutionId != 0) {
                        $condition[$InstitutionGrades->aliasField('institution_id')] = $institutionId;
                    }
                }
                if($feature =='Report.InstitutionStudents'){
                    if ($educationlevelId != 0) {
                        $condition['EducationCycles.education_level_id'] = $educationlevelId;
                    }
                }
                /*POCOR-6337 ends*/
                $programmeOptions = $EducationProgrammes
                    ->find('list', ['keyField' => 'id', 'valueField' => 'cycle_programme_name'])
                    ->find('visible')
                    ->contain(['EducationCycles.EducationLevels.EducationSystems'])
                    /*POCOR-6337 starts*/
                    ->leftJoin([$EducationGrades->alias() => $EducationGrades->table()], [
                        $EducationGrades->aliasField('education_programme_id') . ' = '. $EducationProgrammes->aliasField('id')
                    ])
                    ->leftJoin([$InstitutionGrades->alias() => $InstitutionGrades->table()], [
                        $InstitutionGrades->aliasField('education_grade_id') . ' = '. $EducationGrades->aliasField('id')
                    ])
                    
                    /*POCOR-6337 ends*/
                    ->where([
                        'EducationSystems.academic_period_id' => $academicPeriodId,
                        $condition //POCOR-6337
                    ])
                    ->order([
                        'EducationCycles.order' => 'ASC',
                        $EducationProgrammes->aliasField('order') => 'ASC'
                    ])
                    ->toArray();

                $attr['type'] = 'select';
                $attr['select'] = false;
                /*POCOR-6337 starts*/
                if (count($programmeOptions) > 1) {
                    $attr['options'] = ['' => '-- ' . _('Select') . ' --', 0 => _('All Programmes')] + $programmeOptions;
                } else {
                    $attr['options'] = ['' => '-- ' . _('Select') . ' --'] + $programmeOptions;
                }
                /*POCOR-6337 starts*/
                $attr['onChangeReload'] = true;
            } else {
                $attr['value'] = self::NO_FILTER;
            }
            return $attr;
        }
    }

    public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, Request $request)
    {

        if (isset($this->request->data[$this->alias()]['academic_period_id'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            $academicPeriodId = $this->request->data[$this->alias()]['academic_period_id'];
            $institutionId = $this->request->data[$this->alias()]['institution_id'];
            if (in_array($feature, [
                            'Report.ClassAttendanceNotMarkedRecords',
                            'Report.SubjectsBookLists',
                            'Report.InstitutionSubjectsClasses',
                            'Report.StudentAttendanceSummary',
                            'Report.ClassAttendanceMarkedSummaryReport',
                            'Report.InstitutionClasses'
                        ])
                ) {

                $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
                $conditions = [];
                if ($institutionId != 0) {
                    $conditions[$InstitutionGrades->aliasField('institution_id')] = $institutionId;
                }
                $gradeOptions = $InstitutionGrades
                    ->find('list', [
                        'keyField' => 'id',
                        'valueField' => 'name'
                    ])
                    ->select([
                        'id' => 'EducationGrades.id',
                        'name' => 'EducationGrades.name',
                        'education_programme_name' => 'EducationProgrammes.name'
                    ])
                    //->contain(['EducationProgrammes'])
                    ->contain(['EducationGrades.EducationProgrammes.EducationCycles.EducationLevels.EducationSystems'])
                    ->where([
                        $conditions,
                        'EducationSystems.academic_period_id' => $academicPeriodId,
                    ])
                    ->order([
                        'EducationProgrammes.order' => 'ASC',
                        'EducationGrades.name' => 'ASC'
                    ])
                    ->toArray();

                $attr['type'] = 'select';
                $attr['select'] = false;
                if (in_array($feature, ['Report.StudentAttendanceSummary', 'Report.ClassAttendanceNotMarkedRecords', 'Report.ClassAttendanceMarkedSummaryReport','Report.InstitutionClasses'])) {
                    $attr['options'] = ['-1' => __('All Grades')] + $gradeOptions;
                } else {
                    $attr['options'] = $gradeOptions;
                }
                $attr['onChangeReload'] = true;
            } elseif (in_array($feature,
                               [
                                   'Report.StudentAttendanceSummary',
                                   'Report.InstitutionSubjectsClasses'
                               ])
                      ) {
                $gradeList = [];
                if (array_key_exists('institution_id', $request->data[$this->alias()]) && !empty($request->data[$this->alias()]['institution_id']) && array_key_exists('academic_period_id', $request->data[$this->alias()]) && !empty($request->data[$this->alias()]['academic_period_id'])) {
                    $institutionId = $request->data[$this->alias()]['institution_id'];
                    $academicPeriodId = $request->data[$this->alias()]['academic_period_id'];

                    $InstitutionGradesTable = TableRegistry::get('Institution.InstitutionGrades');
                    $gradeList = $InstitutionGradesTable->getGradeOptions($institutionId, $academicPeriodId);

                }

                if (empty($gradeList)) {
                    $gradeOptions = ['' => $this->getMessage('general.select.noOptions')];
                } else {
                    if (!in_array($feature, ['Report.StudentAttendanceSummary'])) {
                        $gradeOptions = ['' => __('All Grades')] + $gradeList;
                    } else {
                        $gradeOptions = $gradeList;
                    }
                }

                $attr['type'] = 'select';
                $attr['select'] = false;
                $attr['options'] = $gradeOptions;
                $attr['attr']['required'] = true;
            } else {
                $attr['value'] = self::NO_FILTER;
            }
            return $attr;
        }
    }

    public function onUpdateFieldInstitutionTypeId(Event $event, array $attr, $action, Request $request)
    {
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];

            if (in_array($feature,
                        [
                            'Report.InstitutionSubjects',
                            'Report.StudentAttendanceSummary',
                            'Report.Guardians',
                            'Report.SubjectsBookLists',
                            'Report.InstitutionSubjects'
                        ])
                ) {


                $TypesTable = TableRegistry::get('Institution.Types');
                $typeOptions = $TypesTable
                    ->find('list')
                    ->find('visible')
                    ->find('order')
                    ->toArray();

                $attr['type'] = 'select';
                $attr['onChangeReload'] = true;

                if($feature == 'Report.StudentAttendanceSummary' || $feature == 'Report.SpecialNeedsFacilities' || $feature == 'Report.WashReports' || $feature == 'Report.InstitutionSubjects' || $feature == 'Report.Guardians' || $feature == 'Report.InstitutionInfrastructures') {
                    $attr['options'] = ['0' => __('All Types')] +  $typeOptions;
                } else {
                    $attr['options'] = $typeOptions;
                }

                $attr['attr']['required'] = true;
            }
            return $attr;
        }
    }

    public function onUpdateFieldInfrastructureLevel(Event $event, array $attr, $action, Request $request)
    {
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            if (in_array($feature,
                        [
                            'Report.InstitutionInfrastructures'
                        ])
                ) {

                $TypesTable = TableRegistry::get('infrastructure_levels');
                $typeOptions = $TypesTable
                    ->find('list')
                    ->toArray();

                $attr['type'] = 'select';
                $attr['onChangeReload'] = true;
                $attr['options'] = $typeOptions;
                $attr['attr']['required'] = true;
            }
            return $attr;
        }
    }

    public function onUpdateFieldInfrastructureType(Event $event, array $attr, $action, Request $request)
    {
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            if (in_array($feature,
                        [
                            //'Report.InstitutionInfrastructures'
                        ])
                ) {

                $TypesTable = TableRegistry::get('building_types');
                $typeOptions = $TypesTable
                    ->find('list')
                    ->toArray();

                $attr['type'] = 'select';
                $attr['onChangeReload'] = true;
                $attr['options'] = ['0' => __('All Infrastructure Type')] + $typeOptions;
                //$attr['attr']['required'] = true;
            }
            return $attr;
        }
    }


    public function onUpdateFieldInstitutionId(Event $event, array $attr, $action, Request $request)
    {
        $areaId = $request->data[$this->alias()]['area_education_id'];
        $InstitutionsTable = TableRegistry::get('Institution.Institutions');
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];

            $reportModels = [
                'Report.InstitutionSubjects',
                'Report.InstitutionSubjectsClasses',
                'Report.StudentAttendanceSummary',
                'Report.StaffAttendances',
                'Report.BodyMasses',
                'Report.WashReports',
                'Report.Guardians',
                'Report.InstitutionInfrastructures',
                'Report.InstitutionClasses',
                'Report.StudentWithdrawalReport',
                'Report.SpecialNeedsFacilities',
                'Report.InstitutionCommittees',
                'Report.SubjectsBookLists',
                'Report.StudentAbsences',
                'Report.InfrastructureNeeds',
                'Report.Income',
                'Report.Expenditure',
                'Report.StaffLeave', //POCOR-5762
                'Report.InstitutionAssociations',
                'Report.InstitutionPositions',
                'Report.InstitutionProgrammes',
                'Report.InstitutionStudents',
                'Report.InstitutionStaff',
                'Report.InstitutionSummaryReport',
                'Report.StaffTransfers',
                'Report.InstitutionCases',
                'Report.ClassAttendanceNotMarkedRecords',
                'Report.ClassAttendanceMarkedSummaryReport',
                'Report.InstitutionPositionsSummaries',
                'Report.StudentAbsencesPerDays' //POCOR-7276
            ];


            if (in_array($feature, $reportModels)) {
                $institutionList = [];
                if (array_key_exists('institution_type_id', $request->data[$this->alias()]) && !empty($request->data[$this->alias()]['institution_type_id'])) {
                    $institutionTypeId = $request->data[$this->alias()]['institution_type_id'];
                    if ($institutionTypeId > 0 && $areaId == -1) {
                       $institutionQuery = $InstitutionsTable
                        ->find('list', [
                            'keyField' => 'id',
                            'valueField' => 'code_name'
                        ])
                        ->where([
                            $InstitutionsTable->aliasField('institution_type_id') => $institutionTypeId
                        ])
                        ->order([
                            $InstitutionsTable->aliasField('code') => 'ASC',
                            $InstitutionsTable->aliasField('name') => 'ASC'
                        ]);

                        $superAdmin = $this->Auth->user('super_admin');
                        if (!$superAdmin) { // if user is not super admin, the list will be filtered
                            $userId = $this->Auth->user('id');
                            $institutionQuery->find('byAccess', ['userId' => $userId]);
                        }

                        $institutionList = $institutionQuery->toArray();
                    } else {
                        $institutionQuery = $InstitutionsTable
                        ->find('list', [
                            'keyField' => 'id',
                            'valueField' => 'code_name'
                        ])
                        ->where([
                            $InstitutionsTable->aliasField('institution_type_id') => $institutionTypeId,
                            $InstitutionsTable->aliasField('area_id') => $areaId
                        ])
                        ->order([
                            $InstitutionsTable->aliasField('code') => 'ASC',
                            $InstitutionsTable->aliasField('name') => 'ASC'
                        ]);

                        $superAdmin = $this->Auth->user('super_admin');
                        if (!$superAdmin) { // if user is not super admin, the list will be filtered
                            $userId = $this->Auth->user('id');
                            $institutionQuery->find('byAccess', ['userId' => $userId]);
                        }

                        $institutionList = $institutionQuery->toArray();
                    }
                } elseif (!$institutionTypeId && array_key_exists('area_education_id', $request->data[$this->alias()]) && !empty($request->data[$this->alias()]['area_education_id']) && $areaId != -1) {
                    /**POCOR-6896 starts - updated condition to fetch Institutions query on that bases of selected area level and area education*/
                    $areaIds = [];
                    $lft = $this->Areas->get($areaId)->lft;
                    $rgt = $this->Areas->get($areaId)->rght;
                    $areaFilter = $this->Areas->find('all')
                                ->select(['area_id' => $this->Areas->aliasField('id')])
                                ->where([
                                    $this->Areas->aliasField('lft >= ') => $lft,
                                    $this->Areas->aliasField('rght <=') => $rgt,
                                ])->toArray();
                    if (!empty($areaFilter)) {
                        foreach ($areaFilter as $area) {
                            $areaIds[] = $area->area_id;
                        }
                    }
                    $condition[$this->aliasField('area_id IN')] = $areaIds;
                    /**POCOR-6896 ends*/  
                    $institutionQuery = $InstitutionsTable
                        ->find('list', [
                            'keyField' => 'id',
                            'valueField' => 'code_name'
                        ])
                        ->where([$condition])
                        ->order([
                            $InstitutionsTable->aliasField('code') => 'ASC',
                            $InstitutionsTable->aliasField('name') => 'ASC'
                        ]);
                    $superAdmin = $this->Auth->user('super_admin');
                    if (!$superAdmin) { // if user is not super admin, the list will be filtered
                        $userId = $this->Auth->user('id');
                        $institutionQuery->find('byAccess', ['userId' => $userId]);
                    }

                    $institutionList = $institutionQuery->toArray();
                } else { //Institution Type = All Type
                    $InstitutionsTable = TableRegistry::get('Institution.Institutions');
                    $institutionQuery = $InstitutionsTable
                        ->find('list', [
                           'keyField' => 'id',
                            'valueField' => 'code_name'
                        ])
                        ->order([
                           $InstitutionsTable->aliasField('code') => 'ASC',
                            $InstitutionsTable->aliasField('name') => 'ASC'
                        ]);

                    $superAdmin = $this->Auth->user('super_admin');
                    if (!$superAdmin) { // if user is not super admin, the list will be filtered
                        $userId = $this->Auth->user('id');
                        $institutionQuery->find('byAccess', ['userId' => $userId]);
                    }

                    $institutionList = $institutionQuery->toArray();
                }

                if (empty($institutionList)) {
                    $institutionOptions = ['' => $this->getMessage('general.select.noOptions')];
                    $attr['type'] = 'select';
                    $attr['options'] = $institutionOptions;
                    $attr['attr']['required'] = true;
                } else {
                    if (in_array($feature, ['Report.BodyMasses', 'Report.InstitutionSubjects', 'Report.InstitutionClasses','Report.StudentWithdrawalReport','Report.StudentAbsences','Report.InstitutionSubjectsClasses', 'Report.SpecialNeedsFacilities', 'Report.Income', 'Report.Expenditure', 'Report.WashReports','Report.InstitutionInfrastructures', 'Report.StudentAttendanceSummary'])) {
                        /*POCOR-6304 Starts*/
                        if (count($institutionList) > 1) {
                           $institutionOptions = ['' => '-- ' . __('Select') . ' --', '0' => __('All Institutions')] + $institutionList;
                        } else {
                            $institutionOptions = ['' => '-- ' . __('Select') . ' --'] + $institutionList;
                        }
                        /*POCOR-6304 Ends*/
                    } else {
                        /*POCOR-6304 Starts*/
                        if (count($institutionList) > 1) {
                           $institutionOptions = ['' => '-- ' . __('Select') . ' --', '0' => __('All Institutions')] + $institutionList;
                        } else {
                            $institutionOptions = ['' => '-- ' . __('Select') . ' --'] + $institutionList;
                        }
                        /*POCOR-6304 Ends*/        
                    }

                    $attr['type'] = 'chosenSelect';
                    $attr['onChangeReload'] = true;
                    $attr['attr']['multiple'] = false;
                    $attr['options'] = $institutionOptions;
                    $attr['attr']['required'] = true;
                }
            }
            return $attr;
        }
    }

    public function onUpdateFieldReportStartDate(Event $event, array $attr, $action, Request $request)
    {
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            if (in_array($feature, ['Report.ClassAttendanceNotMarkedRecords',
                                    'Report.InstitutionCases',
                                    //'Report.StudentAttendanceSummary',
                                    'Report.ClassAttendanceMarkedSummaryReport',
                                    'Report.StaffAttendances'
                ]) && isset($this->request->data[$this->alias()]['academic_period_id'])
                ) {

                $academicPeriodId = $this->request->data[$this->alias()]['academic_period_id'];
                $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
                $selectedPeriod = $AcademicPeriods->get($academicPeriodId);
                $attr['type'] = 'date';
                $attr['date_options']['startDate'] = ($selectedPeriod->start_date)->format('d-m-Y');
                $attr['date_options']['endDate'] = ($selectedPeriod->end_date)->format('d-m-Y');
                $attr['value'] = $selectedPeriod->start_date;
            } elseif (in_array($feature, [
                                    'Report.StudentAttendanceSummary'
                ]) && isset($this->request->data[$this->alias()]['academic_period_id'])
                ) {

                $academicPeriodId = $this->request->data[$this->alias()]['academic_period_id'];
                $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
                $selectedPeriod = $AcademicPeriods->get($academicPeriodId);
                $attr['type'] = 'date';
                $attr['date_options']['startDate'] = ($selectedPeriod->start_date)->format('d-m-Y');
                $attr['date_options']['endDate'] = ($selectedPeriod->end_date)->format('d-m-Y');
                $attr['attr']['default'] = $selectedPeriod->start_date;
                $attr['onChangeReload'] = true;
                if ($attr['value'] > 0) {
                    $attr['value'] = $this->request->data[$this->alias()]['report_start_date'];
                } else {
                    if ($this->request->data[$this->alias()]['report_start_date'] != 0) {
                       $attr['value'] = $this->request->data[$this->alias()]['report_start_date'];
                    } else {
                        $attr['value'] = $selectedPeriod->start_date;
                    }
                }
            } elseif (in_array($feature, ['Report.StaffLeave'])) {
                $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
                $academicPeriodId = $AcademicPeriods->getCurrent();
                $selectedPeriod = $AcademicPeriods->get($academicPeriodId);
                $attr['type'] = 'date';
                $attr['date_options']['startDate'] = ($selectedPeriod->start_date)->format('d-m-Y');
                $attr['date_options']['endDate'] = ($selectedPeriod->end_date)->format('d-m-Y');
                $attr['value'] = $selectedPeriod->start_date;
            } else {
                $attr['value'] = self::NO_FILTER;
            }
            return $attr;
        }
    }

    public function onUpdateFieldReportEndDate(Event $event, array $attr, $action, Request $request)
    {
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            if (in_array($feature, ['Report.ClassAttendanceNotMarkedRecords',
                                    'Report.InstitutionCases',
                                    //'Report.StudentAttendanceSummary',
                                    'Report.ClassAttendanceMarkedSummaryReport',
                                    'Report.StaffAttendances'
                                    ])
                ) {

                $academicPeriodId = $this->request->data[$this->alias()]['academic_period_id'];
                $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
                $selectedPeriod = $AcademicPeriods->get($academicPeriodId);

                $attr['type'] = 'date';
                $attr['date_options']['startDate'] = ($selectedPeriod->start_date)->format('d-m-Y');
                $attr['date_options']['endDate'] = ($selectedPeriod->end_date)->format('d-m-Y');
                if ($academicPeriodId != $AcademicPeriods->getCurrent()) {
                    $attr['value'] = $selectedPeriod->end_date;
                }
                else {
                    $attr['value'] = Time::now();
                }
                //POCOR-5907[START]
                $attr['value'] = $selectedPeriod->end_date;
                //POCOR-5907[END]
            } elseif (in_array($feature, [
                                    'Report.StudentAttendanceSummary'
                                    ])
                ) {

                $academicPeriodId = $this->request->data[$this->alias()]['academic_period_id'];
                $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
                $selectedPeriod = $AcademicPeriods->get($academicPeriodId);

                $attr['type'] = 'date';
                if ($request->data['Institutions']['report_start_date'] != 0) {
                    $attr['date_options']['startDate'] = $request->data['Institutions']['report_start_date'];
                } else {
                    $attr['date_options']['startDate'] = ($selectedPeriod->start_date)->format('d-m-Y');
                }
                $date = $attr['date_options']['startDate'];
                $reportEndDate = date('d-m-Y',strtotime('+30 days',strtotime($date)));
                $attr['date_options']['endDate'] = $reportEndDate;
                if ($academicPeriodId == $AcademicPeriods->getCurrent()) {
                    $attr['value'] = $reportEndDate;
                } else {
                    $attr['value'] = Time::now();
                }
                $attr['value'] = $reportEndDate;
            } elseif (in_array($feature, ['Report.StaffLeave'])) {
                $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
                $academicPeriodId = $AcademicPeriods->getCurrent();
                $selectedPeriod = $AcademicPeriods->get($academicPeriodId);

                $attr['type'] = 'date';
                $attr['date_options']['startDate'] = ($selectedPeriod->start_date)->format('d-m-Y');
                $attr['date_options']['endDate'] = ($selectedPeriod->end_date)->format('d-m-Y');
                if ($academicPeriodId != $AcademicPeriods->getCurrent()) {
                    $attr['value'] = $selectedPeriod->end_date;
                } 
                else {
                    $attr['value'] = Time::now();
                }
                //POCOR-5907[START]
                $attr['value'] = $selectedPeriod->end_date;
                //POCOR-5907[END]
            } 


            else {
                $attr['value'] = self::NO_FILTER;
            }

            return $attr;
        }
    }

    //POCOR-7276
    public function onUpdateFieldAttendanceType(Event $event, array $attr, $action, Request $request)
    {

        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            if (in_array($feature, ['Report.StudentAbsencesPerDays'
                ]) && isset($this->request->data[$this->alias()]['academic_period_id'])
                ) {

                /*$StudentAttendanceTypes = TableRegistry::get('Attendance.StudentAttendanceTypes');
                $attendanceOptions = $StudentAttendanceTypes
                ->find('list')
                ->toArray();*/
            $StudentAttendanceTypes = array(1=>'Period');
            $attr['type'] = 'select';

            $attr['attr']['options'] = $StudentAttendanceTypes;
            $attr['onChangeReload'] = true;
            }
            return $attr;
        }
    }

    public function onUpdateFieldSubjects(Event $event, array $attr, $action, Request $request)
    {
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            if (in_array($feature, ['Report.ClassAttendanceMarkedSummaryReport'
                ]) && isset($this->request->data[$this->alias()]['academic_period_id'])
                ) {
                $academic_period_id = $this->request->data[$this->alias()]['academic_period_id'];
                $education_grade_id = $this->request->data[$this->alias()]['education_grade_id'];
                $attendance_type = $this->request->data[$this->alias()]['attendance_type'];
                $StudentAttendanceTypes = TableRegistry::get('Attendance.StudentAttendanceTypes');
                if (!empty($attendance_type)) {

                $attendanceTypeData = $StudentAttendanceTypes
                                        ->find()
                                        ->where([
                                            $StudentAttendanceTypes->aliasField('id') => $attendance_type
                                        ])
                                        ->toArray();
                $attendanceTypeCode = $attendanceTypeData[0]->code;
            }

                $InstitutionSubjects = TableRegistry::get('Institution.InstitutionSubjects');
                $gradeCondition = [];
                if ($attendanceTypeCode == 'SUBJECT') {

                    if ($education_grade_id != -1) {
                        $gradeCondition = [
                            $InstitutionSubjects->aliasField('academic_period_id') => $academic_period_id,
                            $InstitutionSubjects->aliasField('education_grade_id') => $education_grade_id];
                    } else {
                        $gradeCondition = [$InstitutionSubjects->aliasField('academic_period_id') => $academic_period_id];
                    }

                $institutionSubjects = $InstitutionSubjects
                                        ->find('list',
                                            ['keyField' => 'id',
                                            'valueField' => 'name'])
                                        ->where(
                                            $gradeCondition
                                        )
                                        ->group([
                                            $InstitutionSubjects->aliasField('name')
                                        ])
                                        ->toArray();

                $attr['type'] = 'select';
                $attr['options'] = ['0' => __('All Subjects')] + $institutionSubjects;

            return $attr;
            }
        }
        }
    }

    public function onUpdateFieldPeriods(Event $event, array $attr, $action, Request $request)
    {
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            if (in_array($feature, ['Report.ClassAttendanceMarkedSummaryReport'
                ]) && isset($this->request->data[$this->alias()]['academic_period_id'])
                ) {

                $academic_period_id = $this->request->data[$this->alias()]['academic_period_id'];
                $education_grade_id = $this->request->data[$this->alias()]['education_grade_id'];
                $attendance_type = $this->request->data[$this->alias()]['attendance_type'];

                $StudentAttendanceTypes = TableRegistry::get('Attendance.StudentAttendanceTypes');
                if (!empty($attendance_type)) {

                $attendanceTypeData = $StudentAttendanceTypes
                                        ->find()
                                        ->where([
                                            $StudentAttendanceTypes->aliasField('id') => $attendance_type
                                        ])
                                        ->toArray();
                $attendanceTypeCode = $attendanceTypeData[0]->code;
            }

                $StudentMarkTypeStatusGrades = TableRegistry::get('Attendance.StudentMarkTypeStatusGrades');
                $StudentMarkTypeStatuses = TableRegistry::get('Attendance.StudentMarkTypeStatuses');
                $StudentAttendancePerDayPeriods = TableRegistry::get('Attendance.StudentAttendancePerDayPeriods');

                $gradeCondition = [];
                if ($attendanceTypeCode == 'DAY' || $attendance_type == '') {
                    if ($education_grade_id != -1) {
                        $gradeCondition = [
                            $StudentMarkTypeStatuses->aliasField('academic_period_id') => $academic_period_id,
                            $StudentMarkTypeStatusGrades->aliasField('education_grade_id') => $education_grade_id];
                    } else {
                        $gradeCondition = [$StudentMarkTypeStatuses->aliasField('academic_period_id') => $academic_period_id];
                    }

               $periods = $StudentAttendancePerDayPeriods
                                        ->find('list',
                                            ['keyField' => 'id',
                                            'valueField' => 'name'
                                        ])
                                        ->leftJoin(
                                            [$StudentMarkTypeStatuses->alias() => $StudentMarkTypeStatuses->table()],
                                            [
                                                $StudentMarkTypeStatuses->aliasField('student_attendance_mark_type_id') . ' = '. $StudentAttendancePerDayPeriods->aliasField('student_attendance_mark_type_id')
                                            ]
                                        )
                                        ->leftJoin(
                                            [$StudentMarkTypeStatusGrades->alias() => $StudentMarkTypeStatusGrades->table()],
                                            [
                                                $StudentMarkTypeStatusGrades->aliasField('student_mark_type_status_id') . ' = '. $StudentMarkTypeStatuses->aliasField('id')
                                            ]
                                        )
                                        ->where(
                                            $gradeCondition
                                        )
                                        ->toArray();
                $attr['type'] = 'select';
                $attr['options'] = ['0' => __('All Periods')] + $periods;

            return $attr;
            }
        }
        }
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $filter = $requestData->institution_filter;
        $areaId = $requestData->area_education_id;
        $superAdmin = $requestData->super_admin;
        $userId = $requestData->user_id;
        $where = [];
        if ($areaId != -1) {
            $where[$this->aliasField('area_id')] = $areaId;
        }
        $query
            ->contain(['Areas', 'AreaAdministratives','Statuses'])
            ->select(['area_code' => 'Areas.code', 'area_administrative_code' => 'AreaAdministratives.code','institution_status'=>'Statuses.name'])
            ->where([$where]);
        switch ($filter) {
            case self::NO_STUDENT:
                $StudentsTable = TableRegistry::get('Institution.Students');
                $academicPeriodId = $requestData->academic_period_id;

                $query
                    ->leftJoin(
                        [$StudentsTable->alias() => $StudentsTable->table()],
                        [
                            $StudentsTable->aliasField('institution_id') . ' = '. $this->aliasField('id'),
                            $StudentsTable->aliasField('academic_period_id') => $academicPeriodId
                        ]
                    )
                    ->select(['student_count' => $query->func()->count('Students.id')])
                    ->group([$this->aliasField('id')])
                    ->having(['student_count' => 0]);
                break;

            case self::NO_STAFF:
                $query
                    ->leftJoin(
                        ['Staff' => 'institution_staff'],
                        [$this->aliasField('id').' = Staff.institution_id']
                    )
                    ->select(['staff_count' => $query->func()->count('Staff.id')])
                    ->group([$this->aliasField('id')])
                    ->having(['staff_count' => 0]);
                break;

            case self::NO_FILTER:
                break;
        }
        if (!$superAdmin) {
            $query->find('byAccess', ['user_id' => $userId, 'institution_field_alias' => $this->aliasField('id')]);
        }
    }

    public function onUpdateFieldEducationSubjectId(Event $event, array $attr, $action, Request $request)
    {
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            $institutionId = $this->request->data[$this->alias()]['institution_id'];
            $academicPeriodId = $this->request->data[$this->alias()]['academic_period_id'];
            if (in_array($feature,
                        [
                            'Report.InstitutionSubjects',
                            'Report.SubjectsBookLists'
                        ])
                ) {

                $EducationSubjects = TableRegistry::get('Education.EducationSubjects');
                $subjectOptions = $EducationSubjects
                    ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
                    ->find('visible')
                    ->order([
                        $EducationSubjects->aliasField('order') => 'ASC'
                    ])
                    ->toArray();

                $attr['type'] = 'select';
                $attr['select'] = false;

                if($feature == 'Report.InstitutionSubjects') {
                    $educationProgrammeid = $this->request->data['Institutions']['education_programme_id'];

                    if($educationProgrammeid == 0){
                        $attr['options'] = ['' => __('All Subjects')] + $subjectOptions;
                    }else{
                        $where = [];
                        if ($institutionId != 0) {
                            $where['InstitutionSubjects.institution_id'] = $institutionId;
                        }
                        if (!empty($academicPeriodId)) {
                            $where['InstitutionSubjects.academic_period_id'] = $academicPeriodId;
                        }
                        $EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');
                        $EducationGrades = TableRegistry::get('Education.EducationGrades');
                        $EducationSubjects = TableRegistry::get('Education.EducationSubjects');
                        $EducationGradesSubjects = TableRegistry::get('Education.EducationGradesSubjects');
                        $EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');
                        $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
                        $InstitutionSubjects = TableRegistry::get('Institution.InstitutionSubjects');
                        $subjectOptions = $EducationProgrammes
                            ->find()
                            ->select([
                                'EducationSubjects.name','EducationSubjects.id'
                            ])
                            ->innerJoin(
                                ['EducationGrades' => 'education_grades'],
                                ['EducationGrades.education_programme_id = ' . $EducationProgrammes->aliasField('id')]
                            )
                            ->innerJoin(['InstitutionGrades' => 'institution_grades'], ['InstitutionGrades.education_grade_id = ' . $EducationGrades->aliasField('id')
                            ])
                            ->innerJoin(['InstitutionSubjects' => 'institution_subjects'], [
                                'InstitutionSubjects.institution_id = ' . $InstitutionGrades->aliasField('institution_id'),
                                'InstitutionSubjects.education_grade_id = ' . $InstitutionGrades->aliasField('education_grade_id')
                            ])
                            ->innerJoin(['EducationSubjects' => 'education_subjects'], [
                                'EducationSubjects.id = ' . $InstitutionSubjects->aliasField('education_subject_id')
                            ])
                            ->where([
                                $EducationProgrammes->aliasField('id') => $educationProgrammeid,
                                $where
                            ])
                            ->group(['InstitutionSubjects.name'])
                            ->toArray();
                            $attr['type'] = 'select';
                            $attr['select'] = false;
                            foreach($subjectOptions AS $value){
                                $filteredSubjectOptions[$value->EducationSubjects['id']] = $value->EducationSubjects['name'];
                            }
                        $attr['options'] = $filteredSubjectOptions;
                    }
                } else {
                    $attr['options'] = $typeOptions;
                }
            } else {
                $attr['value'] = self::NO_FILTER;
            }
            return $attr;
        }
    }
 public function onUpdateFieldFromDate(Event $event, array $attr, $action, Request $request)
    {
        if (isset($request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];

            if ((in_array($feature, ['Report.Income']))) {
                $attr['type'] = 'date';
                return $attr;
            }
            if ((in_array($feature, ['Report.Expenditure']))) {
                $attr['type'] = 'date';
                return $attr;
            }
        }
    }


    public function onUpdateFieldToDate(Event $event, array $attr, $action, Request $request)
    {
        if (isset($request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];

            if ((in_array($feature, ['Report.Income']))) {
                $attr['type'] = 'date';
                return $attr;
            }
            if ((in_array($feature, ['Report.Expenditure']))) {
                $attr['type'] = 'date';
                return $attr;
            }
        }
    }

    //POCOR-5762 starts
    public function onUpdateFieldLeaveType(Event $event, array $attr, $action, Request $request)
    {
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            if (in_array($feature, ['Report.StaffLeave'])) {
                $staffLeaveTypeTable = TableRegistry::get('Staff.StaffLeaveTypes');
                $staffLeaveTypeOptions = $staffLeaveTypeTable->find('list', [
                            'keyField' => 'id',
                            'valueField' => 'name'
                        ]);

                $staffLeaveTypeList = $staffLeaveTypeOptions->toArray();
                if (empty($staffLeaveTypeList)) {
                    $staffLeaveTypeOptions = ['' => $this->getMessage('general.select.noOptions')];
                    $attr['type'] = 'select';
                    $attr['options'] = $staffLeaveTypeOptions;
                    $attr['attr']['required'] = true;
                } else {
                    if (in_array($feature, [
                        'Report.StaffLeave'
                    ])) {
                        $staffLeaveTypeOptions = ['0' => __('All Staff Leaves')] + $staffLeaveTypeList;
                    }else {
                        $staffLeaveTypeOptions = $staffLeaveTypeList;
                    }

                    $attr['type'] = 'chosenSelect';
                    $attr['onChangeReload'] = true;
                    $attr['attr']['multiple'] = false;
                    $attr['options'] = $staffLeaveTypeOptions;
                    //$attr['attr']['required'] = true;
                }
            }
            return $attr;
        }
    }

    public function onUpdateFieldWorkflowStatus(Event $event, array $attr, $action, Request $request)
    {
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            if (in_array($feature, ['Report.StaffLeave'])) {
                $institutionStaffLeave = TableRegistry::get('institution_staff_leave');
                $workflowModelsTable = TableRegistry::get('workflow_models');
                $workflowsTable = TableRegistry::get('workflows');

                $workflowStepsTable = TableRegistry::get('workflow_steps');
                $workflowStepsOptions = $workflowStepsTable
                        ->find('list', [
                            'keyField' => 'id',
                            'valueField' => 'name'
                        ])
                        ->select([
                            $workflowStepsTable->aliasField('id'),
                            $workflowStepsTable->aliasField('name'),
                            $workflowModelsTable->aliasField('model')
                        ])
                        ->LeftJoin(
                            [$institutionStaffLeave->alias() => $institutionStaffLeave->table()],
                            [
                                $institutionStaffLeave->aliasField('status_id') . ' = '. $workflowStepsTable->aliasField('id')
                            ]
                        )
                        ->LeftJoin(
                            [$workflowsTable->alias() => $workflowsTable->table()],
                            [
                                $workflowsTable->aliasField('id') . ' = '. $workflowStepsTable->aliasField('workflow_id')
                            ]
                        )
                        ->LeftJoin(
                            [$workflowModelsTable->alias() => $workflowModelsTable->table()],
                            [
                                $workflowModelsTable->aliasField('id') . ' = '. $workflowsTable->aliasField('workflow_model_id')
                            ]
                        )
                        ->where([
                            $workflowModelsTable->aliasField('model') => 'Institution.StaffLeave'
                        ]);
                $institutionStaffLeaveList = $workflowStepsOptions->toArray();
                if (empty($institutionStaffLeaveList)) {
                    $workflowStepsOptions = ['' => $this->getMessage('general.select.noOptions')];
                    $attr['type'] = 'select';
                    $attr['options'] = $workflowStepsOptions;
                    $attr['attr']['required'] = true;
                } else {
                    if (in_array($feature, [
                        'Report.StaffLeave'
                    ])) {
                        $workflowStepsOptions = ['0' => __('All Status')] + $institutionStaffLeaveList;
                    }else {
                        $workflowStepsOptions = $institutionStaffLeaveList;
                    }

                    $attr['type'] = 'chosenSelect';
                    $attr['onChangeReload'] = true;
                    $attr['attr']['multiple'] = false;
                    $attr['options'] = $workflowStepsOptions;
                    //$attr['attr']['required'] = true;
                }
            }
            return $attr;
        }
    }

//POCOR-6952
    public function onUpdateFieldPositionStatus(Event $event, array $attr, $action, Request $request)
    {
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            if (in_array($feature, ['Report.InstitutionPositionsSummaries'])) {
                $institutionStaffLeave = TableRegistry::get('institution_staff_leave');
                $workflowModelsTable = TableRegistry::get('workflow_models');
                $workflowsTable = TableRegistry::get('workflow_statuses');
                $workflowsData = TableRegistry::get('workflows');
                $status = array('Active', 'Inactive');
                $workflowStepsTable = TableRegistry::get('workflow_steps');
                //POCOR-7445 start
                $workflowModel = $workflowsData->find()->where([$workflowsData->aliasField('code') => 'POSITION-1001'])->first()->id;
                $workflowStepsOptions = $workflowStepsTable
                        ->find('list', [
                            'keyField' => 'id',
                            'valueField' => 'name'
                        ])
                        ->where(['workflow_id'=> $workflowModel,$workflowStepsTable->aliasField('name IN') =>$status]);
                //POCOR-7445 end
                $institutionStaffLeaveList = $workflowStepsOptions->toArray();
                if (empty($institutionStaffLeaveList)) {
                    $workflowStepsOptions = ['' => $this->getMessage('general.select.noOptions')];
                    $attr['type'] = 'select';
                    $attr['options'] = $workflowStepsOptions;
                    $attr['attr']['required'] = true;
                } else {
                    if (in_array($feature, [
                        'Report.InstitutionPositionsSummaries'
                    ])) {
                        $workflowStepsOptions = ['0' => __('All Status')] + $institutionStaffLeaveList;
                    }else {
                        $workflowStepsOptions = $institutionStaffLeaveList;
                    }

                    $attr['type'] = 'chosenSelect';
                    $attr['onChangeReload'] = true;
                    $attr['attr']['multiple'] = false;
                    $attr['options'] = $workflowStepsOptions;
                }

               // echo "<pre>";print_r($attr);die;
            }
            return $attr;
        }
    }
//POCOR-6952
    public function onUpdateFieldPosition(Event $event, array $attr, $action, Request $request)
    {
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            if (in_array($feature, ['Report.StaffLeave'])) {
                $staffPositionTitlesTable = TableRegistry::get('staff_position_titles');
                $institutionPositionsTable = TableRegistry::get('institution_positions');
                $institutionPositionsOptions = $institutionPositionsTable
                        ->find('list', [
                            'keyField' => $staffPositionTitlesTable->aliasField('id'),
                            'valueField' => $staffPositionTitlesTable->aliasField('name')
                        ])
                        ->select([
                            $staffPositionTitlesTable->aliasField('id'),
                            $staffPositionTitlesTable->aliasField('name')
                        ])
                        ->RightJoin(
                            [$staffPositionTitlesTable->alias() => $staffPositionTitlesTable->table()],
                            [
                                $institutionPositionsTable->aliasField('staff_position_title_id') . ' = '. $staffPositionTitlesTable->aliasField('id')
                            ]
                        );
                $staffPositionTitlesList = $institutionPositionsOptions->toArray();
                if (empty($staffPositionTitlesList)) {
                    $institutionPositionsOptions = ['' => $this->getMessage('general.select.noOptions')];
                    $attr['type'] = 'select';
                    $attr['options'] = $institutionPositionsOptions;
                    $attr['attr']['required'] = true;
                } else {
                    if (in_array($feature, [
                        'Report.StaffLeave'
                    ])) {
                        $institutionPositionsOptions = ['0' => __('All Positions')] + $staffPositionTitlesList;
                    }else {
                        $institutionPositionsOptions = $staffPositionTitlesList;
                    }

                    $attr['type'] = 'chosenSelect';
                    $attr['onChangeReload'] = true;
                    $attr['attr']['multiple'] = false;
                    $attr['options'] = $institutionPositionsOptions;
                    //$attr['attr']['required'] = true;
                }
            }
            return $attr;
        }
    }
    //POCOR-5762 ends

    public function onUpdateFieldEducationLevelId(Event $event, array $attr, $action, Request $request)
    {
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            $academicPeriodId = $this->request->data[$this->alias()]['academic_period_id'];
            
            if (in_array($feature,
                        [
                            'Report.InstitutionStudents'
                        ])
                ) {

                $EducationLevels = TableRegistry::get('Education.EducationLevels');
                $levelOptions = $EducationLevels->find('list', ['valueField' => 'system_level_name'])
                ->find('visible')
                ->find('order')
                ->contain(['EducationSystems'])
                ->where(['EducationSystems.academic_period_id' => $academicPeriodId])
                ->toArray();

                $attr['type'] = 'select';
                $attr['select'] = false;
                if (count($levelOptions) > 1) {
                    $attr['options'] = ['' => '-- ' . _('Select') . ' --', 0 => _('All Level')] + $levelOptions;
                } else {
                    $attr['options'] = ['' => '-- ' . _('Select') . ' --'] + $levelOptions;
                }
                /*POCOR-6337 starts*/
                $attr['onChangeReload'] = true;
            } else {
                $attr['value'] = self::NO_FILTER;
            }
            return $attr;
        }
    }
    public function validationStudentAbsencesPerDays(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        $validator = $validator
            ->notEmpty('institution_id');
        return $validator;
    }


    // Start POCOR-7358
    public function onExcelGetContactPerson(Event $event, Entity $entity)
    {
        $institution_contact_persons = TableRegistry::get('institution_contact_persons')->find()->where(['institution_id' => $entity['id']])->where(['preferred' => 1])->order(['id'=>'DESC'])->first();
        if(!empty($institution_contact_persons)){
            return $institution_contact_persons['contact_person'];
        }
        return '';
    }
    // End POCOR-7358
}
