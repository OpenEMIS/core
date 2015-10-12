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
			'Model.excel.onExcelUpdateHeader' => ['callable' => 'onExcelUpdateHeader', 'priority' => 110],
			'Model.excel.onExcelUpdateRow' => ['callable' => 'onExcelUpdateRow', 'priority' => 120],
		],
		'moduleKey' => 'custom_module_id',
		'formKey' => 'custom_form_id',
		'model' => null,
		'formFilterClass' => ['className' => 'CustomField.CustomFormsFilters'],
		'fieldValueClass' => ['className' => 'CustomField.CustomFieldValues', 'foreignKey' => 'custom_record_id', 'dependent' => true, 'cascadeCallbacks' => true],
		'condition' => [],
	];

	private $_condition = [];

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
			$filterKey = $this->getFilterKey($filter);
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

	// Model.excel.onExcelUpdateHeader
	public function onExcelUpdateHeader(Event $event, ArrayObject $settings, ArrayObject $sheet) {
		$filterValue = null;
		if (isset($sheet['key'])) {
			$filterValue = $sheet['key'];
		}
		$fields = $this->getCustomFields($filterValue);
		$customFields = $fields['customFields'];
		$settings['customFields'] = $customFields;
		$header = $fields['header'];
		return $header;
	}

	// Model.excel.onExcelUpdateRow
	public function onExcelUpdateRow(Event $event, ArrayObject $settings, Entity $entity, ArrayObject $sheet) {
		$id = $entity->id;
		$customFields = null;
		if (isset($settings['customFields'])) {
			$customFields = $settings['customFields'];
		}
		$customFieldOptions = $sheet['customFieldOptions'];
		return $this->getCustomFieldValues($id, $customFields, $customFieldOptions);
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
		
		return $this->_table
			->find('list', [
				'keyField' => 'id',
				'valueField' => 'name'
			])
			->contain(['SurveyForms'])
			->select([
				'id' => $formKeyAlias,
				'name' => 'SurveyForms.name'
			])
			->where($condition)
			->group($formKeyAlias)
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
		$header = null;
		$customModuleKey = $this->config('moduleKey');
		if (is_null($customModuleKey)) {
			// Use for surveys
			$SurveyFormsTable = $this->CustomFieldValues->CustomRecords->SurveyForms;
			$customFormFields = $SurveyFormsTable
				->find()
				->contain(['CustomFields'])
				->where([$SurveyFormsTable->aliasField('id') => $filterValue])
				->toArray();
		} elseif (!(empty($filterValue))) {
			// If there is a filter specified
			$customFilterKey = $this->CustomFormsFilters->CustomFilters->foreignKey();
			$customFormFields = $this->CustomFormsFilters
				->find()
				->where([$this->CustomFormsFilters->aliasField($customFilterKey).' IN' => [$filterValue, 0]])
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
		return ['header' => $header, 'customFields' => $customFields];
	}

	/**
	 *	Function to get the custom values for each type of the filter
	 *
	 *	@param int $customRecordId The entity record id (e.g. institution_site_survey_id)
	 *	@param array $customFields Array containing the custom fields for each of the $filterKeys specified
	 *	@param array $customFieldOptionList The list of the available custom field options for dropdown and checkbox answers
	 *	@return array The value base on the custom field and the record id specified
	 */
	public function getCustomFieldValues($customRecordId, $customFields, $customFieldOptionsList) {
		$customFieldValueTable = $this->CustomFieldValues;
		$customFieldsForeignKey = $customFieldValueTable->CustomFields->foreignKey();
		$customRecordsForeignKey = $customFieldValueTable->CustomRecords->foreignKey();
		
		// Getting the custom field table
		$customFieldsTable = $customFieldValueTable->CustomFields;

		// Getting the custom field values group by the record id, and then group by the field ids
		// Record with similar record id and field ids will be group concat together
		// For example: for checkbox, record id: 1, field id: 1, value: 1 and record id: 1, field id: 1, value: 2 will be
		// group as record id: 1, field id: 1, value: 1,2
		$fieldValue = $customFieldsTable
			->find('list', [
				'keyField' => $customFieldValueTable->aliasField($customFieldsForeignKey),
				'valueField' => 'field_value'
			])
			->innerJoin(
				[$customFieldValueTable->alias() => $customFieldValueTable->table()],
				[$customFieldValueTable->aliasField($customFieldsForeignKey).'='.$customFieldsTable->aliasField('id')]
			)
			->select([
				$customFieldValueTable->aliasField($customRecordsForeignKey),
				$customFieldValueTable->aliasField($customFieldsForeignKey),
				'field_value' => '(GROUP_CONCAT((CASE WHEN '.$customFieldValueTable->aliasField('text_value').' IS NOT NULL THEN '.$customFieldValueTable->aliasField('text_value')
					.' WHEN '.$customFieldValueTable->aliasField('number_value').' IS NOT NULL THEN '.$customFieldValueTable->aliasField('number_value')
					.' WHEN '.$customFieldValueTable->aliasField('textarea_value').' IS NOT NULL THEN '.$customFieldValueTable->aliasField('textarea_value')
					.' WHEN '.$customFieldValueTable->aliasField('date_value').' IS NOT NULL THEN '.$customFieldValueTable->aliasField('date_value')
					.' WHEN '.$customFieldValueTable->aliasField('time_value').' IS NOT NULL THEN '.$customFieldValueTable->aliasField('time_value')
					.' END) SEPARATOR \',\'))'
			])
			->where([$customFieldValueTable->aliasField($customRecordsForeignKey) => $customRecordId])
			->group([$customFieldValueTable->aliasField($customFieldsForeignKey)])
			->toArray();

		// List of options
		$optionsValues = $customFieldOptionsList;

		$fields = [];
		if (!(is_null($customFields))) {
			$fields = $customFields;
		}
		$answer = [];
		foreach ($fields as $field) {
			// Handle existing field types, if there are new field types please add another function for it
			$type = strtolower($field->field_type);
			if (method_exists($this, $type)) {
				$ans = $this->$type($fieldValue, $field->id, $optionsValues);
				if (!(is_null($ans))) {
					$answer[] = $ans;
				}
			}
		}
		return $answer;
	}

	private function text($data, $fieldId, $options=[]) {
		if (isset($data[$fieldId])) {
			return $data[$fieldId];
		} else {
			return '';
		}
	}

	private function number($data, $fieldId, $options=[]) {
		if (isset($data[$fieldId])) {
			return $data[$fieldId];
		} else {
			return '';
		}
	}
	
	private function textarea($data, $fieldId, $options=[]) {
		if (isset($data[$fieldId])) {
			return $data[$fieldId];
		} else {
			return '';
		}
	}

	private function dropdown($data, $fieldId, $options=[]) {
		if (isset($data[$fieldId])) {
			if (isset($options[$data[$fieldId]])) {
				return $options[$data[$fieldId]];
			} else {
				return '';
			}
		} else {
			return '';
		}
	}
	
	private function checkbox($data, $fieldId, $options=[]) {
		if (isset($data[$fieldId])) {
			$values = explode(",", $data[$fieldId]);
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

	private function date($data, $fieldId, $options=[]) {
		if (isset($data[$fieldId])) {
			$date = date_create_from_format('Y-m-d', $data[$fieldId]);
			return date_format($date, 'Y-m-d');
		} else {
			return '';
		}
	}

	private function time($data, $fieldId, $options=[]) {
		if (isset($data[$fieldId])) {
			$time = date_create_from_format('G:i:s', $data[$fieldId]);
			return date_format($time, 'g:i a');
		} else {
			return '';
		}
	}

	private function student_list($data, $fieldId, $options=[]) {
		return null;
	}

	private function table($data, $fieldId, $options=[]) {
		return null;
	}

}
