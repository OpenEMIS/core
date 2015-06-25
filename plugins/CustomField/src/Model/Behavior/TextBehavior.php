<?php
namespace CustomField\Model\Behavior;

use Cake\ORM\Behavior;

class TextBehavior extends Behavior {
	public function initialize(array $config) {
        parent::initialize($config);
    }
	
	public function getTextElement($field, $entity, $order) {
        $this->_table->ControllerAction->field($field.".text_value", [
            'type' => 'element',
            'order' => $order,
            'element' => 'CustomField.text',
            'visible' => true,
            'field' => $field,
            'fieldKey' => $entity->id,
            'options' => ['label' => $entity->name]
        ]);
    }
}
