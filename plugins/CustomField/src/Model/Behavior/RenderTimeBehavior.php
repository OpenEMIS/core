<?php
namespace CustomField\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use CustomField\Model\Behavior\RenderBehavior;
use Cake\I18n\Time;

use Cake\View\Helper\IdGeneratorTrait;
use ControllerAction\Model\Traits\PickerTrait;

class RenderTimeBehavior extends RenderBehavior {
	use IdGeneratorTrait;
	use PickerTrait;

	public function initialize(array $config) {
        parent::initialize($config);
    }

	public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options) {
		$dataArray = $data->getArrayCopy();
		if (array_key_exists('custom_field_values', $dataArray)) {
			foreach ($dataArray['custom_field_values'] as $key => $value) {
				if (array_key_exists('time_value', $value)) {
					if (array_key_exists('field_type', $dataArray['custom_field_values'][$key])) {
						if ($dataArray['custom_field_values'][$key]['field_type'] == $this->fieldTypeCode) {
							$convertedTime = $this->convertForTimePicker($dataArray['custom_field_values'][$key]['time_value']);
							$data['custom_field_values'][$key]['time_value'] = (!empty($convertedTime))? $convertedTime: $data['custom_field_values'][$key]['time_value'];
						}
					}
				}
			}
		}
	}

	public function onGetCustomTimeElement(Event $event, $action, $entity, $attr, $options=[]) {
		$value = '';
		$_options = [
			'defaultTime' => false
		];

		$fieldId = $attr['customField']->id;
        $fieldValues = $attr['customFieldValues'];

        $savedId = null;
        $savedValue = null;
		if (!empty($fieldValues) && array_key_exists($fieldId, $fieldValues)) {
            if (isset($fieldValues[$fieldId]['id'])) {
                $savedId = $fieldValues[$fieldId]['id'];
            }
            if (isset($fieldValues[$fieldId]['time_value'])) {
                $savedValue = $fieldValues[$fieldId]['time_value'];
            }
        }

		if ($action == 'index' || $action == 'view') {
			return (!empty($savedValue))? $this->_table->formatTime(new Time($savedValue)): '';
		} else if ($action == 'edit') {
			$unlockFields = [];
			$fieldPrefix = $attr['model'] . '.custom_field_values.' . $attr['attr']['seq'];
			$attr['fieldName'] = $fieldPrefix.".time_value"; 
			$unlockFields[] = $attr['fieldName'];

			if (!isset($attr['time_options'])) {
				$attr['time_options'] = [];
			}
			if (!isset($attr['default_time'])) {
				$attr['default_time'] = true;
			}

			$attr['id'] = $attr['model'] . '_' . $attr['field']; 
			$attr['time_options'] = array_merge($_options, $attr['time_options']);

		// 	$defaultDate = false;
		// 	if (!isset($attr['default_date'])) {
		// 		$attr['default_date'] = $defaultDate;
		// 	}

			if (!array_key_exists('value', $attr)) {
				if (!is_null($savedValue)) {
					$attr['value'] = date('h:i A', strtotime($savedValue));
					$attr['time_options']['defaultTime'] = $attr['value'];
				} else if ($attr['default_time']) {
					$attr['value'] = date('h:i A');
					$attr['time_options']['defaultTime'] = $attr['value'];
				}
			} else {
				if ($attr['value'] instanceof Time) {
					$attr['value'] = $attr['value']->format('h:i A');
					$attr['time_options']['defaultTime'] = $attr['value'];
				} else {
					$attr['value'] = date('h:i A', strtotime($attr['value']));
					$attr['time_options']['defaultTime'] = $attr['value'];
				}
			}

			$attr['null'] = !$attr['customField']['is_mandatory'];
			$event->subject()->viewSet('timepicker', $attr);
			$value = $event->subject()->renderElement('ControllerAction.bootstrap-timepicker/timepicker_input', ['attr' => $attr]);

			$form = $event->subject()->Form;
			
			$value .= $form->hidden($fieldPrefix.".".$attr['attr']['fieldKey'], ['value' => $fieldId]);
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

    public function onUpdateIncludes(Event $event, ArrayObject $includes, $action) {
    	$includes['timepicker']['include'] = true;
    }

    public function processTimeValues(Event $event, Entity $entity, ArrayObject $data, ArrayObject $settings) {
        $settings['valueKey'] = 'time_value';
        $this->processValues($entity, $data, $settings);
    }
}
