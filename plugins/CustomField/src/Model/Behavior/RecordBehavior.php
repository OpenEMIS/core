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
		$customFields = $CustomFormFields->find('all')->find('order')->contain(['CustomFields.CustomFieldOptions', 'CustomFields.CustomTableColumns', 'CustomFields.CustomTableRows'])->where([$CustomFormFields->aliasField('custom_form_id') => $entity->custom_form_id])->all()->toArray();

		$order = 0;
		foreach ($this->_table->fields as $fieldName => $field) {
			if (!in_array($fieldName, ['id', 'modified_user_id', 'modified', 'created_user_id', 'created'])) {
				//pr($fieldName);pr($field);
				$order = $field['order'] > $order ? $field['order'] : $order;
			}
		}

		foreach ($customFields as $key => $customField) {
			$_field = $this->_table->alias().".custom_field_values.".$key;
			//$_name = $customField->custom_field->name;
			$_type = $customField->custom_field->field_type;
			$_entity = $customField->custom_field;

			$this->_table->addBehavior(
				'CustomField.'.Inflector::camelize(strtolower($_type))
			);

			$method = 'get' . Inflector::humanize(strtolower($_type)) . 'Element';
			if ($this->_table->behaviors()->hasMethod($method)) {
				$this->_table->$method($_field, $_entity, ++$order);
			} else {
				
			}
		}

		return compact('entity');
	}
}
