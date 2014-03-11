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
 
class NavigationComponent extends Component {
	private $controller;
	public $topNavigations;
	public $leftNavigations;
	public $navigations;
	public $breadcrumbs;
	public $params;
	public $ignoredLinks = array();
	public $skip = false;
	
	public $components = array('Auth', 'AccessControl', 'Session');
	
	public function initialize(Controller $controller) {
		$this->controller =& $controller;
		$this->navigations = $this->getLinks();
		$this->topNavigations = array();
                $this->SecurityGroupUser = ClassRegistry::init('SecurityGroupUser');
	}
	
	//called after Controller::beforeFilter()
	public function startup(Controller $controller) {}
	
	//called after Controller::beforeRender()
	public function beforeRender(Controller $controller) {
		if(!$this->skip) {
			$this->apply($controller->params['controller'], $this->controller->action);
		}
		$this->controller->set('_topNavigations', $this->topNavigations);
		$this->controller->set('_leftNavigations', $this->leftNavigations);
		$this->controller->set('_params', $this->params);
		$this->controller->set('_breadcrumbs', $this->breadcrumbs);
	}
	
	//called after Controller::render()
	public function shutdown(Controller $controller) {}
	
	//called before Controller::redirect()
	public function beforeRedirect(Controller $controller, $url, $status = null, $exit = true) {}
	
	public function addCrumb($title, $options=array()) {		
		$item = array(
			'title' => __($title),
			'link' => array('url' => $options),
			'selected' => sizeof($options)==0
		);
		$this->breadcrumbs[] = $item;
	}
	
	public function createLink($title, $controller, $action, $pattern='', $params=array()) {
		$attr = array();
		$attr['title'] = $title;
		$attr['display'] = false;
		$attr['selected'] = false;
		$attr['controller'] = $controller;
		$attr['action'] = $action;
		$attr['pattern'] = strlen($pattern)==0 ? $action : $pattern;
		return array_merge($attr, $params);
	}
	
	public function apply($controller, $action) {
		$navigations = array();
		$found = false;
                //pr($this->navigations);
		foreach($this->navigations as $module => $obj) {
			foreach($obj['links'] as $links) {
				foreach($links as $title => &$linkList) {
					if(!is_array($linkList)) continue;
					foreach($linkList as $link => &$attr) {
						if(!is_array($attr)) continue;
						$_controller = $attr['controller'];
						$pattern = $attr['pattern'];
						
						// Checking access control
						$check = $this->AccessControl->newCheck($_controller, $attr['action']);
						//pr($attr);
						
						if($check || $_controller === 'Home') {
							$linkList['display'] = true;
							$attr['display'] = true;
							
							if($check === true || (isset($check['parent_id']) && $check['parent_id'] == -1) || in_array($module, array('Administration', 'Reports'))) { // to initialise top navigation menu
								if(!array_key_exists($module, $this->topNavigations)) {
									$objController = $module !== 'Administration' ?  : $_controller;
									$this->topNavigations[$module] = array(
										'controller' => $obj['controller'], 
										'action' => isset($obj['action']) ? $obj['action'] : '',
										'selected' => false
									);
								} else {
									if($module !== 'Administration') {
										$this->topNavigations[$module]['controller'] = $obj['controller'];
										$this->topNavigations[$module]['action'] = isset($obj['action']) ? $obj['action'] : '';
									}
								}
							}
						}
						// End access control
						
						// To check which link is selected
						if(!$found && strcasecmp($_controller, $controller)==0 && preg_match(sprintf('/^%s/i', $pattern), $action)) {//pr($attr);
							$found = true;
							$attr['selected'] = true;
							$this->topNavigations[$module]['selected'] = true;
						}
                                                
					}
				}
				if($found) {
					if(empty($this->leftNavigations)) {
						$this->leftNavigations = $links;
					}
				}
                                //pr($this->leftNavigations);
			}
		}//pr($this->navigations);
	}
	
	public function getLinks() {
		$nav = array();
		$nav['Home'] = array('controller' => 'Home', 'links' => $this->getHomeLinks());
		$nav['Institutions'] = array('controller' => 'Institutions', 'links' => $this->getInstitutionsLinks());
		
		// Initialise navigations from plugins
		$modules = $this->settings['modules'];
		foreach($modules as $module) {
			$componentObj = $module.'NavigationComponent';
			App::uses($componentObj, $module.'.Controller/Component');
			$component = new $componentObj(new ComponentCollection);
			$componentLinks = $component->getLinks($this);
			$nav = array_merge($nav, $componentLinks);
		}
		// End initialise
		
		$nav['Administration'] = array('controller' => 'Areas', 'links' => $this->getSettingsLinks());
		return $nav;
	}
	
	public function getHomeLinks() {
		$links = array(
			array(
				array(
					$this->createLink('My Details', 'Home', 'details'),
					$this->createLink('Change Password', 'Home', 'password')
				)
			),
			array(
				array(
					$this->createLink('Support', 'Home', 'support'),
					$this->createLink('System Information', 'Home', 'systemInfo'),
					$this->createLink('License', 'Home', 'license')
				)
			)
		);
		return $links;
	}
	
	public function getInstitutionsLinks() {
		$navigation = ClassRegistry::init('Navigation');
		$links = $navigation->getByModule('Institution', true);
		
		return $links;
	}
	
	public function getSettingsLinks() {
		$navigation = ClassRegistry::init('Navigation');
		$links = $navigation->getByModule('Administration', true);
		$this->ignoreLinks($links, 'Administration');
		return $links;
	}
	
	public function ignoreLinks($links, $module) {
		if(!isset($this->ignoredLinks[$module])) {
			$this->ignoredLinks[$module] = array();
		}
		foreach($links as $i => $category) {
			foreach($category as $j => $items) {
				foreach($items as $k => $obj) {
					if(isset($obj['controller'])) {
						$controller = $obj['controller'];
						$action = $obj['action'];
						$this->ignoredLinks[$module][] = array('controller' => $controller, 'action' => $action);
						$this->AccessControl->ignore($controller, $action);
					}
				}
			}
		}
	}

	public function getWizardLinks($module){
		$navigation = ClassRegistry::init('Navigation');
		$links = $navigation->getByModule($module, false);
		$wizardLinks = array();
		foreach($links as $link){
			if($link['Navigation']['is_wizard']=='1'){
				$wizardLinks[] = $link['Navigation'];
			}
		}
		return $wizardLinks;
	}
}
?>
