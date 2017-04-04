<?php
namespace App\Shell;

use Cake\Console\Shell;
use App\Shell\WorkflowRuleShell;

class WorkflowRuleStaffBehavioursShell extends WorkflowRuleShell
{
	public function initialize()
	{
		parent::initialize();
		$this->loadModel('Workflow.WorkflowRules');
		$this->loadModel('Institution.StaffBehaviours');
	}

 	public function main()
 	{
 		$this->out('Initializing Workflow Rule Staff Behaviours Shell ... ');
 		if (!empty($this->args[0])) {
 			$workflowRuleId = $this->args[0];
 			$workflowRuleEntity = $this->WorkflowRules->get($workflowRuleId);
 			$rule = $workflowRuleEntity->rule;

 			$data = $this->StaffBehaviours->getWorkflowRuleData($rule);
 		}
	}
}
