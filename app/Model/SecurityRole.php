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

class SecurityRole extends AppModel {
	public $hasMany = array('SecurityRoleFunction');
	public $actsAs = array('Named');
	
	public function getGroupAdministratorRole() {
		$roleId = 1; // Role Id for Group Administrator is always 1
		$data = $this->find('first', array('SecurityRole.id' => $roleId));
		return $data;
	}
	
	public function getGroupName($roleId, $userId=false) {
		$this->formatResult = true;
		$joins = array(
			array(
				'table' => 'security_groups',
				'alias' => 'SecurityGroup',
				'conditions' => array('SecurityGroup.id = SecurityRole.security_group_id')
			)
		);
		
		if($userId != false) {
			$joins[] = array(
				'table' => 'security_group_users',
				'alias' => 'SecurityGroupUser',
				'conditions' => array(
					'SecurityGroupUser.security_group_id = SecurityGroup.id',
					'SecurityGroupUser.security_user_id = ' . $userId
				)
			);
		}
		
		$data = $this->find('first', array(
			'recursive' => -1,
			'fields' => array('SecurityGroup.id', 'SecurityGroup.name'),
			'joins' => $joins,
			'conditions' => array('SecurityRole.id' => $roleId)
		));
		return $data;
	}
	
	public function getRoles($groupId) {
		$this->formatResult = true;
		$data = $this->find('all', array(
			'recursive' => -1,
			'conditions' => array('SecurityRole.security_group_id' => $groupId),
			'order' => array('SecurityRole.order')
		));
		return $data;
	}
	
	public function getRoleOptions($groupId, $userId=false, $optGroup=false) {
		$this->formatResult = true;
		$fields = array('SecurityRole.id', 'SecurityRole.name');
		$conditions = array();
		$type = 'list';
		
		if($userId!==false) {
			$conditions = array(
				'OR' => array(
					'SecurityRole.security_group_id' => $groupId, 
					'SecurityRole.security_group_id <=' => 0
				),
				'AND' => array('SecurityRole.visible' => 1)
			);
			$conditions['AND'][] = sprintf('NOT EXISTS (
				SELECT id FROM security_group_users
				WHERE security_role_id = SecurityRole.id
				AND security_group_id = %d
				AND security_user_id = %d)', $groupId, $userId);
		} else {
			$conditions = array('SecurityRole.security_group_id' => $groupId, 'SecurityRole.visible' => 1);
		}
		
		if($optGroup) {
			$type = 'all';
			$fields[] = 'SecurityRole.security_group_id';
		}
		
		$list = $this->find($type, array(
			'recursive' => -1,
			'fields' => $fields,		
			'conditions' => $conditions,
			'order' => array('SecurityRole.order')
		));
		
		$data = array();
		if($optGroup) {
			$systemDefined = __('System Defined Roles');
			$userDefined = __('User Defined Roles');
			foreach($list as $obj) {
				$roleType = $obj['security_group_id'] > 0 ? $userDefined : $systemDefined;
				if(!array_key_exists($roleType, $data)) {
					$data[$roleType] = array();
				}
				$data[$roleType][$obj['id']] = $obj['name'];
			}
		} else {
			$data = $list;
		}
		return $data;
	}
        
        public function getRubricRoleOptions(){
            $this->formatResult = true;
            $options['fields'] = array('SecurityRole.id', 'SecurityRole.name');
            $options['conditions'] = array('SecurityRole.name' => array('QA Assessors','ECE Assessors'), 'SecurityRole.visible' => 1);
            
            $data = $this->find('list', $options);
            
            return $data;
        }
}
