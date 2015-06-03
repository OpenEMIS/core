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
		$header = __('Workflow');

		$this->Navigation->addCrumb('Workflow', ['plugin' => 'Workflow', 'controller' => 'Workflows', 'action' => 'index']);

		$this->set('contentHeader', $header);
    }

    public function beforePaginate($event, $model, $options) {
		return $options;
    }
}
