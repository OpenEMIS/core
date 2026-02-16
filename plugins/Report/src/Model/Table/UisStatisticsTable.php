<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\EventInterface;
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
use Cake\Http\ServerRequest;

class UisStatisticsTable extends AppTable
{
    use OptionsTrait;
    private $classificationOptions = [];

    // filter
    const NO_FILTER = 0;
    const NO_STUDENT = 1;
    const NO_STAFF = 2;

    public function initialize(array $config): void
    {
        $this->setTable('institutions');
        parent::initialize($config);

        $this->belongsTo('Localities', ['className' => 'Institution.Localities', 'foreignKey' => 'institution_locality_id']);
        $this->belongsTo('Types', ['className' => 'Institution.Types', 'foreignKey' => 'institution_type_id']);
        $this->belongsTo('Ownerships', ['className' => 'Institution.Ownerships', 'foreignKey' => 'institution_ownership_id']);
        $this->belongsTo('Statuses', ['className' => 'Institution.Statuses', 'foreignKey' => 'institution_status_id']);
        $this->belongsTo('Sectors', ['className' => 'Institution.Sectors', 'foreignKey' => 'institution_sector_id']);
        $this->belongsTo('Providers', ['className' => 'Institution.Providers', 'foreignKey' => 'institution_provider_id']);
        $this->belongsTo('Genders', ['className' => 'Institution.Genders', 'foreignKey' => 'institution_gender_id']);
        $this->belongsTo('AreaLevels', ['className' => 'Area.AreaLevels']);
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

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);

        // validation for attendance marked record feature
        
       

