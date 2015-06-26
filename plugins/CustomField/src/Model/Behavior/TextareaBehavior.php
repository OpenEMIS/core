<?php
namespace CustomField\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\Event\Event;

class TextareaBehavior extends Behavior {
	public function initialize(array $config) {
    }

    public function onGetCustomTextareaElement(Event $event, $action, $entity, $attr, $options=[]) {
        $value = '';

        if ($action == 'index' || $action == 'view') {
            //$value = $data->$attr['field'];
        } else if ($action == 'edit') {
            $form = $event->subject()->Form;
            $options['type'] = 'textarea';

            $fieldPrefix = $attr['model'] . '.custom_field_values.' . $attr['field'];
            $value = $form->input($fieldPrefix.".textarea_value", $options);
            $value .= $form->hidden($fieldPrefix.".custom_field_id", ['value' => $attr['customField']->id]);
        }

        return $value;
    }
}
