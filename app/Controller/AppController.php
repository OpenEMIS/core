<?php
/**
 * Application level Controller
 *
 * This file is application-wide controller file. You can put all
 * application-wide controller-related methods here.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Controller
 * @since         CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('Controller', 'Controller');
App::import('Helper', 'Model'); 

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @package       app.Controller
 * @link http://book.cakephp.org/2.0/en/controllers.html#the-app-controller
 */

class AppController extends Controller {
	public $_productName = 'OpenEMIS Core';
	public $bodyTitle = '';
	public $modules = array();
	public $uses = array('ConfigItem', 'SecurityUser');
	public $helpers = array('Html', 'Form', 'Js', 'Session', 'Utility', 'Model');
	public $components = array(
		'DebugKit.Toolbar',
		'RequestHandler',
		'Session',
		'DateTime',
		'Auth' => array(
			'loginAction' => array('controller' => 'Security', 'action' => 'login', 'plugin' => false),
			'logoutRedirect' => array('controller' => 'Security', 'action' => 'login', 'plugin' => false),
			'authenticate' => array('Form' => array('userModel' => 'SecurityUser'))
		),

		// Custom Components
		'Localization',
		'Navigation' => array('modules' => array('Students', 'Staff', 'Reports')), 
		'AccessControl',
		'Search',
		'Utility',		
		'Workflow',
		'Message',
		'Option'
	);

	public function ComponentAction() { // Redirect logic to functions in Component or Model
		return $this->ControllerAction->processAction();
	}
	
	public function beforeFilter() {
		if($this->Auth->loggedIn()) {
			$token = null;
			if($this->Session->check('login.token')){
				$token = $this->Session->read('login.token');
			}
			if(!isset($token) || !$this->SecurityUser->validateToken($token)){
				$this->Auth->logout();
				return $this->redirect(array('plugin' => false, 'controller' => 'Security', 'action' => 'logout'));
			}
		}
		$this->set('SystemVersion', $this->getCodeVersion());
		$this->set('_productName', $this->_productName);
	}
	
	public function getCodeVersion() {
		$path = 'webroot/version';
		
		$version = '';
		if(file_exists(APP.$path)) {
			$version = file_get_contents(APP.$path);
			$this->Session->write('System.version', $version);
		}elseif ($this->Session->check('System.version')) {
			$version = $this->Session->read('System.version');
		}
		return $version;
	}
	 
	public function beforeRender() {
		$this->set('bodyTitle', $this->bodyTitle);
	}

	public function invokeAction(CakeRequest $request) {
		try {
			// intercept for ControllerAction behavior
			$action = $request->params['action'];
			if(!method_exists($this, $action)) {
				return $this->processAction();
			}
			// End ControllerAction
			$method = new ReflectionMethod($this, $request->params['action']);

			if ($this->_isPrivateAction($method, $request)) {
				throw new PrivateActionException(array(
					'controller' => $this->name . "Controller",
					'action' => $request->params['action']
				));
			}
			return $method->invokeArgs($this, $request->params['pass']);
		} catch (ReflectionException $e) {
			if ($this->scaffold !== false) {
				return $this->_getScaffold($request);
			}
			throw new MissingActionException(array(
				'controller' => $this->name . "Controller",
				'action' => $request->params['action']
			));
		}
	}
	
	public function processAction() {
		if(!empty($this->modules)) {
			// deprecated logic, should be removed after all modules are moved to the new logic
			$action = strtolower($this->action);
			foreach($this->modules as $name => $module) {
				if($action == strtolower($name) && !is_array($module)) {
					$this->loadModel($module);
					$explode = explode('.', $module);
					$plugin = count($explode) > 1 ? $explode[0] : null;
					$module = $explode[count($explode)-1];
					
					return $this->{$module}->processAction($this, $this->action, $name, $plugin);
				}
			}
			
			//if nothing match, search by partial string
			foreach($this->modules as $name => $module) { 
				if(strpos($action, strtolower($name)) === 0 && !is_array($module)) {
					$this->loadModel($module);
					$explode = explode('.', $module);
					$plugin = count($explode) > 1 ? $explode[0] : null;
					$module = $explode[count($explode)-1];
					
					return $this->{$module}->processAction($this, $this->action, $name, $plugin);
				}
			}
			// end deprecated
			
			$action = $this->action;
			$module = null;
			if (in_array($action, $this->modules)) {
				$module = array();
			} else if (array_key_exists($action, $this->modules)) {
				$module = $this->modules[$action];
			}
			
			if (!is_null($module)) {
				$plugin = isset($module['plugin']) ? $module['plugin'] : '';
				$this->loadModel($plugin . '.' . $action);
				if (!$this->{$action}->Behaviors->loaded('ControllerAction2')) {
					pr('ControllerActionBehavior is not loaded in ' . $action . ' Model');
					die;
				}
				return $this->{$action}->processAction($this);
			}
		}
	}
}
