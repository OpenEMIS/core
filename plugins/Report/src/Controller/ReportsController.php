<?php
namespace Report\Controller;

use App\Controller\AppController;
use Cake\Event\Event;
use Cake\ORM\Table;

class ReportsController extends AppController {
	public function initialize() {
		parent::initialize();

		$this->ControllerAction->models = [
			'Institutions' 		=> ['className' => 'Report.Institutions', 'actions' => ['index']]
		];
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		$header = 'Reports';
		$this->Navigation->addCrumb($header, ['plugin' => 'Security', 'controller' => 'Securities', 'action' => 'index']);
		$this->Navigation->addCrumb($this->request->action);
	}

	public function onInitialize(Event $event, Table $table) {
		$header = __('Reports') . ' - ' . __($table->alias());
		$this->set('contentHeader', $header);
	}

	public function index() {
		return $this->redirect(['action' => 'Users']);
	}
}
