<?php
namespace User\Controller;
use Cake\Event\Event;

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
		// $this->Message->alert('general.add.success');
		//pr($this->request->params);die;

		$this->Auth->allow(['add', 'logout', 'postLogin', 'updatePassword']);
		//$this->log($this->request->method(), 'debug');
	}

	public function login() {
		//return $this->redirect(['plugin' => 'Institution', 'controller' => 'Institutions', 'action'=> 'index']);
		$this->layout = false;
		$username = '';
		$password = '';//pr($this->request->method());

		if ($this->request->is('post') /*&& $this->request->data['submit'] == 'login'*/) {
			pr($this->request->data);die;
			return $this->redirect(['controller' => 'Institutions', 'action'=> 'index']);
			/*
			$username = $this->data['User']['username'];
			$this->log('[' . $username . '] Attempt to login as ' . $username . '@' . $_SERVER['REMOTE_ADDR'], 'security');
			if(!$this->RequestHandler->isAjax()) {
				$result = $this->Auth->login();

				if($result) {
					$this->log('[' . $username . '] Login successfully.', 'security');
					$userId = $this->Auth->user('id');
					
					//Redirect to the respective page
					return $this->redirect(array('controller' => 'Users', 'action'=> 'index'));
				} else {
					$this->Message->alert('security.login.fail', array('type' => 'error'));
				}
			}
			else {
				// ajax login implement here, if necessary
			}
			*/
		}
		
		if ($this->request->is('post') && $this->request->data['submit'] == 'reload') {
			//$username = $this->request->data['User']['username'];
			//$password = $this->request->data['User']['password'];
		}
		
		$this->set('username', $username);
		$this->set('password', $password);
	}

	public function postLogin() {
		$this->autoRender = false;
		
		if ($this->request->is('post')) {
			if (!empty($this->request->data('username'))) {
				$username = $this->request->data('username');
				$user = $this->Users->findByUsername($username)->first();
				if ($user) {
					$session = $this->request->session();
					$session->write('Users.updatePassword.username', $username);
					return $this->redirect(['action' => 'updatePassword']);
				}
			}
			$user = $this->Auth->identify();
			if ($user) {pr($user);
	            $this->Auth->setUser($user);
	            if ($this->Auth->authenticationProvider()->needsPasswordRehash()) {
	            	pr('asd');die;
	                $user = $this->Users->get($this->Auth->user('id'));
	                $user->password = $this->request->data('password');
	                $this->Users->save($user);
	            }
	            return $this->redirect(['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'index']);
	            //return $this->redirect($this->Auth->redirectUrl());
	        } else {
	        	$this->Alert->error('security.login.fail');
	        	return $this->redirect(['action' => 'login']);
	        }
			//return $this->redirect(['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'index']);
		}
	}

	// this action is to allow users to migrate old password hash to use the new password hasher
	// this function will be removed once all users have updated their old password
	public function updatePassword() {
		$this->layout = false;
		$salt = 'thisismysalt';
		$username = '';
		if ($this->request->is('get')) {
			$session = $this->request->session();
			if ($session->check('Users.updatePassword.username')) {
				$this->Alert->success('security.login.updatePassword', ['closeButton' => false]);
				$username = $session->read('Users.updatePassword.username');
			} else {
				return $this->redirect(['action' => 'login']);
			}
		} else {
			pr($this->request->data);
		}
		$this->set('username', $username);
	}

    public function logout() {
		//$this->Auth->logout();
		//$this->Session->destroy();
		$action = ['plugin' => 'User', 'controller' => 'Users', 'action' => 'login'];
        return $this->redirect($action);
    }
}
