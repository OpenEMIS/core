<?php
namespace OpenEmis\Controller\Component;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\Controller\Component;
use Cake\Event\Event;

class LocalAuthComponent extends Component {
	public $components = ['Auth', 'Alert'];

	public function implementedEvents() {
		$events = parent::implementedEvents();
        // $events['Controller.Auth.beforeAuthenticate'] = 'beforeAuthenticate';
        $events['Controller.Auth.authenticate'] = 'authenticate';
        return $events;
    }

    public function beforeFilter(Event $event) {
    	$controller = $this->_registry->getController();
    	$controller->Auth->config('authenticate', [
    		'Form' => [
				'userModel' => 'User.Users',
				'passwordHasher' => [
					'className' => 'Fallback',
					'hashers' => ['Default', 'Legacy']
				]
			]
		]);
    }

    public function authenticate(Event $event, ArrayObject $extra) {
    	$controller = $this->_registry->getController();
    	if ($this->request->is('post')) {
			if ($this->request->data['submit'] == 'login') {
				$username = $this->request->data('username');
				return $this->checkLogin($username);
			} else if ($this->request->data['submit'] == 'reload') {
				$username = $this->request->data['username'];
				$password = $this->request->data['password'];
				$session = $this->request->session();
				$session->write('login.username', $username);
				$session->write('login.password', $password);
				return $controller->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'login']);
			}
		} else {
			return false;
		}
    }

    private function checkLogin($username) {
		$session = $this->request->session();
		$this->log('[' . $username . '] Attempt to login as ' . $username . '@' . $_SERVER['REMOTE_ADDR'], 'debug');
		$user = $this->Auth->identify();
		if ($user) {
			if ($user['status'] != 1) {
				$this->Alert->error('security.login.inactive');
				$controller = $this->_registry->getController();
				return $controller->redirect(['action' => 'login']);
			}
			$this->Auth->setUser($user);
			$labels = TableRegistry::get('Labels');
			$labels->storeLabelsInCache();
			if ($this->Auth->authenticationProvider()->needsPasswordRehash()) {
				$user = $this->Users->get($this->Auth->user('id'));
				$user->password = $this->request->data('password');
				$this->Users->save($user);
			}
			// Support Url
			$ConfigItems = TableRegistry::get('ConfigItems');
			$supportUrl = $ConfigItems->value('support_url');
			$session->write('System.help', $supportUrl);
			// End
			return true;
		} else {
			$this->Alert->error('security.login.fail');
			return false;
		}
	}
}
