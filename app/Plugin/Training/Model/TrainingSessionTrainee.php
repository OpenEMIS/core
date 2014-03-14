<?php
class TrainingSessionTrainee extends TrainingAppModel {
	//public $useTable = 'student_health_histories';
	
	public $belongsTo = array(
		'TrainingSession' => array(
			'className' => 'TrainingSession',
			'foreignKey' => 'training_session_id'
		)
	);

	public function autocomplete($search, $index, $trainingCourseID) {
		$search = sprintf('%%%s%%', $search);
		$data = array();

		$trainingCourse = ClassRegistry::init('TrainingCourse');
		$positions = $trainingCourse->find('all', 
			array(
				'fields'=>array('TrainingCourseTargetPopulation.position_title_id', 'TrainingCourseTargetPopulation.position_title_table'),
				'joins'=> array(
					array(
		                'table' => 'training_course_target_populations','alias' => 'TrainingCourseTargetPopulation','type' => 'INNER',
		                'conditions' => array('TrainingCourse.id = TrainingCourseTargetPopulation.training_course_id')
		            ),
				),
				'conditions'=>array('TrainingCourse.id'=>$trainingCourseID)
			)
		);
		$staffPositionID = '';
		$teacherPositionID = '';
		foreach($positions as $position){
			if($position['TrainingCourseTargetPopulation']['position_title_table']=='staff_position_titles'){
				$staffPositionID .= ','.$position['TrainingCourseTargetPopulation']['position_title_id'];
			}
			if($position['TrainingCourseTargetPopulation']['position_title_table']=='teacher_position_titles'){
				$teacherPositionID .= ','.$position['TrainingCourseTargetPopulation']['position_title_id'];
			}
		}
		
		$staffConditions = '';
		$teacherConditions = '';
		if(!empty($staffPositionID)){
			$staffConditions = ' INNER JOIN institution_site_staff AS InstitutionSiteStaff ON Staff.id = InstitutionSiteStaff.staff_id 
			WHERE InstitutionSiteStaff.staff_position_title_id IN (' . ltrim($staffPositionID, ',') . ')';
			$teacherConditions = ' INNER JOIN institution_site_teachers AS InstitutionSiteTeacher ON Teacher.id = InstitutionSiteTeacher.teacher_id 
			WHERE InstitutionSiteTeacher.teacher_position_title_id IN (' . ltrim($teacherPositionID, ',') . ')';
		}

		$list = $this->query(
			"SELECT * FROM(
			SELECT Staff.*, 'staff' as identification_table FROM staff as Staff " . $staffConditions . " UNION 
			Select Teacher.*, 'teachers' as identification_table from teachers as Teacher " . $teacherConditions . "
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