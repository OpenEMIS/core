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
	
	public function getPermissions($roleId, $operations) {
		$this->unbindModel(array('hasMany' => array('SecurityRoleFunction')));
		$list = $this->find('all', array(
			'fields' => array(
				'SecurityFunction.id', 'SecurityFunction.module', 'SecurityFunction.name', 'SecurityFunction._view', 
				'SecurityFunction._edit', 'SecurityFunction._add', 'SecurityFunction._delete', 'SecurityFunction._execute', 'SecurityFunction.visible',
				'SecurityFunction.parent_id',
				'SecurityRoleFunction.id', 'SecurityRoleFunction._view', 'SecurityRoleFunction._edit', 
				'SecurityRoleFunction._add', 'SecurityRoleFunction._delete', 'SecurityRoleFunction._execute'
			),
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
			)
		));
		$this->bindModel(array('hasMany' => array('SecurityRoleFunction')));
		
		$permissions = array();
		foreach($list as $obj) {
			$function = $obj['SecurityFunction'];
			$roleFunction = $obj['SecurityRoleFunction'];
			$module = $function['module'];
			
			if(!isset($permissions[$module])) {
				$permissions[$module] = array('enabled' => false);
			}
			$row = array();
			foreach($operations as $op) {
				if(!is_null($function[$op])) {
					$row[$op] = is_null($roleFunction[$op]) ? 0 : $roleFunction[$op];
					if($row[$op]==1 && $function['visible'] == 1 && $permissions[$module]['enabled'] == false) { // for enabling the module in view
						$permissions[$module]['enabled'] = true;
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
			$permissions[$module][] = $row;
		}
		return $permissions;
	}
}