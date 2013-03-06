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
		/* Actual SQL
		SELECT
			`census_classes`.`id`,
			`census_classes`.`classes`,
			`census_classes`.`seats`,
			`education_programmes`.`id` AS `education_programme_id`,
			CONCAT(`education_cycles`.`name`, ' - ', `education_programmes`.`name`) AS `education_programme_name`,
			`education_grades`.`id` AS `education_grade_id`,
			`education_grades`.`name` AS `education_grade_name`
		FROM `census_classes`
		JOIN `institution_site_programmes`
			ON `institution_site_programmes`.`institution_site_id` = `census_classes`.`institution_site_id`
			AND `institution_site_programmes`.`status` = 1
		JOIN `education_programmes` 
			ON `education_programmes`.`id` = `institution_site_programmes`.`education_programme_id`
			AND `education_programmes`.`visible` = 1
		JOIN `education_cycles`
			ON `education_cycles`.`id` = `education_programmes`.`education_cycle_id`
			AND `education_cycles`.`visible` = 1
		JOIN `education_levels`
			ON `education_levels`.`id` = `education_cycles`.`education_level_id`
			AND `education_levels`.`visible` = 1
		JOIN `education_grades`
			ON `education_grades`.`education_programme_id` = `education_programmes`.`id`
			AND `education_grades`.`visible` = 1
		JOIN `census_class_grades`
			ON `census_class_grades`.`census_class_id` = `census_classes`.`id`
			AND `census_class_grades`.`education_grade_id` = `education_grades`.`id`
		WHERE `census_classes`.`school_year_id` = 1
		AND `census_classes`.`institution_site_id` = 1
		GROUP BY
			`census_classes`.`id`
		HAVING
			COUNT(`census_class_grades`.`census_class_id`) <= 1
		ORDER BY 
			`education_levels`.`order`,
			`education_cycles`.`order`,
			`education_programmes`.`order`,
			`education_grades`.`order`
		*/
		
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
						'InstitutionSiteProgramme.status = 1',
					)
				),
				array(
					'table' => 'education_programmes',
					'alias' => 'EducationProgramme',
					'conditions' => array(
						'EducationProgramme.id = InstitutionSiteProgramme.education_programme_id',
						'EducationProgramme.visible = 1',
					)
				),
				array(
					'table' => 'education_cycles',
					'alias' => 'EducationCycle',
					'conditions' => array(
						'EducationCycle.id = EducationProgramme.education_cycle_id',
						'EducationCycle.visible = 1'
					)
				),
				array(
					'table' => 'education_levels',
					'alias' => 'EducationLevel',
					'conditions' => array(
						'EducationLevel.id = EducationCycle.education_level_id',
						'EducationLevel.visible = 1'
					)
				),
				array(
					'table' => 'education_grades',
					'alias' => 'EducationGrade',
					'conditions' => array(
						'EducationGrade.education_programme_id = EducationProgramme.id',
						'EducationGrade.visible = 1'
					)
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
		/* Actual SQL
		// Get those records with multi-grades
		SELECT 
			`census_classes`.`id`
		FROM `census_classes`
		JOIN `census_class_grades`
			ON `census_class_grades`.`census_class_id` = `census_classes`.`id`
		WHERE `census_classes`.`school_year_id` = 1
		AND `census_classes`.`institution_site_id` = 1
		GROUP BY 
			`census_classes`.`id`
		HAVING 
			COUNT(`census_class_grades`.`census_class_id`) > 1
		
		// Then fetch the list of grades based on the results from the query above
		SELECT
			`census_classes`.`id`,
			`census_classes`.`classes`,
			`census_classes`.`seats`,
			CONCAT(`education_cycles`.`name`, ' - ', `education_programmes`.`name`) AS `education_programme_name`,
			`education_grades`.`id` AS `education_grade_id`,
			`education_grades`.`name`
		FROM `census_classes`
		JOIN `census_class_grades`
			ON `census_class_grades`.`census_class_id` = `census_classes`.`id`
		JOIN `education_grades`
			ON `education_grades`.`id` = `census_class_grades`.`education_grade_id`
			AND `education_grades`.`visible` = 1
		JOIN `education_programmes`
			ON `education_programmes`.`id` = `education_grades`.`education_programme_id`
			AND `education_programmes`.`visible` = 1
		JOIN `education_cycles`
			ON `education_cycles`.`id` = `education_programmes`.`education_cycle_id`
			AND `education_cycles`.`visible` = 1
		JOIN `education_levels`
			ON `education_levels`.`id` = `education_cycles`.`education_level_id`
			AND `education_levels`.`visible` = 1
		WHERE `census_classes`.`id` = 3
		ORDER BY
			`education_levels`.`order`,
			`education_cycles`.`order`,
			`education_programmes`.`order`,
			`education_grades`.`order`,
			`census_classes`.`id`
		*/
		
		$classList = $this->find('list' , array(
			'recursive' => -1,
			'fields' => array('CensusClass.id'),
			'joins' => array(
				array(
					'table' => 'census_class_grades',
					'alias' => 'CensusClassGrade',
					'conditions' => array(
						'CensusClassGrade.census_class_id = CensusClass.id'
					)
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
					'conditions' => array(
						'CensusClassGrade.census_class_id = CensusClass.id'
					)
				),
				array(
					'table' => 'education_grades',
					'alias' => 'EducationGrade',
					'conditions' => array(
						'EducationGrade.id = CensusClassGrade.education_grade_id',
						'EducationGrade.visible = 1'
					)
				),
				array(
					'table' => 'education_programmes',
					'alias' => 'EducationProgramme',
					'conditions' => array(
						'EducationProgramme.id = EducationGrade.education_programme_id',
						'EducationProgramme.visible = 1',
					)
				),
				array(
					'table' => 'education_cycles',
					'alias' => 'EducationCycle',
					'conditions' => array(
						'EducationCycle.id = EducationProgramme.education_cycle_id',
						'EducationCycle.visible = 1'
					)
				),
				array(
					'table' => 'education_levels',
					'alias' => 'EducationLevel',
					'conditions' => array(
						'EducationLevel.id = EducationCycle.education_level_id',
						'EducationLevel.visible = 1'
					)
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