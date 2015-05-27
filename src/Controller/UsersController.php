<?php
namespace App\Controller;
use Cake\Event\Event;

class UsersController extends AppController {
	public function initialize() {
		parent::initialize();
		$this->ControllerAction->model('SecurityUsers');
		$this->loadComponent('Paginator');
    }

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		//pr($this->Users->fields);die;
		$this->SecurityUsers->fields['password']['visible'] = false;
		$this->SecurityUsers->fields['status']['type'] = 'select';
		$this->SecurityUsers->fields['status']['options'] = ['Inactive', 'Active'];
		$this->SecurityUsers->fields['privileges']['type'] = 'select';
		$this->SecurityUsers->fields['privileges']['options'] = ['User', 'Super User'];
		$this->set('contentHeader', 'Users');
		$this->Message->alert('general.add.success');
	}

	public function login() {
		$this->layout = false;
		$username = '';
		$password = '';

		if ($this->request->is('post') && $this->request->data['submit'] == 'login') {
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
	
    public function logout() {
		//$this->Auth->logout();
		//$this->Session->destroy();
		$action = ['plugin' => false, 'controller' => 'Users', 'action' => 'login'];
        return $this->redirect($action);
    }
}
