<?php

namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\Collection\Collection;
use Cake\Validation\Validator;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Cake\Log\Log;


class SurveysTable extends AppTable
{
    const OPEN = 1;
    const PENDINGAPPROVAL = 2;
    const COMPLETED = 3;
    const SURVEY_DISABLED = -1;
    private $surveyStatuses = [];
    private $_dynamicFieldName = 'custom_field_data';//POCOR-8525


    public function initialize(array $config): void
    {
        $this->setTable('institution_surveys');
        parent::initialize($config);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('SurveyForms', ['className' => 'Survey.SurveyForms']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);
        $this->belongsTo('AreaLevels', ['className' => 'Area.AreaLevels']);

        $this->belongsTo('Areas', ['className' => 'Area.Areas']);
        $this->belongsTo('AreaAdministratives', ['className' => 'Area.AreaAdministratives']);
        $this->addBehavior('Excel', [
            'pages' => false
        ]);
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Report.CustomFieldList', [
            'moduleKey' => null,
            'model' => 'Institution.InstitutionSurveys',
            'formKey' => 'survey_form_id',
            'formFilterClass' => null,
            'fieldValueClass' => ['className' => 'Institution.InstitutionSurveyAnswers',
                'foreignKey' => 'institution_survey_id',
                'dependent' => true,
                'cascadeCallbacks' => true],
            'tableCellClass' => ['className' => 'Institution.InstitutionSurveyTableCells',
                'foreignKey' => 'institution_survey_id',
                'dependent' => true,
                'cascadeCallbacks' => true]
        ]);
        $this->addBehavior('Report.InstitutionSecurity');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator = $validator
            ->notEmptyString('academic_period_id')
            ->notEmptyString('area_level_id')
            ->notEmptyString('area_id')
            ->notEmptyString('academic_period_id')
            ->notEmptyString('survey_form_id')
            ->notEmptyString('institution_status')
            ->notEmptyString('institution_id');

        $feature = $this->request->getData($this->getAlias())['feature'];
        $registryAlias = $this->getRegistryAlias();
        if (in_array($feature, ['Report.SurveysReport'])) {
            $validator = $validator
                ->notEmptyString('survey_form_id')
                ->notEmptyString('table_question')
                ->notEmptyString('survey_section');
        } else {
            $validator->notEmptyString('status');
        }
        if (!$feature) {
            $validator = $validator
                ->notEmptyString('feature');
        }
        return $validator;
    }

    public function beforeAction(EventInterface $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('feature', ['select' => false]);
        $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);
        $this->ControllerAction->field('survey_form_id', ['type' => 'hidden']);
        $this->ControllerAction->field('survey_section', ['type' => 'hidden']); //POCOR-6695
        $this->ControllerAction->field('table_question', ['type' => 'hidden']); //POCOR-6695
        $this->ControllerAction->field('area_level_id', ['type' => 'hidden']);
        $this->ControllerAction->field('area_id', ['attr' => ['label' => __('Area Education')]]);
        $this->ControllerAction->field('institution_id', ['type' => 'hidden']);
        $this->ControllerAction->field('institution_status');
        $this->ControllerAction->field('status', ['type' => 'hidden']);
        $this->ControllerAction->field('format');
    }

    //POCOR - 7415 start
    public function addBeforeAction(EventInterface $event)
    {
        $this->ControllerAction->field('area_id',
            ['type' => 'hidden', 'attr' => ['label' => __('Area Name')]]);
    }

    //POCOR - 7415 end
    public function onUpdateFieldInstitutionStatus(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $attr['options'] = $this->controller->getInstitutionStatusOptions($this->getAlias());
            $data = $request->getData($this->getAlias());
            if (!(isset($data['institution_status']))) {
                $options = [
                    'Active' => __('Active'),
                    'Inactive' => __('Inactive'),
                ];
                $attr['type'] = 'select';
                $attr['select'] = false;
                $attr['options'] = $options;
            }
        }
        return $attr;
    }

    public function onUpdateFieldFeature(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $attr['options'] = ['' => '-- ' . __('Select') . ' --', ] // POCOR-9219
                + $this->controller->getFeatureOptions($this->getAlias());
            $attr['select'] = false;
            $attr['onChangeReload'] = true;
            $data = $request->getData($this->getAlias());
            if (!(isset($data['feature']))) {
                $option = $attr['options'];
                reset($option);
                $data['feature'] = key($option);
                $request = $request->withData($this->getAlias(), $data);
            }
            return $attr;
        }
    }

