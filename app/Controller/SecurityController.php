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
		'Institution',
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
		//'Teachers.Teacher',
		'Staff.Staff',
		'Students.Student',
		'ConfigAttachment'
	);
	
	public $components = array(
		'LDAP'
	);
	
	public $modules = array(
		'SecurityGroup',
		'permissions' => 'SecurityRoleFunction',
		'roles' => 'SecurityRole',
		'SecurityUserAccess'
	);
	
	public function beforeFilter() {
		parent::beforeFilter();
		$this->renderFooter();
		$this->Auth->allow('login');
		$this->Auth->allow('login_remote');
		$this->Auth->allow('switchLanguage');
		
		if($this->action !== 'login' || $this->action !== 'logout') {
			$this->bodyTitle = 'Administration';
			$this->Navigation->addCrumb('Administration', array('controller' => 'Areas', 'action' => 'index', 'plugin' => false));
			$this->Navigation->addCrumb('Accounts & Security', array('controller' => $this->name, 'action' => 'users'));
		}
	}
	
	private function renderFooter() {
		if(!$this->Session->check('footer')){
			$this->Session->write('footer', $this->ConfigItem->getWebFooter());
		}
	}

	public function switchLanguage(){
	  	$this->autoRender = false;
		$lang = $this->ConfigItem->getValue('language');
		$showLanguage = $this->ConfigItem->getValue('language_menu');

		if (!$this->Auth->loggedIn()) {
			$languageList = array('ara', 'chi', 'eng', 'fre', 'rus', 'spa');
			if(isset($this->request->query['lang']) && in_array($this->request->query['lang'], $languageList)) {
				$lang = $this->request->query['lang'];
				setcookie('language', $lang, time()+(60*60*24*7), '/');
			} else if($showLanguage && isset($_COOKIE['language'])) {
				$lang = $_COOKIE['language'];
			}


			$userName = '';
			$userPassword = '';

			if(isset($this->request->query['username'])){
				$userName = $this->request->query['username'];
			}
			if(isset($this->request->query['userpassword'])){
				$userPassword = $this->request->query['userpassword'];
			}
			$this->Session->write('login.username', $userName);
		 	$this->Session->write('login.password', $userPassword);

			// Assign the language to session and configuration
			$this->Session->write('configItem.language', $lang);
		}
	}
	
    public function login() {
		$this->autoLayout = false;
		$lang = $this->ConfigItem->getValue('language');
		$showLanguage = $this->ConfigItem->getValue('language_menu');
		$lang = 'eng';
		if($this->Session->check('configItem.language')){
			$lang = $this->Session->read('configItem.language');
		}else{
			$this->Session->write('configItem.language', $lang);
		}

		$this->set('showLanguage', $showLanguage);
		// Assign the language to session and configuration
		if($this->request->is('post')) {
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
				
				
				if($this->ConfigItem->getValue('authentication_type') == 'LDAP'){
					
					$arrLdapConfig = $this->ConfigItem->getAllLDAPConfig();
					$settings = array_merge($this->data['SecurityUser'],$arrLdapConfig);
					$ldapverify = $this->LDAP->verifyUser($settings);
					if($ldapverify === true){
						$data = $this->SecurityUser->find('first', array('recursive' => 0, 'conditions' => array('SecurityUser.username' => $this->data['SecurityUser']['username'])));
						if(count($data['SecurityUser'])>0)
							$result = $this->Auth->login($data['SecurityUser']);
						else{
							$result = false;
							$errMsg = __("LDAP user is not a valid openemis user");
						}	
					}else{
						$result = false;
						$errMsg = $ldapverify;
					}
				}else{
					$result = $this->Auth->login();
					//Error Message to be used if login false;
					$errMsg = $this->Utility->getMessage("LOGIN_INVALID");
				}
				if($result) {
					if($this->Auth->user('status') == 1) {
						$this->log('[' . $username . '] Login successfully.', 'security');
						$userId = AuthComponent::user('id');
						$this->SecurityUser->updateLastLogin($userId);
						$this->AccessControl->init($userId);
						$this->registerSession();
						$this->redirect($this->Auth->redirect(array('controller' => 'Home')));
					} else if ($this->Auth->user('status') == 0) {
						$this->log('[' . $username . '] Account is not active.', 'security');
						$this->Session->setFlash($this->Utility->getMessage("LOGIN_USER_INACTIVE"));
					}
				} else {
					//$this->Session->setFlash($errMsg);
					//Use Standard Message regardless Ldap or Local Auth accdg to Umai
					$this->Session->setFlash($this->Utility->getMessage("LOGIN_INVALID"));
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
					
				}else{
					$ajaxLoginResult = $this->Auth->login();
					
				}
				//$ajaxLoginResult = $this->Auth->login();
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
				if($this->Auth->user()) { // user already login
					return $this->redirect(array('controller' => 'Home'));
				}
			} else { // ajax login
				$this->set('message', $this->Utility->getMessage('LOGIN_TIMEOUT'));
				$this->render('login_ajax');
			}
			// added cause need to overwrite AppController pre-assigned Session value
			$l10n = new L10n();
			$locale = $l10n->map($this->Session->read('configItem.language'));
			$catalog = $l10n->catalog($locale);
			$this->set('lang_locale', $locale);
			$this->set('lang_dir', $catalog['direction']);
					
			Configure::write('Config.language', $this->Session->read('configItem.language')); 
		}
		$username = $this->Session->check('login.username') ? $this->Session->read('login.username') : '';
		$password = $this->Session->check('login.password') ? $this->Session->read('login.password') : '';
		$this->set('username', $username);
		$this->set('password', $password);
		$this->set('selectedLang', $lang);
	
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
		if ($this->Session->check('configItem.language')) {
			$lang = $this->ConfigItem->getValue('language');
		}
		$this->Session->destroy();
		$this->Session->write('configItem.language', $lang);
		$this->redirect($redirect);
	}
	
	public function index() {
		$this->redirect(array('action' => 'users'));
	}
	
	public function registerSession(){
		// temp modified for translation
		if (!$this->Session->check('configItem.language')) {
			$this->Session->write('configItem.language', $this->ConfigItem->getValue('language'));
		}

		$this->Session->write('login.token', $this->SecurityUser->createToken());
		$this->Session->write('configItem.currency', $this->ConfigItem->getValue('currency'));
		$this->Session->write('footer', $this->ConfigItem->getWebFooter());
	}
	
	public function users() {
		App::uses('Sanitize', 'Utility');
		$this->Navigation->addCrumb('Users');
		
		$page = isset($this->params->named['page']) ? $this->params->named['page'] : 1;
		
		$selectedYear = "";
		$selectedProgramme = "";
		$searchField = "";
		$orderBy = 'SecurityUser.first_name';
		$order = 'asc';
		$prefix = 'SecurityUser.Search.%s';
		if($this->request->is('post')) {
			$searchField = Sanitize::escape(trim($this->data['SecurityUser']['SearchField']));
			if(isset($this->data['SecurityUser']['orderBy'])) {
				$orderBy = $this->data['SecurityUser']['orderBy'];
			}
			if(isset($this->data['SecurityUser']['order'])) {
				$order = $this->data['SecurityUser']['order'];
			}
			
			$this->Session->write(sprintf($prefix, 'SearchField'), $searchField);
			$this->Session->write(sprintf($prefix, 'order'), $order);
			$this->Session->write(sprintf($prefix, 'orderBy'), $orderBy);
		} else {
			$searchField = $this->Session->read(sprintf($prefix, 'SearchField'));
			
			if($this->Session->check(sprintf($prefix, 'orderBy'))) {
				$orderBy = $this->Session->read(sprintf($prefix, 'orderBy'));
			}
			if($this->Session->check(sprintf($prefix, 'order'))) {
				$order = $this->Session->read(sprintf($prefix, 'order'));
			}
		}
		$conditions = array('search' => $searchField, 'SecurityUser.super_admin' => 0);
		
		$this->paginate = array('limit' => 15, 'maxLimit' => 100, 'order' => sprintf('%s %s', $orderBy, $order));
		$data = $this->paginate('SecurityUser', $conditions);
		
		$this->set('searchField', $searchField);
		$this->set('page', $page);
		$this->set('orderBy', $orderBy);
		$this->set('order', $order);
		$this->set('data', $data);
	}
	
	public function usersView() {
		$this->Navigation->addCrumb('Users', array('controller' => 'Security', 'action' => 'users'));
		
		if(isset($this->params['pass'][0])) {
			$userId = $this->params['pass'][0];
			$this->Session->write('SecurityUserId', $userId);
			$this->SecurityUser->formatResult = true;
			$data = $this->SecurityUser->find('first', array('recursive' => 0, 'conditions' => array('SecurityUser.id' => $userId)));
			$data['groups'] = $this->SecurityGroupUser->getGroupsByUserId($userId);
			$data['access'] = $this->SecurityUserAccess->getAccess($userId);
			
			$allowEdit = false;
			if($this->Auth->user('super_admin')==1) {
				// if the user himself is a super admin, then allow edit
				$allowEdit = true;
			} else if($this->Auth->user('super_admin')==$data['super_admin']) {
				//$allowEdit = $this->SecurityGroupUser->isUserInSameGroup($this->Auth->user('id'), $userId);
				$allowEdit = $this->SecurityUser->isUserCreatedByCurrentLoggedUser($this->Auth->user('id'), $userId);//(currentLoggedUser, userBeingViewed)
			}
			$this->set('data', $data);
			$this->set('allowEdit', $allowEdit);
			$this->Navigation->addCrumb($data['first_name'] . ' ' . $data['last_name']);
		} else {
			$this->redirect(array('action' => 'users'));
		}
	}
	
	public function usersEdit() {
		$this->Navigation->addCrumb('Users', array('controller' => 'Security', 'action' => 'users'));
		if(isset($this->params['pass'][0])) {
			$userId = $this->params['pass'][0];
			$this->SecurityUser->formatResult = true;
			$data = $this->SecurityUser->find('first', array('recursive' => 0, 'conditions' => array('SecurityUser.id' => $userId)));
			$data['groups'] = $this->SecurityGroupUser->getGroupsByUserId($userId);
			$data['access'] = $this->SecurityUserAccess->getAccess($userId);
			$name = $data['first_name'] . ' ' . $data['last_name'];
			$allowEdit = false;
			if($this->Auth->user('super_admin')==1) {
				$allowEdit = true;
			} else if($this->Auth->user('super_admin')==$data['super_admin']) {
				//$allowEdit = $this->SecurityGroupUser->isUserInSameGroup($this->Auth->user('id'), $userId);
								$allowEdit = $this->SecurityUser->isUserCreatedByCurrentLoggedUser($this->Auth->user('id'), $userId);//(currentLoggedUser, userBeingViewed)
			}
			
			if(!$allowEdit) {
				$this->redirect(array('action' => 'users'));
			} else {
				if($this->request->is('post') || $this->request->is('put')) {
					$postData = $this->data['SecurityUser'];
					
					if($this->SecurityUser->doValidate($postData)) {
						$name = $postData['first_name'] . ' ' . $postData['last_name'];
						$this->Utility->alert($name . ' has been updated successfully.');
						$this->redirect(array('action' => 'usersView', $userId));
					} else {
						$data = array_merge($data, $postData);
					}
				}
				$this->set('data', $data);
				$this->set('statusOptions', $this->SecurityUser->getStatus());
				$this->Navigation->addCrumb($name);
			}
		} else {
			$this->redirect(array('action' => 'users'));
		}
	}
	
	public function usersAdd() {
		$this->Navigation->addCrumb('Users', array('controller' => 'Security', 'action' => 'users'));
		$this->Navigation->addCrumb('Add User');
		
		if($this->request->is('post')) {
			$data = $this->data;
			$this->SecurityUser->set($data);
			if($this->SecurityUser->validates()) {
				$result =  $this->SecurityUser->save($data);
				$userId = $result['SecurityUser']['id'];
				$name = trim($data['SecurityUser']['first_name'] . ' ' . $data['SecurityUser']['last_name']);
				$this->Utility->alert($name . ' has been added successfully.');
				$this->redirect(array('action' => 'usersView', $userId));
			}
		}
	}
	
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
	
	public function usersAccess() {
		$this->Navigation->addCrumb('Users', array('controller' => 'Security', 'action' => 'users'));
		if($this->Session->check('SecurityUserId')) {
			$userId = $this->Session->read('SecurityUserId');
			$this->SecurityUser->formatResult = true;
			$data = $this->SecurityUser->find('first', array('recursive' => 0, 'conditions' => array('SecurityUser.id' => $userId)));
			$data['access'] = $this->SecurityUserAccess->getAccess($userId);
			$name = $data['first_name'] . ' ' . $data['last_name'];
			$moduleOptions = array('Student' => __('Student'), /*'Teacher' => __('Teacher'), */'Staff' => __('Staff'));
			$this->set('data', $data);
			$this->set('moduleOptions', $moduleOptions);
			$this->Navigation->addCrumb($name);
		} else {
			$this->redirect(array('action' => 'users'));
		}
	}
	
	public function usersAccessAdd() {
		$modelMap = array('student_id' => 'Student', 'staff_id' => 'Staff');
		
		if ($this->request->is(array('post', 'put'))) {
			$postData = $this->data['SecurityUserAccess'];
			
			//$postData['table_id'] = '';
			$postData['table_name'] = $modelMap[$postData['table_id']];
			
			$this->SecurityUserAccess->save($postData);//die;
			
			return $this->redirect(array('action' => 'usersAccess'));
			//unset($postData['search']);
			//pr($postData);die;
			/*
			if($postData['type'] == 'Student'){
				if(empty($postData['student_id'])){
					//$this->Message->alert('UserAccess.add.failed');
					//return $this->redirect(array('action' => 'usersAccess'));
				}
				
				$postData['table_id'] = $postData['student_id'];
				$postData['table_name'] = $postData['type'];
				
				unset($postData['student_id']);
				unset($postData['type']);
			}else if($postData['type'] == 'Staff'){
				if(empty($postData['staff_id'])){
					$this->Message->alert('UserAccess.add.failed');
					//return $this->redirect(array('action' => 'usersAccess'));
				}
				
				$postData['table_id'] = $postData['staff_id'];
				$postData['table_name'] = $postData['type'];
				
				unset($postData['staff_id']);
				unset($postData['type']);
			}else{
				//$this->Message->alert('UserAccess.add.failed');
				//return $this->redirect(array('action' => 'usersAccess'));
			}
			
			if (!$this->SecurityUserAccess->isAccessExists($postData)) {
				$this->SecurityUserAccess->save($postData);
				$this->Message->alert('UserAccess.add.success');
				return $this->redirect(array('action' => 'usersAccess'));
			} else {
				$this->Message->alert('UserAccess.add.accessExists');
				return $this->redirect(array('action' => 'usersAccess'));
			}
			 * 
			 */
		}else{
			return $this->redirect(array('action' => 'usersAccess'));
		}
	}
	
	public function usersAccessDelete() {
		$this->autoRender = false;
		if (count($this->params['pass']) == 3) {
			$conditions = array(
				'security_user_id' => $this->params['pass'][0],
				'table_id' => $this->params['pass'][1],
				'table_name' => $this->params['pass'][2]
			);
			$this->SecurityUserAccess->deleteAll($conditions, false);
			$this->Message->alert('UserAccess.delete.success');
			return $this->redirect(array('action' => 'usersAccess'));
		}
	}

	public function autocomplete() {
		if ($this->request->is('ajax')) {
			$this->autoRender = false;
			$params = $this->params;
			$search = $params->query['term'];
			
			$model = 'Student';
			if(isset($params->query['model'])){
				$model = $params->query['model'];
			}
			
			$data = array();
			if($model == 'Student'){
				$list = $this->Student->autocomplete($search);
				foreach ($list as $obj) {
					$info = $obj['Student'];
					$data[] = array(
						'label' => sprintf('%s - %s %s', $info['identification_no'], $info['first_name'], $info['last_name']),
						'value' => array('table_id' => $info['id']) 
					);
				}
			}else{
				$list = $this->Staff->autocomplete($search);
				foreach ($list as $obj) {
					$info = $obj['Staff'];
					$data[] = array(
						'label' => sprintf('%s - %s %s', $info['identification_no'], $info['first_name'], $info['last_name']),
						'value' => array('table_id' => $info['id']) 
					);
				}
			}
			
			return json_encode($data);
		}
	}
	
}
