<?php
namespace Workflow\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

class WorkflowStepsController extends AppController
{
	public function initialize() {
		parent::initialize();

		$this->ControllerAction->model('Workflow.WorkflowSteps');
		$this->loadComponent('Paginator');
    }

	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events['ControllerAction.onInitialize'] = 'onInitialize';
    	$events['ControllerAction.beforePaginate'] = 'beforePaginate';
    	return $events;
    }

    public function beforeFilter(Event $event) {
    	parent::beforeFilter($event);
	}

    public function onInitialize($event, $model) {
		$header = __('Workflow Steps');

		$this->Navigation->addCrumb('Workflow Steps', ['plugin' => 'Workflow', 'controller' => 'WorkflowSteps', 'action' => 'index']);

		$this->set('contentHeader', $header);
    }

    public function beforePaginate($event, $model, $options) {
		return $options;
    }
}
