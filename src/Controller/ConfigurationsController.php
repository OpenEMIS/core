<?php
namespace App\Controller;

use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\Utility\Inflector;

class ConfigurationsController extends AppController {
	public function initialize() {
		parent::initialize();

		$this->ControllerAction->model('ConfigItems', ['index', 'view', 'edit']);
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		$header = 'System Configurations';
		
		$this->Navigation->addCrumb($header, ['plugin' => null, 'controller' => $this->name, 'action' => 'index']);
		$session = $this->request->session();
		$action = $this->request->params['action'];
		
		$this->set('contentHeader', __($header));
	}
}
