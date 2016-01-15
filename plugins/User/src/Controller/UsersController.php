<?php
namespace User\Controller;
use Cake\Event\Event;
use DateTime;
use Cake\ORM\TableRegistry;

class UsersController extends AppController {
	public function initialize() {
		parent::initialize();
		$this->ControllerAction->model('User.Users');
		$this->loadComponent('Paginator');
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);

		$this->Auth->allow(['login', 'logout', 'postLogin', 'login_remote']);
	}

	public function login() {
		$this->getView()->layout(false);
		$username = '';
		$password = '';
		$session = $this->request->session();

		if ($this->Auth->user()) {
			return $this->redirect(['plugin' => false, 'controller' => 'Dashboard', 'action' => 'index']);
		}
		
		if ($session->check('login.username')) {
			$username = $session->read('login.username');
		}
		if ($session->check('login.password')) {
			$password = $session->read('login.password');
		}
		
		$this->set('username', $username);
		$this->set('password', $password);
	}

	// this function exists so that the browser can auto populate the username and password from the website
	public function login_remote() {
		$this->autoRender = false;
		$session = $this->request->session();
		$username = $this->request->data('username');
		$password = $this->request->data('password');
		$session->write('login.username', $username);
		$session->write('login.password', $password);
		return $this->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'login']);
	}

	public function postLogin() {
		$this->autoRender = false;
		if ($this->request->is('post')) {
			if ($this->request->data['submit'] == 'login') {
				$session = $this->request->session();
				$username = $this->request->data('username');
				$this->log('[' . $username . '] Attempt to login as ' . $username . '@' . $_SERVER['REMOTE_ADDR'], 'debug');
				$user = $this->Auth->identify();
				if ($user) {
					if ($user['status'] != 1) {
						$this->Alert->error('security.login.inactive');
						return $this->redirect(['action' => 'login']);
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
					return $this->redirect(['plugin' => false, 'controller' => 'Dashboard', 'action' => 'index']);
				} else {
					$this->Alert->error('security.login.fail');
					return $this->redirect(['action' => 'login']);
				}
			} else if ($this->request->data['submit'] == 'reload') {
				$username = $this->request->data['username'];
				$password = $this->request->data['password'];
				$session = $this->request->session();
				$session->write('login.username', $username);
				$session->write('login.password', $password);
				return $this->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'login']);
			}
		} else {
			return $this->redirect($this->Auth->logout());
		}
	}

	public function logout() {
		$this->request->session()->destroy();
		return $this->redirect($this->Auth->logout());
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['Auth.afterIdentify'] = 'afterIdentify';
		return $events;
	}

	public function afterIdentify(Event $event, $user) {
		$user = $this->Users->get($user['id']);
		$user->last_login = new DateTime();
		$this->Users->save($user);
		$this->log('[' . $user->username . '] Login successfully.', 'debug');
	}
}
