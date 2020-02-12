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
                        return in_array($feature, ['Report.ClassAttendanceNotMarkedRecords', 'Report.InstitutionCases', 'Report.StudentAttendanceSummary']);
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
            ]);

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
            ->notEmpty('institution_type_id')
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
            ->notEmpty('institution_type_id')
            ->notEmpty('institution_id');
        return $validator;
    }

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
        $this->ControllerAction->field('institution_filter', ['type' => 'hidden']);
        $this->ControllerAction->field('position_filter', ['type' => 'hidden']);
        // $this->ControllerAction->field('license', ['type' => 'hidden']);
        $this->ControllerAction->field('type', ['type' => 'hidden']);
        $this->ControllerAction->field('status', ['type' => 'hidden']);
        $this->ControllerAction->field('module', ['type' => 'hidden']);
        $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);
        $this->ControllerAction->field('institution_type_id', ['type' => 'hidden']);
        $this->ControllerAction->field('institution_id', ['type' => 'hidden']);
        $this->ControllerAction->field('education_programme_id', ['type' => 'hidden']);
        $this->ControllerAction->field('education_grade_id', ['type' => 'hidden']);
        $this->ControllerAction->field('report_start_date', ['type' => 'hidden']);
        $this->ControllerAction->field('report_end_date', ['type' => 'hidden']);
    }

    public function addBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
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
        }
    }

    public function addAfterAction(Event $event, Entity $entity)
    {
        if ($entity->has('feature')) {
            $feature = $entity->feature;

            $fieldsOrder = ['feature', 'format'];
            switch ($feature) {
                case 'Report.StudentAttendanceSummary':
                    $fieldsOrder[] = 'institution_type_id';
                    $fieldsOrder[] = 'institution_id';
                    $fieldsOrder[] = 'academic_period_id';
                    $fieldsOrder[] = 'education_grade_id';
                    break;                
                
                default:
                    break;
            }

            $this->ControllerAction->setFieldOrder($fieldsOrder);
        }
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
            if (in_array($feature, ['Report.InstitutionStudents', 'Report.InstitutionStudentEnrollments', 'Report.InstitutionStaff'])) {
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
            if ((in_array($feature, ['Report.InstitutionStudents', 'Report.StudentAbsences', 'Report.StaffLeave', 'Report.InstitutionCases', 'Report.ClassAttendanceNotMarkedRecords', 'Report.InstitutionSubjects', 'Report.StudentAttendanceSummary', 'Report.StaffAttendances', 'Report.BodyMasses', 'Report.InstitutionSpecialNeedsStudents','Report.InstitutionStudentsWithSpecialNeeds']))
                ||((in_array($feature, ['Report.Institutions']) && !empty($request->data[$this->alias()]['institution_filter']) && $request->data[$this->alias()]['institution_filter'] == self::NO_STUDENT))) {

                $AcademicPeriodTable = TableRegistry::get('AcademicPeriod.AcademicPeriods');
                $academicPeriodOptions = $AcademicPeriodTable->getYearList();
                $currentPeriod = $AcademicPeriodTable->getCurrent();

                $attr['options'] = $academicPeriodOptions;
                $attr['type'] = 'select';
                $attr['select'] = false;
                if (in_array($feature, ['Report.ClassAttendanceNotMarkedRecords', 'Report.InstitutionCases', 'Report.StudentAttendanceSummary', 'Report.StaffAttendances'])) {
                    $attr['onChangeReload'] = true;
                }

                if (empty($request->data[$this->alias()]['academic_period_id'])) {
                    $request->data[$this->alias()]['academic_period_id'] = $currentPeriod;
                }
                return $attr;
            }
        }
    }

    public function onUpdateFieldEducationProgrammeId(Event $event, array $attr, $action, Request $request)
    {
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            if (in_array($feature, ['Report.InstitutionStudents'])) {
                $EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');
                $programmeOptions = $EducationProgrammes
                    ->find('list', ['keyField' => 'id', 'valueField' => 'cycle_programme_name'])
                    ->find('visible')
                    ->contain(['EducationCycles'])
                    ->order([
                        'EducationCycles.order' => 'ASC',
                        $EducationProgrammes->aliasField('order') => 'ASC'
                    ])
                    ->toArray();

                $attr['type'] = 'select';
                $attr['select'] = false;
                $attr['options'] = ['0' => __('All Programmes')] + $programmeOptions;
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
            if (in_array($feature, ['Report.ClassAttendanceNotMarkedRecords'])) {
                $EducationGrades = TableRegistry::get('Education.EducationGrades');
                $gradeOptions = $EducationGrades
                    ->find('list', [
                        'keyField' => 'id',
                        'valueField' => 'name'
                    ])
                    ->select([
                        'id' => $EducationGrades->aliasField('id'),
                        'name' => $EducationGrades->aliasField('name'),
                        'education_programme_name' => 'EducationProgrammes.name'
                    ])
                    ->contain(['EducationProgrammes'])
                    ->order([
                        'EducationProgrammes.order' => 'ASC',
                        $EducationGrades->aliasField('name') => 'ASC'
                    ])
                    ->toArray();

                $attr['type'] = 'select';
                $attr['select'] = false;
                $attr['options'] = ['-1' => __('All Grades')] + $gradeOptions;
            } elseif (in_array($feature, ['Report.StudentAttendanceSummary'])) {
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
                    $gradeOptions = ['-1' => __('All Grades')] + $gradeList;
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
            if (in_array($feature, ['Report.InstitutionSubjects', 'Report.StudentAttendanceSummary','Report.StaffAttendances', 'Report.BodyMasses'])) {
                $TypesTable = TableRegistry::get('Institution.Types');
                $typeOptions = $TypesTable
                    ->find('list')
                    ->find('visible')
                    ->find('order')
                    ->toArray();

                $attr['type'] = 'select';
                $attr['onChangeReload'] = true;
                $attr['options'] = $typeOptions;
                $attr['attr']['required'] = true;
            }
            return $attr;
        }
    }

    public function onUpdateFieldInstitutionId(Event $event, array $attr, $action, Request $request)
    {
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            if (in_array($feature, ['Report.InstitutionSubjects', 'Report.StudentAttendanceSummary','Report.StaffAttendances', 'Report.BodyMasses'])) {
                $institutionList = [];
                if (array_key_exists('institution_type_id', $request->data[$this->alias()]) && !empty($request->data[$this->alias()]['institution_type_id'])) {
                    $institutionTypeId = $request->data[$this->alias()]['institution_type_id'];

                    $InstitutionsTable = TableRegistry::get('Institution.Institutions');
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
                }

                if (empty($institutionList)) {
                    $institutionOptions = ['' => $this->getMessage('general.select.noOptions')];
                    $attr['type'] = 'select';
                    $attr['options'] = $institutionOptions;
                    $attr['attr']['required'] = true;
                } else {

                    if (in_array($feature, ['Report.BodyMasses'])) {
                        $institutionOptions = ['' => '-- ' . __('Select') . ' --', '0' => __('All Institutions')] + $institutionList;
                    } else {
                        $institutionOptions = ['' => '-- ' . __('Select') . ' --'] + $institutionList;
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
            if (in_array($feature, ['Report.ClassAttendanceNotMarkedRecords', 'Report.InstitutionCases','Report.StudentAttendanceSummary']) && isset($this->request->data[$this->alias()]['academic_period_id'])) {
                $academicPeriodId = $this->request->data[$this->alias()]['academic_period_id'];
                $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
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
            if (in_array($feature, ['Report.ClassAttendanceNotMarkedRecords', 'Report.InstitutionCases','Report.StudentAttendanceSummary'])) {
                $academicPeriodId = $this->request->data[$this->alias()]['academic_period_id'];
                $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
                $selectedPeriod = $AcademicPeriods->get($academicPeriodId);

                $attr['type'] = 'date';
                $attr['date_options']['startDate'] = ($selectedPeriod->start_date)->format('d-m-Y');
                $attr['date_options']['endDate'] = ($selectedPeriod->end_date)->format('d-m-Y');
                if ($academicPeriodId != $AcademicPeriods->getCurrent()) {
                    $attr['value'] = $selectedPeriod->end_date;
                } else {
                    $attr['value'] = Time::now();
                }
            } else {
                $attr['value'] = self::NO_FILTER;
            }
            return $attr;
        }
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $filter = $requestData->institution_filter;
        $superAdmin = $requestData->super_admin;
        $userId = $requestData->user_id;
        $query
            ->contain(['Areas', 'AreaAdministratives'])
            ->select(['area_code' => 'Areas.code', 'area_administrative_code' => 'AreaAdministratives.code']);
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
}