//    public function onExcelAfterHeader(EventInterface $event, ArrayObject $settings)
//    {
//       if ($settings['renderNotComplete'] || $settings['renderNotOpen']) {
//            $fields = $settings['sheet']['fields'];
//            $requestData = json_decode($settings['process']['params']);
//            $surveyFormId = $requestData->survey_form;
//            $academicPeriodId = $requestData->academic_period_id;
//            $institutionStatus = $requestData->institution_status;
//            $institution_id = $requestData->institution_id;
//            $areaId = $requestData->area_id;
//            $condition = [];
//            if ($institution_id != 0) {
//                $condition['Institutions.id'] = $institution_id;
//            }
//            if ($areaId != -1) {
//                $condition['Institutions.area_id'] = $areaId;
//            }
//            $institutionFormStatus = [];
//            if ($institutionStatus == "Active") {
//                $institutionFormStatus = 1;
//            }
//            if ($institutionStatus == "Inactive") {
//                $institutionFormStatus = 2;
//            }
//
//            $surveyFormName = $this->SurveyForms->get($surveyFormId)->name;
//            $academicPeriodName = $this->AcademicPeriods->get($academicPeriodId)->name;
//            $userId = $requestData->user_id;
//            $superAdmin = $requestData->super_admin;
//
//            $SurveyFormsFilters = self::getDynamicTableInstance('Survey.SurveyFormsFilters');
//            $institutionType = $SurveyFormsFilters->find()
//                ->where([
//                    $SurveyFormsFilters->aliasField('survey_form_id').' = '.$surveyFormId,
//                ]);
//                //->select([ 'institution_type_id' => $SurveyFormsFilters->aliasField('survey_filter_id') ]); //POCOR-7442::comment this line
//
//            $InstitutionsTable = $this->Institutions;
//
//            if($settings['renderNotComplete']){
//                $notCompleteRecords = $InstitutionsTable->find()
//                ->where(['NOT EXISTS ('.
//                    $this->find()->where([
//                        $this->aliasField('academic_period_id').' = '.$academicPeriodId,
//                        $this->aliasField('survey_form_id').' = '.$surveyFormId,
//                        $this->aliasField('institution_id').' = '.$InstitutionsTable->aliasField('id')
//                    ])
//                .')'])
//                ->where([
//                            $InstitutionsTable->aliasField('institution_status_id') => $institutionFormStatus,
//                            $condition
//                        ])
//                ->innerJoinWith('Areas')
//                ->leftJoinWith('AreaAdministratives')
//                ->select([
//                    'institution_id' => $InstitutionsTable->aliasField('name'),
//                    'code' => $InstitutionsTable->aliasField('code'),
//                    'area' => 'Areas.name',
//                    'area_administrative' => 'AreaAdministratives.name'
//                ]);
//            if ($institutionType->cleanCopy()->first()->institution_type_id) {
//                $notCompleteRecords->where([
//                    $InstitutionsTable->aliasField('institution_type_id').' IN ('.$institutionType.')'
//                ]);
//            }
//
//            if (!$superAdmin) {
//                $notCompleteRecords->find('ByAccess', ['userId' => $userId]);
//            }
//
//            $writer = $settings['writer'];
//            $sheetName = $settings['sheet']['name'];
//            $mappingArray = ['status_id', 'academic_period_id', 'survey_form_id', 'institution_id', 'code'];
//
//            foreach ($notCompleteRecords->all() as $record) {
//
//                $surveyFormCount = $this->SurveyForms->find()
//                    ->select([
//                        'SurveyForms.id',
//                        'SurveyForms.code',
//                        'SurveyForms.name',
//                        'SurveyStatuses.date_enabled',
//                        'SurveyStatuses.date_disabled',
//                        'SurveyStatusPeriods.academic_period_id',
//                    ])
//                    ->leftJoin(['SurveyStatuses' => 'survey_statuses'], [
//                        'SurveyStatuses.survey_form_id = SurveyForms.id'
//                    ])
//                    ->leftJoin(['SurveyStatusPeriods' => 'survey_status_periods'], [
//                        'SurveyStatusPeriods.survey_status_id = SurveyStatuses.id'
//                    ])
//                    ->where(['SurveyForms.id' => $surveyFormId,
//                        'SurveyStatusPeriods.academic_period_id' => $academicPeriodId,
//                        'DATE(SurveyStatuses.date_disabled) >= ' => date('Y-m-d')
//                        ])
//                    ->count();
//
//                $record->status_id = __('Not Completed');
//
//                if( $surveyFormCount > 0){
//                    $record->status_id = __('Open');
//                }
//                $record->academic_period_id = $academicPeriodName;
//                $record->survey_form_id = $surveyFormName;
//
//                $row = [];
//                foreach ($fields as $field) {
//                    if (in_array($field['field'], $mappingArray)) {
//                        $row[] = __($record->{$field['field']});
//                    } else if ($field['field'] == 'area') {
//                        $row[] = __($record->area);
//                    } else if ($field['field'] == 'institution_statusActive') {
//                        $row[] = __('Active');
//                    }
//                    else if ($field['field'] == 'institution_statusInactive') {
//                        $row[] = __('Inactive');
//                    }
//                    else if ($field['field'] == 'area_administrative') {
//                        $row[] = __($record->area_administrative);
//                    }
//                    else {
//                        $row[] = '';
//                    }
//                }
//                $writer->writeSheetRow($sheetName, $row);
//                }
//            }
//
//           if($settings['renderNotOpen']){
//            $notOpenRecords = $InstitutionsTable->find()
//                ->where(['EXISTS ('.
//                    $this->find()->where([
//                        $this->aliasField('academic_period_id').' = '.$academicPeriodId,
//                        $this->aliasField('survey_form_id').' = '.$surveyFormId,
//                        $this->aliasField('institution_id').' = '.$InstitutionsTable->aliasField('id'),
//                        $this->aliasField('status_id').' IN ('.self::SURVEY_DISABLED.','.self::OPEN.','.self::PENDINGAPPROVAL.')'
//                    ])
//                .')'])
//                ->where([
//                            $InstitutionsTable->aliasField('institution_status_id') => $institutionFormStatus,
//                            $condition
//                        ])
//                ->innerJoinWith('Areas')
//                ->leftJoinWith('AreaAdministratives')
//                ->select([
//                    'institution_id' => $InstitutionsTable->aliasField('name'),
//                    'institutionId' => $InstitutionsTable->aliasField('id'),
//                    'code' => $InstitutionsTable->aliasField('code'),
//                    'area' => 'Areas.name',
//                    'area_administrative' => 'AreaAdministratives.name'
//                ]);
//            if ($institutionType->cleanCopy()->first()->institution_type_id) {
//                $notOpenRecords->where([
//                    $InstitutionsTable->aliasField('institution_type_id').' IN ('.$institutionType.')'
//                ]);
//            }
//
//            if (!$superAdmin) {
//                $notOpenRecords->find('ByAccess', ['userId' => $userId]);
//            }
//
//            $writer = $settings['writer'];
//            $sheetName = $settings['sheet']['name'];
//            $mappingArray = ['status_id', 'academic_period_id', 'survey_form_id', 'institution_id', 'code'];
//
//            foreach ($notOpenRecords->all() as $record) {
//                $record->academic_period_id = $academicPeriodName;
//                $record->survey_form_id = $surveyFormName;
//
//                $countDisabledSurveyInstitution = $this->find('list',[
//                'keyField' => 'institution_id', 'valueField' => 'status_id',
//                ])->where([
//                    $this->aliasField('academic_period_id') .' = '. $academicPeriodId,
//                    $this->aliasField('survey_form_id') .' = '. $surveyFormId,
//                    $this->aliasField('institution_id') .' = '. $record->institutionId,
//                    $this->aliasField('status_id') .' = '. self::SURVEY_DISABLED
//                ])->count();
//
//                if($countDisabledSurveyInstitution > 0){
//                    $record->status_id = __('Not Completed');
//                }else{
//                    $record->status_id = __('Open');
//                }
//
//                $row = [];
//                foreach ($fields as $field) {
//                    if (in_array($field['field'], $mappingArray)) {
//                        $row[] = __($record->{$field['field']});
//                    } else if ($field['field'] == 'area') {
//                        $row[] = __($record->area);
//                    }else if ($field['field'] == 'institution_statusActive') {
//                        $row[] = __('Active');
//                    }
//                    else if ($field['field'] == 'institution_statusInactive') {
//                        $row[] = __('Inactive');
//                    }
//                    else if ($field['field'] == 'area_administrative') {
//                        $row[] = __($record->area_administrative);
//                    } else {
//                        $row[] = '';
//                    }
//                }
//                $writer->writeSheetRow($sheetName, $row);
//             }
//            }
//            $settings['renderNotComplete'] = false;
//            $settings['renderNotOpen'] = false;
//        }
//    }
//
    public function onExcelBeforeStart(EventInterface $event, ArrayObject $settings, ArrayObject $sheets)
    {
//        $sheets[] = [
//            'name' => $this->getAlias(),
//            'table' => $this,
//            'query' => $this->find(),
//            'orientation' => 'landscape'
//        ];

        $conditions = [];
        $requestData = json_decode($settings['process']['params']);

        $conditions = $this->conditionsWithInstitutionAndArea($conditions, $requestData);
        $conditions = $this->conditionsWithSurveyStatus($conditions, $requestData);
        $conditions = $this->conditionsWithSurveyFormId($conditions, $requestData);
        $conditions = $this->conditionsWithInstitutionStatus($conditions, $requestData);
        $conditions = $this->conditionsWithAcademicPeriod($conditions, $requestData);
        $surveyFormId = $requestData->survey_form_id;
        $this->setCondition($conditions);
        $forms = $this->getForms($surveyFormId);
//        Log::debug(print_r([__FUNCTION__ . '1' => $sheets],true));
        foreach ($forms as $formId => $formName) {
            $this->excelContent($sheets, $formName, null, $formId);
        }
    }
