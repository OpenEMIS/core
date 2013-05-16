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

class CensusTextbook extends AppModel {
	
	public $belongsTo = array(
		'SchoolYear',
		'EducationGradeSubject',
		'InstitutionSiteProgramme'
	);
	
	public function getCensusData($siteId, $yearId) {
		$InstitutionSiteProgramme = ClassRegistry::init('InstitutionSiteProgramme');
		$InstitutionSiteProgramme->formatResult = true;
		$list = $InstitutionSiteProgramme->find('all' , array(
			'recursive' => -1,
			'fields' => array(
				'InstitutionSiteProgramme.institution_site_id',
				'EducationCycle.name AS education_cycle_name',
				'EducationProgramme.name AS education_programme_name',
				'EducationGrade.id AS education_grade_id',
				'EducationGrade.name AS education_grade_name',
				'EducationSubject.id AS education_subject_id',
				'EducationSubject.name AS education_subject_name',
				'EducationGradeSubject.id AS education_grade_subject_id',			
				'CensusTextbook.id',
				'CensusTextbook.source',
				'CensusTextbook.value'
			),
			'joins' => array(
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
					'table' => 'education_grades_subjects',
					'alias' => 'EducationGradeSubject',
					'conditions' => array('EducationGradeSubject.education_grade_id = EducationGrade.id')
				),
				array(
					'table' => 'education_subjects',
					'alias' => 'EducationSubject',
					'conditions' => array('EducationSubject.id = EducationGradeSubject.education_subject_id')
				),
				array(
					'table' => 'census_textbooks',
					'alias' => 'CensusTextbook',
					'type' => 'LEFT',
					'conditions' => array(
						'CensusTextbook.institution_site_id = InstitutionSiteProgramme.institution_site_id',
						'CensusTextbook.education_grade_subject_id = EducationGradeSubject.id',
						'CensusTextbook.school_year_id = InstitutionSiteProgramme.school_year_id'
					)
				)
			),
			'conditions' => array(
				'InstitutionSiteProgramme.institution_site_id' => $siteId,
				'InstitutionSiteProgramme.school_year_id' => $yearId
			),
			'order' => array('EducationLevel.order', 'EducationCycle.order', 'EducationProgramme.order', 'EducationGrade.order', 'EducationSubject.order')
		));
		
		$data = array();
		foreach($list as $obj) {
			$name = $obj['education_cycle_name'] . ' - ' . $obj['education_programme_name'];
			if(!isset($name)) {
				$data[$name] = array();
			}
			
			$data[$name][] = array(
				'id' => $obj['id'],
				'education_grade_id' => $obj['education_grade_id'],
				'education_grade_name' => $obj['education_grade_name'],
				'education_subject_id' => $obj['education_subject_id'],
				'education_subject_name' => $obj['education_subject_name'],
				'institution_site_id' => $obj['institution_site_id'],
				'education_grade_subject_id' => $obj['education_grade_subject_id'],
				'source' => $obj['source'],
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
				if($obj['value'] > 0) {
					$this->create();
					$this->save(array('CensusTextbook' => $obj));
				}
			} else {
				$this->save(array('CensusTextbook' => $obj));
			}
		}
	}
}
