<?php
class TrainingSessionTrainee extends TrainingAppModel {
	//public $useTable = 'student_health_histories';
	
	public $belongsTo = array(
		'TrainingSession' => array(
			'className' => 'TrainingSession',
			'foreignKey' => 'training_session_id'
		),
		'Staff'
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
		                'table' => 'training_course_target_populations',
		                'alias' => 'TrainingCourseTargetPopulation',
		                'type' => 'INNER',
		                'conditions' => array('TrainingCourse.id = TrainingCourseTargetPopulation.training_course_id')
		            ),
				),
				'conditions'=>array('TrainingCourse.id'=>$trainingCourseID)
			)
		);
		$staffPositionID = '';
		//$teacherPositionID = '';
		foreach($positions as $position){
			if($position['TrainingCourseTargetPopulation']['position_title_table']=='staff_position_titles'){
				$staffPositionID .= ','.$position['TrainingCourseTargetPopulation']['position_title_id'];
			}
			/*if($position['TrainingCourseTargetPopulation']['position_title_table']=='teacher_position_titles'){
				$teacherPositionID .= ','.$position['TrainingCourseTargetPopulation']['position_title_id'];
			}*/
		}
		
		$completed = $trainingCourse->find('all', 
			array(
				'fields'=>array('TrainingSessionTrainee.*'),
				'joins'=> array(
					array(
		                'table' => 'training_sessions',
		                'alias' => 'TrainingSession',
		                'type' => 'INNER',
		                'conditions' => array('TrainingCourse.id = TrainingSession.training_course_id')
		            ),
					array(
		                'table' => 'training_session_trainees',
		                'alias' => 'TrainingSessionTrainee',
		                'type' => 'INNER',
		                'conditions' => array('TrainingSession.id = TrainingSessionTrainee.training_session_id')
		            ),
				),
				'conditions'=>array('TrainingCourse.id'=>$trainingCourseID,'TrainingSession.training_status_id'=>3)
			)
		);

		$excludedStaffID = '';
		//$excludedTeacherID = '';
		foreach($completed as $val){
			$excludedStaffID .= ','.$val['TrainingSessionTrainee']['staff_id'];
			/*
			if($val['TrainingSessionTrainee']['identification_table']=='teachers'){
				$excludedTeacherID .= ','.$val['TrainingSessionTrainee']['identification_id'];
			}*/
		}
		$staffConditions = '';
		//$teacherConditions = '';
		if(!empty($staffPositionID)){
			$staffConditions = ' INNER JOIN institution_site_staff AS InstitutionSiteStaff ON Staff.id = InstitutionSiteStaff.staff_id 
			WHERE InstitutionSiteStaff.staff_position_title_id IN (' . ltrim($staffPositionID, ',') . ')';
			if(!empty($excludedStaffID)){
				$staffConditions .= ' AND InstitutionSiteStaff.staff_id NOT IN (' . ltrim($excludedStaffID, ',') . ')';
			}
		}else{
			if(!empty($excludedStaffID)){
				$staffConditions = ' WHERE Staff.id NOT IN (' . ltrim($excludedStaffID, ',') . ')';
			}
		}
		pr($staffConditions);
		/*
		if(!empty($teacherPositionID)){
			$teacherConditions = ' INNER JOIN institution_site_teachers AS InstitutionSiteTeacher ON Teacher.id = InstitutionSiteTeacher.teacher_id 
			WHERE InstitutionSiteTeacher.teacher_position_title_id IN (' . ltrim($teacherPositionID, ',') . ')';
			if(!empty($excludedTeacherID)){
				$teacherConditions .= ' AND InstitutionSiteTeacher.teacher_id NOT IN (' . ltrim($excludedTeacherID, ',') . ')';
			}
		}else{
			if(!empty($excludedTeacherID)){
				$teacherConditions = ' WHERE Teacher.id NOT IN (' . ltrim($excludedTeacherID, ',') . ')';
			}
		}*/
		/*$list = $this->query(
			"SELECT * FROM(
			SELECT Staff.*, 'staff' as identification_table FROM staff as Staff " . $staffConditions . " UNION 
			Select Teacher.*, 'teachers' as identification_table from teachers as Teacher " . $teacherConditions . "
			)as TrainingSessionTrainee
			WHERE first_name LIKE '" . $search . "' OR last_name LIKE '" . $search  . "' OR  identification_no LIKE '" . $search . "'
			order by identification_no, first_name, last_name;");
		*/

		$list = $this->query(
			"SELECT * FROM(
			SELECT Staff.* FROM staff as Staff " . $staffConditions . " 
			)as TrainingSessionTrainee
			WHERE first_name LIKE '" . $search . "' OR last_name LIKE '" . $search  . "' OR  identification_no LIKE '" . $search . "'
			order by identification_no, first_name, last_name;");

		pr($list);
		$data = array();
		
		foreach($list as $obj) {
			$id = $obj['TrainingSessionTrainee']['id'];
			$firstName = $obj['TrainingSessionTrainee']['first_name'];
			$lastName = $obj['TrainingSessionTrainee']['last_name'];
			
			$data[] = array(
				'label' => trim(sprintf('%s,  %s', $firstName, $lastName)),
				'value' => array(
					'trainee-id-'.$index => $id, 
					'trainee-first-name-'.$index => $firstName,
					'trainee-last-name-'.$index => $lastName,
					'trainee-name-'.$index => trim(sprintf('%s, %s', $firstName, $lastName)),
					'trainee-validate-'.$index => $id
					)
			);
		}
	
		return $data;
	}
}
?>