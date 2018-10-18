<?php
namespace Report\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Utility\Inflector;
use Cake\ORM\Table;

class CustomFieldListBehavior extends Behavior {
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

	public function initialize(array $config) {
		$this->CustomFormsFilters = null;
		$formFilterClass = $this->config('formFilterClass');
		if (!empty($formFilterClass)) {
			$this->CustomFormsFilters = TableRegistry::get($this->config('formFilterClass.className'));
		}
		$this->CustomFieldValues = TableRegistry::get($this->config('fieldValueClass.className'));
		$this->CustomTableCells = TableRegistry::get($this->config('tableCellClass.className'));
		$this->CustomForms = $this->CustomFieldValues->CustomFields->CustomForms;
		$model = $this->config('model');
		if (empty($model)) {
			$this->config('model', $this->_table->registryAlias());
		}
		$this->_condition = $this->config('condition');
	}

	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events = array_merge($events, $this->config('events'));
    	return $events;
	}

	// Model.excel.onExcelBeforeStart
	public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets) {
		if (!(is_null($this->config('moduleKey')))) {
			$filter = $this->getFilter($this->config('model'));
			$types = $this->getType($filter);
			$filterKey = $this->getFilterKey($filter, $this->config('model'));
			if (!empty($types)) {
				foreach ($types as $key => $name) {
					$this->excelContent($sheets, $name, $filterKey, $key);
				}
			} else {
				$name = $this->_table->alias();
				$this->excelContent($sheets, $name);
			}
		} else {
			// For Surveys only
			$forms = $this->getForms();
			foreach ($forms as $formId => $formName) {
				$this->excelContent($sheets, $formName, null, $formId);
			}
		}
	}

	// Model.excel.onExcelUpdateFields
	public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields) {
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

				if (sizeof($row) !=0 && sizeof($col) !=0 ) {
					for($i = 1; $i < sizeof($col); $i++) {
						foreach ($row as $rw) {
							$field['key'] = 'CustomField';
							$field['field'] = 'custom_field';
							$field['type'] = 'custom_field';
							$field['label'] = $customField['name'] . ' ('.$col[$i]['name'].', '.$rw['name'].')';
							$field['customField'] = ['id' => $customField['id'], 'field_type' => $customField['field_type'], 'col_id' => $col[$i]['id'], 'row_id' => $rw['id']];
							$excelFields[] = $field;
						}
					}
				}
			}
		}

		if (!empty($tableCustomFieldIds)) {
			$excelFields[$fieldCount]['tableCustomFieldIds'] = $tableCustomFieldIds;
		}

		$fields->exchangeArray($excelFields);
		// Setting the list of options into the sheet for easier fetching
		$this->setCustomFieldOptionsList($settings['sheet']['customFieldOptions']);
	}

	// Model.excel.onExcelRenderCustomField
	public function onExcelRenderCustomField(Event $event, Entity $entity, array $attr) {
		// Getting the temporary field values that is set
		$tmpFieldValues = $this->getTmpFieldValues();

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

		// Check if the temporary field value has this record information.
		if (isset($tmpFieldValues[$entity->id])) {
			return $this->getCustomFieldValue($tmpFieldValues[$entity->id], $attr['customField'], $this->getCustomFieldOptionsList());
		} else {
			return '';
		}
	}

	private function getTableCellValues($tableCustomFieldIds, $recordId) {
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


	/**
	 *	Function to get the query condition
	 *
	 *	@return array The current condition
	 */
	public function getCondition() {
		return $this->_condition;
	}

	/**
	 *	Function to set the query condition
	 *
	 *	@param array The new query condition
	 *	@return array The current condition
	 */
	public function setCondition(array $condition) {
		$this->_condition = $condition;
		return $this->_condition;
	}

	/**
	 *	Function to set the customFieldOptions
	 *
	 *	@param array The custom field option list
	 */
	public function setCustomFieldOptionsList(array $customFieldOptions) {
		$this->_customFieldOptionsList = $customFieldOptions;
	}

	/**
	 *	Function to get the customFieldOptions
	 *
	 *	@return array The custom field option list
	 */
	public function getCustomFieldOptionsList() {
		return $this->_customFieldOptionsList;
	}

	/**
	 *	Function to set the temporary field values
	 *
	 *	@param array The field values to be stored
	 *	@return array The field values to be stored
	 */
	public function setTmpFieldValues(array $tmpFieldValues) {
		$this->_tmpFieldValues = $tmpFieldValues;
		return $tmpFieldValues;
	}

	/**
	 *	Function to get the temporary field values
	 *
	 *	@return array The stored temporary field values
	 */
	public function getTmpFieldValues() {
		return $this->_tmpFieldValues;
	}

	/**
	 *	Function to get the form ids. Use for surveys only.
	 *
	 *	@param int $formId | null The form id if required to get a specific form
	 *	@return array Form ID of Form Names
	 */
	public function getForms($formId=null) {
		$condition = [];
		$formKeyAlias = $this->_table->aliasField($this->config('formKey'));
		if (!(is_null($formId))) {
			$condition = [$formKeyAlias => $formId];
			$configCondition = $this->getCondition();
			$this->setCondition(array_merge($configCondition, $condition));
		}

		$SurveyFormsTable = TableRegistry::get('Survey.SurveyForms');

		return $SurveyFormsTable
			->find('list', [
				'keyField' => 'id',
				'valueField' => 'name'
			])
			->where([$SurveyFormsTable->aliasField('id') => $formId])
			->group(['id'])
			->toArray();
	}

	// Function to generate the excel content
	public function excelContent(ArrayObject $sheets, $name, $filterKey=null, $key=null) {

		$query = $this->_table->find();

		// If the filter is present
		if (!(is_null($filterKey))) {
			$query->where([$this->_table->aliasField($filterKey) => $key]);
		}

		// If there is any specified query condition
		$condition = $this->_condition;
		$query->where($condition);

		// If it is a survey
		if (is_null($this->config('moduleKey'))) {
			$query->where([$this->_table->aliasField($this->config('formKey')) => $key]);
		}

		// Getting the list of available custom field options
		$optionsValues = $this->CustomFieldValues->CustomFields->CustomFieldOptions->find('list')->toArray();

		// The excel spreadsheets
		$sheets[] = [
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
	 *	Function to get the filter of the given model
	 *
	 *	@param string $model The code of of the custom module
	 *	@return string Filter of the custom module
	 */
	public function getFilter($model) {
		$CustomModuleTable = TableRegistry::get('CustomField.CustomModules');
		$filter = $CustomModuleTable
			->find()
			->where([$CustomModuleTable->aliasField('model')=>$model])
			->first();

		if (empty($filter)) {
			$filter = null;
		} else {
			$filter = $filter->filter;
		}

		return $filter;
	}

    /**
	 *	Function to get the filter key from the filter specified
     *
     *	@param string $filter The filter provided by the custom module
     *	@param string $model The model provided by the custom module
     *	@return The filter foreign key name if found. If not it will return empty.
     */
	public function getFilterKey($filter, $model) {
		$filterKey = '';
		$associations = TableRegistry::get($filter)->associations();
		foreach ($associations as $assoc) {
			if ($assoc->registryAlias() == $model) {
				$filterKey = $assoc->foreignKey();
				return $filterKey;
			}
		}
		return $filterKey;
	}

	/**
	 *	Function to get the filter type list
	 *
	 *	@param string $filter custom field filter
	 *	@return array The list of filter types
	 */
	public function getType($filter) {
		if (!(is_null($filter))) {
			$types = TableRegistry::get($filter)->getList()->toArray();
			return $types;
		} else {
			return null;
		}
	}

	/**
	 *	Function to get the custom headers for each type of the filter
	 *
	 *	@param int | null $filterValue The id value of the filterKey
	 *	@return array The value of the header and the custom fields
	 */
	public function getCustomFields($filterValue=null) {
		$customFields = [];
		$customFormFields = [];
		$customModuleKey = $this->config('moduleKey');
		if (is_null($customModuleKey)) {
			// Use for surveys
			$SurveyFormsTable = $this->CustomFieldValues->CustomRecords->SurveyForms;
			$customFormFields = $SurveyFormsTable
				->find()
				->contain(['CustomFields.CustomTableColumns', 'CustomFields.CustomTableRows'])
				->where([$SurveyFormsTable->aliasField('id') => $filterValue])
				->toArray();
		} elseif (!(empty($filterValue))) {
			// If there is a filter specified
			$customFilterKey = $this->CustomFormsFilters->CustomFilters->foreignKey();
			$customFormFields = $this->CustomFormsFilters
				->find()
				->where([$this->CustomFormsFilters->aliasField($customFilterKey).' IN' => [$filterValue, 0]])
				->contain(['CustomForms', 'CustomForms.CustomFields.CustomTableColumns', 'CustomForms.CustomFields.CustomTableRows'])
				->toArray();
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

			if (!(is_null($fields))) {
				foreach ($fields as $field) {
					if ($field->field_type != 'STUDENT_LIST') {
						$customFields[$field->id] = $field;
					}
				}
			}
			if (!empty($customFields)) {
				ksort($customFields);
			}
		}
		return $customFields;
	}

	/**
	 *	Function to get the field values base on a given record id
	 *
	 *	@param int $recordId The record id of the entity
	 *	@return array The field values of that given record id
	 */
	public function getFieldValue($recordId) {
		$customFieldValueTable = $this->CustomFieldValues;
		$customFieldsForeignKey = $customFieldValueTable->CustomFields->foreignKey();
		$customRecordsForeignKey = $customFieldValueTable->CustomRecords->foreignKey();

		$selectedColumns = [
			$customFieldValueTable->aliasField($customRecordsForeignKey),
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
				'groupField' => $customFieldValueTable->aliasField($customRecordsForeignKey),
			])
			->innerJoin(
				[$customFieldValueTable->alias() => $customFieldValueTable->table()],
				[$customFieldValueTable->aliasField($customFieldsForeignKey).'='.$customFieldsTable->aliasField('id')]
			)
			->select($selectedColumns)
			->where([$customFieldValueTable->aliasField($customRecordsForeignKey) => $recordId])
			->group([$customFieldValueTable->aliasField($customRecordsForeignKey), $customFieldValueTable->aliasField($customFieldsForeignKey)])
			->toArray();

		return $fieldValue;
	}

	/**
	 *	Function to get the custom values for each field values specified
	 *
	 *	@param int $fieldValue List of field values
	 *	@param array $customFields Array containing the custom fields for each of the $filterKeys specified
	 *	@param array $customFieldOptionList The list of the available custom field options for dropdown and checkbox answers
	 *	@return array The value base on the custom field and the field values specified
	 */
	public function getCustomFieldValue($fieldValue, $customField, $customFieldOptionsList) {
		// List of options
		$optionsValues = $customFieldOptionsList;
		$answer = '';
		// Handle existing field types, if there are new field types please add another function for it
		$type = strtolower($customField['field_type']);
		if (method_exists($this, $type)) {
			$ans = $this->$type($fieldValue, $customField, $optionsValues);
			if (!(is_null($ans))) {
				$answer = $ans;
			}
		}
		return $answer;
	}

	private function text($data, $field, $options=[]) {
		if (isset($data[$field['id']])) {
			return $data[$field['id']];
		} else {
			return '';
		}
	}

	private function number($data, $field, $options=[]) {
		if (isset($data[$field['id']])) {
			return $data[$field['id']];
		} else {
			return '';
		}
	}

	private function decimal($data, $field, $options=[]) {
		if (isset($data[$field['id']])) {
			return $data[$field['id']];
		} else {
			return '';
		}
	}

	private function textarea($data, $field, $options=[]) {
		if (isset($data[$field['id']])) {
			return $data[$field['id']];
		} else {
			return '';
		}
	}

	private function dropdown($data, $field, $options=[]) {
		if (isset($data[$field['id']])) {
			if (isset($options[$data[$field['id']]])) {
				return $options[$data[$field['id']]];
			} else {
				return '';
			}
		} else {
			return '';
		}
	}

	private function checkbox($data, $field, $options=[]) {
		if (isset($data[$field['id']])) {
			$values = explode(",", $data[$field['id']]);
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

	private function date($data, $field, $options=[]) {
		if (isset($data[$field['id']])) {
			$date = date_create_from_format('Y-m-d', $data[$field['id']]);
			return $this->_table->formatDate($date);
		} else {
			return '';
		}
	}

	private function time($data, $field, $options=[]) {
		if (isset($data[$field['id']])) {
			$time = date_create_from_format('G:i:s', $data[$field['id']]);
			return $this->_table->formatTime($time);
		} else {
			return '';
		}
	}

	private function student_list($data, $field, $options=[]) {
		return null;
	}

	private function table($data, $field, $options=[]) {
		$id = $field['id'];
		$colId = $field['col_id'];
		$rowId = $field['row_id'];
		if (isset($data[$id][$colId][$rowId])) {
			return $data[$id][$colId][$rowId];
		}
		return '';
	}

}
