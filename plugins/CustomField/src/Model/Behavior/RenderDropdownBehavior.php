<?php
namespace CustomField\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\Event;
use CustomField\Model\Behavior\RenderBehavior;

class RenderDropdownBehavior extends RenderBehavior {

    private $postedData = null;
	public function initialize(array $config) {
        parent::initialize($config);
    }

	public function onGetCustomDropdownElement(Event $event, $action, $entity, $attr, $options=[]) {
        $value = '';

        $dropdownOptions = [];
        $dropdownDefault = '';
        foreach ($attr['customField']['custom_field_options'] as $key => $obj) {
            $dropdownOptions[$obj->id] = $obj->name;
            if ($obj->is_default == 1) {
                $dropdownDefault = $obj->id;
            }
        }

        // for edit
        $fieldKey = $attr['attr']['fieldKey'];
        $formKey = $attr['attr']['formKey'];
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
                $value = $dropdownOptions[$savedValue];
            }
        } else if ($action == 'edit') {
            $form = $event->subject()->Form;
            $unlockFields = [];
            $fieldPrefix = $attr['model'] . '.custom_field_values.' . $attr['attr']['seq'];

            $options['type'] = 'select';
            $options['empty'] = '-- ' . __('Select') . ' --';
            $options['options'] = $dropdownOptions;
            $options['ng-model'] = 'RelevancyRulesController.Dropdown["'.$fieldId.'"]';
            $options['ng-init'] = 'RelevancyRulesController.Dropdown["'.$fieldId.'"] = "";';
            if ($this->_table->request->is(['get'])) {
                $selectedValue = !is_null($savedValue) ? $savedValue : $dropdownDefault;
                $options['default'] = $selectedValue;
                $options['value'] = $selectedValue;
                $options['ng-init'] = 'RelevancyRulesController.Dropdown["'.$fieldId.'"] = "'.$selectedValue.'";';
            } else {
                if (is_null($this->postedData)) {
                    if (array_key_exists($this->_table->alias(), $this->_table->request->data)) {
                        if (array_key_exists('custom_field_values', $this->_table->request->data[$this->_table->alias()])) {
                            $questions = $this->_table->request->data[$this->_table->alias()]['custom_field_values'];
                            foreach ($questions as $question) {
                                if (isset($question['number_value'])) {
                                    if (array_key_exists($fieldKey, $question)) {
                                        $this->postedData[$question[$fieldKey]] = $question['number_value'];
                                    }
                                    // put data back when validation fails
                                    if (array_key_exists($fieldId, $this->postedData)) {
                                        $selectedValue = $this->postedData[$fieldId];
                                        $options['ng-init'] = 'RelevancyRulesController.Dropdown["'.$fieldId.'"] = "'.$selectedValue.'";';
                                    }
                                }
                            }
                        }
                    }
                } else {
                    if (array_key_exists($fieldId, $this->postedData)) {
                        $selectedValue = $this->postedData[$fieldId];
                        $options['ng-init'] = 'RelevancyRulesController.Dropdown["'.$fieldId.'"] = "'.$selectedValue.'";';
                    }
                }
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

    public function processDropdownValues(Event $event, Entity $entity, ArrayObject $data, ArrayObject $settings) {
        $settings['valueKey'] = 'number_value';
        $this->processValues($entity, $data, $settings);
    }
}
