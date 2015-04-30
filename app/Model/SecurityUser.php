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
		),
		'ControllerAction2',
		'Mandatory'
	);
	public $belongsTo = array(
		'Gender',
		'AddressArea' => array(
			'className' => 'AreaAdministrative',
			'foreignKey' => 'address_area_id'
		),
		'BirthplaceArea' => array(
			'className' => 'AreaAdministrative',
			'foreignKey' => 'birthplace_area_id'
		),
		'ModifiedUser' => array(
			'className' => 'SecurityUser',
			'foreignKey' => 'modified_user_id'
		),
		'CreatedUser' => array(
			'className' => 'SecurityUser',
			'foreignKey' => 'created_user_id'
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
				'message' => 'Please enter a valid OpenEMIS ID'
			),
			'ruleUnique' => array(
        		'rule' => 'isUniqueOpenemisNo',
        		'message' => 'Please enter a unique OpenEMIS ID'
		    )
		),
		'gender_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'message' => 'Please select a Gender'
			)
		),
		'address' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'message' => 'Please enter a valid Address'
			)
		),
		'date_of_birth' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'message' => 'Please select a Date of Birth'
			),
			'ruleCompare' => array(
				'rule' => array('comparison', 'NOT EQUAL', '0000-00-00'),
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
			'ruleNoSpaces' => array(
				'rule' => '/^[a-z0-9]{3,}$/i',
				'message' => 'Only alphabets and numbers are allowed'
		),
			'ruleUnique' => array(
				'rule' => 'isUnique',
				'message' => 'This username is already in use.'
			)
		),
		'password' => array(
			'ruleChangePassword' => array(
				'rule' => array('changePassword',false),
				 // authenticate changePassword ('new password', retyped password) // validate behaviour
				'on' => 'update',
				'message' => 'Incorrect password.'
			),
			'ruleCheckUsernameExists' => array(
				'rule' => array('checkUsernameExists'),
				'message' => 'Please enter a valid password'
			),
			'ruleMinLength' => array(
				'rule' => array('minLength', 6),
				'on' => 'create',
				'allowEmpty' => true,
				'message' => 'Password must be at least 6 characters'
			)
		),
		'newPassword' => array(
			'ruleChangePassword' => array(
				'rule' => 'notEmpty',
				'on' => 'update',
				'message' => 'Please enter your new password'
			),
			'ruleMinLength' => array(
				'rule' => array('minLength', 6),
				'on' => 'update',
				'message' => 'Password must be at least 6 characters'
			)
		),
		'retypeNewPassword' => array(
			'ruleChangePassword' => array(
				'rule' => 'notEmpty',
				'on' => 'update',
				'message' => 'Please confirm your new password'
			),
			'ruleCompare' => array(
				'rule' => 'comparePasswords',
				'on' => 'update',
				'message' => 'Both passwords do not match'
			)
		)
	);

	public function checkUsernameExists($check) {
		$check = reset($check);
		if (array_key_exists('username', $this->data[$this->alias])) {
			if (!empty($this->data[$this->alias]['username'])) {
				if (!empty($check)) {
					return true;
				} else {
					return false;
				}
			}
		}
		return true;
	}

	public function comparePasswords() {
		if(strcmp($this->data[$this->alias]['newPassword'], $this->data[$this->alias]['retypeNewPassword']) == 0 ) {
			$this->data[$this->alias]['password'] = AuthComponent::password($this->data[$this->alias]['newPassword']);
			return true;
		}
		return false;
	}

	public function isUniqueOpenemisNo($check) {
		$currentUserId = null;
		if (array_key_exists('SecurityUser', $this->data)) {
			if (array_key_exists('id', $this->data['SecurityUser'])) {
				$currentUserId = $this->data['SecurityUser']['id'];
			}
		}
		$conditions = array('SecurityUser.openemis_no' => $check['openemis_no']);

		if (!empty($currentUserId)) {
			$conditions['NOT'] = array('SecurityUser.id' => $currentUserId);
		}

		$isUnique = $this->find(
			'count',
			array(	
				'conditions' => $conditions
			)
		);
		$isUnique = ($isUnique==0)? true: false;

		return $isUnique;
	}

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
			if(strlen($newPassword) > 0) {
				if(strlen($newPassword) < 6) {
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

	public function beforeValidate($options=array()) {
		if (!$this->id && !isset($this->data[$this->alias][$this->primaryKey])) {
			if (array_key_exists('username', $this->data[$this->alias])) {
				if (empty($this->data[$this->alias]['username'])) {
					$this->data[$this->alias]['username'] = null;
					$this->validator()->remove('username', 'ruleUnique');
					$this->validator()->remove('username', 'ruleNoSpaces');
				}
			}

			if (array_key_exists('password', $this->data[$this->alias])) {
				if (empty($this->data[$this->alias]['password'])) {
					$this->data[$this->alias]['password'] = null;
				}
			}
		}
	}
	
	public function beforeSave($options = array()) {
		parent::beforeSave();
		if (!$this->id && !isset($this->data[$this->alias][$this->primaryKey])) {
			// insert
			if (array_key_exists('password', $this->data[$this->alias])) {
				if (!is_null($this->data[$this->alias]['password'])) {
					$this->data[$this->alias]['password'] = AuthComponent::password($this->data[$this->alias]['password']);
				}
			}
		} else {
			// edit - already handled in authenticate no action required
		}
		return true;
	}

	public function beforeAction() {
		parent::beforeAction();

		if (in_array($this->action,array('view', 'edit'))) {
			$this->fields['nav_tabs'] = array(
				'type' => 'element',
				'element' => '../Security/SecurityUser/nav_tabs',
				'override' => true,
				'visible' => true
			);
		}

		if (in_array($this->action,array('add'))) {
			$this->fields['openemis_no'] = array(
				'type' => 'element',
				'element' => 'form/text_multiple',
				'override' => true,
				'visible' => true,
				'data' => array(
					'fieldName' => 'openemis_no',
					'prefixAttr' => array('maxlength' => 5),
					'suffixAttr' => array('value' => $this->controller->Utility->getUniqueOpenemisId(), 'readonly' => 'readonly'),
				)
			);
		} else {
			$this->fields['openemis_no']['attr']['readonly'] = true;
		}

		$this->fields['id']['type'] = 'hidden';
		$this->fields['username']['visible'] = false;
		$this->fields['password']['visible'] = false;
		$this->fields['address_area_id']['type'] = 'hidden';
		$this->fields['birthplace_area_id']['type'] = 'hidden';
		$this->fields['date_of_death']['type'] = 'hidden';
		$this->fields['super_admin']['visible'] = false;
		$this->fields['photo_name']['type'] = 'hidden';
		$this->fields['photo_content']['type'] = 'hidden';
		$this->fields['gender_id']['type'] = 'select';
		$this->fields['gender_id']['options'] = $this->Gender->getList();
		$this->fields['status']['type'] = 'select';
		$this->fields['status']['options'] = $this->getStatus();
		$this->fields['last_login']['type'] = 'hidden';		

		$this->fields['UserContact'] = array(
			'type' => 'element',
			'element' => '../UserContact/viewContact',
			'class' => 'col-md-8',
			'visible' => true
		);
		$this->fields['SecurityGroupUser'] = array(
			'type' => 'element',
			'element' => '../Security/SecurityGroup/security_user',
			'class' => 'col-md-8',
			'visible' => true,
		);

		$order = 0;
		$this->setFieldOrder('nav_tabs', $order++);
		$this->setFieldOrder('openemis_no', $order++);
		$this->setFieldOrder('first_name', $order++);
		$this->setFieldOrder('middle_name', $order++);
		$this->setFieldOrder('third_name', $order++);
		$this->setFieldOrder('last_name', $order++);
		$this->setFieldOrder('preferred_name', $order++);
		$this->setFieldOrder('address', $order++);
		$this->setFieldOrder('postal_code', $order++);
		$this->setFieldOrder('gender_id', $order++);
		$this->setFieldOrder('date_of_birth', $order++);
		$this->setFieldOrder('status', $order++);
		$this->setFieldOrder('UserContact', $order++);
		$this->setFieldOrder('SecurityGroupUser', $order++);
		$this->setFieldOrder('modified_user_id', $order++);
		$this->setFieldOrder('modified', $order++);
		$this->setFieldOrder('created_user_id', $order++);
		$this->setFieldOrder('created', $order++);
	}

	public function index() {
		$this->Navigation->addCrumb('Users');

		$conditions = array('SecurityUser.super_admin' => 0);

		$order = empty($this->params->named['sort']) ? array('SecurityUser.first_name' => 'asc') : array();
		$data = $this->controller->Search->search($this, $conditions, $order);
		
		if (empty($data)) {
			$this->Message->alert('general.noData');
		}
		$this->setVar('data', $data);
	}

	public function view($id) {
		if ($this->exists($id)) {
			$data = $this->find(
				'first',
				array(

					'contain' => array(
						'UserContact' => array(
							'fields'=> array('id', 'value', 'preferred'),
							'ContactType' => array(
								'fields'=> array('id', 'name'),	
								'ContactOption' => array('fields'=> array('id', 'name'))
							)
						),
						'SecurityGroupUser' => array(
							'SecurityUser',
							'SecurityGroup' => array('fields'=> array('id', 'name')),
							'SecurityRole' => array('fields'=> array('id', 'name'))
						)
					),
					'conditions' => array('SecurityUser.id' => $id)
				)
			);

			$this->fields['UserContact']['data'] = array('data' => $data['UserContact']);
			$elementData = array(
				'data' => array(
					'SecurityUser' => array(
						'SecurityGroupUser' => $data['SecurityGroupUser']
					)
				)
			);
			$this->fields['SecurityGroupUser']['data'] = $elementData;

			$this->Session->write('User.id', $id);
		}
		parent::view($id);
	}

	public function edit($id) {
		unset($this->fields['UserContact']);
		unset($this->fields['SecurityGroupUser']);
		parent::edit($id);
	}

	public function add() {
		unset($this->fields['UserContact']);
		unset($this->fields['SecurityGroupUser']);

		if ($this->request->is(array('post', 'put'))) {
			if (array_key_exists('openemis_no_suffix', $this->request->data['SecurityUser'])) {
				$this->request->data['SecurityUser']['openemis_no'] = $this->request->data['SecurityUser']['openemis_no_prefix'].$this->request->data['SecurityUser']['openemis_no_suffix'];
			}
		}
		parent::add();
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