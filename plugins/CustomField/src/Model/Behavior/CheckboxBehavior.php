<?php
namespace CustomField\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\Event\Event;

class CheckboxBehavior extends Behavior {
	public function initialize(array $config) {
		$this->_table->ControllerAction->addField('options', [
            'type' => 'element',
            'order' => 5,
            'element' => 'CustomField.checkbox',
            'visible' => true
        ]);
    }

    public function addEditOnAddOption(Event $event, Entity $entity, array $data, array $options) {
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
}
