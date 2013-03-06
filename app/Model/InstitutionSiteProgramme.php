<?php
App::uses('AppModel', 'Model');

class InstitutionSiteProgramme extends AppModel {
	public $belongsTo = array(
		'InstitutionSite'=>array('foreignKey' => 'institution_site_id'),
		'EducationProgramme'=>array('foreignKey' => 'education_programme_id'),
		'Institution' =>
            array(
                'className'              => 'Institution',
                'joinTable'              => 'institutions',
				'foreignKey' => false,
				'dependent'    => false,
                'conditions' => array(' Institution.id = InstitutionSite.institution_id '),
            )
	);
	
	public $actsAs = array(
		'CascadeDelete' => array(
			'cascade' => array(
				'CensusClass',
				'CensusTextbook',
				'CensusStudent',
				'CensusTeacher'
			)
		)
	);
	
	/* can't work if recursive is set to 0
	public $virtualFields = array(
		'name' => 'EducationProgramme.name'
	);
	*/
	
	public $virtualFields = array(
		'name' => "SELECT name from `education_programmes` WHERE id = InstitutionSiteProgramme.education_programme_id"
	);
	
	public function getProgrammeList($institutionSiteId, $withGrades = true) {
		$list = $this->getActiveProgrammes($institutionSiteId);
		
		$data = array();
		foreach($list as $obj) {
			$name = $obj[0]['education_programme_name'];
			$programme = $obj['EducationProgramme'];
			$grade = $obj['EducationGrade'];
			
			if($withGrades) {
				if(!isset($data[$name])) {
					$data[$name] = array(
						'institution_site_programme_id' => $obj['InstitutionSiteProgramme']['id'],
						'education_programme_id' => $programme['id'],
						'education_grades' => array()
					);
				}
				$data[$name]['education_grades'][$grade['id']] = $grade['name'];
			} else {
				if(!isset($data[$programme['id']])) {
					$data[$programme['id']] = $name;
				}
			}
		}
		return $data;
	}
	
	public function getActiveProgrammes($institutionSiteId, $formatResult = false) {
		/* SQL
		SELECT
			`institution_site_programmes`.`id` AS `institution_site_programme_id`,
			`education_programmes`.`id` AS `education_programme_id`,
			CONCAT(`education_cycles`.`name`, ' - ', `education_programmes`.`name`) AS `education_programme_name`,
			`education_grades`.`id` AS `education_grade_id`,
			`education_grades`.`name` AS `education_grade_name`
		FROM `institution_site_programmes`
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
		WHERE `institution_site_programmes`.`institution_site_id` = 1
		AND `institution_site_programmes`.`status` = 1
		ORDER BY 
			`education_levels`.`order`,
			`education_cycles`.`order`,
			`education_programmes`.`order`,
			`education_grades`.`order`
		*/
		$fields = $formatResult
				? array(
					'InstitutionSiteProgramme.id AS institution_site_programme_id',
					'EducationProgramme.id AS education_programme_id',
					"CONCAT(EducationCycle.name, ' - ', EducationProgramme.name) AS education_programme_name",
					'EducationGrade.id AS education_grade_id',
					'EducationGrade.name AS education_grade_name'
				)
				: array(
					'InstitutionSiteProgramme.id',
					'EducationProgramme.id', 
					"CONCAT(EducationCycle.name, ' - ', EducationProgramme.name) AS education_programme_name",
					'EducationGrade.id', 'EducationGrade.name'
				);
		
		$this->formatResult = $formatResult;
		$data = $this->find('all' , array(
			'recursive' => -1,
			'fields' => $fields,
			'joins' => array(
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
				)
			),
			'conditions' => array(
				'InstitutionSiteProgramme.institution_site_id' => $institutionSiteId,
				'InstitutionSiteProgramme.status' => 1
			),
			'order' => array('EducationLevel.order', 'EducationCycle.order', 'EducationProgramme.order', 'EducationGrade.order')
		));
		
		return $data;
	}
	
	public function getAllProgBySystemId($educSystemId,$arrExclude = Array()){
		$exclude = (count($arrExclude) > 0 )?" AND education_programmes.id NOT IN (".implode(",",$arrExclude).")":"";
		  $arr = $this->query("
		  SELECT * FROM `education_programmes` 
		  LEFT JOIN education_cycles 
		  ON  education_cycles.id = education_programmes.education_cycle_id
		  LEFT JOIN education_levels 
		  ON  education_levels.id = education_cycles.education_level_id
		  LEFT JOIN education_field_of_studies 
		  ON  education_field_of_studies.id = education_programmes.education_field_of_study_id
		  LEFT JOIN education_certifications 
		  ON  education_certifications.id = education_programmes.education_certification_id
		  LEFT JOIN education_systems
		  ON  education_systems.id = education_levels.education_system_id
		  WHERE education_cycle_id in 
			(SELECT id FROM education_cycles WHERE education_level_id in 
				(SELECT id from education_levels WHERE education_system_id = $educSystemId)) $exclude");
		 
		 return $arr;
	   
	}
}
