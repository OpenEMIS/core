<?php
/**a
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
		)
	);
	
	public function beforeFilter() {
		$l10n = new L10n();
		$lang = 'eng';
		$locale = 'en';
		if($this->Session->check('configItem.language')) {
			$lang = $this->Session->read('configItem.language');
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
}
