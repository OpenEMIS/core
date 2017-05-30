<?php
namespace CustomField\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use CustomField\Model\Behavior\RenderBehavior;

class RenderCoordinatesBehavior extends RenderBehavior {

    // assign values for validation
    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options) {
        $dataArray = $data->getArrayCopy();
        if (array_key_exists('custom_field_values', $dataArray)) {
            foreach ($dataArray['custom_field_values'] as $key => $value) {
                if (array_key_exists('field_type', $dataArray['custom_field_values'][$key])) {
                    if ($dataArray['custom_field_values'][$key]['field_type'] == 'COORDINATES') {
                        $data['custom_field_values'][$key]['coordinates_value'] = [
                            'latitude' => $dataArray['custom_field_values'][$key]['latitude'],
                            'longitude' => $dataArray['custom_field_values'][$key]['longitude']
                        ];
                    }
                }
            }
        }
    }

	public function onGetCustomCoordinatesElement(Event $event, $action, $entity, $attr, $options=[]) {
        $value = '';

        $fieldType = strtolower($this->fieldTypeCode);

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

        $errors = [];
        if ($action == 'view') {
            if (!is_null($savedValue)) {
                $values = json_decode($savedValue);
            } else {
                $values = null;
            }
        } else if ($action == 'edit') {
            $form = $event->subject()->Form;
            $html = '';
            $fieldPrefix = $attr['model'] . '.custom_field_values.' . $attr['attr']['seq'];
            $attr['fieldPrefix'] = $fieldPrefix;
            $attr['form'] = $form;
            $unlockFields = [];

            $unlockFields[] = $attr['fieldPrefix'].".latitude";
            $unlockFields[] = $attr['fieldPrefix'].".longitude";
            $unlockFields[] = $attr['fieldPrefix'].".".$attr['attr']['fieldKey'];
            $unlockFields[] = $attr['fieldPrefix'].".id";

            // $postData = $entity->custom_field_values[$attr['attr']['seq']];
            $postData = null;
            if ($entity->has('custom_field_values')) {
                foreach ($entity->custom_field_values as $key => $obj) {
                    if ($obj->field_type == 'COORDINATES' && $obj->{$attr['attr']['fieldKey']} == $fieldId) {
                        $postData = $obj;
                    }
                }
            }
            if ($postData instanceof Entity && !empty($postData->dirty())) {
                $values = ($postData->invalid('coordinates_value')) ? json_decode(json_encode($postData->invalid('coordinates_value'))) : json_decode(json_encode($postData->coordinates_value));
            } elseif (!is_null($savedValue)) {
                $values = json_decode($savedValue);
            } else {
                $values = null;
            }
            if ($postData instanceof Entity && !empty($postData)) {
                $errors = $postData->errors('coordinates_value');
            }
        }

        $value = $event->subject()->renderElement('CustomField.Render/'.$fieldType, ['action' => $action, 'values' => $values, 'errors' => $errors, 'id' => $savedId, 'attr' => $attr]);
        if ($action == 'edit') {
            $value = $this->processRelevancyDisabled($entity, $value, $fieldId, $form, $unlockFields);
        }
        $event->stopPropagation();
        return $value;
    }

    public function processCoordinatesValues(Event $event, Entity $entity, ArrayObject $data, ArrayObject $settings) {
        $settings['customValue']['text_value'] = json_encode([
            'latitude' => $settings['customValue']['latitude'],
            'longitude' => $settings['customValue']['longitude']
        ]);
        $settings['valueKey'] = 'text_value';
        $this->processValues($entity, $data, $settings);
    }
}
