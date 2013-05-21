<?php
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
		'SecurityFunction',
		'SecurityUserRole',
		'SecurityRoleFunction',
		'SecurityRoleArea',
		'SecurityRoleInstitutionSite'
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
			if(!$this->RequestHandler->isAjax()) {
				if($this->Auth->login()) {
					if($this->Auth->user('status') == 1) {
						$userId = AuthComponent::user('id');
						$this->SecurityUser->updateLastLogin($userId);
						$this->AccessControl->init($userId);
						$this->registerSession();
						$this->redirect($this->Auth->redirect('home'));
					} else if ($this->Auth->user('status') == 0) {
						$this->Session->setFlash($this->Utility->getMessage("LOGIN_USER_INACTIVE"));
					}
				} else {
					$this->Session->setFlash($this->Utility->getMessage("LOGIN_INVALID"));
				}
			} else {
				$this->autoRender = false;
				$ajaxLoginResult = $this->Auth->login();
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
				}else{
					// Check if theres a query lang then use that
					$lang = (isset($this->request->query['lang'])) ? $this->request->query['lang'] : $this->ConfigItem->getValue('language');
					
					// Assign the language to session and configuration
					$this->Session->write('configItem.language', $lang);
				}
			} else { // ajax login
				// Check if session still exist
				if($this->Session->check('configItem.language')){
					// Check if theres a query lang then use that
					$lang = (isset($this->request->query['lang'])) ? $this->request->query['lang'] : $this->ConfigItem->getValue('language'); 
					
					// Assign the language to session and configuration
					$this->Session->write('configItem.language', $lang);
				}
				$this->set('message', $this->Utility->getMessage('LOGIN_TIMEOUT'));
				$this->render('login_ajax');
			}
			// added cause need to overwrite AppController pre-assigned Session value
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
		$this->Navigation->addCrumb('Users');
		$list = $this->SecurityUser->find('all', array(
			'order' => array('SecurityUser.super_admin DESC', 'SecurityUser.status DESC', 'SecurityUser.username')
		));
		
		$data = array();
		foreach($list as $key => $user) {
			$obj = $user['SecurityUser'];
			$roleIds = $this->SecurityUserRole->find('list', array(
				'fields' => array('SecurityUserRole.id', 'SecurityUserRole.security_role_id'),
				'conditions' => array('SecurityUserRole.security_user_id' => $obj['id'])));
			$roles = $this->SecurityRole->find('list', array('conditions' => array('SecurityRole.id' => array_values($roleIds))));
			$obj['roles'] = implode(', ', $roles);
			$data[$key] = $obj;
		}
		$this->set('data', $data);
	}
	
	public function usersView() {
		$this->Navigation->addCrumb('Users', array('controller' => 'Security', 'action' => 'users'));
		
		if(isset($this->params['pass'][0])) {
			$userId = $this->params['pass'][0];
			$this->SecurityUser->formatResult = true;
			$data = $this->SecurityUser->find('first', array('recursive' => 0, 'conditions' => array('SecurityUser.id' => $userId)));
			$roleIds = $this->SecurityUserRole->find('list', array(
				'fields' => array('SecurityUserRole.security_role_id'),
				'conditions' => array('SecurityUserRole.security_user_id' => $userId)
			));
			$data['roles'] = !empty($roleIds) ? $this->SecurityRoleFunction->getModules($roleIds) : $roleIds;
			
			$allowEdit = $this->Auth->user('super_admin')==1 || $this->Auth->user('super_admin')==$data['super_admin'];
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
			$name = $data['first_name'] . ' ' . $data['last_name'];
			$allowEdit = $this->Auth->user('super_admin')==1 || $this->Auth->user('super_admin')==$data['super_admin'];
			
			if(!$allowEdit) {
				$this->redirect(array('action' => 'users'));
			} else {
				if($this->request->is('post') || $this->request->is('put')) {
					$postData = $this->data['SecurityUser'];
					$postRoles = isset($this->data['SecurityRole']) ? $this->data['SecurityRole'] : array();
					
					if($this->SecurityUser->doValidate($postData)) {
						$this->SecurityUserRole->deleteAll(array('SecurityUserRole.security_user_id' => $userId));
						$userRoles = array();
						foreach($postRoles as $roleId => $value) {
							$userRoles[] = array('security_user_id' => $userId, 'security_role_id' => $roleId);
						}
						$this->SecurityUserRole->saveMany($userRoles);
						$name = $postData['first_name'] . ' ' . $postData['last_name'];
						$this->Utility->alert($name . ' has been updated successfully.');
						$this->redirect(array('action' => 'usersView', $userId));
					} else {
						$data = array_merge($data, $postData);
					}
				}
				$roles = array();
				$roleList = $this->SecurityRoleFunction->getModules();
				$roleIds = $this->SecurityUserRole->find('list', array(
					'fields' => array('SecurityUserRole.security_role_id'),
					'conditions' => array('SecurityUserRole.security_user_id' => $userId)
				));
				$userRoleList = !empty($roleIds) ? $this->SecurityRoleFunction->getModules($roleIds) : $roleIds;
				
				$roles[0] = $userRoleList;
				foreach($userRoleList as $key => $value) {
					unset($roleList[$key]);
				}
				$roles[1] = $roleList;
				$this->set('roles', $roles);
				$this->set('data', $data);
				$this->set('statusOptions', $this->SecurityUser->status);
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
			$roles = isset($data['SecurityRole']) ? $data['SecurityRole'] : array();
			unset($data['SecurityRole']);
			
			$this->SecurityUser->set($data);
			if($this->SecurityUser->validates()) {
				if(sizeof($roles) == 0) {
					$this->Utility->alert('You need to assign a role to the user', array('type' => 'error'));
				} else {
					$result =  $this->SecurityUser->save($data);
					$userId = $result['SecurityUser']['id'];
					
					$userRoles = array();
					foreach($roles as $roleId) {
						$userRoles[] = array('security_user_id' => $userId, 'security_role_id' => $roleId);
					}
					$this->SecurityUserRole->saveMany($userRoles);
					$name = trim($data['SecurityUser']['first_name'] . ' ' . $data['SecurityUser']['last_name']);
					$this->Utility->alert($name . ' has been added successfully.');
					$this->redirect(array('action' => 'users'));
				}
			}
		}
		$roleList = $this->SecurityRoleFunction->getModules();
		$this->set('roles', $roleList);
	}
	
	public function roles() {
		$this->Navigation->addCrumb('Roles');
		$list = $this->SecurityRole->findOptions();
		$this->set('list', $list);
	}
	
	public function rolesEdit() {
		$this->Navigation->addCrumb('Edit Roles');
		
		if($this->request->is('post')) {
			$data = $this->data;
			$this->SecurityRole->removeUnnamed(&$data);
			$this->SecurityRole->saveMany($data['SecurityRole']);
			$this->redirect(array('action' => 'roles'));
		}
		
		$list = $this->SecurityRole->findOptions();
		$this->set('list', $list);
	}
	
	public function rolesAdd() {
		$this->layout = 'ajax';
		$order = $this->params->query['order'] + 1;
		$this->set('order', $order);

		if(isset($this->params->query['type'])) {
			$type = $this->params->query['type'];
			$levelList = array();
			$nameOptions = array();
			$exclude = isset($this->params->query['exclude']) ? $this->params->query['exclude'] : array();
			if($type==='areas') {
				$levelOptions = $this->AreaLevel->find('list', array('order' => array('AreaLevel.level')));
				if(!empty($levelOptions)) {
					$conditions = array('Area.area_level_id' => key($levelOptions), 'Area.visible' => 1, 'Area.id NOT' => $exclude);
					$nameOptions = $this->Area->findList(array('conditions' => $conditions));
					if(empty($nameOptions)) {
						$nameOptions[] = '--'.__('No Areas').'--';
					}
				}
			} else {
				$levelOptions = $this->Institution->find('list', array('order' => array('Institution.name')));
				if(!empty($levelOptions)) {
					$conditions = array('InstitutionSite.institution_id' => key($levelOptions), 'InstitutionSite.id NOT' => $exclude);
					$nameOptions = $this->InstitutionSite->find('list', array('conditions' => $conditions, 'order' => array('InstitutionSite.name')));
					if(empty($nameOptions)) {
						$nameOptions[] = '--'.__('No Institution Sites').'--';
					}
				}
			}
			$this->set('type', $type);
			$this->set('levelOptions', $levelOptions);
			$this->set('nameOptions', $nameOptions);
			$this->set('roleId', $this->params->query['roleId']);
			$this->render('roles_add_area');
		}
	}
	
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
	
	public function roleAreas() {
		$this->Navigation->addCrumb('Role - Area Restricted');
		
		if(isset($this->params['pass'][0])) {
			$roleId = $this->params['pass'][0];
			$roleList = $this->SecurityRole->findList(true);
			
			if(array_key_exists($roleId, $roleList)) {
				$this->set('roleId', $roleId);
				$this->set('roleOptions', $roleList);
				$areaConditions = array('SecurityRoleArea.security_role_id' => $roleId);
				$areaLevelList = $this->AreaLevel->find('list', array('order' => array('AreaLevel.level')));
				$areaList = $this->SecurityRoleArea->fetchAreas($areaLevelList, $areaConditions);
				
				$siteConditions = array('SecurityRoleInstitutionSite.security_role_id' => $roleId);
				$institutionList = $this->Institution->find('list', array('order' => array('Institution.name')));
				$siteList = $this->SecurityRoleInstitutionSite->fetchSites($institutionList, $siteConditions);
				
				$this->set('areaList', $areaList);
				$this->set('siteList', $siteList);
			} else {
				$this->redirect(array('action' => 'roles'));
			}
		} else {
			$this->redirect(array('action' => 'roles'));
		}
	}
	
	public function roleAreasEdit() {
		$this->Navigation->addCrumb('Edit Role - Area Restricted');
		
		if(isset($this->params['pass'][0])) {
			$roleId = $this->params['pass'][0];
			$roleList = $this->SecurityRole->findList(true);
			
			if(array_key_exists($roleId, $roleList)) {
				$this->set('roleId', $roleId);
				$this->set('roleOptions', $roleList);
				
				$areaConditions = array('SecurityRoleArea.security_role_id' => $roleId);
				$siteConditions = array('SecurityRoleInstitutionSite.security_role_id' => $roleId);
				if($this->request->is('post')) {
					$this->SecurityRoleArea->deleteAll($areaConditions);
					if(isset($this->data['SecurityRoleArea'])) {
						$areas = $this->data['SecurityRoleArea'];						
						$this->SecurityRoleArea->filterData($areas);
						$this->SecurityRoleArea->saveMany($areas);
					}
					
					$this->SecurityRoleInstitutionSite->deleteAll($siteConditions);
					if(isset($this->data['SecurityRoleInstitutionSite'])) {
						$sites = $this->data['SecurityRoleInstitutionSite'];
						$this->SecurityRoleInstitutionSite->filterData($sites);
						$this->SecurityRoleInstitutionSite->saveMany($sites);
					}
					$this->Utility->alert('Permissions have been saved successfully.');
					$this->redirect(array('action' => 'roleAreas', $roleId));
				}
				
				$areaLevelList = $this->AreaLevel->find('list', array('order' => array('AreaLevel.level')));
				$areaList = $this->SecurityRoleArea->fetchAreas($areaLevelList, $areaConditions);
				
				$institutionList = $this->Institution->find('list', array('order' => array('Institution.name')));
				$siteList = $this->SecurityRoleInstitutionSite->fetchSites($institutionList, $siteConditions);
				
				$this->set('areaList', $areaList);
				$this->set('siteList', $siteList);
			} else {
				$this->redirect(array('action' => 'roles'));
			}
		} else {
			$this->redirect(array('action' => 'roles'));
		}
	}
	
	public function loadOptionList() {
		$this->autoRender = false;
		$parentId = $this->params->query['parentId'];
		$exclude = $this->params->query['exclude'];
		$type = $this->params->query['type'];
		$options = array();
		$conditions = array();
		if($type==='areas') {
			$conditions['Area.area_level_id'] = $parentId;
			$conditions['Area.id NOT'] = $exclude;
			$options = $this->Area->findList(array('conditions' => $conditions));
		} else {
			$conditions['InstitutionSite.institution_id'] = $parentId;
			$conditions['InstitutionSite.id NOT'] = $exclude;
			$options = $this->InstitutionSite->find('list', array('conditions' => $conditions, 'order' => array('InstitutionSite.name')));
		}
		if(empty($options)) {
			$options[] = $type==='areas' ? '-- '.__('No Areas').' --' : '-- '.__('No Institution Sites').' --';
		}
		echo json_encode($options);
	}
	
	public function permissions() {
		$this->Navigation->addCrumb('Permissions');
		
		$operations = $this->AccessControl->operations;
		$roles = $this->SecurityRole->findList();
		$roleId = isset($this->params['pass'][0]) ? $this->params['pass'][0] : key($roles);
		$permissions = $this->SecurityFunction->getPermissions($roleId, $operations);
		
		$this->set('_operations', $operations);
		$this->set('selectedRole', $roleId);
		$this->set('roles', $roles);
		$this->set('permissions', $permissions);
	}
	
	public function permissionsEdit() {
		$roles = $this->SecurityRole->findList();
		$roleId = isset($this->params['pass'][0]) ? $this->params['pass'][0] : key($roles);
		if($this->request->is('get')) {
			$this->Navigation->addCrumb('Edit Permissions');
			
			$operations = $this->AccessControl->operations;	
			$permissions = $this->SecurityFunction->getPermissions($roleId, $operations);
			
			$this->set('_operations', $operations);
			$this->set('selectedRole', $roleId);
			$this->set('roles', $roles);
			$this->set('permissions', $permissions);
		} else {
			$data = $this->data['SecurityRoleFunction'];
			$this->SecurityRoleFunction->saveAll($data);
			$this->redirect(array('action' => 'permissions', $roleId));
		}
	}
}
