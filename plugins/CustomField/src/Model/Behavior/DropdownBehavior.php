<?php
namespace CustomField\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\Event\Event;

class DropdownBehavior extends Behavior {
	public function initialize(array $config) {
		parent::initialize($config);
		if (isset($config['setup']) && $config['setup'] == true) {
			$this->_table->ControllerAction->addField('options', [
	            'type' => 'element',
	            'order' => 5,
	            'element' => 'CustomField.CustomFields/dropdown',
	            'visible' => true
	        ]);
        }
    }

    public function addEditBeforePatch(Event $event, Entity $entity, array $data, array $options) {
		if (isset($data[$this->_table->alias()]['is_default']) && !empty($data[$this->_table->alias()]['custom_field_options'])) {
			$defaultKey = $data[$this->_table->alias()]['is_default'];
			$data[$this->_table->alias()]['custom_field_options'][$defaultKey]['is_default'] = 1;
		}
    	return compact('entity', 'data', 'options');
    }

    public function addEditOnAddDropdownOption(Event $event, Entity $entity, array $data, array $options) {
		$fieldOptions = [
			'name' => '',
			'visible' => 1
		];
		$data[$this->_table->alias()]['custom_field_options'][] = $fieldOptions;

		//Validation is disabled by default when onReload, however immediate line below will not work and have to disabled validation for associated model like the following lines
		$options['associated'] = [
			'CustomFieldOptions' => ['validate' => false]
		];

		return compact('entity', 'data', 'options');
	}

	public function getDropdownElement($field, $entity, $order) {		
		$dropdownOptions = [];
		foreach ($entity['custom_field_options'] as $key => $obj) {
			$dropdownOptions[$obj->id] = $obj->name;
		}

		$this->_table->ControllerAction->field($field.".number_value", [
            'type' => 'element',
            'order' => $order,
            'element' => 'CustomField.dropdown',
            'visible' => true,
            'field' => $field,
            'fieldKey' => $entity->id,
            'options' => [
            	'label' => $entity->name,
            	'options' => $dropdownOptions
            ]
        ]);
    }
}
