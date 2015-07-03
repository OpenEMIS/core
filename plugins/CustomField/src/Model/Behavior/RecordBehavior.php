<?php
namespace CustomField\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Utility\Inflector;

class RecordBehavior extends Behavior {
	protected $_contain = ['CustomFieldValues', 'CustomTableCells'];
	protected $_defaultConfig = [
		'events' => [
			'ControllerAction.Model.view.afterAction' 		=> 'viewAfterAction',
			'ControllerAction.Model.addEdit.beforePatch' 	=> 'addEditBeforePatch',
			'ControllerAction.Model.addEdit.afterAction' 	=> 'addEditAfterAction'
		],
		'behavior' => null,
		'moduleKey' => 'custom_module_id',
		'fieldKey' => 'custom_field_id',
		'fieldOptionKey' => 'custom_field_option_id',
		'formKey' => 'custom_form_id',
		'tableColumnKey' => 'custom_table_column_id',
		'tableRowKey' => 'custom_table_row_id',
		'recordKey' => 'custom_record_id',
		'fieldValueKey' => ['className' => 'CustomField.CustomFieldValues', 'foreignKey' => 'custom_record_id', 'dependent' => true, 'cascadeCallbacks' => true],
		'tableCellKey' => ['className' => 'CustomField.CustomTableCells', 'foreignKey' => 'custom_record_id', 'dependent' => true, 'cascadeCallbacks' => true]
	];

	public function initialize(array $config) {
		parent::initialize($config);
		$this->_table->hasMany('CustomFieldValues', $this->config('fieldValueKey'));
		$this->_table->hasMany('CustomTableCells', $this->config('tableCellKey'));
    }

