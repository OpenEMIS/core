<?php

namespace Report\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Event\EventInterface;
use Cake\Utility\Inflector;
use Cake\ORM\Table;
use Cake\Log\Log; // POCOR-9116
use Cake\Datasource\ConnectionManager;

class CustomFieldListBehavior extends Behavior
{
    protected $_defaultConfig = [
        'events' => [
            'Model.excel.onExcelBeforeStart' => ['callable' => 'onExcelBeforeStart', 'priority' => 100],
            'Model.excel.onExcelUpdateFields' => ['callable' => 'onExcelUpdateFields', 'priority' => 110],
            'Model.excel.onExcelRenderCustomField' => ['callable' => 'onExcelRenderCustomField', 'priority' => 120],
            // 'Model.excel.onExcelUpdateRow' => ['callable' => 'onExcelUpdateRow', 'priority' => 120],
        ],
        'moduleKey' => 'custom_module_id',
        'formKey' => 'custom_form_id',
        'model' => null,
        'formFilterClass' => ['className' => 'CustomField.CustomFormsFilters'],
        'fieldValueClass' => ['className' => 'CustomField.CustomFieldValues', 'foreignKey' => 'custom_record_id', 'dependent' => true, 'cascadeCallbacks' => true],
        'tableCellClass' => ['className' => 'CustomField.CustomTableCells', 'foreignKey' => 'custom_record_id', 'dependent' => true, 'cascadeCallbacks' => true, 'saveStrategy' => 'replace'],
        'condition' => [],
    ];

    private $_condition = [];
    private $_tmpFieldValues = [];
    private $_customFieldOptionsList = [];

    public function initialize(array $config): void
    {
        $this->CustomFormsFilters = null;
        $formFilterClass = $this->getConfig('formFilterClass');
        if (!empty($formFilterClass)) {
            $configClass = $this->getConfig('formFilterClass.className');

            $this->CustomFormsFilters = TableRegistry::getTableLocator()->get($configClass);
        }
        $configVal = $this->getConfig('fieldValueClass.className');
        $this->CustomFieldValues = TableRegistry::getTableLocator()->get($configVal);
        $configCell = $this->getConfig('tableCellClass.className');
        $this->CustomTableCells = TableRegistry::getTableLocator()->get($configCell);
        $this->CustomForms = $this->CustomFieldValues->CustomFields->CustomForms;
        $model = $this->getConfig('model');
        if (empty($model)) {
            $this->setConfig('model', $this->_table->getRegistryAlias());
        }
        $this->_condition = $this->getConfig('condition');
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events = array_merge($events, $this->getConfig('events'));
        return $events;
    }

    // Model.excel.onExcelBeforeStart
    public function onExcelBeforeStart(EventInterface $event, ArrayObject $settings, ArrayObject $sheets)
    {
        if (!(is_null($this->getConfig('moduleKey')))) {
            $filter = $this->getFilter($this->getConfig('model'));
            $types = $this->getType($filter);
            if (!is_null($filter)) {
                $filterKey = $this->getFilterKey($filter, $this->getConfig('model'));
                if (!empty($types)) {
                    foreach ($types as $key => $name) {
                        $this->excelContent($sheets, $name, $filterKey, $key);
                    }
                } else {
                    $name = $this->_table->getAlias();
                    $this->excelContent($sheets, $name);
                }
            } else { // POCOR-9126 start
//                Log::debug(__FUNCTION__ . '7'); // POCOR-9510 removed logging
                $name = $this->_table->getAlias();
                $this->excelContent($sheets, $name);
            } // POCOR-9126 end
        } else {
            // For Surveys only
            $forms = $this->getForms();
            foreach ($forms as $formId => $formName) {
                $this->excelContent($sheets, $formName, null, $formId);
            }
        }
    }

    // Model.excel.onExcelUpdateFields

