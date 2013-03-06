<?php
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
				'SecurityFunction._edit', 'SecurityFunction._add', 'SecurityFunction._delete', 'SecurityFunction.visible',
				'SecurityFunction.parent_id',
				'SecurityRoleFunction.id',
				'SecurityRoleFunction._view', 'SecurityRoleFunction._edit', 
				'SecurityRoleFunction._add', 'SecurityRoleFunction._delete'
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
					if($row[$op]==1) { // for enabling the module in view
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