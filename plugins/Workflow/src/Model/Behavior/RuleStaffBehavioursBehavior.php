<?php
namespace Workflow\Model\Behavior;

use Workflow\Model\Behavior\RuleBehavior;

class RuleStaffBehavioursBehavior extends RuleBehavior
{
	protected $_defaultConfig = [
		'feature' => 'StaffBehaviours',
        'threshold' => []
	];

	public function initialize(array $config)
	{
		parent::initialize($config);
	}
}
