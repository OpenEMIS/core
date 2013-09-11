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

class CensusStaff extends AppModel {
	public $useTable = 'census_staff';
	
	public $belongsTo = array(
		'SchoolYear'
	);
	
	public function getCensusData($siteId, $yearId) {
		$StaffCategory = ClassRegistry::init('Staff.StaffCategory');
		$StaffCategory->formatResult = true;
		$list = $StaffCategory->find('all' , array(
			'recursive' => 0,
			'fields' => array(
				'StaffCategory.id AS staff_category_id',
				'StaffCategory.name AS staff_category_name',
				'StaffCategory.visible AS staff_category_visible',
				'CensusStaff.id',
				'CensusStaff.male',
				'CensusStaff.female',
				'CensusStaff.source'
			),
			'joins' => array(
				array(
					'table' => 'census_staff',
					'alias' => 'CensusStaff',
					'type' => 'LEFT',
					'conditions' => array(
						'CensusStaff.staff_category_id = StaffCategory.id',
						'CensusStaff.institution_site_id = ' . $siteId,
						'CensusStaff.school_year_id = ' . $yearId
					)
				)
			),
			'order' => array('StaffCategory.order')
		));
		
		return $list;
	}
	
	public function saveCensusData($data, $institutionSiteId) {
		$yearId = $data['school_year_id'];
		unset($data['school_year_id']);
		
		foreach($data as $obj) {
			$obj['school_year_id'] = $yearId;
			$obj['institution_site_id'] = $institutionSiteId;
			
			if($obj['male'] > 0 || $obj['female'] > 0) {
				if($obj['id'] == 0) {
					$this->create();
				}
				$save = $this->save(array('CensusStaff' => $obj));
			} else if($obj['id'] > 0 && $obj['male'] == 0 && $obj['female'] == 0) {
				$this->delete($obj['id']);
			}
		}
	}
	
	//Used by Yearbook
	public function getCountByCycleId($yearId, $cycleId) {
		$this->formatResult = true;
		$data = $this->find('first', array(
			'recursive' => -1,
			'fields' => array('SUM(CensusStaff.male) AS M', 'SUM(CensusStaff.female) AS F'),		
			'joins' => array(
				array(
					'table' => 'institution_site_programmes',
					'alias' => 'InstitutionSiteProgramme',
					'conditions' => array(
						'InstitutionSiteProgramme.institution_site_id = CensusStaff.institution_site_id',
						'InstitutionSiteProgramme.school_year_id = CensusStaff.school_year_id'
					)
				),
				array(
					'table' => 'education_programmes',
					'alias' => 'EducationProgramme',
					'conditions' => array(
						'EducationProgramme.id = InstitutionSiteProgramme.education_programme_id',
						'EducationProgramme.education_cycle_id = ' . $cycleId
					)
				)
			),
			'conditions' => array('CensusStaff.school_year_id' => $yearId),
			'group' => array('EducationProgramme.education_cycle_id')
		));
		return $data;
	}
	
	public function getCountByAreaId($yearId, $areaId) {
		$this->formatResult = true;
		$data = $this->find('first', array(
			'recursive' => -1,
			'fields' => array('SUM(CensusStaff.male) AS M', 'SUM(CensusStaff.female) AS F'),
			'joins' => array(
				array(
					'table' => 'institution_sites',
					'alias' => 'InstitutionSite',
					'conditions' => array('InstitutionSite.id = CensusStaff.institution_site_id')
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
			'conditions' => array('CensusStaff.school_year_id' => $yearId)
		));
		return $data;
	}
}