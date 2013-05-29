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
	
	public $components = array('Auth', 'AccessControl');
	
	public function initialize(Controller $controller) {
		$this->controller =& $controller;
		$this->navigations = $this->getLinks();
		$this->topNavigations = array();
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
		foreach($this->navigations as $module => $obj) {
			foreach($obj['links'] as $links) {
				foreach($links as $title => &$linkList) {
					if(!is_array($linkList)) continue;
					foreach($linkList as $link => &$attr) {
						if(!is_array($attr)) continue;
						$_controller = $attr['controller'];
						$pattern = $attr['pattern'];
						
						// Checking access control
						if($this->AccessControl->check($_controller, $attr['action']) || $_controller === 'Home') {
							$linkList['display'] = true;
							$attr['display'] = true;
							if(!array_key_exists($module, $this->topNavigations)) {
								$this->topNavigations[$module] = array(
									'controller' => $obj['controller'], 
									'action' => isset($obj['action']) ? $obj['action'] : '',
									'selected' => false
								);
							}
						}
						// End access control
						
						// To check which link is selected
						if(!$found && strcasecmp($_controller, $controller)==0 && preg_match(sprintf('/^%s/i', $pattern), $action)) {
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
			}
		}
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
		
		$nav['Settings'] = array('controller' => 'Areas', 'links' => $this->getSettingsLinks());
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
		$links = array(
			array(
				array(
					$this->createLink('List of Institutions', 'Institutions', 'index', 'index$'),
					$this->createLink('Add new Institution', 'Institutions', 'add', 'add$')
				)
			),
			array(
				'INFORMATION' => array(
					$this->createLink('General', 'Institutions', 'view', 'view$|^edit$|history$'),
					$this->createLink('Attachments', 'Institutions', 'attachments'),
					$this->createLink('More', 'Institutions', 'additional')
				),
				'INSTITUTION SITE' => array(
					$this->createLink('List of Institution Sites', 'Institutions', 'listSites', 'listSites$'),
					$this->createLink('Add new Institution Site', 'Institutions', 'add', 'add$')
				)
			),
			array(
				'INFORMATION' => array(
					$this->createLink('General', 'InstitutionSites', 'view', 'view$|^edit$|history$'),
					$this->createLink('Attachments', 'InstitutionSites', 'attachments'),
					$this->createLink('Bank Accounts', 'InstitutionSites', 'bankAccounts'),
					$this->createLink('More', 'InstitutionSites', 'additional')
				),
				'DETAILS' => array(
					$this->createLink('Programmes', 'InstitutionSites', 'programmes'),
					$this->createLink('Classes', 'InstitutionSites', 'classes'),
					$this->createLink('Students', 'InstitutionSites', 'students'),
					$this->createLink('Teachers', 'InstitutionSites', 'teachers'),
					$this->createLink('Staff', 'InstitutionSites', 'staff')
				),
				'TOTALS' => array(
					$this->createLink('Enrolment', 'Census', 'enrolment'),
					$this->createLink('Graduates', 'Census', 'graduates'),
					$this->createLink('Classes', 'Census', 'classes'),
					$this->createLink('Textbooks', 'Census', 'textbooks'),
					$this->createLink('Teachers', 'Census', 'teachers'),
					$this->createLink('Staff', 'Census', 'staff'),
					$this->createLink('Infrastructure', 'Census', 'infrastructure'),
					$this->createLink('Finances', 'Census', 'finances'),
					$this->createLink('More', 'Census', 'otherforms')
				)
			)
		);
		return $links;
	}
	
	public function getSettingsLinks() {		
		$links = array(
			array(
				'SYSTEM SETUP' => array(
					$this->createLink('Administrative Boundaries', 'Areas', 'index', 'index$|levels|edit$'),
					$this->createLink('Education Structure', 'Education', 'index', 'index$|setup$'),
					$this->createLink('Assessments', 'Assessment', 'index', 'index$|assessment'),
					$this->createLink('Field Options', 'Setup', 'setupVariables', '^setupVariables|^custom'),
					$this->createLink('System Configurations', 'Config', 'index', 'index$|edit$|^dashboard')
				),
				'ACCOUNTS &amp; SECURITY' => array(
					$this->createLink('Users', 'Security', 'users'),
					$this->createLink('Roles', 'Security', 'roles', '^role'),
					$this->createLink('Permissions', 'Security', 'permissions')
				),
				'NATIONAL DENOMINATORS' => array(
					$this->createLink('Population', 'Population', 'index', 'index$|edit$'),
					$this->createLink('Finance', 'Finance', 'index', 'index$|edit$|financePerEducationLevel$')
				),
				'DATA PROCESSING' => array(
					$this->createLink('Generate', 'DataProcessing', 'reports'),
					$this->createLink('Export', 'DataProcessing', 'exports'),
					$this->createLink('Processes', 'DataProcessing', 'processes')
				),
				'DATABASE' => array(
					$this->createLink('Backup', 'Database', 'backup'),
					$this->createLink('Restore', 'Database', 'restore')
				)
			)
		);
		$this->ignoreLinks($links, 'Settings');
		return $links;
	}
	
	public function ignoreLinks($links, $module) {
		if(!isset($this->ignoredLinks[$module])) {
			$this->ignoredLinks[$module] = array();
		}
		foreach($links as $i => $category) {
			foreach($category as $j => $items) {
				foreach($items as $k => $obj) {
					$controller = $obj['controller'];
					$action = $obj['action'];
					$this->ignoredLinks[$module][] = array('controller' => $controller, 'action' => $action);
					$this->AccessControl->ignore($controller, $action);
				}
			}
		}
	}
}
?>
