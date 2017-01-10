<?php
namespace SSO\Controller;
use Cake\Event\Event;
use DateTime;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\Controller\Controller;
use Cake\Log\Log;

class LoginController extends Controller {
	public function initialize() {
		parent::initialize();
	}

	public function login() {
        Log::write('debug', $this->request);
		if ($this->request->is('post')) {
			$username = $this->request->data('username');
			if (!empty($username)) {
				$SingleLogoutTable = TableRegistry::get('SSO.SingleLogout');
				$SingleLogoutTable->removeLogoutRecord($username);
			}
		} else if ($this->request->is('put')) {
			$this->captureLogin();
		}
	}

    private function captureLogin()
    {
        $url = $this->request->data('url');
        $sessionId = $this->request->data('session_id');
        $username = $this->request->data('username');
        if (!empty($url) && !empty($sessionId) && !empty($username)) {
            TableRegistry::get('SSO.SingleLogout')->addRecord($url, $username, $sessionId);
        }
    }
}
