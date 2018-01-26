<?php
namespace CustomField\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\Event;
use CustomField\Model\Behavior\RenderBehavior;

class RenderCheckboxBehavior extends RenderBehavior
{
    public function initialize(array $config)
    {
        parent::initialize($config);
    }

    public function onGetCustomCheckboxElement(Event $event, $action, $entity, $attr, $options = [])
    {
        $value = '';

        $fieldType = strtolower($this->fieldTypeCode);
        $checkboxOptions = [];
        foreach ($attr['customField']['custom_field_options'] as $key => $obj) {
            $checkboxOptions[$obj->id] = $obj->name;
        }

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

        $checkedValues = [];
        if (!is_null($savedValue)) {
            $checkedValues =  $savedValue;
        }
        if ($action == 'view') {
            if (is_array($checkedValues) && !empty($checkedValues)) {
                $answers = [];
                foreach ($checkedValues as $checkedValue) {
                    $answers[] = $checkboxOptions[$checkedValue];
                }
                $value = implode(', ', $answers);
            }
        } elseif ($action == 'edit') {
            $form = $event->subject()->Form;
            $unlockFields = [];

            $html = '';
            $fieldPrefix = $attr['model'] . '.custom_field_values.' . $attr['attr']['seq'];

            foreach ($checkboxOptions as $key => $value) {
                $html .= '<div class="input">';
                $option = ['kd-checkbox-radio' => ''];
                if (!empty($checkedValues)) {
                    if (in_array($key, $checkedValues)) {
                        $option['checked'] = true;
                    }
                }
                $html .= $form->checkbox("$fieldPrefix.number_value.$key", $option);
                $unlockFields[] = "$fieldPrefix.number_value.$key";
                $html .= '<label class="selection-label">'. $value .'</label>';
                $html .= '</div>';
            }
            $html .= $form->hidden($fieldPrefix.".".$attr['attr']['fieldKey'], ['value' => $fieldId]);
            $unlockFields[] = $fieldPrefix.".".$attr['attr']['fieldKey'];

            $attr['output'] = $html;
            $value = $event->subject()->renderElement('CustomField.Render/'.$fieldType, ['attr' => $attr]);
            $value = $this->processRelevancyDisabled($entity, $value, $fieldId, $form, $unlockFields);
        }

        $event->stopPropagation();
        return $value;
    }

    public function processCheckboxValues(Event $event, Entity $entity, ArrayObject $data, ArrayObject $settings)
    {
        $settings['valueKey'] = 'number_value';

        $fieldKey = $settings['fieldKey'];
        $valueKey = $settings['valueKey'];
        $customValue = $settings['customValue'];

        $settings['deleteFieldIds'][] = $customValue[$fieldKey];
        $checkboxValues = $customValue[$valueKey];
        foreach ($checkboxValues as $checkboxKey => $checked) {
            $customValue[$valueKey] = $checkboxKey;
            $settings['customValue'] = $customValue;
            if ($checked) {
                $this->processValues($entity, $data, $settings);
            }
        }
    }
}
