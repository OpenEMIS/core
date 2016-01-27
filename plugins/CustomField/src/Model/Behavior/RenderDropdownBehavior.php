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

        if ($action == 'view') {
            // if (!empty($dropdownOptions)) {
            //     $valueKey = !is_null($attr['value']) ? $attr['value'] : key($dropdownOptions);
            //     $value = $dropdownOptions[$valueKey];
            // }
        } else if ($action == 'edit') {
            $form = $event->subject()->Form;
            $options['type'] = 'select';
            // $options['default'] = !is_null($attr['value']) ? $attr['value'] : $dropdownDefault;
            // $options['value'] = !is_null($attr['value']) ? $attr['value'] : $dropdownDefault;
            $options['options'] = $dropdownOptions;

            $fieldPrefix = $attr['model'] . '.custom_field_values.' . $attr['attr']['key'];
            $value = $form->input($fieldPrefix.".number_value", $options);
            $value .= $form->hidden($fieldPrefix.".".$attr['fieldKey'], ['value' => $attr['customField']->id]);
            // if (!is_null($attr['id'])) {
            //     $value .= $form->hidden($fieldPrefix.".id", ['value' => $attr['id']]);
            // }
        }

        $event->stopPropagation();
        return $value;
    }

    public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
    }
}
