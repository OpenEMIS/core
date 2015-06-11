<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link	  http://cakephp.org CakePHP(tm) Project
 * @since	 0.2.9
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller;

use Cake\Controller\Controller;
use Cake\Event\Event;
use ControllerAction\Model\Traits\ControllerActionTrait;

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @link http://book.cakephp.org/3.0/en/controllers.html#the-app-controller
 */
class AppController extends Controller {
	use ControllerActionTrait;

	public $_productName = 'OpenEMIS Core';

	public $helpers = [
		'Text',

		// Custom Helper
		'ControllerAction.ControllerAction',
		'OpenEmis.Navigation'
	];

	/**
	 * Initialization hook method.
	 *
	 * Use this method to add common initialization code like loading components.
	 *
	 * @return void
	 */
	public function initialize() {
		parent::initialize();
		$this->loadComponent('Flash');
		$this->loadComponent('Auth', [
			'authenticate' => [
				'Form' => [
					'userModel' => 'User.Users',
					'passwordHasher' => ['className' => 'Default']
				]
			],
			'loginRedirect' => [
				'plugin' => 'Institution',
				'controller' => 'Institutions',
				'action' => 'index'
			],
			'logoutRedirect' => [
				'plugin' => 'User',
				'controller' => 'Users',
				'action' => 'login'
			]
		]);

		// Custom Components
		$this->loadComponent('Navigation');
		$this->loadComponent('Localization.Localization');
		$this->loadComponent('ControllerAction.Alert');
		$this->loadComponent('ControllerAction.ControllerAction', [
			'ignoreFields' => ['modified_user_id', 'created_user_id', 'order']
		]);
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		$session = $this->request->session();
		
		$theme = 'OpenEmis.themes/layout.core';

		$this->set('theme', $theme);
		$this->set('SystemVersion', $this->getCodeVersion());
		$this->set('_productName', $this->_productName);
	}

	public function getCodeVersion() {
		$path = 'version';
		$session = $this->request->session();
		$version = '';

		if (file_exists($path)) {
			$version = file_get_contents($path);
			$session->write('System.version', $version);
		} else if ($session->check('System.version')) {
			$version = $session->read('System.version');
		}
		return $version;
	}
}
