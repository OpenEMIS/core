<?php
namespace App\Shell;

use Cake\Console\Shell;
use Cake\ORM\TableRegistry;

class SurveyShell extends Shell {
	public function initialize() {
		parent::initialize();
	}

 	public function main() {
		$institutionIds = $this->args[0];
		$InstitutionSurveys = TableRegistry::get('Institution.InstitutionSurveys');
		$InstitutionSurveys->addBehavior('Workflow.Workflow', ['model' => $InstitutionSurveys->registryAlias()]);

		foreach ($institutionIds as $institutionId) {
			$InstitutionSurveys->buildSurveyRecords($institutionId);
		}
	}
}
