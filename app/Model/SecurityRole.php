<?php
App::uses('AppModel', 'Model');

class SecurityRole extends AppModel {
	public $hasMany = array('SecurityUserRole', 'SecurityRoleFunction');
	public $actsAs = array('Named');
}