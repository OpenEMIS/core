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

class EducationProgramme extends AppModel {
	/*
	public $validate = array(
		'name' => array(
			'rule' => 'notEmpty',
			'required' => true,
			'message' => 'Please enter a name for the programme.'
		),
		'education_field_of_study_id' => array(
			'rule' => 'notEmpty',
			'required' => true,
			'message' => 'Please choose a field of study.'
		),
		'education_certification_id' => array(
			'rule' => 'notEmpty',
			'required' => true,
			'message' => 'Please select a certification.'
		),
		'duration' => array(
			'rule' => 'notEmpty',
			'required' => true,
			'message' => 'Please enter the duration.'
		)
	);
	*/
	
	public $belongsTo = array('EducationCycle', 'EducationFieldOfStudy', 'EducationCertification');
	public $hasMany = array('EducationGrade', 'InstitutionSiteProgramme');
	
	public function getDurationBySiteProgramme($siteProgrammeId) {
		$obj = $this->find('first', array(
			'recursive' => -1,
			'joins' => array(
				array(
					'table' => 'institution_site_programmes',
					'alias' => 'InstitutionSiteProgramme',
					'conditions' => array(
						'InstitutionSiteProgramme.education_programme_id = EducationProgramme.id',
						'InstitutionSiteProgramme.id = ' . $siteProgrammeId
					)
				)
			)
		));
		return $obj['EducationProgramme']['duration'];
	}
	
