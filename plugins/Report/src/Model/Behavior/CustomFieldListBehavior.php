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
	];

	public function initialize(array $config) {
		$this->CustomFormsFilters = TableRegistry::get($this->config('formFilterClass.className'));
		$this->CustomFieldValues = TableRegistry::get($this->config('fieldValueClass.className'));
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
		$institutionSiteTypes = $this->getType($filter);
		$InstitutionCustomFormFiltersTable = $this->CustomFormsFilters;
		$InstitutionCustomFieldValueTable = $this->CustomFieldValues;
		$filterKey = $this->getFilterKey($filter);
		// Get the custom fields columns
		foreach ($institutionSiteTypes as $key => $name) {

			// Getting the header
			$fields = $this->getCustomFields($InstitutionCustomFormFiltersTable, $key);
			$header = $fields['header'];
			$customField = $fields['customField'];

			// Getting the custom field values
			$query = $this->_table->find()->where([$this->_table->aliasField($filterKey) => $key]);
			$data = $this->getCustomFieldValues($this->_table, $InstitutionCustomFieldValueTable, $customField, $filterKey, $key);

			// The excel spreadsheets
			$sheets[] = [
	    		'name' => __($name),
				'table' => $this->_table,
				'query' => $this->_table->find()->where([$this->_table->aliasField($filterKey) => $key]),
				'additionalHeader' => $header,
				'additionalData' => $data,
	    	];
		}
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
			->first()
			->filter
			;
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
		$types = TableRegistry::get($filter)->getList()->toArray();
		return $types;
	}

	/**
	 *	Function to get the custom headers for each type of the filter
	 *
	 *	@param Table $customFormFilterTable The custom form filter table to use to get the headers
	 *	@param int $filterValue The id value of the filterKey
	 *	@return array The value of the header and the custom fields
	 */	
	public function getCustomFields($customFormFilterTable, $filterValue=null) {
		$condition = [];
		if (!(is_null($filterValue))) {
			$customFilterKey = $customFormFilterTable->CustomFilters->foreignKey();
			$condition = [$customFormFilterTable->aliasField($customFilterKey) => $filterValue];
		}
		$customFormFilters = $customFormFilterTable->find()
				->where($condition)
				->contain(['CustomForms', 'CustomForms.CustomFields'])
				->first();
		$customFields = [];
		$header = null;
		if (isset($customFormFilters['custom_form']['custom_fields'])) {
			foreach ($customFormFilters['custom_form']['custom_fields'] as $field) {
				if ($field->field_type != 'TABLE' && $field->field_type != 'STUDENT_LIST') {
					$header[$field->id] = $field->name;
					$customFields[$field->id] = $field;
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
	 *	@param Table $table The model for which the custom field values is tagged to
	 *	@param Table $customFieldValueTable The table of the customFieldValue for the specified report. 
	 *			E.g. Institution will use InstitutionCustomFieldValue table
	 *	@param array $customFields Array containing the custom fields for each of the $filterKeys specified
	 *	@param string $filterKey The filter column name
	 *	@param int $filterValue The id value of the filterKey
	 *	@return array The values of each of the custom fields value base on the filter value specified
	 */
	public function getCustomFieldValues(Table $table, Table $customFieldValueTable, $customField, $filterKey=null, $filterValue=null) {
		$customFieldsForeignKey = $customFieldValueTable->CustomFields->foreignKey();
		$customRecordsForeignKey = $customFieldValueTable->CustomRecords->foreignKey();
		$CustomFieldOptionsTable = $customFieldValueTable->CustomFields->CustomFieldOptions;
		$condition = [];

		if (!(is_null($filterKey))) {
			$condition = [
				$table->aliasField($filterKey) => $filterValue	
			];
		}

		$ids = $table
			->find('list', [
				'keyField' => 'id',
				'valueField' => 'id'
			])
			->where($condition)
			->toArray();
			
		$customFieldsTable = $customFieldValueTable->CustomFields;
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
