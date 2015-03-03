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

App::uses('AppController', 'Controller');

class SecurityController extends AppController {
	public $uses = array(
		'ConfigItem',
		'Area',
		'AreaLevel',
		'InstitutionSite',
		'SecurityUser',
		'SecurityRole',
		'SecurityGroup',
		'SecurityGroupUser',
		'SecurityFunction',
		'SecurityRoleFunction',
		'SecurityGroupArea',
		'SecurityGroupInstitutionSite',
		'SecurityUserAccess',
		'Staff.Staff',
		'Students.Student',
		'ConfigAttachment'
	);
	
	public $components = array(
		'Paginator',
		'LDAP'
	);
	
	public $modules = array(
		'SecurityUser',
		'SecurityGroup',
		'permissions' => 'SecurityRoleFunction',
		'roles' => 'SecurityRole',
		'SecurityUserAccess',
		'SecurityUserLogin'
	);
	
	public function beforeFilter() {
		parent::beforeFilter();
		$this->renderFooter();
		$this->Auth->allow('login');
		$this->Auth->allow('login_remote');
		
		if($this->action !== 'login' || $this->action !== 'logout') {
			$this->bodyTitle = 'Administration';
			$this->Navigation->addCrumb('Administration', array('controller' => 'Areas', 'action' => 'index', 'plugin' => false));
			$this->Navigation->addCrumb('Security', array('controller' => $this->name, 'action' => 'users'));
		}
	}
	
	private function renderFooter() {
		if(!$this->Session->check('footer')){
			$this->Session->write('footer', $this->ConfigItem->getWebFooter());
		}
	}
	
    public function login() {
		$this->layout = 'login';
		
		$username = '';
		$password = '';

		if($this->request->is('post') && $this->request->data['submit'] == 'login') {
			$username = $this->data['SecurityUser']['username'];
			
			$this->log('[' . $username . '] Attempt to login as ' . $username . '@' . $_SERVER['REMOTE_ADDR'], 'security');
			if(!$this->RequestHandler->isAjax()) {
			
				/*
					Requirement check for Auth Method if LDAP or LOCAL
					if LDAP, 
					1. check if LDAP server is up 
					1.a If LDAP Server DOWN  use the LOCAL Authentication
					1.b if LDAP Server UP -- Authenticate
					1.b.1 if Failed Throw Error
					1.b.2 if Success -- 
						a. Fetch Username from OpenEMIS and force Login them
				*/
				
				if($this->ConfigItem->getValue('authentication_type') == 'LDAP') {
					
					$arrLdapConfig = $this->ConfigItem->getAllLDAPConfig();
					$settings = array_merge($this->data['SecurityUser'],$arrLdapConfig);
					$ldapverify = $this->LDAP->verifyUser($settings);
					if ($ldapverify === true) {
						$data = $this->SecurityUser->find('first', array('recursive' => 0, 'conditions' => array('SecurityUser.username' => $this->data['SecurityUser']['username'])));
						if (count($data['SecurityUser'])>0) {
							$result = $this->Auth->login($data['SecurityUser']);
						} else {
							$result = false;
							$this->Message->alert('security.ldap.fail');
						}	
					} else {
						$result = false;
						$errMsg = $ldapverify;
					}
				} else {
					$result = $this->Auth->login();
				}
				if ($result) {
					if ($this->Auth->user('status') == 1) {
						$this->log('[' . $username . '] Login successfully.', 'security');
						$userId = AuthComponent::user('id');
						$this->SecurityUser->updateLastLogin($userId);
						$this->AccessControl->init($userId);
						$this->registerSession();
						$this->redirect($this->Auth->redirect(array('controller' => 'Home')));
					} else if ($this->Auth->user('status') == 0) {
						$this->log('[' . $username . '] Account is not active.', 'security');
						$this->Message->alert('security.login.inactive');
					}
				} else {
					$this->Message->alert('security.login.fail');
				}
			} else {
				$this->autoRender = false;
				if($this->ConfigItem->getValue('authentication_type') == 'LDAP'){
					
					$arrLdapConfig = $this->ConfigItem->getAllLDAPConfig();
					$settings = array_merge($this->data['SecurityUser'],$arrLdapConfig);
					$ldapverify = $this->LDAP->verifyUser($settings);
					if($ldapverify === true){
						$data = $this->SecurityUser->find('first', array('recursive' => 0, 'conditions' => array('SecurityUser.username' => $this->data['SecurityUser']['username'])));
						
						if(count($data['SecurityUser'])>0)
							$ajaxLoginResult = $this->Auth->login($data['SecurityUser']);
						else{
							$ajaxLoginResult = false;
						}
							
					}else{
						$ajaxLoginResult = false;
					}
					
				} else {
					$ajaxLoginResult = $this->Auth->login();
					
				}
				if($ajaxLoginResult) {
					$userId = AuthComponent::user('id');
					$this->SecurityUser->updateLastLogin($userId);
					$this->AccessControl->init($userId);
					$this->registerSession();
				}
				return $ajaxLoginResult;
			}
		} else {
			if(!$this->RequestHandler->isAjax()) { // normal login

				// login credential sent from website
				if ($this->Session->check('login.username')) {
					$username = $this->Session->read('login.username');
				}
				if ($this->Session->check('login.password')) {
					$password = $this->Session->read('login.password');
				}
				if($this->Auth->user()) { // user already login
					return $this->redirect(array('controller' => 'Home'));
				}
			} else { // ajax login
				$this->set('message', $this->Utility->getMessage('LOGIN_TIMEOUT'));
				$this->render('login_ajax');
			}
		}

		if($this->request->is('post') && $this->request->data['submit'] == 'reload') {
			$username = $this->request->data['SecurityUser']['username'];
			$password = $this->request->data['SecurityUser']['password'];
		}
		
		$this->set('username', $username);
		$this->set('password', $password);
	
		$images = $this->ConfigAttachment->find('all', array('fields' => array('id','file_name','name'), 'conditions' => array('ConfigAttachment.type'=> array('login','partner'), 'ConfigAttachment.active' => 1), 'order'=>array('order')));
		$imageData = array();
		if(!empty($images)){
			$i = 0;
			foreach($images as $image){
				$imageData[$i] = array_merge($image['ConfigAttachment']);
				$i++;
			}
		}
		$this->set('images', $imageData);
	}

