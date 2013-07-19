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

class SecurityGroupUser extends AppModel {
	public function getUsers($groupId) {
		$roles = $this->find('all', array(
			'recursive' => -1,
			'fields' => array('SecurityRole.*', 'SecurityUser.id', 'SecurityUser.identification_no', 'SecurityUser.first_name', 'SecurityUser.last_name'),		
			'joins' => array(
				array(
					'table' => 'security_roles',
					'alias' => 'SecurityRole',
					'conditions' => array('SecurityRole.id = SecurityGroupUser.security_role_id')
				),
				array(
					'table' => 'security_users',
					'alias' => 'SecurityUser',
					'conditions' => array('SecurityUser.id = SecurityGroupUser.security_user_id')
				)
			),
			'conditions' => array('SecurityGroupUser.security_group_id' => $groupId),
			'order' => array('SecurityRole.security_group_id', 'SecurityRole.order', 'SecurityUser.first_name')
		));
		
		$data = array();
		foreach($roles as $obj) {
			$role = $obj['SecurityRole'];
			$roleId = $role['id'];
			if(!array_key_exists($roleId, $data)) {
				$data[$roleId] = array('name' => $role['name'], 'users' => array());
			}
			$data[$roleId]['users'][] = $obj['SecurityUser'];
		}
		return $data;
	}
	
	public function getGroupIdsByUserId($userId) {
		$data = $this->find('list', array(
			'fields' => array('SecurityGroupUser.security_group_id', 'SecurityGroupUser.security_group_id'),
			'conditions' => array('SecurityGroupUser.security_user_id' => $userId)
		));
		return $data;
	}
	
	public function getGroupsByUserId($userId) {
		$this->formatResult = true;
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array('SecurityGroup.name AS security_group_name', 'SecurityRole.name AS security_role_name'),
			'joins' => array(
				array(
					'table' => 'security_groups',
					'alias' => 'SecurityGroup',
					'conditions' => array('SecurityGroup.id = SecurityGroupUser.security_group_id')
				),
				array(
					'table' => 'security_roles',
					'alias' => 'SecurityRole',
					'conditions' => array('SecurityRole.id = SecurityGroupUser.security_role_id')
				)
			),
			'conditions' => array('SecurityGroupUser.security_user_id' => $userId),
			'order' => array('SecurityGroup.name', 'SecurityRole.order')
		));
		return $data;
	}
	
	public function getRolesByUserId($userId) {
		$data = $this->find('all', array(
			'fields' => array('SecurityRole.*'),
			'joins' => array(
				array(
					'table' => 'security_roles',
					'alias' => 'SecurityRole',
					'conditions' => array('SecurityRole.id = SecurityGroupUser.security_role_id')
				)
			),
			'conditions' => array('SecurityGroupUser.security_user_id' => $userId)
		));
		return $data;
	}
}