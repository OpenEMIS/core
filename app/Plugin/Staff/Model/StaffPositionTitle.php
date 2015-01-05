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

App::uses('FieldOptionValue', 'Model');

class StaffPositionTitle extends FieldOptionValue {
	public $useTable = 'field_option_values';
	public function getLookupVariables() {
		$lookup = array(
			'Categories' => array('model' => 'Staff.StaffPositionTitle')
		);
		return $lookup;
	}
	
	public function getInstitutionPositionTitles($institutionId){
		$list = $this->find('list' , array(
			'fields' => array('StaffPositionTitle.id', 'StaffPositionTitle.name'),
			'recursive' => -1,
			'joins' => array(
				array(
					'table' => 'institution_site_positions',
					'alias' => 'InstitutionSitePosition',
					'conditions' => array(
						'InstitutionSitePosition.staff_position_title_id = StaffPositionTitle.id',
						'InstitutionSitePosition.institution_site_id = ' . $institutionId
					)
				)
			),
			'conditions' => array('StaffPositionTitle.visible' => 1),
			'order' => array('StaffPositionTitle.order')
		));

		return $list;
	}
}
