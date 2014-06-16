<?php
class TrainingSessionTrainee extends TrainingAppModel {
	
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

		$trainingCourseTargetPopulation = ClassRegistry::init('TrainingCourseTargetPopulation');
		$staffPositionID = $trainingCourseTargetPopulation->find('list', 
			array(
				'fields'=>array('TrainingCourseTargetPopulation.staff_position_title_id'),
				'conditions'=>array('TrainingCourseTargetPopulation.training_course_id'=>$trainingCourseID)
			)
		);

		$trainingCourse = ClassRegistry::init('TrainingCourse');
		$excludedStaffID = $trainingCourse->find('list', 
			array(
				'fields'=>array('TrainingSessionTrainee.staff_id'),
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
				'conditions'=>array('TrainingCourse.id'=>$trainingCourseID,'TrainingSession.training_status_id'=>3, 'TrainingSessionTrainee.pass'=>1)
			)
		);

		$conditions['OR'] = array("Staff.first_name LIKE '" . $search . "'", "Staff.last_name LIKE '" . $search  . "'", "Staff.identification_no LIKE '" . $search . "'");
		$joins = array();
		if(!empty($staffPositionID)){
			$tableJoin['table'] = 'institution_site_staff';
			$tableJoin['alias'] = 'InstitutionSiteStaff';
			$tableJoin['type'] = 'INNER';
			$tableJoin['conditions'] = array('Staff.id = InstitutionSiteStaff.staff_id', 'InstitutionSiteStaff.staff_position_title_id IN ('.ltrim(implode(",", $staffPositionID), ',').')');
			
			$joins[] = $tableJoin;
			if(!empty($excludedStaffID)){
				$conditions[] = 'InstitutionSiteStaff.staff_id NOT IN (' . ltrim(implode(",",$excludedStaffID), ',') . ')';
			}
		}else{
			if(!empty($excludedStaffID)){
				$conditions[] = 'Staff.id NOT IN (' . ltrim(implode(",",$excludedStaffID), ',') . ')';
			}
		}

		$this->Staff->useTable = 'Staff';
		$list = $this->Staff->find('all', 
			array(
				'fields'=>array('Staff.id', 'Staff.first_name', 'Staff.last_name'),
				'joins'=> $joins,
				'conditions'=>$conditions,
				'order'=> array('Staff.identification_no', 'Staff.first_name', 'Staff.last_name')
			)
		);


		$data = array();
		
		foreach($list as $obj) {
			$id = $obj['Staff']['id'];
			$firstName = $obj['Staff']['first_name'];
			$lastName = $obj['Staff']['last_name'];
			
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