	// Used by InstitutionSiteController->programmeAdd
	public function getAvailableProgrammeOptions($institutionSiteId, $yearId) {
		$table = 'institution_site_programmes';
		$notExists = 'NOT EXISTS (SELECT %s.id FROM %s WHERE %s.institution_site_id = %d AND %s.school_year_id = %d AND %s.education_programme_id = EducationProgramme.id)';
		
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array(
				'EducationSystem.name', 'EducationLevel.name', 
				'EducationCycle.name', 'EducationProgramme.id', 'EducationProgramme.name'
			),
			'joins' => array(
				array(
					'table' => 'education_cycles',
					'alias' => 'EducationCycle',
					'conditions' => array('EducationCycle.id = EducationProgramme.education_cycle_id', 'EducationCycle.visible = 1')
				),
				array(
					'table' => 'education_levels',
					'alias' => 'EducationLevel',
					'conditions' => array('EducationLevel.id = EducationCycle.education_level_id', 'EducationLevel.visible = 1')
				),
				array(
					'table' => 'education_systems',
					'alias' => 'EducationSystem',
					'conditions' => array('EducationSystem.id = EducationLevel.education_system_id', 'EducationSystem.visible = 1')
				)
			),
			'conditions' => array(
				sprintf($notExists, $table, $table, $table, $institutionSiteId, $table, $yearId, $table),
				'EducationProgramme.visible' => 1
			),
			'order' => array('EducationSystem.order', 'EducationLevel.order', 'EducationCycle.order', 'EducationProgramme.order')
		));
		return $data;
	}
	
	// Used by Assessment
	public function getProgrammeOptions($visible = true, $cycleName = true) {
		$conditions = array();
		$cycleConditions = array('EducationCycle.id = EducationProgramme.education_cycle_id');
		$levelConditions = array('EducationLevel.id = EducationCycle.education_level_id');
		$systemConditions = array('EducationSystem.id = EducationLevel.education_system_id');
		if($visible) {
			$conditions['EducationProgramme.visible'] = 1;
			$cycleConditions[] = 'EducationCycle.visible = 1';
			$levelConditions[] = 'EducationLevel.visible = 1';
			$systemConditions[] = 'EducationSystem.visible = 1';
		}
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array('EducationProgramme.id', 'EducationProgramme.name', 'EducationCycle.name'),
			'joins' => array(
				array(
					'table' => 'education_cycles',
					'alias' => 'EducationCycle',
					'conditions' => $cycleConditions
				),
				array(
					'table' => 'education_levels',
					'alias' => 'EducationLevel',
					'conditions' => $levelConditions
				),
				array(
					'table' => 'education_systems',
					'alias' => 'EducationSystem',
					'conditions' => $systemConditions
				)
			),
			'conditions' => $conditions,
			'order' => array('EducationSystem.order', 'EducationLevel.order', 'EducationCycle.order', 'EducationLevel.order')
		));
		
		$options = array();
		foreach($data as $obj) {
			$programme = $obj['EducationProgramme'];
			$cycle = $obj['EducationCycle'];
			if($cycleName) {
				$options[$programme['id']] = $cycle['name'] . ' - ' . $programme['name'];
			} else {
				$options[$programme['id']] = $programme['name'];
			}
		}
		return $options;
	}
	
	// Used by Yearbook
	public function getEducationStructure() {
		$list = $this->find('all', array(
			'fields' => array(
				'EducationSystem.id', 'EducationSystem.name', 'EducationLevel.name', 
				'EducationCycle.name', 'EducationProgramme.id', 'EducationProgramme.name', 
				'EducationFieldOfStudy.name', 'EducationProgrammeOrientation.name', 'EducationCertification.name'
			),
			'recursive' => -1,
			'joins' => array(
				array('table' => 'education_cycles', 'alias' => 'EducationCycle', 
					'conditions' => array('EducationCycle.id = EducationProgramme.education_cycle_id')),
				array('table' => 'education_levels', 'alias' => 'EducationLevel', 
					'conditions' => array('EducationLevel.id = EducationCycle.education_level_id')),
				array('table' => 'education_systems', 'alias' => 'EducationSystem', 
					'conditions' => array('EducationSystem.id = EducationLevel.education_system_id')),
				array('table' => 'education_field_of_studies', 'alias' => 'EducationFieldOfStudy', 
					'conditions' => array('EducationFieldOfStudy.id = EducationProgramme.education_field_of_study_id')),
				array('table' => 'education_programme_orientations', 'alias' => 'EducationProgrammeOrientation', 
					'conditions' => array('EducationProgrammeOrientation.id = EducationFieldOfStudy.education_programme_orientation_id')),
				array('table' => 'education_certifications', 'alias' => 'EducationCertification', 
					'conditions' => array('EducationCertification.id = EducationProgramme.education_certification_id'))
			),
			'order' => array('EducationSystem.order', 'EducationLevel.order', 'EducationCycle.order', 'EducationProgramme.order')
		));
		$EducationGrade = ClassRegistry::init('EducationGrade');
		$data = array();
		foreach($list as $item) {
			$system = $item['EducationSystem'];
			if(!array_key_exists($system['id'], $data)) {
				$data[$system['id']] = array();
			}
			$grades = $EducationGrade->getGradeOptions($item['EducationProgramme']['id'], null, true);
			$data[$system['id']][] = array(
				'system' => $system['name'],
				'level' => $item['EducationLevel']['name'],
				'cycle' => $item['EducationCycle']['name'],
				'programme' => $item['EducationProgramme']['name'],
				'field' => $item['EducationFieldOfStudy']['name'],
				'orientation' => $item['EducationProgrammeOrientation']['name'],
				'certification' => $item['EducationCertification']['name'],
				'grades' => $grades
			);
		}
		return $data;
	}
        
        public function getProgrammeById($programmeId) {
		$this->formatResult = true;
		$data = $this->find('first', array(
			'recursive' => -1,
			'fields' => array(
				'EducationSystem.name AS education_system_name',
				'EducationCycle.name AS education_cycle_name',
                                'EducationCycle.admission_age AS admission_age',
				'EducationProgramme.name AS education_programme_name'
			),
			'joins' => array(
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
					'table' => 'education_systems',
					'alias' => 'EducationSystem',
					'conditions' => array('EducationSystem.id = EducationLevel.education_system_id')
				)
			),
			'conditions' => array(
				'EducationProgramme.id' => $programmeId
			)
		));
		return $data;
	}
}
