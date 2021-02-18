<?php
namespace App\Shell;

use Cake\Console\Shell;
use Cake\ORM\TableRegistry;
use Cake\Datasource\ConnectionManager;

class AutomatedStudentWithdrawalShell extends Shell {
	public function initialize() {
		parent::initialize();
		$this->loadModel('Configurations.ConfigItems');
		$this->loadModel('Institution.InstitutionStudentAbsenceDays');
	}

 	public function main() {
 		
		$ConfigItems = TableRegistry::get('Configuration.ConfigItems');
		$daysAbsent= $ConfigItems->value("automated_student_days_absent");
		$AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');

		$currentYearId = $AcademicPeriod->getCurrent();

		$InstitutionStudentAbsenceDays = TableRegistry::get('Institution.InstitutionStudentAbsenceDays');
		$data = $InstitutionStudentAbsenceDays
				->find('all')
				->where([
                        $this->InstitutionStudentAbsenceDays->aliasField('absent_days') => $daysAbsent
                    ])->toArray();

		print_r($currentYearId);
 		die();




	}
}
