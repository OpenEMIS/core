<?php
namespace CustomField\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\Event\Event;

class CheckboxBehavior extends Behavior {
	public function initialize(array $config) {
		parent::initialize($config);
		if (isset($config['setup']) && $config['setup'] == true) {
			$this->_table->ControllerAction->addField('options', [
	            'type' => 'element',
	            'order' => 5,
	            'element' => 'CustomField.CustomFields/checkbox',
	            'visible' => true
	        ]);
		}
    }

    public function addEditOnAddCheckboxOption(Event $event, Entity $entity, array $data, array $options) {
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

	public function getCheckboxElement($field, $entity, $order) {		
		$checkboxOptions = [];
		foreach ($entity['custom_field_options'] as $key => $obj) {
			$checkboxOptions[$obj->id] = $obj->name;
		}

		$this->_table->ControllerAction->field($field.".number_value", [
            'type' => 'element',
            'order' => $order,
            'element' => 'CustomField.checkbox',
            'visible' => true,
            'field' => $field,
            'fieldKey' => $entity->id,
            'options' => [
            	//'type' => 'checkbox',
            	'label' => $entity->name,
            	'options' => $checkboxOptions
            ]
        ]);
    }
}
