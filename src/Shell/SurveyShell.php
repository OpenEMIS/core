<?php
namespace App\Shell;

use Cake\Console\Shell;

class SurveyShell extends Shell {
	public function initialize() {
		parent::initialize();
		$this->loadModel('Institution.InstitutionSurveys');
	}

 	public function main() {
		$institutionId = $this->args[0];

		$this->InstitutionSurveys->buildSurveyRecords($institutionId);
	}
}
