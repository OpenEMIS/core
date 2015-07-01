<?php
namespace CustomField\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Utility\Inflector;

class RecordBehavior extends Behavior {
	protected $_defaultConfig = [
		'recordKey' => 'custom_record_id',
		'moduleKey' => 'custom_module_id',
		'fieldKey' => 'custom_field_id',
		'formKey' => 'custom_form_id',
		'tableColumnKey' => 'custom_table_column_id',
		'tableRowKey' => 'custom_table_row_id'
	];

	public function initialize(array $config) {
		parent::initialize($config);
    }

    public function viewAfterAction(Event $event, Entity $entity) {
    	$this->buildCustomFields($entity);
    	return compact('entity');
    }

    public function addEditAfterAction(Event $event, Entity $entity) {
    	$this->buildCustomFields($entity);
    	return compact('entity');
	}

	public function getCustomFieldQuery($entity) {
		$CustomFieldValues = $this->_table->CustomFieldValues;
		$CustomTableCells = $this->_table->CustomTableCells;

		$CustomFields = $CustomFieldValues->CustomFields;
		$CustomFieldTypes = $CustomFields->CustomFieldTypes;

		$CustomForms = $CustomFields->CustomForms;
		$CustomModules = $CustomForms->CustomModules;
		$CustomFormTypes = $CustomForms->CustomFormTypes;
		$CustomFormFields = $CustomForms->CustomFormFields;

		$customFieldQuery = null;
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
			$customModuleResults = $CustomModules
				->find('all')
				->select([
					$CustomModules->aliasField('id'),
					$CustomModules->aliasField('field_option')
				])
				->where([$CustomModules->aliasField('model') => $this->_table->alias()])
				->first();
			$customModuleId = $customModuleResults->id;
			$fieldOption = $customModuleResults->field_option;

			$customFormIds = $CustomForms
				->find('list', ['keyField' => 'id', 'valueField' => 'id'])
				->where([$CustomForms->aliasField($this->config('moduleKey')) => $customModuleId])
				->toArray();

			$genaralResults = $CustomFormTypes
				->find('all')
				->select([$CustomFormTypes->aliasField($this->config('formKey'))])
				->where([
					$CustomFormTypes->aliasField($this->config('formKey').' IN') => $customFormIds,
					$CustomFormTypes->aliasField('custom_type_id') => 0
				])
				->all();

			if ($genaralResults->isEmpty()) {
				$customFieldQuery = $CustomFormFields
					->find('all')
					->find('order')
					->contain(['CustomFields.CustomFieldOptions', 'CustomFields.CustomTableColumns', 'CustomFields.CustomTableRows'])
					->where([$CustomFormFields->aliasField($this->config('formKey') . ' IN') => $customFormIds]);
			} else {
				$genaralData = $genaralResults->first();
				$generalId = $genaralData->{$this->config('formKey')};

				$customFieldQuery = $CustomFormFields
					->find('all')
					->find('order')
					->contain(['CustomFields.CustomFieldOptions', 'CustomFields.CustomTableColumns', 'CustomFields.CustomTableRows'])
					->where([$CustomFormFields->aliasField($this->config('formKey')) => $generalId]);
			}

			if (!is_null($fieldOption)) {
				$modelAlias = $this->_table->ControllerAction->getModel($fieldOption)['model'];
				$fieldOptionKey = Inflector::underscore(Inflector::singularize($modelAlias)) . '_id';

				$fieldOptionId = $entity->$fieldOptionKey;
				$typedResults = $CustomFormTypes
					->find('all')
					->select([$CustomFormTypes->aliasField($this->config('formKey'))])
					->where([
						$CustomFormTypes->aliasField($this->config('formKey').' IN') => $customFormIds,
						$CustomFormTypes->aliasField('custom_type_id') => $fieldOptionId
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
		$CustomFormTypes = $CustomForms->CustomFormTypes;
		$CustomFormFields = $CustomForms->CustomFormFields;

		if (isset($customFieldQuery)) {
			$customFields = $customFieldQuery
				->toArray();

			//get the order of the last static fields
			$order = 0;
			foreach ($this->_table->fields as $fieldName => $field) {
				if (!in_array($fieldName, ['id', 'security_group_id', 'modified_user_id', 'modified', 'created_user_id', 'created'])) {
					$order = $field['order'] > $order ? $field['order'] : $order;
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

				$this->_table->ControllerAction->field($key.".value", [
		            'type' => 'custom_'. strtolower($_field_type),
		            'order' => ++$order,
		            'visible' => true,
		            'field' => $key,
		            'attr' => $_attr,
		            'recordKey' => $this->config('recordKey'),
		            'fieldKey' => $this->config('fieldKey'),
		            'tableColumnKey' => $this->config('tableColumnKey'),
		            'tableRowKey' => $this->config('tableRowKey'),
		            'customField' => $_customField,
		            'id' => $_id,
		            'value' => $_value
		        ]);
			}
		}
	}
}
