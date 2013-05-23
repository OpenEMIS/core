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

class SecurityRoleInstitutionSite extends AppModel {
	public $belongsTo = array('SecurityRole', 'InstitutionSite');
	
	public function filterData(&$data) {
		$tmpData = $data;
		foreach($tmpData as $key => $obj) {
			if($obj['institution_site_id']==0) {
				unset($data[$key]);
			}
		}
	}
	
	public function fetchSites($institutionList, $conditions) {
		$this->formatResult = true;
		$list = $this->find('all', array(
			'fields' => array('SecurityRoleInstitutionSite.institution_site_id', 'InstitutionSite.name', 'InstitutionSite.institution_id'),
			'conditions' => $conditions,
			'order' => array('InstitutionSite.name')
		));
		
		$sortList = array();
		foreach($list as $key => &$obj) {
			$name = $institutionList[$obj['institution_id']];
			$obj['institution_name'] = $name;
			$sortList[$name . $key] = $obj;
		}
		ksort($sortList);
		
		return array_values($sortList);
	}
	
	public function findSitesByRoles($roleIds) {
		$institutions = array();
		$list = $this->find('all', array(
			'fields' => array('InstitutionSite.id', 'InstitutionSite.institution_id'),
			'conditions' => array('SecurityRoleInstitutionSite.security_role_id' => $roleIds)
		));
		foreach($list as $obj) {
			$site = $obj['InstitutionSite'];
			$institutionId = $site['institution_id'];
			if(!isset($institutions[$institutionId])) {
				$institutions[$institutionId] = array();
			}
			$institutions[$institutionId][] = $site['id'];
		}
		return $institutions;
	}
        
        public function addInstitutionSitetoRole($arrSettings){
                if($arrSettings['security_role_id'] > 0 && $arrSettings['institution_site_id'] > 0){
                    $records = $this->find('all',array('conditions'=> $arrSettings)); //check if there's an Existing Site to a role
                    if(count($records) == 0){
                       $this->save($arrSettings);
                    }
                }
        }
}
