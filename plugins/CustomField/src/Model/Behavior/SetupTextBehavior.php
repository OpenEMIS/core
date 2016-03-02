<?php
namespace CustomField\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Validation\Validator;
use CustomField\Model\Behavior\SetupBehavior;

class SetupTextBehavior extends SetupBehavior {
	private $validationOptions = [];

	public function initialize(array $config) {
        parent::initialize($config);

        $this->ruleOptions = [
        	'length' => __('Length Validation'),
        	'input_mask' => __('Custom Validation')
		];
    }

    public function addBeforeAction(Event $event) {
    	$model = $this->_table;
    	$fieldTypes = $model->getFieldTypes();
    	$selectedFieldType = !empty($model->request->data($model->aliasField('field_type'))) ? $model->request->data($model->aliasField('field_type')) : key($fieldTypes);

    	if ($selectedFieldType == $this->fieldTypeCode) {
    		$this->buildTextValidator();
    	}
    }

    public function editAfterQuery(Event $event, Entity $entity) {
    	if ($entity->field_type == $this->fieldTypeCode) {
    		$this->buildTextValidator();
    	}
    }

    private function buildTextValidator() {
		$validator = $this->_table->validator();
		$validator
	    	->allowEmpty('minimum_length', function ($context) {
				if (array_key_exists('maximum_length', $context['data'])) {
					return strlen($context['data']['maximum_length']);
				}

				return false;
			})
			->allowEmpty('maximum_length', function ($context) {
				if (array_key_exists('minimum_length', $context['data'])) {
					return strlen($context['data']['minimum_length']);
				}

				return false;
			})
	    	->notEmpty('validation_format')
	    	;
    }

	public function onSetTextElements(Event $event, Entity $entity) {
		$model = $this->_table;

		if ($model->request->is(['get'])) {
			if (isset($entity->id)) {
				// view / edit
				if ($entity->has('params') && !empty($entity->params)) {
					$params = json_decode($entity->params, true);
					if (array_key_exists('min_length', $params)) {
						$model->request->query['text_rule'] = 'length';
						$entity->minimum_length = $params['min_length'];
					} else if (array_key_exists('max_length', $params)) {
						$model->request->query['text_rule'] = 'length';
						$entity->maximum_length = $params['max_length'];
					} else if (array_key_exists('range', $params)) {
						$model->request->query['text_rule'] = 'length';
						$entity->minimum_length = $params['range']['lower'];
						$entity->maximum_length = $params['range']['upper'];
					} else if (array_key_exists('input_mask', $params)) {
						$model->request->query['text_rule'] = 'input_mask';
						$entity->validation_format = $params['input_mask'];
					}
				}
			} else {
				// add
				unset($model->request->query['text_rule']);
			}

			if ($model->action == 'view') {
				$selectedRule = $model->request->query('text_rule');
				$entity->validation_rule = !is_null($selectedRule) ? $this->ruleOptions[$selectedRule] : __('No Validation');
			}
		}

		$ruleOptions = ['' => __('No Validation')] + $this->ruleOptions;
		$selectedRule = $model->queryString('text_rule', $ruleOptions);

		$model->ControllerAction->field('validation_rule', [
			'type' => 'select',
			'options' => $ruleOptions,
			'default' => $selectedRule,
			'value' => $selectedRule,
			'onChangeReload' => true,
			'after' => 'is_unique'
		]);

		switch ($selectedRule) {
			case 'length':
				$model->ControllerAction->field('minimum_length', [
		        	'type' => 'integer',
		        	'after' => 'validation_rule'
		        ]);
		        $model->ControllerAction->field('maximum_length', [
		        	'type' => 'integer',
		        	'after' => 'minimum_length'
		        ]);
				break;
			case 'input_mask':
				$fieldType = strtolower($this->fieldTypeCode);
				$model->ControllerAction->addField('validation_reference', [
		            'type' => 'element',
		            'element' => 'CustomField.Setup/' . $fieldType,
		            'valueClass' => 'table-full-width',
		            'after' => 'validation_rule'
		        ]);
		        $model->ControllerAction->field('validation_format', [
		        	'type' => 'string',
		        	'after' => 'validation_reference',
		        	'attr' => ['onkeypress' => 'return Config.inputMaskCheck(event);']
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
				unset($request->query['text_rule']);

				if (!empty($data['validation_rule'])) {
					$selectedRule = $data['validation_rule'];
					$request->query['text_rule'] = $selectedRule;
					$params = [];

					switch ($selectedRule) {
	    				case 'length':
	    					$minLength = array_key_exists('minimum_length', $data) ? $data['minimum_length']: null;
							$maxLength = array_key_exists('maximum_length', $data) ? $data['maximum_length']: null;

	    					if (!empty($minLength) && !empty($maxLength)) {
	    						$params['range'] = [
	    							'lower' => $minLength,
	    							'upper' => $maxLength
	    						];
	    					} else if (!empty($minLength) && empty($maxLength)) {
								$params['min_length'] = $minLength;
	    					} else if (empty($minLength) && !empty($maxLength)) {
								$params['max_length'] = $maxLength;
	    					}
	    					break;
						case 'input_mask':
							if (array_key_exists('validation_format', $data) && !empty($data['validation_format'])) {
								$params['input_mask'] = $data['validation_format'];
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
