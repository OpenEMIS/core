<?php
namespace User\Controller;
use Cake\Event\Event;
use DateTime;

class UsersController extends AppController {
	public function initialize() {
		parent::initialize();
		$this->ControllerAction->model('User.Users');
		$this->loadComponent('Paginator');
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		//pr($this->Users->fields);die;
		// $this->SecurityUsers->fields['password']['visible'] = false;
		// $this->SecurityUsers->fields['status']['type'] = 'select';
		// $this->SecurityUsers->fields['status']['options'] = ['Inactive', 'Active'];
		// $this->SecurityUsers->fields['privileges']['type'] = 'select';
		// $this->SecurityUsers->fields['privileges']['options'] = ['User', 'Super User'];
		// $this->set('contentHeader', 'Users');
		//pr($this->request->params);die;

		$this->Auth->allow(['add', 'logout', 'postLogin']);
	}

	public function login() {
		$this->layout = false;
		$username = '';
		$password = '';
		
		if ($this->request->is('post') && $this->request->data['submit'] == 'reload') {
			//$username = $this->request->data['User']['username'];
			//$password = $this->request->data['User']['password'];
		}
		
		$this->set('username', $username);
		$this->set('password', $password);
	}

	public function postLogin() {
		$this->autoRender = false;
		
		if ($this->request->is('post') && $this->request->data['submit'] == 'login') {
			$username = $this->request->data('username');
			$this->log('[' . $username . '] Attempt to login as ' . $username . '@' . $_SERVER['REMOTE_ADDR'], 'debug');
			$user = $this->Auth->identify();
			if ($user) {
				$this->Auth->setUser($user);
				if ($this->Auth->authenticationProvider()->needsPasswordRehash()) {
					$user = $this->Users->get($this->Auth->user('id'));
					$user->password = $this->request->data('password');
					$this->Users->save($user);
				}
				return $this->redirect(['plugin' => false, 'controller' => 'Dashboard', 'action' => 'index']);
			} else {
				$this->Alert->error('security.login.fail');
				return $this->redirect(['action' => 'login']);
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
