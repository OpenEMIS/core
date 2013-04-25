<?php
class AccessControlComponent extends Component {
	private $controller;
	private $User;
	private $Role;
	private $Function;
	private $UserRole;
	private $RoleFunction;
	private $RoleArea;
	private $RoleInstitutionSite;
	public $ignoreList = array(
		'HOME' => array('index', 'details', 'detailsEdit', 'password'), 
		'SECURITY' => array('login', 'logout'), 
		'CONFIG' => array('getI18n', 'getJSConfig', 'fetchImage'),
		'STUDENTS' => array('viewStudent'),
		'TEACHERS' => array('viewTeacher'),
		'STAFF' => array('viewStaff')
	);
	public $operations = array('_view', '_edit', '_add', '_delete');
	
	private $modelMap = array(
		'User' => 'SecurityUser',
		'Role' => 'SecurityRole',
		'Function' => 'SecurityFunction',
		'UserRole' => 'SecurityUserRole',
		'RoleFunction' => 'SecurityRoleFunction',
		'RoleArea' => 'SecurityRoleArea',
		'RoleInstitutionSite' => 'SecurityRoleInstitutionSite',
        'Area' => 'Area'
	);
	
	public $components = array('Auth', 'Session', 'AreaHandler', 'Navigation', 'Utility');
	
	//called before Controller::beforeFilter()
	public function initialize(Controller $controller) {
		$this->controller =& $controller;
		foreach($this->modelMap as $model => $modelClass) {
			$this->{$model} = ClassRegistry::init($modelClass);
		}
		$this->setUserPermissions($this->Auth->user('id'));
	}
	
	//called after Controller::beforeFilter()
	public function startup(Controller $controller) {}
	
	//called after Controller::beforeRender()
	public function beforeRender(Controller $controller) {}
	
	//called after Controller::render()
	public function shutdown(Controller $controller) {}
	
	//called before Controller::redirect()
	public function beforeRedirect(Controller $controller, $url, $status = null, $exit = true) {}
	
	public function init($userId) {
		$this->setUserPermissions($userId);
		$this->loadAreas();
		$this->loadInstitutions($this->Session->read('AccessControl.areas'));
	}
	
	public function setUserPermissions($userId) {
		if($userId > 0) {
			$permissions = $this->getPermissions($userId);
			$this->Session->write('permissions', $permissions);
		} else {
			$this->Session->delete('permissions');
		}
	}
	
