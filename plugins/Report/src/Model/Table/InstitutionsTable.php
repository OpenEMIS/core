<?php

namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use Cake\I18n\FrozenTime;
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
        $this->belongsTo('AreaLevels', ['className' => 'Area.AreaLevels']); // POCOR-8157
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
        $this->addBehavior('ControllerAction.FileUpload');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        // validation for attendance marked record feature
        $validator
            ->add('report_start_date', [
                'ruleCompareDate' => [
                    'rule' => ['compareDate', 'report_end_date', true],
                    'on' => function ($context) {
                        $feature = $context['data']['feature'];
                        return in_array($feature, [
                            'Report.ClassAttendanceNotMarkedRecords',
                            'Report.InstitutionCases',
                            'Report.StudentAttendanceSummary',
                            'Report.ClassAttendanceMarkedSummaryReport',
                            'Report.StudentAbsences'
                        ]);
                    }
                ],
                'ruleInAcademicPeriod' => [
                    'rule' => ['inAcademicPeriod', 'academic_period_id', []],
                    'on' => function ($context) {
                        $feature = $context['data']['feature'];
                        return in_array($feature, ['Report.ClassAttendanceNotMarkedRecords', 'Report.InstitutionCases', 'Report.StudentAttendanceSummary', 'Report.StudentAbsences']);
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
                        return in_array($feature, ['Report.ClassAttendanceNotMarkedRecords', 'Report.InstitutionCases', 'Report.StudentAttendanceSummary', 'Report.StudentAbsences']);
                    },
                    'message' => __('Report End Date should be earlier than Academic Period End Date')
                ],
                'ruleForOneMonthDate' => [
                    'rule' => ['forOneMonthDate'],
                    'on' => function ($context) {
                        $feature = $context['data']['feature'];
                        return in_array($feature, ['Report.StudentAttendanceSummary', 'Report.StudentAbsences']);
                    },
                    'message' => __('Date range should be one month only')
                ]
            ]);


        $feature = $this->request->getData($this->getAlias())['feature']; //POCOR-6333
        if (in_array($feature, ['Report.StaffBehaviours', 'Report.StudentAbsencesPerDays', 'Report.StudentBehaviours',])) {
            $validator = $validator
                ->notEmpty('area_level_id')
                ->notEmpty('area_education_id');
        }

        if (in_array($feature, ['Report.WashReports', 'Report.StudentAbsencesPerDaysTable'])) {
            $validator = $validator
                ->notEmpty('institution_id');
        }

        if ($feature == 'Report.Institutions') { //POCOR-8417
            $validator
                ->notEmpty('area_level_id', __('This field cannot be left empty'))
                ->notEmpty('area_education_id', __('This field cannot be left empty'));

            $validator->add('institution_id', 'required', [
                'rule' => function ($value, $context) {
                    if (!empty($context['data']['reload'])) {
                        return true;
                    }
                    if (empty($value) || !isset($value['_ids'])) {
                        return false;
                    }
                    $ids = (array)$value['_ids'];
                    $ids = array_filter($ids, function($v) {
                        return $v !== '' && $v !== null;
                    });

                    return !empty($ids);
                },
                'message' => __('This field cannot be left empty')
            ]);
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
            ->notEmpty('institution_id') //POCOR-9345
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

    public function validationStudentAbsences(Validator $validator)
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

    /*public function validationInstitutionInfrastructures(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        $validator = $validator
            //->notEmpty('institution_type_id')
            ->notEmpty('institution_id')
            ->notEmpty('infrastructure_level');
        return $validator;
    }*/

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
    } //POCOR-5762 ends

    public function beforeAction(EventInterface $event)
    {
        $this->fields = [];
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
        $this->ControllerAction->field('teaching_filter', ['type' => 'hidden']); //POCOR-6614
        // $this->ControllerAction->field('license', ['type' => 'hidden']);
        $this->ControllerAction->field('type', ['type' => 'hidden']);
        $this->ControllerAction->field('status', ['type' => 'hidden']);
        $this->ControllerAction->field('module', ['type' => 'hidden']);
        $this->ControllerAction->field('from_date', ['type' => 'hidden']);
        $this->ControllerAction->field('to_date', ['type' => 'hidden']);

        $this->ControllerAction->field('institution_status_id', ['type' => 'hidden']);
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
        $this->ControllerAction->field('institution_type_id', ['type' => 'hidden', 'value' => '']);
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
        $this->ControllerAction->field('position_status', ['type' => 'hidden', 'value' => 'x']);
        $this->ControllerAction->field('email', ['type' => 'hidden']);
        //        $this->ControllerAction->field('fax', ['type' => 'hidden']);
        $this->ControllerAction->field('contact', ['type' => 'hidden']);
        $this->ControllerAction->field('security_group_id', ['type' => 'hidden']);
        $this->ControllerAction->field('institution_gender_id', ['type' => 'hidden', 'value' => 'x']);
        $this->ControllerAction->field('institution_provider_id', ['type' => 'hidden', 'value' => 'x']);
        $this->ControllerAction->field('institution_sector_id', ['type' => 'hidden', 'value' => 'x']);
        $this->ControllerAction->field('name', ['type' => 'hidden', 'value' => 'x']);
        $this->ControllerAction->field('alternative_name', ['type' => 'hidden']);
        $this->ControllerAction->field('name', ['type' => 'hidden']);
        $this->ControllerAction->field('code', ['type' => 'hidden', 'value' => 'x']);
        $this->ControllerAction->field('address', ['type' => 'hidden', 'value' => 'x']);
        $this->ControllerAction->field('postal_code', ['type' => 'hidden']);
        $this->ControllerAction->field('contact_person', ['type' => 'hidden']);
        $this->ControllerAction->field('telephone', ['type' => 'hidden']);
        $this->ControllerAction->field('website', ['type' => 'hidden']);
        $this->ControllerAction->field('date_opened', ['type' => 'hidden', 'value' => '2021-01-01']);
        $this->ControllerAction->field('year_opened', ['type' => 'hidden', 'value' => '2021-01-01']);
        $this->ControllerAction->field('date_closed', ['type' => 'hidden', 'value' => '2021-01-01']);
        $this->ControllerAction->field('year_closed', ['type' => 'hidden', 'value' => '2021-01-01']);
        $this->ControllerAction->field('longitude', ['type' => 'hidden']);
        $this->ControllerAction->field('latitude', ['type' => 'hidden']);
        $this->ControllerAction->field('logo_name', ['type' => 'hidden']);
        $this->ControllerAction->field('area_id', ['type' => 'hidden', 'value' => 'x']);
        $this->ControllerAction->field('area_education_id', ['type' => 'hidden', 'value' => '']);
        $this->ControllerAction->field('area_administrative_id', ['type' => 'hidden', 'value' => 'x']);
        $this->ControllerAction->field('institution_status_id', ['type' => 'hidden', 'value' => 'x']);
        $this->ControllerAction->field('institution_ownership_id', ['type' => 'hidden', 'value' => 'x']);
        $this->ControllerAction->field('area_administrative_id', ['type' => 'hidden']);
        $this->ControllerAction->field('institution_locality_id', ['type' => 'hidden']);
        $this->ControllerAction->field('shift_type', ['type' => 'hidden', 'value' => 'x']);
        $this->ControllerAction->field('classification', ['type' => 'hidden']);
        $this->ControllerAction->field('institution_locality_id', ['type' => 'hidden', 'value' => 'x']);
        $this->ControllerAction->field('logo_content', ['type' => 'hidden']);
        
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
        } elseif ($data[$this->getAlias()]['feature'] == 'Report.StudentAbsences') {
            $options['validate'] = 'studentAbsences';
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
        } elseif ($data[$this->getAlias()]['feature'] == 'Report.StaffLeave') { //POCOR-5762
            $options['validate'] = 'StaffLeave';
        } elseif ($data[$this->getAlias()]['feature'] == 'Report.StudentAbsencesPerDays') { //POCOR-7276
            $options['validate'] = 'StudentAbsencesPerDays';
        }
    }

    public function addAfterAction(EventInterface $event, Entity $entity)
    {
        if ($entity->has('feature')) {
            $feature = $entity->feature;

            $fieldsOrder = ['feature'];
            switch ($feature) {
                /*POCOR-6176 Starts*/
                case 'Report.StaffBehaviours':
                    $fieldsOrder[] = 'area_level_id';
                    $fieldsOrder[] = 'area_education_id';
                    $fieldsOrder[] = 'institution_filter';
                    $fieldsOrder[] = 'format';
                    break;
                case 'Report.Institutions':
                    $fieldsOrder[] = 'area_level_id';
                    $fieldsOrder[] = 'area_education_id';
                    $fieldsOrder[] = 'institution_id';
                    $fieldsOrder[] = 'institution_dropdown';
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
                // case 'Report.StudentAbsences':
                case 'Report.StudentWithdrawalReport':
                case 'Report.InstitutionInfrastructureSummaryReport':
                    $fieldsOrder[] = 'academic_period_id';
                    $fieldsOrder[] = 'area_level_id';
                    $fieldsOrder[] = 'area_education_id';
                    $fieldsOrder[] = 'institution_id';
                    $fieldsOrder[] = 'institution_status_id';
                    $fieldsOrder[] = 'format';
                    break;
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
                    $fieldsOrder[] = 'teaching_filter'; //POCOR-6614
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
                case 'Report.StudentAbsences':
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
                    // $fieldsOrder[] = 'academic_period_id';//POCOR-7786
                    $fieldsOrder[] = 'area_level_id';
                    $fieldsOrder[] = 'area_education_id';
                    $fieldsOrder[] = 'institution_id';
                    // $fieldsOrder[] = 'module';//POCOR-7786
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
                case 'Report.InstitutionAssets':
                    $fieldsOrder[] = 'area_level_id';
                    $fieldsOrder[] = 'area_education_id';
                    $fieldsOrder[] = 'institution_id';
                    $fieldsOrder[] = 'report_start_date';
                    $fieldsOrder[] = 'report_end_date';
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
                case 'Report.StudentBehaviours': //POCOR-7517
                    $fieldsOrder[] = 'academic_period_id';
                    $fieldsOrder[] = 'area_level_id';
                    $fieldsOrder[] = 'area_education_id';
                    $fieldsOrder[] = 'institution_id';
                    $fieldsOrder[] = 'format';
                    break;
                case 'Report.InfrastructureElectricities': //POCOR-7517
                    $fieldsOrder[] = 'academic_period_id';
                    $fieldsOrder[] = 'area_level_id';
                    $fieldsOrder[] = 'area_education_id';
                    $fieldsOrder[] = 'institution_id';
                    $fieldsOrder[] = 'format';
                    break;
                default:
                    break;
            }

            $this->ControllerAction->setFieldOrder($fieldsOrder);
        } else {  //POCOR-6637::Start
            $fieldsOrder = ['feature'];
            $fieldsOrder[] = 'area_level_id';
            $fieldsOrder[] = 'area_education_id';
            $fieldsOrder[] = 'institution_filter';
            $fieldsOrder[] = 'format';
            $this->ControllerAction->setFieldOrder($fieldsOrder);
        } //POCOR-6637::END
        if($feature = 'Report.Institution'){ //POCOR-8417
            $this->ControllerAction->field('institution_dropdown', [
                'type' => 'element',
                'element' => 'institutiondropdown',   
            ]);
        }
       
    }

    public function onExcelBeforeStart(EventInterface $event, ArrayObject $settings, ArrayObject $sheets)
    {

        $requestData = json_decode($settings['process']['params']);
        $feature = $requestData->feature;
        $filter = $requestData->institution_filter;
        if ($feature == 'Report.Institutions' && $filter != self::NO_FILTER) {
            $sheets[] = [
                'name' => $this->getAlias(), //POCOR-8794
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
            //POCOR-9302 start
            if ($value['field'] == 'date_opened') {
                $newFields[$key] = [
                    'key' => 'Institutions.date_opened',
                    'field' => 'date_opened',
                    'type' => 'string',
                    'label' => __('Date Opened')
                ];
            }
            //POCOR-9302 end
            if ($value['field'] == 'date_closed') {
                $newFields[$key] = [
                    'key' => 'Institutions.date_closed',
                    'field' => 'date_closed',
                    'type' => 'string',
                    'label' => __('Date Closed')
                ];
            }
        }

        $fields->exchangeArray($newFields);

        /*if ($feature == 'Report.Institutions' && $filter != self::NO_FILTER) {
            // Stop the customfieldlist behavior onExcelUpdateFields function
            $includedFields = ['name', 'alternative_name', 'code', 'area_code', 'area_id', 'area_administrative_code', 'area_administrative_id'];
            foreach ($newFields as $key => $value) {
                if (!in_array($value['field'], $includedFields)) {
                    unset($newFields[$key]);
                }
            }
            $filter = $requestData->institution_filter;
            if ($filter == 2) {
                $newFields[] = [
                    'key' => 'institutions.institution_status_id',
                    'field' => 'institution_status',
                    'type' => 'integer',
                    'label' => __('Institutions Status')
                ];
            }
            $fields->exchangeArray($newFields);
            $event->stopPropagation();
        }*/
    }

    public function onExcelGetShiftType(EventInterface $event, Entity $entity)
    {
        if (isset($this->shiftTypes[$entity->shift_type])) {
            return __($this->shiftTypes[$entity->shift_type]);
        } else {
            return '';
        }
    }
    //POCOR-9302 start
    public function onExcelGetDateOpened(EventInterface $event, Entity $entity)
    {
        return isset($entity->date_opened) ? $entity->date_opened->format('Y-m-d') : '';
    }

    public function onExcelGetDateClosed(EventInterface $event, Entity $entity)
    {
        return isset($entity->date_closed) ? $entity->date_closed->format('Y-m-d') : '';
    }
    //POCOR-9302 endp
    public function onExcelGetClassification(EventInterface $event, Entity $entity)
    {
        return __($this->classificationOptions[$entity->classification]);
    }

    //POCOR-9380 start
    public function onUpdateFieldFeature(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $options = $this->controller->getFeatureOptions($this->getAlias());
            $attr['options'] = $this->controller->getFeatureOptions($this->getAlias());
            $attr['onChangeReload'] = true;
            if (!(isset($this->request->getData($this->getAlias())['feature']))) {
                $option = $attr['options'];
                reset($option);
                $defaultFeatureValue = key($options);
                $this->request = $this->request->withData($this->getAlias() . '.feature', $defaultFeatureValue);
            }
            return $attr;
        }
    }
    //POCOR-9380 end

    public function onUpdateFieldInstitutionFilter(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];
            if ($feature == 'Report.StaffBehaviours') {
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

    public function onUpdateFieldPositionFilter(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];
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
    public function onUpdateFieldTeachingFilter(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];
            if (in_array($feature, ['Report.InstitutionPositions'])) {
                $options = [
                    InstitutionPositions::ALL_STAFF => __('All Staff'), //POCOR-6850
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

    public function onUpdateFieldLicense(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];
            if (in_array($feature, ['Report.InstitutionStaff'])) {
                // need to find all types
                $typeOptions = [];
                $typeOptions[0] = __('All Licenses');

                $Types = self::getDynamicTableInstance('FieldOption.LicenseTypes');
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

    public function onUpdateFieldModule(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];
            if (in_array($feature, ['Report.InstitutionCases'])) {
                $WorkflowRules = self::getDynamicTableInstance('Workflow.WorkflowRules');
                $featureOptions = $WorkflowRules->getFeatureOptions();
                $attr['type'] = 'hidden'; //POCOR-7786
                // $attr['type'] = 'select';
                // $attr['options'] = $featureOptions;
                // $attr['select'] = false;
                // return $attr;
            } else {
                $attr['value'] = self::NO_FILTER;
            }
        }
    }

    public function onUpdateFieldType(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];
            if (in_array($feature, ['Report.InstitutionStaff'])) {
                // need to find all types
                $typeOptions = [];
                $typeOptions[0] = __('All Types');

                $Types = self::getDynamicTableInstance('Staff.StaffTypes');
                $typeData = $Types->getList();
                foreach ($typeData as $key => $value) {
                    $typeOptions[$key] = $value;
                }

                $attr['type'] = 'select';
                $attr['select'] = false;
                $attr['options'] = $typeOptions;
                $attr['onChangeReload'] = true;
                return $attr;
            } else {
                $attr['value'] = self::NO_FILTER;
            }
        }
    }

    public function onUpdateFieldStatus(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];
            if (in_array(
                $feature,
                [
                    'Report.InstitutionStudents',
                    'Report.InstitutionStudentEnrollments',
                    'Report.InstitutionStaff',
                    'Report.InstitutionPositions'  // POCOR-6869
                ]
            )) {


                // need to find all status
                $statusOptions = [];

                switch ($feature) {
                    case 'Report.InstitutionStudents':
                        //POCOR-8416[START]
                        $Statuses = self::getDynamicTableInstance('Student.StudentStatuses');
                        $statusData = $Statuses->find()->select(['id', 'name'])->toArray();
                        foreach ($statusData as $key => $value) {
                            $statusOptions[$value->id] = $value->name;
                        }
                        $statusOptions = array(-1 => __('All Status')) + $statusOptions;
                        break;
                    //POCOR-8416[END]
                    case 'Report.InstitutionStudentEnrollments':
                        $Statuses = self::getDynamicTableInstance('Student.StudentStatuses');
                        $statusData = $Statuses->find()->select(['id', 'name'])->toArray();
                        foreach ($statusData as $key => $value) {
                            $statusOptions[$value->id] = $value->name;
                        }
                        break;

                    case 'Report.InstitutionStaff':
                        $Statuses = self::getDynamicTableInstance('Staff.StaffStatuses');
                        $statusData = $Statuses->getList();
                        foreach ($statusData as $key => $value) {
                            $statusOptions[$key] = $value;
                        }
                        $statusOptions = [-1 => 'All Statuses'] + $statusOptions; //POCOR-9041

                        break;


                    case 'Report.InstitutionPositionsSummaries':
                        $Statuses = self::getDynamicTableInstance('Staff.StaffStatuses');
                        $statusData = $Statuses->getList();
                        foreach ($statusData as $key => $value) {
                            $statusOptions[$key] = $value;
                        }
                        break;

                    //Start POCOR-6869
                    case 'Report.InstitutionPositions':
                        $Workflows = self::getDynamicTableInstance('Workflow.Workflows');
                        $Statuses = self::getDynamicTableInstance('Workflow.WorkflowSteps');
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
                $attr['onChangeReload'] = true;
                $attr['attr']['required'] = true;
                return $attr;
            } else {
                $attr['value'] = self::NO_FILTER;
            }
        }
    }

    public function onUpdateFieldAcademicPeriodId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {

        $alias = $this->getAlias();
        $data = $request->getData($alias);
        if (isset($data['feature'])) {
            $feature = $data['feature'];

            if ((in_array(
                $feature,
                [
                    'Report.InstitutionStudents',
                    'Report.InstitutionSubjectsClasses',
                    'Report.StudentAbsences',
                    'Report.InstitutionCases',
                    'Report.ClassAttendanceNotMarkedRecords',
                    'Report.InstitutionSubjects',
                    'Report.StudentAttendanceSummary',
                    'Report.StudentAbsences',
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
                    'Report.InstitutionInfrastructureSummaryReport',
                    'Report.StudentBehaviours'


                ]
            )) || (((in_array($feature, ['Report.Institutions']) || in_array($feature, ['Report.StaffBehaviours'])) && !empty($data['institution_filter']) && $data['institution_filter'] == self::NO_STUDENT))) {

                $AcademicPeriodTable = self::getDynamicTableInstance('AcademicPeriod.AcademicPeriods');
                $academicPeriodOptions = $AcademicPeriodTable->getYearList();
                $currentPeriod = $AcademicPeriodTable->getCurrent();
                $attr['options'] = $academicPeriodOptions;
                if ($feature == 'Report.InstitutionCases') { //POCOR-7786
                    $attr['type'] = 'hidden';
                } else {
                    $attr['type'] = 'select';
                }
                $attr['select'] = false;
                $attr['onChangeReload'] = true;
                if (empty($data['academic_period_id'])) {
                    $request = $request->withData('academic_period_id', $currentPeriod);
                    $request = $request->withData('institution_id', -1);
                    $request = $request->withData('education_level_id', -1);
                }
                return $attr;
            }
        }
    }

    public function onUpdateFieldAreaLevelId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $alias = $this->getAlias();
        $data = $this->request->getData($alias);//POCOR-9380

        if (isset($data['feature'])) {
            $feature = $data['feature'];

            if ((in_array($feature, [
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
                'Report.StudentAbsences',
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
                'Report.InstitutionAssets',
                'Report.SpecialNeedsFacilities',
                'Report.InstitutionCommittees',
                'Report.ClassAttendanceMarkedSummaryReport',
                'Report.InfrastructureNeeds',
                'Report.Income',
                'Report.Expenditure',
                'Report.InstitutionPositionsSummaries',
                'Report.StudentAbsencesPerDays',
                'Report.StaffBehaviours', //POCOR-7276
                'Report.InstitutionInfrastructureSummaryReport',
                'Report.StudentBehaviours', //POCOR-7517
            ]))) {
                $Areas = self::getDynamicTableInstance('Area.AreaLevels');
                $entity = $attr['entity'];

                if ($action == 'add') {
                    $areaOptions = $Areas
                        ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
                        ->order([$Areas->aliasField('level')])->toArray();

                    $attr['type'] = 'chosenSelect';
                    $attr['attr']['multiple'] = false;
                    $attr['select'] = true;
                    if ($feature == "Report.InstitutionSummaryReport") {
                        $attr['options'] = ['' => '-- ' . __('Select') . ' --'] + $areaOptions;
                    } else {
                        $attr['options'] = ['' => '-- ' . __('Select') . ' --', '-1' => __('All Areas Level')] + $areaOptions;
                    }

                    $attr['onChangeReload'] = true;
                } else {
                    $attr['type'] = 'hidden';
                }
            }
        }
        return $attr;
    }

    public function onUpdateFieldAreaEducationId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $alias = $this->getAlias();
        $data = $this->request->getData($alias);//POCOR-9380
        if (isset($data['feature'])) {
            $feature = $data['feature'];
            $areaLevelId = $data['area_level_id']; //POCOR-6333
            if ((in_array(
                $feature,
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
                    'Report.InstitutionAssets',
                    'Report.SpecialNeedsFacilities',
                    'Report.InstitutionCommittees',
                    'Report.ClassAttendanceMarkedSummaryReport',
                    'Report.InfrastructureNeeds',
                    'Report.Income',
                    'Report.Expenditure',
                    'Report.InstitutionPositionsSummaries',
                    'Report.StudentAbsencesPerDays',
                    'Report.StaffBehaviours', //POCOR-7276
                    'Report.InstitutionInfrastructureSummaryReport',
                    'Report.StudentBehaviours' //POCOR-7517
                ]
            ))) {
                $Areas = self::getDynamicTableInstance('Area.Areas');
                $entity = $attr['entity'];

                if ($action == 'add') {
                    $where = [];
                    if ($areaLevelId != -1 && !empty($areaLevelId)) {//POCOR-9380
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
                        $attr['options'] = ['' => '-- ' . __('Select') . ' --', '-1' => __('All Areas')] + $areaOptions;
                    } else {
                        $attr['options'] = ['' => '-- ' . __('Select') . ' --'] + $areaOptions;
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

    public function onUpdateFieldEducationProgrammeId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $alias = $this->getAlias();
        $data = $request->getData($alias);
        $feature = $data['feature'];
        if (isset($feature)) {
            if (in_array(
                $feature,
                [
                    'Report.InstitutionStudents',
                    'Report.InstitutionSubjects'
                ]
            )) {
                $AcademicPeriodTable = self::getDynamicTableInstance('AcademicPeriod.AcademicPeriods');
                $currentAcademicPeriodID = $AcademicPeriodTable->getCurrent();

                $institutionId = $data['institution_id'] > 0 ? $data['institution_id'] : -1;
                $educationLevelId = $data['education_level_id'] > 0 ? $data['education_level_id'] : -1;
                $academicPeriodId = $data['academic_period_id'] > 0 ? $data['academic_period_id'] : $currentAcademicPeriodID;
                $EducationProgrammes = self::getDynamicTableInstance('Education.EducationProgrammes');
                /*POCOR-6337 starts*/
                $EducationGrades = self::getDynamicTableInstance('Education.EducationGrades');
                $InstitutionGrades = self::getDynamicTableInstance('Institution.InstitutionGrades');
                $condition = [];
                if ($feature == 'Report.InstitutionSubjects') {
                    if ($institutionId > 0) {
                        $condition[$InstitutionGrades->aliasField('institution_id')] = $institutionId;
                    }
                }
                if ($feature == 'Report.InstitutionStudents') {
                    if ($educationLevelId > 0) {
                        $condition['EducationCycles.education_level_id'] = $educationLevelId;
                    }
                }
                /*POCOR-6337 ends*/
                $programmeOptionsQuery = $EducationProgrammes
                    ->find('list', ['keyField' => 'id', 'valueField' => 'cycle_programme_name'])
                    ->find('visible')
                    ->contain(['EducationCycles.EducationLevels.EducationSystems'])
                    /*POCOR-6337 starts*/
                    ->leftJoin([$EducationGrades->getAlias() => $EducationGrades->getTable()], [
                        $EducationGrades->aliasField('education_programme_id') . ' = ' . $EducationProgrammes->aliasField('id')
                    ])
                    ->leftJoin([$InstitutionGrades->getAlias() => $InstitutionGrades->getTable()], [
                        $InstitutionGrades->aliasField('education_grade_id') . ' = ' . $EducationGrades->aliasField('id')
                    ])
                    /*POCOR-6337 ends*/
                    ->where([
                        'EducationSystems.academic_period_id = ' . $academicPeriodId,
                        $condition //POCOR-6337
                    ])
                    ->order([
                        'EducationCycles.order' => 'ASC',
                        $EducationProgrammes->aliasField('order') => 'ASC'
                    ]);

                $programmeOptions = $programmeOptionsQuery->toArray();
                $attr['type'] = 'select';
                $attr['select'] = false;
                /*POCOR-6337 starts*/
                if (!empty($programmeOptions) && count($programmeOptions) > 1) {
                    $attr['options'] = ['' => '-- ' . __('Select') . ' --', 0 => __('All Programmes')] + $programmeOptions;
                    if (!isset($data['education_programme_id'])) {
                        $attr['attr']['value'] = 0;
                    }
                } else {
                    $attr['options'] = ['' => '-- ' . __('Select') . ' --'] + $programmeOptions;
                    $attr['default'] = 1;
                }
                /*POCOR-6337 starts*/
                $attr['attr']['required'] = true; //POCOR-9345
                $attr['onChangeReload'] = true;
            } else {
                $attr['value'] = self::NO_FILTER;
            }
            return $attr;
        }
    }

    public function onUpdateFieldEducationGradeId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {

        if (isset($this->request->getData($this->getAlias())['academic_period_id'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];
            $academicPeriodId = $this->request->getData($this->getAlias())['academic_period_id'];
            $institutionId = $this->request->getData($this->getAlias())['institution_id'];
            if (in_array($feature, [
                'Report.ClassAttendanceNotMarkedRecords',
                'Report.SubjectsBookLists',
                'Report.InstitutionSubjectsClasses',
                'Report.StudentAttendanceSummary',
                'Report.StudentAbsences',
                'Report.ClassAttendanceMarkedSummaryReport',
                'Report.InstitutionClasses'
            ])) {

                $InstitutionGrades = self::getDynamicTableInstance('Institution.InstitutionGrades');
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
                if (in_array($feature, ['Report.StudentAttendanceSummary', 'Report.ClassAttendanceNotMarkedRecords', 'Report.ClassAttendanceMarkedSummaryReport', 'Report.InstitutionClasses', 'Report.StudentAbsences'])) {
                    $attr['options'] = ['-1' => __('All Grades')] + $gradeOptions;
                } else {
                    $attr['options'] = $gradeOptions;
                }
                $attr['onChangeReload'] = true;
            } elseif (in_array(
                $feature,
                [
                    'Report.StudentAttendanceSummary',
                    'Report.StudentAbsences',
                    'Report.InstitutionSubjectsClasses'
                ]
            )) {
                $gradeList = [];
                if (array_key_exists('institution_id', $this->request->getData($this->getAlias())) && !empty($this->request->getData($this->getAlias())['institution_id']) && array_key_exists('academic_period_id', $this->request->getData($this->getAlias())) && !empty($this->request->getData($this->getAlias())['academic_period_id'])) {
                    $institutionId = $this->request->getData($this->getAlias())['institution_id'];
                    $academicPeriodId = $this->request->getData($this->getAlias())['academic_period_id'];

                    $InstitutionGradesTable = self::getDynamicTableInstance('Institution.InstitutionGrades');
                    $gradeList = $InstitutionGradesTable->getGradeOptions($institutionId, $academicPeriodId);
                }

                if (empty($gradeList)) {
                    $gradeOptions = ['' => $this->getMessage('general.select.noOptions')];
                } else {
                    if (!in_array($feature, ['Report.StudentAttendanceSummary', 'Report.StudentAbsences'])) {
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
    //POCOR-8006
    public function onUpdateFieldInstitutionStatusId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];
            if (in_array($feature, ['Report.InstitutionInfrastructureSummaryReport'])) {
                $TypesTab = self::getDynamicTableInstance('Institution.InstitutionStatuses');
                $typeOptionn = $TypesTab
                    ->find('list')
                    ->toArray();
                $attr['type'] = 'select';
                $attr['onChangeReload'] = true;

                if (in_array(
                    $feature,
                    [
                        'Report.InstitutionInfrastructureSummaryReport'
                    ]
                )) {
                    $attr['options'] =  $typeOptionn;
                } else {
                    $attr['options'] = $typeOptionn;
                }
                $attr['attr']['required'] = true;
            }
            return $attr;
        }
    }
    //POCOR-8006
    public function onUpdateFieldInstitutionTypeId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];

            if (in_array(
                $feature,
                [
                    'Report.InstitutionSubjects',
                    'Report.StudentAttendanceSummary',
                    'Report.StudentAbsences',
                    'Report.Guardians',
                    'Report.SubjectsBookLists',
                    'Report.InstitutionSubjects'
                ]
            )) {


                $TypesTable = self::getDynamicTableInstance('Institution.Types');
                $typeOptions = $TypesTable
                    ->find('list')
                    ->find('visible')
                    ->find('order')
                    ->toArray();

                $attr['type'] = 'select';
                $attr['onChangeReload'] = true;

                if (in_array(
                    $feature,
                    [
                        'Report.StudentAbsences',
                        'Report.StudentAttendanceSummary',
                        'Report.SpecialNeedsFacilities',
                        'Report.WashReports',
                        'Report.InstitutionSubjects',
                        'Report.Guardians',
                        'Report.InstitutionInfrastructures',
                        'Report.InstitutionAssets',
                    ]
                )) {
                    $attr['options'] = ['0' => __('All Types')] + $typeOptions;
                } else {
                    $attr['options'] = $typeOptions;
                }

                $attr['attr']['required'] = true;
            }
            return $attr;
        }
    }

    public function onUpdateFieldInfrastructureType(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];
            if (in_array(
                $feature,
                [
                    //'Report.InstitutionInfrastructures'
                ]
            )) {

                $TypesTable = self::getDynamicTableInstance('building_types');
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

    // Start POCOR-7479
    public function getAllAreaID($areaId)
    {

        $areaTable = self::getDynamicTableInstance('Area.Areas');
        $areaList = $areaTable
            ->find('list')
            ->select('id')
            ->where([
                $areaTable->aliasField('parent_id') => $areaId,
            ])
            ->toArray();

        $ids = [];
        if (!empty($areaList)) {
            foreach ($areaList as $key => $val) {
                $ids[$key] = $key;
            }
        }
        return $ids;
    }

    // END POCOR-7479


    public function onUpdateFieldInstitutionId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $alias = $this->getAlias();
        $data = $this->request->getData($alias);
        $areaId = $data['area_education_id'];
        $institutionTypeId = $data['institution_type_id'] ?? -1;
        $InstitutionsTable = self::getDynamicTableInstance('Institution.Institutions');
        if (isset($data['feature'])) {
            $feature = $data['feature'];

            $reportModels = [
                'Report.Institutions',
                'Report.InstitutionSubjects',
                'Report.InstitutionSubjectsClasses',
                'Report.StudentAttendanceSummary',
                'Report.StudentAbsences',
                'Report.StaffAttendances',
                'Report.BodyMasses',
                'Report.WashReports',
                'Report.Guardians',
              //  'Report.InstitutionInfrastructures',
                'Report.InstitutionAssets',
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
                'Report.StudentAbsencesPerDays', //POCOR-7276
                'Report.InstitutionInfrastructureSummaryReport',
                'Report.StudentBehaviours' //POCOR-7517
            ];


            if (in_array($feature, $reportModels)) {
                $institutionList = [];
                if (array_key_exists('institution_type_id', $data) && !empty($data['institution_type_id'])) {
                    $institutionTypeId = $data['institution_type_id'];
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
                        // Start POCOR-7479
                        $area_level_id = $data['area_level_id'];
                        if (in_array($area_level_id, [1, 2, 3])) {
                            $areasId = $this->getAllAreaID($areaId);
                            if (empty($areasId)) {
                                $areasId = [$areaId];
                            }
                        } else {
                            $areasId = [$areaId];
                        }
                        // END POCOR-7479
                        //                        die(print_r($areasId, true));

                        $institutionQuery = $InstitutionsTable
                            ->find('list', [
                                'keyField' => 'id',
                                'valueField' => 'code_name'
                            ])
                            ->where([
                                $InstitutionsTable->aliasField('institution_type_id') => $institutionTypeId,
                                $InstitutionsTable->aliasField('area_id IN') => $areasId    //POCOR-7479
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
                } elseif (!$institutionTypeId && array_key_exists('area_education_id', $data) && !empty($data['area_education_id']) && $areaId != -1) {
                    /**POCOR-6896 starts - updated condition to fetch Institutions query on that bases of selected area level and area education*/
                    $areaIds = [];
                    $lft = $this->Areas->get($areaId)->lft;
                    $rgt = $this->Areas->get($areaId)->rght;
                    $this->Areas =  self::getDynamicTableInstance('Area.Areas');
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
                    $InstitutionsTable = self::getDynamicTableInstance('Institution.Institutions');
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
                    if (in_array($feature, [
                        'Report.BodyMasses',
                        'Report.InstitutionSubjects',
                        'Report.InstitutionClasses',
                        'Report.StudentWithdrawalReport',
                        'Report.StudentAbsences',
                        'Report.InstitutionSubjectsClasses',
                        'Report.SpecialNeedsFacilities',
                        'Report.Income',
                        'Report.Expenditure',
                        'Report.WashReports',
                     //   'Report.InstitutionInfrastructures',
                        'Report.InstitutionAssets',
                        'Report.StudentAttendanceSummary',
                        'Report.StudentAbsences',
                        'Report.InstitutionInfrastructureSummaryReport'
                    ])) {
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
                    if(!$superAdmin){
                        $institutionOptions = ['' => '-- ' . __('Select') . ' --'] + $institutionList;
                    }
                    if(in_array($feature, ['Report.Institutions'])) { //POCOR-8417
                        $attr['attr']['multiple'] = true;
                    } else {
                        $attr['attr']['multiple'] = false;
                    }
                    $attr['type'] = 'chosenSelect';
                    $attr['onChangeReload'] = true;
                    $attr['options'] = $institutionOptions;
                    $attr['attr']['required'] = true;
                }
            }
            return $attr;
        }
    }

    public function onUpdateFieldReportStartDate(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        //POCOR-7665 refactured code to minimize errors
        $requestData = $this->request->getData($this->getAlias());
        $feature = isset($requestData['feature']) ? $requestData['feature'] : null;
        $selectedAcademicPeriodId = isset($requestData['academic_period_id']) ? $requestData['academic_period_id'] : null;
        if ($feature) {
            $attr['value'] = self::NO_FILTER;
            if ($selectedAcademicPeriodId) {
                $AcademicPeriods = self::getDynamicTableInstance('AcademicPeriod.AcademicPeriods');
                $selectedPeriod = $AcademicPeriods->get($selectedAcademicPeriodId);
                if (in_array($feature, [
                    'Report.ClassAttendanceNotMarkedRecords',
                    'Report.InstitutionCases',
                    //'Report.StudentAttendanceSummary',
                    //'Report.InstitutionAssets',
                    'Report.ClassAttendanceMarkedSummaryReport',
                    'Report.StaffAttendances'
                ])) {
                    $attr['type'] = 'date';
                    $attr['date_options']['startDate'] = ($selectedPeriod->start_date)->format('d-m-Y');
                    $attr['date_options']['endDate'] = ($selectedPeriod->end_date)->format('d-m-Y');
                    $attr['value'] = $selectedPeriod->start_date;
                }
                if (in_array($feature, [
                    'Report.StudentAttendanceSummary',
                    'Report.StudentAbsences'
                ])) {
                    $attr['type'] = 'date';
                    $attr['date_options']['startDate'] = ($selectedPeriod->start_date)->format('d-m-Y');
                    $attr['date_options']['endDate'] = ($selectedPeriod->end_date)->format('d-m-Y');
                    $attr['attr']['default'] = $selectedPeriod->start_date;
                    $attr['onChangeReload'] = true;
                    if ($attr['value'] > 0) {
                        $attr['value'] = $requestData['report_start_date'];
                    } else {
                        if ($requestData['report_start_date'] != 0) {
                            $attr['value'] = $requestData['report_start_date'];
                        } else {
                            $attr['value'] = $selectedPeriod->start_date;
                        }
                    }
                }
                //                if (in_array($feature, [
                //                    'Report.InstitutionAssets'
                //                ])
                //                ) {
                //                    $presentPreviousAcademicYearId = null;
                //                    if (isset($requestData['report_start_date'])) {
                //                        $report_start_date = new \DateTime($requestData['report_start_date']);
                //                        $presentPreviousAcademicYearId = $AcademicPeriods->getAcademicPeriodIdByDate($report_start_date);
                //                    }
                //                    $selectedPeriodStart = $selectedPeriod->start_date;
                //                    $previousPeriodDay = $selectedPeriodStart->sub(new \DateInterval('P2M'));
                //                    $previousPeriodId = $AcademicPeriods->getAcademicPeriodIdByDate($previousPeriodDay);
                //                    $previousPeriod = $AcademicPeriods->get($previousPeriodId);
                //
                //                    $attr['type'] = 'date';
                //                    $attr['date_options']['startDate'] = ($previousPeriod->start_date)->format('d-m-Y');
                //                    $attr['date_options']['endDate'] = ($previousPeriod->end_date)->format('d-m-Y');
                //                    if ($presentPreviousAcademicYearId != $previousPeriodId) {
                //                        $attr['attr']['default'] = $previousPeriod->start_date;
                //                        $attr['value'] = $previousPeriod->start_date;
                //                    } else {
                //                        $attr['value'] = $requestData['report_start_date'];
                //                    }
                //                    $attr['onChangeReload'] = true;
                //                }
            }
            if (in_array($feature, ['Report.StaffLeave'])) {
                $AcademicPeriods = self::getDynamicTableInstance('AcademicPeriod.AcademicPeriods');
                $selectedAcademicPeriodId = $AcademicPeriods->getCurrent();
                $selectedPeriod = $AcademicPeriods->get($selectedAcademicPeriodId);
                $attr['type'] = 'date';
                $attr['date_options']['startDate'] = ($selectedPeriod->start_date)->format('d-m-Y');
                $attr['date_options']['endDate'] = ($selectedPeriod->end_date)->format('d-m-Y');
                $attr['value'] = $selectedPeriod->start_date;
            }
            if (in_array($feature, [
                'Report.InstitutionAssets'
            ])) {
                $attr['type'] = 'date';
                if ($requestData['report_start_date']) {
                    $attr['value'] = $requestData['report_start_date'];
                } else {
                    $currentDate = new \DateTime();
                    // Set the date to the first day of the year
                    $firstDayOfTheYear = $currentDate->setDate($currentDate->format('Y'), 1, 1);
                    // Format the result if needed
                    $attr['value'] = $firstDayOfTheYear;
                }
                $attr['onChangeReload'] = false;
            }
            return $attr;
        }
    }


    //POCOR-7665 added to change caption
    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        $requestData = $this->request->getData($this->getAlias());
        $feature = isset($requestData['feature']) ? $requestData['feature'] : null;
        if ($field == 'report_start_date') {
            if ($feature === 'Report.InstitutionAssets') {
                return __('Year Start');
            }
        }
        if ($field == 'report_end_date') {
            if ($feature === 'Report.InstitutionAssets') {
                return __('Year End');
            }
        }
        switch ($field) {
            case 'feature':
                return __('Feature');
            case 'format':
                return __('Format');
            case 'academic_period_id':
                return __('Academic Period');
            case 'area_level_id':
                return __('Area Level');
            case 'area_education_id':
                return __('Area Education');
            case 'institution_id':
                return __('Institution');
            case 'report_start_date':
                return __('Start Date');
            case 'report_end_date':
                return __('End Date');
            case 'status':
                return __('Status');
            case 'health_report_type':
                return __('Health Report Type');
            case 'system_usage':
                return __('System Usage');
            case 'education_grade_id':
                return __('Education Grade');
            case 'education_subject_id':
                return __('Education Subject');
            case 'institution_type_id':
                return __('Institution Type');
            case 'institution_filter':
                return __('Institution Filter');
            case 'position_filter':
                return __('Position Filter');
            case 'teaching_filter':
                return __('Teaching Filter');
            case 'education_programme_id':
                return __('Education Programme');
            case 'type':
                return __('Type');
            case 'position_status':
                return __('Status');
            case 'position':
                return __('Position');
            case 'leave_type':
                return __('Leave Type');
            case 'workflow_status':
                return __('Workflow Status');
            case 'infrastructure_level':
                return __('Infrastructure Level');

            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public
    function onUpdateFieldReportEndDate(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        //POCOR-7665 refactured code to minimize errors
        $requestData = $this->request->getData($this->getAlias());
        $feature = isset($requestData['feature']) ? $requestData['feature'] : null;
        $selectedAcademicPeriodId = isset($requestData['academic_period_id']) ? $requestData['academic_period_id'] : null;
        if ($feature) {
            $attr['value'] = self::NO_FILTER;
            if ($selectedAcademicPeriodId) {
                $AcademicPeriods = self::getDynamicTableInstance('AcademicPeriod.AcademicPeriods');
                $selectedPeriod = $AcademicPeriods->get($selectedAcademicPeriodId);
                if (in_array($feature, [
                    'Report.ClassAttendanceNotMarkedRecords',
                    'Report.InstitutionCases',
                    //'Report.StudentAttendanceSummary',
                    //'Report.InstitutionAssets',
                    'Report.ClassAttendanceMarkedSummaryReport',
                    'Report.StaffAttendances'
                ])) {
                    $attr['type'] = 'date';
                    $attr['date_options']['startDate'] = ($selectedPeriod->start_date)->format('d-m-Y');
                    $attr['date_options']['endDate'] = ($selectedPeriod->end_date)->format('d-m-Y');
                    $attr['value'] = $selectedPeriod->end_date;
                }
                if (in_array($feature, [
                    'Report.StudentAttendanceSummary',
                    'Report.StudentAbsences'
                ])) {
                    $attr['type'] = 'date';
                    if ($requestData['report_start_date'] != 0) {
                        $attr['date_options']['startDate'] = $requestData['report_start_date'];
                    } else {
                        $attr['date_options']['startDate'] = ($selectedPeriod->start_date)->format('d-m-Y');
                    }
                    $date = $attr['date_options']['startDate'];
                    $reportEndDate = date('d-m-Y', strtotime('+30 days', strtotime($date)));
                    $attr['date_options']['endDate'] = $reportEndDate;
                    if ($selectedAcademicPeriodId == $AcademicPeriods->getCurrent()) {
                        $attr['value'] = $reportEndDate;
                    } else {
                        $attr['value'] = FrozenTime::now();  // POCOR-8902 change Time to FrozenTime due to deprecation error
                    }
                    $attr['value'] = $reportEndDate;
                }
            }


            if (in_array($feature, ['Report.StaffLeave'])) {
                $AcademicPeriods = self::getDynamicTableInstance('AcademicPeriod.AcademicPeriods');
                $academicPeriodId = $AcademicPeriods->getCurrent();
                $selectedPeriod = $AcademicPeriods->get($academicPeriodId);

                $attr['type'] = 'date';
                if ($requestData['report_start_date'] != 0) {
                    $attr['date_options']['startDate'] = $requestData['report_start_date'];
                } else {
                    $attr['date_options']['startDate'] = ($selectedPeriod->start_date)->format('d-m-Y');
                }
                $attr['date_options']['endDate'] = ($selectedPeriod->end_date)->format('d-m-Y');
                if ($academicPeriodId != $AcademicPeriods->getCurrent()) {
                    $attr['value'] = $selectedPeriod->end_date;
                } else {
                    $attr['value'] = FrozenTime::now();  // POCOR-8902 change Time to FrozenTime due to deprecation error
                }
                //POCOR-5907[START]
                $attr['value'] = $selectedPeriod->end_date;
                //POCOR-5907[END]
            }
            if (in_array($feature, [
                'Report.InstitutionAssets'
            ])) {
                $attr['type'] = 'date';
                if ($requestData['report_end_date']) {
                    $attr['value'] = $requestData['report_end_date'];
                } else {
                    $currentDate = new \DateTime();
                    // Set the date to the first day of the year
                    $lastDayOfTheYear = $currentDate->setDate($currentDate->format('Y'), 12, 31);
                    // Format the result if needed
                    $attr['value'] = $lastDayOfTheYear;
                }
                $attr['onChangeReload'] = false;
            }
            return $attr;
        }
    }

    //POCOR-7276
    public function onUpdateFieldAttendanceType(EventInterface $event, array $attr, $action, ServerRequest $request)
    {

        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];
            if (
                in_array($feature, [
                    'Report.StudentAbsencesPerDays'
                ]) && isset($this->request->getData($this->getAlias())['academic_period_id'])
            ) {

                /*$StudentAttendanceTypes = self::getDynamicTableInstance('Attendance.StudentAttendanceTypes');
                $attendanceOptions = $StudentAttendanceTypes
                ->find('list')
                ->toArray();*/
                $StudentAttendanceTypes = array(1 => 'Period');
                $attr['type'] = 'select';

                $attr['attr']['options'] = $StudentAttendanceTypes;
                $attr['onChangeReload'] = true;
            }
            return $attr;
        }
    }

    public
    function onUpdateFieldSubjects(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];
            if (
                in_array($feature, [
                    'Report.ClassAttendanceMarkedSummaryReport'
                ]) && isset($this->request->getData($this->getAlias())['academic_period_id'])
            ) {
                $academic_period_id = $this->request->getData($this->getAlias())['academic_period_id'];
                $education_grade_id = $this->request->getData($this->getAlias())['education_grade_id'];
                $attendance_type = $this->request->getData($this->getAlias())['attendance_type'];
                $StudentAttendanceTypes = self::getDynamicTableInstance('Attendance.StudentAttendanceTypes');
                if (!empty($attendance_type)) {

                    $attendanceTypeData = $StudentAttendanceTypes
                        ->find()
                        ->where([
                            $StudentAttendanceTypes->aliasField('id') => $attendance_type
                        ])
                        ->toArray();
                    $attendanceTypeCode = $attendanceTypeData[0]->code;
                }

                $InstitutionSubjects = self::getDynamicTableInstance('Institution.InstitutionSubjects');
                $gradeCondition = [];
                if ($attendanceTypeCode == 'SUBJECT') {

                    if ($education_grade_id != -1) {
                        $gradeCondition = [
                            $InstitutionSubjects->aliasField('academic_period_id') => $academic_period_id,
                            $InstitutionSubjects->aliasField('education_grade_id') => $education_grade_id
                        ];
                    } else {
                        $gradeCondition = [$InstitutionSubjects->aliasField('academic_period_id') => $academic_period_id];
                    }

                    $institutionSubjects = $InstitutionSubjects
                        ->find(
                            'list',
                            [
                                'keyField' => 'id',
                                'valueField' => 'name'
                            ]
                        )
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

    public
    function onUpdateFieldPeriods(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];
            if (
                in_array($feature, [
                    'Report.ClassAttendanceMarkedSummaryReport'
                ]) && isset($this->request->getData($this->getAlias())['academic_period_id'])
            ) {

                $academic_period_id = $this->request->getData($this->getAlias())['academic_period_id'];
                $education_grade_id = $this->request->getData($this->getAlias())['education_grade_id'];
                $attendance_type = $this->request->getData($this->getAlias())['attendance_type'];

                $StudentAttendanceTypes = self::getDynamicTableInstance('Attendance.StudentAttendanceTypes');
                if (!empty($attendance_type)) {

                    $attendanceTypeData = $StudentAttendanceTypes
                        ->find()
                        ->where([
                            $StudentAttendanceTypes->aliasField('id') => $attendance_type
                        ])
                        ->toArray();
                    $attendanceTypeCode = $attendanceTypeData[0]->code;
                }

                $StudentMarkTypeStatusGrades = self::getDynamicTableInstance('Attendance.StudentMarkTypeStatusGrades');
                $StudentMarkTypeStatuses = self::getDynamicTableInstance('Attendance.StudentMarkTypeStatuses');
                $StudentAttendancePerDayPeriods = self::getDynamicTableInstance('Attendance.StudentAttendancePerDayPeriods');

                $gradeCondition = [];
                if ($attendanceTypeCode == 'DAY' || $attendance_type == '') {
                    if ($education_grade_id != -1) {
                        $gradeCondition = [
                            $StudentMarkTypeStatuses->aliasField('academic_period_id') => $academic_period_id,
                            $StudentMarkTypeStatusGrades->aliasField('education_grade_id') => $education_grade_id
                        ];
                    } else {
                        $gradeCondition = [$StudentMarkTypeStatuses->aliasField('academic_period_id') => $academic_period_id];
                    }

                    $periods = $StudentAttendancePerDayPeriods
                        ->find(
                            'list',
                            [
                                'keyField' => 'id',
                                'valueField' => 'name'
                            ]
                        )
                        ->leftJoin(
                            [$StudentMarkTypeStatuses->getAlias() => $StudentMarkTypeStatuses->getTable()],
                            [
                                $StudentMarkTypeStatuses->aliasField('student_attendance_mark_type_id') . ' = ' . $StudentAttendancePerDayPeriods->aliasField('student_attendance_mark_type_id')
                            ]
                        )
                        ->leftJoin(
                            [$StudentMarkTypeStatusGrades->getAlias() => $StudentMarkTypeStatusGrades->getTable()],
                            [
                                $StudentMarkTypeStatusGrades->aliasField('student_mark_type_status_id') . ' = ' . $StudentMarkTypeStatuses->aliasField('id')
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

   public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
       // $filter = $requestData->institution_filter;
        $areaId = $requestData->area_education_id; // area id dropdown
        $superAdmin = $requestData->super_admin;
        $userId = $requestData->user_id;
        $institutionIds = $requestData->institution_id->_ids ?? [];
        $selectedArea = $requestData->area_education_id;
        $conditions = [];
        if ($areaId != -1 && $areaId != '') {
            $areaIds = [];
            $allgetArea = $this->getChildren($selectedArea, $areaIds);
            $selectedArea1[]= $selectedArea;
            if(!empty($allgetArea)){
                $allselectedAreas = array_merge($selectedArea1, $allgetArea);
            }else{
                $allselectedAreas = $selectedArea1;
            }
            $conditions['Institutions.area_id IN'] = $allselectedAreas;
        }

        if (!empty($institutionIds) && !in_array(0, $institutionIds)) {
            if (!$superAdmin) {
                $conditions['Institutions.id IN'] = $institutionIds;
            } else {
                $conditions['Institutions.id IN'] = $institutionIds;
            }
        }elseif (!empty($areaId) && $areaId != -1) {
            // "All Institutions" selected -> get institutions by area
            $Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
            $areaInstitutionIds = $Institutions->find()
                ->select(['id'])
                ->where(['area_id' => $areaId])
                ->extract('id')
                ->toArray();
            if (!empty($areaInstitutionIds)) {
                $conditions['Institutions.id IN'] = $areaInstitutionIds;
            }
        }
        //POCOR-9449 Start
        $query
            ->contain(['Areas', 'AreaAdministratives', 'Statuses'])
            ->select([
                'area_code' => 'Areas.code',
                'area_administrative_code' => 'AreaAdministratives.code',
                'institution_status' => 'Statuses.name'
            ])->where($conditions);  
    }

    public
    function onUpdateFieldEducationSubjectId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $alias = $this->getAlias();
        $data = $request->getData($alias);
        if (isset($data['feature'])) {
            $feature = $data['feature'];
            $institutionId = $data['institution_id'];
            $academicPeriodId = $data['academic_period_id'];
            if (in_array(
                $feature,
                [
                    'Report.InstitutionSubjects',
                    'Report.SubjectsBookLists'
                ]
            )) {

                $EducationSubjects = self::getDynamicTableInstance('Education.EducationSubjects');
                $subjectOptions = $EducationSubjects
                    ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
                    ->find('visible')
                    ->order([
                        $EducationSubjects->aliasField('order') => 'ASC'
                    ])
                    ->toArray();

                $attr['type'] = 'select';
                $attr['select'] = false;

                if ($feature == 'Report.InstitutionSubjects') {
                    $educationProgrammeid =  $data['education_programme_id'];

                    if (!$educationProgrammeid || $educationProgrammeid == 0) {
                        $attr['options'] = ['' => __('All Subjects')] + $subjectOptions;
                    } else {
                        $where = [];
                        if ($institutionId != 0) {
                            $where['InstitutionSubjects.institution_id'] = $institutionId;
                        }
                        if (!empty($academicPeriodId)) {
                            $where['InstitutionSubjects.academic_period_id'] = $academicPeriodId;
                        }
                        $EducationProgrammes = self::getDynamicTableInstance('Education.EducationProgrammes');
                        $EducationGrades = self::getDynamicTableInstance('Education.EducationGrades');
                        $EducationSubjects = self::getDynamicTableInstance('Education.EducationSubjects');
                        $EducationGradesSubjects = self::getDynamicTableInstance('Education.EducationGradesSubjects');
                        $EducationProgrammes = self::getDynamicTableInstance('Education.EducationProgrammes');
                        $InstitutionGrades = self::getDynamicTableInstance('Institution.InstitutionGrades');
                        $InstitutionSubjects = self::getDynamicTableInstance('Institution.InstitutionSubjects');
                        $subjectOptions = $EducationProgrammes
                            ->find()
                            ->select([
                                'EducationSubjects.name',
                                'EducationSubjects.id'
                            ])
                            ->innerJoin(
                                ['EducationGrades' => 'education_grades'],
                                ['EducationGrades.education_programme_id = ' . $EducationProgrammes->aliasField('id')]
                            )
                            ->innerJoin(['InstitutionGrades' => 'institution_grades'], [
                                'InstitutionGrades.education_grade_id = ' . $EducationGrades->aliasField('id')
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
                        foreach ($subjectOptions as $value) {
                            $filteredSubjectOptions[$value->EducationSubjects['id']] = $value->EducationSubjects['name'];
                        }
                        $attr['options'] = $filteredSubjectOptions;
                    }
                } else {
                    $attr['options'] = $subjectOptions;
                }
            } else {
                $attr['value'] = self::NO_FILTER;
            }
            return $attr;
        }
    }

    //POCOR-5762 starts
    public
    function onUpdateFieldLeaveType(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];
            if (in_array($feature, ['Report.StaffLeave'])) {
                $staffLeaveTypeTable = self::getDynamicTableInstance('Staff.StaffLeaveTypes');
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
                    } else {
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

    public
    function onUpdateFieldWorkflowStatus(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];
            if (in_array($feature, ['Report.StaffLeave'])) {
                $institutionStaffLeave = self::getDynamicTableInstance('institution_staff_leave');
                $workflowModelsTable = self::getDynamicTableInstance('workflow_models');
                $workflowsTable = self::getDynamicTableInstance('workflows');

                $workflowStepsTable = self::getDynamicTableInstance('workflow_steps');
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
                        [$institutionStaffLeave->getAlias() => $institutionStaffLeave->getTable()],
                        [
                            $institutionStaffLeave->aliasField('status_id') . ' = ' . $workflowStepsTable->aliasField('id')
                        ]
                    )
                    ->LeftJoin(
                        [$workflowsTable->getAlias() => $workflowsTable->getTable()],
                        [
                            $workflowsTable->aliasField('id') . ' = ' . $workflowStepsTable->aliasField('workflow_id')
                        ]
                    )
                    ->LeftJoin(
                        [$workflowModelsTable->getAlias() => $workflowModelsTable->getTable()],
                        [
                            $workflowModelsTable->aliasField('id') . ' = ' . $workflowsTable->aliasField('workflow_model_id')
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
                    } else {
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
    public
    function onUpdateFieldPositionStatus(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];
            if (in_array($feature, ['Report.InstitutionPositionsSummaries'])) {
                $institutionStaffLeave = self::getDynamicTableInstance('institution_staff_leave');
                $workflowModelsTable = self::getDynamicTableInstance('workflow_models');
                $workflowsTable = self::getDynamicTableInstance('workflow_statuses');
                $workflowsData = self::getDynamicTableInstance('workflows');
                $status = array('Active', 'Inactive');
                $workflowStepsTable = self::getDynamicTableInstance('workflow_steps');
                //POCOR-7445 start
                $workflowModel = $workflowsData->find()->where([$workflowsData->aliasField('code') => 'POSITION-1001'])->first()->id;
                $workflowStepsOptions = $workflowStepsTable
                    ->find('list', [
                        'keyField' => 'id',
                        'valueField' => 'name'
                    ])
                    ->where(['workflow_id' => $workflowModel, $workflowStepsTable->aliasField('name IN') => $status]);
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
                    } else {
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
    public
    function onUpdateFieldPosition(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];
            if (in_array($feature, ['Report.StaffLeave'])) {
                $staffPositionTitlesTable = self::getDynamicTableInstance('staff_position_titles');
                $institutionPositionsTable = self::getDynamicTableInstance('institution_positions');
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
                        [$staffPositionTitlesTable->getAlias() => $staffPositionTitlesTable->getTable()],
                        [
                            $institutionPositionsTable->aliasField('staff_position_title_id') . ' = ' . $staffPositionTitlesTable->aliasField('id')
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
                    } else {
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

    public
    function onUpdateFieldEducationLevelId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];
            $academicPeriodId = $this->request->getData($this->getAlias())['academic_period_id'];

            if (in_array(
                $feature,
                [
                    'Report.InstitutionStudents'
                ]
            )) {

                $EducationLevels = self::getDynamicTableInstance('Education.EducationLevels');
                $levelOptions = $EducationLevels->find('list', ['valueField' => 'system_level_name'])
                    ->find('visible')
                    ->find('order')
                    ->contain(['EducationSystems'])
                    ->where(['EducationSystems.academic_period_id' => $academicPeriodId])
                    ->toArray();

                $attr['type'] = 'select';
                $attr['select'] = false;
                if (count($levelOptions) > 1) {
                    $attr['options'] = ['' => '-- ' . __('Select') . ' --', 0 => __('All Level')] + $levelOptions;
                } else {
                    $attr['options'] = ['' => '-- ' . __('Select') . ' --'] + $levelOptions;
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
    public function onExcelGetContactPerson(EventInterface $event, Entity $entity)
    {
        $institution_contact_persons = self::getDynamicTableInstance('institution_contact_persons')->find()->where(['institution_id' => $entity['id']])->where(['preferred' => 1])->order(['id' => 'DESC'])->first();
        if (!empty($institution_contact_persons)) {
            return $institution_contact_persons['contact_person'];
        }
        return '';
    }

    /**
     * POCOR-8391
     * Get a dynamic table instance with all associations.
     *
     * @param string $tableName
     * @return \Cake\ORM\Table
     */
    private static function getDynamicTableInstance(string $tableName): Table
    {
        // Parse plugin and table names if dot notation is used
        $locator = TableRegistry::getTableLocator();
        try {
            return $locator->get($tableName);
        } catch (\Exception $exception) {
        }
        if ($tableName == 'Institution.InstitutionStatuses') {
            $tableName = 'Institution.Statuses';
        }
        $parts = explode('.', $tableName);
        $plugin = count($parts) > 1 ? $parts[0] : null;
        $table = count($parts) > 1 ? $parts[1] : $parts[0];

        // Convert the table name to camel case as expected by CakePHP conventions
        $tableFullAlias = Inflector::camelize($tableName);
        $tableAlias = Inflector::camelize($table);

        // Create the fully qualified class name if a plugin is specified
        if ($plugin) {
            $className = $plugin . '\\Model\\Table\\' . $tableAlias . 'Table';
        } else {
            $className = 'App\\Model\\Table\\' . $tableAlias . 'Table';
        }
        // Check if the table instance already exists
        if (!$locator->exists($tableFullAlias)) {
            // Check if the specific table class exists
            if (!class_exists($className)) {
                $className = Table::class; // Fallback to generic Table class
            }

            // Configure a new table instance
            $locator->setConfig($tableAlias, [
                'className' => $className,
                'table' => $table,
                'alias' => $tableAlias,
            ]);
        }

        // Return the table instance
        return $locator->get($tableFullAlias);
    }

    public function getChildren($id, $idArray) {
        $Areas = TableRegistry::getTableLocator()->get('Area.Areas');
        $result = $Areas->find()
                           ->where([
                               $Areas->aliasField('parent_id IS') => $id
                            ])
                             ->toArray();
       foreach ($result as $key => $value) {
            $idArray[] = $value['id'];
           $idArray = $this->getChildren($value['id'], $idArray);
        }
        return $idArray;
    }
}
