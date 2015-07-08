<?php
namespace CustomField\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\Event\Event;

class NumberBehavior extends Behavior {
	public function initialize(array $config) {
        parent::initialize($config);
    }

    public function onGetCustomNumberElement(Event $event, $action, $entity, $attr, $options=[]) {
        $value = '';

        if ($action == 'view') {
            $value = !is_null($attr['value']) ? $attr['value'] : '';
        } else if ($action == 'edit') {
            $form = $event->subject()->Form;
            $options['type'] = 'number';

            $fieldPrefix = $attr['model'] . '.custom_field_values.' . $attr['field'];
            $value = $form->input($fieldPrefix.".number_value", $options);
            $value .= $form->hidden($fieldPrefix.".".$attr['fieldKey'], ['value' => $attr['customField']->id]);
            if (!is_null($attr['id'])) {
                $value .= $form->hidden($fieldPrefix.".id", ['value' => $attr['id']]);
            }
        }

        return $value;
    }
}
