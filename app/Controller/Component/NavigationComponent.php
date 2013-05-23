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
	public $navigations;
	public $breadcrumbs;
	public $params;
	public $ignoredLinks = array();
	public $skip = false;
	
	public $components = array('Auth', 'AccessControl');
	
	public function initialize(Controller $controller) {
		$this->controller =& $controller;
		$this->navigations = $this->getLinks();
	}
	
	//called after Controller::beforeFilter()
	public function startup(Controller $controller) {}
	
	//called after Controller::beforeRender()
	public function beforeRender(Controller $controller) {
		if(!$this->skip) {
			$this->apply($controller->params['controller'], $this->controller->action);
		}
		$this->controller->set('_navigations', $this->navigations);
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
	
	public function createLink($title, $action, $params=array()) {
		$attr = array();
		$attr['title'] = $title;
		$attr['display'] = false;
		$attr['selected'] = false;
		$attr['action'] = $action;
		return array_merge($attr, $params);
	}
	
	public function apply($controller, $action, $actionFound=false) {
		foreach($this->navigations as $module => &$obj) { // looping through modules
			$moduleDisplay = false;
			$moduleSelected = false;
			foreach($obj['links'] as &$links) { // looping through modules links
				$linkListDisplay = false;
				foreach($links as $title => &$linkList) { // looping through the list of links
					if($title === '_display') continue;
					$linkFound = false;
					foreach($linkList as $key => &$link) { // looping through each link
						if($key === '_controller' || $key === '_display') continue;
						$_controller = isset($linkList['_controller']) ? $linkList['_controller'] : $link['controller'];
						$pattern = isset($link['pattern']) ? $link['pattern'] : $link['action'];
						
						// Checking access control
						if($this->AccessControl->check($_controller, $link['action']) || $_controller === 'Home') {
							$link['display'] = true;
							$moduleDisplay = true;
							$linkList['_display'] = true;
						}
						// End access control
						
						// To check which link is selected
						
						if(!$linkFound && strcasecmp($_controller, $controller)==0 && preg_match(sprintf('/^%s/i', $pattern), $action)) {
							$linkFound = true;
							$link['selected'] = true;
							$linkListDisplay = true;
							$moduleSelected = true;
							$actionFound = true;
							if(!$moduleDisplay) {
								$moduleDisplay = true;
							}
							$linkList['_display'] = true;
						}
					}
					if(!isset($linkList['_display'])) {
						$linkList['_display'] = false;
					}
				}
				$links['_display'] = $linkListDisplay;
			}
			$obj['display'] = $moduleDisplay;
			$obj['selected'] = $moduleSelected;
		}
		
		if(!$actionFound) {
			$parentId = $this->AccessControl->check($controller, $action);
			$parent = $this->AccessControl->getFunctionParent($parentId['parent_id']);
			$parentAction = $parent['_view'];
			$this->apply($controller, $parentAction, true);
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
		
		$nav['Settings'] = array('controller' => 'Setup', 'links' => $this->getSettingsLinks());
		return $nav;
	}
	
	public function getHomeLinks() {
		$links = array(
			array(
				array(
					'_controller' => 'Home',
					$this->createLink('My Details', 'details'),
					$this->createLink('Change Password', 'password')
				)
			),
			array(
				array(
					'_controller' => 'Home',
					$this->createLink('Support', 'support'),
					$this->createLink('System Information', 'systemInfo'),
					$this->createLink('License', 'license')
				)
			)
		);
		return $links;
	}
	
	public function getInstitutionsLinks() {
		$links = array(
			array(
				array(
					'_controller' => 'Institutions',
					$this->createLink('List of Institutions', 'index', array('pattern' => 'index$')),
					$this->createLink('Add new Institution', 'add', array('pattern' => 'add$'))
				)
			),
			array(
				'INFORMATION' => array(
					'_controller' => 'Institutions',
					$this->createLink('General', 'view', array('pattern' => 'view$|^edit$|history$')),
					$this->createLink('Attachments', 'attachments'),
					$this->createLink('More', 'additional')
				),
				'INSTITUTION SITES' => array(
					$this->createLink('List of Institution Sites', 'listSites', array('pattern' => 'listSites$', 'controller' => 'Institutions')),
					$this->createLink('Add New Institution Site', 'add', array('pattern' => 'add$', 'controller' => 'InstitutionSites'))
				)
			),
			array(
				'INFORMATION' => array(
					'_controller' => 'InstitutionSites',
					$this->createLink('General', 'view', array('pattern' => 'view$|^edit$|history$')),
					$this->createLink('Attachments', 'attachments'),
					$this->createLink('Bank Accounts', 'bankAccounts'),
					$this->createLink('More', 'additional')
				),
				'DETAILS' => array(
					'_controller' => 'InstitutionSites',
					$this->createLink('Programmes', 'programmes'),
					$this->createLink('Classes', 'classes'),
					$this->createLink('Students', 'students'),
					$this->createLink('Teachers', 'teachers'),
					$this->createLink('Staff', 'staff'),
					$this->createLink('Results', 'results')
				),
				'TOTALS' => array(
					'_controller' => 'Census',
					$this->createLink('Enrolment', 'enrolment'),
					$this->createLink('Graduates', 'graduates'),
					$this->createLink('Classes', 'classes'),
					$this->createLink('Textbooks', 'textbooks'),
					$this->createLink('Teachers', 'teachers'),
					$this->createLink('Staff', 'staff'),
					$this->createLink('Infrastructure', 'infrastructure'),
					$this->createLink('Finances', 'finances'),
					$this->createLink('More', 'otherforms')
				)
			)
		);
		return $links;
	}
	
	public function getSettingsLinks() {
		$links = array(
			array(
				'SYSTEM SETUP' => array(
					$this->createLink('Administrative Boundaries', 'index', array('pattern' => 'index$|levels|edit$', 'controller' => 'Areas')),
					$this->createLink('Education Structure', 'index', array('pattern' => 'index$|setup$', 'controller' => 'Education')),
					$this->createLink('National Assessments', 'index', array('pattern' => 'index|assessment', 'controller' => 'Assessment')),
					$this->createLink('Field Options', 'setupVariables', array('pattern' => '^setupVariables|^custom', 'controller' => 'Setup')),
					$this->createLink('System Configurations', 'index', array('pattern' => 'index$|edit$|^dashboard', 'controller' => 'Config'))
				),
				'ACCOUNTS &amp; SECURITY' => array(
					'_controller' => 'Security',
					$this->createLink('Users', 'users'),
					$this->createLink('Roles', 'roles', array('pattern' => '^role')),
					$this->createLink('Permissions', 'permissions')
				),
				'NATIONAL DENOMINATORS' => array(
					$this->createLink('Population', 'index', array('pattern' => 'index$|edit$', 'controller' => 'Population')),
					$this->createLink('Finance', 'index', array('pattern' => 'index$|edit$|financePerEducationLevel$', 'controller' => 'Finance'))
				),
				'DATA PROCESSING' => array(
					'_controller' => 'DataProcessing',
					$this->createLink('Generate', 'reports'),
					$this->createLink('Export', 'exports'),
					$this->createLink('Processes', 'processes'),
					//$this->createLink('Scheduler', 'scheduler')
				),
				'DATABASE' => array(
					'_controller' => 'Database',
					$this->createLink('Backup', 'backup'),
					$this->createLink('Restore', 'restore')
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
					if($k === '_controller') continue;
					$controller = isset($obj['controller']) ? $obj['controller'] : $items['_controller'];
					$action = $obj['action'];
					$this->ignoredLinks[$module][] = array('controller' => $controller, 'action' => $action);
					$this->AccessControl->ignore($controller, $action);
				}
			}
		}
	}
}
?>
