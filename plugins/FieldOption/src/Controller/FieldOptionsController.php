<?php
namespace FieldOption\Controller;

use FieldOption\Controller\AppController;
use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\Utility\Inflector;

class FieldOptionsController extends AppController {
	public function initialize() {
		parent::initialize();

		$this->ControllerAction->model('FieldOption.FieldOptionValues');
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		$header = 'Field Options';
		
		$this->Navigation->addCrumb($header, ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'index']);
		$session = $this->request->session();
		$action = $this->request->params['action'];

		// if ($action == 'index') {
		// 	$session->delete('Institutions.id');
		// }

		// if ($session->check('Institutions.id') || $action == 'view') {
		// 	$id = 0;
		// 	if ($session->check('Institutions.id')) {
		// 		$id = $session->read('Institutions.id');
		// 	} else if (isset($this->request->pass[0])) {
		// 		$id = $this->request->pass[0];
		// 	}
		// 	if (!empty($id)) {
		// 		$obj = $this->Institutions->get($id);
		// 		$name = $obj->name;
		// 		$this->Navigation->addCrumb($name, ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'view', $id]);
		// 	} else {
		// 		return $this->redirect(['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'index']);
		// 	}
		// }

		
		$this->set('contentHeader', __($header));
	}

	public function onInitialize(Event $event, $model) {
		
	}

	public function beforePaginate(Event $event, Table $table, $options) {
		
	}
}
