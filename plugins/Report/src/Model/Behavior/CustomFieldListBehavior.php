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
			'Model.excel.onExcelBeforeStart' => 'onExcelBeforeStart',
		],
		'model' => null,
		'formFilterClass' => ['className' => 'CustomField.CustomFormsFilters'],
		'fieldValueClass' => ['className' => 'CustomField.CustomFieldValues', 'foreignKey' => 'custom_record_id', 'dependent' => true, 'cascadeCallbacks' => true],
		'condition' => null,
	];

	public function initialize(array $config) {
		$this->CustomFormsFilters = null;
		$formFilterClass = $this->config('formFilterClass');
		if (!empty($formFilterClass)) {
			$this->CustomFormsFilters = TableRegistry::get($this->config('formFilterClass.className'));
		}
		$this->CustomFieldValues = TableRegistry::get($this->config('fieldValueClass.className'));
		$this->CustomForms = $this->CustomFieldValues->CustomFields->CustomForms;
		$model = $this->config('model');
		if (empty($model)) {
			$this->config('model', $this->_table->registryAlias());
		}
	}

	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events = array_merge($events, $this->config('events'));
    	return $events;
	}

	public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets) {
		$filter = $this->getFilter($this->config('model'));
		$types = $this->getType($filter);
		$filterKey = $this->getFilterKey($filter);

		if (!empty($types)) {
			foreach ($types as $key => $name) {
				$this->excelContent($sheets, $name, $filterKey, $key);
			}
		} else {
			$name = $this->_table->alias();
			$this->excelContent($sheets, $name);
		}

	}

	// Function to generate the excel content
	private function excelContent(ArrayObject $sheets, $name, $filterKey=null, $key=null) {
		// Getting the header
		$fields = $this->getCustomFields($key);
		$header = $fields['header'];
		$customField = $fields['customField'];

		// Getting the custom field values
		$data = $this->getCustomFieldValues($customField, $filterKey, $key);

		$query = $this->_table->find();

		// If the filter is present
		if (!(is_null($filterKey))) {
			$query->where([$this->_table->aliasField($filterKey) => $key]);
		}

		// If there is any specified query condition
		$condition = $this->config('condition');
		if (!(is_null($condition))) {
			$query->where($condition);
		}

		// The excel spreadsheets
		$sheets[] = [
    		'name' => __($name),
			'table' => $this->_table,
			'query' => $query,
			'orientation' => 'landscape',
			'additionalHeader' => $header,
			'additionalData' => $data,
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
	 *	@param String $model The filter provided by the custom module
	 *	@return String The filter column name
	 */
	public function getFilterKey($model) {
		$split = explode('.', $model);
		$plugin = null;
		$modelClass = $model;
		if (count($split) > 1) {
			$plugin = $split[0];
			$modelClass = $split[1];
		}
		$filterKey = Inflector::underscore(Inflector::singularize($modelClass)) . '_id';
		return $filterKey;
	}

	/**
	 *	Function to get the filter type list
	 *
	 *	@param string $filter custom field filter
	 *	@return array The list of institution site types
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
		$header = null;
		if (!(empty($filterValue))) {
			// If there is a filter specified
			$customFilterKey = $this->CustomFormsFilters->CustomFilters->foreignKey();
			$customFormFields = $this->CustomFormsFilters
				->find()
				->where([$this->CustomFormsFilters->aliasField($customFilterKey) => $filterValue])
				->contain(['CustomForms', 'CustomForms.CustomFields'])
				->toArray();	
		} else {
			// If there is no filter specified
			$customFormFields = $this->CustomForms
				->find()
				->contain(['CustomFields'])
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
					if ($field->field_type != 'TABLE' && $field->field_type != 'STUDENT_LIST') {
						$header[$field->id] = $field->name;
						$customFields[$field->id] = $field;
					}	
				}
			}
			if (!empty($header)) {
				ksort($header);
				ksort($customFields);
			}
		}
		return ['header' => $header, 'customField' => $customFields];
	}

	/**
	 *	Function to get the custom values for each type of the filter
	 *
	 *	@param array $customFields Array containing the custom fields for each of the $filterKeys specified
	 *	@param string $filterKey The filter column name
	 *	@param int $filterValue The id value of the filterKey
	 *	@return array The values of each of the custom fields value base on the filter value specified
	 */
	public function getCustomFieldValues($customField, $filterKey=null, $filterValue=null) {
		$customFieldValueTable = $this->CustomFieldValues;
		$customFieldsForeignKey = $customFieldValueTable->CustomFields->foreignKey();
		$customRecordsForeignKey = $customFieldValueTable->CustomRecords->foreignKey();
		$CustomFieldOptionsTable = $customFieldValueTable->CustomFields->CustomFieldOptions;
		$condition = [];

		// If there is any specified filter key
		if (!(empty($filterKey))) {
			$condition = [
				$this->_table->aliasField($filterKey) => $filterValue	
			];
		}

		// If there is any specified query condition
		$configCondition = $this->config('condition');
		if (is_null($configCondition)) {
			$configCondition = [];
		}

		// List of record ids
		$ids = $this->_table
			->find('list', [
				'keyField' => 'id',
				'valueField' => 'id'
			])
			->where($condition)
			->where($configCondition)
			->toArray();

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
				'groupField' => $customFieldValueTable->aliasField($customRecordsForeignKey)
			])
			->innerJoin(
				[$customFieldValueTable->alias() => $customFieldValueTable->table()],
				[$customFieldValueTable->aliasField($customFieldsForeignKey).'='.$customFieldsTable->aliasField('id')]
			)
			->select([
				$customFieldValueTable->aliasField($customRecordsForeignKey),
				$customFieldValueTable->aliasField($customFieldsForeignKey),
				'field_value' => '(GROUP_CONCAT((CASE 
					WHEN '.$customFieldValueTable->aliasField('text_value').' IS NOT NULL THEN '.$customFieldValueTable->aliasField('text_value')
					.' WHEN '.$customFieldValueTable->aliasField('number_value').' IS NOT NULL THEN '.$customFieldValueTable->aliasField('number_value')
					.' WHEN '.$customFieldValueTable->aliasField('textarea_value').' IS NOT NULL THEN '.$customFieldValueTable->aliasField('textarea_value')
					.' WHEN '.$customFieldValueTable->aliasField('date_value').' IS NOT NULL THEN '.$customFieldValueTable->aliasField('date_value')
					.' WHEN '.$customFieldValueTable->aliasField('time_value').' IS NOT NULL THEN '.$customFieldValueTable->aliasField('time_value')
					.' END) SEPARATOR \',\'))'
			])
			->group([$customFieldValueTable->aliasField($customRecordsForeignKey), $customFieldValueTable->aliasField($customFieldsForeignKey)])
			->hydrate(false)
			->toArray();

		// List of options
		$optionsValues = $CustomFieldOptionsTable->find('list')->toArray();

		$consolidatedValues = [];
		foreach ($ids as $id) {
			$fields = $customField;
			$answer = [];
			foreach ($fields as $field) {
				// Handle existing field types, if there are new field types please add another function for it
				$type = strtolower($field->field_type);
				if (method_exists($this, $type)) {
					$ans = $this->$type($fieldValue, $id, $field->id, $optionsValues);
					if (!(is_null($ans))) {
						$answer[] = $ans;
					}
				}
			}
			$consolidatedValues[] = $answer;
		}
		return $consolidatedValues;
	}

	public function text($data, $recordId, $fieldId, $options=[]) {
		if (isset($data[$recordId][$fieldId])) {
			return $data[$recordId][$fieldId];
		} else {
			return '';
		}
	}

	public function number($data, $recordId, $fieldId, $options=[]) {
		if (isset($data[$recordId][$fieldId])) {
			return $data[$recordId][$fieldId];
		} else {
			return '';
		}
	}
	
	public function textarea($data, $recordId, $fieldId, $options=[]) {
		if (isset($data[$recordId][$fieldId])) {
			return $data[$recordId][$fieldId];
		} else {
			return '';
		}
	}

	public function dropdown($data, $recordId, $fieldId, $options=[]) {
		if (isset($data[$recordId][$fieldId])) {
			if (isset($options[$data[$recordId][$fieldId]])) {
				return $options[$data[$recordId][$fieldId]];
			} else {
				return '';
			}
		} else {
			return '';
		}
	}
	
	public function checkbox($data, $recordId, $fieldId, $options=[]) {
		if (isset($data[$recordId][$fieldId])) {
			$values = explode(",", $data[$recordId][$fieldId]);
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

	public function date($data, $recordId, $fieldId, $options=[]) {
		if (isset($data[$recordId][$fieldId])) {
			return $data[$recordId][$fieldId];
		} else {
			return '';
		}
	}

	public function time($data, $recordId, $fieldId, $options=[]) {
		if (isset($data[$recordId][$fieldId])) {
			return $data[$recordId][$fieldId];
		} else {
			return '';
		}
	}

	public function student_list($data, $recordId, $fieldId, $options=[]) {
		return null;
	}

	public function table($data, $recordId, $fieldId, $options=[]) {
		return null;
	}

}
