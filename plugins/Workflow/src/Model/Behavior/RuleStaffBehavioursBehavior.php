<?php
namespace Workflow\Model\Behavior;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Utility\Inflector;
use Cake\Log\Log;
use Workflow\Model\Behavior\RuleBehavior;

class RuleStaffBehavioursBehavior extends RuleBehavior
{
	protected $_defaultConfig = [
		'feature' => 'StaffBehaviours',
        'rule' => [
			'behaviour_classification_id' => [
				'type' => 'select',
				'after' => 'workflow_id',
				'lookupModel' => 'Student.BehaviourClassifications',
				'attr' => [
                    'required' => true
                ]
			]
        ]
	];

	public function initialize(array $config)
	{
		parent::initialize($config);
	}

	public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        $model = $this->_table;
        if (isset($data['feature']) && !empty($data['feature']) && $data['feature'] == $this->rule) {
            if (isset($data['submit']) && $data['submit'] == 'save') {
                $validator = $model->validator();
                $validator->add('behaviour_classification_id', 'notBlank', ['rule' => 'notBlank']);
				$validator->requirePresence('behaviour_classification_id');
            }
        }
    }

    public function onGetStaffBehavioursRule(Event $event, Entity $entity)
    {
    	$model = $this->_table;
    	if ($model->action == 'index' && $entity->has('rule')) {
    		$ruleArray = json_decode($entity->rule, true);

    		if (array_key_exists('where', $ruleArray)) {
    			$where = $ruleArray['where'];
    			$field = 'behaviour_classification_id';
    			if (array_key_exists($field, $where)) {
    				$fieldId = $where[$field];
	    			$lookupModel = $this->config('rule.'.$field.'.lookupModel');
	    			$modelTable = TableRegistry::get($lookupModel);

	    			$label = Inflector::humanize($field);
					if ($model->endsWith($field, '_id') && $model->endsWith($label, ' Id')) {
						$label = str_replace(' Id', '', $label);
					}
					$label = __($label);

	    			try {
	    				$fieldRecord = $modelTable->get($fieldId);
		    			$value = $label . ': ' . $fieldRecord->name;
		    			return $value;
					} catch (\Exception $e) {
			            Log::write('debug', $e->getMessage());
			        }
				}
    		}
    	}
    }
}
