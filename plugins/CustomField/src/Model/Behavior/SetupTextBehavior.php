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
			'input_mask' => __('Custom Validation')
		];
    }

    public function buildValidator(Event $event, Validator $validator, $name) {
    	$validator->notEmpty('validation_format');
    }

	public function onSetTextElements(Event $event, Entity $entity) {
		$model = $this->_table;

		if ($model->request->is(['get'])) {
			if (isset($entity->id)) {
				// view / edit
				if ($entity->has('params') && !empty($entity->params)) {
					$params = json_decode($entity->params, true);
					if (array_key_exists('input_mask', $params)) {
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

		$ruleOptions = ['' => __('-- Select Rule --')] + $this->ruleOptions;
		$selectedRule = $model->queryString('text_rule', $ruleOptions);

		$model->ControllerAction->field('validation_rule', [
			'type' => 'select',
			'options' => $ruleOptions,
			'default' => $selectedRule,
			'value' => $selectedRule,
			'onChangeReload' => 'changeRule',
			'after' => 'is_unique'
		]);

		switch ($selectedRule) {
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

	public function addEditOnChangeRule(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$model = $this->_table;
		$request = $model->request;
		unset($request->query['text_rule']);

		if ($request->is(['post', 'put'])) {
			if (array_key_exists($model->alias(), $request->data)) {
				if (array_key_exists('validation_rule', $request->data[$model->alias()])) {
					$selectedRule = $request->data[$model->alias()]['validation_rule'];
					$request->query['text_rule'] = $selectedRule;
				}
			}
		}
	}

	public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
    	$model = $this->_table;
    	if ($data[$model->alias()]['field_type'] == $this->fieldTypeCode) {
    		if (array_key_exists('validation_rule', $data[$model->alias()]) && !empty($data[$model->alias()]['validation_rule'])) {
	    		if (array_key_exists('validation_format', $data[$model->alias()])) {
	    			$params = [];
	    			$validationFormat = $data[$model->alias()]['validation_format'];
	    			if (!empty($validationFormat)) {
	    				$params['input_mask'] = $validationFormat;
	    			}
	    			$data[$model->alias()]['params'] = json_encode($params, JSON_UNESCAPED_UNICODE);
	    		}
	    	} else {
	    		$data[$model->alias()]['params'] = '';
	    	}
		}
	}
}
