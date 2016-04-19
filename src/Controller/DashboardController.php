<?php
namespace App\Controller;

use ArrayObject;
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
			'StudentAdmission' 	=> ['className' => 'Institution.StudentAdmission', 'actions' => ['edit']],
			'StudentDropout' 	=> ['className' => 'Institution.StudentDropout', 'actions' => ['edit']],
		];
		
		$this->loadComponent('Workbench', [
			'models' => [
				'Institution.TransferApprovals' => ['version' => 1],
				'Institution.StudentAdmission' => ['version' => 1],
				'Institution.StudentDropout' => ['version' => 1],
				'Institution.InstitutionSurveys' => ['version' => 2],
				'Institution.StaffPositionProfiles' => ['version' => 1],
				'Institution.StaffTransferApprovals' => ['version' => 1],
				'Institution.StaffTransferRequests' => ['version' => 1]
			]
		]);
    }

    public function beforeFilter(Event $event) {
    	parent::beforeFilter($event);

    	$header = __('Home Page');
		$this->set('contentHeader', $header);
    }

    public function onInitialize(Event $event, Table $model, ArrayObject $extra) {
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
