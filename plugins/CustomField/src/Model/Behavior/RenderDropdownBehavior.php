<?php
namespace CustomField\Model\Behavior;

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

        $fieldId = $attr['customField']->id;
        $fieldValues = $attr['customFieldValues'];
        if ($action == 'view') {
            if (!empty($dropdownOptions)) {
                if (array_key_exists($fieldId, $fieldValues)) {
                    $selectedValue = !is_null($fieldValues[$fieldId]['number_value']) ? $fieldValues[$fieldId]['number_value'] : $dropdownDefault;
                    $value = $dropdownOptions[$selectedValue];
                }
            }
        } else if ($action == 'edit') {
            $form = $event->subject()->Form;
            $options['type'] = 'select';
            $options['options'] = $dropdownOptions;

            $fieldPrefix = $attr['model'] . '.custom_field_values.' . $attr['attr']['key'];

            if (array_key_exists($fieldId, $fieldValues)) {
                $selectedValue = !is_null($fieldValues[$fieldId]['number_value']) ? $fieldValues[$fieldId]['number_value'] : $dropdownDefault;
                $options['default'] = $selectedValue;
                $options['value'] = $selectedValue;

                $value .= $form->hidden($fieldPrefix.".id", ['value' => $fieldValues[$fieldId]['id']]);
            }
            $value .= $form->input($fieldPrefix.".number_value", $options);
            $value .= $form->hidden($fieldPrefix.".".$attr['fieldKey'], ['value' => $fieldId]);
        }

        $event->stopPropagation();
        return $value;
    }
}
