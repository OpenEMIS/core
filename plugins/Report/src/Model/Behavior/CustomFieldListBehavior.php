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
			'Model.excel.onExcelBeforeStart'				=> 'onExcelBeforeStart',
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
		if ($filterValue != null) {
			$customFilterKey = $customFormFilterTable->CustomFilters->foreignKey();
			$condition = [$customFormFilterTable->aliasField($customFilterKey) => $filterValue];
		}
		$customFormFilters = $customFormFilterTable->find()
				->where($condition)
				->contain(['CustomForms', 'CustomForms.CustomFields'])
				->first();
		$customField = [];
		$header = null;
		if (isset($customFormFilters['custom_form']['custom_fields'])) {
			$customField = $customFormFilters['custom_form']['custom_fields'];
			foreach ($customField as $field) {
				if ($field->field_type != 'TABLE' && $field->field_type != 'STUDENT_LIST') {
					$header[$field->id] = $field->name;
				}	
			}
			if (!empty($header)) {
				ksort($header);
			}
		}
		return ['header' => $header, 'customField' => $customField];
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

		if ($filterKey != null) {
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

		$consolidatedValues = [];
		foreach ($ids as $id) {
			$fields = $customField;
			$answer = [];
			foreach ($fields as $field) {
				$fieldValue = $customFieldValueTable->find()
							->where([
								$customFieldValueTable->aliasField($customRecordsForeignKey) => $id,
								$customFieldValueTable->aliasField($customFieldsForeignKey) => $field->id,
							]);
				$fieldType = $field->field_type;
				switch ($fieldType) {
					case 'CHECKBOX':
					case 'DROPDOWN':
						$fieldValue->innerJoin(
								[$CustomFieldOptionsTable->alias() => $CustomFieldOptionsTable->table()],
								[$CustomFieldOptionsTable->aliasField('id').'='.$customFieldValueTable->aliasField('number_value')]
							)
							->select([$CustomFieldOptionsTable->aliasField('name')]);
						$tmpAnswer = '';
						$alias = $CustomFieldOptionsTable->alias();
						foreach ($fieldValue->toArray() as $value) {
							if (empty($tmpAnswer)) {
								$tmpAnswer = $value[$alias]['name'];
							} else {
								$tmpAnswer = $tmpAnswer.', '.$value[$alias]['name'];
							}
						}
						$answer[] = $tmpAnswer;
						break;

					default:
						$value = $fieldValue->first();
						if (!empty($value)) {
							switch ($fieldType) {
								case 'TABLE':
								case 'STUDENT_LIST':
									break;

								case 'DATE':
									$answer[] = $value->date_value;
									break;

								case 'TIME':
									$answer[] = $value->time_value;
									break;

								case 'TEXTAREA':
									$answer[] = $value->textarea_value;
									break;

								case 'NUMBER':
									$answer[] = $value->number_value;
									break;

								case 'TEXT':
									$answer[] = $value->text_value;
									break;
							}
						} else {
							switch ($fieldType) {
								case 'TABLE':
								case 'STUDENT_LIST':
									break;
								default:
									$answer[] = '';
									break;
							}
						}
						break;
				}
			}
			$consolidatedValues[] = $answer;
		}
		return $consolidatedValues;
	}
}
