<?php
namespace CustomField\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use CustomField\Model\Behavior\RenderBehavior;
use Cake\I18n\Time;

use Cake\View\Helper\IdGeneratorTrait;

class RenderDateBehavior extends RenderBehavior {
	use IdGeneratorTrait;

	public function initialize(array $config) {
        parent::initialize($config);
    }

    public function implementedEvents() {
		$events = parent::implementedEvents();
		$newEvent = [
			'ControllerAction.Model.onUpdateIncludes' => 'onUpdateIncludes'
		];
		$events = array_merge($events, $newEvent);
		return $events;
	}

	public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options) {
		if (!$this->_table->hasBehavior('ControllerAction.DatePicker')) {
			$this->_table->addBehavior('ControllerAction.DatePicker');
		}

		$dataArray = $data->getArrayCopy();
		if (array_key_exists('custom_field_values', $dataArray)) {
			foreach ($dataArray['custom_field_values'] as $key => $value) {
				if (array_key_exists('date_value', $value)) {
					$convertedDate = $this->_table->convertForDatePicker($dataArray['custom_field_values'][$key]['date_value']);
					$data['custom_field_values'][$key]['date_value'] = (!empty($convertedDate))? $convertedDate: $data['custom_field_values'][$key]['date_value'];
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

// pr($this->_table->request->data);
// pr($attr);
        $savedId = null;
        $savedValue = null;
		if (!empty($fieldValues) && array_key_exists($fieldId, $fieldValues)) {
            if (isset($fieldValues[$fieldId]['id'])) {
                $savedId = $fieldValues[$fieldId]['id'];
            }
            if (isset($fieldValues[$fieldId]['date_value'])) {
                $savedValue = $fieldValues[$fieldId]['date_value'];
            }

        }


		// $field = $attr['field'];
		// $table = TableRegistry::get($attr['className']);
		// $schema = $table->schema();
		// $columnAttr = $schema->column($field);
		// $defaultDate = true;
		// if ($columnAttr['null'] == true) {
		// 	$defaultDate = false;
		// }

		// if (!isset($attr['date_options'])) {
		// 	$attr['date_options'] = [];
		// }	
		
		// if (!isset($attr['default_date'])) {
		// 	$attr['default_date'] = $defaultDate;
		// }

		if ($action == 'index' || $action == 'view') {
			return (!empty($savedValue))? $this->_table->formatDate($savedValue): '';
		} else if ($action == 'edit') {
			$_options = [
				'format' => 'dd-mm-yyyy',
				'todayBtn' => 'linked',
				'orientation' => 'auto',
				'autoclose' => true,
			];
			$attr['date_options'] = $_options;
			$fieldPrefix = $attr['model'] . '.custom_field_values.' . $attr['attr']['seq'];
			
			$attr['fieldName'] = $fieldPrefix.".date_value"; 

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
					if ($savedValue instanceof Time) {
						$attr['value'] = $savedValue->format('d-m-Y');
					} else {
						$attr['value'] = date('d-m-Y', strtotime($savedValue));
					}
				} else if ($attr['default_date']) {
					$attr['value'] = date('d-m-Y');
				}
			} else {	
				if ($attr['value'] instanceof Time) {
					$attr['value'] = $attr['value']->format('d-m-Y');
				} else {
					$attr['value'] = date('d-m-Y', strtotime($attr['value']));
				}
			}


			// $attr['value'] = '22-02-2014'; 
			// $attr['default-date'] = '22-02-2014'; 

			$event->subject()->viewSet('datepicker', $attr);
			$value = $event->subject()->renderElement('ControllerAction.bootstrap-datepicker/datepicker_input', ['attr' => $attr]);

			$form = $event->subject()->Form;
			$value .= $form->hidden($fieldPrefix.".".$attr['attr']['fieldKey'], ['value' => $fieldId]);
            if (!is_null($savedId)) {
                $value .= $form->hidden($fieldPrefix.".id", ['value' => $savedId]);
            }
		}


        // $value = '';

        // $dropdownOptions = [];
        // $dropdownDefault = null;
        // foreach ($attr['customField']['custom_field_options'] as $key => $obj) {
        //     $dropdownOptions[$obj->id] = $obj->name;
        //     if ($obj->is_default == 1) {
        //         $dropdownDefault = $obj->id;
        //     }
        // }
        // // default to first key if is not set
        // $dropdownDefault = !is_null($dropdownDefault) ? $dropdownDefault : key($dropdownOptions);

        // // for edit
        // $fieldId = $attr['customField']->id;
        // $fieldValues = $attr['customFieldValues'];
        // $savedId = null;
        // $savedValue = null;
        // if (!empty($fieldValues) && array_key_exists($fieldId, $fieldValues)) {
        //     if (isset($fieldValues[$fieldId]['id'])) {
        //         $savedId = $fieldValues[$fieldId]['id'];
        //     }
        //     if (isset($fieldValues[$fieldId]['number_value'])) {
        //         $savedValue = $fieldValues[$fieldId]['number_value'];
        //     }
        // }
        // // End

        // if ($action == 'view') {
        //     if (!is_null($savedValue)) {
        //         $value = $dropdownOptions[$savedValue];
        //     }
        // } else if ($action == 'edit') {
        //     $form = $event->subject()->Form;
        //     $fieldPrefix = $attr['model'] . '.custom_field_values.' . $attr['attr']['seq'];

        //     $options['type'] = 'select';
        //     $options['options'] = $dropdownOptions;

        //     if ($this->_table->request->is(['get'])) {
        //         $selectedValue = !is_null($savedValue) ? $savedValue : $dropdownDefault;
        //         $options['default'] = $selectedValue;
        //         $options['value'] = $selectedValue;
        //     }
        //     $value .= $form->input($fieldPrefix.".number_value", $options);
        //     $value .= $form->hidden($fieldPrefix.".".$attr['attr']['fieldKey'], ['value' => $fieldId]);
        //     if (!is_null($savedId)) {
        //         $value .= $form->hidden($fieldPrefix.".id", ['value' => $savedId]);
        //     }
        // }

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
