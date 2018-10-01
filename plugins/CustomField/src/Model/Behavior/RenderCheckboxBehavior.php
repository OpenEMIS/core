<?php
namespace CustomField\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Log\Log;
use CustomField\Model\Behavior\RenderBehavior;

class RenderCheckboxBehavior extends RenderBehavior
{   
    private $postedData = null;
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
        $blah = [];
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
            // putting back user data if validation fails.
            if ($entity->errors()) {
                if (array_key_exists($this->_table->alias(), $this->_table->request->data)) {
                    if (array_key_exists('custom_field_values', $this->_table->request->data[$this->_table->alias()])) {
                        $questions = $this->_table->request->data[$this->_table->alias()]['custom_field_values'];
                        foreach ($questions as $question) {
                            if ($question['survey_question_id'] == $fieldId) {
                                $this->postedData[$question['survey_question_id']] = $question;
                            }
                        }
                    }
                }
            }
            $html = '';
            $fieldPrefix = $attr['model'] . '.custom_field_values.' . $attr['attr']['seq'];

            foreach ($checkboxOptions as $key => $value) {
                $html .= '<div class="input">';
                $option = ['kd-checkbox-radio' => ''];
                if (!empty($checkedValues)) {
                    if (in_array($key, $checkedValues)) {
                        $option['checked'] = true;
                    }
                } elseif (!empty($this->postedData) && isset($this->postedData[$fieldId]['number_value'][$key])) {
                    if ($this->postedData[$fieldId]['number_value'][$key]) {
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
