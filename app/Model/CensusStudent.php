<?php
App::uses('AppModel', 'Model');

class CensusStudent extends AppModel {
	public $actsAs = array('Containable');
	public $belongsTo = array(
		'SchoolYear' => array('foreignKey' => 'school_year_id'),
		'EducationGrade' => array('foreignKey' => 'education_grade_id'),
		'StudentCategory'=>array('foreignKey' => 'student_category_id'),
		'InstitutionSite' => array('foreignKey' => 'institution_site_id'),
		'Institution' =>
            array(
                'joinTable'  => 'institutions',
				'foreignKey' => false,
                'conditions' => array(' Institution.id = InstitutionSite.institution_id '),
            )
	);
	
	public function getCensusData($siteId, $yearId, $gradeId, $categoryId) {
		$this->formatResult = true;
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array('CensusStudent.id', 'CensusStudent.age', 'CensusStudent.male', 'CensusStudent.female', 'CensusStudent.source'),
			'joins' => array(
				array(
					'table' => 'education_grades',
					'alias' => 'EducationGrade',
					'conditions' => array(
						'EducationGrade.id = CensusStudent.education_grade_id'
					)
				),
				array(
					'table' => 'education_programmes',
					'alias' => 'EducationProgramme',
					'conditions' => array(
						'EducationProgramme.id = EducationGrade.education_programme_id'
					)
				),
				array(
					'table' => 'education_cycles',
					'alias' => 'EducationCycle',
					'conditions' => array(
						'EducationCycle.id = EducationProgramme.education_cycle_id'
					)
				),
				array(
					'table' => 'education_levels',
					'alias' => 'EducationLevel',
					'conditions' => array(
						'EducationLevel.id = EducationCycle.education_level_id'
					)
				)
			),
			'conditions' => array(
				'CensusStudent.education_grade_id' => $gradeId,
				'CensusStudent.school_year_id' => $yearId,
				'CensusStudent.student_category_id' => $categoryId,
				'CensusStudent.institution_site_id' => $siteId
			),
			'order' => array('EducationLevel.order', 'EducationCycle.order', 'EducationProgramme.order', 'CensusStudent.age')
		));
		return $data;
	}
	
	public function saveCensusData($data, $institutionSiteId) {
		$keys = array();
		$deleted = array();
		
		if(isset($data['deleted'])) {
			 $deleted = $data['deleted'];
			 unset($data['deleted']);
		}
		foreach($deleted as $id) {
			$this->delete($id);
		}
		
		for($i=0; $i<sizeof($data); $i++) {
			$row = $data[$i];
			if($row['age'] > 0 && ($row['male'] > 0 || $row['female'] > 0)) {
				if($row['id'] == 0) {
					$this->create();
				}
				$row['institution_site_id'] = $institutionSiteId;
				$save = $this->save(array('CensusStudent' => $row));
				if($row['id'] == 0) {
					$keys[strval($i+1)] = $save['CensusStudent']['id'];
				}
			} else if($row['id'] > 0 && $row['male'] == 0 && $row['female'] == 0) {
				$this->delete($row['id']);
			}
		}
		return $keys;
	}
	
	public function findListAsSubgroups() {
		$this->formatResult = true;
		$list = $this->find('all', array(
			'fields' => array('CensusStudent.id', 'CensusStudent.education_grade_id', 'CensusStudent.age'),
			'conditions' => array('EducationGrade.visible' => 1),
			'group' => array('CensusStudent.education_grade_id', 'CensusStudent.age')
		));
		
		$ageList = array();
		foreach($list as $obj) {
			$gradeId = $obj['education_grade_id'];
			$age = $obj['age'];
			if(!isset($ageList[$age])) {
				$ageList[$age] = array('grades' => array());
			}
			$ageList[$age]['grades'][] = $gradeId;
		}
		
		return $ageList;
	}
}
