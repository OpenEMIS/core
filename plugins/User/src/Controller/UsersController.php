<?php
namespace User\Controller;
use Cake\Event\Event;
use DateTime;
use Cake\ORM\TableRegistry;
use Cake\Log\Log;

class UsersController extends AppController {
	public function initialize() {
		parent::initialize();
		$this->ControllerAction->model('User.Users');
		$this->loadComponent('Paginator');
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);

		$this->Auth->allow(['login', 'logout', 'postLogin', 'login_remote', 'patchPasswords']);
	}

	public function patchPasswords() {
		$this->autoRender = false;
		$script = 'password';

		$consoleDir = ROOT . DS . 'bin' . DS;
		$cmd = sprintf("%scake %s %s", $consoleDir, $script, 'User.Users');
		$nohup = '%s > %slogs/'.$script.'.log & echo $!';
		$shellCmd = sprintf($nohup, $cmd, ROOT.DS);
		\Cake\Log\Log::write('debug', $shellCmd);
		exec($shellCmd);
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
		$InstitutionStaffTable->removeIndividualStaffSecurityRole($user['id']);
		$this->startInactiveRoleRemoval();
		$this->shellErrorRecovery();
	}

	private function startInactiveRoleRemoval() {
		$cmd = ROOT . DS . 'bin' . DS . 'cake InactiveRoleRemoval';
		$logs = ROOT . DS . 'logs' . DS . 'RemoveInactiveRoles.log & echo $!';
		$shellCmd = $cmd . ' >> ' . $logs;

		try {
			$pid = exec($shellCmd);
			Log::write('debug', $shellCmd);
		} catch(\Exception $ex) {
			Log::write('error', __METHOD__ . ' exception when removing inactive roles : '. $ex);
		}
	}

	private function shellErrorRecovery() {
		$SystemProcesses = TableRegistry::get('SystemProcesses');
		$processes = $SystemProcesses->getErrorProcesses();
		foreach ($processes as $process) {
			$id = $process['id'];
			$model = $process['model'];
			$params = $process['params'];
			$eventName = $process['callable_event'];
			$executedCount = $process['executed_count'];
			$modelTable = TableRegistry::get($model);
			if (!empty($eventName)) {
				$event = $modelTable->dispatchEvent('Shell.'.$eventName, [$id, $executedCount, $params]);
			}
		}
	}
}
