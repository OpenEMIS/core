<?php
namespace CustomField\Model\Behavior;

use Cake\ORM\Behavior;

class TextareaBehavior extends Behavior {
	public function initialize(array $config) {
    }

    public function getTextareaElement($field, $entity, $order) {
        $this->_table->ControllerAction->field($field.".textarea_value", [
            'type' => 'element',
            'order' => $order,
            'element' => 'CustomField.textarea',
            'visible' => true,
            'field' => $field,
            'fieldKey' => $entity->id,
            'options' => [
            	'type' => 'textarea',
            	'label' => $entity->name
            ]
        ]);
    }
}
