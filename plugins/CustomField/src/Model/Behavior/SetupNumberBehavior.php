<?php
namespace CustomField\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Validation\Validator;
use CustomField\Model\Behavior\SetupBehavior;

class SetupNumberBehavior extends SetupBehavior {
	private $validationOptions = [];

	public function initialize(array $config) {
        parent::initialize($config);

        $this->ruleOptions = [
			'min_value' => __('Should not be lesser than'),
			'max_value' => __('Should not be greater than'),
			'range' => __('In between (inclusive)')
		];
    }

	public function addBeforeAction(Event $event) {
    	$model = $this->_table;
    	$fieldTypes = $model->getFieldTypes();
    	$selectedFieldType = !empty($model->request->data($model->aliasField('field_type'))) ? $model->request->data($model->aliasField('field_type')) : key($fieldTypes);

    	if ($selectedFieldType == $this->fieldTypeCode) {
    		$this->buildNumberValidator();
    	}
    }

    public function editAfterQuery(Event $event, Entity $entity) {
    	if ($entity->field_type == $this->fieldTypeCode) {
    		$this->buildNumberValidator();
    	}
    }

	private function buildNumberValidator() {
		$validator = $this->_table->validator();
		$validator
			->notEmpty('minimum_value')
    		->notEmpty('maximum_value')
    		->notEmpty('lower_limit')
    		->notEmpty('upper_limit');
	}

    public function onSetNumberElements(Event $event, Entity $entity) {
		$model = $this->_table;

		if ($model->request->is(['get'])) {
			if (isset($entity->id)) {
				// view / edit
				if ($entity->has('params') && !empty($entity->params)) {
					$params = json_decode($entity->params, true);
					if (array_key_exists('min_value', $params)) {
						$model->request->query['number_rule'] = 'min_value';
						$entity->minimum_value = $params['min_value'];
					} else if (array_key_exists('max_value', $params)) {
						$model->request->query['number_rule'] = 'max_value';
						$entity->maximum_value = $params['max_value'];
					} else if (array_key_exists('range', $params)) {
						$model->request->query['number_rule'] = 'range';
						$entity->lower_limit = $params['range']['lower'];
						$entity->upper_limit = $params['range']['upper'];
					}
				}
			} else {
				// add
				unset($model->request->query['number_rule']);
			}

			if ($model->action == 'view') {
				$selectedRule = $model->request->query('number_rule');
				$entity->validation_rule = !is_null($selectedRule) ? $this->ruleOptions[$selectedRule] : __('No Validation');
			}
		}

		$ruleOptions = ['' => __('No Validation')] + $this->ruleOptions;
		$selectedRule = $model->queryString('number_rule', $ruleOptions);

		$model->ControllerAction->field('validation_rule', [
			'type' => 'select',
			'options' => $ruleOptions,
			'default' => $selectedRule,
			'value' => $selectedRule,
			'onChangeReload' => 'changeRule',
			'after' => 'is_unique'
		]);

		switch ($selectedRule) {
			case 'min_value':
		        $model->ControllerAction->field('minimum_value', [
		        	'type' => 'integer',
		        	'after' => 'validation_rule'
		        ]);
				break;
			case 'max_value':
		        $model->ControllerAction->field('maximum_value', [
		        	'type' => 'integer',
		        	'after' => 'validation_rule'
		        ]);
				break;
			case 'range':
		        $model->ControllerAction->field('lower_limit', [
		        	'type' => 'integer',
		        	'after' => 'validation_rule'
		        ]);
		        $model->ControllerAction->field('upper_limit', [
		        	'type' => 'integer',
		        	'after' => 'lower_limit'
		        ]);
				break;
			default:
				break;
		}
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options) {
		if (array_key_exists('validation_rule', $data)) {
			if ($data['field_type'] == $this->fieldTypeCode) {
				$model = $this->_table;
				$request = $model->request;
				unset($request->query['number_rule']);

				if (!empty($data['validation_rule'])) {
					$selectedRule = $data['validation_rule'];
					$request->query['number_rule'] = $selectedRule;
					$params = [];

					switch ($selectedRule) {
						case 'min_value':
							if (array_key_exists('minimum_value', $data) && !empty($data['minimum_value'])) {
								$params['min_value'] = $data['minimum_value'];
							}
							break;
						case 'max_value':
							if (array_key_exists('maximum_value', $data) && !empty($data['maximum_value'])) {
								$params['max_value'] = $data['maximum_value'];
							}
							break;
						case 'range':
							$lowerLimit = array_key_exists('lower_limit', $data) ? $data['lower_limit']: null;
							$upperLimit = array_key_exists('upper_limit', $data) ? $data['upper_limit']: null;

							if (!empty($lowerLimit) && !empty($upperLimit)) {
	    						$params['range'] = [
									'lower' => $data['lower_limit'],
									'upper' => $data['upper_limit']
								];
	    					}
							break;
						default:
							break;
					}

					$data['params'] = json_encode($params, JSON_UNESCAPED_UNICODE);
				} else {
					$data['params'] = '';
				}
			}
		}
	}
}
