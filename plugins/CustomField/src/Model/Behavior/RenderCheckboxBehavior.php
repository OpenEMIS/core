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
            $fieldPrefix = $attr['model'] . '.custom_field_values.' . $attr['attr']['key'];

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
            $html .= $form->hidden($fieldPrefix.".".$attr['fieldKey'], ['value' => $fieldId]);

            $attr['output'] = $html;
            $value = $event->subject()->renderElement('CustomField.Render/'.$fieldType, ['attr' => $attr]);
        }

        $event->stopPropagation();
        return $value;
    }

    public function onSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $settings) {
        $alias = $this->_table->alias();
        $values = $data[$alias]['custom_field_values'];
        $fieldKey = $settings['fieldKey'];
        $recordKey = $settings['recordKey'];

        $count = 0;
        $checkboxes = [];
        $fieldIds = [];

        foreach ($values as $key => $obj) {
            if (array_key_exists('number_value', $obj) && is_array($obj['number_value'])) {
                $checkboxes[] = $obj;
                $fieldIds[$obj[$fieldKey]] = $obj[$fieldKey];
                unset($data[$alias]['custom_field_values'][$key]);
            }
            $count++;
        }

        foreach ($checkboxes as $checkbox) {
            $checkboxValues = $checkbox['number_value'];

            foreach ($checkboxValues as $checkboxKey => $checked) {
                if ($checked) {
                    $checkbox['number_value'] = $checkboxKey;
                    $data[$alias]['custom_field_values'][++$count] = $checkbox;
                }
            }
        }

        if (array_key_exists('id', $data[$alias])) {
            if (!empty($fieldIds)) {
                $id = $data[$alias]['id'];
                $CustomFieldValues = $this->_table->CustomFieldValues;

                $CustomFieldValues->deleteAll([
                    $CustomFieldValues->aliasField($recordKey) => $id,
                    $CustomFieldValues->aliasField($fieldKey . ' IN ') => $fieldIds
                ]);
            }
        }

        $requestData = $data->getArrayCopy();
        $entity = $this->_table->patchEntity($entity, $requestData);
    }
}
