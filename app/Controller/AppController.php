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
App::uses('L10n', 'I18n');

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
	public $bodyTitle = '';
	public $modules = array();
	public $uses = array('ConfigItem', 'SecurityUser');
	public $helpers = array('Html', 'Form', 'Js', 'Session', 'Utility');
	public $components = array(
		'RequestHandler',
		'Session',
		'Navigation' => array('modules' => array('Students', 'Staff', 'Reports', 'Visualizer')), 
		'AccessControl',
		'Utility',
		'DateTime',
		'Auth' => array(
			'loginAction' => array('controller' => 'Security', 'action' => 'login', 'plugin' => false),
			'logoutRedirect' => array('controller' => 'Security', 'action' => 'login', 'plugin' => false),
			'authenticate' => array('Form' => array('userModel' => 'SecurityUser'))
		),
		'Workflow',
		'Message',
		'Option'
	);
	
	public function beforeFilter() {
		$l10n = new L10n();
		
		$lang = $this->Session->check('configItem.language') ? $this->Session->read('configItem.language') : $this->ConfigItem->getValue('language');
		if(empty($lang)) {
			$lang = 'eng';
		}
		$locale = $l10n->map($lang);
		$catalog = $l10n->catalog($locale);
 		$this->set('lang_locale', $locale);
		$this->set('lang_dir', $catalog['direction']);

		Configure::write('Config.language', $lang);
	
		if($this->Auth->loggedIn()){
			$token = null;
			if($this->Session->check('login.token')){
				$token = $this->Session->read('login.token');
			}
			if(!isset($token) || !$this->SecurityUser->validateToken($token)){
				$this->Auth->logout();
				return $this->redirect(array('controller'=>'Security', 'action'=>'logout'));
			}
		}
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