<?php
namespace CustomField\Model\Behavior;

use Cake\ORM\Behavior;

class NumberBehavior extends Behavior {
	public function initialize(array $config) {
        parent::initialize($config);
    }

    public function getNumberElement($field, $entity, $order) {
        $this->_table->ControllerAction->field($field.".number_value", [
            'type' => 'element',
            'order' => $order,
            'element' => 'CustomField.number',
            'visible' => true,
            'field' => $field,
            'fieldKey' => $entity->id,
            'options' => ['label' => $entity->name]
        ]);
    }
}