	public function login_remote() {
		$this->autoRender = false;
		$this->Session->write('login.username', $this->data['username']);
		$this->Session->write('login.password', $this->data['password']);
		return $this->redirect(array('action' => 'login'));
	}

	public function logout() {
		$redirect = $this->Auth->logout();
		$this->Session->destroy();
		$this->redirect($redirect);
	}
	
	public function registerSession() {
		$this->Session->write('login.token', $this->SecurityUser->createToken());
		$this->Session->write('configItem.currency', $this->ConfigItem->getValue('currency'));
		$this->Session->write('footer', $this->ConfigItem->getWebFooter());
	}
	
	// public function users() {
	// 	$this->Navigation->addCrumb('Users');

	// 	$conditions = array('SecurityUser.super_admin' => 0);

	// 	$order = empty($this->params->named['sort']) ? array('SecurityUser.first_name' => 'asc') : array();
	// 	$data = $this->Search->search($this->SecurityUser, $conditions, $order);
		
	// 	if (empty($data)) {
	// 		$this->Message->alert('general.noData');
	// 	}
	// 	$this->set('data', $data);
	// }
	
	// public function usersView() {
	// 	$this->Navigation->addCrumb('Users', array('controller' => 'Security', 'action' => 'users'));
		
	// 	if(isset($this->params['pass'][0])) {
	// 		$userId = $this->params['pass'][0];
	// 		$this->Session->write('SecurityUserId', $userId);
	// 		$data = $this->SecurityUser->find(
	// 			'first', 
	// 			array(
	// 				'contain' => array(
	// 					'UserContact' => array(
	// 						'fields'=> array('id', 'value', 'preferred'),
	// 						'ContactType' => array(
	// 							'fields'=> array('id', 'name'),	
	// 							'ContactOption' => array('fields'=> array('id', 'name'))
	// 						)
	// 					),
	// 					'SecurityGroupUser' => array(
	// 						'SecurityGroup' => array('fields'=> array('id', 'name')),
	// 						'SecurityRole' => array('fields'=> array('id', 'name'))
	// 					),
	// 					'SecurityUserAccess' => array(
	// 						'fields' => array('table_name', 'security_user_id')
	// 					)
	// 				),
	// 				'conditions' => array('SecurityUser.id' => $userId)
	// 			)
	// 		);

