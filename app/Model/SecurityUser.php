<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-14

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
