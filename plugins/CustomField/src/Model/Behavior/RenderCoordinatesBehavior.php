<?php
namespace CustomField\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use CustomField\Model\Behavior\RenderBehavior;
use Cake\I18n\Time;

use Cake\View\Helper\IdGeneratorTrait;

class RenderCoordinatesBehavior extends RenderBehavior {
	use IdGeneratorTrait;

	public function initialize(array $config) {
        parent::initialize($config);
    }

	public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options) {
		$dataArray = $data->getArrayCopy();
		if (array_key_exists('custom_field_values', $dataArray)) {
			foreach ($dataArray['custom_field_values'] as $key => $value) {
				if (array_key_exists('text_value', $value)) {
					if (array_key_exists('field_type', $dataArray['custom_field_values'][$key])) {
						if ($dataArray['custom_field_values'][$key]['field_type'] == $this->fieldTypeCode) {
							// $convertedTime = $this->convertForTimePicker($dataArray['custom_field_values'][$key]['text_value']);
							// $data['custom_field_values'][$key]['text_value'] = (!empty($convertedTime))? $convertedTime: $data['custom_field_values'][$key]['text_value'];
						}
					}
				}
			}
		}
	}

	public function onGetCustomCoordinatesElement(Event $event, $action, $entity, $attr, $options=[]) {
        $value = '';

        $fieldType = strtolower($this->fieldTypeCode);
        // $checkboxOptions = [];
        // foreach ($attr['customField']['custom_field_options'] as $key => $obj) {
        //     $checkboxOptions[$obj->id] = $obj->name;
        // }

        // for edit
        $fieldId = $attr['customField']->id;
        $fieldValues = $attr['customFieldValues'];
        $savedId = null;
        $savedValue = null;
        if (!empty($fieldValues) && array_key_exists($fieldId, $fieldValues)) {
            if (isset($fieldValues[$fieldId]['id'])) {
                $savedId = $fieldValues[$fieldId]['id'];
            }
            if (isset($fieldValues[$fieldId]['text_value'])) {
                $savedValue = $fieldValues[$fieldId]['text_value'];
            }
        }
        // End

        // $checkedValues = [];
        // if (!is_null($savedValue)) {
        //     $checkedValues =  $savedValue;
        // }
        if ($action == 'view') {
            // if (is_array($checkedValues) && !empty($checkedValues)) {
            //     $answers = [];
            //     foreach ($checkedValues as $checkedValue) {
            //         $answers[] = $checkboxOptions[$checkedValue];
            //     }
            //     $value = implode(', ', $answers);
            // }
        } else if ($action == 'edit') {
            $form = $event->subject()->Form;

            $html = '';
            $fieldPrefix = $attr['model'] . '.custom_field_values.' . $attr['attr']['seq'];

            // foreach ($checkboxOptions as $key => $value) {
            //     $html .= '<div class="input">';
            //         $option = ['label' => false, 'class' => 'icheck-input'];
            //         if (!empty($checkedValues)) {
            //             if (in_array($key, $checkedValues)) {
            //                 $option['checked'] = true;
            //             }
            //         }
            //         $html .= $form->checkbox("$fieldPrefix.number_value.$key", $option);
            //         $html .= '<label class="selection-label">'. $value .'</label>';
            //     $html .= '</div>';
            // }
            // $html .= $form->hidden($fieldPrefix.".".$attr['attr']['fieldKey'], ['value' => $fieldId]);

            // $attr['output'] = $html;
            $value = $event->subject()->renderElement('CustomField.Render/'.$fieldType, ['attr' => $attr]);
        }

        $event->stopPropagation();
        return $value;
    }

    public function processCoordinatesValues(Event $event, Entity $entity, ArrayObject $data, ArrayObject $settings) {
        // $settings['valueKey'] = 'number_value';

        // $fieldKey = $settings['fieldKey'];
        // $valueKey = $settings['valueKey'];
        // $customValue = $settings['customValue'];

        // $settings['deleteFieldIds'][] = $customValue[$fieldKey];
        // $checkboxValues = $customValue[$valueKey];
        // foreach ($checkboxValues as $checkboxKey => $checked) {
        //     $customValue[$valueKey] = $checkboxKey;
        //     $settings['customValue'] = $customValue;
        //     if ($checked) {
        //         $this->processValues($entity, $data, $settings);
        //     }
        // }
    }
}
