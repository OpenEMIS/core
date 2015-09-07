<?php
namespace CustomField\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\Event\Event;

class StudentListBehavior extends Behavior {
	public function initialize(array $config) {
        parent::initialize($config);
    }

    public function onGetCustomStudentListElement(Event $event, $action, $entity, $attr, $options=[]) {
        $value = '';

        if ($action == 'view') {
            $value = !is_null($attr['value']) ? $attr['value'] : '';
        } else if ($action == 'edit') {
            $form = $event->subject()->Form;
            $options['type'] = 'string';

            $fieldPrefix = $attr['model'] . '.custom_field_values.' . $attr['field'];
            $value = $form->input($fieldPrefix.".text_value", $options);
            $value .= $form->hidden($fieldPrefix.".".$attr['fieldKey'], ['value' => $attr['customField']->id]);
            if (!is_null($attr['id'])) {
                $value .= $form->hidden($fieldPrefix.".id", ['value' => $attr['id']]);
            }
        }

        return $value;
    }
}
