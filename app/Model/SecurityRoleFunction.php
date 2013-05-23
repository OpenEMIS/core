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

class SecurityRoleFunction extends AppModel {
	public $belongsTo = array('SecurityRole', 'SecurityFunction');
	
	public function getModules($roleIds = array()) {
		$roleList = array();
		$roleModel = ClassRegistry::init('SecurityRole');
		if(empty($roleIds)) {
			$roles = $roleModel->findList(true);
		} else {
			$roles = $roleModel->findList(array('conditions' => array('SecurityRole.id' => $roleIds, 'SecurityRole.visible' => 1)));
		}
		foreach($roles as $roleId => $role) {
			$roleList[$roleId] = array('name' => $role);
			$roleList[$roleId]['modules'] = $this->getFunctionModules($roleId);

			foreach ($roleList[$roleId]['modules'] as $key => $value) {
				$roleList[$roleId]['modules'][$key] = __($value);
			}
			$roleList[$roleId]['modulesToString'] = implode(', ', $roleList[$roleId]['modules']);
		}
		return $roleList;
	}
	
	public function getFunctionModules($roleId) {
		$modules = array();
		$roleFunctions = $this->find('all', array('conditions' => array('SecurityRoleFunction.security_role_id' => $roleId)));
		foreach($roleFunctions as $obj) {
			$function = $obj['SecurityFunction'];
			$roleFunction = $obj['SecurityRoleFunction'];
			
			if($roleFunction['_view'] || $roleFunction['_edit'] || $roleFunction['_add'] || $roleFunction['_delete']) {
				if(!in_array($function['module'], $modules)) {
					$modules[] = $function['module'];
				}
			}
		}
		return $modules;
	}
}