	// 		// manually getting security user data because table has no 'id'
	// 		foreach ($data['SecurityUserAccess'] as $key => $value) {
	// 			$test = $this->SecurityUser->find(
	// 				'first',
	// 				array(
	// 					'recursive' => -1,
	// 					'fields' => array('id', 'first_name', 'middle_name', 'third_name', 'last_name', 'openemis_no'),
	// 					'conditions' => array('id' => $value['security_user_id'])
	// 				)
	// 			);
	// 			$data['SecurityUserAccess'][$key] = array_merge($data['SecurityUserAccess'][$key], $test);
	// 		}
			
	// 		$allowEdit = false;
	// 		if($this->Auth->user('super_admin')==1) {
	// 			// if the user himself is a super admin, then allow edit
	// 			$allowEdit = true;
	// 		} else if($this->Auth->user('super_admin')==$data['super_admin']) {
	// 			//$allowEdit = $this->SecurityGroupUser->isUserInSameGroup($this->Auth->user('id'), $userId);
	// 			$allowEdit = $this->SecurityUser->isUserCreatedByCurrentLoggedUser($this->Auth->user('id'), $userId);//(currentLoggedUser, userBeingViewed)
	// 		}
	// 		$this->set('data', $data);
	// 		$this->set('allowEdit', $allowEdit);

			
	// 		$this->Navigation->addCrumb(ModelHelper::getName($data['SecurityUser']));
	// 	} else {
	// 		$this->redirect(array('action' => 'users'));
	// 	}
	// }
	
	// public function usersEdit() {
	// 	$this->Navigation->addCrumb('Users', array('controller' => 'Security', 'action' => 'users'));
	// 	if(isset($this->params['pass'][0])) {
	// 		$userId = $this->params['pass'][0];

	// 		$data = $this->SecurityUser->find(
	// 			'first', 
	// 			array(
	// 				'contain' => array(
	// 					'UserContact' => array(
	// 						'fields'=> array('id', 'value', 'preferred'),
	// 						'ContactType' => array(
	// 							'fields'=> array('id', 'name'),	
	// 							'ContactOption' => array('fields'=> array('id', 'name'))
	// 						)
	// 					),
	// 					'SecurityGroupUser' => array(
	// 						'SecurityGroup' => array('fields'=> array('id', 'name')),
	// 						'SecurityRole' => array('fields'=> array('id', 'name'))
	// 					),
	// 					'SecurityUserAccess' => array(
	// 						'fields' => array('table_name', 'security_user_id')
	// 					)
	// 				),
	// 				'conditions' => array('SecurityUser.id' => $userId)
	// 			)
	// 		);

	// 		// manually getting security user data because table has no 'id'
	// 		foreach ($data['SecurityUserAccess'] as $key => $value) {
	// 			$test = $this->SecurityUser->find(
	// 				'first',
	// 				array(
	// 					'recursive' => -1,
	// 					'fields' => array('id', 'first_name', 'middle_name', 'third_name', 'last_name', 'openemis_no'),
	// 					'conditions' => array('id' => $value['security_user_id'])
	// 				)
	// 			);
	// 			$data['SecurityUserAccess'][$key] = array_merge($data['SecurityUserAccess'][$key], $test);
	// 		}

	// 		$allowEdit = false;
	// 		if($this->Auth->user('super_admin')==1) {
	// 			$allowEdit = true;
	// 		} else if($this->Auth->user('super_admin')==$data['super_admin']) {
	// 			//$allowEdit = $this->SecurityGroupUser->isUserInSameGroup($this->Auth->user('id'), $userId);
	// 			$allowEdit = $this->SecurityUser->isUserCreatedByCurrentLoggedUser($this->Auth->user('id'), $userId);//(currentLoggedUser, userBeingViewed)
	// 		}
			
