<?php
namespace App\Shell;

use Cake\Console\Shell;
use Cake\ORM\TableRegistry;
use Cake\Datasource\ConnectionManager;

class UpdateInstitutionShiftTypeShell extends Shell {
	public function initialize() {
		parent::initialize();
	}

 	public function main() {
 		$this->out("started");
 		
		$academicPeriod = $this->args[0];

		$this->out('academic period -> ' . $academicPeriod);

		$conn = ConnectionManager::get('default');

    	$conn->execute("UPDATE `institutions`
						SET `shift_type` = 0;"); //set all back to 0

    	//for owner
    	$query = "UPDATE `institutions` I
					INNER JOIN (
				    	SELECT `institution_id`, COUNT(`id`) AS counter 
				    	FROM `institution_shifts` 
				    	WHERE `academic_period_id` = $academicPeriod
				    	GROUP BY `institution_id`
				   	)S ON S.`institution_id` = I.`id`
					SET `shift_type` = IF(S.`counter` > 1, 3, 1);";
		//$this->out($query);
    	$conn->execute($query);

    	//for occupier
    	$query = "UPDATE `institutions` I
					INNER JOIN (
				    	SELECT `location_institution_id`, COUNT(`id`) AS counter
				        FROM `institution_shifts`
				        WHERE `academic_period_id` = $academicPeriod
				        AND `location_institution_id` <> `institution_id`
				        GROUP BY `location_institution_id`
				   	)S ON S.`location_institution_id` = I.`id`
					SET `shift_type` = IF(S.`counter`	 > 1, 4, 2);";
		//$this->out($query);
    	$conn->execute($query);

		$this->out("ended");
	}
}