//    public function onExcelBeforeStart(EventInterface $event, ArrayObject $settings, ArrayObject $sheets)
//    {
//        // Setting request data and modifying fetch condition
//        $requestData = json_decode($settings['process']['params']);
//        $surveyFormId = $requestData->survey_form;
//        $academicPeriodId = $requestData->academic_period_id;
//        $status = $requestData->status;
//        $institutionStatus = $requestData->institution_status;
//        $areaId = $requestData->area_id;
//
//        $WorkflowStatusesTable = self::getDynamicTableInstance('Workflow.WorkflowStatuses');
//
//        if (!empty($academicPeriodId) && empty($areaId)) { //POCOR-7046
//            $surveyStatuses = $WorkflowStatusesTable->WorkflowModels->getWorkflowStatusesCode('Institution.InstitutionSurveys');
//            if($status == '' || $status == 'all'){
//                  $settings['renderNotOpen'] = true;
//                  $settings['renderNotComplete'] = true;
//            } elseif ($surveyStatuses[$status] == 'Open') {
//                  $settings['renderNotOpen'] = true;
//                  $settings['renderNotComplete'] = false;
//            } elseif (  !$status || $surveyStatuses[$status] == 'NOT_COMPLETED') {
//                  $settings['renderNotOpen'] = false;
//                  $settings['renderNotComplete'] = true;
//            } else {
//                  $settings['renderNotOpen'] = false;
//                  $settings['renderNotComplete'] = false;
//            }
//        }//Start POCOR-7046
//        else if (!empty($areaId)) {
//            if($status == '' || $status == 'all'){
//                  $settings['renderNotOpen'] = false;
//                  $settings['renderNotComplete'] = true;
//            } elseif ($surveyStatuses[$status] == 'Open') {
//                  $settings['renderNotOpen'] = true;
//                  $settings['renderNotComplete'] = false;
//            } elseif (  !$status || $surveyStatuses[$status] == 'NOT_COMPLETED') {
//                  $settings['renderNotOpen'] = false;
//                  $settings['renderNotComplete'] = true;
//            } else {
//                  $settings['renderNotOpen'] = false;
//                  $settings['renderNotComplete'] = false;
//            }
//        }//End POCOR-7046
//        else {
//            $academicPeriodId = 0;
//        }
//
//        $configCondition = $this->getCondition();
//        $condition = [
//            $this->aliasField('academic_period_id') => $academicPeriodId
//        ];
//
//        if (!$status) $status = array_keys($surveyStatuses);
//
//        $surveyStatuses = $WorkflowStatusesTable->getWorkflowSteps($status);
//
//        $this->surveyStatuses = $WorkflowStatusesTable->getWorkflowStepStatusNameMappings('Institution.InstitutionSurveys');
//        if (!empty($surveyStatuses) || $status == '' || $status == 'all') {
//            /*POCOR-6600 - removed all conditions, as report must have all status ids when all option selected*/
//            // if($settings['renderNotComplete'] === true && $settings['renderNotOpen'] === true){
//            //     $statusCondition = [
//            //         $this->aliasField('status_id').' IN ('.self::OPEN.')'
//            //     ];
//            // }elseif($settings['renderNotComplete'] === true && $settings['renderNotOpen'] === false){
//            //     $statusCondition = [
//            //         $this->aliasField('status_id').' NOT IN ('.self::OPEN.', '.self::PENDINGAPPROVAL.', '.self::COMPLETED.' )'
//            //     ];
//            // }else{
//            //     $statusCondition = [
//            //         $this->aliasField('status_id').' IN ' => array_keys($surveyStatuses)
//            //     ];
//            // }
//            $statusCondition = [];
//            $condition = array_merge($condition, $statusCondition);
//        }
//        $condition = array_merge($condition, $configCondition);
//        $this->setCondition($condition);
//        // For Surveys only
//        $forms = $this->getForms($surveyFormId);
//        foreach ($forms as $formId => $formName) {
//            $this->excelContent($sheets, $formName, null, $formId);
//        }
//        // Stop the customfieldlist behavior onExcelBeforeStart function
//        $event->stopPropagation();
//    }

    /**
     * @param array $conditions
     * @param mixed $requestData
     * @return array
     */
    private function conditionsWithInstitutionAndArea(array $conditions, mixed $requestData): array
    {
        $institutions = self::getDynamicTableInstance('Institution.Institutions');
        $institutionID = $requestData->institution_id;

        if ($institutionID > 0) {
            $conditions['Institutions.id'] = $institutionID;
        } else {
            $areaId = $requestData->area_id;
            $selectedArea = $requestData->area_id;
            if ($areaId != -1 && $areaId != '' && $areaId != 0) {
                $areaIds = [];
                $allgetArea = $this->getChildren($selectedArea, $areaIds);
                $selectedArea1[] = $selectedArea;
                if (!empty($allgetArea)) {
                    $allselectedAreas = array_merge($selectedArea1, $allgetArea);
                } else {
                    $allselectedAreas = $selectedArea1;
                }

                $conditions[$institutions->aliasField('area_id IN')] = $allselectedAreas;
            }
        }
        return $conditions;
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

public function getChildren($id, $idArray)
    {
        $Areas = self::getDynamicTableInstance('Area.Areas');
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

    /**
     * @param array $conditions
     * @param mixed $requestData
     * @return array
     */
    private function conditionsWithSurveyStatus(array $conditions, mixed $requestData): array
    {
        $status = $requestData->status;

        if (!empty($status) && $status != "all") {
            $WorkflowModels = self::getDynamicTableInstance('Workflow.WorkflowModels');
            $WorkflowSteps = self::getDynamicTableInstance('Workflow.WorkflowSteps');
            $WorkflowStatuses = self::getDynamicTableInstance('Workflow.WorkflowStatuses');
            $WorkflowStatusesSteps = self::getDynamicTableInstance('Workflow.WorkflowStatusesSteps');
            $statusData = $this->find()->select([
                "status_id" => $this->aliasField('status_id'),
                "status_name" => $WorkflowSteps->aliasField('name')
            ])
                ->innerJoin([$WorkflowSteps->getAlias() => $WorkflowSteps->getTable()], [
                    $WorkflowSteps->aliasField('id=') . $this->aliasField('status_id')
                ])
                ->innerJoin([$WorkflowStatusesSteps->getAlias() => $WorkflowStatusesSteps->getTable()], [
                    $WorkflowStatusesSteps->aliasField('workflow_step_id=') . $WorkflowSteps->aliasField('id')
                ])
                ->innerJoin([$WorkflowStatuses->getAlias() => $WorkflowStatuses->getTable()], [
                    $WorkflowStatuses->aliasField('id=') . $WorkflowStatusesSteps->aliasField('workflow_status_id')
                ])
                ->innerJoin([$WorkflowModels->getAlias() => $WorkflowModels->getTable()], [
                    $WorkflowModels->aliasField('id=') . $WorkflowStatuses->aliasField('workflow_model_id')
                ])->where([
                    $WorkflowModels->aliasField('name') => "Institutions > Survey > Forms",
                    $WorkflowStatuses->aliasField('id') => $status
                ])->group($this->aliasField('status_id'))
                ->toArray();

            foreach ($statusData as $key => $value) {
                $statusList[] = $value['status_id'];
            }
            $statusCondition = [
                $this->aliasField('status_id') . ' IN ' => array_values($statusList)
            ];
            $conditions = array_merge($conditions, $statusCondition);
        }
        return $conditions;
        //POCOR-7821 end
    }

    /**
     * @param array $conditions
     * @param mixed $requestData
     * @return array
     */
    private function conditionsWithSurveyFormId(array $conditions, mixed $requestData): array
    {
        $surveyFormId = $requestData->survey_form_id;
        if (!empty($surveyFormId)) {
            $conditions['SurveyForms.id'] = $surveyFormId;
        }
        return $conditions;
        //POCOR-7821 end
    }

    /**
     * @param array $conditions
     * @param mixed $requestData
     * @return array
     */
    private function conditionsWithInstitutionStatus(array $conditions, mixed $requestData): array
    {
        $institutionStatus = $requestData->institution_status;
        if ($institutionStatus) {
            $conditions['Statuses.name'] = $institutionStatus;
        }
        return $conditions;
    }

    /**
     * @param array $conditions
     * @param mixed $requestData
     * @return array
     */
    private function conditionsWithAcademicPeriod(array $conditions, mixed $requestData): array
    {
        $academicPeriodId = $requestData->academic_period_id;
        if ($academicPeriodId) {
            $conditions[$this->aliasField('academic_period_id')] = $academicPeriodId;
        }
        return $conditions;
    }

    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, $query)
    {

        $conditions = $this->getCondition();
        $query->select([
            'code' => 'Institutions.code',
            'area' => 'Areas.name',
            'area_administrative' => 'AreaAdministratives.name',
            'Statuses_name' => 'Statuses.name'
        ])
            ->innerJoin(['SurveyForms' => 'survey_forms'], [
                'Surveys.id = SurveyForms.id'
            ])
            ->leftJoin(['SurveyFormsFilters' => 'survey_forms_filters'], [
                'SurveyFormsFilters.survey_form_id = SurveyForms.id'
            ])
            ->contain([
                'Institutions.Areas',
                'Institutions.AreaAdministratives',
                'Institutions.Statuses'
            ]);
        $query->where([$conditions])
            ->group(['Surveys.id']); //POCOR-8226 added group by to avoid duplicates
//        $debug = false;
//        if ($debug) {
//            // Clone the query to avoid mutating the original one
//            $clonedQuery = clone $query;
//
//            // Get SQL for debugging
//            $sql = $clonedQuery->sql();
//
//            // Execute and dump results (optional: limit rows to avoid big output)
//            $results = $clonedQuery->limit(10)->toArray();
//
//            // Use CakePHP's built-in debug tools
//            Log::debug("SQL: " . $sql);
//            Log::debug(print_r(["Preview Results" => $results], true));
//        }
    }

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, ArrayObject $fields)
    {
        foreach ($fields as $key => $field) {
            if ($field['field'] == 'institution_id') {
                unset($fields[$key]);
                break;
            }
        }

        $fields[] = [
            'key' => 'Institutions.code',
            'field' => 'code',
            'type' => 'string',
            'label' => '',
        ];
        $fields[] = [
            'key' => 'InstitutionSurveys.institution_id',
            'field' => 'institution_id',
            'type' => 'integer',
            'label' => '',
        ];
        $fields[] = [
            'key' => 'Institutions.area_id',
            'field' => 'area',
            'type' => 'string',
            'label' => '',
        ];
        $fields[] = [
            'key' => 'Institutions.area_administrative_id',
            'field' => 'area_administrative',
            'type' => 'string',
            'label' => '',
        ];
        $fields[] = [
            'key' => 'Statuses_name',
            'field' => 'Statuses_name',
            'type' => 'string',
            'label' => __('Institution Status')
        ];
//        $requestData = json_decode($settings['process']['params']);
//
//        $surveyForm = $requestData->survey_form;
//
//        $SurveyQuestions = self::getDynamicTableInstance('Survey.SurveyQuestions');
//        $SurveyTblColumns = self::getDynamicTableInstance('Survey.SurveyTableColumns');
//        $surveyFormsQuestion = self::getDynamicTableInstance('Survey.SurveyFormsQuestions');
//        $SurveyTblColumnRes = $SurveyTblColumns
//            ->find()
//            ->select([
//                'survey_table_id' => $SurveyQuestions->aliasField('id'),
//                'survey_table_name' => $SurveyQuestions->aliasField('name'),
//                'survey_column_id' => $SurveyTblColumns->aliasField('id'),
//                'survey_column_name' => $SurveyTblColumns->aliasField('name'),
//                'survey_column_order' => $SurveyTblColumns->aliasField('order')
//            ])
//            ->innerJoin([$surveyFormsQuestion->getAlias() => $surveyFormsQuestion->getTable()],
//                [
//                    $surveyFormsQuestion->aliasField('survey_question_id') . ' = '. $SurveyTblColumns->aliasField('survey_question_id')
//                ])
//            ->innerJoin([$SurveyQuestions->getAlias() => $SurveyQuestions->getTable()],
//                [
//                    $SurveyQuestions->aliasField('id') . ' = '. $surveyFormsQuestion->aliasField('survey_question_id')
//                ])
//            ->where([$surveyFormsQuestion->aliasField('survey_form_id') => $surveyForm])
////            ->order([$SurveyQuestions->aliasField('id') => 'ASC'])
//            ->toArray();
//        Log::debug(print_r($SurveyTblColumnRes, true));
//        if(!empty($SurveyTblColumnRes)){
//            $table_name = "";
//            foreach ($SurveyTblColumnRes as $S_key => $S_val) {
//                $new_table_name = $S_val->survey_table_name;
//                if($table_name != $new_table_name){
//                    $table_name = $new_table_name;
//                    $fields[] = [
//                        'key' => 'survey_table_name',
//                        'field' =>'survey_table_name',
//                        'type' => 'string',
//                        'label' => __('Question')
//                    ];
//                }
//                if($S_val->survey_column_order == 1){
//                    $fields[] = [
//                        'key' => 'question_row',
//                        'field' =>'question_row',
//                        'type' => 'string',
//                        'label' => $S_val->survey_column_name
//                    ];
//                }else{
//                    $fields[] = [
//                        'key' => '',
//                        'field' => "'".$S_val->survey_column_name."'",
//                        'type' => 'string',
//                        'label' => $S_val->survey_column_name
//                    ];
//                }
//            }
//        }
    }

    //POCOR-8515 starts

    public function onUpdateFieldSurveyFormId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $data = $this->request->getData($this->getAlias());
            if (isset($data['feature'])) {
                $feature = $data['feature'];
                $academicPeriodId = $data['academic_period_id'];
                $todayDate = date('Y-m-d');
                $todayTimestamp = date('Y-m-d H:i:s', strtotime($todayDate));
                if ($feature == $this->getRegistryAlias() || $feature == 'Report.SurveysReport') {
                    // $SurveyStatusTable = $this->SurveyForms->surveyStatuses;
                    $SurveyStatusTable = TableRegistry::getTableLocator()->get('Survey.SurveyStatuses');
                    $surveyFormOptions = $this->SurveyForms
                        ->find('list')
                        ->leftJoin([$SurveyStatusTable->getAlias() => $SurveyStatusTable->getTable()], [
                            $SurveyStatusTable->aliasField('survey_form_id = ') . $this->SurveyForms->aliasField('id'),
                        ])
                        ->leftJoin([$SurveyStatusTable->SurveyStatusPeriods->getAlias() => $SurveyStatusTable->SurveyStatusPeriods->getTable()], [
                            $SurveyStatusTable->SurveyStatusPeriods->aliasField('survey_status_id = ') . $SurveyStatusTable->aliasField('id'),
                        ])
                        ->where([
                            $SurveyStatusTable->SurveyStatusPeriods->aliasField('academic_period_id') => $academicPeriodId,
                            // $SurveyStatusTable->aliasField('date_enabled <=') => $todayTimestamp,
                            // $SurveyStatusTable->aliasField('date_disabled >=') => $todayTimestamp
                            //POCOR-7022
                        ])->toArray();
                    if (!empty($surveyFormOptions)) {
                        $attr['options'] = ['' => '-- ' . __('Select') . ' --'] + $surveyFormOptions; //POCOR-9207
                        $attr['onChangeReload'] = true;
                        $attr['type'] = 'select';
                        $attr['select'] = false;

                    } else {
                        $surveyFormOptions = ['' => $this->getMessage('general.select.noOptions')];
                        $attr['type'] = 'select';
                        $attr['options'] = ['' => '-- ' . __('Select') . ' --'] + $surveyFormOptions; //POCOR-9207
                        $attr['attr']['required'] = true;
                    }


                    if (!(isset($data['survey_form_id']))) {
                        $option = $attr['options'];
                        reset($option);
                        $data['survey_form_id'] = key($option);
                        $attr['attr']['select'] = false;
                        $attr['select'] = false;
                        $request = $request->withData($this->getAlias(), $data);
                    }
//                    dd($attr);

                    return $attr;
                }
            }
        }
    }//POCOR-8515 ends

    public function onUpdateFieldAcademicPeriodId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $feature = $this->request->getData($this->getAlias())['feature'];
            $surveyForm = $this->request->getData($this->getAlias())['survey_form_id'];
            // $SurveyStatusTable = $this->SurveyForms->surveyStatuses;
            $SurveyStatusTable = self::getDynamicTableInstance('Survey.SurveyStatuses');
            $academicPeriodOptions = $SurveyStatusTable
                ->find('list', [
                    'keyField' => 'academic_id',
                    'valueField' => 'academic_name'
                ])
                ->matching('AcademicPeriods')
                ->select(['academic_id' => 'AcademicPeriods.id', 'academic_name' => 'AcademicPeriods.name'])
                ->order(['AcademicPeriods.order'])
                ->toArray();
            $attr['options'] = $academicPeriodOptions;
            $attr['onChangeReload'] = true;
            $attr['type'] = 'select';
            $attr['select'] = false;
            $data = $request->getData($this->getAlias());
            if (!(isset($data['academic_period_id']))) {
                $option = $attr['options'];
                reset($option);
                $data['academic_period_id'] = key($option);
                $request = $request->withData($this->getAlias(), $data);
            }
            return $attr;
        }
    }

    //POCOR-6695 Starts

    public function onUpdateFieldStatus(EventInterface $event, array $attr, $action, ServerRequest $request)
    {

        if ($action == 'add') {
            $data = $this->request->getData($this->getAlias());
            if (isset($data['feature'])
                && isset($data['academic_period_id'])) {
                $feature = $data['feature'];

                if ($feature == $this->getRegistryAlias()) {
                    $surveyStatuses = $this->Workflow->getWorkflowStatuses('Institution.InstitutionSurveys');
                    $attr['type'] = 'select';
                    $arrAll = array("all" => "All");
                    $collectionData = new Collection($surveyStatuses);
                    $attr['options'] = $collectionData->prepend($arrAll)->toArray();
                    $attr['select'] = false;
                    if (!(isset($data['status']))) {
                        $option = $attr['options'];
                        reset($option);
                        $data['status'] = key($option);
                        $request = $request->withData($this->getAlias(), $data);
                    }
                    return $attr;
                }
            }
        }
    }//End of POCOR-6695

    //POCOR-6695 Starts

    public function onExcelGetStatusId(EventInterface $event, Entity $entity)
    {
        $status = $entity->status_id;
        if ($status == 1 || $status == -1) {
            return "Open";
        }
        if ($status == 2) {
            return "PENDINGAPPROVAL";
        }
        if ($status == 3) {
            return "COMPLETED";
        }
    }//End of POCOR-6695

    public function onUpdateFieldAreaLevelId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (!isset($request->getData($this->getAlias())['feature'])) {
            return $attr;
        }
        if ($action == 'add') {
            $AreaLevels = self::getDynamicTableInstance('Area.AreaLevels');
            $areaOptions = $AreaLevels
                ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
                ->order([$AreaLevels->aliasField('level')]);

            $attr['type'] = 'chosenSelect';
            $attr['attr']['multiple'] = false;
            $attr['select'] = false;
            $attr['options'] = ['-1' => __('All Areas Level')] + $areaOptions->toArray();
            $attr['onChangeReload'] = true;
        } else {
            $attr['type'] = 'hidden';
        }

        return $attr;
    }

    public function onUpdateFieldInstitutionId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        //POCOR-8515 Starts
        $selectedArea = $request->getData($this->getAlias())['area_id'];
        $areaIds = [];
        $selectedArea1[] = $selectedArea;
        if (!empty($selectedArea)) {
            $allgetArea = $this->getChildren($selectedArea, $areaIds);
            if (!empty($allgetArea)) {
                $areaId = array_merge($selectedArea1, $allgetArea);
            } else {
                $areaId = $selectedArea1;
            }
        } else {
            $areaId = $selectedArea1;
        }//POCOR-8515 ends

        $InstitutionsTable = TableRegistry::getTableLocator()->get('Institution.Institutions');
        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];
            $institutionList = [];
            if (array_key_exists('area_id', $request->getData($this->getAlias())) && !empty($request->getData($this->getAlias())['area_id']) && $areaId != -1) {
                $institutionQuery = $InstitutionsTable
                    ->find('list', [
                        'keyField' => 'id',
                        'valueField' => 'code_name'
                    ])
                    ->where([
                        $InstitutionsTable->aliasField('area_id IN') => $areaId//POCOR-8515
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
                    ->find()
                    ->select([
                        'id' => $InstitutionsTable->aliasField('id'),
                        'code' => $InstitutionsTable->aliasField('code'),
                        'name' => $InstitutionsTable->aliasField('name')
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
                foreach ($institutionList AS $institutionListData) {
                    $institutionListArr[] = array($institutionListData['id'] => $institutionListData['code'] . ' - ' . $institutionListData['name']);
                }
                $institutionList = $this->array_flatten($institutionListArr);
            }
            if (empty($institutionList)) {
                $institutionOptions = ['' => $this->getMessage('general.select.noOptions')];
                $attr['type'] = 'select';
                $attr['options'] = $institutionOptions;
                $attr['attr']['required'] = true;
            } else {
                if (in_array($feature, ['Report.Surveys', 'Report.SurveysReport']) && count($institutionList) > 1) { //POCOR-6695 add condition 'Report.SurveysReport'
                    // POCOR-6440 starts
                    if (array_key_exists('area_id', $request->getData($this->getAlias())) && !empty($request->getData($this->getAlias())['area_id']) && $areaId != -1) {
                        $institutionQuery = $InstitutionsTable
                            ->find('list', [
                                'keyField' => 'id',
                                'valueField' => 'code_name'
                            ])
                            ->where([
                                $InstitutionsTable->aliasField('area_id IN') => $areaId//POCOR-8515
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
                    // POCOR-6440 end
                    $institutionOptions = ['0' => __('All Institutions')] + $institutionList;
                } else {
                    $institutionOptions = $institutionList;
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

    function array_flatten($array)
    {
        if (!is_array($array)) {
            return false;
        }
        $result = array();
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = array_merge($result, $this->array_flatten($value));
            } else {
                $result = array_merge($result, array($key => $value));
            }
        }
        return $result;
    }

    public function onUpdateFieldAreaId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];
            $areaLevelId = $this->request->getData($this->getAlias())['area_level_id'];//POCOR-8515
            $Areas = TableRegistry::getTableLocator()->get('Area.Areas');
            //POCOR-8515 starts
            if ($areaLevelId != -1 && !empty($areaLevelId)) {
                $where[$Areas->aliasField('area_level_id')] = $areaLevelId;
            }//POCOR-8515 ends
            $entity = $attr['entity'];
            if ($action == 'add') {
                $areas = $Areas
                    ->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
                    ->where([$where])//POCOR-8515
                    ->order([$Areas->aliasField('order')]);
                $areaOptions = $areas->toArray();//POCOR-8515
                $attr['type'] = 'chosenSelect';
                $attr['attr']['multiple'] = false;
                $attr['select'] = false;
                //POCOR-8515 starts
                //$attr['options'] = ['' => '-- ' . __('Select') . ' --', '0' => __('All Areas')] + $areaOptions->toArray();
                if (count($areaOptions) > 1) {
                    $attr['options'] = ['0' => __('All Areas')] + $areaOptions;
                } else {
                    $attr['options'] = $areaOptions;
                }//POCOR-8515 ends
                $attr['onChangeReload'] = true;

            } else {
                $attr['type'] = 'hidden';
            }
        }
        return $attr;
    }

public function onUpdateFieldSurveySection(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $surveyForm = $this->request->getData($this->getAlias())['survey_form_id'];
        if ($action == 'add') {
            if (isset($this->request->getData($this->getAlias())['feature'])) {
                $feature = $this->request->getData($this->getAlias())['feature'];
                $academicPeriodId = $this->request->getData('Surveys')['academic_period_id'];
                $surveyFormId = $this->request->getData('Surveys')['survey_form_id'];
                $todayDate = date('Y-m-d');
                $todayTimestamp = date('Y-m-d H:i:s', strtotime($todayDate));
                if ($feature == 'Report.SurveysReport') {
                    $SurveyStatusTable = TableRegistry::getTableLocator()->get('Survey.SurveyStatuses');
                    $surveyQuestions = self::getDynamicTableInstance('FieldOption.IdentityTypes');
                    $surveySection = self::getDynamicTableInstance('Survey.SurveyFormsQuestions');
                    $surveyFormOptions = $surveySection
                        ->find('list', ['keyField' => 'id', 'valueField' => 'section'])
                        ->where([
                            $surveySection->aliasField('survey_form_id') => $surveyForm
                        ])
                        ->distinct(['section'])
                        ->order([$surveySection->aliasField('section')])
                        ->toArray();
                    if (!empty($surveyFormOptions)) {
                        $attr['options'] = $surveyFormOptions;
                        $attr['onChangeReload'] = true;
                        $attr['type'] = 'select';
                    } else {
                        $surveyFormOptions = ['' => $this->getMessage('general.select.noOptions')];
                        $attr['type'] = 'select';
                        $attr['options'] = $surveyFormOptions;
                        $attr['attr']['required'] = true;
                    }

                    if (empty($this->request->getData($this->getAlias())['survey_section'])) {
                        $option = $attr['options'];
                        reset($option);
                        $this->request->getData($this->getAlias())['survey_section'] = key($option);
                    }
                    return $attr;
                }
            }
        }
    }

public function onUpdateFieldTableQuestion(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $surveyQuestionId = $this->request->getData($this->getAlias())['survey_section'] ?? '';
        $surveyFormId = $this->request->getData($this->getAlias())['survey_form_id'] ?? -1;
        $surveySectionQuestions = TableRegistry::getTableLocator()->get('Survey.SurveyFormsQuestions')
            ->find('all', ['conditions' => ['id' => $surveyQuestionId]])
            ->first();
        if (!empty($surveySectionQuestions)) {
            $surveySectionQuestionName = $surveySectionQuestions->section;
        } else {
            $surveySectionQuestionName = '';
        }
        if ($action == 'add') {
            if (isset($this->request->getData($this->getAlias())['feature'])) {
                $feature = $this->request->getData($this->getAlias())['feature'];
                $academicPeriodId = $this->request->getData('Surveys')['academic_period_id'];
                $todayDate = date('Y-m-d');
                $todayTimestamp = date('Y-m-d H:i:s', strtotime($todayDate));
                if ($feature == 'Report.SurveysReport') {
                    $SurveyStatusTable = self::getDynamicTableInstance('Survey.SurveyStatuses');
                    $surveyFormQuestions = self::getDynamicTableInstance('Survey.SurveyFormsQuestions');
                    $surveyFormOptions = $surveyFormQuestions
                        ->find('list', ['keyField' => 'survey_question_id', 'valueField' => 'name'])
                        ->matching('CustomFields', function ($q) {
                            return $q->where(['CustomFields.field_type IN'  => ['REPEATER', 'STAFF_LIST', 'STUDENT_LIST']]);
                        })
                        ->where([
                        $surveyFormQuestions->aliasField('section') =>
                            $surveySectionQuestionName,
                        $surveyFormQuestions->aliasField('survey_form_id') =>
                            $surveyFormId
                        ])
                        ->toArray();
                    if (!empty($surveyFormOptions)) {
                        $attr['options'] = $surveyFormOptions;
                        $attr['onChangeReload'] = true;
                        $attr['type'] = 'select';
                    } else {
                        $surveyFormOptions = ['' => $this->getMessage('general.select.noOptions')];
                        $attr['type'] = 'select';
                        $attr['options'] = $surveyFormOptions;
                        $attr['attr']['required'] = true;
                    }

                    if (empty($this->request->getData($this->getAlias())['survey_questions'])) {
                        $option = $attr['options'];
                        reset($option);
                        $this->request->getData($this->getAlias())['survey_questions'] = key($option);
                    }
                    return $attr;
                }
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
            case 'area_level_id':
                return __('Area Level');
            case 'survey_form_id':
                return __('Survey Form');
            case 'institution_id':
                return __('Institution');
            case 'institution_status':
                return __('Institution Status');
            case 'survey_section':
                return __('Survey Section');
            case 'table_question':
                return __('Repeater Question');
            case 'status':
                return __('Survey Status');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}
