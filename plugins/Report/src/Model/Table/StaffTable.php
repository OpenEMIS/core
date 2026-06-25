<?php
namespace Report\Model\Table;

use App\Model\Traits\MessagesTrait;
use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\EventInterface;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\Datasource\ResultSetInterface;
use Cake\Http\ServerRequest;

class StaffTable extends AppTable  {
    use MessagesTrait; //POCOR-5185
    const NO_FILTER = 0; //POCOR-6779
    public function initialize(array $config): void
    {
        $this->setTable('security_users');
        parent::initialize($config);

        $this->belongsTo('Genders', ['className' => 'User.Genders']);
        $this->belongsTo('AddressAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'address_area_id']);
        $this->belongsTo('BirthplaceAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'birthplace_area_id']);
        $this->belongsTo('AreaLevels', ['className' => 'Area.AreaLevels']);

        $this->belongsTo('Areas', ['className' => 'Area.Areas']);
        $this->belongsTo('AreaAdministratives', ['className' => 'Area.AreaAdministratives']);
        $this->belongsTo('MainNationalities', ['className' => 'FieldOption.Nationalities', 'foreignKey' => 'nationality_id']); //POCOR-7590
        $this->belongsTo('MainIdentityTypes', ['className' => 'FieldOption.IdentityTypes', 'foreignKey' => 'identity_type_id']);//POCOR-7590
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

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        $validator
            ->notEmpty('academic_period_id')
            ->notEmpty('area_level_id')
            ->notEmpty('area_education_id');
        return $validator;
    }

    //POCOR-8417
    public function validationStaff(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        $validator
            ->notEmpty('academic_period_id')
            ->notEmpty('area_level_id')
            ->notEmpty('area_education_id');
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
        
        return $validator;
    }
    public function validationStaffLeaveReport(Validator $validator): Validator

    {
        $validator = $this->validationDefault($validator);
        $validator = $validator
            ->notEmpty('academic_period_id')
            ->notEmpty('institution_id');
        return $validator;
    }

    public function validationStaffDuties(Validator $validator): Validator
    {
        $validator = $this->validationDefault($validator);
        $validator = $validator
            ->notEmpty('academic_period_id')
            ->notEmpty('institution_id');
        return $validator;
    }

    public function validationStaffHealthReports(Validator $validator): Validator
    {
        $validator = $this->validationDefault($validator);
        $validator = $validator
            ->notEmpty('institution_id');
        return $validator;
    }

     public function addBeforePatch(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        if ($data[$this->getAlias()]['feature'] == 'Report.StaffDuties') {
            $options['validate'] = 'StaffDuties';
        }
        if ($data[$this->getAlias()]['feature'] == 'Report.StaffLeaveReport') {
            $options['validate'] = 'StaffLeaveReport';
        }else if ($data[$this->getAlias()]['feature'] == 'Report.StaffHealthReports') {
            $options['validate'] = 'StaffHealthReports';
        }

        //POCOR-5185
        if ($data[$this->getAlias()]['feature'] == 'Report.StaffRequirements') {
            $options['validate'] = 'StaffRequirements';
        }
        //POCOR-8417
        if ($data[$this->getAlias()]['feature'] == 'Report.Staff') {
            $options['validate'] = 'Staff';
        }
    }
    //POCOR - 7408 start
    public function addBeforeAction(EventInterface $event)
    {
        $this->ControllerAction->field('area_education_id', ['type' => 'hidden', 'attr' => ['label'=>'Area Name','required' => true]]);

    }
    //POCOR - 7408 end

    public function beforeAction(EventInterface $event)
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
    public function addAfterAction(EventInterface $event, Entity $entity)
    {
        $fieldsOrder = ['feature'];
        if ($entity->has('feature')) {
            $feature = $entity->feature;
            switch ($feature) {
                case 'Report.Staff':
                    $fieldsOrder[] = 'academic_period_id';
                    $fieldsOrder[] = 'area_level_id';
                    $fieldsOrder[] = 'area_education_id';
                    $fieldsOrder[] = 'institution_id';
                    $fieldsOrder[] = 'institution_dropdown';
                    $fieldsOrder[] = 'format';
                    //custom element field
                    $this->ControllerAction->field('institution_dropdown', [
                        'type'   => 'element',
                        'element'=> 'institutiondropdown',
                    ]);

                    break;
            }
            $this->ControllerAction->setFieldOrder($fieldsOrder);
        }
    }

    public function onUpdateFieldFeature(EventInterface $event, array $attr, $action, ServerRequest $request) {
        $options = $this->controller->getFeatureOptions($this->getAlias());
        $attr['options'] = $this->controller->getFeatureOptions($this->getAlias());
        $attr['onChangeReload'] = true;
        /*POCOR-6176 starts*/
        if (!(isset($this->request->getData($this->getAlias())['feature']))) {
                $option = $attr['options'];
                reset($option);
                $defaultFeatureValue = key($options);
                $this->request = $this->request->withData($this->getAlias() . '.feature', $defaultFeatureValue);
            }
        /*POCORO-6176 ends*/

        //POCOR-5185[start]
        if(isset($this->request->getData($this->getAlias())['feature']) && $this->request->getData($this->getAlias())['feature'] == 'Report.StaffRequirements') {
            $this->fields['academic_period_id']['visible'] = false;
            $this->fields['area_level_id']['visible'] = false;
            $this->fields['area_education_id']['visible'] = false;
            $this->fields['institution_id']['visible'] = false;
        }
        //POCOR-5185[end]

        return $attr;
    }

    public function onUpdateFieldStaffLeaveTypeId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];
            if (in_array($feature, ['Report.StaffLeaveReport'])) {
                $staffLeaveTypeTable = TableRegistry::getTableLocator()->get('Staff.StaffLeaveTypes');
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

    public function onUpdateFieldAcademicPeriodId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];
            if (in_array($feature, ['Report.StaffSalaries',
                                    'Report.StaffLeaveReport',
                'Report.StaffDuties',
                'Report.StaffHealthReports',
                'Report.Staff','Report.StaffPhoto',
                'Report.StaffIdentities','Report.StaffContacts',
                'Report.StaffQualifications',
                'Report.StaffEmploymentStatuses',
                'Report.StaffTrainingReports','Report.StaffPositions','Report.PositionSummary',
                'Report.InstitutionStaffDetailed','Report.StaffSubjects', 'Report.StaffRequirements','Report.StaffOutOfSchool'])) {   // POCOR-4827
                //POCOR-8028 removed academic period in curriculars
                $AcademicPeriodTable = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
                $academicPeriodOptions = $AcademicPeriodTable->getYearList();
                $currentPeriod = $AcademicPeriodTable->getCurrent();//POCOR-6662
                $attr['options'] = $academicPeriodOptions;
                $attr['type'] = 'select';
                $attr['select'] = false;
                 $attr['onChangeReload'] = true; //POCOR-6662
                if (empty($request->getData($this->getAlias())['academic_period_id'])) {
                    $request->getData($this->getAlias())['academic_period_id'] = $currentPeriod; //POCOR-6662
                }
                return $attr;
            }
        }
    }

    public function onUpdateFieldAreaLevelId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];

            if ((in_array($feature, ['Report.Staff',
                'Report.StaffPhoto','Report.StaffIdentities',
                'Report.StaffContacts','Report.StaffQualifications',
                'Report.StaffLicenses','Report.StaffEmploymentStatuses',
                'Report.StaffHealthReports','Report.StaffSalaries','Report.StaffTrainingReports',
                'Report.StaffLeaveReport','Report.StaffPositions','Report.PositionSummary',
                'Report.StaffDuties','Report.StaffExtracurriculars','Report.InstitutionStaffDetailed','Report.StaffSubjects', 'Report.StaffRequirements']))) {
                $Areas = TableRegistry::getTableLocator()->get('Area.AreaLevels');
                $entity = $attr['entity'];

                if ($action == 'add') {
                    $areaOptions = $Areas
                        ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
                        ->order(['level'])
                        ->enableHydration(false);

                    $attr['type'] = 'chosenSelect';
                    $attr['attr']['multiple'] = false;
                    $attr['select'] = true;
                    $attr['options'] = ['' => '-- ' . __('Select') . ' --', '-1' => __('All Areas Level')] + $areaOptions->toArray();
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
        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];
            $areaLevelId = $this->request->getData($this->getAlias())['area_level_id'];//POCOR-6333
            if (in_array($feature, ['Report.Staff'
            ,'Report.StaffPhoto','Report.StaffIdentities',
                'Report.StaffContacts','Report.StaffQualifications',
                'Report.StaffLicenses','Report.StaffEmploymentStatuses',
                'Report.StaffHealthReports','Report.StaffSalaries','Report.StaffTrainingReports',
                'Report.StaffLeaveReport','Report.StaffPositions','Report.PositionSummary',
                'Report.StaffDuties','Report.StaffExtracurriculars','Report.InstitutionStaffDetailed','Report.StaffSubjects', 'Report.StaffRequirements'])) {
                $Areas = TableRegistry::getTableLocator()->get('Area.Areas');
                $entity = $attr['entity'];

                if ($action == 'add') {
                    $where = [];
                        if ($areaLevelId != -1 && !empty($areaLevelId)) {
                            if($areaLevelId == 'null' || $areaLevelId == ' '){
                                $where[$Areas->aliasField('area_level_id IS')] = $areaLevelId;
                            }else{
                                $where[$Areas->aliasField('area_level_id')] = $areaLevelId;
                            }
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

    public function onUpdateFieldSystemUsage(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];
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

    public function onUpdateFieldStatus(EventInterface $event, array $attr, $action, ServerRequest $request) {
        if ($action == 'add') {
            $Workflow = TableRegistry::getTableLocator()->get('Workflow.WorkflowModels');
            if (isset($this->request->getData($this->getAlias())['feature'])) {
                $feature = $this->request->getData($this->getAlias())['feature'];
                if (in_array($feature, ['Report.StaffLicenses'])) {
                    $licenseStatuses = $this->getWorkflowStatuses(); //POCOR-9418
                    $licenseStatuses = ['-1' => __('All Statuses')] + $licenseStatuses;
                    $attr['type'] = 'select';
                    $attr['select'] = false;
                    $attr['options'] = $licenseStatuses;
                    return $attr;
                }
            }
        }
    }

    //POCOR-9418
    public function getWorkflowStatuses()
    {
        $WorkflowModels = TableRegistry::getTableLocator()->get('Workflow.WorkflowModels');
        $Workflows = TableRegistry::getTableLocator()->get('Workflow.Workflows');
        $WorkflowFilters = TableRegistry::getTableLocator()->get('Workflow.WorkflowsFilters');
        $WorkflowSteps = TableRegistry::getTableLocator()->get('Workflow.WorkflowSteps');
        //workflow_model_id for Staff.Licenses
        $workflowModel = $WorkflowModels->find()
            ->select(['id'])
            ->where(['model' => 'Staff.Licenses'])
            ->first();

        if (!$workflowModel) {
            return [];
        }
        //latest workflow for this model
        $latestWorkflow = $Workflows->find()
            ->where(['workflow_model_id' => $workflowModel->id, ])
            ->order(['id' => 'DESC'])
            ->first();
        if (!$latestWorkflow) {
            return [];
        }
        $hasFilters = $WorkflowFilters->exists([
            'workflow_id' => $latestWorkflow->id,
            'filter_id !=' => 0
        ]);

        if (!$hasFilters) {
            return [];
        }
        $steps = $WorkflowSteps->find()
            ->select(['id', 'name'])
            ->where(['workflow_id' => $latestWorkflow->id])
            ->order(['id' => 'ASC'])
            ->toArray();
        $statuses = [];
        foreach ($steps as $step) {
            $statuses[$step->id] = $step->name;
        }
        return $statuses;
    }

    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);

        $academicPeriodId = $requestData->academic_period_id;
        $areaId = $requestData->area_education_id;
        $institutionIds = $requestData->institution_id->_ids ?? [];
        $selectedArea = $requestData->area_education_id;
        $InstitutionStaffTable = TableRegistry::getTableLocator()->get('Institution.Staff');
        $InstitutionsTable = TableRegistry::getTableLocator()->get('Institution.Institutions');
        $AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');

        $periodEntity = $AcademicPeriods->get($academicPeriodId);
        $startDate = $periodEntity->start_date->format('Y-m-d');
        $endDate = $periodEntity->end_date->format('Y-m-d');

        $userId = $requestData->user_id;
        $superAdmin = $requestData->super_admin;
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
        // Institution list based on access
        $institutionQuery = $InstitutionsTable
            ->find('list', [
                'keyField' => 'id',
                'valueField' => 'code_name'
            ])
            ->order([
                $InstitutionsTable->aliasField('code') => 'ASC',
                $InstitutionsTable->aliasField('name') => 'ASC'
            ]);

        if (!$superAdmin) {
            $institutionQuery->find('byAccess', ['userId' => $userId]);
        }
        $institutionList = $institutionQuery->toArray();
        // Academic Period Date Conditions
        if (!empty($academicPeriodId)) {
            $conditions[] = [
                'OR' => [
                    [
                        'InstitutionStaff.end_date IS NOT NULL',
                        'InstitutionStaff.start_date <=' => $startDate,
                        'InstitutionStaff.end_date >=' => $startDate
                    ],
                    [
                        'InstitutionStaff.end_date IS NOT NULL',
                        'InstitutionStaff.start_date <=' => $endDate,
                        'InstitutionStaff.end_date >=' => $endDate
                    ],
                    [
                        'InstitutionStaff.end_date IS NOT NULL',
                        'InstitutionStaff.start_date >=' => $startDate,
                        'InstitutionStaff.end_date <=' => $endDate
                    ],
                    [
                        'InstitutionStaff.end_date IS NULL',
                        'InstitutionStaff.start_date <=' => $endDate
                    ]
                ]
            ];
        }

        // Institution Filter (_ids logic)
        if (!empty($institutionIds) && $institutionIds !== [0]) {
            if (in_array(0, $institutionIds)) {
                if (!$superAdmin) {
                    $conditions['InstitutionStaff.institution_id IN'] = array_keys($institutionList);
                }
            } else {
                $conditions['InstitutionStaff.institution_id IN'] = $institutionIds;
            }
        }

        // Area Filter
        if (!empty($areaId) && $areaId != -1) {
            $conditions[$InstitutionsTable->aliasField('area_id')] = $areaId;
        }

        // Main Query
        $query
            ->select([
                'user_id' => $this->aliasField('id'),
                'username' => $this->aliasField('username'),
                'first_name' => $this->aliasField('first_name'),
                'middle_name' => $this->aliasField('middle_name'),
                'third_name' => $this->aliasField('third_name'),
                'last_name' => $this->aliasField('last_name'),
                'preferred_name' => $this->aliasField('preferred_name'),
                'email' => $this->aliasField('email'),
                'address' => $this->aliasField('address'),
                'postal_code' => $this->aliasField('postal_code'),
                'birth_date' => $this->aliasField('date_of_birth'),
                'death_date' => $this->aliasField('date_of_death'),
                'external_reference' => $this->aliasField('external_reference'),
                'preferred_language' => $this->aliasField('preferred_language'),
                'last_login' => $this->aliasField('last_login'),
                'institution_name' => 'Institutions.name',
                'institution_code' => 'Institutions.code',
            ])
            ->contain([
                'AddressAreas' => [
                    'fields' => ['address_area' => 'AddressAreas.name']
                ],
                'BirthplaceAreas' => [
                    'fields' => ['birth_area' => 'BirthplaceAreas.name']
                ],
                'Genders' => [
                    'fields' => ['gender_name' => 'Genders.name']
                ],
                'MainNationalities' => [
                    'fields' => ['nationality_name' => 'MainNationalities.name']
                ],
            ])
            ->innerJoin(['InstitutionStaff' => 'institution_staff'], [
                'InstitutionStaff.staff_id = ' . $this->aliasField('id')
            ])
            ->innerJoin(['Institutions' => 'institutions'], [
                'Institutions.id = InstitutionStaff.institution_id'
            ])
            ->leftJoin([$InstitutionsTable->getAlias() => $InstitutionsTable->getTable()], [
                $InstitutionsTable->aliasField('id') . ' = InstitutionStaff.institution_id'
            ])
            ->where([$this->aliasField('is_staff') => 1])
            ->andWhere($conditions)
            ->group([
                $this->aliasField('id'),
                'Institutions.id'
            ]);
    }

    public function onExcelGetBirthcertificateNumber(EventInterface $event, Entity $entity)
    {

        $userTable = TableRegistry::getTableLocator()->get('security_users');
        $userIdentities = TableRegistry::getTableLocator()->get('user_identities');
        $IdentityType = TableRegistry::getTableLocator()->get('FieldOption.IdentityTypes');
        $birth_certificate_result = $IdentityType->find('all')
                                     ->select('id')
                                     ->where([$IdentityType->aliasField('name') => 'Birth Certificate'])
                                     ->first();
        $birth_certificate_id = 0;
        if(!empty($birth_certificate_result)){
            $birth_certificate_id = $birth_certificate_result->id;
        }
       $data = $userTable->find()
                ->select(['birth_certificate' => $userIdentities->aliasField('number')])
                ->leftJoin([$userIdentities->getAlias() => $userIdentities->getTable()], [
                    $userIdentities->aliasField('security_user_id = ') . $userTable->aliasField('id'),
                ])
                ->leftJoin([$IdentityType->getAlias() => $IdentityType->getTable()], [
                $IdentityType->aliasField('id = ') . $userIdentities->aliasField('identity_type_id')
            ])
            ->where([$userIdentities->aliasField('identity_type_id') => $birth_certificate_id,
                     $userIdentities->aliasField('security_user_id') => $entity->user_id])->first();
            $getbirthCertificate = $data->birth_certificate;
            return $getbirthCertificate;
    }

    public function onExcelGetOtherIdentityType(EventInterface $event, Entity $entity)
    {
        $userTable = TableRegistry::getTableLocator()->get('User.Users');
        $userIdentities = TableRegistry::getTableLocator()->get('User.UserIdentities');
        $IdentityType = TableRegistry::getTableLocator()->get('FieldOption.IdentityTypes');
        $birth_certificate_result = $IdentityType->find('all')
                                     ->select('id')
                                     ->where([$IdentityType->aliasField('name') => 'Birth Certificate'])
                                     ->first();
        $birth_certificate_id = 0;
        if(!empty($birth_certificate_result)){
            $birth_certificate_id = $birth_certificate_result->id;
        }
       $data = $userTable->find()
                ->select(['IdentityTypes' => $IdentityType->aliasField('name'), 'number' => $userIdentities->aliasField('number')])
                ->leftJoin([$userIdentities->getAlias() => $userIdentities->getTable()], [
                    $userIdentities->aliasField('security_user_id = ') . $userTable->aliasField('id'),
                ])
                ->leftJoin([$IdentityType->getAlias() => $IdentityType->getTable()], [
                $IdentityType->aliasField('id = ') . $userIdentities->aliasField('identity_type_id')
            ])
            ->where([$userIdentities->aliasField('identity_type_id IS NOT') => $birth_certificate_id,
                     $userIdentities->aliasField('security_user_id') => $entity->user_id])->toArray();

        $entity->getIdentityTypes = '';
        $entity->getIdentitynumber = '';
        if(!empty($data)){
            foreach($data as $result){
                $entity->getIdentityTypes = $result->IdentityTypes;
                $entity->getIdentitynumber = $result->number;
            }
        }
         return $entity->getIdentityTypes;
    }

    public function onExcelGetOtherIdentityNumber(EventInterface $event, Entity $entity)
    {
        return $entity->getIdentitynumber;
    }

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, ArrayObject $fields) {
        $IdentityType = TableRegistry::getTableLocator()->get('FieldOption.IdentityTypes');
        $identity = $IdentityType->getDefaultEntity();
         $newFields[] = [
            'key' => 'username',
            'field' => 'username',
            'type' => 'string',
            'label' => __('Username')
        ];
        $newFields[] = [
            'key' => 'openemis_no',
            'field' => 'openemis_no',
            'type' => 'integer',
            'label' => __('OpenEMIS ID')
        ];
        $newFields[] = [
            'key' => 'first_name',
            'field' => 'first_name',
            'type' => 'string',
            'label' => __('First Names')
        ];
        $newFields[] = [
            'key' => 'middle_name',
            'field' => 'middle_name',
            'type' => 'string',
            'label' => __('Middle Names')
        ];
        $newFields[] = [
            'key' => 'third_name',
            'field' => 'third_name',
            'type' => 'string',
            'label' => __('Third Names')
        ];
        $newFields[] = [
            'key' => 'last_name',
            'field' => 'last_name',
            'type' => 'string',
            'label' => __('Last Names')
        ];
        $newFields[] = [
            'key' => 'institution_name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institution Name')
        ];
        $newFields[] = [
            'key' => 'institution_code',
            'field' => 'institution_code',
            'type' => 'string',
            'label' => __('Institution Code')
        ];
        $newFields[] = [
            'key' => 'preferred_name',
            'field' => 'preferred_name',
            'type' => 'string',
            'label' => __('Preferred Names')
        ];
        $newFields[] = [
            'key' => 'email',
            'field' => 'email',
            'type' => 'string',
            'label' => __('Email')
        ];
        $newFields[] = [
            'key' => 'address',
            'field' => 'address',
            'type' => 'string',
            'label' => __('Address')
        ];
        $newFields[] = [
            'key' => 'postal_code',
            'field' => 'postal_code',
            'type' => 'string',
            'label' => __('Postal Code')
        ];
        $newFields[] = [
            'key' => 'address_area',
            'field' => 'address_area',
            'type' => 'string',
            'label' => __('Address Area')
        ];
        $newFields[] = [
            'key' => 'birth_area',
            'field' => 'birth_area',
            'type' => 'string',
            'label' => __('Birthplace Area')
        ];
        $newFields[] = [
            'key' => 'gender_name',
            'field' => 'gender_name',
            'type' => 'string',
            'label' => __('Gender')
        ];
        $newFields[] = [
            'key' => 'birth_date',
            'field' => 'birth_date',
            'type' => 'string', // POCOR-9510
            'label' => __('Date of Birth')
        ];
        $newFields[] = [
            'key' => 'death_date',
            'field' => 'death_date',
            'type' => 'string', // POCOR-9510
            'label' => __('Date of death')
        ];
        $newFields[] = [
            'key' => 'nationality_name',
            'field' => 'nationality_name',
            'type' => 'string',
            'label' => __('Nationality')
        ];

        $newFields[] = [
            'key' => 'birth_certificate_number',
            'field' => 'birth_certificate_number',
            'type' => 'string',
            'label' => __('Birth Certificate')
        ];
        $newFields[] = [
            'key' => 'other_identity_type',
            'field' => 'other_identity_type',
            'type' => 'string',
            'label' => __('Other Identity Type')
        ];
        $newFields[] = [
            'key' => 'other_identity_number',
            'field' => 'other_identity_number',
            'type' => 'string',
            'label' => __('Other Identity Number')
        ];

        $newFields[] = [
            'key' => 'external_reference',
            'field' => 'external_reference',
            'type' => 'string',
            'label' => __('External Reference')
        ];
        $newFields[] = [
            'key' => 'last_login',
            'field' => 'last_login',
            'type' => 'datetime',
            'label' => __('Last Login')
        ];
        $newFields[] = [
            'key' => 'preferred_language',
            'field' => 'preferred_language',
            'type' => 'string',
            'label' => __('Preferred Language')
        ];

        $fields->exchangeArray($newFields);
    }

    public function onUpdateFieldAreaId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];

            if (in_array($feature, [
                  ])) {
                    $Areas = TableRegistry::getTableLocator()->get('Area.Areas');
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
    public function onUpdateFieldInstitutionId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $InstitutionsTable = TableRegistry::getTableLocator()->get('Institution.Institutions');
        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];

            if (in_array($feature, ['Report.StaffPositions', 'Report.StaffHealthReports','Report.StaffLeaveReport',
                'Report.StaffDuties',
                'Report.PositionSummary',
                'Report.Staff','Report.StaffPhoto',
                'Report.StaffIdentities','Report.StaffContacts',
                'Report.StaffQualifications','Report.StaffQualifications',
                'Report.StaffLicenses','Report.StaffEmploymentStatuses','Report.StaffSalaries',
                'Report.StaffTrainingReports','Report.StaffExtracurriculars','Report.InstitutionStaffDetailed','Report.StaffSubjects', 'Report.StaffRequirements'])) {
                $areaId = $this->request->getData($this->getAlias())['area_education_id'];
                if(!empty($areaId) && $areaId != -1) {
                    //Start:POCOR-6779
                    $AreaT = TableRegistry::getTableLocator()->get('Area.Areas');
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
                      //  'Report.Staff',
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

                           $institutionOptions = ['' => '-- ' . __('Select') . ' --', 0 => __('All Institutions')]+ $institutionList ;
                        } else {

                            $institutionOptions = ['' => '-- ' . __('Select') . ' --', 0 => __('All Institutions')] + $institutionList;
                        }

                    } else {

                        $institutionOptions = ['' => '-- ' . __('Select') . ' --'] + $institutionList;
                    }
                    if($superAdmin){
                        $institutionOptions = ['' => '-- ' . __('Select') . ' --', 0 => __('All Institutions')]+ $institutionList ;
                    }

                    if(in_array($feature, ['Report.Staff'])) { //POCOR-8417
                        $attr['attr']['multiple'] = true;
                    } else {
                        $attr['attr']['multiple'] = false;
                    }
                    $attr['type'] = 'chosenSelect';
                    $attr['onChangeReload'] = true;
                    $attr['options'] = $institutionOptions;
                    $attr['attr']['required'] = true;
                }
            //}
            return $attr;
        }
    }
    //Start:POCOR-6779
    public function onUpdateFieldEducationGradeId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($this->request->getData($this->getAlias())['academic_period_id'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];
            $academicPeriodId = $this->request->getData($this->getAlias())['academic_period_id'];
            if (in_array($feature,
                        [
                            'Report.StaffSubjects'
                        ])
                ) {

                $EducationGrades = TableRegistry::getTableLocator()->get('Education.EducationGrades');
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
                if (array_key_exists('institution_id', $request->getData($this->getAlias())) && !empty($request->getData($this->getAlias())['institution_id']) && array_key_exists('academic_period_id', $request->getData($this->getAlias())) && !empty($request->getData($this->getAlias())['academic_period_id'])) {
                    $institutionId = $request->getData($this->getAlias())['institution_id'];
                    $academicPeriodId = $request->getData($this->getAlias())['academic_period_id'];

                    $InstitutionGradesTable = TableRegistry::getTableLocator()->get('Institution.InstitutionGrades');
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

    public function onUpdateFieldEducationSubjectId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {

        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];
            if (in_array($feature,
                        [
                            'Report.InstitutionSubjects'

                        ])
                ) {

                $EducationSubjects = TableRegistry::getTableLocator()->get('Education.EducationSubjects');
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

                $EducationGradesSubjects = TableRegistry::getTableLocator()->get('Education.EducationGradesSubjects');
                $EducationSubjects = TableRegistry::getTableLocator()->get('Education.EducationSubjects');
                $subjectOptions = $EducationGradesSubjects
                                    ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
                                    ->select([
                                        'education_subject_id' => $EducationGradesSubjects->aliasField('education_subject_id'),
                                        'education_grade_id' => $EducationGradesSubjects->aliasField('education_grade_id'),
                                        'id' => $EducationSubjects->aliasField('id'),
                                        'name' => $EducationSubjects->aliasField('name')
                                    ])
                                    ->leftJoin(
                                        [$EducationSubjects->getAlias() => $EducationSubjects->getTable()],
                                        [
                                            $EducationSubjects->aliasField('id = ') . $EducationGradesSubjects->aliasField('education_subject_id')
                                        ]
                                    )
                                    ->where([
                                        $EducationGradesSubjects->aliasField('education_grade_id') => $this->request->getData($this->getAlias())['education_grade_id']
                                    ])
                                    ->order([
                                        $EducationSubjects->aliasField('order') => 'ASC'
                                    ])->toArray();
                $attr['type'] = 'select';
                $attr['select'] = false;

                if($this->request->getData($this->getAlias())['education_grade_id'] == -1){ //for all grades
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

    public function onUpdateFieldHealthReportType(EventInterface $event, array $attr, $action, ServerRequest $request){
        if (isset($request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];
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
    public function onUpdateFieldStudentPerTeacherRatio(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $feature = $this->request->getData($this->getAlias())['feature'] ?? null;
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
    public function onUpdateFieldUpperTolerance(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (in_array(($this->request->getData($this->getAlias())['feature'] ?? null), ['Report.StaffRequirements'])) {
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
    public function onUpdateFieldLowerTolerance(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (in_array(($this->request->getData($this->getAlias())['feature'] ?? null), ['Report.StaffRequirements'])) {
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

    // POCOR-9510 start
    public function onExcelGetBirthDate(EventInterface $event, Entity $entity) {
        if (!empty($entity->birth_date)) {
            return $this->formatDate($entity->birth_date);
        }else{
            return '';
        }
    }

    public function onExcelGetDeathDate(EventInterface $event, Entity $entity) {
        if (!empty($entity->death_date)) {
            return $this->formatDate($entity->death_date);
        } else {
            return '';
        }
    }

    // POCOR-9510 end
    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'feature':
                return __('Feature');
            case 'format':
                return __('Format');
            case 'academic_period_id':
                return __('Academic Period');
            case 'area_level_id':
                return __('Area Level');
            case 'institution_id':
                return __('Institution');
            case 'start_date':
                return __('Start Date');
            case 'end_date':
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
            case 'staff_leave_type_id':
                return __('Staff Leave Type');
            case 'student_per_teacher_ratio':
                return __('Student Per Teacher Ratio');
            case 'area_education_id':
                return __('Area Education');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function getChildren($id, $idArray) {
        $Areas = TableRegistry::getTableLocator()->get('Area.Areas');
        $result = $Areas->find()
                           ->where([
                               $Areas->aliasField('parent_id') => $id
                            ])
                             ->toArray();
       foreach ($result as $key => $value) {
            $idArray[] = $value['id'];
           $idArray = $this->getChildren($value['id'], $idArray);
        }
        return $idArray;
    }
}
