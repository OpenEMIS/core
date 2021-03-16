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

    public function initialize(array $config)
    {
        parent::initialize($config);
        if (is_null($this->config('moduleKey'))) {
            $this->_table->belongsTo('CustomForms', $this->config('formClass'));
        }
        $this->_table->hasMany('CustomFieldValues', $this->config('fieldValueClass'));
        $this->CustomFieldValues = $this->_table->CustomFieldValues;

        if (!is_null($this->config('tableCellClass'))) {
            $this->_table->hasMany('CustomTableCells', $this->config('tableCellClass'));
            $this->CustomTableCells = $this->_table->CustomTableCells;
        }
        $this->firstTabName = null;
        $this->CustomModules = TableRegistry::get('CustomField.CustomModules');
        $this->CustomFieldTypes = TableRegistry::get('CustomField.CustomFieldTypes');

        $this->CustomFields = $this->CustomFieldValues->CustomFields;
        $this->CustomFieldOptions = $this->CustomFieldValues->CustomFields->CustomFieldOptions;
        $this->CustomForms = $this->CustomFields->CustomForms;
        $this->CustomFormsFields = TableRegistry::get($this->config('formFieldClass.className'));
        $this->CustomFormsFilters = TableRegistry::get($this->config('formFilterClass.className'));

        // Each field type will have one behavior attached
        $this->_table->addBehavior('CustomField.RenderText');
        $this->_table->addBehavior('CustomField.RenderNumber');
        $this->_table->addBehavior('CustomField.RenderDecimal');
        $this->_table->addBehavior('CustomField.RenderTextarea');
        $this->_table->addBehavior('CustomField.RenderDropdown');
        $this->_table->addBehavior('CustomField.RenderCheckbox');
        $this->_table->addBehavior('CustomField.RenderTable');
        $this->_table->addBehavior('CustomField.RenderDate');
        $this->_table->addBehavior('CustomField.RenderTime');
        $this->_table->addBehavior('CustomField.RenderStudentList');
        $this->_table->addBehavior('CustomField.RenderCoordinates');
        $this->_table->addBehavior('CustomField.RenderFile');
        $this->_table->addBehavior('CustomField.RenderRepeater');
        $this->_table->addBehavior('CustomField.RenderNote');
        // End

        // If tabSection is not set, added to handle Section Header
        if (!$this->config('tabSection')) {
            $this->_table->addBehavior('OpenEmis.Section');
        }

        $model = $this->config('model');
        if (empty($model)) {
            $this->config('model', $this->_table->registryAlias());
        }
    }

    private function isCAv4()
    {
        return isset($this->_table->CAVersion) && $this->_table->CAVersion=='4.0';
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events = array_merge($events, $this->config('events'));
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
        if (!is_null($this->config('tableCellClass'))) {
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
        // add here to make view has the same format in edit
        $this->formatEntity($entity);
        $this->setupCustomFields($entity);
        // check if the query string contains tab_section if tab_section exists for a particular survey
        if (!(isset($model->request->query['tab_section'])) && $this->firstTabName) {
            $model->request->query['tab_section'] = $this->firstTabName;
        }
    }

    public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $model = $this->_table;
        $alias = $model->alias();

        if (array_key_exists($alias, $data)) {
            $CustomFields = TableRegistry::get($this->config('fieldClass.className'));

            // patch custom_field_values
            if (array_key_exists('custom_field_values', $data[$alias])) {
                $values = $data[$alias]['custom_field_values'];
                $fieldValues = $model->array_column($values, $this->config('fieldKey'));
                $fieldResults = $CustomFields->find()
                    ->where(['id IN' => $fieldValues])
                    ->all();

                $fields = [];
                foreach ($fieldResults as $f) {
                    $fields[$f->id] = $f;
                }

                foreach ($values as $key => $attr) {
                    $fieldId = $attr[$this->config('fieldKey')];
                    $thisField = array_key_exists($fieldId, $fields) ? $fields[$fieldId] : null;
                    if (!is_null($thisField)) {
                        $data[$alias]['custom_field_values'][$key]['field_type'] = $thisField->field_type;
                        $data[$alias]['custom_field_values'][$key]['mandatory'] = $thisField->is_mandatory;
                        $data[$alias]['custom_field_values'][$key]['unique'] = $thisField->is_unique;
                        $data[$alias]['custom_field_values'][$key]['params'] = $thisField->params;

                        // logic to patch request data
                        $fieldType = Inflector::camelize(strtolower($thisField->field_type));
                        $settings = new ArrayObject([
                            'recordKey' => $this->config('recordKey'),
                            'fieldKey' => $this->config('fieldKey'),
                            'tableColumnKey' => $this->config('tableColumnKey'),
                            'tableRowKey' => $this->config('tableRowKey'),
                            'customValue' => $attr
                        ]);
                        $event = $model->dispatchEvent('Render.patch'.$fieldType.'Values', [$entity, $data, $settings], $model);
                        if ($event->isStopped()) {
                            return $event->result;
                        }
                        // End
                    }
                }
            }
            // end

            // patch custom_table_cells
            $tableCells = [];
            $deleteTableCells = [];
            if (array_key_exists('custom_table_cells', $data[$alias])) {
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
                    'recordKey' => $this->config('recordKey'),
                    'fieldKey' => $this->config('fieldKey'),
                    'tableColumnKey' => $this->config('tableColumnKey'),
                    'tableRowKey' => $this->config('tableRowKey'),
                    'customValue' => [
                        'customField' => null,
                        'cellValues' => []
                    ],
                    'tableCells' => [],
                    'deleteTableCells' => []
                ]);
                foreach ($cells as $fieldId => $rows) {
                    $thisField = array_key_exists($fieldId, $fields) ? $fields[$fieldId] : null;
                    if (!is_null($thisField)) {
                        $settings['customValue']['customField'] = $thisField;
                        $settings['customValue']['cellValues'] = $rows;

                        $event = $model->dispatchEvent('Render.patchTableValues', [$entity, $data, $settings], $model);
                        if ($event->isStopped()) {
                            return $event->result;
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
            if (!is_null($this->config('tableCellClass'))) {
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
        if (!(isset($model->request->query['tab_section'])) && $this->firstTabName) {
            $model->request->query['tab_section'] = $this->firstTabName;
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
        $process = function ($model, $entity) use ($data) {
            try {
                $repeaterSuccess = true;
                $repeaterErrors = false;
                $errors = $entity->errors();

                $fileErrors = [];
                $session = $model->request->session();
                $sessionErrors = $model->registryAlias().'.parseFileError';
                if ($session->check($sessionErrors)) {
                    $fileErrors = $session->read($sessionErrors);
                }

                if (empty($errors) && empty($fileErrors)) {
                    $settings = new ArrayObject([
                        'recordKey' => $this->config('recordKey'),
                        'fieldKey' => $this->config('fieldKey'),
                        'formKey' => $this->config('formKey'),
                        'tableColumnKey' => $this->config('tableColumnKey'),
                        'tableRowKey' => $this->config('tableRowKey'),
                        'valueKey' => null,
                        'customValue' => null,
                        'fieldValues' => [],
                        'tableCells' => $data[$model->alias()]['custom_table_cells'],
                        'deleteFieldIds' => []
                    ]);

                    if (array_key_exists($model->alias(), $data)) {
                        if (array_key_exists('custom_field_values', $data[$model->alias()])) {
                            $values = $data[$model->alias()]['custom_field_values'];
                            foreach ($values as $key => $obj) {
                                $fieldType = Inflector::camelize(strtolower($obj['field_type']));
                                $settings['customValue'] = $obj;

                                $event = $model->dispatchEvent('Render.process'.$fieldType.'Values', [$entity, $data, $settings], $model);
                                if ($event->isStopped()) {
                                    return $event->result;
                                }
                            }
                        }
                    }

                    //calling processRepeaterValues() in RenderRepeaterBehavior
                    if ($this->_table->hasBehavior('RenderRepeater')) {
                        if (array_key_exists($model->alias(), $data)) {
                            if (array_key_exists('institution_repeater_surveys', $data[$model->alias()])) {
                                $event = $model->dispatchEvent('Render.processRepeaterValues', [$entity, $data, $settings], $model);
                                if ($event->isStopped()) {
                                    return $event->result;
                                }
                            }
                        }
                    }
                    $data[$model->alias()]['custom_field_values'] = $settings['fieldValues'];

                    $conn = ConnectionManager::get('default');
                    $conn->begin();

                    // POCOR-4799 Modified to only delete all dependent answers only if the selected value is not the show_options value in SurveyRules.
                    if ($model->alias() == 'InstitutionSurveys') {
                        $entityCustomFieldValues = [];
                        foreach ($entity->custom_field_values as $key => $value) {
                            $entityCustomFieldValues[$value['survey_question_id']] = $value;
                        }
                        if (is_null($this->config('moduleKey'))) {
                            if (isset($data[$this->_table->alias()][$this->config('formKey')])) {
                                $surveyFormId = $data[$this->_table->alias()][$this->config('formKey')];
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
                                        if (isset($entityCustomFieldValues[$rule->dependent_question_id]) && !in_array($entityCustomFieldValues[$rule->dependent_question_id]['number_value'], $ruleShowOptions)) {
                                            $settings['deleteFieldIds'][] = $rule->survey_question_id;
                                            foreach ($data[$model->alias()]['custom_field_values'] as $key => $value) {
                                                if ($value['survey_question_id'] == $rule->survey_question_id) {
                                                    unset($data[$model->alias()]['custom_field_values'][$key]);
                                                }
                                            }
                                        }
                                        $data[$model->alias()]['custom_field_values'] = array_values($data[$model->alias()]['custom_field_values']);
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
                            if (!is_null($this->config('tableCellClass'))) {
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
                                if (array_key_exists($fieldId, $entity->institution_repeaters)) {
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
                            if (!empty($surveyIds) && array_key_exists('repeaterValues', $settings)) {
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

                        if(array_key_exists('repeaterValues', $settings)){
                            foreach ($settings['repeaterValues'] as $key => $value) {
                                $surveyEntity = $RepeaterSurveys->newEntity($value);
                                $all[] = $surveyEntity;
                                if ($RepeaterSurveys->save($surveyEntity)) {
                                } else {
                                    Log::write('debug', $surveyEntity->errors());
                                    $repeaterErrors = true;
                                    $repeaterSuccess = false;
                                }
                            }

                            //pass the entity with repeater errors back to onGetCustomRepeaterElement for rendering the error messages
                            $entity['institution_repeater_surveys_error_obj'] = $all;
                            //if any validation error is found for repeater, display error message
                            if($repeaterErrors){
                                $entity->errors('institution_repeater_surveys', '');
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
                    if (array_key_exists('custom_field_values', $errors)) {
                        if ($entity->has('custom_field_values')) {
                            foreach ($entity->custom_field_values as $key => $obj) {
                                $fieldId = $obj->{$this->config('fieldKey')};

                                if (array_key_exists($key, $errors['custom_field_values'])) {
                                    $indexedErrors[$fieldId] = $errors['custom_field_values'][$key];
                                    foreach ($fields as $field) {
                                        $entity->custom_field_values[$key]->dirty($field, true);
                                    }
                                }
                            }
                        }
                    }

                    $indexedErrors = $indexedErrors + $fileErrors;

                    if (!empty($indexedErrors)) {
                        if (array_key_exists($model->alias(), $data)) {
                            if (array_key_exists('custom_field_values', $data[$model->alias()])) {
                                foreach ($data[$model->alias()]['custom_field_values'] as $key => $obj) {
                                    $fieldId = $obj[$this->config('fieldKey')];

                                    if (array_key_exists($fieldId, $indexedErrors)) {
                                        foreach ($fields as $field) {
                                            if (array_key_exists($field, $indexedErrors[$fieldId])) {
                                                $error = $indexedErrors[$fieldId][$field];
                                                $entity->custom_field_values[$key]->errors($field, $error, true);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }

                    Log::write('debug', 'entity Errors:');
                    Log::write('debug', $entity->errors());
                    Log::write('debug', 'file Errors:');
                    Log::write('debug', $fileErrors);

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
        $associations = TableRegistry::get($filterAlias)->associations();
        foreach ($associations as $assoc) {
            if ($assoc->registryAlias() == $modelAlias) {
                $filterKey = $assoc->foreignKey();
                return $filterKey;
            }
        }
        return $filterKey;
    }

    public function getCustomFieldQuery($entity, $params = [])
    {
        $query = null;
        $withContain = array_key_exists('withContain', $params) ? $params['withContain'] : true;
        $generalOnly = array_key_exists('generalOnly', $params) ? $params['generalOnly'] : false;

        // For Institution Survey
        if (is_null($this->config('moduleKey'))) {
            if ($entity->has($this->config('formKey'))) {
                $customFormId = $entity->{$this->config('formKey')};

                if (isset($customFormId)) {
                    $customFormQuery = $this->CustomForms
                        ->find('list', ['keyField' => 'id', 'valueField' => 'id'])
                        ->where([$this->CustomForms->aliasField('id') => $customFormId]);
                }
            }
        } else {
            $where = [$this->CustomModules->aliasField('model') => $this->config('model')];

            $results = $this->CustomModules
                ->find('all')
                ->where($where)
                ->first();

            if (!empty($results)) {
                $moduleId = $results->id;
                $filterAlias = $results->filter;

                $customFormQuery = $this->CustomForms
                    ->find('list', ['keyField' => 'id', 'valueField' => 'id'])
                    ->where([$this->CustomForms->aliasField($this->config('moduleKey')) => $moduleId]);

                if (!empty($filterAlias)) {
                    $filterKey = $this->getFilterKey($filterAlias, $this->config('model'));
                    if (empty($filterKey)) {
                        list($modelplugin, $modelAlias) = explode('.', $filterAlias, 2);
                        $filterKey = Inflector::underscore(Inflector::singularize($modelAlias)) . '_id';
                    }

                    $filterId = $entity->{$filterKey};

                    // conditions
                    $generalConditions = [
                        $this->CustomFormsFilters->aliasField($this->config('formKey') . ' = ') . $this->CustomForms->aliasField('id'),
                        $this->CustomFormsFilters->aliasField($this->config('filterKey')) => 0
                    ];
                    $filterConditions = [
                        $this->CustomFormsFilters->aliasField($this->config('formKey') . ' = ') . $this->CustomForms->aliasField('id'),
                        $this->CustomFormsFilters->aliasField($this->config('filterKey')) => $filterId
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
                            'table' => $this->CustomFormsFilters->table(),
                            'alias' => $this->CustomFormsFilters->alias(),
                            'conditions' => $conditions
                        ]);
                }
            }
        }

        if (!empty($customFormQuery)) {
            $customFormIds = $customFormQuery
                ->toArray();

            if (!empty($customFormIds)) {
                $query = $this->CustomFormsFields
                    ->find('all')
                    ->find('order')
                    ->where([
                        $this->CustomFormsFields->aliasField($this->config('formKey') . ' IN') => $customFormIds
                    ])
                    ->group([
                        $this->CustomFormsFields->aliasField($this->config('fieldKey'))
                    ]);

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

                        if (!is_null($this->config('tableColumnKey'))) {
                            $query->contain([
                                'CustomFields.CustomTableColumns' => function ($q) {
                                    return $q
                                        ->find('visible')
                                        ->find('order');
                                }
                            ]);
                        }

                        if (!is_null($this->config('tableRowKey'))) {
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

    public function formatEntity(Entity $entity)
    {
        $model = $this->_table;
        $primaryKey = $model->primaryKey();
        $idKey = $model->aliasField($primaryKey);
        $id = $entity->id;

        $values = [];
        if ($model->exists([$idKey => $id])) {
            $query = $model->find()->contain(['CustomFieldValues.CustomFields'])->where([$idKey => $id]);

            $newEntity = $query->first();
            if ($newEntity->has('custom_field_values')) {
                foreach ($newEntity->custom_field_values as $key => $obj) {
                    $fieldId = $obj->{$this->config('fieldKey')};
                    $customField = $obj->custom_field;

                    if ($customField->field_type == 'CHECKBOX') {
                        $checkboxValues = [$obj['number_value']];
                        if (array_key_exists($fieldId, $values)) {
                            $checkboxValues = array_merge($checkboxValues, $values[$fieldId]['number_value']);
                        }
                        $obj['number_value'] = $checkboxValues;
                    }
                    $values[$fieldId] = $obj;
                }
            }
        }

        $query = $this->getCustomFieldQuery($entity, ['withContain' => ['CustomFields']]);

        $fieldValues = [];  // values of custom field must be in sequence for validation errors to be placed correctly
        if (!is_null($query)) {
            $where =[];
            if ($entity->survey_form['custom_module_id'] == 1 && isset($model->request->query['tab_section'])){
                $tabSection = $model->request->query['tab_section'];
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
                $customField = $obj->custom_field;
                $fieldTypeCode = $customField->field_type;

                // only apply for field type store in custom_field_values
                if (in_array($fieldTypeCode, $this->fieldValueArray)) {
                    $fieldId = $customField->id;

                    if (array_key_exists($fieldId, $values)) {
                        $fieldValues[] = $values[$fieldId];
                    } else {
                        $valueData = [
                            'text_value' => null,
                            'number_value' => null,
                            'decimal_value' => null,
                            'textarea_value' => null,
                            'date_value' => null,
                            'time_value' => null,
                            $this->config('fieldKey') => $fieldId,
                            $this->config('recordKey') => $entity->id,
                            'custom_field' => null // set after data is patched else will lost
                        ];
                        $valueEntity = $this->CustomFieldValues->newEntity($valueData, ['validate' => false]);
                        $valueEntity->custom_field = $customField;
                        $fieldValues[] = $valueEntity;
                    }
                } else {
                    $fieldType = Inflector::camelize(strtolower($fieldTypeCode));
                    $settings = new ArrayObject([
                        'fieldKey' => $this->config('fieldKey'),
                        'formKey' => $this->config('formKey'),
                        'customField' => $customField
                    ]);

                    $event = $model->dispatchEvent('Render.format'.$fieldType.'Entity', [$entity, $settings], $model);
                    if ($event->isStopped()) {
                        return $event->result;
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
        $session = $model->request->session();
        $query = $this->getCustomFieldQuery($entity);

        // If tabSection is set, setup Tab Section
        if ($this->config('tabSection')) {
            $customFields = $query->toArray();

            $tabElements = [];
            if ($this->isCAv4()) {
                $action = $model->action;
            } else {
                $action = $ControllerAction->action();
            }
            $url = $ControllerAction->url($action);
            $sectionName = null;
            foreach ($customFields as $key => $obj) {
                if (isset($obj->section)) {
                    if ($sectionName != $obj->section) {
                        $sectionName = $obj->section;
                        $tabName = Inflector::slug($sectionName);
                        // set the first tab section into a global variable
                        if (is_null($this->firstTabName)) {
                            $this->firstTabName = $tabName;
                        }
                        if (empty($tabElements)) {
                            $selectedAction = $tabName;
                        }
                        $url['tab_section'] = $tabName;
                        $tabElements[$tabName] = [
                            'url' => $url,
                            'text' => $sectionName,
                        ];
                    }
                }
            }

            if (!empty($tabElements)) {
                $selectedAction = !is_null($model->request->query('tab_section')) ? $model->request->query('tab_section') : $selectedAction;
                // $model->controller->TabPermission->checkTabPermission($tabElements);
                $model->controller->set('tabElements', $tabElements);
                $model->controller->set('selectedAction', $selectedAction);

                $query->where([
                    $this->CustomFormsFields->aliasField('section') => $tabElements[$selectedAction]['text']
                ]);
            }
        }
        // End

        // For survey only
        // To get the rules for the survey form
        if (is_null($this->config('moduleKey')) && $this->_table->action == 'view') {
            $SurveyRules = TableRegistry::get('Survey.SurveyRules');
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
                    if (array_key_exists($order, $fieldOrder)) {
                        $order++;
                    }
                    $fieldOrder[$order] = $fieldName;
                }
            }
            // retrieve saved values
            $values = new ArrayObject([]);
            $cells = new ArrayObject([]);

            if (isset($entity->id)) {
                $fieldKey = $this->config('fieldKey');
                $tableRowKey = $this->config('tableRowKey');
                $tableColumnKey = $this->config('tableColumnKey');

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
                                    'recordKey' => $this->config('recordKey'),
                                    'fieldKey' => $this->config('fieldKey'),
                                    'tableColumnKey' => $this->config('tableColumnKey'),
                                    'tableRowKey' => $this->config('tableRowKey'),
                                    'customValue' => $obj
                                ]);
                                $event = $model->dispatchEvent('Render.on'.$fieldType.'Initialize', [$entity, $settings], $model);
                                if ($event->isStopped()) {
                                    return $event->result;
                                }
                                // End
                            } else if ($model->request->is(['post', 'put'])) {
                                // onPost, no actions
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
            $sectionName = null;
            foreach ($customFields as $key => $obj) {
                // If tabSection is not set, setup Section Header
                if (!$this->config('tabSection')) {
                    if (isset($obj->section)) {
                        if ($sectionName != $obj->section) {
                            $sectionName = $obj->section;
                            $fieldName = "section_".$key."_header";

                            if (!empty($sectionName)) {
                                $ControllerAction->field($fieldName, ['type' => 'section', 'title' => $sectionName]);
                                $fieldOrder[++$order] = $fieldName;
                            }
                        }
                    }
                }
                // End

                $customField = $obj->custom_field;

                $fieldType = $customField->field_type;
                $fieldName = "custom_".$key."_field";
                $valueClass = strtolower($fieldType) == 'table' || strtolower($fieldType) == 'student_list' ? 'table-full-width' : '';

                $attr = [
                    'type' => 'custom_'. strtolower($fieldType),
                    'attr' => [
                        'label' => $customField->name,
                        'fieldKey' => $this->config('fieldKey'),
                        'formKey' => $this->config('formKey'),
                        'tableColumnKey' => $this->config('tableColumnKey'),
                        'tableRowKey' => $this->config('tableRowKey')
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
                if (is_null($this->config('moduleKey')) && $this->_table->action == 'view') {
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

            foreach ($ignoreFields as $key => $field) {
                // add checking (map_section, map) to append ignore fields only if exists
                if (array_key_exists($field, $this->_table->fields)) {
                    $fieldOrder[++$order] = $field;
                }
            }
            ksort($fieldOrder);
            $ControllerAction->setFieldOrder($fieldOrder);
        }
    }

    private function deleteUploadSessions()
    {
        $model = $this->_table;
        $session = $model->request->session();
        $session->delete($model->registryAlias().'.parseFile');
        $session->delete($model->registryAlias().'.parseFileError');
    }

    // Model.excel.onExcelBeforeStart
    public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets)
    {
        $optionsValues = $this->CustomFieldOptions->find('list')->toArray();
        $sheets[] = [
            'name' => $this->_table->alias(),
            'table' => $this->_table,
            'query' => $this->_table->find(),
            'customFieldOptions' => $optionsValues,
        ];
    }

    // Model.excel.onExcelUpdateFields
    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $recordId = $settings['id'];
        $entity = $this->_table->get($recordId);

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

        // Set the fetched table cell values to avoid multiple call to the database
        $tableCellValues = $this->getTableCellValues($tableCustomFieldIds, $entity->id);

        // Set the fetched field values to avoid multiple call to the database
        $fieldValues = $this->getFieldValue($entity->id) + $tableCellValues;
        ksort($fieldValues);
        $this->_fieldValues = $fieldValues;
    }

    private function getTableCellValues($tableCustomFieldIds, $recordId)
    {
        if (!empty($tableCustomFieldIds)) {
            $TableCellTable = $this->CustomTableCells;
            $customFieldsForeignKey = $TableCellTable->CustomFields->foreignKey();
            $customRecordsForeignKey = $TableCellTable->CustomRecords->foreignKey();
            $customColumnForeignKey = $TableCellTable->CustomTableColumns->foreignKey();
            $customRowForeignKey = $TableCellTable->CustomTableRows->foreignKey();
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
        if (!empty($this->_fieldValues)) {
            $answer = '';
            $type = strtolower($attr['customField']['field_type']);
            if (method_exists($this, $type)) {
                $ans = $this->$type($this->_fieldValues, $attr['customField'], $this->_customFieldOptions);
                if (!(is_null($ans))) {
                    $answer = $ans;
                }
            }
            return $answer;
        } else {
            return '';
        }
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
        $customFieldsForeignKey = $customFieldValueTable->CustomFields->foreignKey();
        $customRecordsForeignKey = $customFieldValueTable->CustomRecords->foreignKey();

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
                [$customFieldValueTable->alias() => $customFieldValueTable->table()],
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
        $registryAlias = $model->registryAlias();

        $primaryKey = $model->primaryKey();
        $idKey = $model->aliasField($primaryKey);

        $fieldKey = $this->config('fieldKey');
        $formKey = $this->config('formKey');
        $filterKey = $this->config('filterKey');
        $recordKey = $this->config('recordKey');
        $supportTableType = !is_null($this->config('tableCellClass')) ? true: false;

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
            if (array_key_exists('custom_field_values', $requestData)) {
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

            if (array_key_exists('custom_table_cells', $requestData)) {
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
        if ($this->config('tabSection')) {
            if ($action == 'view') {
                if ($toolbarButtons->offsetExists('back')) {
                    if (array_key_exists('tab_section', $toolbarButtons['back']['url'])) {
                        unset($toolbarButtons['back']['url']['tab_section']);
                    }
                }
            }elseif ($action == 'edit') {
                if ($toolbarButtons->offsetExists('list')) {
                    if (array_key_exists('tab_section', $toolbarButtons['list']['url'])) {
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

    private function table($data, $fieldInfo, $options = [])
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

}
