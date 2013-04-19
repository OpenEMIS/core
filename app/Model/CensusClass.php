<?php
App::uses('AppModel', 'Model');

class CensusClass extends AppModel {
	public $belongsTo = array(
		'SchoolYear',
		'EducationGrade',
		'InstitutionSiteProgramme'
	);
	
	public function getClassId($institutionSiteId, $yearId) {
		$data = $this->find('list', array(
			'fields' => array('CensusClass.id'),
			'conditions' => array('CensusClass.institution_site_id' => $institutionSiteId, 'CensusClass.school_year_id' => $yearId)
		));
		return $data;
	}
	
	public function mergeSingleGradeData(&$class, $data) {
		foreach($class as $key => &$obj) {
			foreach($obj['education_grades'] as $gradeId => &$grade) {
				$classes = 0;
				$seats = null;
				foreach($data as $value) {
					if($value['education_grade_id'] == $gradeId 
					&& $value['education_programme_id'] == $obj['education_programme_id']) {
						$classes = $value['classes'];
						$seats = $value['seats'];
						break;
					}
				}
				$grade = array('name' => $grade, 'classes' => $classes, 'seats' => $seats);
			}
		}
	}
	
	public function getSingleGradeData($institutionSiteId, $yearId) {
		$this->formatResult = true;
		$data = $this->find('all' , array(
			'recursive' => -1,
			'fields' => array(
				'CensusClass.id',
				'CensusClass.classes',
				'CensusClass.seats',
				'EducationProgramme.id AS education_programme_id',
				"CONCAT(EducationCycle.name, ' - ', EducationProgramme.name) AS education_programme_name",
				'EducationGrade.id AS education_grade_id',
				'EducationGrade.name AS education_grade_name'
			),
			'joins' => array(
				array(
					'table' => 'institution_site_programmes',
					'alias' => 'InstitutionSiteProgramme',
					'conditions' => array(
						'InstitutionSiteProgramme.institution_site_id = CensusClass.institution_site_id',
						'InstitutionSiteProgramme.school_year_id = CensusClass.school_year_id'
					)
				),
				array(
					'table' => 'education_programmes',
					'alias' => 'EducationProgramme',
					'conditions' => array('EducationProgramme.id = InstitutionSiteProgramme.education_programme_id')
				),
				array(
					'table' => 'education_cycles',
					'alias' => 'EducationCycle',
					'conditions' => array('EducationCycle.id = EducationProgramme.education_cycle_id')
				),
				array(
					'table' => 'education_levels',
					'alias' => 'EducationLevel',
					'conditions' => array('EducationLevel.id = EducationCycle.education_level_id')
				),
				array(
					'table' => 'education_grades',
					'alias' => 'EducationGrade',
					'conditions' => array('EducationGrade.education_programme_id = EducationProgramme.id')
				),
				array(
					'table' => 'census_class_grades',
					'alias' => 'CensusClassGrade',
					'conditions' => array(
						'CensusClassGrade.census_class_id = CensusClass.id',
						'CensusClassGrade.education_grade_id = EducationGrade.id'
					)
				)
			),
			'conditions' => array(
				'CensusClass.school_year_id' => $yearId,
				'CensusClass.institution_site_id' => $institutionSiteId
			),
			'group' => array('CensusClass.id HAVING COUNT(CensusClassGrade.census_class_id) <= 1'),
			'order' => array('EducationLevel.order', 'EducationCycle.order', 'EducationProgramme.order', 'EducationGrade.order')
		));
		
		return $data;
	}
	
