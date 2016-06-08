<?php

namespace Restful\Controller;

use Cake\Controller\Controller;

class AppController extends Controller {
	public function initialize() {
		parent::initialize();

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
				'plugin' => 'User',
				'controller' => 'Users',
				'action' => 'login'
			]
		]);

		$this->loadComponent('Csrf');
	}
}
