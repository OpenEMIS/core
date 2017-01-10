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
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;

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
		'OpenEmis.Navigation',
		'OpenEmis.Resource'
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
			'loginAction' => [
				'plugin' => 'User',
            	'controller' => 'Users',
            	'action' => 'login'
            ],
			'logoutRedirect' => [
				'plugin' => null,
				'controller' => 'Login',
				'action' => 'login'
			]
		]);

		$this->loadComponent('Paginator');

		$this->Auth->config('authorize', ['Security']);

		// Custom Components
		$this->loadComponent('Navigation');
		$this->loadComponent('Localization.Localization');
		$this->loadComponent('OpenEmis.OpenEmis', [
			'homeUrl' => ['plugin' => false, 'controller' => 'Dashboard', 'action' => 'index'],
			'headerMenu' => [
				'Preferences' => [
					'url' => ['plugin' => false, 'controller' => 'Preferences', 'action' => 'index']
				]
			],
			'theme' => 'core'
		]);

		$this->loadComponent('OpenEmis.ApplicationSwitcher');

		// Angular initialization
		$this->loadComponent('Angular.Angular', [
			'app' => 'OE_Core',
			'modules' => [
				'bgDirectives', 'ui.bootstrap', 'ui.bootstrap-slider', 'ui.tab.scroll', 'agGrid', 'app.ctrl', 'advanced.search.ctrl', 'kd-elem-sizes', 'kd-angular-checkbox-radio'
			]
		]);

		$this->loadComponent('ControllerAction.Alert');
		$this->loadComponent('AccessControl');

		$this->loadComponent('Workflow.Workflow');
		$this->loadComponent('SSO.SSO', [
			'homePageURL' => ['plugin' => null, 'controller' => 'Dashboard', 'action' => 'index'],
			'loginPageURL' => ['plugin' => 'User', 'controller' => 'Users', 'action' => 'login'],
			'userModel' => 'User.Users',
			'cookieAuth' => [
				'username' => 'openemis_no'
			],
			'cookie' => [
				'domain' => Configure::read('domain')
			]
		]); // for single sign on authentication
		$this->loadComponent('Security.SelectOptionsTampering');
		$this->loadComponent('Security', [
			'unlockedFields' => [
				'area_picker'
			],
			'unlockedActions' => [
				'postLogin'
			]
		]);
		$this->loadComponent('Csrf');
		if ($this->request->action == 'postLogin') {
            $this->eventManager()->off($this->Csrf);
        }
	}

	// Triggered from LocalizationComponent
	// Controller.Localization.getLanguageOptions
	public function getLanguageOptions(Event $event)
	{
		$ConfigItemsTable = TableRegistry::get('Configuration.ConfigItems');
		$session = $event->subject()->request->session();
		$showLanguage = $session->read('System.language_menu');
		$systemLanguage = $session->read('System.language');

		// Check if the language menu is enabled
		if (!$session->check('System.language_menu')) {
			$showLanguage = $ConfigItemsTable->value('language_menu');
			$systemLanguage = $ConfigItemsTable->value('language');
			$session->write('System.language', $systemLanguage);
			$session->write('System.language_menu', $showLanguage);
		}

		return [$showLanguage, $systemLanguage];
	}

	// Triggered from Localization component
	// Controller.Localization.updateLoginLanguage
	public function updateLoginLanguage(Event $event, $user, $lang)
	{
		$UsersTable = TableRegistry::get('User.Users');
		$UsersTable->dispatchEvent('Model.Users.updateLoginLanguage', [$user, $lang], $this);
	}
}
