<?php
namespace CustomField\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\Event;
use CustomField\Model\Behavior\RenderBehavior;

class RenderDropdownBehavior extends RenderBehavior {
	public function initialize(array $config) {
        parent::initialize($config);
    }

	public function onGetCustomDropdownElement(Event $event, $action, $entity, $attr, $options=[]) {
        $value = '';

        $dropdownOptions = [];
        $dropdownDefault = null;
        foreach ($attr['customField']['custom_field_options'] as $key => $obj) {
            $dropdownOptions[$obj->id] = $obj->name;
            if ($obj->is_default == 1) {
                $dropdownDefault = $obj->id;
            }
        }
        // default to first key if is not set
        $dropdownDefault = !is_null($dropdownDefault) ? $dropdownDefault : key($dropdownOptions);

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
                $value = $dropdownOptions[$savedValue];
            }
        } else if ($action == 'edit') {
            $form = $event->subject()->Form;
            $fieldPrefix = $attr['model'] . '.custom_field_values.' . $attr['attr']['seq'];

            $options['type'] = 'select';
            $options['options'] = $dropdownOptions;

            if ($this->_table->request->is(['get'])) {
                $selectedValue = !is_null($savedValue) ? $savedValue : $dropdownDefault;
                $options['default'] = $selectedValue;
                $options['value'] = $selectedValue;
            }
            $value .= $form->input($fieldPrefix.".number_value", $options);
            $value .= $form->hidden($fieldPrefix.".".$attr['attr']['fieldKey'], ['value' => $fieldId]);
            if (!is_null($savedId)) {
                $value .= $form->hidden($fieldPrefix.".id", ['value' => $savedId]);
            }
        }

        $event->stopPropagation();
        return $value;
    }

    public function processDropdownValues(Event $event, Entity $entity, ArrayObject $data, ArrayObject $settings) {
        $settings['valueKey'] = 'number_value';
        $this->processValues($entity, $data, $settings);
    }
}
