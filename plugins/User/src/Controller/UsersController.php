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
		$this->SSO->doAuthentication();
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

		// To remove inactive staff security group users records
		$InstitutionStaffTable = TableRegistry::get('Institution.Staff');
		$InstitutionStaffTable->removeInactiveStaffSecurityRole();
	}
}
