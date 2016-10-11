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

        // for edit
        $fieldId = $attr['customField']->id;
        $fieldValues = $attr['customFieldValues'];
        $savedId = null;
        $savedValue = null;
        if (!empty($fieldValues) && array_key_exists($fieldId, $fieldValues)) {
            if (isset($fieldValues[$fieldId]['id'])) {
                $savedId = $fieldValues[$fieldId]['id'];
            }
            if (isset($fieldValues[$fieldId]['number_value'])) {
                $savedValue = $fieldValues[$fieldId]['number_value'];
            }
        }
        // End

        if ($action == 'view') {
            if (!is_null($savedValue)) {
                $value = $savedValue;
            }
        } else if ($action == 'edit') {
            $form = $event->subject()->Form;
            $unlockFields = [];
            $fieldPrefix = $attr['model'] . '.custom_field_values.' . $attr['attr']['seq'];

            $options['type'] = 'number';
            if (!is_null($savedValue)) {
                $options['value'] = $savedValue;
            }
            $value .= $form->input($fieldPrefix.".number_value", $options);
            $value .= $form->hidden($fieldPrefix.".".$attr['attr']['fieldKey'], ['value' => $fieldId]);
            $unlockFields[] = $fieldPrefix.".number_value";
            $unlockFields[] = $fieldPrefix.".".$attr['attr']['fieldKey'];
            if (!is_null($savedId)) {
                $value .= $form->hidden($fieldPrefix.".id", ['value' => $savedId]);
                $unlockFields[] = $fieldPrefix.".id";
            }
            $value = $this->processRelevancyDisabled($entity, $value, $fieldId, $form, $unlockFields);
        }

        $event->stopPropagation();
        return $value;
    }

    public function processNumberValues(Event $event, Entity $entity, ArrayObject $data, ArrayObject $settings) {
        $settings['valueKey'] = 'number_value';
        $this->processValues($entity, $data, $settings);
    }
}
