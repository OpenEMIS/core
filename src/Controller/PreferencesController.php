<?php
namespace App\Controller;

use Cake\Event\Event;

class PreferencesController extends AppController {
	public function initialize() {
		parent::initialize();

		$this->ControllerAction->model('Users');
		$this->ControllerAction->models = ['Users' => ['className' => 'Users']];
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		$header = __('Preferences');

		$this->Navigation->addCrumb('Preferences', ['plugin' => false, 'controller' => 'Preferences', 'action' => 'index']);

		$this->set('contentHeader', $header);
	}

	public function index() {
		$userId = $this->Auth->user('id');
		return $this->redirect(['plugin' => false, 'controller' => $this->name, 'action' => 'Users', 'view', $userId]);
	}
}
