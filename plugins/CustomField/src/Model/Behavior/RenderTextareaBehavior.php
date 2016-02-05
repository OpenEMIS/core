<?php
namespace CustomField\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\Event;
use CustomField\Model\Behavior\RenderBehavior;

class RenderTextareaBehavior extends RenderBehavior {
	public function initialize(array $config) {
        parent::initialize($config);
    }

	public function onGetCustomTextareaElement(Event $event, $action, $entity, $attr, $options=[]) {
        $value = '';

        $fieldId = $attr['customField']->id;
        $fieldValues = $attr['customFieldValues'];
        if ($action == 'view') {
            if (array_key_exists($fieldId, $fieldValues)) {
                $value = nl2br($fieldValues[$fieldId]['textarea_value']);
            }
        } else if ($action == 'edit') {
            $form = $event->subject()->Form;
            $fieldPrefix = $attr['model'] . '.custom_field_values.' . $attr['attr']['seq'];

            $options['type'] = 'textarea';
            // for edit
            // if (array_key_exists($fieldId, $fieldValues)) {
            //     if ($attr['attr']['request'] == 'get') {
            //         $options['value'] = $fieldValues[$fieldId]['textarea_value'];
            //     }
            //     $value .= $form->hidden($fieldPrefix.".id", ['value' => $fieldValues[$fieldId]['id']]);
            // }
            $value .= $form->input($fieldPrefix.".textarea_value", $options);
            $value .= $form->hidden($fieldPrefix.".".$attr['attr']['fieldKey'], ['value' => $fieldId]);
        }

        $event->stopPropagation();
        return $value;
    }

    public function processTextareaValues(Event $event, Entity $entity, ArrayObject $data, ArrayObject $settings) {
        $settings['valueKey'] = 'textarea_value';
        $this->processValues($entity, $data, $settings);
    }
}
