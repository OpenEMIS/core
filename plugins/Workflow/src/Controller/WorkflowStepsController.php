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

    public function beforeFilter(Event $event) {
    	parent::beforeFilter($event);
        $header = __('Workflow Steps');
        $this->Navigation->addCrumb('Workflow Steps', ['plugin' => 'Workflow', 'controller' => 'WorkflowSteps', 'action' => 'index']);
        $this->set('contentHeader', $header);
	}

    public function beforePaginate($event, $model, $options) {
        list($workflowOptions, $selectedWorkflow) = array_values($this->WorkflowSteps->getSelectOptions());

        $this->set(compact('workflowOptions', 'selectedWorkflow'));
        $options['conditions'][] = [
            $model->aliasField('workflow_id') => $selectedWorkflow
        ];

		return $options;
    }
}