    /**
     * // POCOR-9116
     *    Function to get the filter of the given model
     *
     * @param string $model The code of of the custom module
     * @return string Filter of the custom module
     */
    public function getFilter($model)
    {
        $CustomModuleTable = TableRegistry::getTableLocator()->get('CustomField.CustomModules');
        $filter = $CustomModuleTable
            ->find()
            ->where([$CustomModuleTable->aliasField('model') => $model])
            ->first();

        if (empty($filter)) {
            $filter = null;
        } else {
            $filter = $filter->filter;
        }

        return $filter;
    }

    // Model.excel.onExcelRenderCustomField

    /**
     *    Function to get the filter type list
     *
     * @param string $filter custom field filter
     * @return array The list of filter types
     */
    public function getType($filter)
    {
        if (!(is_null($filter))) {
            $types = TableRegistry::getTableLocator()->get($filter)->getList()->toArray();
            return $types;
        } else {
            return null;
        }
    }

    /**
     *    Function to get the filter key from the filter specified
     *
     * @param string $filter The filter provided by the custom module
     * @param string $model The model provided by the custom module
     * @return The filter foreign key name if found. If not it will return empty.
     */
    public function getFilterKey($filter, $model)
    {
        $filterKey = '';
        if (isset($filter)) {
            $associations = TableRegistry::getTableLocator()->get($filter)->associations();
            foreach ($associations as $assoc) {
                if ($assoc->getRegistryAlias() == $model) {
                    $filterKey = $assoc->getForeignKey();
                    return $filterKey;
                }
            }
            return $filterKey;
        }
    }

    public function excelContent(ArrayObject $sheets, $name, $filterKey = null, $key = null)
    {
        $query = $this->_table->find();

        // If the filter is present
        if (!(is_null($filterKey))) {
            $query->where([$this->_table->aliasField($filterKey) => $key]);
        }

        // If there is any specified query condition
        $condition = $this->_condition;
        $query->where($condition);
        // If it is a survey
        if (is_null($this->getConfig('moduleKey'))) {
            $query->where([$this->_table->aliasField($this->getConfig('formKey')) => $key]);
        }

        // Getting the list of available custom field options
        $optionsValues = $this->CustomFieldValues->CustomFields->CustomFieldOptions->find('list')->toArray();
//        Log::debug(print_r([__FUNCTION__ => $optionsValues], true));
        // The excel spreadsheets
        $sheets[$name] = [
            'name' => __($name),
            'table' => $this->_table,
            'query' => $query,
            'orientation' => 'landscape',
            'filterKey' => $filterKey,
            'key' => $key,
            'customFieldOptions' => $optionsValues,
        ];
    }

    /**
     *    Function to get the form ids. Use for surveys only.
     *
     * @param int $formId | null The form id if required to get a specific form
     * @return array Form ID of Form Names
     */
    public function getForms($formId = null)
    {
        $formKeyAlias = $this->_table->aliasField($this->getConfig('formKey'));
        $configCondition = $this->getCondition();
        if (!(is_null($formId))) {
            $condition = [$formKeyAlias => $formId];
            $this->setCondition(array_merge($configCondition, $condition));
        } else {
            $formId = $configCondition[$formKeyAlias] ?? -1;
        }

        $SurveyFormsTable = TableRegistry::getTableLocator()->get('Survey.SurveyForms');
        return $SurveyFormsTable
            ->find('list', [
                'keyField' => 'id',
                'valueField' => 'name'
            ])
            ->where([$SurveyFormsTable->aliasField('id') => $formId])
            ->group(['id'])
            ->toArray();
    }

    /**
     *    Function to get the query condition
     *
     * @return array The current condition
     */
    public function getCondition()
    {
        return $this->_condition;
    }

