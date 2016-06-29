<?php
namespace CustomField\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\Event;
use CustomField\Model\Behavior\RenderBehavior;

class RenderTextBehavior extends RenderBehavior {
	public function initialize(array $config) {
        parent::initialize($config);
    }

	public function onGetCustomTextElement(Event $event, $action, $entity, $attr, $options=[]) {
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
            if (isset($fieldValues[$fieldId]['text_value'])) {
                $savedValue = $fieldValues[$fieldId]['text_value'];
            }
        }
        // End

        if ($action == 'view') {
            if (!is_null($savedValue)) {
                $value = $savedValue;
            }
        } else if ($action == 'edit') {
            $form = $event->subject()->Form;
            $form->unlockField($attr['model'].".custom_field_values");
            $fieldPrefix = $attr['model'] . '.custom_field_values.' . $attr['attr']['seq'];

            $options['type'] = 'string';
            if (!is_null($savedValue)) {
                $options['value'] = $savedValue;
            }
            // input mask
            $customField = $attr['customField'];
            if ($customField->has('params') && !empty($customField->params)) {
                $params = json_decode($customField->params, true);
                if (array_key_exists('input_mask', $params) && !empty($params['input_mask'])) {
                    $HtmlField = $event->subject();
                    $HtmlField->includes['jasny']['include'] = true;
                    $options['data-mask'] = $params['input_mask'];
                }
            }
            // End

            $value .= $form->input($fieldPrefix.".text_value", $options);
            $value .= $form->hidden($fieldPrefix.".".$attr['attr']['fieldKey'], ['value' => $fieldId]);
            if (!is_null($savedId)) {
                $value .= $form->hidden($fieldPrefix.".id", ['value' => $savedId]);
            }
            $value = $this->processRelevancyDisabled($entity, $value, $fieldId);
        }

        $event->stopPropagation();
        return $value;
    }

    public function processTextValues(Event $event, Entity $entity, ArrayObject $data, ArrayObject $settings) {
        $settings['valueKey'] = 'text_value';
        $this->processValues($entity, $data, $settings);
    }
}
