<?php
namespace CustomField\Model\Behavior;

use ArrayObject;

use Cake\Event\Event;
use Cake\I18n\Time;
use Cake\ORM\Entity;
use Cake\Validation\Validator;
use CustomField\Model\Behavior\SetupBehavior;

class SetupTimeBehavior extends SetupBehavior {
	private $rangeValidationOptions;

	public function initialize(array $config) {
        parent::initialize($config);

        $this->rangeValidationOptions = [
			'no' => __('No Range Validation'),
			'earlier' => __('Should not be earlier than'),
			'later' => __('Should not be later than'),
			'between' => __('In between (inclusive)')
		];

        $this->_table->addBehavior('ControllerAction.TimePicker', ['range_start_time', 'range_end_time']);
    }

    public function buildValidator(Event $event, Validator $validator, $name) {
    	if (!empty($this->_table->request->data)) {
    		if (array_key_exists('field_type', $this->_table->request->data[$this->_table->alias()]) &&
    			$this->_table->request->data[$this->_table->alias()]['field_type'] == 'TIME'
    			) {
    			// only do this if it is TIME
	    		if ($this->_table->request->data[$this->_table->alias()]['time_range'] == 'between') {
	    			$validator->add('range_start_time', 'ruleCompareTime', [
						'rule' => ['compareTime', 'range_end_time', true],
						'provider' => 'table'
					]);
	    		}
	    	}
    	}
    }


    public function onSetTimeElements(Event $event, Entity $entity) {
    	$fieldType = strtolower($this->fieldTypeCode);

		$paramsArray = [];
    	if ($this->_table->action == 'edit') {
    		if (empty($this->_table->request->data)) {
    			$paramsArray = (!empty($entity->params))? json_decode($entity->params, true): [];
    		}
    	}

		if (!empty($this->_table->request->data)) {
			$selectedRangeValidation = (array_key_exists($this->_table->alias(), $this->_table->request->data) && array_key_exists('time_range', $this->_table->request->data[$this->_table->alias()]))? $this->_table->request->data[$this->_table->alias()]['time_range']: null;
		} else {
			if (array_key_exists('range_start_time', $paramsArray) && array_key_exists('range_end_time', $paramsArray)) {
				$selectedRangeValidation = 'between';
			} else if (array_key_exists('range_start_time', $paramsArray)) {
				$selectedRangeValidation = 'earlier';
			} else if (array_key_exists('range_end_time', $paramsArray)) {
				$selectedRangeValidation = 'later';
			} else {
				$selectedRangeValidation = 'no';
			}
		}

		$this->_table->ControllerAction->field('time_range', ['options' => $this->rangeValidationOptions, 'onChangeReload' => true, 'after' => 'is_mandatory', 'default' => $selectedRangeValidation]);

		if (!empty($selectedRangeValidation)) {
			switch ($selectedRangeValidation) {
				case 'earlier':
					$options = ['type' => 'time', 'after' => 'time_range'];
					if (array_key_exists('range_start_time', $paramsArray)) {
						$options['value'] = $paramsArray['range_start_time'];
					}
					$this->_table->ControllerAction->field('range_start_time', $options);
					break;
				case 'later':
					$options = ['type' => 'time', 'after' => 'time_range'];
					if (array_key_exists('range_end_time', $paramsArray)) {
						$options['value'] = $paramsArray['range_end_time'];
					}
        			$this->_table->ControllerAction->field('range_end_time', $options);
					break;
				case 'between':
					$options = ['type' => 'time', 'after' => 'time_range'];
					if (array_key_exists('range_start_time', $paramsArray)) {
						$options['value'] = $paramsArray['range_start_time'];
					}
					$this->_table->ControllerAction->field('range_start_time', $options);
					$options = ['type' => 'time', 'after' => 'range_start_time'];
					if (array_key_exists('range_end_time', $paramsArray)) {
						$options['value'] = $paramsArray['range_end_time'];
					}
        			$this->_table->ControllerAction->field('range_end_time', $options);
					break;
				case 'no': default:
					// no code required
					break;
			}
		}
	}

	public function onGetTimeRange(Event $event, Entity $entity) {
		$paramsArray = (!empty($entity->params))? json_decode($entity->params, true): [];
		if (array_key_exists('range_start_time', $paramsArray) && array_key_exists('range_end_time', $paramsArray)) {
			return $this->rangeValidationOptions['between'].' '.$this->_table->formatTime(new Time($paramsArray['range_start_time'])).' - '.$this->_table->formatTime(new Time($paramsArray['range_end_time']));
		} else if (array_key_exists('range_start_time', $paramsArray)) {
			return $this->rangeValidationOptions['earlier'].' '.$this->_table->formatTime(new Time($paramsArray['range_start_time']));
		} else if (array_key_exists('range_end_time', $paramsArray)) {
			return $this->rangeValidationOptions['later'].' '.$this->_table->formatTime(new Time($paramsArray['range_end_time']));
		} else {
			return $this->rangeValidationOptions['no'];
		}
	}

	public function beforeSave(Event $event, Entity $entity) {
		if ($entity->field_type == 'TIME') {
			$paramsArray = [];
			$range_start_time = ($entity->has('range_start_time'))? $entity->range_start_time: null;
			$range_end_time = ($entity->has('range_end_time'))? $entity->range_end_time: null;

			if (!empty($range_start_time)) {
				$paramsArray['range_start_time'] = $range_start_time;
			}
			if (!empty($range_end_time)) {
				$paramsArray['range_end_time'] = $range_end_time;
			}

			if (!empty($paramsArray)) {
				$entity->params = json_encode($paramsArray);
			} else {
				$entity->params = '';
			}
		}
	}


}
