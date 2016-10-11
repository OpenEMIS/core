<?php
namespace Workflow\Controller;

use ArrayObject;
use App\Controller\AppController;
use Cake\ORM\TableRegistry;
use Cake\ORM\Table;
use Cake\Event\Event;
use Cake\Log\Log;

class WorkflowsController extends AppController
{
	public function initialize() {
		parent::initialize();

        $this->ControllerAction->models = [
            'Workflows' => ['className' => 'Workflow.Workflows', 'options' => ['deleteStrategy' => 'transfer']],
            'Steps' => ['className' => 'Workflow.WorkflowSteps', 'options' => ['deleteStrategy' => 'restrict']],
            'Actions' => ['className' => 'Workflow.WorkflowActions'],
            'Statuses' => ['className' => 'Workflow.WorkflowStatuses'],
        ];
		$this->loadComponent('Paginator');
    }

    public function beforeFilter(Event $event) {
        parent::beforeFilter($event);

        $tabElements = [];
        if ($this->AccessControl->check([$this->name, 'Workflows', 'view'])) {
            $tabElements['Workflows'] = [
                'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Workflows'],
                'text' => __('Workflows')
            ];
        }

        if ($this->AccessControl->check([$this->name, 'Steps', 'view'])) {
            $tabElements['Steps'] = [
                'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Steps'],
                'text' => __('Steps')
            ];
            $tabElements['Actions'] = [
                'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Actions'],
                'text' => __('Actions')
            ];
        }

        if ($this->AccessControl->check([$this->name, 'Statuses', 'view'])) {
            $tabElements['Statuses'] = [
                'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Statuses'],
                'text' => __('Statuses')
            ];
        }

        $selectedAction = $this->request->action;
        if (!$this->AccessControl->check([$this->name, 'Workflows', 'view'])) {
            if ($this->AccessControl->check([$this->name, 'Steps', 'view'])) {
                $selectedAction = 'Steps';
            } elseif ($this->AccessControl->check([$this->name, 'Statuses', 'view'])) {
                $selectedAction = 'Statuses';
            }
        }

        $this->set('tabElements', $tabElements);
        $this->set('selectedAction', $this->request->action);
    }

    public function onInitialize(Event $event, Table $model, ArrayObject $extra) {
        $header = __('Workflow');

        $header .= ' - ' . $model->getHeader($model->alias);
        $this->Navigation->addCrumb('Workflow', ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => $model->alias]);
        $this->Navigation->addCrumb($model->getHeader($model->alias));

        $this->set('contentHeader', $header);
    }

    public function ajaxGetAssignees() {
        $this->viewBuilder()->layout('ajax');

        $isSchoolBased = $this->request->query('is_school_based');
        $nextStepId = $this->request->query('next_step_id');

        $SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
        $params = [
            'is_school_based' => $isSchoolBased,
            'workflow_step_id' => $nextStepId
        ];
        if ($isSchoolBased) {
            $session = $this->request->session();
            if ($session->check('Institution.Institutions.id')) {
                $institutionId = $session->read('Institution.Institutions.id');
                $params['institution_id'] = $institutionId;
            }
        }

        $assigneeOptions = $SecurityGroupUsers->getAssigneeList($params);

        Log::write('debug', 'Assignee:');
        Log::write('debug', $assigneeOptions);

        $defaultKey = empty($assigneeOptions) ? __('No options') : '-- '.__('Select').' --';
        $responseData = [
            'default_key' => $defaultKey,
            'assignees' => $assigneeOptions
        ];

        $this->response->body(json_encode($responseData, JSON_UNESCAPED_UNICODE));
        $this->response->type('json');

        return $this->response;
    }
}
