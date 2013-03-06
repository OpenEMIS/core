<?php
App::uses('AppModel', 'Model');

class SecurityUserRole extends AppModel {
	public $belongsTo = array('SecurityRole');
	
	public function getUserRoles($userId) {
		$roles = $this->find('list', array(
			'fields' => array('SecurityUserRole.id', 'SecurityUserRole.security_role_id'),
			'conditions' => array('SecurityUserRole.security_user_id' => $userId)
		));
		return $roles;
	}
        
	public function getHighestUserRole($userId){
		$roles = $this->find('first', array(
		'conditions' => array('SecurityUserRole.security_user_id' => $userId),
			'order' => array('SecurityRole.order'),
			'limit' => 1
		));
		return $roles;
	}
}
