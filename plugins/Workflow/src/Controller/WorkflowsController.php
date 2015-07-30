<?php
namespace Workflow\Controller;

use ArrayObject;
use App\Controller\AppController;
use Cake\ORM\Table;
use Cake\Event\Event;

class WorkflowsController extends AppController
{
	public function initialize() {
		parent::initialize();

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
                'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Workflows'],
                'text' => __('Workflows')
            ],
            'Steps' => [
                'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Steps'],
                'text' => __('Steps')
            ]
        ];

        $this->set('tabElements', $tabElements);
        $this->set('selectedAction', $this->request->action);
    }

    public function onInitialize(Event $event, Table $model) {
        $header = __('Workflow');

        $header .= ' - ' . $model->getHeader($model->alias);
        $this->Navigation->addCrumb('Workflow', ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => $model->alias]);
        $this->Navigation->addCrumb($model->getHeader($model->alias));

        $this->set('contentHeader', $header);
    }
}
