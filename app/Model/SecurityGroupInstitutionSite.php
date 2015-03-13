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

class SecurityGroupInstitutionSite extends AppModel {
	public $belongsTo = array(
		'SecurityGroup',
		'InstitutionSite'
	);
	
	public function autocomplete($search, $exclude=array(), $conditions=array()) {
		$conditions = array_merge(array(
			'OR' => array(
				'InstitutionSite.code LIKE' => $search,
				'InstitutionSite.name LIKE' => $search
			),
			'InstitutionSite.id NOT' => $exclude
		), $conditions);
		
		$isSuperUser = CakeSession::read('Auth.User.super_admin') == 1;
		if(!$isSuperUser){
			$userId = CakeSession::read('Auth.User.id');
			$userInstitutionSites = ClassRegistry::init('SecurityGroupUser')->getUserInstitutionSites($userId);
			$institutionSiteIds = array();
			foreach($userInstitutionSites as $row){
				$siteId = $row['InstitutionSite']['id'];
				$institutionSiteIds[$siteId] = $siteId;
			}
		}
		
		$conditions = array(
			'OR' => array(
				$conditions,
				array(
					'InstitutionSite.id' => $institutionSiteIds
				)
			)
		);
		
		$this->InstitutionSite->contain('Area');
		$list = $this->InstitutionSite->find('all', array(
			'fields' => array('InstitutionSite.id', 'InstitutionSite.code', 'InstitutionSite.name'),
			'conditions' => $conditions,
			'order' => array('InstitutionSite.name')
		));
		
		$data = array();
		foreach($list as $obj) {
			$site = $obj['InstitutionSite'];
			$data[] = array(
				'label' => sprintf('%s - %s', $site['code'], $site['name']),
				'value' => array('value-id' => $site['id'], 'site-name' => $site['name'], 'site-code' => $site['code'])
			);
		}
		return $data;
	}
	
	public function getSites($groupId) {
		$this->formatResult = true;
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array('InstitutionSite.id AS institution_site_id', 'InstitutionSite.name AS institution_site_name'),
			'joins' => array(
				array(
					'table' => 'institution_sites',
					'alias' => 'InstitutionSite',
					'conditions' => array('InstitutionSite.id = SecurityGroupInstitutionSite.institution_site_id')
				)
			),
			'conditions' => array('SecurityGroupInstitutionSite.security_group_id' => $groupId),
			'order' => array('InstitutionSite.name')
		));
		return $data;
	}
	
	public function fetchSites($institutionList, $conditions) {
		$this->formatResult = true;
		
		$this->unbindModel(array('belongsTo' => array('InstitutionSite')));
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
	
	public function findSitesByUserId($userId) {
		$data = array();
		$list = $this->find('all', array(
			'recursive' => -1,
			'fields' => array('InstitutionSite.id', 'InstitutionSite.institution_id'),
			'joins' => array(
				array(
					'table' => 'security_group_users',
					'alias' => 'SecurityGroupUser',
					'conditions' => array(
						'SecurityGroupUser.security_group_id = SecurityGroupInstitutionSite.security_group_id',
						'SecurityGroupUser.security_user_id = ' . $userId
					)
				),
				array(
					'table' => 'institution_sites',
					'alias' => 'InstitutionSite',
					'conditions' => array('InstitutionSite.id = SecurityGroupInstitutionSite.institution_site_id')
				)
			)
		));
		
		foreach($list as $obj) {
			$site = $obj['InstitutionSite'];
			$institutionId = $site['institution_id'];
			if(!isset($data[$institutionId])) {
				$data[$institutionId] = array();
			}
			$data[$institutionId][] = $site['id'];
		}
		return $data;
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
