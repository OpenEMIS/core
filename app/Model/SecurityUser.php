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
	public $actsAs = array(
		'DatePicker' => array('date_of_birth'),
		'FileUpload' => array(
			array(
				'name' => 'photo_name',
				'content' => 'photo_content',
				'size' => '1MB',
				'allowEmpty' => true
			)
		)
	);
	public $belongsTo = array(
		'Gender',
		'AddressArea' => array(
			'className' => 'Area',
			'foreignKey' => 'address_area_id'
		),
		'BirthplaceArea' => array(
			'className' => 'Area',
			'foreignKey' => 'birthplace_area_id'
		)
	);
	public $hasMany = array(
		'Students.Student',
		'Staff.Staff',
		'UserIdentity',
		'UserNationality',
		'UserLanguage',
		'UserComment',
		'UserSpecialNeed',
		'UserAward',
		'UserContact',
		'Students.StudentIdentity',
		'Students.StudentNationality',
		'Students.StudentLanguage',
		'Students.StudentComment',
		'Students.StudentSpecialNeed',
		'Students.StudentAward',
		'Students.StudentContact',
		'Staff.StaffIdentity',
		'Staff.StaffNationality',
		'Staff.StaffLanguage',
		'Staff.StaffComment',
		'Staff.StaffSpecialNeed',
		'Staff.StaffAward',
		'Staff.StaffContact',
		'SecurityGroupUser',
		'SecurityUserAccess'


	);
	public $validate = array(
		'first_name' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid First Name'
			),
			'ruleCheckIfStringGotNoNumber' => array(
				'rule' => 'checkIfStringGotNoNumber',
				'message' => 'Please enter a valid First Name'
			)
		),
		'middle_name' => array(
			'ruleCheckIfStringGotNoNumber' => array(
				'rule' => 'checkIfStringGotNoNumber',
				'message' => 'Please enter a valid Middle Name'
			)
		),
		'third_name' => array(
			'ruleCheckIfStringGotNoNumber' => array(
				'rule' => 'checkIfStringGotNoNumber',
				'message' => 'Please enter a valid Third Name'
			)
		),
		'last_name' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Last Name'
			),
			'ruleCheckIfStringGotNoNumber' => array(
				'rule' => 'checkIfStringGotNoNumber',
				'message' => 'Please enter a valid Last Name'
			)
		),
		'preferred_name' => array(
			'ruleCheckIfStringGotNoNumber' => array(
				'rule' => 'checkIfStringGotNoNumber',
				'message' => 'Please enter a valid Preferred Name'
			)
		),
		'openemis_no' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid OpenEMIS ID'
			),
			'ruleUnique' => array(
        		'rule' => 'isUnique',
        		'required' => true,
        		'message' => 'Please enter a unique OpenEMIS ID'
		    )
		),
		'gender_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a Gender'
			)
		),
		'address' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Address'
			)
		),
		'date_of_birth' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a Date of Birth'
			),
			'ruleCompare' => array(
				'rule' => array('comparison', 'NOT EQUAL', '0000-00-00'),
				'required' => true,
				'message' => 'Please select a Date of Birth'
			),
			'ruleCompare' => array(
				'rule' => 'compareBirthDate',
				'message' => 'Date of Birth cannot be future date'
			)
		),
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
		'email' => array(
			'ruleRequired' => array(
				'rule' => 'email',
				'allowEmpty' => true,
				'message' => 'Please enter a valid Email'
			)
		)
	);

	public function checkIfStringGotNoNumber($check) {
		$check = array_values($check);
		$check = $check[0];
		return !preg_match('#[0-9]#',$check);
	}

	public function compareBirthDate() {
		if(!empty($this->data[$this->alias]['date_of_birth'])) {
			$birthDate = $this->data[$this->alias]['date_of_birth'];
			$birthTimestamp = strtotime($birthDate);
			$todayDate=date("Y-m-d");
			$todayTimestamp = strtotime($todayDate);

			return $todayTimestamp >= $birthTimestamp;
		}
		return true;
	}
	
	public function getStatus() {
		return array(0 => __('Inactive', true), 1 => __('Active', true));
	}
	
	public function doValidate($data) {
		$validate = true;
		if(isset($data['new_password'])) {
			$newPassword = $data['new_password'];
			$retypePassword = $data['retype_password'];
			if(strlen($newPassword) < 1) {
				$this->invalidate('new_password', __('Please enter a valid password.'));
				unset($data['password']);
				$validate = false;
			}else if(strlen($newPassword) < 6) {
				$this->invalidate('new_password', __('Password must be at least 6 characters.'));
				unset($data['password']);
				$validate = false;
			}else if((strlen($newPassword) != strlen($retypePassword)) || $newPassword != $retypePassword){
				$this->invalidate('retype_password', __('Your passwords do not match.'));
				unset($data['password']);
				$validate = false;
			}else{
				$data['password'] = $newPassword;
			}
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

	public function createToken() {
	  $loginToken = "";
	  $secret = Router::url('/', true);
	  for ($i=0; $i<8; $i++) $loginToken .= $this->rand_alphanumeric();
	  return $loginToken . md5($loginToken . $secret);
	}

	public function validateToken($loginToken) {
	    $rs = substr($loginToken, 0, 8);
     	$secret = Router::url('/', true);
	    return $loginToken === $rs . md5($rs . $secret);
  	}

	private function rand_alphanumeric() {
		$subsets[0] = array('min' => 48, 'max' => 57); // ascii digits
		$subsets[1] = array('min' => 65, 'max' => 90); // ascii lowercase English letters
		$subsets[2] = array('min' => 97, 'max' => 122); // ascii uppercase English letters

		// random choice between lowercase, uppercase, and digits
		$s = rand(0, 2);
		$ascii_code = rand($subsets[$s]['min'], $subsets[$s]['max']);

		return chr( $ascii_code );
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
				'conditions' => array('SecurityUser.openemis_no' => $search, 'SecurityUser.super_admin <>' => 1),
				'order' => array('SecurityUser.first_name')
			));
		} else {
			$search = '%' . $search . '%';
			$limit = isset($params['limit']) ? $params['limit'] : false;
			
			$conditions = array(
				'SecurityUser.super_admin <>' => 1,
				'OR' => array(
					'openemis_no LIKE' => $search,
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

	// used by SearchComponent
	public function getSearchConditions($search) {
		$search = '%' . $search . '%';
		$conditions['OR'] = array(
			'SecurityUser.username LIKE' => $search,
			'SecurityUser.first_name LIKE' => $search,
			'SecurityUser.last_name LIKE' => $search
		);
		return $conditions;
	}

	public function isUserCreatedByCurrentLoggedUser($currentLoggedUser, $user) {
		$data = $this->find('first', array(
			'recursive' => -1,
			'conditions' => array(
				'SecurityUser.id' => $user,
				'SecurityUser.created_user_id' => $currentLoggedUser
			)
		));
		return $data;
	}

	public function autocomplete($search) {
		$search = '%' . $search . '%';
		
		$conditions = array(
			'OR' => array(
				'SecurityUser.openemis_no LIKE' => $search,
				'SecurityUser.first_name LIKE' => $search,
				'SecurityUser.middle_name LIKE' => $search,
				'SecurityUser.third_name LIKE' => $search,
				'SecurityUser.last_name LIKE' => $search
			)
		);
		$options = array(
			'recursive' => -1,
			'conditions' => $conditions,
			'order' => array('SecurityUser.first_name')
		);
		
		$options['fields'] = array('id', 'SecurityUser.first_name', 'SecurityUser.last_name', 'SecurityUser.middle_name', 'SecurityUser.third_name', 'SecurityUser.openemis_no', 'SecurityUser.date_of_birth');
		$data = $this->find('all', $options);
		
		return $data;
	}
}