    /**
     *    Function to set the query condition
     *
     * @param array The new query condition
     * @return array The current condition
     */
    public function setCondition(array $condition)
    {
        $this->_condition = $condition;
        return $this->_condition;
    }

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, ArrayObject $fields)
    {
        if (isset($settings['sheet']['customFieldOptions'])) {
            $this->setCustomFieldOptionsList($settings['sheet']['customFieldOptions']);
        }
        $filterValue = null;
        if (isset($settings['sheet']['key'])) {
            $filterValue = $settings['sheet']['key'];
        }
        $excelFields = $fields->getArrayCopy();
        $customFields = $this->getCustomFields($filterValue);
        $tableCustomFieldIds = [];
        $excelFields = array_values($excelFields);
        $fieldCount = count($excelFields);

        foreach ($customFields as $customField) {
            if ($customField['field_type'] != 'TABLE') {
                $field['key'] = 'CustomField';
                $field['field'] = 'custom_field';
                $field['type'] = 'custom_field';
                $field['label'] = $customField['name'];
                $field['customField'] = ['id' => $customField['id'], 'field_type' => $customField['field_type']];

                if ($customField['field_type'] == 'DECIMAL') {
                    $field['formatting'] = 'string';
                } else {
                    $field['formatting'] = 'GENERAL';
                }

                $excelFields[] = $field;
            } else {
                $tableCustomFieldIds[] = $customField['id'];
                $tableRow = $customField->custom_table_rows;
                $tableCol = $customField->custom_table_columns;

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

                if (sizeof($row) != 0 && sizeof($col) != 0) {
                    for ($i = 1; $i < sizeof($col); $i++) {
                        foreach ($row as $rw) {
                            $field['key'] = 'CustomField';
                            $field['field'] = 'custom_field';
                            $field['type'] = 'custom_field';
                            $field['label'] = $customField['name'] . ' (' . $col[$i]['name'] . ', ' . $rw['name'] . ')';
                            $field['customField'] = ['id' => $customField['id'],
                                'field_type' => $customField['field_type'],
                                'col_id' => $col[$i]['id'],
                                'row_id' => $rw['id']];
                            $excelFields[] = $field;
                        }
                    }
                }
            }
        }
        //POCOR-8562[START]
        $excelFields = array_filter($excelFields, function ($fieldValue) {
            // Check if the 'customField' key exists and if the 'field_type' is 'REPEATER'
            if (isset($fieldValue['customField']) && $fieldValue['customField']['field_type'] === 'REPEATER') {
                return false; // Exclude this element
            }
            // Check if the 'customField' key exists and if the 'field_type' is 'STUDENT_LIST'
            if (isset($fieldValue['customField']) && $fieldValue['customField']['field_type'] === 'STUDENT_LIST') {
                return false; // Exclude this element
            }
            // Check if the 'customField' key exists and if the 'field_type' is 'STAFF_LIST'
            if (isset($fieldValue['customField']) && $fieldValue['customField']['field_type'] === 'STAFF_LIST') {
                return false; // Exclude this element
            }
            return true;
        });
        //POCOR-8562[END]
        if (!empty($tableCustomFieldIds)) {
            $excelFields[$fieldCount]['tableCustomFieldIds'] = $tableCustomFieldIds;
        }

        $fields->exchangeArray($excelFields);
        // Setting the list of options into the sheet for easier fetching

    }

    /**
     *    Function to set the customFieldOptions
     *
     * @param array The custom field option list
     */
    public function setCustomFieldOptionsList(array $customFieldOptions)
    {
        $this->_customFieldOptionsList = $customFieldOptions;
    }

    /**
     * // POCOR-9116
     *    Function to get the custom headers for each type of the filter
     *
     * @param int | null $formId The id value of the filterKey
     * @return array The value of the header and the custom fields
     */
    public function getCustomFields($formId = null)
    {
        $formKeyAlias = $this->_table->aliasField($this->getConfig('formKey'));
        $configCondition = $this->getCondition();
        if (!$formId) {
            $formId = $configCondition[$formKeyAlias] ?? -1;
        }

        $customFields = [];
        $customFormFields = [];
        $customModuleKey = $this->getConfig('moduleKey');
        if (is_null($customModuleKey)) {
            // Use for surveys
            $SurveyFormsTable = $this->CustomFieldValues->CustomRecords->SurveyForms;
            $customFormFields = $SurveyFormsTable
                ->find()
                ->contain(['CustomFields.CustomTableColumns', 'CustomFields.CustomTableRows'])
                ->where([$SurveyFormsTable->aliasField('id') => $formId]) // POCOR-9116
                ->toArray();
        } elseif (!(empty($formId))) {
            // If there is a filter specified
            $association = $this->CustomFormsFilters->CustomFilters;
            if ($association) {
                $customFilterKey = $association->getForeignKey();
                $customFormFields = $this->CustomFormsFilters
                    ->find()
                    ->where([$this->CustomFormsFilters->aliasField($customFilterKey) . ' IN' => [$formId, 0]])
                    ->contain(['CustomForms', 'CustomForms.CustomFields.CustomTableColumns', 'CustomForms.CustomFields.CustomTableRows'])
                    ->toArray();
            } else {
                $customFormFields = $this->CustomForms
                    ->find()
                    ->contain(['CustomFields.CustomTableColumns', 'CustomFields.CustomTableColumns'])
                    ->toArray();
            }
        } else {
            // If there is no filter specified
            $customFormFields = $this->CustomForms
                ->find()
                ->contain(['CustomFields.CustomTableColumns', 'CustomFields.CustomTableColumns'])
                ->toArray();
        }

        // Process each of the custom fields
        foreach ($customFormFields as $customFormField) {
            $fields = null;

            if (isset($customFormField['custom_fields'])) {
                $fields = $customFormField['custom_fields'];
            } elseif (isset($customFormField['custom_form']['custom_fields'])) {
                $fields = $customFormField['custom_form']['custom_fields'];
            }
            // POCOR-9265 start
            if (!empty($fields)) {
                // Detect if any field has an ->order property
                $hasOrder = false;
                foreach ($fields as $field) {
                    if (isset($field->_joinData->order)) {
                        $hasOrder = true;
                        break;
                    }
                }

                if ($hasOrder) {
                    // Sort by ->order, push missing ->order to the end
                    uasort($fields, function($a, $b) {
                        $oA = isset($a->_joinData->order) ? (int)$a->_joinData->order : PHP_INT_MAX;
                        $oB = isset($b->_joinData->order) ? (int)$b->_joinData->order : PHP_INT_MAX;
                        return $oA <=> $oB;
                    });
                } else {
                    // Fallback: sort by key (ID)
                    ksort($fields);
                }
                // POCOR-9265 end
            }
        }
        return $fields;
    }

    // Function to generate the excel content

    public function onExcelRenderCustomField(EventInterface $event, Entity $entity, array $attr) // POCOR-9116
    {
        // Getting the temporary field values that is set
        $tmpFieldValues = $this->getTmpFieldValues();
//        Log::debug(print_r(['tmpValues' => $tmpFieldValues],true));

        // If the field value is not for the particular record, refetch the field values and set
        // the temporary field values
        // This is to avoid multiple fetch to the database
        if (!array_key_exists($entity->id, $tmpFieldValues)) {
            $fieldValues = $this->getFieldValue($entity->id);
            if (isset($attr['tableCustomFieldIds'])) {
                $tableCellValues = $this->getTableCellValues($attr['tableCustomFieldIds'], $entity->id);
                $fieldValues = $fieldValues + $tableCellValues;

                if (!empty($tableCellValues)) {
                    if (isset($fieldValues[$entity->id])) {
                        $tmpArray = $fieldValues[$entity->id];
                        $tmpArray = $tmpArray + $tableCellValues;
                        ksort($tmpArray);
                        $fieldValues[$entity->id] = $tmpArray;
                    } else {
                        $fieldValues[$entity->id] = $tableCellValues;
                    }
                }

                ksort($fieldValues);
            }
            $tmpFieldValues = $this->setTmpFieldValues($fieldValues);
        }
        $customFieldAttributes = $attr['customField'];
//        Log::debug(print_r([
//            'id' => $entity->id,
//            'arr' => $customFieldAttributes,
//            'tmpvalues' => $tmpFieldValues],true));
        // Check if the temporary field value has this record information.
        $customFieldValues = $tmpFieldValues[$entity->id];
        if (isset($customFieldValues)) {
            $customFieldOptionsList = $this->getCustomFieldOptionsList();
            return $this->getCustomFieldValue($customFieldValues,
                $customFieldAttributes,
                $customFieldOptionsList);
        } else {
            return '';
        }
    }

    /**
     *    Function to get the temporary field values
     *
     * @return array The stored temporary field values
     */
    public function getTmpFieldValues()
    {
        return $this->_tmpFieldValues;
    }

    /**
     *    Function to get the field values base on a given record id
     *
     * @param int $recordId The record id of the entity
     * @return array The field values of that given record id
     */
    public function getFieldValue($recordId)
    {
        //POCOR-9182 START
        // Set group_concat_max_len to handle longer text values at the beginning
        $conn = ConnectionManager::get('default');
        $conn->execute('SET SESSION group_concat_max_len = 1048576'); // 1MB limit
        //POCOR-9182 END
        $customFieldValueTable = $this->CustomFieldValues;
        $customFieldsForeignKey = $customFieldValueTable->CustomFields->getForeignKey();
        $customRecordsForeignKey = $customFieldValueTable->CustomRecords->getForeignKey();

        $selectedColumns = [
            $customFieldValueTable->aliasField($customRecordsForeignKey),
            $customFieldValueTable->aliasField($customFieldsForeignKey),
            'field_value' => '(GROUP_CONCAT((CASE WHEN ' . $customFieldValueTable->aliasField('text_value') . ' IS NOT NULL THEN ' . $customFieldValueTable->aliasField('text_value')
                . ' WHEN ' . $customFieldValueTable->aliasField('number_value') . ' IS NOT NULL THEN ' . $customFieldValueTable->aliasField('number_value')
                . ' WHEN ' . $customFieldValueTable->aliasField('decimal_value') . ' IS NOT NULL THEN ' . $customFieldValueTable->aliasField('decimal_value')
                . ' WHEN ' . $customFieldValueTable->aliasField('textarea_value') . ' IS NOT NULL THEN ' . $customFieldValueTable->aliasField('textarea_value')
                . ' WHEN ' . $customFieldValueTable->aliasField('date_value') . ' IS NOT NULL THEN ' . $customFieldValueTable->aliasField('date_value')
                . ' WHEN ' . $customFieldValueTable->aliasField('time_value') . ' IS NOT NULL THEN ' . $customFieldValueTable->aliasField('time_value')
                . ' END) SEPARATOR \',\'))'
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
                'groupField' => $customFieldValueTable->aliasField($customRecordsForeignKey),
            ])
            ->innerJoin(
                [$customFieldValueTable->getAlias() => $customFieldValueTable->getTable()],
                [$customFieldValueTable->aliasField($customFieldsForeignKey) . '=' . $customFieldsTable->aliasField('id')]
            )
            ->select($selectedColumns)
            ->where([$customFieldValueTable->aliasField($customRecordsForeignKey) => $recordId])
            ->group([$customFieldValueTable->aliasField($customRecordsForeignKey), $customFieldValueTable->aliasField($customFieldsForeignKey)])
            ->toArray();

        return $fieldValue;
    }

    private function getTableCellValues($tableCustomFieldIds, $recordId) // POCOR-9116
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
                ->where([$TableCellTable->aliasField($customFieldsForeignKey) . ' IN ' => $tableCustomFieldIds, $TableCellTable->aliasField($customRecordsForeignKey) => $recordId])
                ->map(function ($row) use ($tableCellData, $customFieldsForeignKey, $customColumnForeignKey, $customRowForeignKey) {
                    $value = null;
                    if (isset($row['number_value'])) { //POCOR-9272
                        $value = $row['number_value'];
                    } elseif (isset($row['text_value'])) { //POCOR-9272
                        $value = $row['text_value'];
                    } elseif (isset($row['decimal_value'])) { //POCOR-9272
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

    /**
     *    Function to set the temporary field values
     *
     * @param array The field values to be stored
     * @return array The field values to be stored
     */
    public function setTmpFieldValues(array $tmpFieldValues)
    {
        $this->_tmpFieldValues = $tmpFieldValues;
        return $tmpFieldValues;
    }

    /**
     *    Function to get the customFieldOptions
     *
     * @return array The custom field option list
     */
    public function getCustomFieldOptionsList()
    {
        return $this->_customFieldOptionsList;
    }

    /**bef
     * // POCOR-9116
     *    Function to get the custom values for each field values specified
     *
     * @param array $customFieldValues List of field values
     * @param array $customFields Array containing the custom fields for each of the $filterKeys specified
     * @param array $customFieldOptionList The list of the available custom field options for dropdown and checkbox answers
     * @return array The value base on the custom field and the field values specified
     */
    public function getCustomFieldValue(array $customFieldValues, $customFieldAttributes, $customFieldOptionsList)
    {

        // List of options
        $optionsValues = $customFieldOptionsList;
        $answer = '';
        // Handle existing field types, if there are new field types please add another function for it
        $type = strtolower($customFieldAttributes['field_type']);
        if($type == 'table'){
            $type = 'table_field';
            $optionsValues = $customFieldAttributes;
        }
        $id = $customFieldAttributes['id'];

        if (method_exists($this, $type)) {
            $ans = $this->$type($customFieldValues, $id, $optionsValues);
            if (!(is_null($ans))) {
                $answer = $ans;
            }
        } else {
            $answer = $customFieldValues[$id] ?? '';
        }
        return $answer;
    }

    private function text($data, $id, $options = [])
    {
        return $data[$id] ?? '';
    }

    private function number($data, $id, $options = [])
    {
        return $data[$id] ?? '';
    }

    private function decimal($data, $id, $options = [])
    {
        return $data[$id] ?? '';
    }

    private function textarea($data, $id, $options = [])
    {
        return $data[$id] ?? '';
    }

    private function dropdown($data, $id, $options = [])
    {
        $thisData = $data[$id] ?? 0;

        $neededData = $options[$thisData] ?? '';
        return $neededData;
    }

    private function checkbox($data, $id, $options = [])
    {
        if (isset($data[$id])) {

            $values = explode(",", $data[$id]);
            $returnValue = '';
            foreach ($values as $value) {
                if (isset($options[$value])) {
                    if (empty($returnValue)) {
                        $returnValue = $options[$value];
                    } else {
                        $returnValue = $returnValue . ', ' . $options[$value];
                    }
                }
            }
            return $returnValue;
        } else {
            return '';
        }
    }

    private function date($data, $id, $options = [])
    {
        if (isset($data[$id])) {
            $date = date_create_from_format('Y-m-d', $data[$id]);
            return $this->_table->formatDate($date);
        } else {
            return '';
        }
    }

    private function time($data, $id, $options = [])
    {
        if (isset($data[$id])) {
            $time = date_create_from_format('G:i:s', $data[$id]);
            return $this->_table->formatTime($time);
        } else {
            return '';
        }
    }

    private function student_list($data, $id, $options = [])
    {
        return '';
    }
    //POCOR[POCOR-8471]
     public function table_field($data, $id, $options=[]): string { // POCOR-9116
         $colId = $options['col_id'] ?? 0;
         $rowId = $options['row_id'] ?? 0;
         return $data[$id][$colId][$rowId] ?? '';
     }

}
