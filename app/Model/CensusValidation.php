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

class CensusValidation extends AppModel {
	public function getValidations($institutionSiteId) {
		$data = $this->find('all', array(
			'fields' => array(
				'CensusValidation.*', 'SchoolYear.name',
				'SecurityUser.username', 'SecurityUser.first_name', 'SecurityUser.last_name'
			),
			'recursive' => -1,
			'joins' => array(
				array(
					'table' => 'security_users',
					'alias' => 'SecurityUser',
					'conditions' => array('SecurityUser.id = CensusValidation.created_user_id')
				),
				array(
					'table' => 'school_years',
					'alias' => 'SchoolYear',
					'conditions' => array('SchoolYear.id = CensusValidation.school_year_id')
				)
			),
			'order' => array('SchoolYear.name', 'CensusValidation.created')
		));
		return $data;
	}
	
	public function isEditable($institutionSiteId, $yearId) {
		$data = $this->find('first', array(
			'recursive' => -1,
			'conditions' => array(
				'CensusValidation.institution_site_id' => $institutionSiteId,
				'CensusValidation.school_year_id' => $yearId
			),
			'order' => array('CensusValidation.created DESC')
		));
		return !is_array($data);
	}
}