<?php
namespace CustomField\Model\Behavior;

use Cake\ORM\Behavior;

class DropdownBehavior extends Behavior {
	public function initialize(array $config) {
        $this->_table->ControllerAction->addField('options', [
            'type' => 'element',
            'order' => 5,
            'element' => 'CustomField.field_options',
            'visible' => true
        ]);
    }
}
