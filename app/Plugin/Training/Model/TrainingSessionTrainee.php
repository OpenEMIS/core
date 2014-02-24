<?php
class TrainingSessionTrainee extends TrainingAppModel {
	//public $useTable = 'student_health_histories';
	
	public $belongsTo = array(
		'TrainingSession' => array(
			'className' => 'TrainingSession',
			'foreignKey' => 'training_session_id'
		)
	);

	public function autocomplete($search, $index) {
		$search = sprintf('%%%s%%', $search);
		$data = array();

		$list = $this->query(
			"SELECT * FROM(
			SELECT *, 'staff' as identification_table FROM staff as Staff UNION Select *, 'teachers' as identification_table from teachers as Teacher 
			)as TrainingSessionTrainee
			WHERE first_name LIKE '" . $search . "' OR last_name LIKE '" . $search  . "' OR  identification_no LIKE '" . $search . "'
			order by identification_no, first_name, last_name;");
		
		
		$data = array();
		
		foreach($list as $obj) {
			$id = $obj['TrainingSessionTrainee']['id'];
			$firstName = $obj['TrainingSessionTrainee']['first_name'];
			$lastName = $obj['TrainingSessionTrainee']['last_name'];
			$table = $obj['TrainingSessionTrainee']['identification_table'];
			
			$data[] = array(
				'label' => trim(sprintf('%s,  %s', $firstName, $lastName)),
				'value' => array(
					'trainee-id-'.$index => $id, 
					'trainee-table-'.$index => $table,
					'trainee-first-name-'.$index => $firstName,
					'trainee-last-name-'.$index => $lastName,
					'trainee-name-'.$index => trim(sprintf('%s, %s', $firstName, $lastName)),
					'trainee-validate-'.$index => $table . '_' . $id
					)
			);
		}
	
		return $data;
	}
}
?>