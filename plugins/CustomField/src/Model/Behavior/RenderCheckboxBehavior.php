<?php
namespace CustomField\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\Event;
use CustomField\Model\Behavior\RenderBehavior;

class RenderCheckboxBehavior extends RenderBehavior {
	public function initialize(array $config) {
        parent::initialize($config);
    }

	public function onGetCustomCheckboxElement(Event $event, $action, $entity, $attr, $options=[]) {
        $value = '';

        $fieldType = strtolower($this->fieldTypeCode);
        $checkboxOptions = [];
        foreach ($attr['customField']['custom_field_options'] as $key => $obj) {
            $checkboxOptions[$obj->id] = $obj->name;
        }

        $fieldId = $attr['customField']->id;
        $fieldValues = $attr['customFieldValues'];
        $checkedValues = [];
        if (array_key_exists($fieldId, $fieldValues)) {
            $checkedValues = $fieldValues[$fieldId]['number_value'];
        }
        if ($action == 'view') {
            if (!empty($checkedValues)) {
                $answers = [];
                foreach ($checkedValues as $checkedValue) {
                    $answers[] = $checkboxOptions[$checkedValue];
                }
                $value = implode(', ', $answers);
            }
        } else if ($action == 'edit') {
            $form = $event->subject()->Form;

            $html = '';
            $fieldPrefix = $attr['model'] . '.custom_field_values.' . $attr['attr']['seq'];

            foreach ($checkboxOptions as $key => $value) {
                $html .= '<div class="input">';
                    $option = ['label' => false, 'class' => 'icheck-input'];
                    if (!empty($checkedValues)) {
                        if (in_array($key, $checkedValues)) {
                            $option['checked'] = true;
                        }
                    }
                    $html .= $form->checkbox("$fieldPrefix.number_value.$key", $option);
                    $html .= '<label class="selection-label">'. $value .'</label>';
                $html .= '</div>';
            }
            $html .= $form->hidden($fieldPrefix.".".$attr['attr']['fieldKey'], ['value' => $fieldId]);

            $attr['output'] = $html;
            $value = $event->subject()->renderElement('CustomField.Render/'.$fieldType, ['attr' => $attr]);
        }

        $event->stopPropagation();
        return $value;
    }

    public function onSetCheckboxValues(Event $event, Entity $entity, ArrayObject $values, ArrayObject $settings) {
        $fieldKey = $settings['fieldKey'];
        $fieldRecord = $settings['fieldRecord'];

        $fieldId = $fieldRecord->{$fieldKey};
        $checkboxValues = [$fieldRecord->number_value];
        if (array_key_exists($fieldId, $values)) {
            if (is_array($values[$fieldId]['number_value'])) {
                $checkboxValues = array_merge($checkboxValues, $values[$fieldId]['number_value']);
            }
        }

        $settings['fieldValue']['number_value'] = $checkboxValues;
    }

    public function processCheckboxValues(Event $event, Entity $entity, ArrayObject $data, ArrayObject $settings) {
        $settings['valueKey'] = 'number_value';

        $valueKey = $settings['valueKey'];
        $customValue = $settings['customValue'];
        $checkboxValues = $customValue[$valueKey];
        foreach ($checkboxValues as $checkboxKey => $checked) {
            $customValue[$valueKey] = $checkboxKey;
            $settings['customValue'] = $customValue;
            if (!$checked) {
                $settings['deleteFieldIds'][] = $checkboxKey;
            } else {
                $this->processValues($entity, $data, $settings);
            }
        }
    }
}
