<?php
App::uses('AppModel', 'Model');

class CensusStudent extends AppModel {
	public $actsAs = array('Containable');
	public $belongsTo = array(
		'SchoolYear' => array('foreignKey' => 'school_year_id'),
		'EducationGrade' => array('foreignKey' => 'education_grade_id'),
		'StudentCategory'=>array('foreignKey' => 'student_category_id'),
		'InstitutionSiteProgramme' => array('foreignKey' => 'institution_site_programme_id'),
		'InstitutionSite' =>
            array(
                'joinTable'  => 'institution_sites',
				'foreignKey' => false,
                'conditions' => array(' InstitutionSite.id = InstitutionSiteProgramme.institution_site_id '),
            ),
		'Institution' =>
            array(
                'joinTable'  => 'institutions',
				'foreignKey' => false,
                'conditions' => array(' Institution.id = InstitutionSite.institution_id '),
            )
	);
	
	public function getData($siteId, $yearId, $gradeId, $categoryId) {
		$sql = "
SELECT
	`census_students`.`id`,
	IFNULL(`census_students`.`age`, `grades`.`official_age`) AS `age`,
	IFNULL(`census_students`.`male`, 0) AS `male`,
	IFNULL(`census_students`.`female`, 0) AS `female`
FROM `institution_site_programmes`
JOIN `education_programmes` 
	ON `education_programmes`.`id` = `institution_site_programmes`.`education_programme_id`
JOIN `education_cycles`
	ON `education_cycles`.`id` = `education_programmes`.`education_cycle_id`
JOIN `education_levels`
	ON `education_levels`.`id` = `education_cycles`.`education_level_id`
JOIN (
	SELECT
	`edu_grades`.`id` AS grade_id,
	`education_programmes`.`id` AS `education_programme_id`,
	`education_programmes`.`name` AS `education_programme_name`,
	`edu_grades`.`name` AS `education_grade_name`,
	IF(
		`edu_grades`.`order` = 1,
		@curRow := `education_cycles`.`admission_age`,
		@curRow := @curRow + 1
	) AS `official_age`
	FROM (
		SELECT `id`, `name`, `education_programme_id`, `order`
		FROM `education_grades`
		ORDER BY `education_programme_id`, `order`
	) `edu_grades`
	LEFT JOIN `education_programmes` ON `education_programmes`.`id` = `edu_grades`.`education_programme_id`
	LEFT JOIN `education_cycles` ON `education_cycles`.`id` = `education_programmes`.`education_cycle_id`
) AS `grades`
	ON `grades`.`education_programme_id` = `education_programmes`.`id`
LEFT JOIN `student_categories`
	ON `student_categories`.`id` = %d
LEFT JOIN `census_students`
	ON `census_students`.`institution_site_programme_id` = `institution_site_programmes`.`id`
	AND `census_students`.`education_grade_id` = `grades`.`grade_id`
	AND `census_students`.`student_category_id` = `student_categories`.`id`
	AND `census_students`.`school_year_id` = %d
WHERE `institution_site_programmes`.`institution_site_id` = %d
AND `grades`.`grade_id` = %d
ORDER BY `education_levels`.`order`, `education_cycles`.`order`, `education_programmes`.`order`
";
		
		$query = sprintf($sql, $categoryId, $yearId, $siteId, $gradeId);
		$list = $this->query($query);
		
		return $list;
	}
	
	public function getCensusData($siteId, $yearId, $gradeId, $categoryId) {
		return $this->formatArray($this->getData($siteId, $yearId, $gradeId, $categoryId));
	}
	
	public function saveCensusData($data) {
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