	// 		if(!$allowEdit) {
	// 			$this->redirect(array('action' => 'users'));
	// 		} else {
	// 			if($this->request->is('post') || $this->request->is('put')) {
	// 				if ($this->request->data['submit'] == 'Add') {
						

	// 					// add an additional row to userContact
	// 					$newRow = array(
	// 						'value' => '',
	// 						'preferred' => 1
	// 					);
	// 					$this->request->data['UserContact'][] = $newRow;

	// 				} else if ($this->request->data['submit'] == 'Save') {
	// 					$postData = $this->data['SecurityUser'];
					
	// 					if($this->SecurityUser->doValidate($postData)) {
	// 						$name = $postData['first_name'] . ' ' . $postData['last_name'];
	// 						$this->Utility->alert($name . ' has been updated successfully.');
	// 						$this->redirect(array('action' => 'usersView', $userId));
	// 					} 
	// 				}
	// 			} else {
	// 				$this->request->data = $data;
	// 			}

	// 			// need to handle contact options
	// 			$contactTypeOptions = $this->SecurityUser->UserContact->ContactType->getOptions();
	// 			$contactOptionOptions = $this->SecurityUser->UserContact->ContactType->ContactOption->getOptions();

	// 			$this->set(compact('data', 'contactTypeOptions', 'contactOptionOptions'));
	// 			$this->set('statusOptions', $this->SecurityUser->getStatus());
	// 			$this->Navigation->addCrumb(ModelHelper::getName($data['SecurityUser']));
	// 		}
	// 	} else {
	// 		$this->redirect(array('action' => 'users'));
	// 	}
	// }
	
	// public function usersAdd() {
	// 	$this->Navigation->addCrumb('Users', array('controller' => 'Security', 'action' => 'users'));
	// 	$this->Navigation->addCrumb('Add User');
		
	// 	if($this->request->is('post')) {
	// 		$data = $this->data;
	// 		$this->SecurityUser->set($data);
	// 		if($this->SecurityUser->validates()) {
	// 			$result =  $this->SecurityUser->save($data);
	// 			$userId = $result['SecurityUser']['id'];
	// 			$name = trim($data['SecurityUser']['first_name'] . ' ' . $data['SecurityUser']['last_name']);
	// 			$this->Utility->alert($name . ' has been added successfully.');
	// 			$this->redirect(array('action' => 'usersView', $userId));
	// 		}
	// 	}
	// }
	
	public function usersSearch() {
		$searchString = $this->params->query['searchString'];
		$searchType = isset($this->params['pass'][0]) ? $this->params['pass'][0] : 0;
		$params = array('limit' => 100);
		
		if($searchType==0) { // only search by identification no and display name
			$this->autoRender = false;
			$obj = $this->SecurityUser->search($searchType, $searchString);
			$name = $obj ? $obj['SecurityUser']['first_name'] . ' ' . $obj['SecurityUser']['last_name'] : '';
			$result = array();
			if(empty($name)) {
				$result['type'] = 'error';
			} else {
				$result['type'] = 'ok';
				$result['id'] = $obj['SecurityUser']['id'];
				$result['name'] = $name;
			}
			return json_encode($result);
		} else if($searchType==1) { // search by identification or name and display rows
			$this->layout = 'ajax';
			$groupId = $this->params['pass'][1];
			$data = $this->SecurityUser->search($searchType, $searchString, $params);
			if($data) {
				foreach($data as &$user) {
					$obj = $user['SecurityUser'];
					$roleOptions = $this->SecurityRole->getRoleOptions($groupId, $obj['id'], true);
					$user['SecurityUser']['roles'] = $roleOptions;
				}
			}
			$this->set('search', $searchString);
			$this->set('data', $data);
		} else {
			$this->layout = 'ajax';
			$module = $this->params->query['module'];
			$data = $this->{$module}->search($searchString, $params);
			$this->set('search', $searchString);
			$this->set('module', $module);
			$this->set('data', $data);
		}
		$this->set('type', $searchType);
	}
	
}
