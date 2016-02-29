<?php
namespace CustomField\Model\Behavior;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\Validation\Validator;
use CustomField\Model\Behavior\SetupBehavior;

class SetupDateBehavior extends SetupBehavior {
	private $rangeValidationOptions;

	public function initialize(array $config) {
        parent::initialize($config);

        $this->rangeValidationOptions = [
			'no' => __('No Range Validation'),
			'earlier' => __('Should not be earlier than'),
			'later' => __('Should not be later than'),
			'between' => __('In between (inclusive)')
		];

        $this->_table->addBehavior('ControllerAction.DatePicker', ['range_start_date', 'range_end_date']);
    }

    public function buildValidator(Event $event, Validator $validator, $name) {
    	$validator->notEmpty('date_range');
		$validator->notEmpty('range_start_date');
		$validator->notEmpty('range_end_date');

		$validator->add('range_start_date', 'ruleCompareDate', [
			'rule' => ['compareDate', 'range_end_date', true],
			'provider' => 'table',
			'on' => function ($context) {
				return $context['data']['field_type'] == $this->fieldTypeCode && $context['data']['date_range'] == 'between';
			}
		]);
    }

    public function onSetDateElements(Event $event, Entity $entity) {
    	$fieldType = strtolower($this->fieldTypeCode);

		$paramsArray = [];
    	if ($this->_table->action == 'edit') {
    		if (empty($this->_table->request->data)) {
    			$paramsArray = (!empty($entity->params))? json_decode($entity->params, true): [];
    		}
    	}

		if (!empty($this->_table->request->data)) {
			$selectedRangeValidation = (array_key_exists($this->_table->alias(), $this->_table->request->data) && array_key_exists('date_range', $this->_table->request->data[$this->_table->alias()]))? $this->_table->request->data[$this->_table->alias()]['date_range']: null;
		} else {
			if (array_key_exists('range_start_date', $paramsArray) && array_key_exists('range_end_date', $paramsArray)) {
				$selectedRangeValidation = 'between';
			} else if (array_key_exists('range_start_date', $paramsArray)) {
				$selectedRangeValidation = 'earlier';
			} else if (array_key_exists('range_end_date', $paramsArray)) {
				$selectedRangeValidation = 'later';
			} else {
				$selectedRangeValidation = 'no';
			}
		}

		$this->_table->ControllerAction->field('date_range', ['options' => $this->rangeValidationOptions, 'onChangeReload' => true, 'after' => 'is_mandatory', 'default' => $selectedRangeValidation]);

		if (!empty($selectedRangeValidation)) {
			switch ($selectedRangeValidation) {
				case 'earlier':
					$options = ['type' => 'date', 'after' => 'date_range', 'null' => false];
					if (array_key_exists('range_start_date', $paramsArray)) {
						$options['value'] = $paramsArray['range_start_date'];
					}
					$this->_table->ControllerAction->field('range_start_date', $options);
					break;
				case 'later':
					$options = ['type' => 'date', 'after' => 'date_range', 'null' => false];
					if (array_key_exists('range_end_date', $paramsArray)) {
						$options['value'] = $paramsArray['range_end_date'];
					}
        			$this->_table->ControllerAction->field('range_end_date', $options);
					break;
				case 'between':
					$options = ['type' => 'date', 'after' => 'date_range', 'null' => false];
					if (array_key_exists('range_start_date', $paramsArray)) {
						$options['value'] = $paramsArray['range_start_date'];
					}
					$this->_table->ControllerAction->field('range_start_date', $options);
					$options = ['type' => 'date', 'after' => 'range_start_date', 'null' => false];
					if (array_key_exists('range_end_date', $paramsArray)) {
						$options['value'] = $paramsArray['range_end_date'];
					}
        			$this->_table->ControllerAction->field('range_end_date', $options);
					break;
				case 'no': default:
					// no code required
					break;
			}
		}
	}

	public function onGetDateRange(Event $event, Entity $entity) {
		$paramsArray = (!empty($entity->params))? json_decode($entity->params, true): [];
		if (array_key_exists('range_start_date', $paramsArray) && array_key_exists('range_end_date', $paramsArray)) {
			return $this->rangeValidationOptions['between'].' '.$paramsArray['range_start_date'].' - '.$paramsArray['range_end_date'];
		} else if (array_key_exists('range_start_date', $paramsArray)) {
			return $this->rangeValidationOptions['earlier'].' '.$paramsArray['range_start_date'];
		} else if (array_key_exists('range_end_date', $paramsArray)) {
			return $this->rangeValidationOptions['later'].' '.$paramsArray['range_end_date'];
		} else {
			return $this->rangeValidationOptions['no'];
		}
	}

	public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$model = $this->_table;
		if ($data[$model->alias()]['field_type'] == $this->fieldTypeCode) {
			$paramsArray = [];
			$range_start_date = (array_key_exists('range_start_date', $data[$model->alias()]))? $data[$model->alias()]['range_start_date']: null;
			$range_end_date = (array_key_exists('range_end_date', $data[$model->alias()]))? $data[$model->alias()]['range_end_date']: null;

			if (!empty($range_start_date)) {
				$paramsArray['range_start_date'] = $range_start_date;
			}
			if (!empty($range_end_date)) {
				$paramsArray['range_end_date'] = $range_end_date;
			}

			if (!empty($paramsArray)) {
				$data[$model->alias()]['params'] = json_encode($paramsArray);
			} else {
				$data[$model->alias()]['params'] = '';
			}
		}
	}

}
