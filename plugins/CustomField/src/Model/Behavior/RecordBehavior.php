<?php
namespace CustomField\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Utility\Inflector;

class RecordBehavior extends Behavior {
	protected $_defaultConfig = [
		'recordKey' => 'custom_record_id'
	];

	public function initialize(array $config) {
		parent::initialize($config);
    }

    public function addEditAfterAction(Event $event, Entity $entity) {
		$CustomFieldValues = $this->_table->CustomFieldValues;
		$CustomTableCells = $this->_table->CustomTableCells;

		$CustomFields = $CustomFieldValues->CustomFields;
		$CustomFieldTypes = $CustomFields->CustomFieldTypes;

		$CustomForms = $CustomFields->CustomForms;
		$CustomModules = $CustomForms->CustomModules;
		$CustomFormTypes = $CustomForms->CustomFormTypes;
		$CustomFormFields = $CustomForms->CustomFormFields;

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
			->where([$CustomForms->aliasField('custom_module_id') => $customModuleId])
			->toArray();

		$genaralResults = $CustomFormTypes
			->find('all')
			->select([$CustomFormTypes->aliasField('custom_form_id')])
			->where([
				$CustomFormTypes->aliasField('custom_form_id IN') => $customFormIds,
				$CustomFormTypes->aliasField('custom_type_id') => 0
			])
			->all();

		if (!$genaralResults->isEmpty()) {
			$genaralData = $genaralResults->first();
			$generalId = $genaralData->custom_form_id;

			$customFieldQuery = $CustomFormFields
				->find('all')
				->find('order')
				->contain(['CustomFields.CustomFieldOptions', 'CustomFields.CustomTableColumns', 'CustomFields.CustomTableRows'])
				->where([$CustomFormFields->aliasField('custom_form_id') => $generalId]);
		}

		if (!is_null($fieldOption)) {
			$modelAlias = $this->_table->ControllerAction->getModel($fieldOption)['model'];
			$fieldOptionKey = Inflector::underscore(Inflector::singularize($modelAlias)) . '_id';

			$fieldOptionId = $entity->$fieldOptionKey;
			$typedResults = $CustomFormTypes
				->find('all')
				->select([$CustomFormTypes->aliasField('custom_form_id')])
				->where([
					$CustomFormTypes->aliasField('custom_form_id IN') => $customFormIds,
					$CustomFormTypes->aliasField('custom_type_id') => $fieldOptionId
				])
				->all();

			if (!$typedResults->isEmpty()) {
				$typedData = $typedResults->first();
				$typedId = $typedData->custom_form_id;

				$typedCustomFieldQuery = $CustomFormFields
					->find('all')
					->find('order')
					->contain(['CustomFields.CustomFieldOptions', 'CustomFields.CustomTableColumns', 'CustomFields.CustomTableRows'])
					->where([$CustomFormFields->aliasField('custom_form_id') => $typedId]);

				$customFieldQuery->union($typedCustomFieldQuery);
			}
		}

		$customFields = $customFieldQuery
			->toArray();

		//get the order of the last static fields
		$order = 0;
		foreach ($this->_table->fields as $fieldName => $field) {
			if (!in_array($fieldName, ['id', 'modified_user_id', 'modified', 'created_user_id', 'created'])) {
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
						$CustomFieldValues->aliasField('custom_field_id') => $_customField->id,
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
	            'customField' => $_customField,
	            'id' => $_id,
	            'value' => $_value
	        ]);
		}

		return compact('entity');
	}
}
