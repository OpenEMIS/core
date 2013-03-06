<?php
App::uses('AppModel', 'Model');

class SecurityUser extends AppModel {
	public $hasMany = array('SecurityUserRole');
	
	// public $status = array(0 => 'Inactive', 1 => 'Active');
	public $status = array();
	public function beforeFind() {
		$this->status = array(0 => __('Inactive', true), 1 => __('Active', true));
	}
	
	public $validate = array(
		'username' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'message' => 'Please enter a valid username'
			),
			'ruleUnique' => array(
				'rule' => 'isUnique',
				'message' => 'This username is already in use.'
			)
		),
		'password' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'message' => 'Please enter a valid password'
			),
			'ruleMinLength' => array(
				'rule' => array('minLength', 6),
				'message' => 'Password must be at least 6 characters'
			)
		),
		'first_name' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid First Name'
			)
		),
		'last_name' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Last Name'
			)
		),
		'email' => array(
			'ruleRequired' => array(
				'rule' => 'email',
				'allowEmpty' => true,
				'message' => 'Please enter a valid Email'
			)
		)
	);
	
	public function doValidate($data) {
		$validate = true;
		if(isset($data['new_password']) && !empty($data['new_password'])) {
			$newPassword = $data['new_password'];
			$retypePassword = $data['retype_password'];
			if(strcmp($newPassword, $retypePassword) != 0) {
				$this->invalidate('password', __('Your passwords do not match.'));
				unset($data['password']);
			} else {
				$data['password'] = $newPassword;
			}
		} else {
			unset($data['password']);
		}
		if($validate) {
			$this->set($data);
			if($this->validates()) {
				$this->save($data);
			} else {
				$validate = false;
			}
		}
		return $validate;
	}
	
	public function beforeSave() {
		if (isset($this->data[$this->alias]['password'])) {
			$this->data[$this->alias]['password'] = AuthComponent::password($this->data[$this->alias]['password']);
		}
		return true;
	}
	
	public function updateLastLogin($id) {
		$this->id = $id;
		$this->saveField('last_login', date('Y-m-d H:i:s'));
	}
}
