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
		/* Actual SQL
		SELECT
			`staff_categories`.`id` AS `staff_category_id`,
			`staff_categories`.`name` AS `staff_category_name`,
			`staff_categories`.`visible` AS `staff_category_visible`,
			`census_staff`.`id` AS `id`,
			`census_staff`.`male` AS `male`,
			`census_staff`.`female` AS `female`
		FROM `staff_categories`
		LEFT JOIN `census_staff`
			ON `census_staff`.`staff_category_id` = `staff_categories`.`id`
			AND `census_staff`.`institution_site_id` = %d
			AND `census_staff`.`school_year_id` = %d
		ORDER BY `staff_categories`.`order`
		*/
		
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

	/**
	 * calculate the total number of staff in a particular area, Required by Yearbook
	 * @return int 	sum of staff
	 */
	public function calculateTotalStaffByAreaId($areaId, $schoolYearId) {

		$this->unbindModel(array('belongsTo' => array('SchoolYear')));
		$this->bindModel(
			array('belongsTo' =>
				array(
					'InstitutionSite',
					'InstitutionSiteProgramme' => array(
	                'joinTable'  => 'institution_sites',
					'foreignKey' => false,
	                'conditions' => array(' InstitutionSite.id = InstitutionSiteProgramme.institution_site_id '),
			    ))
			));

		$options['fields'] = array(
            'SUM(CensusStaff.male) as TotalMale',
            'SUM(CensusStaff.female) as TotalFemale',
            'SUM(CensusStaff.male + CensusStaff.female) as TotalStaff'
        );

		// $options['conditions'] = array('CensusStaff.school_year_id' => $schoolYearId);
        $options['conditions'] = array('AND' => array('CensusStaff.school_year_id' => $schoolYearId, 'InstitutionSite.area_id' => $areaId, 'NOT' => array('InstitutionSite.area_id' => null)));
		$values = $this->find('all', $options);
		$values = $this->formatArray($values);

		$data = ($values[0]['TotalStaff'] > 0) ? $values[0]['TotalStaff'] : 0;
		return $data;
	}
}