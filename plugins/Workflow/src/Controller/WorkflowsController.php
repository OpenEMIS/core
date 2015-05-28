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
    	$controller = $this;
    	$this->ControllerAction->onInitialize = function($model) use ($controller, $header) {
			// logic here
		};

		$this->ControllerAction->beforePaginate = function($model, $options) {
			// logic here
			return $options;
		};

		$this->set('contentHeader', $header);
	}
}
