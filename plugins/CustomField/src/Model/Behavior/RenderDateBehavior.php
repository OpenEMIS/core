<?php
namespace CustomField\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use CustomField\Model\Behavior\RenderBehavior;
use Cake\I18n\Time;
use Cake\I18n\Date;

use Cake\View\Helper\IdGeneratorTrait;
use ControllerAction\Model\Traits\PickerTrait;

class RenderDateBehavior extends RenderBehavior {
	use IdGeneratorTrait;
	use PickerTrait;

	public function initialize(array $config) {
        parent::initialize($config);
    }

	public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options) {
		$dataArray = $data->getArrayCopy();
		if (array_key_exists('custom_field_values', $dataArray)) {
			foreach ($dataArray['custom_field_values'] as $key => $value) {
				if (array_key_exists('date_value', $value)) {
					if (array_key_exists('field_type', $dataArray['custom_field_values'][$key])) {
						if ($dataArray['custom_field_values'][$key]['field_type'] == $this->fieldTypeCode) {
							$convertedDate = $this->convertForDatePicker($dataArray['custom_field_values'][$key]['date_value']);
							$data['custom_field_values'][$key]['date_value'] = (!empty($convertedDate))? $convertedDate: $data['custom_field_values'][$key]['date_value'];
						}
					}
				}
			}
		}
	}

	public function onGetCustomDateElement(Event $event, $action, $entity, $attr, $options=[]) {
		$value = '';
		$_options = [
			'format' => 'dd-mm-yyyy',
			'todayBtn' => 'linked',
			'orientation' => 'auto',
			'autoclose' => true,
		];

		$fieldId = $attr['customField']->id;
        $fieldValues = $attr['customFieldValues'];

        $savedId = null;
        $savedValue = null;
		if (!empty($fieldValues) && array_key_exists($fieldId, $fieldValues)) {
            if (isset($fieldValues[$fieldId]['id'])) {
                $savedId = $fieldValues[$fieldId]['id'];
            }
            if (isset($fieldValues[$fieldId]['date_value'])) {
            	// this will never work. issue with all date fields. request data will never be set back on validation fail
                $savedValue = $fieldValues[$fieldId]['date_value'];
            }
        }

		if ($action == 'index' || $action == 'view') {
			return (!empty($savedValue))? $this->_table->formatDate($savedValue): '';
		} else if ($action == 'edit') {
			$attr['date_options'] = $_options;
			$fieldPrefix = $attr['model'] . '.custom_field_values.' . $attr['attr']['seq'];
			$unlockFields = [];
			$attr['fieldName'] = $fieldPrefix.".date_value";
			$unlockFields[] = $attr['fieldName'];

			$attr['id'] = $attr['model'] . '_' . $attr['field']; 
			if (array_key_exists('fieldName', $attr)) {
				$attr['id'] = $this->_domId($attr['fieldName']);
			} 

			$defaultDate = false;
			if (!isset($attr['default_date'])) {
				$attr['default_date'] = $defaultDate;
			}

			if (!array_key_exists('value', $attr)) {
				if (!is_null($savedValue)) {
					if ($savedValue instanceof Time || $savedValue instanceof Date) {
						$attr['value'] = $savedValue->format('d-m-Y');
					} else {
						$attr['value'] = date('d-m-Y', strtotime($savedValue));
					}
				} else if ($attr['default_date']) {
					$attr['value'] = date('d-m-Y');
				}
			} else {	
				if ($attr['value'] instanceof Time || $savedValue instanceof Date) {
					$attr['value'] = $attr['value']->format('d-m-Y');
				} else {
					$attr['value'] = date('d-m-Y', strtotime($attr['value']));
				}
			}

			$attr['null'] = !$attr['customField']['is_mandatory'];
			$event->subject()->viewSet('datepicker', $attr);
			$value = $event->subject()->renderElement('ControllerAction.bootstrap-datepicker/datepicker_input', ['attr' => $attr]);

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
    	$includes['datepicker']['include'] = true;
    }

    public function processDateValues(Event $event, Entity $entity, ArrayObject $data, ArrayObject $settings) {
        $settings['valueKey'] = 'date_value';
        $this->processValues($entity, $data, $settings);
    }
}
