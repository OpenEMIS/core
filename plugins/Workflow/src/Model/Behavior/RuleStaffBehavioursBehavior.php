<?php
namespace Workflow\Model\Behavior;

use ArrayObject;
use Workflow\Model\Behavior\RuleBehavior;
use Cake\Event\Event;

class RuleStaffBehavioursBehavior extends RuleBehavior
{
	protected $_defaultConfig = [
		'feature' => 'StaffBehaviours',
        'threshold' => [
			'behaviour_classification' => [
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
                $validator->add('behaviour_classification', 'notBlank', ['rule' => 'notBlank']);
				$validator->requirePresence('behaviour_classification');
            }
        }
    }
}
