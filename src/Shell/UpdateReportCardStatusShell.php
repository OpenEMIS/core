<?php
namespace App\Shell;

use Cake\Console\Shell;
use Cake\Datasource\ConnectionManager;
//POCOR-7530 for updating status
class UpdateReportCardStatusShell extends Shell {
	public function initialize() {
		parent::initialize();
	}

 	public function main() {
 		$this->out("started");
        
        $conn = ConnectionManager::get('default');

    	$stmt = $conn->query("UPDATE institution_students_report_cards 
                              INNER JOIN report_card_processes 
                              ON institution_students_report_cards.report_card_id = report_card_processes.report_card_id 
                              AND institution_students_report_cards.student_id = report_card_processes.student_id 
                              AND institution_students_report_cards.institution_id = report_card_processes.institution_id 
                              AND institution_students_report_cards.academic_period_id = report_card_processes.academic_period_id
                              AND institution_students_report_cards.education_grade_id = report_card_processes.education_grade_id
                              AND institution_students_report_cards.institution_class_id = report_card_processes.institution_class_id 
                              SET institution_students_report_cards.status = report_card_processes.status  
                              where institution_students_report_cards.status In (1,2,3)");
        $success=$stmt->execute();
		$this->out("ended");
	}
}