<?php
App::uses('AppModel', 'Model');

class CensusTextbook extends AppModel {
	
	public $belongsTo = array(
		'SchoolYear',
		'EducationGradeSubject',
		'InstitutionSiteProgramme'
	);
	
	public function getCensusData($siteId, $yearId) {
		/* Actual SQL
		SELECT
			IFNULL(`census_textbooks`.`id`, 0) AS `id`,
			`institution_site_programmes`.`id` AS `institution_site_programme_id`,
			`education_programmes`.`name` AS `programme_name`,
			`education_grades`.`id` AS `education_grade_id`,
			`education_grades`.`name` AS `grade_name`,
			`education_subjects`.`id` AS `education_subject_id`,
			`education_subjects`.`name` AS `subject_name`,
			`education_grades_subjects`.`id` AS `education_grade_subject_id`,
			IFNULL(`census_textbooks`.`value`, 0) AS `total`
		FROM `institution_site_programmes`
		JOIN `education_programmes` 
			ON `education_programmes`.`id` = `institution_site_programmes`.`education_programme_id`
		JOIN `education_grades`
			ON `education_grades`.`education_programme_id` = `education_programmes`.`id`
		JOIN `education_grades_subjects`
			ON `education_grades_subjects`.`education_grade_id` = `education_grades`.`id`
		JOIN `education_subjects`
			ON `education_subjects`.`id` = `education_grades_subjects`.`education_subject_id`
		LEFT JOIN `census_textbooks`
			ON `census_textbooks`.`institution_site_programme_id` = `institution_site_programmes`.`id`
			AND `census_textbooks`.`education_grade_subject_id` = `education_grades_subjects`.`id`
			AND `census_textbooks`.`school_year_id` = %d
		WHERE `institution_site_programmes`.`institution_site_id` = %d
		*/
		
		$InstitutionSiteProgramme = ClassRegistry::init('InstitutionSiteProgramme');
		$InstitutionSiteProgramme->formatResult = true;
		$InstitutionSiteProgramme->unbindModel(array('belongsTo' => array('EducationProgramme')));
		$list = $InstitutionSiteProgramme->find('all' , array(
			'recursive' => 0,
			'fields' => array(
				'InstitutionSiteProgramme.id AS institution_site_programme_id',
				'EducationProgramme.name AS education_programme_name',
				'EducationGrade.id AS education_grade_id',
				'EducationGrade.name AS education_grade_name',
				'EducationSubject.id AS education_subject_id',
				'EducationSubject.name AS education_subject_name',
				'EducationGradeSubject.id AS education_grade_subject_id',			
				'CensusTextbook.id',
				'CensusTextbook.value'
			),
			'joins' => array(
				array(
					'table' => 'education_programmes',
					'alias' => 'EducationProgramme',
					'type' => 'INNER',
					'conditions' => array('EducationProgramme.id = InstitutionSiteProgramme.education_programme_id')
				),
				array(
					'table' => 'education_cycles',
					'alias' => 'EducationCycle',
					'type' => 'INNER',
					'conditions' => array('EducationCycle.id = EducationProgramme.education_cycle_id')
				),
				array(
					'table' => 'education_levels',
					'alias' => 'EducationLevel',
					'type' => 'INNER',
					'conditions' => array('EducationLevel.id = EducationCycle.education_level_id')
				),
				array(
					'table' => 'education_grades',
					'alias' => 'EducationGrade',
					'type' => 'INNER',
					'conditions' => array('EducationGrade.education_programme_id = EducationProgramme.id')
				),
				array(
					'table' => 'education_grades_subjects',
					'alias' => 'EducationGradeSubject',
					'type' => 'INNER',
					'conditions' => array('EducationGradeSubject.education_grade_id = EducationGrade.id')
				),
				array(
					'table' => 'education_subjects',
					'alias' => 'EducationSubject',
					'type' => 'INNER',
					'conditions' => array('EducationSubject.id = EducationGradeSubject.education_subject_id')
				),
				array(
					'table' => 'census_textbooks',
					'alias' => 'CensusTextbook',
					'type' => 'LEFT',
					'conditions' => array(
						'CensusTextbook.institution_site_programme_id = InstitutionSiteProgramme.id',
						'CensusTextbook.education_grade_subject_id = EducationGradeSubject.id',
						'CensusTextbook.school_year_id = ' . $yearId
					)
				)
			),
			'conditions' => array('InstitutionSiteProgramme.institution_site_id' => $siteId),
			'order' => array('EducationLevel.order', 'EducationCycle.order', 'EducationProgramme.order', 'EducationGrade.order', 'EducationSubject.order')
		));
		$InstitutionSiteProgramme->bindModel(array('belongsTo' => array('EducationProgramme')));
		
		$data = array();
		foreach($list as $obj) {
			if(!isset($obj['education_programme_name'])) {
				$data[$obj['education_programme_name']] = array();
			}
			
			$data[$obj['education_programme_name']][] = array(
				'id' => $obj['id'],
				'education_grade_id' => $obj['education_grade_id'],
				'education_grade_name' => $obj['education_grade_name'],
				'education_subject_id' => $obj['education_subject_id'],
				'education_subject_name' => $obj['education_subject_name'],
				'institution_site_programme_id' => $obj['institution_site_programme_id'],
				'education_grade_subject_id' => $obj['education_grade_subject_id'],
				'total' => is_null($obj['value']) ? 0 : $obj['value']
			);
		}
		
		return $data;
	}
	
	public function saveCensusData($data) {
		$yearId = $data['school_year_id'];
		unset($data['school_year_id']);
		
		foreach($data as $obj) {
			$obj['school_year_id'] = $yearId;
			if($obj['id'] == 0) {
				$this->create();
			}
			$this->save(array('CensusTextbook' => $obj));
		}
	}
}
