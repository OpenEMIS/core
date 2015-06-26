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
				$order = $field['order'] > $order ? $field['order'] : $order;
			}
		}

		foreach ($customFields as $key => $customField) {
			$_type = strtolower($customField->custom_field->field_type);
			$_customField = $customField->custom_field;

			$this->_table->addBehavior(
				'CustomField.'.Inflector::camelize(strtolower($_type))
			);

			$this->_table->ControllerAction->field($key.".value", [
	            'type' => 'custom_'. $_type,
	            'order' => ++$order,
	            'visible' => true,
	            'field' => $key,
	            'attr' => ['label' => $_customField->name],
	            'customField' => $_customField
	        ]);
		}

		return compact('entity');
	}
}
