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
			'StudentWithdraw' 	=> ['className' => 'Institution.StudentWithdraw', 'actions' => ['edit']],
		];
		$this->attachAngularModules();
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Controller.SecurityAuthorize.isActionIgnored'] = 'isActionIgnored';
        return $events;
    }

    public function isActionIgnored(Event $event, $action)
    {
        return true;
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
		$this->set('ngController', 'DashboardCtrl as DashboardController');
		$this->set('noBreadcrumb', true);
	}

	private function attachAngularModules() {
		$action = $this->request->action;

		switch ($action) {
			case 'index':
				$this->Angular->addModules([
					'alert.svc',
					'dashboard.ctrl',
					'dashboard.svc'
				]);
				break;
		}
	}
}
