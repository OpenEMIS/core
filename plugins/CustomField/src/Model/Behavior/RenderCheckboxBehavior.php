<?php
namespace CustomField\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\EventInterface;
use CustomField\Model\Behavior\RenderBehavior;

class RenderCheckboxBehavior extends RenderBehavior
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
    }

    public function onGetCustomCheckboxElement(EventInterface $event, $action, $entity, $attr, $options = [])
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
            $form = $event->getSubject()->Form;
            $unlockFields = [];

            $html = '';
            $fieldPrefix = $attr['model'] . '.custom_field_values.' . $attr['attr']['seq'];

            foreach ($checkboxOptions as $key => $value) {
                $html .= '<div class="input" style="display:flex;">';//POCOR-7950
                $option = ['kd-checkbox-radio' => ''];
                if (!empty($checkedValues)) {
                    if (in_array($key, $checkedValues)) {
                        $option['checked'] = true;
                    }
                }
                $html .= $form->checkbox("$fieldPrefix.number_value.$key", $option);
                $unlockFields[] = "$fieldPrefix.number_value.$key";
                $html .= '<label class="selection-label" style="padding:0 20px 0 0!important;">'. $value .'</label>';//POCOR-7950
                $html .= '</div>';
            }
            $html .= $form->hidden($fieldPrefix.".".$attr['attr']['fieldKey'], ['value' => $fieldId]);
            $unlockFields[] = $fieldPrefix.".".$attr['attr']['fieldKey'];

            //POCOR-6233: start - detect validation error on this checkbox field so the template can show inline error
            $seq = $attr['attr']['seq'];
            $fieldError = null;
            if (method_exists($entity, 'getErrors')) {
                $entityErrors = $entity->getErrors();
                if (isset($entityErrors['custom_field_values'][$seq]['number_value'])) {
                    $errs = $entityErrors['custom_field_values'][$seq]['number_value'];
                    $fieldError = is_array($errs) ? reset($errs) : $errs;
                }
            }
            $attr['error'] = $fieldError;
            //POCOR-6233: end

            $attr['output'] = $html;
            $value = $event->getSubject()->renderElement('CustomField.Render/'.$fieldType, ['attr' => $attr]);
            $value = $this->processRelevancyDisabled($entity, $value, $fieldId, $form, $unlockFields);
        }

        $event->stopPropagation();
        return $value;
    }

    public function processCheckboxValues(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $settings)
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
