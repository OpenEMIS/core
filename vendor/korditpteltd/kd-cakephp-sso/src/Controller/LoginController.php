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
	}

	public function login() {
		$this->viewBuilder()->layout(false);
		if ($this->request->is('post')) {
			$username = $this->request->data('username');
			if (!empty($username)) {
				$SingleLogoutTable = TableRegistry::get('SSO.SingleLogout');
				$SingleLogout->removeLogoutRecord($username);
			}
		}
	}
}
