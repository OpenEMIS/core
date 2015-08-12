<?php
namespace Report\Controller;

use App\Controller\AppController;
use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

class ReportsController extends AppController {
	public function initialize() {
		parent::initialize();

		$this->ControllerAction->models = [
			'Institutions' 		=> ['className' => 'Report.Institutions', 'actions' => ['index', 'add']]
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

	public function ajaxGetReportProgress() {
		$this->autoRender = false;

		$userId = $this->Auth->user('id');
		$id = $this->request->query['id'];

		$fields = array(
			'ReportProgress.status',
			'ReportProgress.modified',
			'ReportProgress.current_records',
			'ReportProgress.total_records'
		);
		$ReportProgress = TableRegistry::get('Report.ReportProgress');
		$entity = $ReportProgress->find()->where(['id' => $id])->first();
		$data = [];

		if ($obj) {
			if ($obj->total_records > 0) {
				$data['percent'] = intval($obj->current_records / $obj->total_records * 100);
			} else {
				$data['percent'] = 0;
			}
			$data['modified'] = $obj->modified;
			$data['status'] = $obj->status;
		}
		echo json_encode($data);
		die;
	}
}
