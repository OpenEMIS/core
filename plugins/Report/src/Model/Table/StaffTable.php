<?php
namespace Report\Model\Table;

use App\Model\Traits\MessagesTrait;
use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class StaffTable extends AppTable  {
    use MessagesTrait; //POCOR-5185
    const NO_FILTER = 0; //POCOR-6779
    public function initialize(array $config) {
        $this->table('security_users');
        parent::initialize($config);

        $this->belongsTo('Genders', ['className' => 'User.Genders']);
        $this->belongsTo('AddressAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'address_area_id']);
        $this->belongsTo('BirthplaceAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'birthplace_area_id']);
        $this->belongsTo('AreaLevels', ['className' => 'AreaLevel.AreaLevels']);

        $this->belongsTo('Areas', ['className' => 'Area.Areas']);
        $this->belongsTo('AreaAdministratives', ['className' => 'Area.AreaAdministratives']);
        $this->addBehavior('Excel', [
            'excludes' => ['is_student', 'is_staff', 'is_guardian', 'photo_name', 'super_admin', 'status'],
            'pages' => false
        ]);

        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Report.CustomFieldList', [
            'model' => 'Staff.Staff',
            'formFilterClass' => null,
            'fieldValueClass' => ['className' => 'StaffCustomField.StaffCustomFieldValues', 'foreignKey' => 'staff_id', 'dependent' => true, 'cascadeCallbacks' => true],
            'tableCellClass' => ['className' => 'StaffCustomField.StaffCustomTableCells', 'foreignKey' => 'staff_id', 'dependent' => true, 'cascadeCallbacks' => true, 'saveStrategy' => 'replace']
        ]);
    }

    public function validationStaffLeaveReport(Validator $validator)

    {
        $validator = $this->validationDefault($validator);
        $validator = $validator
            ->notEmpty('academic_period_id')
            ->notEmpty('institution_id');
        return $validator;
    }

    public function validationStaffDuties(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        $validator = $validator
            ->notEmpty('academic_period_id')
            ->notEmpty('institution_id');
        return $validator;
    }

    public function validationStaffHealthReports(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        $validator = $validator
            ->notEmpty('institution_id');
        return $validator;
    }

     public function addBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        if ($data[$this->alias()]['feature'] == 'Report.StaffDuties') {
            $options['validate'] = 'StaffDuties';
        }
        if ($data[$this->alias()]['feature'] == 'Report.StaffLeaveReport') {
            $options['validate'] = 'StaffLeaveReport';
        }else if ($data[$this->alias()]['feature'] == 'Report.StaffHealthReports') {
            $options['validate'] = 'StaffHealthReports';
        }

        //POCOR-5185[start]
        if ($data[$this->alias()]['feature'] == 'Report.StaffRequirements') {
            $options['validate'] = 'StaffRequirements';
        }
        //POCOR-5185[end]
    }
    //POCOR - 7408 start
    public function addBeforeAction(Event $event)
    {
        $this->ControllerAction->field('area_education_id', ['type' => 'hidden', 'attr' => ['label'=>'Area Name','required' => true]]);
       
    }
    //POCOR - 7408 end

    public function beforeAction(Event $event)
    {
        $this->fields = [];

        $this->ControllerAction->field('feature', ['select' => false]);
        $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);
        $this->ControllerAction->field('area_id', ['type' => 'hidden']);
        $this->ControllerAction->field('area_level_id', ['type' => 'hidden']);
        $this->ControllerAction->field('area_education_id', ['type' => 'hidden', 'attr' => ['required' => true]]);
        $this->ControllerAction->field('institution_id', ['type' => 'hidden']);
        $this->ControllerAction->field('status', ['type' => 'hidden']);
        $this->ControllerAction->field('system_usage', ['type' => 'hidden']);
        $this->ControllerAction->field('staff_leave_type_id', ['type' => 'hidden']);
        $this->ControllerAction->field('health_report_type',['type' => 'hidden']);
        $this->ControllerAction->field('education_grade_id', ['type' => 'hidden']); //POCOR-6779
        $this->ControllerAction->field('education_subject_id', ['type' => 'hidden']); //POCOR-6779
        $this->ControllerAction->field('student_per_teacher_ratio', ['type' => 'hidden']); //POCOR-5185
        $this->ControllerAction->field('upper_tolerance', ['type' => 'hidden']); //POCOR-5185
        $this->ControllerAction->field('lower_tolerance', ['type' => 'hidden']); //POCOR-5185
        $this->ControllerAction->field('format');

    }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request) {
        $attr['options'] = $this->controller->getFeatureOptions($this->alias());
        $attr['onChangeReload'] = true;
        /*POCOR-6176 starts*/
        if (!(isset($this->request->data[$this->alias()]['feature']))) {
                $option = $attr['options'];
                reset($option);
                $this->request->data[$this->alias()]['feature'] = key($option);
        }
        /*POCORO-6176 ends*/

        //POCOR-5185[start]
        if(isset($this->request->data[$this->alias()]['feature']) && $this->request->data[$this->alias()]['feature'] == 'Report.StaffRequirements') {
            $this->fields['academic_period_id']['visible'] = false;
            $this->fields['area_level_id']['visible'] = false;
            $this->fields['area_education_id']['visible'] = false;
            $this->fields['institution_id']['visible'] = false;
        }
        //POCOR-5185[end]

        return $attr;
    }

    public function onUpdateFieldStaffLeaveTypeId(Event $event, array $attr, $action, Request $request)
    {
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            if (in_array($feature, ['Report.StaffLeaveReport'])) {
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
                        'Report.StaffLeaveReport'
                    ])) {
                        $staffLeaveTypeOptions = ['' => '-- ' . __('Select') . ' --', '0' => __('All Staff Leaves')] + $staffLeaveTypeList;
                    }else {
                        $staffLeaveTypeOptions = ['' => '-- ' . __('Select') . ' --'] + $staffLeaveTypeList;
                    }

                    $attr['type'] = 'chosenSelect';
                    $attr['onChangeReload'] = true;
                    $attr['attr']['multiple'] = false;
                    $attr['options'] = $staffLeaveTypeOptions;
                    $attr['attr']['required'] = true;
                }
            }
        }

        return $attr;
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            if (in_array($feature, ['Report.StaffSalaries',
                                    'Report.StaffLeaveReport',
                'Report.StaffDuties',
                'Report.StaffHealthReports',
                'Report.Staff','Report.StaffPhoto',
                'Report.StaffIdentities','Report.StaffContacts',
                'Report.StaffQualifications','Report.StaffLicenses',
                'Report.StaffEmploymentStatuses',
                'Report.StaffTrainingReports','Report.StaffPositions','Report.PositionSummary',
                'Report.StaffExtracurriculars','Report.InstitutionStaffDetailed','Report.StaffSubjects', 'Report.StaffRequirements','Report.StaffOutOfSchool'])) {   // POCOR-4827
                $AcademicPeriodTable = TableRegistry::get('AcademicPeriod.AcademicPeriods');
                $academicPeriodOptions = $AcademicPeriodTable->getYearList();
                $currentPeriod = $AcademicPeriodTable->getCurrent();//POCOR-6662
                $attr['options'] = $academicPeriodOptions;
                $attr['type'] = 'select';
                $attr['select'] = false;
                 $attr['onChangeReload'] = true; //POCOR-6662
                if (empty($request->data[$this->alias()]['academic_period_id'])) {
                    $request->data[$this->alias()]['academic_period_id'] = $currentPeriod; //POCOR-6662 
                }
                return $attr;
            }
        }
    }

    public function onUpdateFieldAreaLevelId(Event $event, array $attr, $action, Request $request)
    {
        if (isset($request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];

            if ((in_array($feature, ['Report.Staff',
                'Report.StaffPhoto','Report.StaffIdentities',
                'Report.StaffContacts','Report.StaffQualifications',
                'Report.StaffLicenses','Report.StaffEmploymentStatuses',
                'Report.StaffHealthReports','Report.StaffSalaries','Report.StaffTrainingReports',
                'Report.StaffLeaveReport','Report.StaffPositions','Report.PositionSummary',
                'Report.StaffDuties','Report.StaffExtracurriculars','Report.InstitutionStaffDetailed','Report.StaffSubjects', 'Report.StaffRequirements']))) {
                $Areas = TableRegistry::get('AreaLevel.AreaLevels');
                $entity = $attr['entity'];

                if ($action == 'add') {
                    $areaOptions = $Areas
                        ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
                        ->order([$Areas->aliasField('level')]);

                    $attr['type'] = 'chosenSelect';
                    $attr['attr']['multiple'] = false;
                    $attr['select'] = true;
                    $attr['options'] = ['' => '-- ' . _('Select') . ' --', '-1' => _('All Areas Level')] + $areaOptions->toArray();
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
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            $areaLevelId = $this->request->data[$this->alias()]['area_level_id'];//POCOR-6333
            if (in_array($feature, ['Report.Staff'
            ,'Report.StaffPhoto','Report.StaffIdentities',
                'Report.StaffContacts','Report.StaffQualifications',
                'Report.StaffLicenses','Report.StaffEmploymentStatuses',
                'Report.StaffHealthReports','Report.StaffSalaries','Report.StaffTrainingReports',
                'Report.StaffLeaveReport','Report.StaffPositions','Report.PositionSummary',
                'Report.StaffDuties','Report.StaffExtracurriculars','Report.InstitutionStaffDetailed','Report.StaffSubjects', 'Report.StaffRequirements'])) {
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

    public function onUpdateFieldSystemUsage(Event $event, array $attr, $action, Request $request)
    {
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            if (in_array($feature, ['Report.StaffSystemUsage'])) {
                $options = [
                    '1' => __('No previous login'),
                    '2' => __('Logged in within the last 7 days')
                ];
                $attr['type'] = 'select';
                $attr['select'] = false;
                $attr['options'] = $options;
                return $attr;
            }
        }
    }

    public function onUpdateFieldStatus(Event $event, array $attr, $action, Request $request) {
        if ($action == 'add') {
            if (isset($this->request->data[$this->alias()]['feature'])) {
                $feature = $this->request->data[$this->alias()]['feature'];

                if (in_array($feature, ['Report.StaffLicenses'])) {
                    $licenseStatuses = $this->Workflow->getWorkflowStatuses('Staff.Licenses');
                    $licenseStatuses = ['-1' => __('All Statuses')] + $licenseStatuses;

                    $attr['type'] = 'select';
                    $attr['select'] = false;
                    $attr['options'] = $licenseStatuses;
                    return $attr;
                }
            }
        }
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) {
        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;
        $areaId = $requestData->area_education_id;
        $institutionId = $requestData->institution_id;
        $InstitutionStaffTable = TableRegistry::get('Institution.Staff');
        $InstitutionsTable = TableRegistry::get('Institution.Institutions');
        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $periodEntity = $AcademicPeriods->get($academicPeriodId);
        $startDate = $periodEntity->start_date->format('Y-m-d');
        $endDate = $periodEntity->end_date->format('Y-m-d');
        $userId = $requestData->user_id;
        $superAdmin = $requestData->super_admin;
        $institutionQuery = $InstitutionsTable
                        ->find('list', [
                            'keyField' => 'id',
                            'valueField' => 'code_name'
                        ])
                        ->order([
                            $InstitutionsTable->aliasField('code') => 'ASC',
                            $InstitutionsTable->aliasField('name') => 'ASC'
                        ]);

        if (!$superAdmin) { // if user is not super admin, the list will be filtered
            $institutionQuery->find('byAccess', ['userId' => $userId]);
        }
        $institutionList = $institutionQuery->toArray();
        $conditions = [];
        if (!empty($academicPeriodId)) {
                $conditions['OR'] = [
                    'OR' => [
                        [
                            'InstitutionStaff.end_date' . ' IS NOT NULL',
                            'InstitutionStaff.start_date' . ' <=' => $startDate,
                            'InstitutionStaff.end_date' . ' >=' => $startDate
                        ],
                        [
                            'InstitutionStaff.end_date' . ' IS NOT NULL',
                            'InstitutionStaff.start_date' . ' <=' => $endDate,
                            'InstitutionStaff.end_date' . ' >=' => $endDate
                        ],
                        [
                            'InstitutionStaff.end_date' . ' IS NOT NULL',
                            'InstitutionStaff.start_date' . ' >=' => $startDate,
                            'InstitutionStaff.end_date' . ' <=' => $endDate
                        ]
                    ],
                    [
                        'InstitutionStaff.end_date' . ' IS NULL',
                        'InstitutionStaff.start_date' . ' <=' => $endDate
                    ]
                ];
        }
        if ($institutionId == 0 && !$superAdmin) {
            $conditions['InstitutionStaff.institution_id IN'] = array_keys($institutionList);
        }
        if (!empty($institutionId) && $institutionId > 0) {
            $conditions['InstitutionStaff.institution_id'] = $institutionId;
        }
        if (!empty($areaId) && $areaId != -1) {
            $conditions[$InstitutionsTable->aliasField('area_id')] = $areaId; 
        }
        $query
            ->innerJoin(['InstitutionStaff' => 'institution_staff'], [
                'InstitutionStaff.staff_id = ' . $this->aliasField('id')
            ])
            ->leftJoin([$InstitutionsTable->alias() => $InstitutionsTable->table()], [
                $InstitutionsTable->aliasField('id = ') . 'InstitutionStaff.institution_id'
            ])
            ->where([$this->aliasField('is_staff') => 1, $conditions]);
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields) {
        $IdentityType = TableRegistry::get('FieldOption.IdentityTypes');
        $identity = $IdentityType->getDefaultEntity();

        foreach ($fields as $key => $field) {
            //get the value from the table, but change the label to become default identity type.
            if ($field['field'] == 'identity_number') {
                $fields[$key] = [
                    'key' => 'Staff.identity_number',
                    'field' => 'identity_number',
                    'type' => 'string',
                    'label' => __($identity->name)
                ];
                break;
            }
        }
    }

    public function onUpdateFieldAreaId(Event $event, array $attr, $action, Request $request)
    {
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];

            if (in_array($feature, [
                  ])) {
                    $Areas = TableRegistry::get('Area.Areas');
                    $entity = $attr['entity'];

                    if ($action == 'add') {
                        $areaOptions = $Areas
                            ->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
                            ->order([$Areas->aliasField('order')]);

                        $attr['type'] = 'chosenSelect';
                        $attr['attr']['multiple'] = false;
                        $attr['select'] = true;
                        $attr['options'] = ['' => '-- ' . __('Select') . ' --', '0' => __('All Areas')] + $areaOptions->toArray();
                        $attr['onChangeReload'] = true;
                    } else {
                        $attr['type'] = 'hidden';
                    }
            }
        }
        return $attr;
    }
    public function onUpdateFieldInstitutionId(Event $event, array $attr, $action, Request $request)
    {
        $InstitutionsTable = TableRegistry::get('Institution.Institutions');
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];

            if (in_array($feature, ['Report.StaffPositions', 'Report.StaffHealthReports','Report.StaffLeaveReport',
                'Report.StaffDuties',
                'Report.PositionSummary',
                'Report.Staff','Report.StaffPhoto',
                'Report.StaffIdentities','Report.StaffContacts',
                'Report.StaffQualifications','Report.StaffQualifications',
                'Report.StaffLicenses','Report.StaffEmploymentStatuses','Report.StaffSalaries',
                'Report.StaffTrainingReports','Report.StaffExtracurriculars','Report.InstitutionStaffDetailed','Report.StaffSubjects', 'Report.StaffRequirements'])) {
                $areaId = $this->request->data[$this->alias()]['area_education_id'];
                if(!empty($areaId) && $areaId != -1) {
                    //Start:POCOR-6779
                    $AreaT = TableRegistry::get('areas');
                    $AreaData = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $areaId])->toArray();
                    $childArea =[];
                    foreach($AreaData as $kkk =>$AreaData11 ){
                        $childArea[$kkk] = $AreaData11->id;
                    }
                    array_push($childArea,$areaId);
                    $finalIds = implode(',',$childArea);
                    $finalIds = explode(',',$finalIds);
                    //End:POCOR-6779
                    $institutionQuery = $InstitutionsTable
                        ->find('list', [
                            'keyField' => 'id',
                            'valueField' => 'code_name'
                        ])
                        ->where([
                                $InstitutionsTable->aliasField('area_id').' IN' => $finalIds //POCOR-6779
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
                /*if (empty($institutionList)) {
                    $institutionOptions = ['' => $this->getMessage('general.select.noOptions')];
                    $attr['type'] = 'select';
                    $attr['options'] = $institutionOptions;
                    $attr['attr']['required'] = true;
                } else {*/
                    if (in_array($feature, [
                        'Report.StaffPositions',
                        'Report.StaffLeaveReport',
                        'Report.StaffDuties',
                        'Report.PositionSummary',
                        'Report.Staff',
                        'Report.StaffPhoto',
                        'Report.StaffIdentities',
                        'Report.StaffContacts',
                        'Report.StaffQualifications',
                        'Report.StaffLicenses',
                        'Report.StaffEmploymentStatuses',
                        'Report.StaffHealthReports',
                        'Report.StaffSalaries',
                        'Report.StaffTrainingReports',
                        'Report.StaffExtracurriculars',
                        'Report.InstitutionStaffDetailed'//POCOR-6662
                        ,'Report.StaffSubjects' //POCOR-6688
                        ,'Report.StaffRequirements' //POCOR-5785
                        ,'Report.StaffOutOfSchool' //POCOR- 4827
                    ])) {
                        if (!empty($institutionList) && count($institutionList) > 1) {

                           $institutionOptions = ['' => '-- ' . __('Select') . ' --', '0' => __('All Institutions')]+ $institutionList ;
                        } else {
                            
                            $institutionOptions = ['' => '-- ' . __('Select') . ' --', '0' => __('All Institutions')] + $institutionList;
                        }
                        
                    } else {
                        
                        $institutionOptions = ['' => '-- ' . __('Select') . ' --'] + $institutionList;
                    }

                    $attr['type'] = 'chosenSelect';
                    $attr['onChangeReload'] = true;
                    $attr['attr']['multiple'] = false;
                    $attr['options'] = $institutionOptions;
                    $attr['attr']['required'] = true;
                }
            //}
            return $attr;
        }
    }
    //Start:POCOR-6779
    public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, Request $request)
    {
        if (isset($this->request->data[$this->alias()]['academic_period_id'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            $academicPeriodId = $this->request->data[$this->alias()]['academic_period_id'];
            if (in_array($feature,
                        [
                            'Report.StaffSubjects'
                        ])
                ) {

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
                    ->contain(['EducationProgrammes.EducationCycles.EducationLevels.EducationSystems'])
                    ->where([
                        'EducationSystems.academic_period_id' => $academicPeriodId,
                    ])
                    ->order([
                        'EducationProgrammes.order' => 'ASC',
                        $EducationGrades->aliasField('name') => 'ASC'
                    ])
                    ->toArray();
                if (in_array($feature, ['Report.StaffSubjects'])) {
                    $attr['onChangeReload'] = true;
                } 
                $attr['type'] = 'select';
                $attr['select'] = false;
                $attr['options'] = ['-1' => __('All Grades')] + $gradeOptions;
            } elseif (in_array($feature,
                               [
                                   'Report.StudentAttendanceSummary'
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

    public function onUpdateFieldEducationSubjectId(Event $event, array $attr, $action, Request $request)
    {

        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            if (in_array($feature,
                        [
                            'Report.InstitutionSubjects'
                            
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
                $attr['options'] = ['' => __('All Subjects')] + $subjectOptions;
            } elseif(in_array($feature, ['Report.StaffSubjects'])){ 

                $EducationGradesSubjects = TableRegistry::get('education_grades_subjects');
                $EducationSubjects = TableRegistry::get('Education.EducationSubjects');
                $subjectOptions = $EducationGradesSubjects
                                    ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
                                    ->select([
                                        'education_subject_id' => $EducationGradesSubjects->aliasField('education_subject_id'),
                                        'education_grade_id' => $EducationGradesSubjects->aliasField('education_grade_id'),
                                        'id' => $EducationSubjects->aliasField('id'),
                                        'name' => $EducationSubjects->aliasField('name')
                                    ])
                                    ->leftJoin(
                                        [$EducationSubjects->alias() => $EducationSubjects->table()],
                                        [
                                            $EducationSubjects->aliasField('id = ') . $EducationGradesSubjects->aliasField('education_subject_id')
                                        ]
                                    )
                                    ->where([
                                        $EducationGradesSubjects->aliasField('education_grade_id') => $this->request->data[$this->alias()]['education_grade_id']
                                    ])
                                    ->order([
                                        $EducationSubjects->aliasField('order') => 'ASC'
                                    ])->toArray();
                $attr['type'] = 'select';
                $attr['select'] = false;

                if($this->request->data[$this->alias()]['education_grade_id'] == -1){ //for all grades
                    $attr['options'] = ['' => __('All Subjects')];
                }else{
                    $attr['options'] = $subjectOptions;
                }
            } else {
                $attr['value'] = self::NO_FILTER;
            }
            return $attr;
        }
    }
    //End:POCOR-6779

    public function onUpdateFieldHealthReportType(Event $event, array $attr, $action, Request $request){
        if (isset($request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            if ((in_array($feature, ['Report.StaffHealthReports']))
                ) {
                //POCOR-5890 starts
                $healthReportTypeOptions = [
                    'Overview' => __('Overview'),
                    'Allergies' => __('Allergies'),
                    'Consultations' => __('Consultations'),
                    'Families' => __('Families'),
                    'Histories' => __('Histories'),
                    'Immunizations' => __('Vaccinations'),//POCOR-5890
                    'Medications' => __('Medications'),
                    'Tests' => __('Tests'),
                    'Insurance' => __('Insurance'),
                ];
                //POCOR-5890 ends
                $attr['options'] = $healthReportTypeOptions;
                $attr['type'] = 'select';
                $attr['select'] = false;
                $attr['onChangeReload'] = true;

                return $attr;
            }
        }
    }

    //POCOR-5185[start]
    /***
     * function to get value for student per teacher ratio on feature of staff requirements
     * @author lakshman jangid
     * @return array
     * @ticket POCOR-5185
    **/
    public function onUpdateFieldStudentPerTeacherRatio(Event $event, array $attr, $action, Request $request)
    {
        $feature = $this->request->data[$this->alias()]['feature'] ?? null;
        if ($feature && in_array($feature, ['Report.StaffRequirements'])) {
            $attr['type'] = 'integer';
            $attr['attr']['min'] = 0;
            $attr['attr']['max'] = 150;
            $attr['attr']['required'] = true;
            return $attr;
        }
    }

    /***
     * function to get value for Upper Tolerance on feature of staff requirements
     * @author lakshman jangid
     * @return array
     * @ticket POCOR-5185
     **/
    public function onUpdateFieldUpperTolerance(Event $event, array $attr, $action, Request $request)
    {
        if (in_array(($this->request->data[$this->alias()]['feature'] ?? null), ['Report.StaffRequirements'])) {
            $attr['type'] = 'integer';
            $attr['attr']['min'] = 0;
            $attr['attr']['max'] = 100;
            $attr['attr']['required'] = true;
            $attr['attr']['step'] = '.01';
            $attr['attr']['label'] = __('Upper Tolerance') . ' <i class="fa fa-info-circle fa-lg icon-blue" tooltip-placement="bottom" uib-tooltip="It corresponds to the Cap that the user selects to restrict Year-over-Year decrease for Students and Staff data." tooltip-append-to-body="true" tooltip-class="tooltip-blue"></i>';
            return $attr;
        }
    }

    /***
     * function to get value for Lower Tolerance on feature of staff requirements
     * @author lakshman jangid
     * @return array
     * @ticket POCOR-5185
     **/
    public function onUpdateFieldLowerTolerance(Event $event, array $attr, $action, Request $request)
    {
        if (in_array(($this->request->data[$this->alias()]['feature'] ?? null), ['Report.StaffRequirements'])) {
            $attr['type'] = 'integer';
            $attr['attr']['min'] = 0;
            $attr['attr']['max'] = 99999999;
            $attr['attr']['required'] = true;
            $attr['attr']['step'] = '.01';
            $attr['attr']['label'] = __('Lower Tolerance') . ' <i class="fa fa-info-circle fa-lg icon-blue" tooltip-placement="bottom" uib-tooltip="It corresponds to the Cap that the user selects to restrict Year-over-Year increase for Students and Staff data." tooltip-append-to-body="true" tooltip-class="tooltip-blue"></i>';
            return $attr;
        }
    }

    /***
     * function to apply validation for empty values of staff requirements feature
     * @author lakshman jangid
     * @return object
     * @ticket POCOR-5185
     **/
    public function validationStaffRequirements(Validator $validator)
    {
        return $validator->notEmpty(['student_per_teacher_ratio', 'lower_tolerance', 'upper_tolerance'])
            ->range('student_per_teacher_ratio', [0, 150], $this->getMessage('StaffRequirements.studentTeacherRatio'))
            ->range('lower_tolerance', [0, 100], $this->getMessage('StaffRequirements.lowerTolerance'))
            ->add('lower_tolerance', 'comparison', [
                'rule' => function ($value, $context) {
                    return $value <= $context['data']['upper_tolerance'] ;
                },
                'message' => $this->getMessage('StaffRequirements.lowerToleranceCompare')
            ])
            ->range('upper_tolerance', [0, 99999999], $this->getMessage('StaffRequirements.upperTolerance'));
    }
    //POCOR-5185[end]
}
