<?php
namespace CustomField\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\Event;
use CustomField\Model\Behavior\SetupBehavior;

class SetupTextBehavior extends SetupBehavior {
	private $validationOptions = [];

	public function initialize(array $config) {
        parent::initialize($config);

        $this->ruleOptions = [
			'' => __('No Validation'),
			'input_mask' => __('Custom Validation')
		];
    }

	public function onSetTextElements(Event $event, Entity $entity) {
		$model = $this->_table;

		if ($model->request->is(['get'])) {
			if ($model->action == 'add') {
				unset($model->request->query['text_rule']);
			} else {
				if ($entity->has('params') && !empty($entity->params)) {
					$params = json_decode($entity->params, true);
					if (array_key_exists('input_mask', $params)) {
						$model->request->query['text_rule'] = 'input_mask';
						if ($model->action == 'view') {
							$entity->rule = $this->ruleOptions['input_mask'];
						}
						$entity->validation_format = $params['input_mask'];
					}
				}
			}
		}

		$ruleOptions = $this->ruleOptions;
		$selectedRule = $model->queryString('text_rule', $ruleOptions);

		$model->ControllerAction->field('rule', [
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
		            'after' => 'rule'
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
				if (array_key_exists('rule', $request->data[$model->alias()])) {
					$selectedRule = $request->data[$model->alias()]['rule'];
					$request->query['text_rule'] = $selectedRule;
				}
			}
		}
	}

	public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
    	$model = $this->_table;
    	if ($data[$model->alias()]['field_type'] == $this->fieldTypeCode) {
    		if (array_key_exists('rule', $data[$model->alias()]) && !empty($data[$model->alias()]['rule'])) {
	    		if (array_key_exists('validation_format', $data[$model->alias()])) {
	    			$params = [];
	    			$validationValue = $data[$model->alias()]['validation_format'];
	    			if (!empty($validationValue)) {
	    				$params['input_mask'] = $validationValue;
	    			}
	    			$data[$model->alias()]['params'] = json_encode($params, JSON_UNESCAPED_UNICODE);
	    		}
	    	} else {
	    		$data[$model->alias()]['params'] = '';
	    	}
		}
	}
}
