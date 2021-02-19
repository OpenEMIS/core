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
		$this->loadModel('Institution.InstitutionStudents');
		$this->loadModel('Institution.StudentWithdraw');
		$this->loadModel('Institution.InstitutionClassStudents');
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
                    ])->all();
		if (!$data->isEmpty()) {
			$InstitutionStudents = TableRegistry::get('Institution.InstitutionStudents');
			foreach ($data as $key => $value) {
				$conditions = [
			        $InstitutionStudents->aliasField('academic_period_id = ') => $currentYearId,
			        $InstitutionStudents->aliasField('student_id = ') => $value['student_id'],
			        $InstitutionStudents->aliasField('institution_id = ') => $value['institution_id'],
			        $InstitutionStudents->aliasField('student_status_id = ') => 1,
	        	];
	        	$StudentStatusUpdate = $InstitutionStudents
				        ->find()
				        ->where($conditions)
				        ->all();
				if (!$StudentStatusUpdate->isEmpty()) {
					$statusUpdate = $StudentStatusUpdate->first();
					//update Institution Students table
					$InstitutionStudents
                	->updateAll(['student_status_id' => 4],['id' => $statusUpdate->id]);
					

					//update institution_class_students table
					$InstitutionClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
					$conditionsClassStudents = [
				        $InstitutionClassStudents->aliasField('academic_period_id = ') => $currentYearId,
				        $InstitutionClassStudents->aliasField('student_id = ') => $value['student_id'],
				        $InstitutionClassStudents->aliasField('institution_id = ') => $value['institution_id'],
				       $InstitutionClassStudents->aliasField('student_status_id = ') => 1,
		        	];

		        	$ClassStudentsStatusUpdate = $InstitutionClassStudents
				        ->find()
				        ->where($conditionsClassStudents)
				        ->all();
				    if (!$ClassStudentsStatusUpdate->isEmpty()) {
				    	$ClassStudentsUpdate = $ClassStudentsStatusUpdate->first();
				    	$InstitutionClassStudents
                		->updateAll(['student_status_id' => 4],['id' => $ClassStudentsUpdate->id]);

				    }

					 $StudentWithdraw = TableRegistry::get('Institution.StudentWithdraw');
					 $conditions = [
				        $StudentWithdraw->aliasField('academic_period_id = ') => $currentYearId,
				        $StudentWithdraw->aliasField('student_id = ') => $value['student_id'],
				        $StudentWithdraw->aliasField('institution_id = ') => $value['institution_id'],
				        $StudentWithdraw->aliasField('education_grade_id = ') => $statusUpdate->education_grade_id,
	        		];

	        		$StudentWithdrawAdd = $StudentWithdraw
				        ->find()
				        ->where($conditions)
				        ->all();
				        
				     if ($StudentWithdrawAdd->isEmpty()) {
				     	$date = date('Y-m-d H:i:s');
				     	$newStudentWithdraw = [
                            'effective_date' => $date,
                            'status_id' => 76,
                            'student_id' => $value['student_id'],
                            'institution_id' => $value['institution_id'],
                            'education_grade_id' => $statusUpdate->education_grade_id,
                            'academic_period_id' => $currentYearId,
                            'student_withdraw_reason_id' => 669,
                            'comment' => "dropout",
                            'created' => $date,
                        	'created_user_id' => 1
                           
                        ];

                        $StudentWithdraw
                        ->query()
                        ->insert(['effective_date', 'status_id','student_id','institution_id','education_grade_id','academic_period_id','student_withdraw_reason_id','comment','created', 'created_user_id'])
                        ->values($newStudentWithdraw)
                        ->execute();
				     }

				}
			}
		}

	}
}
