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

class CensusVerification extends AppModel {
	public function getVerifications($institutionSiteId) {
		$data = $this->find('all', array(
			'fields' => array(
				'CensusVerification.*', 'SchoolYear.name',
				'SecurityUser.username', 'SecurityUser.first_name', 'SecurityUser.last_name'
			),
			'recursive' => -1,
			'joins' => array(
				array(
					'table' => 'security_users',
					'alias' => 'SecurityUser',
					'conditions' => array('SecurityUser.id = CensusVerification.created_user_id')
				),
				array(
					'table' => 'school_years',
					'alias' => 'SchoolYear',
					'conditions' => array('SchoolYear.id = CensusVerification.school_year_id')
				)
			),
			'conditions' => array('CensusVerification.institution_site_id' => $institutionSiteId),
			'order' => array('SchoolYear.name', 'CensusVerification.created')
		));
		return $data;
	}
	
	public function isEditable($institutionSiteId, $yearId) {
		$SchoolYear = ClassRegistry::init('SchoolYear');
		$yearList = $SchoolYear->getYearListForVerification($institutionSiteId);
		
		return array_key_exists($yearId, $yearList);
	}
	
	public function saveEntry($data) {
		$result = $this->find('all', array(
			'conditions' => array(
				'CensusVerification.institution_site_id' => $data['institution_site_id'],
				'CensusVerification.school_year_id' => $data['school_year_id']
			),
			'order' => array('CensusVerification.created DESC')
		));
		$insert = true;
		if(count($result) > 0) {
			$first = $result[0]['CensusVerification'];
			if(intval($data['status']) === intval($first['status'])) {
				$insert = false;
			}
		}
		if($insert) {
			$this->save($data);
		}
	}
}