    public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events = array_merge($events, $this->config('events'));
    	return $events;
	}

    public function viewAfterAction(Event $event, Entity $entity) {
    	$this->buildCustomFields($entity);
    }

    public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
    	// Checking : skip insert if value is empty
    	if (array_key_exists('custom_field_values', $data[$this->_table->alias()]) || array_key_exists('custom_table_cells', $data[$this->_table->alias()])) {
			$CustomFields = $this->_table->CustomFieldValues->CustomFields;
			$CustomFieldTypes = $CustomFields->CustomFieldTypes;
			$fieldTypes = $CustomFieldTypes
				->find('list', ['keyField' => 'code', 'valueField' => 'value'])
				->toArray();
		}

		if (array_key_exists('custom_field_values', $data[$this->_table->alias()])) {
			foreach ($data[$this->_table->alias()]['custom_field_values'] as $key => $obj) {
				$fieldType = $CustomFields
					->find('all')
					->select([$CustomFields->aliasField('field_type')])
					->where([$CustomFields->aliasField('id') => $obj[$this->config('fieldKey')]])
					->first()
					->field_type;

				$fieldValue = $fieldTypes[$fieldType];

				if (strlen($obj[$fieldValue]) == 0) {
					unset($data[$this->_table->alias()]['custom_field_values'][$key]);
				}
			}
		}

		if (array_key_exists('custom_table_cells', $data[$this->_table->alias()])) {
			foreach ($data[$this->_table->alias()]['custom_table_cells'] as $key => $obj) {
				$fieldType = $CustomFields
					->find('all')
					->select([$CustomFields->aliasField('field_type')])
					->where([$CustomFields->aliasField('id') => $obj[$this->config('fieldKey')]])
					->first()
					->field_type;

				$fieldValue = $fieldTypes[$fieldType];

				if (strlen($obj[$fieldValue]) == 0) {
					unset($data[$this->_table->alias()]['custom_table_cells'][$key]);
				}
			}
		}
		// End Checking

    	$associatedOptions = $options->offsetExists('associated') ? $options->offsetGet('associated') : [];
    	$associatedOptions = array_merge_recursive($associatedOptions, $this->_contain);
    	$options->offsetSet('associated', $associatedOptions);
    }

    public function addEditAfterAction(Event $event, Entity $entity) {
    	$this->buildCustomFields($entity);
	}

	public function getCustomFieldQuery($entity) {
		$CustomFieldValues = $this->_table->CustomFieldValues;
		$CustomTableCells = $this->_table->CustomTableCells;

		$CustomFields = $CustomFieldValues->CustomFields;
		$CustomFieldTypes = $CustomFields->CustomFieldTypes;

		$CustomForms = $CustomFields->CustomForms;
		$CustomModules = $CustomForms->CustomModules;
		$CustomFormFilters = $CustomForms->CustomFormFilters;
		$CustomFormFields = $CustomForms->CustomFormFields;

		$customFieldQuery = null;
		//For Institution Survey
		if (is_null($this->config('moduleKey'))) {
			$customFormId = $entity->{$this->config('formKey')};

			if (isset($customFormId)) {
				$customFieldQuery = $CustomFormFields
					->find('all')
					->find('order')
					->contain(['CustomFields.CustomFieldOptions', 'CustomFields.CustomTableColumns', 'CustomFields.CustomTableRows'])
					->where([$CustomFormFields->aliasField($this->config('formKey')) => $customFormId]);
			}
		} else {
			$where = [$CustomModules->aliasField('model') => $this->_table->registryAlias()];
			if ($this->config('behavior')) {
				$where[$CustomModules->aliasField('behavior')] = $this->config('behavior');
			}

			$customModuleResults = $CustomModules
				->find('all')
				->select([
					$CustomModules->aliasField('id'),
					$CustomModules->aliasField('filter')
				])
				->where($where)
				->first();
			$customModuleId = $customModuleResults->id;
			$filter = $customModuleResults->filter;

			$customFormIds = $CustomForms
				->find('list', ['keyField' => 'id', 'valueField' => 'id'])
				->where([$CustomForms->aliasField($this->config('moduleKey')) => $customModuleId])
				->toArray();

			$genaralResults = $CustomFormFilters
				->find('all')
				->select([$CustomFormFilters->aliasField($this->config('formKey'))])
				->where([
					$CustomFormFilters->aliasField($this->config('formKey').' IN') => $customFormIds,
					$CustomFormFilters->aliasField('custom_filter_id') => 0
				])
				->all();

			if ($genaralResults->isEmpty()) {
				if (is_null($filter)) {
					$customFieldQuery = $CustomFormFields
						->find('all')
						->find('order')
						->contain(['CustomFields.CustomFieldOptions', 'CustomFields.CustomTableColumns', 'CustomFields.CustomTableRows'])
						->where([$CustomFormFields->aliasField($this->config('formKey') . ' IN') => $customFormIds]);
				}
			} else {
				$genaralData = $genaralResults->first();
				$generalId = $genaralData->{$this->config('formKey')};

				$customFieldQuery = $CustomFormFields
					->find('all')
					->find('order')
					->contain(['CustomFields.CustomFieldOptions', 'CustomFields.CustomTableColumns', 'CustomFields.CustomTableRows'])
					->where([$CustomFormFields->aliasField($this->config('formKey')) => $generalId]);
			}

			if (!is_null($filter)) {
				$modelAlias = $this->_table->ControllerAction->getModel($filter)['model'];
				$filterKey = Inflector::underscore(Inflector::singularize($modelAlias)) . '_id';

				$filterId = $entity->$filterKey;
				$typedResults = $CustomFormFilters
					->find('all')
					->select([$CustomFormFilters->aliasField($this->config('formKey'))])
					->where([
						$CustomFormFilters->aliasField($this->config('formKey').' IN') => $customFormIds,
						$CustomFormFilters->aliasField('custom_filter_id') => $filterId
					])
					->all();

				if (!$typedResults->isEmpty()) {
					$typedData = $typedResults->first();
					$typedId = $typedData->{$this->config('formKey')};

					$typedCustomFieldQuery = $CustomFormFields
						->find('all')
						->find('order')
						->contain(['CustomFields.CustomFieldOptions', 'CustomFields.CustomTableColumns', 'CustomFields.CustomTableRows'])
						->where([$CustomFormFields->aliasField($this->config('formKey')) => $typedId]);

					if (isset($customFieldQuery)) {
						$customFieldQuery
							->union($typedCustomFieldQuery);
					} else {
						$customFieldQuery = $typedCustomFieldQuery;
					}
				}
			}
		}

		return $customFieldQuery;
	}

	public function buildCustomFields($entity) {
		$customFieldQuery = $this->getCustomFieldQuery($entity);

		$CustomFieldValues = $this->_table->CustomFieldValues;
		$CustomTableCells = $this->_table->CustomTableCells;

		$CustomFields = $CustomFieldValues->CustomFields;
		$CustomFieldTypes = $CustomFields->CustomFieldTypes;

		$CustomForms = $CustomFields->CustomForms;
		$CustomModules = $CustomForms->CustomModules;
		$CustomFormFilters = $CustomForms->CustomFormFilters;
		$CustomFormFields = $CustomForms->CustomFormFields;

		if (isset($customFieldQuery)) {
			$customFields = $customFieldQuery
				->toArray();

			$order = 0;
			$fieldOrder = [];
			$ignoreFields = ['id', 'modified_user_id', 'modified', 'created_user_id', 'created'];
			foreach ($this->_table->fields as $fieldName => $field) {
				if (!in_array($fieldName, $ignoreFields)) {
					$order = $field['order'] > $order ? $field['order'] : $order;
					$fieldOrder[$field['order']] = $fieldName;
				}
			}

			foreach ($customFields as $key => $customField) {
				$_customField = $customField->custom_field;

				$_field_type = $_customField->field_type;
				$_name = $_customField->name;
				$_attr = ['label' => $_name];
				if ($_customField->is_mandatory == 1) {
					$_attr['required'] = 'required';
				}

				$_id = null;
				$_value = null;
				if (isset($entity->id)) {
					$fieldValueData = $CustomFieldTypes
						->find('all')
						->select([$CustomFieldTypes->aliasField('value')])
						->where([$CustomFieldTypes->aliasField('code') => $_field_type])
						->first();
					$fieldValue = $fieldValueData->value;

					$results = $CustomFieldValues
						->find('all')
						->select([
							$CustomFieldValues->aliasField('id'),
							$CustomFieldValues->aliasField($fieldValue),
						])
						->where([
							$CustomFieldValues->aliasField($this->config('fieldKey')) => $_customField->id,
							$CustomFieldValues->aliasField($this->config('recordKey')) => $entity->id
						])
						->all();

					if (!$results->isEmpty()) {
						$data = $results
							->first();

						$_id = $data->id;
						$_value = $data->$fieldValue;
						$_attr['value'] = $_value;
					}
				}

				$this->_table->addBehavior(
					'CustomField.'.Inflector::camelize(strtolower($_field_type))
				);

				$fieldName = "custom_".$key."_field";
				$fieldOrder[$order++] = $fieldName;
				$valueClass = strtolower($_field_type) == 'table' ? 'table-full-width' : '';

				$this->_table->ControllerAction->field($fieldName, [
		            'type' => 'custom_'. strtolower($_field_type),
		            'visible' => true,
		            'field' => $key,
		            'attr' => $_attr,
		            'recordKey' => $this->config('recordKey'),
		            'fieldKey' => $this->config('fieldKey'),
		            'tableColumnKey' => $this->config('tableColumnKey'),
		            'tableRowKey' => $this->config('tableRowKey'),
		            'customField' => $_customField,
		            'id' => $_id,
		            'value' => $_value,
		            'valueClass' => $valueClass
		        ]);
			}

			foreach ($ignoreFields as $key => $field) {
				$fieldOrder[++$order] = $field;
			}
			ksort($fieldOrder);
			$this->_table->ControllerAction->setFieldOrder($fieldOrder);
		}
	}
}
