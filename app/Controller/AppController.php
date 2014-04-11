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
	public $uses = array('ConfigItem');
	public $helpers = array('Html', 'Form', 'Js', 'Session', 'Utility');
	public $components = array(
		'RequestHandler',
		'Session',
		'Navigation' => array('modules' => array('Students', 'Teachers', 'Staff', 'Reports')), 
		'AccessControl',
		'Utility',
		'DateTime',
		'Auth' => array(
			'loginAction' => array('controller' => 'Security', 'action' => 'login'),
			'logoutRedirect' => array('controller' => 'Security', 'action' => 'login'),
			'authenticate' => array('Form' => array('userModel' => 'SecurityUser'))
		),
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
		if(!$this->request->is('ajax')) {
			$this->AccessControl->checkAccess();
		}
	}
	 
	public function beforeRender() {
		$this->AccessControl->apply($this->params['controller'], $this->params['action']);
		$this->set('bodyTitle', $this->bodyTitle);
		$this->set('_accessControl', $this->AccessControl);
	}
	
	public function invokeAction(CakeRequest $request) {
		try {
			$action = $request->params['action'];
			if(!method_exists($this, $action)) {
				return $this->processAction();
			}
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
           /* if(strpos(strtolower(substr($this->action, 0,6)), 'health') !== false){
                $action = Inflector::underscore($this->action);
            }
            else */if(strpos(strtolower(substr($this->action, 0,8)), 'training') !== false){
                $action = Inflector::underscore($this->action);
            } 
            else if(strpos(strtolower(substr($this->action, 0,7)), 'special') !== false){
                $action = Inflector::underscore($this->action);
            }
            else{
                $action = strtolower($this->action); 
            }
            
		if(!empty($this->modules)) { // for modules / plugin 
		//search for exact match
			foreach($this->modules as $name => $module) {
				if($action == strtolower($name)) {
                                    
					$this->loadModel($module);
					$explode = explode('.', $module);
					$plugin = count($explode) > 1 ? $explode[0] : null;
					$module = $explode[count($explode)-1];
					
					return $this->{$module}->processAction($this, $this->action, $name, $plugin);
				}
			}
		//if nothing match, search by partial string
			foreach($this->modules as $name => $module) { 
				if(strpos($action, strtolower($name)) === 0) {
					$this->loadModel($module);
					$explode = explode('.', $module);
					$plugin = count($explode) > 1 ? $explode[0] : null;
					$module = $explode[count($explode)-1];
					
					return $this->{$module}->processAction($this, $this->action, $name, $plugin);
				}
			}
		}
		if(!empty($this->components)) { // for components
			$actionCamel = Inflector::camelize($action);
			$name = '';
			foreach($this->components as $component => $option) {
				if(is_string($component) && strpos($actionCamel, $component) === 0) {
					$name = $component;
				} else if (is_string($option) && strpos($actionCamel, $option) === 0) {
					$name = $option;
				}
				if(strlen($name) != 0) {
					$action = substr($actionCamel, strlen($name));
					return $this->{$name}->processAction($this, $action);
				}
			}
		}
	}
}