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

class SecurityFunction extends AppModel {
	public $hasMany = array('SecurityRoleFunction');
	public $operations = array('_view', '_edit', '_add', '_delete', '_execute');
	public $rootAccessModules = array('List of Users', 'Users', 'List of Groups', 'Groups', 'Group Users', 'Roles');
	
	public function getFunctions() {
		$functions = $this->find('all', array('conditions' => array('visible' => 1)));
		
		$list = array();
		foreach($functions as $func) {
			$obj = $func['SecurityFunction'];
			$module = $obj['module'];
			
			if(!isset($list[$module])) {
				$list[$module] = array();
			}
			$list[$module][] = $obj;
		}
		
		return $list;
	}
	
	public function arrange($list, $isSuperUser=false) {
		$operations = $this->operations;
		$data = array();
		foreach($list as $obj) {
			$function = $obj['SecurityFunction'];
			$roleFunction = $obj['SecurityRoleFunction'];
			$operationObj = array_key_exists('_view', $roleFunction) ? $roleFunction : $obj[0];
			$module = $function['module'];
			
			// if super user then show all permissions, if not super user then only show restricted permissions
			if($isSuperUser || ($isSuperUser == false && !in_array($function['name'], $this->rootAccessModules))) {
				if(!isset($data[$module])) {
					$data[$module] = array('enabled' => false);
				}
				$row = array();
				foreach($operations as $op) {
					if(!is_null($function[$op])) {
						$row[$op] = is_null($operationObj[$op]) ? 0 : $operationObj[$op];
						if($row[$op]==1 && $function['visible'] == 1 && $data[$module]['enabled'] == false) { // for enabling the module in view
							$data[$module]['enabled'] = true;
						}
					} else {
						$row[$op] = NULL;
					}
				}
				$row['id'] = $roleFunction['id'];
				$row['security_function_id'] = $function['id'];
				$row['name'] = $function['name'];
				$row['visible'] = $function['visible'];
				$row['parent_id'] = $function['parent_id'];
				$data[$module][$function['id']] = $row;
			}
		}//pr($data);
		return $data;
	}
	
	public function getPermissions($roleId, $isSuperUser=false) {
		$list = $this->find('all', array(
			'recursive' => -1,
			'fields' => array('SecurityFunction.*', 'SecurityRoleFunction.*'),
			'joins' => array(
				array(
					'table' => 'security_role_functions',
					'alias' => 'SecurityRoleFunction',
					'type' => 'LEFT',
					'conditions' => array(
						'SecurityRoleFunction.security_function_id = SecurityFunction.id',
						'SecurityRoleFunction.security_role_id = ' . $roleId
					)
				)
			),
			'order' => array('SecurityFunction.order')
		));
		return $this->arrange($list, $isSuperUser);
	}
        
        public function getUserPermissions($userId, $arrange = false) {
		$list = $this->find('all', array(
			'recursive' => -1,
			'fields' => array(
				'SecurityFunction.*',
				'SecurityRole.visible',
                                'SecurityRole.id',
				'SecurityRoleFunction.id',
				'SecurityRoleFunction._view AS _view',
				'SecurityRoleFunction._edit AS _edit',
				'SecurityRoleFunction._add AS _add',
				'SecurityRoleFunction._delete AS _delete',
				'SecurityRoleFunction._execute AS _execute'
			),
			'joins' => array(
				array(
					'table' => 'security_role_functions',
					'alias' => 'SecurityRoleFunction',
					'conditions' => array('SecurityRoleFunction.security_function_id = SecurityFunction.id')
				),
				array(
					'table' => 'security_roles',
					'alias' => 'SecurityRole',
					'conditions' => array('SecurityRole.id = SecurityRoleFunction.security_role_id')
				),
				array(
					'table' => 'security_group_users',
					'alias' => 'SecurityGroupUser',
					'conditions' => array(
						'SecurityGroupUser.security_role_id = SecurityRoleFunction.security_role_id',
						'SecurityGroupUser.security_user_id = ' . $userId
					)
				)
			),
//			'group' => array('SecurityFunction.id'),
			'order' => array('SecurityFunction.order')
		));
		return $arrange ? $this->arrange($list) : $list;
	}
	
	public function getAllowedPermissions($roleId, $userId, $isSuperUser) {
		$operations = $this->operations;
		$permissions = $this->getPermissions($roleId, $isSuperUser);//pr($permissions);
		$userPermissions = $this->getUserPermissions($userId, true);
		//pr($userPermissions);
		foreach($permissions as $module => &$functions) {//pr($functions);pr($userPermissions[$module]);die;
			foreach($functions as $key => &$data) {
				if($key == 'enabled') continue;
				
				if(isset($userPermissions[$module])) { // if module exists in user's permissions
					if(isset($userPermissions[$module][$key])) { // if function exists in user's permissions
						//pr($userPermissions[$module][$key]);
						$userData = $userPermissions[$module][$key];
						foreach($operations as $op) {
							if($userData[$op]==0) {
								if($data[$op] == 1) {
									$data[$op] = 2; // checked but disabled
								} else {
									$data[$op] = null;
								}
							}/* else if($userData[$op] == 1) {
								
							}*/
						}
					}
				} else {
					//pr($module);
				}
				//pr(isset($userPermissions[$module][$key]['_view']));
			}
		}
		return $permissions;
	}
}