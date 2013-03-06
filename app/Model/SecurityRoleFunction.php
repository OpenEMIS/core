<?php
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
