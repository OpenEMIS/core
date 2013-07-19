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

class AccessControlComponent extends Component {
	private $controller;
	private $User;
	private $Role;
	private $Function;
	private $RoleFunction;
	private $Group;
	private $GroupUser;
	private $GroupArea;
	private $GroupInstitutionSite;
	
	public $ignoreList = array(
		'HOME' => array('index', 'details', 'detailsEdit', 'password', 'support', 'systemInfo', 'license'),
		'SECURITY' => array('login', 'logout'), 
		'CONFIG' => array('getI18n', 'getJSConfig', 'fetchImage'),
		'STUDENTS' => array('viewStudent'),
		'TEACHERS' => array('viewTeacher'),
		'STAFF' => array('viewStaff')
	);
	public $operations = array('_view', '_edit', '_add', '_delete', '_execute');
	
	private $modelMap = array(
		'User' => 'SecurityUser',
		'Role' => 'SecurityRole',
		'Function' => 'SecurityFunction',
		'RoleFunction' => 'SecurityRoleFunction',
		'Group' => 'SecurityGroup',
		'GroupUser' => 'SecurityGroupUser',
		'GroupArea' => 'SecurityGroupArea',
		'GroupInstitutionSite' => 'SecurityGroupInstitutionSite',
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
		
		if($this->Auth->user('super_admin')==0) {
			$this->loadAreas();
			$this->loadInstitutions();
		}
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
		$divider = '|';
		$permissions = array();
		$check = array();
		$apply = array();
		
		if($this->Auth->user('super_admin')==0) {
			$list = $this->GroupUser->getRolesByUserId($userId);
			
			$roleFunctions = $this->Function->getUserPermissions($userId);
			foreach($roleFunctions as $obj) {
				if($obj[$modelMap['Role']]['visible'] != 1) continue; // if role is disabled then skip this permission
				
				$function = $obj[$modelMap['Function']];
				$roleFunction = $obj[$modelMap['RoleFunction']];
				$functionAttr = array('parent_id' => $function['parent_id']);
				
				$controller = strtoupper($function['controller']);
				if(!isset($check[$controller])) {
					$check[$controller] = array();
					$apply[$controller] = array();
				}
				
				$operationObj = $obj[0];
				foreach($this->operations as $op) {
					if($operationObj[$op]==1 && !is_null($function[$op])) {
						$action = explode($separator, $function[$op]); // separate the action and the dependency
						if(sizeof($action) == 1) { // if the array size is 1, then there is no dependency
							if(strlen($action[0]) > 0) {
								$actionList = explode($divider, $action[0]);
								foreach($actionList as $a) {
									$check[$controller][$a] = $functionAttr;
								}
							}
						} else { // the action is dependent on another action
							$actionParent = $function[$action[0]];
							if(strpos($function[$action[0]], $separator) !== false) { // check if the parent has dependency
								$actionParent = explode($separator, $function[$action[0]]);
								$actionParent = $actionParent[1];
							}
							
							$actionList = explode($divider, $actionParent);
							foreach($actionList as $a) {
								if(!isset($apply[$controller][$a])) {
									$apply[$controller][$a] = array();
								}
								$apply[$controller][$a][$op] = true;
							}
							if(strlen($action[1]) > 0) {
								$actionList = explode($divider, $action[1]);
								foreach($actionList as $a) {
									$check[$controller][$a] = $functionAttr;
								}
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
		if($userId > 0) {
			$areaList = array();
			$isSuperAdmin = $this->Auth->user('super_admin');
	
			if($isSuperAdmin == 0) {
				$areas = $this->GroupArea->findAreasByUserId($userId);
				foreach($areas as $key => $areaId) {
					$areaList[$areaId] = 0;
					$this->AreaHandler->getAreasByParent($areaList, $areaId);
				}
				$this->Session->write('AccessControl.areas', $areaList);
			}
		}
	}
	
	public function loadInstitutions() {
		$userId = $this->Auth->user('id');
		$groupMaps = array();
		if($userId > 0) {
			// getting all accessible sites
			$sites = $this->GroupInstitutionSite->findSitesByUserId($userId);
			$siteOnly = array();
			
			foreach($sites as $key => $obj) {
				$siteOnly = array_merge($siteOnly, $obj);
			}
			
			// For institutions without a site, only display the institutions to 
			// users in the same group as the creator of that institution
			// if creator has no group anymore, the institution will be displayed to everyone
			$Institution = ClassRegistry::init('Institution');
			$institutionList = $Institution->getInstitutionsWithoutSites();
			$userGroups = $this->GroupUser->getGroupIdsByUserId($userId);
			
			foreach($institutionList as $key => $obj) {
				$creator = $obj['Institution']['created_user_id'];
				$creatorGroups = array();
				if(!array_key_exists($creator, $groupMaps)) {
					$creatorGroups = $this->GroupUser->getGroupIdsByUserId($creator);
					$groupMaps[$creator] = $creatorGroups;
				} else {
					$creatorGroups = $groupMaps[$creator];
				}
				if(!empty($creatorGroups)) {
					$existsInGroup = array_intersect($creatorGroups, $userGroups);
					if(empty($existsInGroup)) { // user and creator is in same group
						unset($institutionList[$key]);
					} else {
						$sites[$obj['Institution']['id']] = array();
					}
				} else {
					$sites[$obj['Institution']['id']] = array();
				}
			}
			
			$InstitutionSite = ClassRegistry::init('InstitutionSite');
			$areas = $this->Session->read('AccessControl.areas');
			if(!empty($areas)) {
				// getting all sites from accessible areas
				$list = $InstitutionSite->getInstitutionsByAreas(array_keys($areas));
				foreach($list as $obj) {
					$site = $obj['InstitutionSite'];
					$institutionId = $site['institution_id'];
					if(!isset($sites[$institutionId])) {
						$sites[$institutionId] = array();
					}
					if(!in_array($site['id'], $sites[$institutionId])) {
						$sites[$institutionId][] = $site['id'];
						$siteOnly[] = $site['id'];
					}
				}
			}
			$this->Session->write('AccessControl.institutions', $sites);
			$this->Session->write('AccessControl.sites', $siteOnly);
		}
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
		$sites = $this->Session->read('AccessControl.sites');
		
		return $sites;
	}
}
?>