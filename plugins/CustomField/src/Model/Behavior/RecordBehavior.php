<?php
namespace CustomField\Model\Behavior;

use ArrayObject;
use Exception;

use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Utility\Inflector;
use Cake\ORM\Table;
use Cake\Log\Log;
use Cake\I18n\Time;
use Cake\I18n\Date;
use Cake\Datasource\ConnectionManager;
use Cake\Http\ServerRequest;
use Cake\Utility\Text;

class RecordBehavior extends Behavior
{
    protected $_defaultConfig = [
        'events' => [
            'ControllerAction.Model.afterAction'            => ['callable' => 'afterAction', 'priority' => 1],
            'ControllerAction.Model.add.onInitialize'       => ['callable' => 'addOnInitialize', 'priority' => 100],
            'ControllerAction.Model.edit.onInitialize'      => ['callable' => 'editOnInitialize', 'priority' => 100],
            'ControllerAction.Model.viewEdit.beforeQuery'   => ['callable' => 'viewEditBeforeQuery', 'priority' => 100],
            'ControllerAction.Model.view.afterAction'       => ['callable' => 'viewAfterAction', 'priority' => 100],
            'ControllerAction.Model.addEdit.beforePatch'    => ['callable' => 'addEditBeforePatch', 'priority' => 100],
            'ControllerAction.Model.addEdit.afterAction'    => ['callable' => 'addEditAfterAction', 'priority' => 100],
            'ControllerAction.Model.add.beforeSave'         => ['callable' => 'addBeforeSave', 'priority' => 100],
            'ControllerAction.Model.edit.afterQuery'        => ['callable' => 'editAfterQuery', 'priority' => 100],
            'ControllerAction.Model.edit.beforeSave'        => ['callable' => 'editBeforeSave', 'priority' => 100],
            'Model.custom.onUpdateToolbarButtons'           => 'onUpdateToolbarButtons',
            'Model.excel.onExcelUpdateFields'               => ['callable' => 'onExcelUpdateFields', 'priority' => 110],
            'Model.excel.onExcelBeforeStart'                => 'onExcelBeforeStart',
            'Model.excel.onExcelRenderCustomField'          => 'onExcelRenderCustomField'
        ],
        'model' => null,
        'behavior' => null,
        'tabSection' => false,
        'moduleKey' => 'custom_module_id',
        'fieldKey' => 'custom_field_id',
        'fieldOptionKey' => 'custom_field_option_id',
        'tableColumnKey' => 'custom_table_column_id',
        'tableRowKey' => 'custom_table_row_id',
        'fieldClass' => ['className' => 'CustomField.CustomFields'],
        'formKey' => 'custom_form_id',
        'filterKey' => 'custom_filter_id',
        'formClass' => ['className' => 'CustomField.CustomForms'],
        'formFieldClass' => ['className' => 'CustomField.CustomFormsFields'],
        'formFilterClass' => ['className' => 'CustomField.CustomFormsFilters'],
        'recordKey' => 'custom_record_id',
        'fieldValueClass' => ['className' => 'CustomField.CustomFieldValues', 'foreignKey' => 'custom_record_id', 'dependent' => true, 'cascadeCallbacks' => true],
        'tableCellClass' => ['className' => 'CustomField.CustomTableCells', 'foreignKey' => 'custom_record_id', 'dependent' => true, 'cascadeCallbacks' => true]
    ];

    // value for these field types will be saved on custom_field_values
    private $fieldValueArray = ['TEXT', 'NUMBER', 'DECIMAL', 'TEXTAREA', 'DROPDOWN', 'CHECKBOX', 'DATE', 'TIME', 'COORDINATES', 'FILE', 'NOTE'];

    private $CustomFieldValues = null;
    private $CustomTableCells = null;

    private $CustomModules = null;
    private $CustomFieldTypes = null;

    private $CustomFields = null;
    private $CustomForms = null;
    private $CustomFormsFields = null;
    private $CustomFormsFilters = null;

    // Use for excel only
    private $_fieldValues = [];
    private $_customFieldOptions = [];
    private $_tableCellValues = [];

    public function initialize(array $config): void
    {
        // POCOR-8917 start
        parent::initialize($config);
        $model = $this->_table;
//        dd($model);
        if (is_null($this->getConfig('moduleKey'))) {
            $model->belongsTo('CustomForms', $this->getConfig('formClass'));
        }



// Check if the incorrect association exists
        $needToReplaceAssociation = false;
        if ($model->getAlias() == 'StudentUser') {
            $associationName = 'CustomFieldValues';
//            dd($model->associations());
            if ($model->hasAssociation($associationName)) {
                $association = $model->getAssociation($associationName);

                if ($association->getClassName() != 'StudentCustomField.StudentCustomFieldValues') {
                    $needToReplaceAssociation = true;
                }
            }
// Now add the correct association
        }

        if ($model->getAlias() == 'StudentAdmission') {

            $associationName = 'CustomFieldValues';
//            dd($model->associations());
            if ($model->hasAssociation($associationName)) {
                $association = $model->getAssociation($associationName);
//                dd($association->getClassName());
                if ($association->getClassName() != 'StudentCustomField.StudentAdmissionCustomFieldValues') {
                    $needToReplaceAssociation = true;
                }
            }
// Now add the correct association
        }
        if($needToReplaceAssociation){
            $model->associations()->remove($associationName);
        }

        $model->hasMany('CustomFieldValues', $this->getConfig('fieldValueClass'));

        $this->CustomFieldValues = $model->CustomFieldValues;
        if (!is_null($this->getConfig('tableCellClass'))) {
            $model->hasMany('CustomTableCells', $this->getConfig('tableCellClass'));
            $this->CustomTableCells = $model->CustomTableCells;
        }
        $this->firstTabName = null;
        $this->CustomModules = TableRegistry::get('CustomField.CustomModules');
        $this->CustomFieldTypes = TableRegistry::get('CustomField.CustomFieldTypes');
        try{
            $this->CustomFields = $this->CustomFieldValues->CustomFields;
        }catch (\Exception $exception){
            Log::debug($exception->getMessage());
        }
        $this->CustomFieldOptions = $this->CustomFieldValues->CustomFields->CustomFieldOptions;
        $this->CustomForms = $this->CustomFields->CustomForms;
        $this->CustomFormsFields = TableRegistry::get($this->getConfig('formFieldClass.className'));
        $this->CustomFormsFilters = TableRegistry::get($this->getConfig('formFilterClass.className'));

        // Each field type will have one behavior attached
        $model->addBehavior('CustomField.RenderText');
        $model->addBehavior('CustomField.RenderNumber');
        $model->addBehavior('CustomField.RenderDecimal');
        $model->addBehavior('CustomField.RenderTextarea');
        $model->addBehavior('CustomField.RenderDropdown');
        $model->addBehavior('CustomField.RenderCheckbox');
        $model->addBehavior('CustomField.RenderTable');
        $model->addBehavior('CustomField.RenderDate');
        $model->addBehavior('CustomField.RenderTime');
        $model->addBehavior('CustomField.RenderStudentList');
        $model->addBehavior('CustomField.RenderCoordinates');
        $model->addBehavior('CustomField.RenderFile');
        $model->addBehavior('CustomField.RenderRepeater');
        $model->addBehavior('CustomField.RenderNote');
        $model->addBehavior('CustomField.RenderStaffList');//POCOR-2135
        // End

        // If tabSection is not set, added to handle Section Header
        if (!$this->getConfig('tabSection')) {
            $model->addBehavior('OpenEmis.Section');
        }

        $theModel = $this->getConfig('model');
        if (empty($theModel)) {
            $this->setConfig('model', $model->getRegistryAlias());
        }
        // POCOR-8917 end
    }

    private function isCAv4()
    {
        return isset($this->_table->CAVersion) && $this->_table->CAVersion=='4.0';
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events = array_merge($events, $this->getConfig('events'));
        return $events;
    }

