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

class SecurityUser extends AppModel {
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
	
	public function getStatus() {
		return array(0 => __('Inactive', true), 1 => __('Active', true));
	}
	
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
	
	public function beforeSave($options = array()) {
		if (isset($this->data[$this->alias]['password'])) {
			$this->data[$this->alias]['password'] = AuthComponent::password($this->data[$this->alias]['password']);
		}
		parent::beforeSave();
		return true;
	}
	
	public function updateLastLogin($id) {
		$this->id = $id;
		$this->saveField('last_login', date('Y-m-d H:i:s'));
	}
	
	public function search($type, $search, $params=array()) {
		$data = array();
		if($type==0) {
			$data = $this->find('first', array(
				'recursive' => -1,
				'fields' => array('SecurityUser.id', 'SecurityUser.first_name', 'SecurityUser.last_name'),
				'conditions' => array('SecurityUser.identification_no' => $search, 'SecurityUser.super_admin <>' => 1),
				'order' => array('SecurityUser.first_name')
			));
		} else {
			$search = '%' . $search . '%';
			$limit = isset($params['limit']) ? $params['limit'] : false;
			
			$conditions = array(
				'SecurityUser.super_admin <>' => 1,
				'OR' => array(
					'SecurityUser.identification_no LIKE' => $search,
					'SecurityUser.first_name LIKE' => $search,
					'SecurityUser.last_name LIKE' => $search
				)
			);
			
			$options = array(
				'recursive' => -1,
				'conditions' => $conditions,
				'order' => array('SecurityUser.first_name')
			);
			
			$count = $this->find('count', $options);
			
			$data = false;
			if($limit === false || $count < $limit) {
				$options['fields'] = array('SecurityUser.*');
				$data = $this->find('all', $options);
			}
		}
		return $data;
	}
	
	public function paginateJoins(&$conditions) {
		
	}
	
	public function paginateConditions(&$conditions) {
		if(isset($conditions['search']) && !empty($conditions['search'])) {
			$search = $conditions['search'];
			$search = '%' . $search . '%';
			$conditions['OR'] = array(
				'SecurityUser.username LIKE' => $search,
				'SecurityUser.first_name LIKE' => $search,
				'SecurityUser.last_name LIKE' => $search
			);
		}
		unset($conditions['search']);
	}
	
	public function paginate($conditions, $fields, $order, $limit, $page = 1, $recursive = null, $extra = array()) {
		$this->paginateConditions($conditions);
		$data = $this->find('all', array(
			'fields' => array('SecurityUser.id', 'SecurityUser.username', 'SecurityUser.first_name', 'SecurityUser.last_name', 'SecurityUser.status'),
			//'joins' => $this->paginateJoins($conditions),
			'conditions' => $conditions,
			'limit' => $limit,
			'offset' => (($page-1)*$limit),
			'order' => $order
		));
		return $data;
	}
	 
	public function paginateCount($conditions = null, $recursive = 0, $extra = array()) {
		$this->paginateConditions($conditions);
		$count = $this->find('count', array(
			//'joins' => $this->paginateJoins($conditions), 
			'conditions' => $conditions
		));
		return $count;
	}
}