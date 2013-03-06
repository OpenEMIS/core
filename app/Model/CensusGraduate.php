<?php
App::uses('AppModel', 'Model');

class CensusGraduate extends AppModel {
	public $belongsTo = array(
		'SchoolYear' => array('foreignKey' => 'school_year_id'),
		'InstitutionSiteProgramme' => array('foreignKey' => 'institution_site_programme_id')
	);
	
	public function getCensusData($siteId, $yearId) {
		/* Actual SQL
		SELECT
			`education_levels`.`name` AS `education_level_name`,
			`education_programmes`.`name` AS `education_programme_name`,
			`education_certifications`.`name` AS `education_certification_name`,
			IFNULL(`census_graduates`.`id`, 0) AS `id`,
			`census_graduates`.`male` AS `male`,
			`census_graduates`.`female` AS `female`
		FROM `institution_site_programmes`
		JOIN `education_programmes` ON `education_programmes`.`id` = `institution_site_programmes`.`education_programme_id`
		JOIN `education_certifications` 
			ON `education_certifications`.`id` = `education_programmes`.`education_certification_id`
			AND `education_certifications`.`id` <> 1
		JOIN `education_cycles` ON `education_cycles`.`id` = `education_programmes`.`education_cycle_id`
		JOIN `education_levels` ON `education_levels`.`id` = `education_cycles`.`education_level_id`
		LEFT JOIN `census_graduates`
			ON `census_graduates`.`institution_site_programme_id` = `institution_site_programmes`.`id`
			AND `census_graduates`.`school_year_id` = %d
		WHERE `institution_site_programmes`.`institution_site_id` = %d
		ORDER BY `education_levels`.`order`, `education_cycles`.`order`, `education_programmes`.`order`
		*/
		
		$InstitutionSiteProgramme = ClassRegistry::init('InstitutionSiteProgramme');
		$InstitutionSiteProgramme->formatResult = true;
		$InstitutionSiteProgramme->unbindModel(array('belongsTo' => array('EducationProgramme')));
		$list = $InstitutionSiteProgramme->find('all' , array(
			'recursive' => 0,
			'fields' => array(
				'EducationLevel.name AS education_level_name',
				'EducationProgramme.name AS education_programme_name',
				'EducationCertification.id AS education_certification_id',
				'EducationCertification.name AS education_certification_name',
				'InstitutionSiteProgramme.id AS institution_site_programme_id',
				'CensusGraduate.id',
				'CensusGraduate.male',
				'CensusGraduate.female'
			),
			'joins' => array(
				array(
					'table' => 'education_programmes',
					'alias' => 'EducationProgramme',
					'type' => 'INNER',
					'conditions' => array('EducationProgramme.id = InstitutionSiteProgramme.education_programme_id')
				),
				array(
					'table' => 'education_certifications',
					'alias' => 'EducationCertification',
					'type' => 'INNER',
					'conditions' => array('EducationCertification.id = EducationProgramme.education_certification_id')
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
					'table' => 'census_graduates',
					'alias' => 'CensusGraduate',
					'type' => 'LEFT',
					'conditions' => array(
						'CensusGraduate.institution_site_programme_id = InstitutionSiteProgramme.id',
						'CensusGraduate.school_year_id = ' . $yearId
					)
				)
			),
			'conditions' => array('InstitutionSiteProgramme.institution_site_id' => $siteId),
			'order' => array('EducationLevel.order', 'EducationCycle.order', 'EducationProgramme.order')
		));
		$InstitutionSiteProgramme->bindModel(array('belongsTo' => array('EducationProgramme')));
		
		return $list;
	}
	
	public function saveCensusData($data) {
		$yearId = $data['school_year_id'];
		unset($data['school_year_id']);
		
		foreach($data as $obj) {
			$obj['school_year_id'] = $yearId;
			
			if($obj['male'] > 0 || $obj['female'] > 0) {
				if($obj['id'] == 0) {
					$this->create();
				}
				$save = $this->save(array('CensusGraduate' => $obj));
			} else if($obj['id'] > 0 && $obj['male'] == 0 && $obj['female'] == 0) {
				$this->delete($obj['id']);
			}
		}
	}
}
