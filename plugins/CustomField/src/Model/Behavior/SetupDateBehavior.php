<?php
namespace CustomField\Model\Behavior;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Entity;
use CustomField\Model\Behavior\SetupBehavior;

class SetupDateBehavior extends SetupBehavior {
	public function initialize(array $config) {
        parent::initialize($config);

        $this->_table->addBehavior('ControllerAction.DatePicker', ['range_start', 'range_end']);
    }
    public function onSetDateElements(Event $event, Entity $entity) {
    	// pr($this->_table->action);
    	// pr($this->_table->request->data);

    	$fieldType = strtolower($this->fieldTypeCode);
		$rangeValidationOptions = [
			'no' => __('No Range Validation'),
			'earlier' => __('Should not be earlier than'),
			'later' => __('Should not be later than'),
			'between' => __('In between (inclusive)')
		];

		$paramsArray = [];
    	if ($this->_table->action == 'edit') {
    		if (empty($this->_table->request->data)) {
    			$paramsArray = (!empty($entity->params))? json_decode($entity->params, true): [];
    		}
    	}

		if (!empty($this->_table->request->data)) {
			$selectedRangeValidation = (array_key_exists($this->_table->alias(), $this->_table->request->data) && array_key_exists('date_range', $this->_table->request->data[$this->_table->alias()]))? $this->_table->request->data[$this->_table->alias()]['date_range']: null;
		} else {
			if (array_key_exists('range_start', $paramsArray) && array_key_exists('range_end', $paramsArray)) {
				$selectedRangeValidation = 'between';
			} else if (array_key_exists('range_start', $paramsArray)) {
				$selectedRangeValidation = 'earlier';
			} else if (array_key_exists('range_end', $paramsArray)) {
				$selectedRangeValidation = 'later';
			} else {
				$selectedRangeValidation = 'no';
			}
		}

		$this->_table->ControllerAction->field('date_range', ['options' => $rangeValidationOptions, 'onChangeReload' => true, 'after' => 'is_mandatory', 'default' => $selectedRangeValidation]);

		if (!empty($selectedRangeValidation)) {
			switch ($selectedRangeValidation) {
				case 'earlier':
					$options = ['type' => 'date', 'after' => 'date_range'];
					if (array_key_exists('range_start', $paramsArray)) {
						$options['value'] = $paramsArray['range_start'];
					}
					$this->_table->ControllerAction->field('range_start', $options);
					break;
				case 'later':
					$options = ['type' => 'date', 'after' => 'date_range'];
					if (array_key_exists('range_end', $paramsArray)) {
						$options['value'] = $paramsArray['range_end'];
					}
        			$this->_table->ControllerAction->field('range_end', $options);
					break;
				case 'between':
					$options = ['type' => 'date', 'after' => 'date_range'];
					if (array_key_exists('range_start', $paramsArray)) {
						$options['value'] = $paramsArray['range_start'];
					}
					$this->_table->ControllerAction->field('range_start', $options);
					$options = ['type' => 'date', 'after' => 'range_start'];
					if (array_key_exists('range_end', $paramsArray)) {
						$options['value'] = $paramsArray['range_end'];
					}
        			$this->_table->ControllerAction->field('range_end', $options);
					break;
				case 'no': default:
					// no code required
					break;
			}
		}
	}

	// public function 

	public function beforeSave(Event $event, Entity $entity) {
		// preparing params variable
		$paramsArray = [];
		$range_start = ($entity->has('range_start'))? $entity->range_start: null;
		$range_end = ($entity->has('range_end'))? $entity->range_end: null;

		if (!empty($range_start)) {
			$paramsArray['range_start'] = $range_start;
		}
		if (!empty($range_end)) {
			$paramsArray['range_end'] = $range_end;
		}

		if (!empty($paramsArray)) {
			$entity->params = json_encode($paramsArray);
		}
	}


}