	public function getMultiGradeData($institutionSiteId, $yearId) {
		$classList = $this->find('list' , array(
			'recursive' => -1,
			'fields' => array('CensusClass.id'),
			'joins' => array(
				array(
					'table' => 'census_class_grades',
					'alias' => 'CensusClassGrade',
					'conditions' => array('CensusClassGrade.census_class_id = CensusClass.id')
				)
			),
			'conditions' => array(
				'CensusClass.school_year_id' => $yearId,
				'CensusClass.institution_site_id' => $institutionSiteId
			),
			'group' => array('CensusClass.id HAVING COUNT(CensusClassGrade.census_class_id) > 1')
		));
		
		$gradeList = $this->find('all' , array(
			'recursive' => -1,
			'fields' => array(
				'CensusClass.id',
				'CensusClass.classes',
				'CensusClass.seats',
				'EducationProgramme.id',
				"CONCAT(EducationCycle.name, ' - ', EducationProgramme.name) AS education_programme_name",
				'EducationGrade.id',
				'EducationGrade.name'
			),
			'joins' => array(
				array(
					'table' => 'census_class_grades',
					'alias' => 'CensusClassGrade',
					'conditions' => array('CensusClassGrade.census_class_id = CensusClass.id')
				),
				array(
					'table' => 'education_grades',
					'alias' => 'EducationGrade',
					'conditions' => array('EducationGrade.id = CensusClassGrade.education_grade_id')
				),
				array(
					'table' => 'education_programmes',
					'alias' => 'EducationProgramme',
					'conditions' => array('EducationProgramme.id = EducationGrade.education_programme_id')
				),
				array(
					'table' => 'education_cycles',
					'alias' => 'EducationCycle',
					'conditions' => array('EducationCycle.id = EducationProgramme.education_cycle_id')
				),
				array(
					'table' => 'education_levels',
					'alias' => 'EducationLevel',
					'conditions' => array('EducationLevel.id = EducationCycle.education_level_id')
				)
			),
			'conditions' => array('CensusClass.id' => $classList),
			'order' => array('EducationLevel.order', 'EducationCycle.order', 'EducationProgramme.order', 'EducationGrade.order', 'CensusClass.id')
		));
		
		$data = array();
		foreach($gradeList as $obj) {
			$programme = $obj['EducationProgramme'];
			$grade = $obj['EducationGrade'];
			$class = $obj['CensusClass'];
			
			if(!isset($data[$class['id']])) {
				$data[$class['id']] = array(
					'classes' => $class['classes'],
					'seats' => $class['seats'],
					'programmes' => array(),
					'grades' => array()
				);
			}
			$data[$class['id']]['programmes'][] = $obj[0]['education_programme_name'];
			$data[$class['id']]['grades'][$grade['id']] = $grade['name'];
		}
		return $data;
	}
	
	public function clean($data, $yearId, $institutionSiteId) {
		$clean = array();
		$gradeList = array();
		// get the current list of census class record ids from the database
		$classIds = $this->getClassId($institutionSiteId, $yearId);		
		foreach($data as $obj) {
			// remove duplicate grades per record
			$grades = array_unique($obj['CensusClassGrade']);
			if(array_search($grades, $gradeList, true) === false) { // the multi grade combination must not exists
				$gradeList[] = $grades;
				// reuse the current census class record ids
				$id = current($classIds);
				if($id === false) {
					$id = null;
				} else {
					next($classIds);
				}
				// build CensusClassGrade records
				foreach($grades as &$grade) {
					$grade = array('census_class_id' => $id, 'education_grade_id' => $grade);
				}
				$clean[] = array(
					'id' => $id,
					'classes' => $obj['classes'],
					'seats' => $obj['seats'],
					'institution_site_id' => $institutionSiteId,
					'school_year_id' => $yearId,
					'CensusClassGrade' => $grades
				);
			}
		}
		// Reset all values of classes and seats for the existing class ids
		$this->unbindModel(array('belongsTo' => array_keys($this->belongsTo)), true);
		$this->updateAll(
			array('CensusClass.classes' => 0, 'CensusClass.seats' => null),
			array('CensusClass.id' => $classIds)
		);
		// Finally, delete all existing census class grades records and re-insert them upon saving
		$CensusClassGrade = ClassRegistry::init('CensusClassGrade');
		$CensusClassGrade->deleteAll(array('CensusClassGrade.census_class_id' => $classIds), false);
		return $clean;
	}
	
	public function saveCensusData($data) {
		$CensusClassGrade = ClassRegistry::init('CensusClassGrade');
		foreach($data as $obj) {
			if(empty($obj['id'])) {
				$this->create();
			}
			$censusGrades = $obj['CensusClassGrade'];
			unset($obj['CensusClassGrade']);
			$result = $this->save($obj);
			$id = $result['CensusClass']['id'];
			foreach($censusGrades as $grade) {
				$grade['census_class_id'] = $id;
				$CensusClassGrade->save($grade);
			}
		}
	}
}