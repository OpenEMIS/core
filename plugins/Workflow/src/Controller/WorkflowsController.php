<?php
namespace Workflow\Controller;

use ArrayObject;
use App\Controller\AppController;
use Cake\ORM\TableRegistry;
use Cake\ORM\Table;
use Cake\Event\Event;
use Cake\Core\Configure;
use Cake\Log\Log;

class WorkflowsController extends AppController
{
	public function initialize()
    {
		parent::initialize();

        $this->ControllerAction->models = [
            'Workflows' => ['className' => 'Workflow.Workflows', 'options' => ['deleteStrategy' => 'transfer']],
            'Steps' => ['className' => 'Workflow.WorkflowSteps', 'options' => ['deleteStrategy' => 'restrict']],
            'Actions' => ['className' => 'Workflow.WorkflowActions'],
            'Statuses' => ['className' => 'Workflow.WorkflowStatuses'],
        ];
		$this->loadComponent('Paginator');
    }

    // CAv4
    public function Rules() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Workflow.WorkflowRules']); }
    // End

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);

        $hasWorkflowsAccess = $this->AccessControl->check([$this->name, 'Workflows', 'view']);
        $hasStepsAccess = $this->AccessControl->check([$this->name, 'Steps', 'view']);
        $hasActionsAccess = $this->AccessControl->check([$this->name, 'Actions', 'view']);
        $hasRulesAccess = $this->AccessControl->check([$this->name, 'Rules', 'view']);
        $hasStatusesAccess = $this->AccessControl->check([$this->name, 'Statuses', 'view']);

        $tabElements = [];
        if ($hasWorkflowsAccess) {
            $url = ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Workflows'];
            $paramsQuery = $this->paramsQuery(['model']);
            $url = array_merge($url, $paramsQuery);

            $tabElements['Workflows'] = [
                'url' => $url,
                'text' => __('Workflows')
            ];
        }

        if ($hasStepsAccess) {
            $url = ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Steps'];
            $paramsQuery = $this->paramsQuery(['model', 'workflow']);
            $url = array_merge($url, $paramsQuery);

            $tabElements['Steps'] = [
                'url' => $url,
                'text' => __('Steps')
            ];
        }

        if ($hasActionsAccess) {
            $url = ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Actions'];
            $paramsQuery = $this->paramsQuery();
            $url = array_merge($url, $paramsQuery);

            $tabElements['Actions'] = [
                'url' => $url,
                'text' => __('Actions')
            ];
        }

        if ($hasRulesAccess) {
            $url = ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Rules'];
            $paramsQuery = $this->paramsQuery();
            $url = array_merge($url, $paramsQuery);

            $tabElements['Rules'] = [
                'url' => $url,
                'text' => __('Rules')
            ];
        }

        if ($hasStatusesAccess) {
            $url = ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Statuses'];
            $paramsQuery = $this->paramsQuery(['model']);
            $url = array_merge($url, $paramsQuery);

            $tabElements['Statuses'] = [
                'url' => $url,
                'text' => __('Statuses')
            ];
        }

        $selectedAction = $this->request->action;
        // add this logic to highlight the tab correctly
        if (!$hasWorkflowsAccess && !$hasStepsAccess && !$hasActionsAccess) {
            $selectedAction = 'Statuses';
        } else if (!$hasWorkflowsAccess && !$hasStepsAccess) {
            $selectedAction = 'Actions';
        } else if (!$hasWorkflowsAccess) {
            $selectedAction = 'Steps';
        }

        if (in_array('Cases', (array) Configure::read('School.excludedPlugins'))) {
            if (isset($tabElements['Rules'])) {
                unset($tabElements['Rules']);
            }
        }

        $tabElements = $this->TabPermission->checkTabPermission($tabElements);
        $this->set('tabElements', $tabElements);
        $this->set('selectedAction', $this->request->action);
    }

    public function onInitialize(Event $event, Table $model, ArrayObject $extra)
    {
        $header = __('Workflow');

        $header .= ' - ' . $model->getHeader($model->alias);
        $this->Navigation->addCrumb('Workflow', ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => $model->alias]);
        $this->Navigation->addCrumb($model->getHeader($model->alias));

        $this->set('contentHeader', $header);
    }

    public function ajaxGetAssignees()
    {
        $this->viewBuilder()->layout('ajax');

        $isSchoolBased = $this->request->query('is_school_based');
        $nextStepId = $this->request->query('next_step_id');
        $autoAssignAssignee = $this->request->query('auto_assign_assignee');

        if (!$autoAssignAssignee) {
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
            $options = $assigneeOptions;

        } else {
            Log::write('debug', 'Auto Assign Assignee');

            $defaultKey = '';
            $options = ['-1' => __('Auto Assign')];
        }

        $responseData = [
            'default_key' => $defaultKey,
            'assignees' => $options
        ];

        $this->response->body(json_encode($responseData, JSON_UNESCAPED_UNICODE));
        $this->response->type('json');

        return $this->response;
    }

    private function paramsQuery($keys=[])
    {
        $requestQuery = $this->request->query;

        if (!empty($keys)) {
            $params = [];
            foreach ($keys as $value) {
                if (array_key_exists($value, $requestQuery)) {
                    $params[$value] = $requestQuery[$value];
                }
            }

            if (!empty($params)) {
                return $params;
            }
        }

        return $requestQuery;
    }
}
