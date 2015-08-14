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

	public function getFeatureOptions($module) {
		$options = [];
		if ($module == 'Institutions') {
			$options = [
				'Report.Institutions' => __('Overview'),
				'Report.InstitutionPositions' => __('Positions'),
				'Report.InstitutionProgrammes' => __('Programmes')
			];
		}
		return $options;
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

		if ($entity) {
			if ($entity->total_records > 0) {
				$data['percent'] = intval($entity->current_records / $entity->total_records * 100);
			} else {
				$data['percent'] = 0;
			}
			$data['modified'] = $ReportProgress->formatDateTime($entity->modified);
			$data['status'] = $entity->status;
		}
		echo json_encode($data);
		die;
	}

	public function download($id) {
		$this->controller->autoRender = false;
		$ReportProgress = TableRegistry::get('Report.ReportProgress');

		$entity = $ReportProgress->find()->where(['id' => $id])->first();
		$path = $entity->file_path;
		if (!empty($path)) {
			$filename = basename($path);
			header("Pragma: public", true);
			header("Expires: 0"); // set expiration time
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Content-Type: application/force-download");
			header("Content-Type: application/octet-stream");
			header("Content-Type: application/download");
			header("Content-Disposition: attachment; filename=".$filename);
			header("Content-Transfer-Encoding: binary");
			header("Content-Length: ".filesize($path));
			echo file_get_contents($path);
		}
	}
}
