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

            $hasInteracted = !empty($checkedValues) ? 1 : 0;
            $isMandatory   = isset($attr['attr']['required']) && $attr['attr']['required'] === 'required';
            $showSilent    = $isMandatory && !$hasInteracted;

            $groupId     = 'cf-group-' . $fieldId;
            $fieldPrefix = $attr['model'] . '.custom_field_values.' . $attr['attr']['seq'];

            // Wrapper div — carries silent state data attribute
            $html = '<div id="' . $groupId . '"'
                  . ' data-has-interacted="' . $hasInteracted . '"'
                  . ($showSilent ? ' class="cf-checkbox-silent"' : '') . '>';

            foreach ($checkboxOptions as $key => $value) {
                $html .= '<div class="input" style="display:flex;">';
                $option = ['kd-checkbox-radio' => ''];
                if (!empty($checkedValues) && in_array($key, $checkedValues)) {
                    $option['checked'] = true;
                }
                $html .= $form->checkbox("$fieldPrefix.number_value.$key", $option);
                $unlockFields[] = "$fieldPrefix.number_value.$key";
                $labelColor = $showSilent ? 'color:#999;' : '';
                $html .= '<label class="selection-label" style="padding:0 20px 0 0!important;' . $labelColor . '">' . $value . '</label>';
                $html .= '</div>';
            }

            // has_interacted hidden field — validated server-side
            $hasInteractedFieldId = 'has-interacted-' . $fieldId;
            $html .= $form->hidden("$fieldPrefix.has_interacted", [
                'value' => $hasInteracted,
                'id'    => $hasInteractedFieldId
            ]);
            $unlockFields[] = "$fieldPrefix.has_interacted";

            $html .= $form->hidden($fieldPrefix . '.' . $attr['attr']['fieldKey'], ['value' => $fieldId]);
            $unlockFields[] = $fieldPrefix . '.' . $attr['attr']['fieldKey'];

            $html .= '</div>'; // end group wrapper

            // Inline JS: set indeterminate on load; clear on first interaction
            if ($showSilent) {
                $html .= '<script>(function(){
            var g  = document.getElementById("' . $groupId . '");
            var cb = g.querySelectorAll("input[type='checkbox']");
            cb.forEach(function(el){ el.indeterminate = true; });
            g.addEventListener("change", function handler(e){
                if (e.target.type === "checkbox") {
                    g.dataset.hasInteracted = "1";
                    g.classList.remove("cf-checkbox-silent");
                    cb.forEach(function(el){ el.indeterminate = false; });
                    document.getElementById("' . $hasInteractedFieldId . '").value = "1";
                    g.removeEventListener("change", handler);
                }
            });
        })();</script>';
            }

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
