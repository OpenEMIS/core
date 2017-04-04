<?php
namespace App\Shell;

use Cake\Console\Shell;

class WorkflowRuleShell extends Shell
{
	public function initialize()
	{
		parent::initialize();
	}

 	public function main()
 	{
 		$this->out('Initializing Workflow Rule Shell ... ');
	}
}
