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

class CensusStudent extends AppModel {
	public $actsAs = array('Containable');
	public $belongsTo = array(
		'SchoolYear' => array('foreignKey' => 'school_year_id'),
		'EducationGrade' => array('foreignKey' => 'education_grade_id'),
		'StudentCategory'=>array('foreignKey' => 'student_category_id'),
		'InstitutionSite' => array('foreignKey' => 'institution_site_id'),
		'Institution' =>
            array(
                'joinTable'  => 'institutions',
				'foreignKey' => false,
                'conditions' => array(' Institution.id = InstitutionSite.institution_id '),
            )
	);
	
	public function getCensusData($siteId, $yearId, $gradeId, $categoryId) {
		$this->formatResult = true;
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array('CensusStudent.id', 'CensusStudent.age', 'CensusStudent.male', 'CensusStudent.female', 'CensusStudent.source'),
			'joins' => array(
				array(
					'table' => 'education_grades',
					'alias' => 'EducationGrade',
					'conditions' => array(
						'EducationGrade.id = CensusStudent.education_grade_id'
					)
				),
				array(
					'table' => 'education_programmes',
					'alias' => 'EducationProgramme',
					'conditions' => array(
						'EducationProgramme.id = EducationGrade.education_programme_id'
					)
				),
				array(
					'table' => 'education_cycles',
					'alias' => 'EducationCycle',
					'conditions' => array(
						'EducationCycle.id = EducationProgramme.education_cycle_id'
					)
				),
				array(
					'table' => 'education_levels',
					'alias' => 'EducationLevel',
					'conditions' => array(
						'EducationLevel.id = EducationCycle.education_level_id'
					)
				)
			),
			'conditions' => array(
				'CensusStudent.education_grade_id' => $gradeId,
				'CensusStudent.school_year_id' => $yearId,
				'CensusStudent.student_category_id' => $categoryId,
				'CensusStudent.institution_site_id' => $siteId
			),
			'order' => array('EducationLevel.order', 'EducationCycle.order', 'EducationProgramme.order', 'CensusStudent.age')
		));
		return $data;
	}
	
	public function saveCensusData($data, $institutionSiteId,$source=0) {
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
				$row['institution_site_id'] = $institutionSiteId;
				$row['source'] = $source;
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
	
	//Used by Yearbook
	public function getCountByCycleId($yearId, $cycleId, $extras=array()) {
		$this->formatResult = true;
		$options = array('recursive' => -1, 'fields' => array('SUM(CensusStudent.male) AS M', 'SUM(CensusStudent.female) AS F'));
		$joins = array(
			array(
				'table' => 'education_grades',
				'alias' => 'EducationGrade',
				'conditions' => array('EducationGrade.id = CensusStudent.education_grade_id')
			),
			array(
				'table' => 'education_programmes',
				'alias' => 'EducationProgramme',
				'conditions' => array(
					'EducationProgramme.id = EducationGrade.education_programme_id',
					'EducationProgramme.education_cycle_id = ' . $cycleId
				)
			)
		);
		
		if(isset($extras['areaId'])) {
			$joins[] = array(
				'table' => 'institution_sites',
				'alias' => 'InstitutionSite',
				'conditions' => array('InstitutionSite.id = CensusStudent.institution_site_id')
			);
			$joins[] = array(
				'table' => 'areas',
				'alias' => 'AreaSite',
				'conditions' => array('AreaSite.id = InstitutionSite.area_id')
			);
			$joins[] = array(
				'table' => 'areas',
				'alias' => 'Area',
				'conditions' => array(
					'Area.id = ' . $extras['areaId'],
					'Area.lft <= AreaSite.lft',
					'Area.rght >= AreaSite.rght'
				)
			);
		}
		if(isset($extras['providerId'])) {
			$joins[] = array(
				'table' => 'institution_sites',
				'alias' => 'InstitutionSite',
				'conditions' => array('InstitutionSite.id = CensusStudent.institution_site_id')
			);
			$joins[] = array(
				'table' => 'institutions',
				'alias' => 'Institution',
				'conditions' => array(
					'Institution.id = InstitutionSite.institution_id',
					'Institution.institution_provider_id = ' . $extras['providerId']
				)
			);
		}
		$options['joins'] = $joins;
		$options['conditions'] = array('CensusStudent.school_year_id' => $yearId);
		$options['group'] = array('EducationProgramme.education_cycle_id');
		$data = $this->find('first', $options);
		return $data;
	}
	
	public function getCountByAreaId($yearId, $areaId) {
		$this->formatResult = true;
		$data = $this->find('first', array(
			'recursive' => -1,
			'fields' => array('SUM(CensusStudent.male) AS M', 'SUM(CensusStudent.female) AS F'),
			'joins' => array(
				array(
					'table' => 'institution_sites',
					'alias' => 'InstitutionSite',
					'conditions' => array('InstitutionSite.id = CensusStudent.institution_site_id')
				),
				array(
					'table' => 'areas',
					'alias' => 'AreaSite',
					'conditions' => array('AreaSite.id = InstitutionSite.area_id')
				),
				array(
					'table' => 'areas',
					'alias' => 'Area',
					'conditions' => array(
						'Area.id = ' . $areaId,
						'Area.lft <= AreaSite.lft',
						'Area.rght >= AreaSite.rght'
					)
				)
			),
			'conditions' => array('CensusStudent.school_year_id' => $yearId)
		));
		return $data;
	}
	// End Yearbook
}