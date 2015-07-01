<?php
namespace Workflow\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

class WorkflowsController extends AppController
{
	public function initialize() {
		parent::initialize();

		$this->ControllerAction->model('Workflow.Workflows');
		$this->loadComponent('Paginator');
    }

    public function beforeFilter(Event $event) {
    	parent::beforeFilter($event);
        $header = __('Workflow');
        $this->Navigation->addCrumb('Workflow', ['plugin' => 'Workflow', 'controller' => 'Workflows', 'action' => 'index']);
        $this->set('contentHeader', $header);
	}
}
