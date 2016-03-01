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

	public function buildValidator(Event $event, Validator $validator, $name) {
    	$validator->notEmpty('minimum_value');
    	$validator->notEmpty('maximum_value');
    	$validator->notEmpty('lower_limit');
    	$validator->notEmpty('upper_limit');
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

	public function addEditOnChangeRule(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$model = $this->_table;
		$request = $model->request;
		unset($request->query['number_rule']);

		if ($request->is(['post', 'put'])) {
			if (array_key_exists($model->alias(), $request->data)) {
				if (array_key_exists('validation_rule', $request->data[$model->alias()])) {
					$selectedRule = $request->data[$model->alias()]['validation_rule'];
					$request->query['number_rule'] = $selectedRule;
				}
			}
		}
	}

	public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
    	$model = $this->_table;
    	if ($data[$model->alias()]['field_type'] == $this->fieldTypeCode) {
    		if (array_key_exists('validation_rule', $data[$model->alias()]) && !empty($data[$model->alias()]['validation_rule'])) {
    			$params = [];

    			$selectedRule = $data[$model->alias()]['validation_rule'];
    			switch ($selectedRule) {
					case 'min_value':
						$params['min_value'] = $data[$model->alias()]['minimum_value'];
						break;
					case 'max_value':
						$params['max_value'] = $data[$model->alias()]['maximum_value'];
						break;
					case 'range':
						$params['range'] = [
							'lower' => $data[$model->alias()]['lower_limit'],
							'upper' => $data[$model->alias()]['upper_limit']
						];
						break;
					default:
						break;
				}

    			$data[$model->alias()]['params'] = json_encode($params, JSON_UNESCAPED_UNICODE);
	    	} else {
	    		$data[$model->alias()]['params'] = '';
	    	}
		}
	}
}
