<?php
namespace FieldOption\Controller;

use FieldOption\Controller\AppController;
use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\Utility\Inflector;

class FieldOptionsController extends AppController {
	public function initialize() {
		parent::initialize();

		// $this->ControllerAction->model('FieldOption.FieldOptionValues', ['!search'], ['deleteStrategy' => 'transfer']);
		$this->ControllerAction->model('FieldOption.FieldOptionValues', ['!search']);
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		$header = 'Field Options';
		
		$this->Navigation->addCrumb($header, ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'index']);
		$session = $this->request->session();
		$action = $this->request->params['action'];
		
		$this->set('contentHeader', __($header));
	}

	public function onInitialize(Event $event, $model) {
		
	}
}
