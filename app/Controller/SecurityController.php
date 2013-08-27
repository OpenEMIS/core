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
		'Teachers.Teacher',
		'Staff.Staff',
		'Students.Student'
	);
	public $components = array(
		'LDAP'
	);
	public function beforeFilter() {
		parent::beforeFilter();
		$this->renderFooter();
		$this->Auth->allow('login');
		
		if($this->action !== 'login' || $this->action !== 'logout') {
			$this->bodyTitle = 'Settings';
			$this->Navigation->addCrumb('Settings', array('controller' => 'Setup', 'action' => 'index'));
		}
	}
	
	private function renderFooter() {
		if(!$this->Session->check('footer')){
			$val = $this->ConfigItem->getValue('version');
			
			$results = $this->ConfigItem->find('all', array(
				'conditions' => array('name' => array('footer', 'version'))
			));
			
			$values = array('footer' => '', 'version' => '0');
			foreach ($results as $element) {
				if($element['ConfigItem']['name'] === 'version'){
					$values['version'] = $element['ConfigItem']['value'];
				}
				
				if($element['ConfigItem']['name'] === 'footer'){
					$values['footer'] = $element['ConfigItem']['value'];
				}
			}
			
			$this->Session->write('footer', $values['footer'].' | '.$values['version']);
		}
		
	}
	
    public function login() {
		$this->autoLayout = false;
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
						$this->redirect($this->Auth->redirect('home'));
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
					$this->redirect($this->Auth->redirect('home'));
				}
			} else { // ajax login
				$this->set('message', $this->Utility->getMessage('LOGIN_TIMEOUT'));
				$this->render('login_ajax');
			}
			// added cause need to overwrite AppController pre-assigned Session value
            $lang = (isset($this->request->query['lang'])) ? $this->request->query['lang'] : $this->ConfigItem->getValue('language');

            // Assign the language to session and configuration
            $this->Session->write('configItem.language', $lang);

			$l10n = new L10n();
			$locale = $l10n->map($this->Session->read('configItem.language'));
			$catalog = $l10n->catalog($locale);
			$this->set('lang_locale', $locale);
			$this->set('lang_dir', $catalog['direction']);
					
			Configure::write('Config.language', $this->Session->read('configItem.language')); 
		}
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
		$this->Session->write('configItem.currency', $this->ConfigItem->getValue('currency'));
		$this->Session->write('footer', $this->ConfigItem->getValue('footer').' | '.$this->ConfigItem->getValue('version'));
	}
	
	public function users() {
		App::uses('Sanitize', 'Utility');
		$this->Navigation->addCrumb('List of Users');
		
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
				$allowEdit = $this->SecurityGroupUser->isUserInSameGroup($this->Auth->user('id'), $userId);
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
				$allowEdit = $this->SecurityGroupUser->isUserInSameGroup($this->Auth->user('id'), $userId);
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
			if($this->request->is('post') || $this->request->is('put')) {
				$postData = $this->data['SecurityUserAccess'];
				unset($postData['SearchField']);
				if($postData['table_id'] != 0) {
					if(!$this->SecurityUserAccess->isAccessExists($postData)) {
						$this->SecurityUserAccess->save($postData);
						$this->Utility->alert($this->Utility->getMessage('SECURITY_ACCESS_LINKED'));
					} else {
						$this->Utility->alert($this->Utility->getMessage('SECURITY_ACCESS_EXISTS'), array('type' => 'error'));
					}
				}
			}
			$userId = $this->Session->read('SecurityUserId');
			$this->SecurityUser->formatResult = true;
			$data = $this->SecurityUser->find('first', array('recursive' => 0, 'conditions' => array('SecurityUser.id' => $userId)));
			$data['access'] = $this->SecurityUserAccess->getAccess($userId);
			$name = $data['first_name'] . ' ' . $data['last_name'];
			$moduleOptions = array('Student' => __('Student'), 'Teacher' => __('Teacher'), 'Staff' => __('Staff'));
			$this->set('data', $data);
			$this->set('moduleOptions', $moduleOptions);
			$this->Navigation->addCrumb($name);
		} else {
			$this->redirect(array('action' => 'users'));
		}
	}
	
	public function usersDeleteAccess() {
		if($this->request->is('ajax')) {
			$this->autoRender = false;
			if(count($this->params['pass']) == 3) {
				$conditions = array(
					'security_user_id' => $this->params['pass'][0],
					'table_id' => $this->params['pass'][1],
					'table_name' => $this->params['pass'][2]
				);
				$this->SecurityUserAccess->deleteAll($conditions, false);
			}
		}
	}
	
	public function usersAddAdmin() {
		$this->layout = 'ajax';
		$index = $this->params->query['index'];
		
		$this->set('index', $index);
	}
	
	public function groups() {
		App::uses('Sanitize', 'Utility');
		$this->Navigation->addCrumb('List of Groups');
		
		$page = isset($this->params->named['page']) ? $this->params->named['page'] : 1;
		
		$selectedYear = "";
		$selectedProgramme = "";
		$searchField = "";
		$orderBy = 'SecurityGroup.name';
		$order = 'asc';
		$prefix = 'SecurityGroup.Search.%s';
		if($this->request->is('post')) {
			$searchField = Sanitize::escape(trim($this->data['SecurityGroup']['SearchField']));
			if(isset($this->data['SecurityGroup']['orderBy'])) {
				$orderBy = $this->data['SecurityGroup']['orderBy'];
			}
			if(isset($this->data['SecurityGroup']['order'])) {
				$order = $this->data['SecurityGroup']['order'];
			}
		}
		$conditions = array(
			'search' => $searchField, 
			'super_admin' => $this->Auth->user('super_admin')==1,
			'user_id' => $this->Auth->user('id')
		);
		
		$this->paginate = array('limit' => 15, 'maxLimit' => 100, 'order' => sprintf('%s %s', $orderBy, $order));
		$data = $this->paginate('SecurityGroup', $conditions);
		
		foreach($data as &$group) {
			$obj = $group['SecurityGroup'];
			$count = $this->SecurityGroupUser->find('count', array('conditions' => array('SecurityGroupUser.security_group_id' => $obj['id'])));
			$group['SecurityGroup']['count'] = $count;
		}
		
		$this->set('searchField', $searchField);
		$this->set('page', $page);
		$this->set('orderBy', $orderBy);
		$this->set('order', $order);
		$this->set('data', $data);
		$this->set('groupCount', $this->SecurityGroup->paginateCount($conditions));
	}
	
	public function groupsAddAccessOptions() {
		$this->layout = 'ajax';
		
		$type = $this->params['pass'][0];
		$index = $this->params->query['index'];
		$exclude = isset($this->params->query['exclude']) ? $this->params->query['exclude'] : array();
		
		$levelOptions = array();
		$valueOptions = array();
		$model = $type==='areas' ? $this->Area : $this->InstitutionSite;
		$levelOptions = $model->getGroupAccessList($exclude);
		
		$emptyOption = '-- ' . __('No records') . ' --';
		
		if(!empty($levelOptions)) {
			$parentId = key($levelOptions);
			$valueOptions = $model->getGroupAccessValueList($parentId, $exclude);
			if(empty($valueOptions)) {
				$valueOptions = array('0' => $emptyOption);
			}
		} else {
			$levelOptions = array('0' => $emptyOption);
		}
		
		$this->set('index', $index);
		$this->set('type', $type);
		$this->set('levelOptions', $levelOptions);
		$this->set('valueOptions', $valueOptions);
	}
	
	public function groupsLoadValueOptions() {
		$this->layout = 'ajax';
		$type = $this->params['pass'][0];
		$parentId = $this->params->query['parentId'];
		$exclude = isset($this->params->query['exclude']) ? $this->params->query['exclude'] : array();
		$model = $type==='areas' ? $this->Area : $this->InstitutionSite;
		$emptyOption = '-- ' . __('No records') . ' --';
		
		$valueOptions = $model->getGroupAccessValueList($parentId, $exclude);
		if(empty($valueOptions)) {
			$valueOptions = array('0' => $emptyOption);
		}
		$this->set('valueOptions', $valueOptions);
	}
	
	public function groupsAddValidate() {
		$this->autoRender = false;
		$name = trim($this->params->query['name']);
		
		$msg = '';
		$result = array('type' => 'error');
		if(empty($name)) {
			$msg = $this->Utility->getMessage('SECURITY_GRP_NO_NAME');
		} else {
			$found = $this->SecurityGroup->field('id', array('SecurityGroup.name' => $name));
			if($found) {
				$msg = $this->Utility->getMessage('SECURITY_GRP_NAME_EXISTS');
			} else {
				$result['type'] = 'ok';
			}
		}
		$result['msg'] = $msg;
		return json_encode($result);
	}
	
	public function groupsAdd() {
		$this->Navigation->addCrumb('Add Group');
		
		if($this->request->is('post')) {
			$groupData = $this->data['SecurityGroup'];
			$groupObj = $this->SecurityGroup->save($groupData);
			if($groupObj) {
				$groupId = $groupObj['SecurityGroup']['id'];
				
				// Group Users
				if(isset($this->data['SecurityGroupUser'])) {
					$userData = $this->data['SecurityGroupUser'];
					$role = $this->SecurityRole->getGroupAdministratorRole();
					foreach($userData as &$user) {
						$user['security_group_id'] = $groupId;
						$user['security_role_id'] = $role['SecurityRole']['id'];
					}
					$this->SecurityGroupUser->saveMany($userData);
				}
				
				// Group Areas
				if(isset($this->data['SecurityGroupArea'])) {
					$areaData = $this->data['SecurityGroupArea'];
					$this->SecurityGroupArea->saveGroupAccess($groupId, $areaData);
				}
				
				// Group Institution Sites
				if(isset($this->data['SecurityGroupInstitutionSite'])) {
					$siteData = $this->data['SecurityGroupInstitutionSite'];
					$this->SecurityGroupInstitutionSite->saveGroupAccess($groupId, $siteData);
				}
				$this->redirect(array('action' => 'groupsView', $groupId));
			}
		}
	}
	
	public function groupsView() {
		$this->Navigation->addCrumb('Groups', array('controller' => 'Security', 'action' => 'groups'));
		
		if(isset($this->params['pass'][0])) {
			$groupId = $this->params['pass'][0];
			$data = $this->SecurityGroup->find('first', array('conditions' => array('SecurityGroup.id' => $groupId)));
			if($data) {
				$this->Navigation->addCrumb($data['SecurityGroup']['name']);
				$areas = $this->SecurityGroupArea->getAreas($groupId);
				$sites = $this->SecurityGroupInstitutionSite->getSites($groupId);
				$systemRoles = $this->SecurityRole->getRoles(0);
				$userRoles = $this->SecurityRole->getRoles($groupId);
				$roles = array('system' => $systemRoles, 'user' => $userRoles);
				
				foreach($roles as &$roleList) {
					foreach($roleList as &$role) {
						$role['count'] = $this->SecurityGroupUser->find('count', array(
							'conditions' => array('SecurityGroupUser.security_group_id' => $groupId, 'SecurityGroupUser.security_role_id' => $role['id'])
						));
					}
				}
				$data['SecurityGroup']['areas'] = $areas;
				$data['SecurityGroup']['sites'] = $sites;
				$data['SecurityRole'] = $roles;
				
				$this->set('data', $data);
			} else {
				$this->redirect(array('action' => 'groups'));
			}
		} else {
			$this->redirect(array('action' => 'groups'));
		}
	}
	
	public function groupsEdit() {
		$this->Navigation->addCrumb('Edit Group Details');
		
		if(isset($this->params['pass'][0])) {
			$groupId = $this->params['pass'][0];
			
			if($this->request->is('post')) {
				$this->SecurityGroup->save($this->data['SecurityGroup']);
				$areaData = isset($this->data['SecurityGroupArea']) ? $this->data['SecurityGroupArea'] : array();
				$siteData = isset($this->data['SecurityGroupInstitutionSite']) ? $this->data['SecurityGroupInstitutionSite'] : array();
				$this->SecurityGroupArea->saveGroupAccess($groupId, $areaData);
				$this->SecurityGroupInstitutionSite->saveGroupAccess($groupId, $siteData);
				
				$this->redirect(array('action' => 'groupsView', $groupId));
			}
			
			$data = $this->SecurityGroup->find('first', array('conditions' => array('SecurityGroup.id' => $groupId)));
			if($data) {
				$areas = $this->SecurityGroupArea->getAreas($groupId);
				$sites = $this->SecurityGroupInstitutionSite->getSites($groupId);
				$systemRoles = $this->SecurityRole->getRoles(0);
				$userRoles = $this->SecurityRole->getRoles($groupId);
				$roles = array('system' => $systemRoles, 'user' => $userRoles);
				
				foreach($roles as &$roleList) {
					foreach($roleList as &$role) {
						$role['count'] = $this->SecurityGroupUser->find('count', array(
							'conditions' => array('SecurityGroupUser.security_group_id' => $groupId, 'SecurityGroupUser.security_role_id' => $role['id'])
						));
					}
				}
				$data['SecurityGroup']['areas'] = $areas;
				$data['SecurityGroup']['sites'] = $sites;
				$data['SecurityRole'] = $roles;
				
				$this->set('data', $data);
			} else {
				$this->redirect(array('action' => 'groups'));
			}
		} else {
			$this->redirect(array('action' => 'groups'));
		}
	}
	
	public function groupsRolesAdd() {
		$this->Navigation->addCrumb('Add Group Roles');
		
		if(isset($this->params['pass'][0])) {
			$groupId = $this->params['pass'][0];
			$data = $this->SecurityGroup->find('first', array('conditions' => array('SecurityGroup.id' => $groupId)));
			if($data) {
				$this->set('data', $data);
				
			} else {
				$this->redirect(array('action' => 'groups'));
			}
		} else {
			$this->redirect(array('action' => 'groups'));
		}
	}
	
	public function groupsUserAdd() {
		if($this->request->is('post')) {
			$data = $this->data['SecurityGroupUser'];
			$groupId = $data['security_group_id'];
			if($this->SecurityGroupUser->save($data)) {
				$this->Utility->alert($this->Utility->getMessage('SECURITY_GRP_USER_ADD'));
			}
			$this->redirect(array('action' => 'groupsUsers', $groupId, 'edit'));
		}
	}
	
	public function groupsUserRemove() {
		if($this->RequestHandler->isAjax()) {
			$this->autoRender = false;
			$groupId = $this->params['pass'][0];
			$roleId = $this->params['pass'][1];
			$userId = $this->params['pass'][2];
			
			$this->SecurityGroupUser->deleteAll(array(
				'SecurityGroupUser.security_group_id' => $groupId,
				'SecurityGroupUser.security_user_id' => $userId,
				'SecurityGroupUser.security_role_id' => $roleId
			), false);
		}
	}
	
	public function groupsUsers() {
		$this->Navigation->addCrumb('Group Users');
		
		if(isset($this->params['pass'][0])) {
			$groupId = $this->params['pass'][0];
			$group = $this->SecurityGroup->find('first', array('conditions' => array('SecurityGroup.id' => $groupId)));
			if($group) {
				$data = $this->SecurityGroupUser->getUsers($groupId);
				$this->set('group', $group['SecurityGroup']);
				$this->set('data', $data);
				
				if(isset($this->params['pass'][1]) && $this->params['pass'][1] === 'edit') {
					$this->render('groupsUsersEdit');
				}
			} else {
				$this->redirect(array('action' => 'groups'));
			}
		} else {
			$this->redirect(array('action' => 'groups'));
		}
	}
	
	public function roles() {
		$this->Navigation->addCrumb('Roles');
		
		$systemRoles = $this->SecurityRole->getRoles(array(0, -1));
		$isSuperUser = $this->Auth->user('super_admin')==1;
		$groupOptions = $this->SecurityGroup->getGroupOptions($isSuperUser ? false : $this->Auth->user('id'));
		$userRoles = array();
		$selectedGroup = 0;
		
		if(!empty($groupOptions)) {
			if(isset($this->params['pass'][0])) {
				$groupId = $this->params['pass'][0];
				$selectedGroup = array_key_exists($groupId, $groupOptions) ? $groupId : key($groupOptions);
			} else {
				$selectedGroup = key($groupOptions);
			}
			$userRoles = $this->SecurityRole->getRoles($selectedGroup);
		}
		
		$this->set('systemRoles', $systemRoles);
		$this->set('userRoles', $userRoles);
		$this->set('groupOptions', $groupOptions);
		$this->set('selectedGroup', $selectedGroup);
	}
	
	public function rolesEdit() {
		$this->Navigation->addCrumb('Edit Roles');
		
		$systemRoles = $this->SecurityRole->getRoles(array(0, -1));
		$isSuperUser = $this->Auth->user('super_admin')==1;
		$groupOptions = $this->SecurityGroup->getGroupOptions($isSuperUser ? false : $this->Auth->user('id'));
		$userRoles = array();
		$selectedGroup = 0;
		
		if(!empty($groupOptions)) {
			if(isset($this->params['pass'][0])) {
				$groupId = $this->params['pass'][0];
				$selectedGroup = array_key_exists($groupId, $groupOptions) ? $groupId : key($groupOptions);
			} else {
				$selectedGroup = key($groupOptions);
			}
			$userRoles = $this->SecurityRole->getRoles($selectedGroup);
		}
		
		if($this->request->is('post')) {
			$data = $this->data;
			$groupId = $this->params['pass'][0];
			$this->SecurityRole->removeUnnamed(&$data);
			$this->SecurityRole->saveMany($data['SecurityRole']);
			$this->redirect(array('action' => 'roles', $groupId));
		}
		
		$this->set('systemRoles', $systemRoles);
		$this->set('userRoles', $userRoles);
		$this->set('groupOptions', $groupOptions);
		$this->set('selectedGroup', $selectedGroup);
	}
	
	public function rolesAdd() {
		$this->layout = 'ajax';
		$size = $this->params->query['size'];
		$order = $this->params->query['order'] + 1;
		$groupId = $this->params->query['groupId'];
		$this->set('size', $size);
		$this->set('order', $order);
		$this->set('groupId', $groupId);
	}
	
	/*
	public function roleUsers() {
		$this->Navigation->addCrumb('Role Assignment');
		
		if(isset($this->params['pass'][0])) {
			$roleId = $this->params['pass'][0];
			$roleList = $this->SecurityRole->findList(true); // TODO: must be able to search for non-visible records

			if(array_key_exists($roleId, $roleList)) {
				$userIds = $this->SecurityUserRole->find('list', array(
					'fields' => array('SecurityUserRole.id', 'SecurityUserRole.security_user_id'),
					'conditions' => array('SecurityUserRole.security_role_id' => $roleId)
				));
				
				$users = $this->SecurityUser->find('all', array(
					'recursive' => 0,
					'conditions' => array('SecurityUser.id' => array_values($userIds), 'SecurityUser.super_admin' => 0)
				));
				
				$data = $this->SecurityUser->formatArray($users);
				$this->set('roleId', $roleId);
				$this->set('roleOptions', $roleList);
				$this->set('data', $data);
			} else {
				$this->redirect(array('action' => 'roles'));
			}
		} else {
			$this->redirect(array('action' => 'roles'));
		}
	}
	
	public function roleUsersEdit() {
		$this->Navigation->addCrumb('Edit Role Assignment');
		
		if(isset($this->params['pass'][0])) {
			$roleId = $this->params['pass'][0];
			
			if($this->request->is('post')) {
				$data = array();
				$userList = $this->data['SecurityUser'];
				
				$this->SecurityUserRole->deleteAll(array('SecurityUserRole.security_role_id' => $roleId));
				foreach($userList as $userId => $value) {
					$data[] = array('security_user_id' => $userId, 'security_role_id' => $roleId);
				}
				$this->SecurityUserRole->saveMany($data);
				$this->redirect(array('action' => 'roleUsers', $roleId));
			}
			
			$roleList = $this->SecurityRole->findList(true);

			if(array_key_exists($roleId, $roleList)) {
				$userIds = $this->SecurityUserRole->find('list', array(
					'fields' => array('SecurityUserRole.id', 'SecurityUserRole.security_user_id'),
					'conditions' => array('SecurityUserRole.security_role_id' => $roleId)
				));
				$users = $this->SecurityUser->find('all', array('recursive' => 0, 'conditions' => array('SecurityUser.super_admin' => 0)));
				$data = array(0 => array(), 1 => array());
				foreach($users as $user) {
					$obj = $user['SecurityUser'];
					if(in_array($obj['id'], $userIds)) {
						$data[0][] = $obj;
					} else {
						$data[1][] = $obj;
					}
				}
				$this->set('roleId', $roleId);
				$this->set('roleOptions', $roleList);
				$this->set('data', $data);
				
			} else {
				$this->redirect(array('action' => 'roles'));
			}
		} else {
			$this->redirect(array('action' => 'roles'));
		}
	}
	*/
	
	public function permissions() {
		$this->Navigation->addCrumb('Permissions');
		
		if(isset($this->params['pass'][0])) {
			$selectedRole = $this->params['pass'][0];
			
			$isSuperUser = $this->Auth->user('super_admin')==1;
			$userId = $isSuperUser ? false : $this->Auth->user('id');
			$groupObj = $this->SecurityRole->getGroupName($selectedRole, $userId);
			
			$allowEdit = true;
			if(!$isSuperUser) {
				$userRoles = $this->SecurityGroupUser->getRolesByUserId($userId);
				foreach($userRoles as $obj) {
					if($obj['SecurityRole']['id'] === $selectedRole) {
						$allowEdit = false;
						break;
					}
				}
			}
			$roles = $groupObj ? $this->SecurityRole->getRoleOptions($groupObj['id']) : $this->SecurityRole->getRoleOptions(array(0, -1));
			$permissions = array();
			if($isSuperUser) {
				$permissions = $this->SecurityFunction->getPermissions($selectedRole, $isSuperUser);
			} else {
				$permissions = $this->SecurityFunction->getAllowedPermissions($selectedRole, $userId, $isSuperUser);
			}
			$this->set('_operations', $this->AccessControl->operations);
			$this->set('selectedRole', $selectedRole);
			$this->set('roles', $roles);
			$this->set('permissions', $permissions);
			$this->set('group', $groupObj);
			$this->set('allowEdit', $allowEdit);
		} else {
			$this->redirect(array('action' => 'roles'));
		}
	}
	
	public function permissionsEdit() {
		if(isset($this->params['pass'][0])) {
			$selectedRole = $this->params['pass'][0];
			
			if($this->request->is('get')) {
				$this->Navigation->addCrumb('Edit Permissions');
				
				$isSuperUser = $this->Auth->user('super_admin')==1;
				$userId = $isSuperUser ? false : $this->Auth->user('id');			
				$allowEdit = true;
				if(!$isSuperUser) {
					$userRoles = $this->SecurityGroupUser->getRolesByUserId($userId);
					foreach($userRoles as $obj) {
						if($obj['SecurityRole']['id'] === $selectedRole) {
							$allowEdit = false;
							break;
						}
					}
				}
				if($allowEdit) {
					$groupObj = $this->SecurityRole->getGroupName($selectedRole, $userId);
					$roles = $groupObj ? $this->SecurityRole->getRoleOptions($groupObj['id']) : $this->SecurityRole->getRoleOptions(array(0, -1));
					$permissions = array();
					if($isSuperUser) {
						$permissions = $this->SecurityFunction->getPermissions($selectedRole, $isSuperUser);
					} else {
						$permissions = $this->SecurityFunction->getAllowedPermissions($selectedRole, $userId, $isSuperUser);
					}
					
					$this->set('_operations', $this->AccessControl->operations);
					$this->set('selectedRole', $selectedRole);
					$this->set('roles', $roles);
					$this->set('permissions', $permissions);
					$this->set('group', $groupObj);
				} else {
					$this->redirect(array('action' => 'permissions', $selectedRole));
				}
			} else {
				$data = $this->data['SecurityRoleFunction'];
				$this->SecurityRoleFunction->saveAll($data);
				$this->redirect(array('action' => 'permissions', $selectedRole));
			}
		} else {
			$this->redirect(array('action' => 'roles'));
		}
	}
}