    public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel)
    {
        $this->setToolbarButtons($toolbarButtons, $attr, $action);
    }

    public function addOnInitialize(Event $event, Entity $entity)
    {
        $this->deleteUploadSessions();
    }

    public function editOnInitialize(Event $event, Entity $entity)
    {
        $this->deleteUploadSessions();
    }

    public function viewEditBeforeQuery(Event $event, Query $query)
    {


        // do not contain CustomFieldValues
        if (!is_null($this->getConfig('tableCellClass'))) {
            $query->contain(['CustomTableCells']);
        }

    }

    public function editAfterQuery(Event $event, Entity $entity)
    {
        $this->formatEntity($entity);
    }

    public function viewAfterAction(Event $event, Entity $entity)
    {

        $model = $this->_table;
        $this->formatEntity($entity);
        $this->setupCustomFields($entity);
        // check if the query string contains tab_section if tab_section exists for a particular survey
        if ($model->request->getQuery('tab_section')!=null && $this->firstTabName) {
            $tabSection = $model->request->getQuery('tab_section');
            $model->request->getQuery('tab_section', $tabSection ?? $this->firstTabName);

        }
    }

    public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $model = $this->_table;
        $alias = $model->getAlias();

        if (isset($data[$alias])) {
            $CustomFields = TableRegistry::get($this->getConfig('fieldClass.className'));

            // patch custom_field_values
            if (isset($data[$alias]['custom_field_values'])) {
                $values = $data[$alias]['custom_field_values'];
                $fieldValues = $model->array_column($values, $this->getConfig('fieldKey'));
                $fieldResults = $CustomFields->find()
                    ->where(['id IN' => $fieldValues])
                    ->all();

                $fields = [];
                foreach ($fieldResults as $f) {
                    $fields[$f->id] = $f;
                }

                foreach ($values as $key => $attr) {
                    $fieldId = $attr[$this->getConfig('fieldKey')];
                    $thisField = $fields[$fieldId] ?? null;
                    if (!is_null($thisField)) {
                        $data[$alias]['custom_field_values'][$key]['field_type'] = $thisField->field_type;
                        $data[$alias]['custom_field_values'][$key]['mandatory'] = $thisField->is_mandatory;
                        $data[$alias]['custom_field_values'][$key]['unique'] = $thisField->is_unique;
                        $data[$alias]['custom_field_values'][$key]['params'] = $thisField->params;

                        // logic to patch request data
                        $fieldType = Inflector::camelize(strtolower($thisField->field_type));
                        $settings = new ArrayObject([
                            'recordKey' => $this->getConfig('recordKey'),
                            'fieldKey' => $this->getConfig('fieldKey'),
                            'tableColumnKey' => $this->getConfig('tableColumnKey'),
                            'tableRowKey' => $this->getConfig('tableRowKey'),
                            'customValue' => $attr
                        ]);
                        $event = $model->dispatchEvent('Render.patch'.$fieldType.'Values', [$entity, $data, $settings], $model);
                        if ($event->isStopped()) {
                            return $event->getResult();
                        }
                        // End
                    }
                }
            }
            // end

            // patch custom_table_cells
            $tableCells = [];
            $deleteTableCells = [];
            if (isset($data[$alias]['custom_table_cells'])) {
                $cells = $data[$alias]['custom_table_cells'];
                $fieldValues = array_keys($cells);

                $fieldResults = $CustomFields->find()
                    ->where(['id IN' => $fieldValues])
                    ->all();

                $fields = [];
                foreach ($fieldResults as $f) {
                    $fields[$f->id] = $f;
                }

                $settings = new ArrayObject([
                    'recordKey' => $this->getConfig('recordKey'),
                    'fieldKey' => $this->getConfig('fieldKey'),
                    'tableColumnKey' => $this->getConfig('tableColumnKey'),
                    'tableRowKey' => $this->getConfig('tableRowKey'),
                    'customValue' => [
                        'customField' => null,
                        'cellValues' => []
                    ],
                    'tableCells' => [],
                    'deleteTableCells' => []
                ]);
                foreach ($cells as $fieldId => $rows) {
                    $thisField = $fields[$fieldId] ?? null;
                    if (!is_null($thisField)) {
                        $settings['customValue']['customField'] = $thisField;
                        $settings['customValue']['cellValues'] = $rows;

                        $event = $model->dispatchEvent('Render.patchTableValues', [$entity, $data, $settings], $model);
                        if ($event->isStopped()) {
                            return $event->getResult();
                        }
                    }
                }
                $tableCells = $settings->offsetExists('tableCells') ? $settings['tableCells'] : [];
                $deleteTableCells = $settings->offsetExists('deleteTableCells') ? $settings['deleteTableCells'] : [];
            }

            $data[$alias]['custom_table_cells'] = $tableCells;
            $data[$alias]['delete_table_cells'] = $deleteTableCells;
            // end
        }

        $arrayOptions = $options->getArrayCopy();
        if (!empty($arrayOptions)) {
            if (!is_null($this->getConfig('tableCellClass'))) {
                $associated = ['CustomFieldValues', 'CustomTableCells'];
            } else {
                $associated = ['CustomFieldValues'];
            }
            $arrayOptions = array_merge_recursive($arrayOptions, ['associated' => $associated]);
            $options->exchangeArray($arrayOptions);
        }
    }

    public function addEditAfterAction(Event $event, Entity $entity)
    {
        $model = $this->_table;
        $this->setupCustomFields($entity);
        // check if the query string contains tab_section if tab_section exists for a particular survey
        $modelTabSection = $model->request->getQuery('tab_section');
        if (!is_null($modelTabSection) && $this->firstTabName) {
            $tabSection = $model->request->getQuery('tab_section');
            $model->request->Query['tab_section'] = $this->firstTabName;
        }
    }

    public function afterAction(Event $event)
    {
        if ($this->isCAv4()) {
            $extra = func_get_arg(1);

            $toolbarButtons = $extra['toolbarButtons'];
            $action = $this->_table->action;
            $toolbarAttr = [
                'class' => 'btn btn-xs btn-default',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false
            ];
            $this->setToolbarButtons($toolbarButtons, $toolbarAttr, $action);
            $extra['toolbarButtons'] = $toolbarButtons;
        }
    }

    public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $extra)
    {
        return $this->processSave($entity, $data, $extra);
    }

    public function editBeforeSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $extra)
    {
        return $this->processSave($entity, $data, $extra);
    }

    private function processSave(Entity $entity, ArrayObject $data, ArrayObject $extra)
    {

        $model = $this->_table;
        //POCOR-8538 start
        if($model->getRegistryAlias()=="Institution.InstitutionClasses"){
            return;
        }
        //POCOR-8538 end
        $process = function ($model, $entity) use ($data) {
            try {
                $repeaterSuccess = true;
                $repeaterErrors = false;
                $errors = $entity->getErrors();

                $fileErrors = [];
                $session = $model->request->getSession();
                $sessionErrors = $model->getRegistryAlias().'.parseFileError';
                if ($session->check($sessionErrors)) {
                    $fileErrors = $session->read($sessionErrors);
                }

                $alias = $model->getAlias();
                if (empty($errors) && empty($fileErrors)) {
                    $settings = new ArrayObject([
                        'recordKey' => $this->getConfig('recordKey'),
                        'fieldKey' => $this->getConfig('fieldKey'),
                        'formKey' => $this->getConfig('formKey'),
                        'tableColumnKey' => $this->getConfig('tableColumnKey'),
                        'tableRowKey' => $this->getConfig('tableRowKey'),
                        'valueKey' => null,
                        'customValue' => null,
                        'fieldValues' => [],
                        'tableCells' => $data[$alias]['custom_table_cells'],
                        'deleteFieldIds' => []
                    ]);

                    if (isset($data[$alias])) {
                        if (isset($data[$alias]['custom_field_values'])) {
                            $values = $data[$alias]['custom_field_values'];
                            foreach ($values as $key => $obj) {
                                $fieldType = Inflector::camelize(strtolower($obj['field_type']));
                                $settings['customValue'] = $obj;

                                $event = $model->dispatchEvent('Render.process'.$fieldType.'Values', [$entity, $data, $settings], $model);
                                if ($event->isStopped()) {
                                    return $event->getResult();
                                }
                            }
                        }
                    }

                    //calling processRepeaterValues() in RenderRepeaterBehavior
                    if ($this->_table->hasBehavior('RenderRepeater')) {
                        if (isset($data[$alias])) {
                            if (isset($data[$alias]['institution_repeater_surveys'])) {
                                $event = $model->dispatchEvent('Render.processRepeaterValues', [$entity, $data, $settings], $model);
                                if ($event->isStopped()) {
                                    return $event->getResult();
                                }
                            }
                        }
                    }
                    $data[$alias]['custom_field_values'] = $settings['fieldValues'];

                    $conn = ConnectionManager::get('default');
                    $conn->begin();

                    // POCOR-4799 Modified to only delete all dependent answers only if the selected value is not the show_options value in SurveyRules.
                    if ($alias == 'InstitutionSurveys') {
                        $entityCustomFieldValues = [];
                        foreach ($entity->custom_field_values as $key => $value) {
                            $entityCustomFieldValues[$value['survey_question_id']] = $value;
                        }
                        if (is_null($this->getConfig('moduleKey'))) {
                            if (isset($data[$this->_table->getAlias()][$this->getConfig('formKey')])) {
                                $surveyFormId = $data[$this->_table->getAlias()][$this->getConfig('formKey')];
                                $SurveyRules = TableRegistry::get('Survey.SurveyRules');
                                $rules = $SurveyRules
                                    ->find()
                                    ->where([
                                        $SurveyRules->aliasField('survey_form_id') => $surveyFormId,
                                        $SurveyRules->aliasField('enabled') => 1
                                    ])
                                    ->toArray();
                                if (!empty($rules)) {
                                    foreach ($rules as $rule) {
                                        $ruleShowOptions = json_decode($rule->show_options);
                                        // POCOR-9129 start
                                        if(!is_array($ruleShowOptions)) {
                                            $ruleShowOptions = [$ruleShowOptions];
                                        }
                                        // POCOR-9129 end
                                        if (isset($entityCustomFieldValues[$rule->dependent_question_id])
                                            && !in_array($entityCustomFieldValues[$rule->dependent_question_id]['number_value'], $ruleShowOptions)) {
                                            $settings['deleteFieldIds'][] = $rule->survey_question_id;
                                            foreach ($data[$alias]['custom_field_values'] as $key => $value) {
                                                if ($value['survey_question_id'] == $rule->survey_question_id) {
                                                    unset($data[$alias]['custom_field_values'][$key]);
                                                }
                                            }
                                        }
                                        $data[$alias]['custom_field_values'] = array_values($data[$alias]['custom_field_values']);
                                    }
                                }
                            }
                        }
                    }

                    // when edit always delete all the checkbox values before reinsert,
                    // also delete previously saved records with empty value
                    if (isset($entity->id)) {
                        $id = $entity->id;
                        $deleteFieldIds = $settings['deleteFieldIds'];

                        if (!empty($deleteFieldIds)) {
                            $this->CustomFieldValues->deleteAll([
                                $this->CustomFieldValues->aliasField($settings['recordKey']) => $id,
                                $this->CustomFieldValues->aliasField($settings['fieldKey'] . ' IN ') => $deleteFieldIds
                            ]);

                            // when edit always delete all the cell values before reinsert
                            if (!is_null($this->getConfig('tableCellClass'))) {
                                $this->CustomTableCells->deleteAll([
                                    $this->CustomTableCells->aliasField($settings['recordKey']) => $id,
                                    $this->CustomTableCells->aliasField($settings['fieldKey'] . ' IN ') => $deleteFieldIds
                                ]);
                            }
                        }
                    }

                    $requestData = $data->getArrayCopy();
                    $entity = $model->patchEntity($entity, $requestData);
                    // End

                    // Logic to delete all exisiting values of a repeater
                    if ($entity->has('institution_repeater_surveys')) {
                        $formKey = 'survey_form_id';
                        $RepeaterSurveys = TableRegistry::get('InstitutionRepeater.RepeaterSurveys');
                        $RepeaterSurveyAnswers = TableRegistry::get('InstitutionRepeater.RepeaterSurveyAnswers');

                        $status = $entity->status_id;
                        $institutionId = $entity->institution_id;
                        $periodId = $entity->academic_period_id;
                        $parentFormId = $entity->{$formKey};

                        foreach ($entity->institution_repeater_surveys as $fieldId => $fieldObj) {
                            $formId = $fieldObj[$formKey];
                            unset($fieldObj[$formKey]);

                            // Logic to delete all answers before re-insert
                            $repeaterIds = array_keys($fieldObj);

                            $originalRepeaterIds = [];
                            if ($entity->has('institution_repeaters')) {
                                if (isset($entity->institution_repeaters[$fieldId])) {
                                    $originalRepeaterIds = array_values($entity->institution_repeaters[$fieldId]);
                                }
                            }
                            $surveyIds = [];
                            if (!empty($originalRepeaterIds)) {
                                $surveyIds = $RepeaterSurveys
                                    ->find('list', ['keyField' => 'id', 'valueField' => 'id'])
                                    ->where([
                                        $RepeaterSurveys->aliasField('status_id') => $status,
                                        $RepeaterSurveys->aliasField('institution_id') => $institutionId,
                                        $RepeaterSurveys->aliasField('academic_period_id') => $periodId,
                                        $RepeaterSurveys->aliasField($formKey) => $formId,
                                        $RepeaterSurveys->aliasField('repeater_id IN ') => $originalRepeaterIds
                                    ])
                                    ->toArray();
                            }
                            // POCOR-8231 simplified isset
                            if (!empty($surveyIds) && isset($settings['repeaterValues'])) {
                                // always deleted all existing answers before re-insert
                                $RepeaterSurveyAnswers->deleteAll([
                                    $RepeaterSurveyAnswers->aliasField('institution_repeater_survey_id IN ') => $surveyIds
                                ]);
                            }

                            if (!empty($repeaterIds)) {
                                if (!empty($originalRepeaterIds)) {
                                    $missingRepeaters = array_diff($originalRepeaterIds, $repeaterIds);
                                    if (!empty($missingRepeaters)) {
                                        // if user has remove particular repeater from form, delete away that repeater from database too
                                        $RepeaterSurveys->deleteAll([
                                            $RepeaterSurveys->aliasField('status_id') => $status,
                                            $RepeaterSurveys->aliasField('institution_id') => $institutionId,
                                            $RepeaterSurveys->aliasField('academic_period_id') => $periodId,
                                            $RepeaterSurveys->aliasField($formKey) => $formId,
                                            $RepeaterSurveys->aliasField('repeater_id IN ') => $missingRepeaters
                                        ]);
                                    }
                                }
                            } else {
                                // if user remove all rows from form, delete away all repeater records
                                $RepeaterSurveys->deleteAll([
                                    $RepeaterSurveys->aliasField('status_id') => $status,
                                    $RepeaterSurveys->aliasField('institution_id') => $institutionId,
                                    $RepeaterSurveys->aliasField('academic_period_id') => $periodId,
                                    $RepeaterSurveys->aliasField($formKey) => $formId
                                ]);
                            }
                        }
                        // POCOR-8436 if settings is an array
                        // POCOR-8231 simplified and cleancoded
                        if(is_array($settings)){
                            $settingsArray = $settings;
                        }else{
                            $settingsArray = $settings->getArrayCopy();
                        }
                        if(isset($settingsArray['repeaterValues'])){
                            foreach ($settingsArray['repeaterValues'] as $key => $value) {
                                $surveyEntity = $RepeaterSurveys->newEntity($value);
                                $all[] = $surveyEntity;
                                if ($RepeaterSurveys->save($surveyEntity)) {
                                } else {
                                    Log::write('debug', print_r($surveyEntity->getErrors(), true));
                                    $repeaterErrors = true;
                                    $repeaterSuccess = false;
                                }
                            }

                            //pass the entity with repeater errors back to onGetCustomRepeaterElement for rendering the error messages
                            $entity['institution_repeater_surveys_error_obj'] = $all;
                            //if any validation error is found for repeater, display error message
                            if($repeaterErrors){
                                $entity->getErrors('institution_repeater_surveys', '');
                            }
                        }
                    }

                    $result = $model->save($entity);
                    if ($result && $repeaterSuccess) {
                        $conn->commit();
                    } else {
                        $conn->rollback();
                    }
                    return $result;
                } else {
                    $indexedErrors = [];
                    $fields = ['text_value', 'number_value', 'decimal_value', 'textarea_value', 'date_value', 'time_value', 'file'];
                    // POCOR-8231 simplified isset
                    if (isset($errors['custom_field_values'])) {
                        if ($entity->has('custom_field_values')) {
                            foreach ($entity->custom_field_values as $key => $obj) {
                                $fieldId = $obj->{$this->getConfig('fieldKey')};

                                if (isset($errors['custom_field_values'][$key])) {
                                    $indexedErrors[$fieldId] = $errors['custom_field_values'][$key];
                                    foreach ($fields as $field) {
                                        $entity->custom_field_values[$key]->getDirty($field, true);
                                    }
                                }
                            }
                        }
                    }

                    $indexedErrors = $indexedErrors + $fileErrors;
                    // POCOR-8231 simplified isset
                    if (!empty($indexedErrors)) {
                        if (isset($data[$alias])) {
                            if (isset($data[$alias]['custom_field_values'])) {
                                foreach ($data[$alias]['custom_field_values'] as $key => $obj) {
                                    $fieldId = $obj[$this->getConfig('fieldKey')];

                                    if (isset($indexedErrors[$fieldId])) {
                                        foreach ($fields as $field) {
                                            if (isset($indexedErrors[$fieldId][$field])) {
                                                $error = $indexedErrors[$fieldId][$field];
                                                if (isset($entity->custom_field_values[$key])) { // POCOR-9147
                                                    $entity->custom_field_values[$key]->getErrors($field, $error, true);
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }

//                    Log::write('debug', 'entity Errors:');
//                    Log::write('debug', $entity->errors());
//                    Log::write('debug', 'file Errors:');
//                    Log::write('debug', $fileErrors);

                    return false;
                }
            } catch (Exception $ex) {
                Log::write('error', $ex);
                $msg = $ex->getMessage();
                $model->Alert->error($msg, ['type' => 'text', 'reset' => true]);
            }
        };

        return $process;
    }

    /**
     *  Function to get the filter key from the filter specified
     *
     *  @param string $filter The filter provided by the custom module
     *  @param string $model The model provided by the custom module
     *  @return The filter foreign key name if found. If not it will return empty.
     */
    private function getFilterKey($filterAlias, $modelAlias)
    {
        $filterKey = '';
        $associations = TableRegistry::getTableLocator()->get($filterAlias)->associations();

        foreach ($associations as $assoc) {
            if ($assoc->getRegistryAlias() == $modelAlias) {
                $filterKey = $assoc->getForeignKey();
                return $filterKey;
            }
        }
        return $filterKey;
    }

    public function getCustomFieldQuery($entity, $params = [])
    {
        $query = null;
        $withContain = $params['withContain'] ?? true;
        $generalOnly = $params['generalOnly'] ?? false;
        // For Institution Survey
        if (is_null($this->getConfig('moduleKey'))) {
            if ($entity->has($this->getConfig('formKey'))) {
                $customFormId = $entity->{$this->getConfig('formKey')};
                // if (isset($customFormId)) {
                    $customFormQuery = $this->CustomForms
                        ->find('list', ['keyField' => 'id', 'valueField' => 'id'])
                        ->where([$this->CustomForms->aliasField('id') => $customFormId]);
                // }
            }
        } else {
            //cakephp4 start
            $model = $this->getConfig('model');
            if (empty($model)) {
                $model =  $this->_table->getRegistryAlias();
            } //END
            $where = [$this->CustomModules->aliasField('model') => $model];
            $results = $this->CustomModules
                ->find('all')
                ->where($where)
                ->first();

            if (!empty($results)) {
                $moduleId = $results->id;
                $filterAlias = $results->filter;

                $customFormQuery = $this->CustomForms
                    ->find('list', ['keyField' => 'id', 'valueField' => 'id'])
                    ->where([$this->CustomForms->aliasField($this->getConfig('moduleKey')) => $moduleId]);

                if (!empty($filterAlias)) {
                    $filterKey = $this->getFilterKey($filterAlias, $this->getConfig('model'));
                    if (empty($filterKey)) {
                        list($modelplugin, $modelAlias) = explode('.', $filterAlias, 2);
                        $filterKey = Inflector::underscore(Inflector::singularize($modelAlias)) . '_id';
                    }
                    $filterId = isset($entity->{$filterKey})? $entity->{$filterKey} : 0;

                    // conditions
                    $generalConditions = [
                        $this->CustomFormsFilters->aliasField($this->getConfig('formKey') . ' = ') . $this->CustomForms->aliasField('id'),
                        $this->CustomFormsFilters->aliasField($this->getConfig('filterKey')) => 0
                    ];
                    $filterConditions = [
                        $this->CustomFormsFilters->aliasField($this->getConfig('formKey') . ' = ') . $this->CustomForms->aliasField('id'),
                        $this->CustomFormsFilters->aliasField($this->getConfig('filterKey')) => $filterId
                    ];
                    if ($generalOnly) {
                        $conditions = $generalConditions;
                    } else {
                        $conditions = [
                            'OR' => [$generalConditions, $filterConditions]
                        ];
                    }

                    // End
                 $customFormQuery
                        ->join([
                            'table' => $this->CustomFormsFilters->getTable(),
                            'alias' => $this->CustomFormsFilters->getAlias(),
                            'conditions' => $conditions
                        ]);

                }
            }
        }

        if (!empty($customFormQuery)) {
            $customFormIds = $customFormQuery
                ->toArray();
            //POCOR-8434 starts
            if($model == 'Institution.StudentAdmission' && !empty($customFormIds)){
                $customFormIds = $this->getcustomFormIdByStudentFormFilters($customFormIds, $entity, $moduleId);
            }//POCOR-8434 ends

            if (!empty($customFormIds)) {
                //POCOR-8434 starts
                $query = $this->CustomFormsFields
                        ->find('all')
                        ->find('order')
                        ->where([
                            $this->CustomFormsFields->aliasField($this->getConfig('formKey') . ' IN') => $customFormIds
                        ]);
                    $group = [
                        $this->CustomFormsFields->aliasField($this->getConfig('fieldKey'))
                    ];
                    //POCOR-8434 starts
                    if ($model == 'Institution.StudentAdmission') {
                        $group[] = $this->CustomFormsFields->aliasField($this->getConfig('formKey')); // POCOR-8434 add formkey condition
                    }//POCOR-8434 ends

                    $query->group($group);
                    //POCOR-8434 ends
                    if ($withContain) {
                    if (is_array($withContain)) {
                        $query->contain($withContain);
                    } else {
                        $query->contain([
                            'CustomFields.CustomFieldOptions' => function ($q) {
                                return $q
                                    ->find('visible')
                                    ->find('order');
                            }
                        ]);

                        if (!is_null($this->getConfig('tableColumnKey'))) {
                            $query->contain([
                                'CustomFields.CustomTableColumns' => function ($q) {
                                    return $q
                                        ->find('visible')
                                        ->find('order');
                                }
                            ]);
                        }

                        if (!is_null($this->getConfig('tableRowKey'))) {
                            $query->contain([
                                'CustomFields.CustomTableRows' => function ($q) {
                                    return $q
                                        ->find('visible')
                                        ->find('order');
                                }
                            ]);
                        }
                    }
                }
            }
        }

        return $query;
    }

    //POCOR-8434 starts
    public function getcustomFormIdByStudentFormFilters($customFormIds, $entity, $moduleId){
        $EducationGradesTbl = TableRegistry::getTableLocator()->get('Education.EducationGrades');
        $EducationGrades = $EducationGradesTbl
             ->find()
             ->where([$EducationGradesTbl->aliasField('id') => $entity->education_grade_id])
             ->first();
        $customFormData = [];
        if(!empty($EducationGrades) && !empty($entity->academic_period_id) && !empty($customFormIds)){
            $StudentCustomFiltersTbl = TableRegistry::getTableLocator()->get('StudentCustomField.StudentCustomFilters');
            $customFormData = $StudentCustomFiltersTbl
                ->find('list', ['keyField' => 'student_custom_form_id', 'valueField' => 'student_custom_form_id'])
                ->where([
                    $StudentCustomFiltersTbl->aliasField('education_programme_id') => $EducationGrades->education_programme_id,
                    $StudentCustomFiltersTbl->aliasField('academic_period_id') => $entity->academic_period_id,
                    $StudentCustomFiltersTbl->aliasField('custom_module_id') => $moduleId,
                    $StudentCustomFiltersTbl->aliasField('student_custom_form_id IN') => $customFormIds
                ])->toArray();
        }
        return $customFormData;
    }//POCOR-8434 ends

    public function formatEntity(Entity $entity)
    {
        $model = $this->_table;
        $primaryKey = $model->getPrimaryKey();
        $idKey = $model->aliasField($primaryKey);
        $id = $entity->id;

        $values = [];
        if ($model->exists([$idKey => $id])) {
            $query = $model->find()->contain(['CustomFieldValues.CustomFields'])->where([$idKey => $id]);

            $newEntity = $query->first();
            if ($newEntity->has('custom_field_values')) {
                foreach ($newEntity->custom_field_values as $key => $obj) {
                    $fieldId = $obj->{$this->getConfig('fieldKey')};
                    $customField = $obj->custom_field;
                    $isCheckbox = $customField->field_type == 'CHECKBOX';//POCOR-8434
                    if ($isCheckbox) {
                        $checkboxValues = [$obj['number_value']];
                        if (isset($values[$fieldId])) {
                            $checkboxValues = array_merge($checkboxValues, $values[$fieldId]['number_value']);
                        }
                        $obj['number_value'] = $checkboxValues;
                    }
                    $values[$fieldId] = $obj;
                }
            }
        }

        $query = $this->getCustomFieldQuery($entity, ['withContain' => ['CustomFields']]);
		$tabSection = '';
        $fieldValues = [];  // values of custom field must be in sequence for validation errors to be placed correctly
        if (!is_null($query)) {
            $where =[];
            if ($entity->survey_form['custom_module_id'] == 1 && isset($model->request->getQuery['tab_section'])){
                $tabSection = $model->request->getQuery['tab_section'];
                //POCOR-4850[START]
                // $where[] = $query->newExpr('REPLACE(REPLACE(' . $this->CustomFormsFields->aliasField('section') . ', " ", "-" ), ".","") = "'.$tabSection.'"');
                //POCOR-4850[END]
            }
            $customFields = $query
                ->where([
                    $where
                ])
                ->toArray();

            foreach ($customFields as $key => $obj) {
                //$obj->custom_field['student_custom_form_id'] = $obj->student_custom_form_id;
                $customField = $obj->custom_field;
                //$customField['student_custom_form_id'] = $obj->student_custom_form_id;
                $fieldTypeCode = $customField->field_type;
                $section = $obj->section ?? "section";
                $slug = Text::slug($section);

                // only apply for field type store in custom_field_values
                if (in_array($fieldTypeCode, $this->fieldValueArray)) {
					if(empty($tabSection) || ($slug == $tabSection)) {
						$fieldId = $customField->id;
                        //$formId = $customField->student_custom_form_id;//not useable
                        //POCOR-8434 starts
                        $recordKey = $entity->id;
                        $fieldValue = $values[$fieldId] ?? null;
                        if ($fieldValue) {
                            //$fieldValues['student_custom_form_id'] = $formId;//not useable
                            $fieldValues[] = $fieldValue;
                        } else {
                            $valueData = [
                                'text_value' => null,
                                'number_value' => null,
                                'decimal_value' => null,
                                'textarea_value' => null,
                                'date_value' => null,
                                'time_value' => null,
                                $this->getConfig('fieldKey') => $fieldId,
                                $this->getConfig('recordKey') => $recordKey,
                                'custom_field' => null, // set after data is patched else will be lost
                            ];

                            $valueEntity = $this->CustomFieldValues->newEntity($valueData, ['validate' => false]);
                            $valueEntity->custom_field = $customField;
                            $fieldValues[] = $valueEntity;
                        }//POCOR-8434 ends
                    }
                } else {
                    $fieldType = Inflector::camelize(strtolower($fieldTypeCode));
                    $settings = new ArrayObject([
                        'fieldKey' => $this->getConfig('fieldKey'),
                        'formKey' => $this->getConfig('formKey'),
                        'customField' => $customField
                    ]);

                    $event = $model->dispatchEvent('Render.format'.$fieldType.'Entity', [$entity, $settings], $model);
                    if ($event->isStopped()) {
                        return $event->getResult();
                    }
                }
            }
        }

        $entity->set('custom_field_values', $fieldValues);
    }

    public function setupCustomFields(Entity $entity)
    {
        $model = $this->_table;
        $ControllerAction = $this->isCAv4() ? $model : $model->ControllerAction;
        $session = $model->request->getSession();
        $query = $this->getCustomFieldQuery($entity);
        // echo "<pre>";print_r($entity);die;
        if (!$query) {
            // Log an error message or handle the null query case
            Log::error('Custom field query returned null.');
            return;
        }
        // If tabSection is set, setup Tab Section
        if ($this->getConfig('tabSection')) {
            $customFields = $query->toArray();

            $tabElements = [];
            if ($this->isCAv4()) {
                $action = $model->action;
            } else {
                $action = $ControllerAction->action();
            }
            $url = $ControllerAction->url($action);
            $sectionName = '__section'; // POCOR-8542
            foreach ($customFields as $key => $obj) {
                if (isset($obj->section)) {
                    if ($sectionName != $obj->section) {
                        $sectionName = $obj->section;
                        $tabName = Text::slug($sectionName);
                        // set the first tab section into a global variable
                        if (is_null($this->firstTabName)) {
                            $this->firstTabName = $tabName;
                        }
                        if (empty($tabElements)) {
                            $selectedAction = $tabName;
                        }
                        if(isset($url['?'])) {
                            unset( $url['?'] );
                        }
                        $url['tab_section'] = $tabName;
                        $moduleUrl = $url;
                        $moduleUrl['?']['tab_section'] = $tabName;
                        // POCOR-8542 Start
                        $tabElements[$tabName] = [
                            'url' => $moduleUrl,
                            'text' => $sectionName != '' ? $sectionName :__('Questions'),
                            'section' => $sectionName
                        ];
                        // POCOR-8542 End


                    }
                }
            }
            if (!empty($tabElements)) {
                $selectedAction = !is_null($model->request->getQuery('tab_section')) ? $model->request->getQuery('tab_section') : $selectedAction;
                //$model->controller->TabPermission->checkTabPermission($tabElements);
                $model->controller->set('tabElements', $tabElements);
                $model->controller->set('selectedAction', $selectedAction);

                $query->where([
                    $this->CustomFormsFields->aliasField('section') => $tabElements[$selectedAction]['section']
                ]);
            }
        }
        // End

        // For survey only
        // To get the rules for the survey form
        if (is_null($this->getConfig('moduleKey')) && $this->_table->action == 'view') {
            $SurveyRules = TableRegistry::getTableLocator()->get('Survey.SurveyRules');
            $surveyFormId = $entity->survey_form_id;
            $rules = $SurveyRules
                ->find('SurveyRulesList', [
                    'survey_form_id' => $surveyFormId
                ])
                ->toArray();
        }

        if (!is_null($query)) {
            $customFields = $query->toArray();

            $order = 0;
            $fieldOrder = [];
            // temporary fix: to make custom fields appear before map in Institutions > General > Overview
            $ignoreFields = ['id', 'map_section', 'map', 'modified_user_id', 'modified', 'created_user_id', 'created'];

            // re-order array sequence based on 'order' attribute value.
            $modelFields = $model->fields;
            uasort($modelFields, function ($a, $b) {
                return $a['order']-$b['order'];
            });
            foreach ($modelFields as $fieldName => $field) {
                if (!in_array($fieldName, $ignoreFields)) {
                    $order = $field['order'] > $order ? $field['order'] : $order;
                    if (isset($fieldOrder[$order])) {
                        $order++;
                    }
                    $fieldOrder[$order] = $fieldName;
                }
            }
            // retrieve saved values
            $values = new ArrayObject([]);
            $cells = new ArrayObject([]);

            if (isset($entity->id)) {
                $fieldKey = $this->getConfig('fieldKey');
                $tableRowKey = $this->getConfig('tableRowKey');
                $tableColumnKey = $this->getConfig('tableColumnKey');

                if ($entity->has('custom_field_values')) {
                    foreach ($entity->custom_field_values as $key => $obj) {
                        if (isset($obj->id)) {
                            $fieldId = $obj->{$fieldKey};
                            $fieldData = ['id' => $obj->id];

                            if ($model->request->is(['get'])) {
                                // onGet
                                $fieldData['text_value'] = $obj->text_value;
                                $fieldData['number_value'] = $obj->number_value;
                                $fieldData['decimal_value'] = $obj->decimal_value;
                                $fieldData['textarea_value'] = $obj->textarea_value;
                                $fieldData['date_value'] = $obj->date_value;
                                $fieldData['time_value'] = $obj->time_value;
                                $fieldData['file'] = $obj->file;

                                // logic for Initialize
                                $fieldType = Inflector::camelize(strtolower($obj->custom_field->field_type));
                                $settings = new ArrayObject([
                                    'recordKey' => $this->getConfig('recordKey'),
                                    'fieldKey' => $this->getConfig('fieldKey'),
                                    'tableColumnKey' => $this->getConfig('tableColumnKey'),
                                    'tableRowKey' => $this->getConfig('tableRowKey'),
                                    'customValue' => $obj
                                ]);
                                $event = $model->dispatchEvent('Render.on'.$fieldType.'Initialize', [$entity, $settings], $model);
                                if ($event->isStopped()) {
                                    return $event->getResult();
                                }
                                // End
                            } else if ($model->request->is(['post', 'put'])) {
                                // onPost, no actions
                                // POCOR-8352 Start
                                $fieldData['text_value'] = $obj->text_value;
                                $fieldData['number_value'] = $obj->number_value;
                                $fieldData['decimal_value'] = $obj->decimal_value;
                                $fieldData['textarea_value'] = $obj->textarea_value;
                                $fieldData['date_value'] = $obj->date_value;
                                $fieldData['time_value'] = $obj->time_value;
                                $fieldData['file'] = $obj->file;

                                // logic for Initialize
                                $fieldType = Inflector::camelize(strtolower($obj->custom_field->field_type));
                                $settings = new ArrayObject([
                                    'recordKey' => $this->getConfig('recordKey'),
                                    'fieldKey' => $this->getConfig('fieldKey'),
                                    'tableColumnKey' => $this->getConfig('tableColumnKey'),
                                    'tableRowKey' => $this->getConfig('tableRowKey'),
                                    'customValue' => $obj
                                ]);
                                $event = $model->dispatchEvent('Render.on'.$fieldType.'Initialize', [$entity, $settings], $model);
                                if ($event->isStopped()) {
                                    return $event->getResult();
                                }
                                // POCOR-8352 End
                            }
                            $values[$fieldId] = $fieldData;
                        }
                    }
                }

                if ($entity->has('custom_table_cells')) {
                    foreach ($entity->custom_table_cells as $key => $obj) {
                        $fieldId = $obj->{$fieldKey};
                        $rowId = $obj->{$tableRowKey};
                        $columnId = $obj->{$tableColumnKey};

                        $cells[$fieldId][$rowId][$columnId] = [
                            'text_value' => $obj['text_value'],
                            'number_value' => $obj['number_value'],
                            'decimal_value' => $obj['decimal_value']
                        ];
                    }
                }
            }

            $valuesArray = $values->getArrayCopy();
            $cellsArray = $cells->getArrayCopy();
            // End

            $count = 0;
            $sectionName = [];
            foreach ($customFields as $key => $obj) {
                // If tabSection is not set, setup Section Header
                //POCOR-7600
                if ((!$this->getConfig('tabSection'))|| $model->request->getParam('action')=="Surveys") {
                    if (isset($obj->section)) {
                        if (!in_array($obj->section, $sectionName)) {
                            $sectionName[$key] = $obj->section;
                            $fieldName = "section_".$key."_header";

                            if (!empty($sectionName)
                                &&$model->request->getParam('action')!="Surveys"
                                && $model->request->getParam('action')!="Classes") {//POCOR-8538
                                $ControllerAction->field($fieldName, ['type' => 'section', 'title' => $sectionName[$key]]);
                                $fieldOrder[++$order] = $fieldName;
                               // echo "<pre>";print_r($customFields);die;
                            }


                            foreach($customFields as $we => $cfld){
                                if($cfld->section == $sectionName[$key]){
                                    $customField = $cfld->custom_field;
                                    $fieldType = $customField->field_type;
                                    $fieldName = "custom_".$we."_field";
                                    $valueClass = strtolower($fieldType) == 'table' || strtolower($fieldType) == 'student_list' ? 'table-full-width' : '';

                                    $attr = [
                                        'type' => 'custom_'. strtolower($fieldType),
                                        'attr' => [
                                            'label' => $customField->name,
                                            'fieldKey' => $this->getConfig('fieldKey'),
                                            'formKey' => $this->getConfig('formKey'),
                                            'tableColumnKey' => $this->getConfig('tableColumnKey'),
                                            'tableRowKey' => $this->getConfig('tableRowKey')
                                        ],
                                        'valueClass' => $valueClass,
                                        'customField' => $customField,
                                        'customFieldValues' => $valuesArray,
                                        'customTableCells' => $cellsArray
                                    ];

                                    // for label of mandatory *
                                    if ($customField->is_mandatory == 1) {
                                        $attr['attr']['required'] = 'required';
                                    }

                                    // seq is very important for validation errors
                                    if (in_array($fieldType, $this->fieldValueArray)) {
                                        $attr['attr']['seq'] = $count++;
                                    }

                                    $renderField = true;

                                    // For survey only
                                    // To show the field in the view page base on the rules
                                    if (is_null($this->getConfig('moduleKey')) && $this->_table->action == 'view') {
                                        $id = $attr['customField']['id'];
                                        if (isset($rules[$id])) {
                                            $answer = $this->_table->array_column($attr['customFieldValues'], 'number_value');
                                            $forRender = false;
                                            foreach ($rules[$id] as $ruleKey => $ruleOpt) {
                                                if (isset($answer[$ruleKey])) {
                                                    if (in_array($answer[$ruleKey], json_decode($ruleOpt, true))) {
                                                        $forRender = true;
                                                    }
                                                }
                                            }
                                            $renderField = $forRender;
                                        }
                                    }

                                    if ($renderField) {
                                        $ControllerAction->field($fieldName, $attr);
                                        $fieldOrder[++$order] = $fieldName;
                                    }
                                }


                            }
                        }
                    }
                }//POCOR-7600
                // End

            }

            foreach ($ignoreFields as $key => $field) {
                // add checking (map_section, map) to append ignore fields only if exists
                if (isset($this->_table->fields[$field])) {
                    $fieldOrder[++$order] = $field;
                }
            }
            //POCOR-8538 start
            if($model->request->getParam('action')=="Classes"){
              $fieldOrder=$this->setUpFieldOrderForClasses($fieldOrder);
            }
            //POCOR-8538 end
            $ControllerAction->setFieldOrder($fieldOrder);
        }
    }

    private function deleteUploadSessions()
    {
        $model = $this->_table;
        $session = $model->request->getSession();
        $session->delete($model->getRegistryAlias().'.parseFile');
        $session->delete($model->getRegistryAlias().'.parseFileError');
    }

    // Model.excel.onExcelBeforeStart
    public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets)
    {
        $optionsValues = $this->CustomFieldOptions->find('list')->toArray();
        $sheets[] = [
            'name' => $this->_table->getAlias(),
            'table' => $this->_table,
            'query' => $this->_table->find(),
            'customFieldOptions' => $optionsValues,
        ];
    }

    // Model.excel.onExcelUpdateFields
    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {

        $recordId = $settings['id'];
        Log::debug(print_r($settings, true));
        Log::debug(print_r($this->_table->request->getAttribute('params'), true));
        // POCOR-9067 start: problem for class or institution
        if(!isset($recordId)) {
            $checkEncodedClassId = $this->_table->request->getAttribute('params')['pass'][1];//POCOR-8324
            $encodedClassId = $this->_table->paramsDecode($checkEncodedClassId);//POCOR-8323
            // POCOR-9090 start
            if (isset($encodedClassId['institution_class_id'])) {//POCOR-8323        }
                $recordId = $encodedClassId['institution_class_id'];
                $entityType = 'institution_class';
            }else{
                if (isset($encodedClassId['institution_id'])) {//POCOR-8323        }
                    $recordId = $encodedClassId['institution_id'];
                    $entityType = 'institution';
                }
            }
        }
        try {
            if($entityType == 'institution_class'){
                $institutionClasses = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
                $entity = $institutionClasses->get($recordId);
                }
            if($entityType == 'institution'){
                $institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
                $entity = $institutions->get($recordId);
            }
//            $entity = $this->_table->get($recordId);
            // POCOR-9090 end
        } catch (\Exception $e) {
            Log::error('Error fetching entity: ' . $e->getMessage());
            return $fields;
        }
        // POCOR-9067 end

        $tableCustomFieldIds = [];
        $customFieldQuery = $this->getCustomFieldQuery($entity);
        $customFields = [];
        if (!is_null($customFieldQuery)) {
            $customFields = $customFieldQuery->toArray();
        }

        foreach ($customFields as $customField) {
            $_customField = $customField->custom_field;
            $_field_type = $_customField->field_type;
            $_id = $_customField->id;
            $_name = $_customField->name;

            if ($_field_type != 'TABLE') {
                $field['key'] = 'CustomField';
                $field['field'] = 'custom_field';
                $field['type'] = 'custom_field';
                $field['label'] = $_name;
                $field['customField'] = ['id' => $_id, 'field_type' => $_field_type];
                $fields[] = $field;
            } else {
                $tableCustomFieldIds[] = $_id;
                $tableRow = $_customField->custom_table_rows;
                $tableCol = $_customField->custom_table_columns;
                $row = [];
                foreach ($tableRow as $r) {
                    $row[$r['order']] = $r;
                }
                ksort($row);
                $row = array_values($row);
                $col = [];
                foreach ($tableCol as $c) {
                    $col[$c['order']] = $c;
                }
                ksort($col);
                $col = array_values($col);

                if (sizeof($row) !=0 && sizeof($col) !=0) {
                    for ($i = 1; $i < sizeof($col); $i++) {
                        foreach ($row as $rw) {
                            $field['key'] = 'CustomField';
                            $field['field'] = 'custom_field';
                            $field['type'] = 'custom_field';
                            $field['label'] = $_name . ' ('.$col[$i]['name'].', '.$rw['name'].')';
                            $field['customField'] = ['id' => $_id, 'field_type' => $_field_type, 'col_id' => $col[$i]['id'], 'row_id' => $rw['id']];
                            $fields[] = $field;
                        }
                    }
                }
            }
        }

        // Set the available options for dropdown and checkbox type
        $this->_customFieldOptions = $settings['sheet']['customFieldOptions'];
        // POCOR-9067 start
        $request = $this->_table->request;
        if ($request->getParam('controller') == 'Institutions' && $request->getParam('action') == 'Classes') {
            $this->_fieldValues = $tableCustomFieldIds;
        } else {

            $tableCellValues = $this->getTableCellValues($tableCustomFieldIds, $entity->id);

            // Set the fetched field values to avoid multiple call to the database
            $fieldValues = $this->getFieldValue($entity->id) + $tableCellValues;
            ksort($fieldValues);
        }
        // POCOR-9067 end
        $this->_fieldValues = $fieldValues;
    }

    private function getTableCellValues($tableCustomFieldIds, $recordId)
    {
        if (!empty($tableCustomFieldIds)) {
            $TableCellTable = $this->CustomTableCells;
            $customFieldsForeignKey = $TableCellTable->CustomFields->getForeignKey();
            $customRecordsForeignKey = $TableCellTable->CustomRecords->getForeignKey();
            $customColumnForeignKey = $TableCellTable->CustomTableColumns->getForeignKey();
            $customRowForeignKey = $TableCellTable->CustomTableRows->getForeignKey();
            $tableCellData = new ArrayObject();
            $TableCellTable
                    ->find()
                    ->where([$TableCellTable->aliasField($customFieldsForeignKey).' IN ' => $tableCustomFieldIds, $TableCellTable->aliasField($customRecordsForeignKey) => $recordId])
                    ->map(function ($row) use ($tableCellData, $customFieldsForeignKey, $customColumnForeignKey, $customRowForeignKey) {
                        $value = null;
                        if (isset($row['number_value']) && $row['number_value']) {
                            $value = $row['number_value'];
                        } elseif (isset($row['text_value']) && $row['text_value']) {
                            $value = $row['text_value'];
                        } elseif (isset($row['decimal_value']) && $row['decimal_value']) {
                            $value = $row['decimal_value'];
                        }
                        $tableCellData[$row[$customFieldsForeignKey]][$row[$customColumnForeignKey]][$row[$customRowForeignKey]] = $value;
                        return $row;
                    })
                    ->toArray();
            $tableCellData = $tableCellData->getArrayCopy();
            return $tableCellData;
        }
        return [];
    }

    // Model.excel.onExcelRenderCustomField
    public function onExcelRenderCustomField(Event $event, Entity $entity, array $attr)
    {
        // POCOR-9067 start
        $request = $this->_table->request; //POCOR-8409
        $answer = '';
        if ($request->getParam('controller') == 'Institutions' && $request->getParam('action') == 'Classes') {
            $tableCustomFieldIds = $this->_fieldValues;
            $tableCellValues = $this->getTableCellValues($tableCustomFieldIds, $entity->institution_class_id);
            $field_values = $this->getFieldValue($entity->institution_class_id) + $tableCellValues;
            ksort($field_values);
        } else {
            $field_values = $this->_fieldValues;
        }
        if (!empty($field_values)) {

            $type = strtolower($attr['customField']['field_type']);
            if (method_exists($this, $type)) {
                $request = $this->_table->request; //POCOR-8409
                if($request->getParam('controller') == 'Institutions' && $request->getParam('action') == 'Surveys') {
                    $type = 'getCustomField';
                }
                $ans = $this->$type($field_values, $attr['customField'], $this->_customFieldOptions);
                if (!(is_null($ans))) {
                    $answer = $ans;
                }
            }
            return $answer;
        }
    // POCOR-9067 start
    }

    /**
     *  Function to get the field values base on a given record id
     *
     *  @param int $recordId The record id of the entity
     *  @return array The field values of that given record id
     */
    public function getFieldValue($recordId)
    {
        $customFieldValueTable = $this->CustomFieldValues;
        $customFieldsForeignKey = $customFieldValueTable->CustomFields->getForeignKey();
        $customRecordsForeignKey = $customFieldValueTable->CustomRecords->getForeignKey();

        $selectedColumns = [
            $customFieldValueTable->aliasField($customFieldsForeignKey),
            'field_value' => '(GROUP_CONCAT((CASE WHEN '.$customFieldValueTable->aliasField('text_value').' IS NOT NULL THEN '.$customFieldValueTable->aliasField('text_value')
                .' WHEN '.$customFieldValueTable->aliasField('number_value').' IS NOT NULL THEN '.$customFieldValueTable->aliasField('number_value')
                .' WHEN '.$customFieldValueTable->aliasField('decimal_value').' IS NOT NULL THEN '.$customFieldValueTable->aliasField('decimal_value')
                .' WHEN '.$customFieldValueTable->aliasField('textarea_value').' IS NOT NULL THEN '.$customFieldValueTable->aliasField('textarea_value')
                .' WHEN '.$customFieldValueTable->aliasField('date_value').' IS NOT NULL THEN '.$customFieldValueTable->aliasField('date_value')
                .' WHEN '.$customFieldValueTable->aliasField('time_value').' IS NOT NULL THEN '.$customFieldValueTable->aliasField('time_value')
                .' END) SEPARATOR \',\'))'
        ];

        // Getting the custom field table
        $customFieldsTable = $customFieldValueTable->CustomFields;

        // Getting the custom field values group by the record id, and then group by the field ids
        // Record with similar record id and field ids will be group concat together
        // For example: for checkbox, record id: 1, field id: 1, value: 1 and record id: 1, field id: 1, value: 2 will be
        // group as record id: 1, field id: 1, value: 1,2
        $fieldValue = $customFieldsTable
            ->find('list', [
                'keyField' => $customFieldValueTable->aliasField($customFieldsForeignKey),
                'valueField' => 'field_value',
            ])
            ->innerJoin(
                [$customFieldValueTable->getAlias() => $customFieldValueTable->getTable()],
                [$customFieldValueTable->aliasField($customFieldsForeignKey).'='.$customFieldsTable->aliasField('id')]
            )
            ->select($selectedColumns)
            ->where([$customFieldValueTable->aliasField($customRecordsForeignKey) => $recordId])
            ->group([$customFieldValueTable->aliasField($customFieldsForeignKey)])
            ->toArray();

        return $fieldValue;
    }

    public function copyCustomFields($copyFrom, $copyTo, $generalOnly = false)
    {
        // default is all
        $model = $this->_table;
        $registryAlias = $model->getRegistrygetAlias();

        $primaryKey = $model->getPrimaryKey();
        $idKey = $model->aliasField($primaryKey);

        $fieldKey = $this->getConfig('fieldKey');
        $formKey = $this->getConfig('formKey');
        $filterKey = $this->getConfig('filterKey');
        $recordKey = $this->getConfig('recordKey');
        $supportTableType = !is_null($this->getConfig('tableCellClass')) ? true: false;

        if ($model->exists([$idKey => $copyFrom]) && $model->exists([$idKey => $copyTo])) {
            $query = $model->find()->contain(['CustomFieldValues'])->where([$idKey => $copyFrom]);
            if ($supportTableType) {
                $query->contain(['CustomTableCells']);
            }
            $entity = $query->first();
            $requestData = $entity->toArray();

            $newEntity = $model->find()->where([$idKey => $copyTo])->first();
            $newRequestData = $newEntity->toArray();

            $customFieldQuery = $this->getCustomFieldQuery($entity, ['generalOnly' => $generalOnly]);
            $customFields = $customFieldQuery->toArray();
            $fieldIds = $model->array_column($customFields, $fieldKey);

            $ignoreFields = ['id', 'modified_user_id', 'modified', 'created_user_id', 'created'];
            if (isset($requestData['custom_field_values'])) { // POCOR-8542
                $newRequestData['custom_field_values'] = [];
                foreach ($requestData['custom_field_values'] as $key => $fieldValue) {
                    if (in_array($fieldValue[$fieldKey], $fieldIds)) {
                        foreach ($ignoreFields as $field) {
                            unset($fieldValue[$field]);
                        }
                        $fieldValue[$recordKey] = $newEntity->id;
                        $newRequestData['custom_field_values'][] = $fieldValue;
                    }
                }
            }

            if (isset($requestData['custom_table_cells'])) { // POCOR-8542
                $newRequestData['custom_table_cells'] = [];
                foreach ($requestData['custom_table_cells'] as $key => $fieldCell) {
                    if (in_array($fieldCell[$fieldKey], $fieldIds)) {
                        foreach ($ignoreFields as $field) {
                            unset($fieldCell[$field]);
                        }
                        $fieldCell[$recordKey] = $newEntity->id;
                        $newRequestData['custom_table_cells'][] = $fieldCell;
                    }
                }
            }

            $newEntity = $model->patchEntity($newEntity, $newRequestData, ['validate' => false]);
            $model->save($newEntity);
        }
    }

    private function setToolbarButtons(ArrayObject $toolbarButtons, array $attr, $action)
    {
        if ($this->getConfig('tabSection')) {
            if ($action == 'view') {
                if ($toolbarButtons->offsetExists('back')) {
                    if (isset($toolbarButtons['back']['url']['tab_section'])) {
                        unset($toolbarButtons['back']['url']['tab_section']);
                    }
                }
            }elseif ($action == 'edit') {
                if ($toolbarButtons->offsetExists('list')) {
                    if (isset($toolbarButtons['list']['url']['tab_section'])) {
                        unset($toolbarButtons['list']['url']['tab_section']);
                    }
                }
            }
        }
    }

    private function text($data, $fieldInfo, $options = [])
    {
        if (isset($data[$fieldInfo['id']])) {
            return $data[$fieldInfo['id']];
        } else {
            return '';
        }
    }

    private function number($data, $fieldInfo, $options = [])
    {
        if (isset($data[$fieldInfo['id']])) {
            return $data[$fieldInfo['id']];
        } else {
            return '';
        }
    }

    private function decimal($data, $fieldInfo, $options = [])
    {
        if (isset($data[$fieldInfo['id']])) {
            return $data[$fieldInfo['id']];
        } else {
            return '';
        }
    }

    private function textarea($data, $fieldInfo, $options = [])
    {
        if (isset($data[$fieldInfo['id']])) {
            return $data[$fieldInfo['id']];
        } else {
            return '';
        }
    }

    private function dropdown($data, $fieldInfo, $options = [])
    {
        if (isset($data[$fieldInfo['id']])) {
            if (isset($options[$data[$fieldInfo['id']]])) {
                return $options[$data[$fieldInfo['id']]];
            } else {
                return '';
            }
        } else {
            return '';
        }
    }

    private function checkbox($data, $fieldInfo, $options = [])
    {
        if (isset($data[$fieldInfo['id']])) {
            $values = explode(",", $data[$fieldInfo['id']]);
            $returnValue = '';
            foreach ($values as $value) {
                if (isset($options[$value])) {
                    if (empty($returnValue)) {
                        $returnValue = $options[$value];
                    } else {
                        $returnValue = $returnValue.', '.$options[$value];
                    }
                }
            }
            return $returnValue;
        } else {
            return '';
        }
    }

    private function date($data, $fieldInfo, $options = [])
    {
        if (isset($data[$fieldInfo['id']])) {
            $date = $data[$fieldInfo['id']];
            return $this->_table->formatDate(new Date($date));
        } else {
            return '';
        }
    }

    private function time($data, $fieldInfo, $options = [])
    {
        if (isset($data[$fieldInfo['id']])) {
            $time = date_create_from_format('G:i:s', $data[$fieldInfo['id']]);
            return $this->_table->formatTime(new Time($time));
        } else {
            return '';
        }
    }

    private function student_list($data, $fieldInfo, $options = [])
    {
        return null;
    }

    // public function table($data, $fieldInfo, $options = []):  Table|string
    // {
    //     $id = $fieldInfo['id'];
    //     $colId = $fieldInfo['col_id'];
    //     $rowId = $fieldInfo['row_id'];
    //     if (isset($data[$id][$colId][$rowId])) {
    //         return $data[$id][$colId][$rowId];
    //     }
    //     return '';
    // }

    public function getCustomField($data, $fieldInfo, $options = []) //POCOR8409
    {
        $id = $fieldInfo['id'];
        $colId = $fieldInfo['col_id'];
        $rowId = $fieldInfo['row_id'];
        if (isset($data[$id][$colId][$rowId])) {
            return $data[$id][$colId][$rowId];
        }
        return '';
    }
    private function coordinates($data, $fieldInfo, $options = [])
    {
        $coordinates = '';

        if (!empty($data[$fieldInfo['id']])) {
            $coordinates = preg_replace('/[{}""]/', '', $data[$fieldInfo['id']]);
            $coordinates = preg_replace('/,/', ' | ', $coordinates);
        }

        return $coordinates;
    }
    //POCOR-2135 start
    private function staff_list($data, $fieldInfo, $options = [])
    {
        return null;
    }
    //POCOR-2135 end
    //POCOR-8538 start
    public function setUpFieldOrderForClasses($fieldOrder){

        $position = array_search('institution_shift_id', $fieldOrder);
        $customFields = array_values(array_filter($fieldOrder, fn($field) => strpos($field, 'custom') === 0));
        $fieldOrder = array_values(array_filter($fieldOrder, fn($field) => strpos($field, 'custom') !== 0));
        array_splice($fieldOrder, $position + 1, 0, $customFields);
        return $fieldOrder;
    }
    //POCOR-8538 end
}
