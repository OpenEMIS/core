<?php
class TrainingSessionTrainee extends TrainingAppModel {
	public $belongsTo = array(
		'TrainingSession' => array(
			'className' => 'TrainingSession',
			'foreignKey' => 'training_session_id'
		),
		'Staff.Staff'
	);

	public $hasMany = array(
        'TrainingSessionTraineeResult' => array(
			'className' => 'TrainingSessionTraineeResult',
			'foreignKey' => 'training_session_trainee_id',
			'dependent' => true
		),
    );

    public function searchCriteria($conditions, $trainingCourseID){
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
		
		$trainingCourseSpecialisation = ClassRegistry::init('TrainingCourseSpecialisation');
		$qualificationSpecialisationID = $trainingCourseSpecialisation->find('list', 
			array(
				'fields'=>array('TrainingCourseSpecialisation.qualification_specialisation_id'),
				'conditions'=>array('TrainingCourseSpecialisation.training_course_id'=>$trainingCourseID)
			)
		);

		$trainingCourseExperience = ClassRegistry::init('TrainingCourseExperience');
		$experience = $trainingCourseExperience->find('first', 
			array(
				'fields'=>array('TrainingCourseExperience.months'),
				'conditions'=>array('TrainingCourseExperience.training_course_id'=>$trainingCourseID)
			)
		);

		$joins = array();
		if(!empty($staffPositionID)){
			$tableJoin['table'] = 'institution_site_staff';
			$tableJoin['alias'] = 'InstitutionSiteStaff';
			$tableJoin['type'] = 'INNER';
			$tableJoin['conditions'] = array('Staff.id = InstitutionSiteStaff.staff_id');
			$joins[] = $tableJoin;

			$tableJoin['table'] = 'institution_site_positions';
			$tableJoin['alias'] = 'InstitutionSitePosition';
			$tableJoin['type'] = 'INNER';
			$tableJoin['conditions'] = array('InstitutionSiteStaff.institution_site_position_id = InstitutionSitePosition.id', 'InstitutionSitePosition.staff_position_title_id IN ('.ltrim(implode(",", $staffPositionID), ',').')');
			$joins[] = $tableJoin;
		}

		if(!empty($excludedStaffID)){
			$conditions[] = 'Staff.id NOT IN (' . ltrim(implode(",",$excludedStaffID), ',') . ')';
		}
		
		if(!empty($qualificationSpecialisationID)){
			$tableJoin['table'] = 'staff_qualifications';
			$tableJoin['alias'] = 'StaffQualification';
			$tableJoin['type'] = 'INNER';
			$tableJoin['conditions'] = array('Staff.id = StaffQualification.staff_id', 'StaffQualification.qualification_specialisation_id IN ('.ltrim(implode(",", $qualificationSpecialisationID), ',').')');
			$joins[] = $tableJoin;
		}

		if(!empty($experience)){
			if($experience['TrainingCourseExperience']['months']>0){
				$tableJoin['table'] = 'staff_employments';
				$tableJoin['alias'] = 'StaffEmployment';
				$tableJoin['type'] = 'INNER';
				$compareDate = date("Y-m-d",strtotime("-".$experience['TrainingCourseExperience']['months']." month",  time())); 
				$tableJoin['conditions'] = array('Staff.id = StaffEmployment.staff_id', 'StaffEmployment.employment_date <= "' . $compareDate . '"');
			
				$joins[] = $tableJoin;
			}
		}

		$list = $this->Staff->find('all', 
			array(
				'fields'=>array('Staff.id', 'Staff.first_name', 'Staff.middle_name', 'Staff.third_name', 'Staff.last_name'),
				'joins'=> $joins,
				'conditions'=>$conditions,
				'order'=> array('Staff.identification_no', 'Staff.first_name', 'Staff.last_name')
			)
		);

		return $list;
    }


	public function autocomplete($search, $index, $trainingCourseID) {
		$search = sprintf('%%%s%%', $search);
		$data = array();
		$conditions['OR'] = array("Staff.first_name LIKE '" . $search . "'", "Staff.middle_name LIKE '" . $search  . "'", "Staff.third_name LIKE '" . $search . "'", "Staff.last_name LIKE '" . $search  . "'", "Staff.identification_no LIKE '" . $search . "'");
		$list = $this->searchCriteria($conditions, $trainingCourseID);
		
		foreach($list as $obj) {
			$id = $obj['Staff']['id'];
			
			$data[] = array(
				'label' => ModelHelper::getName($obj['Staff']),
				'value' => array(
					'trainee-id-'.$index => $id, 
					'trainee-first-name-'.$index => $obj['Staff']['first_name'],
					'trainee-middle-name-'.$index => $obj['Staff']['middle_name'],
					'trainee-third-name-'.$index => $obj['Staff']['third_name'],
					'trainee-last-name-'.$index => $obj['Staff']['last_name'],
					'trainee-name-'.$index => ModelHelper::getName($obj['Staff']),
					'trainee-validate-'.$index => $id
					)
			);
		}
	
		return $data;
	}
}
?>