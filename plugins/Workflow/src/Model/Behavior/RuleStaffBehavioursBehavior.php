<?php
namespace Workflow\Model\Behavior;

use Workflow\Model\Behavior\RuleBehavior;

class RuleStaffBehavioursBehavior extends RuleBehavior
{
	protected $_defaultConfig = [
		'feature' => 'StaffBehaviours',
        'threshold' => [
			'behaviour_classification' => [
				'type' => 'select',
				'select' => false,
				'after' => 'workflow_id',
				'lookupModel' => 'Student.BehaviourClassifications'
			]
        ]
	];

	public function initialize(array $config)
	{
		parent::initialize($config);
	}
}
