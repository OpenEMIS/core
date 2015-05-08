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
	public $actsAs = array('ControllerAction');
	public $belongsTo = array('SecurityRole', 'SecurityFunction');
	
	public function beforeAction($controller, $action) {
        parent::beforeAction($controller, $action);
		$controller->Navigation->addCrumb('Permissions');
		$controller->set('header', __('Permissions'));
    }
	
	public function getOperationsLookup(){
		$lookup = array(
			'_execute' => array(
				'level' => 5
			),
			'_delete' => array(
				'level' => 4
			),
			'_add' => array(
				'level' => 3
			),
			'_edit' => array(
				'level' => 2
			),
			'_view' => array(
				'level' => 1
			)
		);
		
		return $lookup;
	}
	
	public function permissions($controller, $params) {
		if(isset($params->pass[0])) {
			$selectedRole = $params->pass[0];
			
			if ($this->SecurityRole->exists($selectedRole)) {
				$this->SecurityRole->recursive = 0;
				$role = $this->SecurityRole->findById($selectedRole);
				$roleOptions = $this->SecurityRole->find('list', array(
					'conditions' => array('SecurityRole.security_group_id' => $role['SecurityRole']['security_group_id'])
				));
				
				$moduleOptions = $this->SecurityFunction->find('list', array(
					'fields' => array('SecurityFunction.module', 'SecurityFunction.module'),
					'order' => array('SecurityFunction.order'),
					'group' => array('SecurityFunction.module')
				));
				$selectedModule = isset($params->pass[1]) ? $params->pass[1] : key($moduleOptions);
				
				$allowEdit = true;
				$isSuperUser = $controller->Auth->user('super_admin')==1;
				$userId = $isSuperUser ? false : $controller->Auth->user('id');
				if(!$isSuperUser) {
					$userRoles = ClassRegistry::init('SecurityGroupUser')->getRolesByUserId($userId);
					foreach($userRoles as $obj) {
						if($obj['SecurityRole']['id'] === $selectedRole) {
							$allowEdit = false;
							break;
						}
					}
				}
				
				$this->SecurityFunction->recursive = 0;
				$permissions = $this->SecurityFunction->getPermissions($selectedRole, $selectedModule, $isSuperUser);
				$selectedGroup = $role['SecurityRole']['security_group_id'];
				$groupObj = ClassRegistry::init('SecurityGroup')->findById($role['SecurityRole']['security_group_id']);
				if (!empty($groupObj)) {
					$header = $groupObj['SecurityGroup']['name'];// . ' - ' . __('Permissions');
					$controller->set('header', $header);
				}
				// Apply the translation function to all values in the array for both $roleOptions and $moduleOptions
				array_walk($roleOptions, function(&$roleArg){
					$roleArg = __($roleArg);
				});
				array_walk($moduleOptions, function(&$modArg){
					$modArg = __($modArg);
				});
				
				$controller->set('_operations', $controller->AccessControl->operations);
				$controller->set(compact('allowEdit', 'roleOptions', 'selectedRole', 'moduleOptions', 'selectedModule', 'permissions', 'selectedGroup'));
			} else {
				$controller->Message->alert('general.notExists');
				return $controller->redirect(array('action' => 'roles'));
			}
			
			/*
			$isSuperUser = $controller->Auth->user('super_admin')==1;
			$userId = $isSuperUser ? false : $this->Auth->user('id');
			$groupObj = $this->SecurityRole->getGroupName($selectedRole, $userId);
			
			$allowEdit = true;
			if(!$isSuperUser) {
				$userRoles = ClassRegistry::init('SecurityGroupUser')->getRolesByUserId($userId);
				foreach($userRoles as $obj) {
					if($obj['SecurityRole']['id'] === $selectedRole) {
						$allowEdit = false;
						break;
					}
				}
			}
			$roles = $groupObj ? $this->SecurityRole->getRoleOptions($groupObj['id']) : $this->SecurityRole->getRoleOptions(array(0, -1));
			$permissions = array();
			if($isSuperUser) {
				$permissions = $this->SecurityFunction->getPermissions($selectedRole, $isSuperUser);
			} else {
				$permissions = $this->SecurityFunction->getAllowedPermissions($selectedRole, $userId, $isSuperUser);
			}
			$controller->set('_operations', $controller->AccessControl->operations);
			$controller->set('selectedRole', $selectedRole);
			$controller->set('roles', $roles);
			$controller->set('permissions', $permissions);
			$controller->set('group', $groupObj);
			$controller->set('allowEdit', $allowEdit);
			*/
		} else {
			$controller->Message->alert('general.notExists');
			$controller->redirect(array('action' => 'roles'));
		}
	}
	
	public function permissionsEdit($controller, $params) {
		if(isset($params->pass[0])) {
			$selectedRole = $params->pass[0];
			
			if ($this->SecurityRole->exists($selectedRole)) {
				$this->SecurityRole->recursive = 0;
				$role = $this->SecurityRole->findById($selectedRole);
				$roleOptions = $this->SecurityRole->find('list', array(
					'conditions' => array('SecurityRole.security_group_id' => $role['SecurityRole']['security_group_id'])
				));
				
				$moduleOptions = $this->SecurityFunction->find('list', array(
					'fields' => array('SecurityFunction.module', 'SecurityFunction.module'),
					'order' => array('SecurityFunction.order'),
					'group' => array('SecurityFunction.module')
				));
				$selectedModule = isset($params->pass[1]) ? $params->pass[1] : key($moduleOptions);
				
				if($controller->request->is('get')) {
					$allowEdit = true;
					$isSuperUser = $controller->Auth->user('super_admin')==1;
					$userId = $isSuperUser ? false : $controller->Auth->user('id');
					
					$operationsLookup = $this->getOperationsLookup();
					$permissionLookup = array();
					if(!$isSuperUser) {
						$userPermissions = $this->SecurityFunction->getUserPermissions($userId);
						foreach($userPermissions as $row){
							$securityFunctionId = $row['SecurityFunction']['id'];
							$securityRoleFunction = $row['SecurityRoleFunction'];

							foreach($operationsLookup as $operation => $operationObj){
								if($securityRoleFunction[$operation] == 1){
									if(!isset($permissionLookup[$securityFunctionId]) || $permissionLookup[$securityFunctionId]['highest'] < $operationObj['level']){
										$permissionLookup[$securityFunctionId] = $operationObj['level'];
									}
									break;
								}
							}
						}
						
						$userRoles = ClassRegistry::init('SecurityGroupUser')->getRolesByUserId($userId);
						foreach($userRoles as $obj) {
							if($obj['SecurityRole']['id'] === $selectedRole) {
								$allowEdit = false;
								break;
							}
						}
					}
					
					if($allowEdit) {
						$this->SecurityFunction->recursive = 0;
						
						$permissions = array();
						$permissions = $this->SecurityFunction->getPermissions($selectedRole, $selectedModule, $isSuperUser);
					} else {
						$controller->redirect(array('action' => 'permissions', $selectedRole));
					}
					
					$controller->set('_operations', $controller->AccessControl->operations);
					$controller->set(compact('roleOptions', 'selectedRole', 'moduleOptions', 'selectedModule', 'permissions', 'permissionLookup', 'operationsLookup', 'isSuperUser'));
				} else {
					$data = $controller->request->data['SecurityRoleFunction'];
					$this->saveAll($data);
					$controller->Message->alert('general.edit.success');
					$controller->redirect(array('action' => 'permissions', $selectedRole, $selectedModule));
				}
			} else {
				$controller->Message->alert('general.notExists');
				return $controller->redirect(array('action' => 'roles'));
			}
		} else {
			$controller->Message->alert('general.notExists');
			return $controller->redirect(array('action' => 'roles'));
		}
	}
	
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
			
			if($roleFunction['_view'] || $roleFunction['_edit'] || $roleFunction['_add'] || $roleFunction['_delete'] || $roleFunction['_execute']) {
				if(!in_array($function['module'], $modules)) {
					$modules[] = $function['module'];
				}
			}
		}
		return $modules;
	}
}
