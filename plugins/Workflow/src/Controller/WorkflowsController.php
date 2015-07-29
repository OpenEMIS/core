<?php
namespace Workflow\Controller;

use ArrayObject;
use App\Controller\AppController;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\Event\Event;

class WorkflowsController extends AppController
{
	public function initialize() {
		parent::initialize();

		//$this->ControllerAction->model('Workflow.Workflows');
        $this->ControllerAction->models = [
            'Workflows' => ['className' => 'Workflow.Workflows'],
            'Steps' => ['className' => 'Workflow.WorkflowSteps']
        ];
		$this->loadComponent('Paginator');
    }

    public function beforeFilter(Event $event) {
        parent::beforeFilter($event);

        $tabElements = [
            'Workflows' => [
                'url' => ['plugin' => 'Workflow', 'controller' => 'Workflows', 'action' => 'Workflows'],
                'text' => __('Workflows')
            ],
            'Steps' => [
                'url' => ['plugin' => 'Workflow', 'controller' => 'Workflows', 'action' => 'Steps'],
                'text' => __('Steps')
            ]
        ];

        $this->set('tabElements', $tabElements);
        $this->set('selectedAction', $this->request->action);
    }

    public function onInitialize(Event $event, Table $model) {
        $header = __('Workflow');

        $header .= ' - ' . $model->getHeader($model->alias);
        $this->Navigation->addCrumb('Workflow', ['plugin' => 'Workflow', 'controller' => 'Workflows', 'action' => $model->alias]);
        $this->Navigation->addCrumb($model->getHeader($model->alias));

        $this->set('contentHeader', $header);
    }

    public function beforePaginate(Event $event, Table $model, Query $query, ArrayObject $options) {
        if ($model->alias == 'Steps') {
            list($workflowOptions, $selectedWorkflow) = array_values($this->WorkflowSteps->getSelectOptions());
            $this->set(compact('workflowOptions', 'selectedWorkflow'));

            $query->where([$model->aliasField('workflow_id') => $selectedWorkflow]);
        }
    }
}
