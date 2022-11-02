<?php
namespace Workflow\Model\Behavior;

use Workflow\Model\Behavior\RuleBehavior;

class RuleStudentBehavioursBehavior extends RuleBehavior
{
	protected $_defaultConfig = [
		'feature' => 'StudentBehaviours',
        'rule' => []
	];

	public function initialize(array $config)
	{
		parent::initialize($config);
	}
}