	public function getPermissions($userId) {
		$modelMap = $this->modelMap;
		$separator = ':';
		$permissions = array();
		$check = array();
		$apply = array();
		
		if($this->Auth->user('super_admin')==0) {
			$foreignKeyUserId = sprintf('%s.%s_id', $modelMap['UserRole'], Inflector::underscore($modelMap['User']));
			$foreignKeyRoleId = sprintf('%s.%s_id', $modelMap['RoleFunction'], Inflector::underscore($modelMap['Role']));
			$list = $this->UserRole->find('all', array('conditions' => array($foreignKeyUserId => $userId)));
			foreach($list as $obj) {
				$role = $obj[$modelMap['Role']];
				$roleFunctions = $this->RoleFunction->find('all', array('conditions' => array($foreignKeyRoleId => $role['id'])));
				
				foreach($roleFunctions as $obj) {
					$function = $obj[$modelMap['Function']];
					$roleFunction = $obj[$modelMap['RoleFunction']];
					$functionAttr = array('parent_id' => $function['parent_id']);
					
					$controller = strtoupper($function['controller']);
					if(!isset($check[$controller])) {
						$check[$controller] = array();
						$apply[$controller] = array();
					}
					
					foreach($this->operations as $op) {
						if($roleFunction[$op]==1 && !is_null($function[$op])) {
							$action = explode($separator, $function[$op]); // separate the action and the dependency
							if(sizeof($action) == 1) { // if the array size is 1, then there is no dependency
								if(strlen($action[0]) > 0) {
									$check[$controller][$action[0]] = $functionAttr;
								}
							} else { // the action is dependent on another action
								$actionParent = $function[$action[0]];
								if(strpos($function[$action[0]], $separator) !== false) { // check if the parent has dependency
									$actionParent = explode($separator, $function[$action[0]]);
									$actionParent = $actionParent[1];
								}
								
								if(!isset($apply[$controller][$actionParent])) {
									$apply[$controller][$actionParent] = array();
								}
								$apply[$controller][$actionParent][$op] = true;
								if(strlen($action[1]) > 0) {
									$check[$controller][$action[1]] = $functionAttr;
								}
							}
						}
					}
				}
			}
		} else {
			$list = $this->Function->find('all');
			foreach($list as $obj) {
				$function = $obj[$modelMap['Function']];
				$functionAttr = array('parent_id' => $function['parent_id']);
				
				$controller = strtoupper($function['controller']);
				if(!isset($check[$controller])) {
					$check[$controller] = array();
					$apply[$controller] = array();
				}
				
				foreach($this->operations as $op) {
					if(!is_null($function[$op])) {
						$action = explode($separator, $function[$op]); // separate the action and the dependency
						if(sizeof($action) == 1) { // if the array size is 1, then there is no dependency
							if(strlen($action[0]) > 0) {
								$check[$controller][$action[0]] = $functionAttr;
							}
						} else { // the action is dependent on another action
							$actionParent = $function[$action[0]];
							if(strpos($function[$action[0]], $separator) !== false) { // check if the parent has dependency
								$actionParent = explode($separator, $function[$action[0]]);
								$actionParent = $actionParent[1];
							}
							
							if(!isset($apply[$controller][$actionParent])) {
								$apply[$controller][$actionParent] = array();
							}
							$apply[$controller][$actionParent][$op] = true;
							if(strlen($action[1]) > 0) {
								$check[$controller][$action[1]] = $functionAttr;
							}
						}
					}
				}
			}
		}
		$permissions['check'] = $check;
		$permissions['apply'] = $apply;
		//pr($check);
		//pr($apply);
		return $permissions;
	}
	
	public function apply($controller, $action) {
		$controller = strtoupper($controller);
		$permissions = $this->Session->read('permissions');
		$apply = $permissions['apply'];
		
		if($this->Auth->user('super_admin')==1) {
			foreach($this->operations as $op) {
				$this->controller->set($op, true);
			}
		} else {
			if(isset($apply[$controller][$action])) {
				foreach($this->operations as $op) {
					$value = isset($apply[$controller][$action][$op]);
					$this->controller->set($op, $value);
				}
			} else {
				foreach($this->operations as $op) {
					$this->controller->set($op, false);
				}
			}
		}
	}
	
	public function check($controller, $action) {
		$access = false;
		$controller = strtoupper($controller);
		
		if($this->Session->check('permissions')) {
			$permissions = $this->Session->read('permissions');
			$check = $permissions['check'];
			if($this->Auth->user('super_admin')==0) {
				$access = isset($check[$controller][$action]) ? $check[$controller][$action] : false;
			} else {
				// need to verify logic
				$access = isset($check[$controller][$action]) ? $check[$controller][$action] : true;
			}
		} else {
			$access = true;
		}
		return $access;
	}
	
	public function ignore($controller, $action) {
		$controller = strtoupper($controller);
		if(isset($this->ignoreList[$controller])) {
			if(!in_array($action, $this->ignoreList[$controller])) {
				$this->ignoreList[$controller][] = $action;
			}
		} else {
			$this->ignoreList[$controller] = array($action);
		}
	}
	
	public function isIgnored($controller, $action) {
		$controller = strtoupper($controller);
		$ignore = false;
		if(isset($this->ignoreList[$controller])) {
			if(in_array($action, $this->ignoreList[$controller])) {
				$ignore = true;
			}
		}
		return $ignore;
	}
	
