<?php
namespace SSO\Controller;
use Cake\Event\Event;
use DateTime;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\Controller\Controller;

class LoginController extends Controller {
	public function initialize() {
		parent::initialize();
		$this->loadComponent('Auth', [
			'loginAction' => [
				'plugin' => 'SSO',
            	'controller' => 'Login',
            	'action' => 'login'
            ],
			'logoutRedirect' => [
				'plugin' => 'SSO',
				'controller' => 'Login',
				'action' => 'login'
			]
		]);
		$this->loadComponent('OpenEmis.OpenEmis', [
			'homeUrl' => ['plugin' => false, 'controller' => 'Dashboard', 'action' => 'index'],
			'headerMenu' => [
				'Preferences' => [
					'url' => ['plugin' => false, 'controller' => 'Preferences', 'action' => 'index']
				]
			],
			'theme' => 'core'
		]);
		$this->loadComponent('Localization.Localization');
	}

	public function login() {
		$this->viewBuilder()->layout(false);
		if ($this->Auth->user()) {
            return $this->redirect(['plugin' => false, 'controller' => 'Dashboard', 'action' => 'index']);
        }
		$username = '';
		$password = '';
		$session = $this->request->session();

		$this->set('username', $username);
		$this->set('password', $password);
	}
}
