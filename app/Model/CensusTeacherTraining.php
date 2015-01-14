<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-16

OpenEMIS
Open Education Management Information System

Copyright © 2013 UNECSO.  This program is free software: you can redistribute it and/or modify 
it under the terms of the GNU General Public License as published by the Free Software Foundation
, either version 3 of the License, or any later version.  This program is distributed in the hope 
that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE.See the GNU General Public License for more details. You should 
have received a copy of the GNU General Public License along with this program.  If not, see 
<http://www.gnu.org/licenses/>.  For more information please wire to contact@openemis.org.
*/

App::uses('AppModel', 'Model');

class CensusTeacherTraining extends AppModel {
	public $useTable = "census_teacher_training";
	public $belongsTo = array(
		'InstitutionSite',
		'AcademicPeriod',
		'EducationLevel',
		'Gender' => array(
			'className' => 'FieldOptionValue',
			'foreignKey' => 'gender_id'
		)
	);
	
	public function getCensusData($institutionSiteId, $academicPeriodId) {
		/* Actual SQL
		SELECT
			`education_levels`.`id` AS `education_level_id`,
			`education_levels`.`name` AS `education_level_name`,
			`census_teacher_training`.`id`,
			`census_teacher_training`.`male` AS `male`,
			`census_teacher_training`.`female` AS `female`
		FROM `institution_site_programmes`
		LEFT JOIN `education_programmes`
			ON `education_programmes`.`id` = `institution_site_programmes`.`education_programme_id`
			AND `education_programmes`.`visible` = 1
		LEFT JOIN `education_cycles`
			ON `education_cycles`.`id` = `education_programmes`.`education_cycle_id`
			AND `education_cycles`.`visible` = 1
		LEFT JOIN `education_levels`
			ON `education_levels`.`id` = `education_cycles`.`education_level_id`
			AND `education_levels`.`visible` = 1
		LEFT JOIN `census_teacher_training`
			ON `census_teacher_training`.`education_level_id` = `education_levels`.`id`
			AND `census_teacher_training`.`institution_site_id` = `institution_site_programmes`.`institution_site_id`
			AND `census_teacher_training`.`academic_period_id` = %d
		WHERE `institution_site_programmes`.`institution_site_id` = %d
		GROUP BY `education_levels`.`id`
		ORDER BY `education_levels`.`order`
		*/
		
		$InstitutionSiteProgramme = ClassRegistry::init('InstitutionSiteProgramme');
		$InstitutionSiteProgramme->formatResult = true;
		$list = $InstitutionSiteProgramme->find('all' , array(
			'recursive' => -1,
			'fields' => array(
				'EducationLevel.id AS education_level_id',
				'EducationLevel.name AS education_level_name',
				'CensusTeacherTraining.id',
				'CensusTeacherTraining.source',
				'CensusTeacherTraining.gender_id',
				'CensusTeacherTraining.value'
			),
			'joins' => array(
				array(
					'table' => 'education_programmes',
					'alias' => 'EducationProgramme',
					'conditions' => array(
						'EducationProgramme.id = InstitutionSiteProgramme.education_programme_id',
						'EducationProgramme.visible = 1'
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
					'table' => 'census_teacher_training',
					'alias' => 'CensusTeacherTraining',
					'type' => 'LEFT',
					'conditions' => array(
						'CensusTeacherTraining.institution_site_id = InstitutionSiteProgramme.institution_site_id',
						'CensusTeacherTraining.education_level_id = EducationLevel.id',
						'CensusTeacherTraining.academic_period_id = ' . $academicPeriodId
					)
				)
			),
			'conditions' => array(
				'InstitutionSiteProgramme.institution_site_id' => $institutionSiteId,
				'InstitutionSiteProgramme.status' => 1
			),
			'order' => array('EducationLevel.order')
		));
		
		$data = array();
		foreach($list AS $row){
			$censusId = $row['id'];
			$eduLevelId = $row['education_level_id'];
			$genderId = $row['gender_id'];
			
			if(!empty($censusId) && !empty($genderId)){
				$data[$eduLevelId][$genderId] = array(
					'censusId' => $censusId,
					'value' => $row['value'],
					'source' => $row['source']
				);
			}
		}
		
		return $data;
	}
	
	public function saveCensusData($data, $academicPeriodId, $institutionSiteId) {
		foreach($data as $key => $obj) {
			$obj['academic_period_id'] = $academicPeriodId;
			$obj['institution_site_id'] = $institutionSiteId;
			if(empty($obj['id'])) {
				if($obj['value'] > 0) {
					$this->create();
					$this->save($obj);
				}
			} else {
				$this->save($obj);
			}
		}
	}
}
