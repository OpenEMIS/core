<?php
namespace CustomField\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\Event;
use CustomField\Model\Behavior\RenderBehavior;

class RenderNumberBehavior extends RenderBehavior {
	public function initialize(array $config) {
        parent::initialize($config);
    }

    public function onGetCustomNumberElement(Event $event, $action, $entity, $attr, $options=[]) {
        $value = '';

        if ($action == 'view') {
            // $value = !is_null($attr['value']) ? $attr['value'] : '';
        } else if ($action == 'edit') {
            $form = $event->subject()->Form;
            $options['type'] = 'number';

            $fieldPrefix = $attr['model'] . '.custom_field_values.' . $attr['attr']['key'];
            $value = $form->input($fieldPrefix.".number_value", $options);
            $value .= $form->hidden($fieldPrefix.".".$attr['fieldKey'], ['value' => $attr['customField']->id]);
            // if (!is_null($attr['id'])) {
            //     $value .= $form->hidden($fieldPrefix.".id", ['value' => $attr['id']]);
            // }
        }

        $event->stopPropagation();
        return $value;
    }

    public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
    }
}
