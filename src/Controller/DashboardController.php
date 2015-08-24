<?php
namespace App\Controller;

use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\ORM\Table;
use App\Controller\AppController;

class DashboardController extends AppController {
	public function initialize() {
		parent::initialize();

		// $this->ControllerAction->model('Notices');
		// $this->loadComponent('Paginator');

		$this->ControllerAction->models = [
			'TransferApprovals' 	=> ['className' => 'Institution.TransferApprovals', 'actions' => ['edit']],
			'StudentAdmission' 	=> ['className' => 'Institution.StudentAdmission', 'actions' => ['edit']]
		];
		
		$this->loadComponent('Workbench', [
			'models' => [
				'Institution.TransferApprovals',
				'Institution.StudentAdmission',
				// 'Institution.StudentDropout'
			]
		]);
    }

    public function beforeFilter(Event $event) {
    	parent::beforeFilter($event);

    	$header = __('Dashboard');
		$this->set('contentHeader', $header);
    }

    public function onInitialize(Event $event, Table $model) {
    	$header = $model->getHeader($model->alias);
    	$this->set('contentHeader', $header);
    }

	public function index() {
		$workbenchData = $this->Workbench->getList();
		$noticeData = TableRegistry::get('Notices')->find('all')->order(['Notices.created desc'])->toArray();

		$this->set('workbenchData', $workbenchData);
		$this->set('noticeData', $noticeData);
	}
}
