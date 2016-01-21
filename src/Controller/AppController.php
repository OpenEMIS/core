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

		// ControllerActionComponent must be loaded before AuthComponent for it to work
		$this->loadComponent('ControllerAction.ControllerAction', [
			'ignoreFields' => ['modified_user_id', 'created_user_id', 'order']
		]);
		
		$this->loadComponent('Auth', [
			'authenticate' => [
				'Form' => [
					'userModel' => 'User.Users',
					'passwordHasher' => [
						'className' => 'Fallback',
						'hashers' => ['Default', 'Legacy']
					]
				],
			],
			'logoutRedirect' => [
				'plugin' => 'User',
				'controller' => 'Users',
				'action' => 'login'
			]
		]);

		$this->Auth->config('authorize', ['Security']);

		// Custom Components
		$this->loadComponent('Navigation');
		$this->loadComponent('Localization.Localization');
		$this->loadComponent('ControllerAction.Alert');
		$this->loadComponent('AccessControl', [
			'ignoreList' => [
				'Users' => ['login', 'logout', 'postLogin'],
				'Dashboard' => [],
				'Preferences' => [],
				'About' => []
			]
		]);

		$this->loadComponent('Workflow.Workflow');
		$this->loadComponent('OpenEmis.SSO', [
			'homePageURL' => ['plugin' => null, 'controller' => 'Dashboard', 'action' => 'index'],
			'userNotAuthorisedURL' => ['plugin' => 'Error', 'controller' => 'Errors', 'action' => 'error403'],
		]); // for single sign on authentication
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		$session = $this->request->session();
		
		$theme = 'OpenEmis.themes/layout.core';

		$session = $this->request->session();
		if (!$session->check('System.home')) {
			$session->write('System.home', ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'index']);
		}

		$homeUrl = ['plugin' => false, 'controller' => 'Dashboard', 'action' => 'index'];
		$session->write('System.home', $homeUrl);

		if (!is_null($this->Auth->user())) { // if user is logged in
			if ($this->Auth->user('super_admin') == 1) {
				$session->write('System.User.roles', __('System Administrator'));
			} else {
				$rolesList = $this->AccessControl->getRolesByUser();
				$roles = [];
				foreach ($rolesList as $obj) {
					if (!empty($obj->security_group) && !empty($obj->security_role)) {
						$roles[] = sprintf("%s (%s)", $obj->security_group->name, $obj->security_role->name);
					}
				}
				$session->write('System.User.roles', implode(', ', $roles));
			}
		}

		$this->set('theme', $theme);
		$this->set('SystemVersion', $this->getCodeVersion());
		$this->set('_productName', $this->_productName);

		//Retriving the panel width size from session
		if ($session->check('System.layout')) {
			$layout = $session->read('System.layout');
			$this->set('SystemLayout_leftPanel', 'width:'.$layout['panelLeft'].'px');
			$this->set('SystemLayout_rightPanel','width:'.$layout['panelRight'].'px');
		} else {
			$this->set('SystemLayout_leftPanel', 'width: 10%');
			$this->set('SystemLayout_rightPanel','width: 90%');
		}
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

	//Storing the panel width size from session
	public function setJqxSpliterSize() {
		$this->autoRender = false;

		if ($this->request->is(['ajax'])) {
			$session = $this->request->session();
			$session->write('System.layout', $this->request->data);
			$layout = $session->read('System.layout');
		}
	}


}
