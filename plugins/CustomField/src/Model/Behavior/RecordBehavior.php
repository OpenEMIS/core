<?php
namespace CustomField\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Utility\Inflector;

class RecordBehavior extends Behavior {
	public function initialize(array $config) {
		parent::initialize($config);
    }

    public function addEditAfterAction(Event $event, Entity $entity) {
    	$CustomFormFields = $this->_table->CustomForms->CustomFormFields;
		$customFields = $CustomFormFields
			->find('all')
			->find('order')
			->contain(['CustomFields.CustomFieldOptions', 'CustomFields.CustomTableColumns', 'CustomFields.CustomTableRows'])
			->where([$CustomFormFields->aliasField('custom_form_id') => $entity->custom_form_id])
			->all()
			->toArray();

		//get the order of the last static fields
		$order = 0;
		foreach ($this->_table->fields as $fieldName => $field) {
			if (!in_array($fieldName, ['id', 'modified_user_id', 'modified', 'created_user_id', 'created'])) {
				$order = $field['order'] > $order ? $field['order'] : $order;
			}
		}

		$CustomFieldTypes = $this->_table->CustomFieldValues->CustomFields->CustomFieldTypes;
		$CustomFieldValues = $this->_table->CustomFieldValues;

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
						$CustomFieldValues->aliasField('custom_record_id') => $entity->id
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
	            'customField' => $_customField,
	            'id' => $_id,
	            'value' => $_value
	        ]);
		}

		return compact('entity');
	}
}