        return $validator;
    }

   
    public function beforeAction(EventInterface $event)
    {
        $this->fields = [];
        $controllerName = $this->controller->getName();
        $reportName = __('UIS Statistics');
		$this->controller->Navigation->substituteCrumb($this->getAlias(), $reportName);
		$this->controller->set('contentHeader', __($controllerName).' - '.$reportName);

        $this->ControllerAction->field('feature', ['select' => false]);
        $this->ControllerAction->field('format');
    }

    public function onGetReportName(EventInterface $event, ArrayObject $data)
    {
        return __('Overview');
    }

    public function addBeforeAction(EventInterface $event)
    {
        $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);

        $this->ControllerAction->field('area_level_id', ['type' => 'hidden']);
        $this->ControllerAction->field('area_education_id', ['type' => 'hidden']);
        $this->ControllerAction->field('institution_filter', ['type' => 'hidden']);
        $this->ControllerAction->field('position_filter', ['type' => 'hidden']);
        $this->ControllerAction->field('teaching_filter', ['type' => 'hidden']);
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
        $this->ControllerAction->field('periods', ['type' => 'hidden']);
        $this->ControllerAction->field('subjects', ['type' => 'hidden']);
        $this->ControllerAction->field('wash_type', ['type' => 'hidden']);
        $this->ControllerAction->field('education_subject_id', ['type' => 'hidden']);
        $this->ControllerAction->field('education_grade_id', ['type' => 'hidden']);
        $this->ControllerAction->field('infrastructure_level', ['type' => 'hidden']);
        $this->ControllerAction->field('infrastructure_type', ['type' => 'hidden']);
        
        $this->ControllerAction->field('position', ['type' => 'hidden']);
        $this->ControllerAction->field('leave_type', ['type' => 'hidden']);
        $this->ControllerAction->field('workflow_status', ['type' => 'hidden']);
        
        $this->ControllerAction->field('education_level_id', ['type' => 'hidden']);
    }

    public function addBeforePatch(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        if ($data[$this->getAlias()]['feature'] == 'Report.InstitutionSubjectsClasses') {
            $options['validate'] = 'subjectsClasses';
        }
        if ($data[$this->getAlias()]['feature'] == 'Report.InstitutionSubjects') {
            $options['validate'] = 'subjects';
        } elseif ($data[$this->getAlias()]['feature'] == 'Report.InstitutionStudents') {
            $options['validate'] = 'students';
        } elseif ($data[$this->getAlias()]['feature'] == 'Report.InstitutionStaff') {
            $options['validate'] = 'staff';
        } elseif ($data[$this->getAlias()]['feature'] == 'Report.StudentAttendanceSummary') {
            $options['validate'] = 'studentAttendanceSummary';
        } elseif ($data[$this->getAlias()]['feature'] == 'Report.StaffAttendances') {
            $options['validate'] = 'staffAttendances';
        } elseif ($data[$this->getAlias()]['feature'] == 'Report.BodyMasses') {
            $options['validate'] = 'bodyMasses';
        } elseif ($data[$this->getAlias()]['feature'] == 'Report.Guardians') {
            $options['validate'] = 'guardians';
        } elseif ($data[$this->getAlias()]['feature'] == 'Report.InstitutionInfrastructures') {
            $options['validate'] = 'institutionInfrastructures';
        } elseif ($data[$this->getAlias()]['feature'] == 'Report.InfrastructureNeeds') {
            $options['validate'] = 'infrastructureNeeds';
        } elseif ($data[$this->getAlias()]['feature'] == 'Report.StaffLeave') { 
            $options['validate'] = 'StaffLeave';
        }

    }

    public function addAfterAction(EventInterface $event, Entity $entity)
    {
        if ($entity->has('feature')) {
            $feature = $entity->feature;

            $fieldsOrder = ['feature'];
            switch ($feature) {
                case 'Report.Institutions':
                    $fieldsOrder[] = 'area_level_id';
                    $fieldsOrder[] = 'area_education_id';
                    $fieldsOrder[] = 'institution_filter';
                    $fieldsOrder[] = 'format';
                    break;
                case 'Report.InstitutionAssociations':
                case 'Report.InstitutionProgrammes':
                case 'Report.InstitutionClasses':
                case 'Report.StudentAbsences':
                case 'Report.StudentWithdrawalReport':
                case 'Report.InstitutionSummaryReport':
                case 'Report.BodyMasses':
                case 'Report.StaffAttendances':
                case 'Report.StaffTransfers':
                case 'Report.SpecialNeedsFacilities':
                case 'Report.InstitutionCommittees':
                   
                case 'Report.Uis2':
                    $fieldsOrder[] = 'academic_period_id';
                    $fieldsOrder[] = 'format';
                case 'Report.Uis3':
                    $fieldsOrder[] = 'academic_period_id';
                    $fieldsOrder[] = 'format';
                case 'Report.Uis5':
                    $fieldsOrder[] = 'academic_period_id';
                    $fieldsOrder[] = 'format';
                case 'Report.Uis6':
                    $fieldsOrder[] = 'academic_period_id';
                    $fieldsOrder[] = 'format';
                case 'Report.Uis9':
                    $fieldsOrder[] = 'academic_period_id';
                    $fieldsOrder[] = 'format';   
                case 'Report.Uis10':
                    $fieldsOrder[] = 'academic_period_id';
                    $fieldsOrder[] = 'format';
                case 'Report.Uis102':
                    $fieldsOrder[] = 'academic_period_id';
                    $fieldsOrder[] = 'format';
                case 'Report.Uis13':
                    $fieldsOrder[] = 'academic_period_id';
                    $fieldsOrder[] = 'format'; 
                   
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
                    $fieldsOrder[] = 'teaching_filter';
                    $fieldsOrder[] = 'status'; 
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
                default:
                    break;
            }

            $this->ControllerAction->setFieldOrder($fieldsOrder);
        }
    }

    public function onExcelBeforeStart(EventInterface $event, ArrayObject $settings, ArrayObject $sheets)
    {

        $requestData = json_decode($settings['process']['params']);
        $feature = $requestData->feature;
        $filter = $requestData->institution_filter;
        if ($feature == 'Report.Institutions' && $filter != self::NO_FILTER) {
            $sheets[] = [
                'name' => $this->getAlias(),
                'table' => $this,
                'query' => $this->find(),
            ];
            // Stop the customfieldlist behavior onExcelBeforeStart function
            $event->stopPropagation();
        }
    }

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, $fields)
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

    public function onExcelGetShiftType(EventInterface $event, Entity $entity)
    {

        if (isset($this->shiftTypes[$entity->shift_type])) {
            return __($this->shiftTypes[$entity->shift_type]);
        } else {
            return '';
        }
    }


    public function onExcelGetClassification(EventInterface $event, Entity $entity)
    {
        return __($this->classificationOptions[$entity->classification]);
    }

    public function onUpdateFieldFeature(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $option = $this->controller->getFeatureOptions($this->getAlias());
            $attr['options'] = $this->controller->getFeatureOptions($this->getAlias());
            $attr['onChangeReload'] = true;
            if (!(isset($this->request->getData($this->getAlias())['feature']))) {
                $option = $attr['options'];
                reset($option);
                $defaultFeatureValue = key($option);
                $this->request = $this->request->withData($this->getAlias() . '.feature', $defaultFeatureValue);
            }
            return $attr;
        }
    }


  

    public function onUpdateFieldStatus(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];
            if (in_array($feature,
                        [
                            'Report.InstitutionStudents',
                            'Report.InstitutionStudentEnrollments',
                            'Report.InstitutionStaff',
                            'Report.InstitutionPositions'  
                        ])
                ) {
                // need to find all status
                $statusOptions = [];

                switch ($feature) {
                    case 'Report.InstitutionStudents':
                    case 'Report.InstitutionStudentEnrollments':
                        $Statuses = TableRegistry::getTableLocator()->get('Student.StudentStatuses');
                        $statusData = $Statuses->find()->select(['id', 'name'])->toArray();
                        foreach ($statusData as $key => $value) {
                            $statusOptions[$value->id] = $value->name;
                        }
                        break;

                    case 'Report.InstitutionStaff':
                        $Statuses = TableRegistry::getTableLocator()->get('Staff.StaffStatuses');
                        $statusData = $Statuses->getList();
                        foreach ($statusData as $key => $value) {
                            $statusOptions[$key] = $value;
                        }
                        break;

                    
                    case 'Report.InstitutionPositions':
                        $Workflows = TableRegistry::getTableLocator()->get('Workflow.Workflows');
                        $Statuses = TableRegistry::getTableLocator()->get('Workflow.WorkflowSteps');
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

    public function onUpdateFieldAcademicPeriodId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {

        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];

            if ((in_array($feature,
                         [
                             
                             'Report.Uis2',
                             'Report.Uis3',
                             'Report.Uis5',
                             'Report.Uis6',
                             'Report.Uis9',
                             'Report.Uis10',
                             'Report.Uis102',
                             'Report.Uis13',
                             
                         ]
                    )) ||((in_array($feature, ['Report.Institutions']) && !empty($request->getData($this->getAlias())['institution_filter']) && $request->getData($this->getAlias())['institution_filter'] == self::NO_STUDENT))) {

                $AcademicPeriodTable = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
                $academicPeriodOptions = $AcademicPeriodTable->getYearList();
                $currentPeriod = $AcademicPeriodTable->getCurrent();
                $attr['options'] = $academicPeriodOptions;
                $attr['type'] = 'select';
                $attr['select'] = false;
                $attr['onChangeReload'] = true;
                if (empty($request->getData($this->getAlias())['academic_period_id'])) {
                    $request->getData($this->getAlias())['academic_period_id'] = $currentPeriod;
                }
                return $attr;
            }
        }
    }
    
   
    public function onUpdateFieldFromDate(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];

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


    public function onUpdateFieldToDate(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];

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

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'feature':
                return __('Feature');
            case 'format':
                return __('Format');
            case 'academic_period_id':
                return __('Academic Period');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}