	public function checkAccess() {
		$controller = $this->controller->params['controller'];
		$action = $this->controller->action;
		
		// if action is not in ignore list then check for access	
		if(!$this->isIgnored($controller, $action)) {
			if(!$this->check($controller, $action)) {
				$this->Utility->alert($this->Utility->getMessage('SECURITY_NO_ACCESS'), array('type' => 'warn'));
				$this->controller->redirect(array('plugin' => false, 'controller' => 'Home', 'action' => 'index'));
			}
		} else { // if action is in ignore list then check against navigation ignore list
			// To navigate to the correct view when user click on Settings
			$hasAccess = $this->check($controller, $action);
			if(!$hasAccess) {
				$found = false;
				$links = $this->Navigation->ignoredLinks;
				$url = array();
				$currentModule = null;
				
				foreach($links as $module => $items) {
					foreach($items as $obj) {
						if($found) {
							if($currentModule === $module) {
								if($this->check($obj['controller'], $obj['action'])) {
									$url = $obj;
									break 2;
								}
							} else {
								break 2;
							}
						} else {
							if(strtoupper($obj['controller']) === strtoupper($controller) && $obj['action'] === $action) {
								$found = true;
								$currentModule = $module;
							}
						}
					}
				}
				if(!empty($url)) {
					$this->controller->redirect(array('controller' => $url['controller'], 'action' => $url['action']));
				}
				if($found) {
					$this->Utility->alert($this->Utility->getMessage('SECURITY_NO_ACCESS'), array('type' => 'warn'));
					$this->controller->redirect(array('plugin' => false, 'controller' => 'Home', 'action' => 'index'));
				}
			}
		}
	}
	
	public function getFunctionParent($parentId) {
		$conditions = array($this->modelMap['Function'].'.id' => $parentId);
		$parent = $this->Function->find('first', array('conditions' => $conditions));
		return $parent[$this->modelMap['Function']];
	}
	
	public function loadAreas() {
		$userId = $this->Auth->user('id');
		$areaList = array();
        $isSuperAdmin = $this->Auth->user('super_admin');

        if($isSuperAdmin == 1) {
            $areas = $this->Area->find('list', array(
                'fields' => array('Area.id'),
                'conditions' => array('Area.parent_id' => '-1', 'Area.visible' => 1)
            ));
        }else{
            $roles = $this->UserRole->getUserRoles($userId);
            $areas = $this->RoleArea->findAreasByRoles(array_values($roles));
        }

		foreach($areas as $key => $areaId) {
			$areaList[$areaId] = 0;
			$this->AreaHandler->getAreasByParent($areaList, $areaId);
		}
		$this->Session->write('AccessControl.areas', $areaList);
	}
	
	public function loadInstitutions($areas) {
		$userId = $this->Auth->user('id');
		$roles = $this->UserRole->getUserRoles($userId);
		$sites = $this->RoleInstitutionSite->findSitesByRoles(array_values($roles));
		
		$InstitutionSite = ClassRegistry::init('InstitutionSite');
		$list = $InstitutionSite->getInstitutionsByAreas(array_keys($areas));
		$siteOnly = array();
		foreach($list as $obj) {
			$site = $obj['InstitutionSite'];
			$institutionId = $site['institution_id'];
			if(!isset($sites[$institutionId])) {
				$sites[$institutionId] = array();
			}
			if(!in_array($site['id'], $sites[$institutionId])) {
				$sites[$institutionId][] = $site['id'];
				$siteOnly[]=$site['id'];
			}
		}
		if(empty($list)){
			foreach($sites as $arrSites) {
				foreach ($arrSites as $arrSitesValue) {
					$siteOnly[] = $arrSitesValue;
				}
			}
		}
		$this->Session->write('AccessControl.institutions', $sites);
		$this->Session->write('AccessControl.sites', $siteOnly);
	}
	
	public function getAccessibleAreas() {
		return $this->Session->read('AccessControl.areas');
	}
	
	public function getAccessibleInstitutions($includeSites=false) {
		$institutions =  $this->Session->read('AccessControl.institutions');
		if(!$includeSites) {
			$institutions = array_keys($institutions);
		}
		return $institutions;
	}

	public function getAccessibleSites() {
		$sites =  $this->Session->read('AccessControl.sites');
		
		return $sites;
	}
}
?>
