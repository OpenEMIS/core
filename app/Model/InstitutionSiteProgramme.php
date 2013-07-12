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
	
	public function getProgrammeOptions($institutionSiteId, $yearId=null) {
		$conditions = array('InstitutionSiteProgramme.institution_site_id' => $institutionSiteId);
		
		if(!is_null($yearId)) {
			$conditions['InstitutionSiteProgramme.school_year_id'] = $yearId;
		}
		
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array('EducationProgramme.id', 'EducationCycle.name', 'EducationProgramme.name'),
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
				)
			),
			'conditions' => $conditions,
			'order' => array('EducationCycle.order', 'EducationProgramme.order')
		));
		
		$list = array();
		foreach($data as $obj) {
			$id = $obj['EducationProgramme']['id'];
			$cycle = $obj['EducationCycle']['name'];
			$programme = $obj['EducationProgramme']['name'];
			$list[$id] = $cycle . ' - ' . $programme;
		}
		return $list;
	}
	
	// used by InstitutionSiteController
	public function getSiteProgrammes($institutionSiteId, $yearId) {
		$this->formatResult = true;
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array(
				'InstitutionSiteProgramme.id',
				'EducationSystem.name AS education_system_name',
				'EducationCycle.name AS education_cycle_name',
				'EducationProgramme.id AS education_programme_id',
				'EducationProgramme.name AS education_programme_name'
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
					'table' => 'education_systems',
					'alias' => 'EducationSystem',
					'conditions' => array('EducationSystem.id = EducationLevel.education_system_id')
				)
			),
			'conditions' => array(
				'InstitutionSiteProgramme.institution_site_id' => $institutionSiteId,
				'InstitutionSiteProgramme.school_year_id' => $yearId
			),
			'order' => array('EducationSystem.order', 'EducationLevel.order', 'EducationCycle.order', 'EducationProgramme.order')
		));
		return $data;
	}
	
	public function getSiteProgrammeOptions($institutionSiteId, $yearId, $withCycle=true) {
		$data = array();
		if($withCycle) {
			$list = $this->getSiteProgrammes($institutionSiteId, $yearId);
			foreach($list as &$obj) {
				$data[$obj['education_programme_id']] = $obj['education_cycle_name'] . ' - ' . $obj['education_programme_name'];
			}
		} else {
			$data = $this->find('list', array(
				'recursive' => -1,
				'fields' => array('EducationProgramme.id AS education_programme_id', 'EducationProgramme.name AS education_programme_name'),
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
						'table' => 'education_systems',
						'alias' => 'EducationSystem',
						'conditions' => array('EducationSystem.id = EducationLevel.education_system_id')
					)
				),
				'conditions' => array(
					'InstitutionSiteProgramme.institution_site_id' => $institutionSiteId,
					'InstitutionSiteProgramme.school_year_id' => $yearId
				),
				'order' => array('EducationSystem.order', 'EducationLevel.order', 'EducationCycle.order', 'EducationProgramme.order')
			));
		}
		return $data;
	}
	
	// used by CensusController, classes/teachers
	public function getProgrammeList($institutionSiteId, $yearId, $withGrades = true) {
		$list = $this->getActiveProgrammes($institutionSiteId, $yearId);
		
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
	
	public function getActiveProgrammes($institutionSiteId, $yearId, $formatResult = false) {
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
					'conditions' => array(
						'EducationGrade.education_programme_id = EducationProgramme.id',
						'EducationGrade.visible = 1'
					)
				)
			),
			'conditions' => array(
				'InstitutionSiteProgramme.institution_site_id' => $institutionSiteId,
				'InstitutionSiteProgramme.school_year_id' => $yearId
			),
			'order' => array('EducationLevel.order', 'EducationCycle.order', 'EducationProgramme.order', 'EducationGrade.order')
		));
		
		return $data;
	}
	
	public function getProgrammeCountByInstitutionSite($institutionSiteId) {
		$count = $this->find('count', array(
			'conditions' => array('InstitutionSiteProgramme.institution_site_id' => $institutionSiteId)
		));
		
		return $count;
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

	// Required by Yearbook, summary by element and level
	public function calculateTotalSitesPerEducationCycle($year) {

		$startDate = mktime(0,0,0,1,1,$year);
		$endDate = mktime(0,0,0,12,31,$year);

		$this->unbindModel(array('belongsTo' => array('Institution')));

        $options['fields'] = array(
        	'EducationProgramme.education_cycle_id',
            'COUNT(InstitutionSite.id) as TotalInstitutionSites'
        );

        $options['group'] = array('EducationProgramme.education_cycle_id');
        $options['conditions'] = array(array('InstitutionSite.date_opened <=' => date('Y-m-d', $endDate)), 'NOT' => array('EducationProgramme.education_cycle_id' => NULL, 'InstitutionSite.date_closed' => NULL, 'InstitutionSite.date_closed !=' => "0000-00-00"));

		$values = $this->find('all', $options);
		$values = $this->formatArray($values);

		// massage data
		foreach ($values as $k => $v) {
			$eduCycleId = $v['education_cycle_id'];
			$data[$eduCycleId] = $v['TotalInstitutionSites'];
		}

		return $data;
	}

	// Required by Yearbook, schools per level and provides
	public function calculateTotalSchoolsPerLevel($year, $eduCycleId) {

		$startDate = mktime(0,0,0,1,1,$year);
		$endDate = mktime(0,0,0,12,31,$year);
		$this->bindModel(array('hasOne' => array(
			'InstitutionProvider' =>
            array(
                'className'              => 'InstitutionProvider',
                'joinTable'              => 'institution_providers',
				'foreignKey' => false,
				'dependent'    => false,
                'conditions' => array(' Institution.institution_provider_id = InstitutionProvider.id '),
            ),
            'EducationCycle' =>
            array(
                'className'              => 'EducationCycle',
                'joinTable'              => 'education_cycles',
				'foreignKey' => false,
				'dependent'    => false,
                'conditions' => array(' EducationProgramme.education_cycle_id = EducationCycle.id '),
            )
        )));

        $options['fields'] = array(
        	'InstitutionProvider.id as ProviderId',
        	'InstitutionProvider.name as ProviderName',
        	'EducationCycle.id as CycleId',
        	'EducationCycle.name as CycleName',
        	'COUNT(InstitutionSite.id) as TotalInstitutionSites'
        );

        $options['group'] = array('InstitutionProvider.id','EducationProgramme.education_cycle_id');
        // $options['conditions'] = array('AND' => array('EducationProgramme.education_cycle_id' => $eduCycleId, 'InstitutionSite.date_opened >=' => date('Y-m-d', $startDate), 'InstitutionSite.date_opened <=' => date('Y-m-d', $endDate)), 'NOT' => array('EducationProgramme.education_cycle_id'=>null));
        $options['conditions'] = array('AND' => array('EducationProgramme.education_cycle_id' => $eduCycleId, 'InstitutionSite.date_opened <=' => date('Y-m-d', $endDate)), 'NOT' => array('EducationProgramme.education_cycle_id'=>null, 'InstitutionSite.date_closed' => NULL, 'InstitutionSite.date_closed !=' => "0000-00-00"));
		$values = $this->find('all', $options);
		$values = $this->formatArray($values);

		return $values;
	}

}