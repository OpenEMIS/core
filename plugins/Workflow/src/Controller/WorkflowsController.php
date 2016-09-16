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
        Log::write('debug', 'Is School Based: ' . $isSchoolBased);
        Log::write('debug', 'Next Step Id: ' . $nextStepId);

        $assigneeOptions = [];
        if (!is_null($nextStepId)) {
            $WorkflowStepsRoles = TableRegistry::get('Workflow.WorkflowStepsRoles');
            $stepRoles = $WorkflowStepsRoles->getRolesByStep($nextStepId);
            Log::write('debug', 'Roles By Step:');
            Log::write('debug', $stepRoles);

            if (!empty($stepRoles)) {
                $SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
                $Areas = TableRegistry::get('Area.Areas');
                $Institutions = TableRegistry::get('Institution.Institutions');

                if ($isSchoolBased) {
                    $session = $this->request->session();

                    if ($session->check('Institution.Institutions.id')) {
                        $institutionId = $session->read('Institution.Institutions.id');
                        $institutionObj = $Institutions->find()->where([$Institutions->aliasField('id') => $institutionId])->contain(['Areas'])->first();
                        $securityGroupId = $institutionObj->security_group_id;
                        $areaObj = $institutionObj->area;

                        Log::write('debug', $areaObj);
                        Log::write('debug', 'Institution Id: ' . $institutionId);
                        Log::write('debug', 'Security Group Id: ' . $securityGroupId);

                        // School based assignee
                        $where = [
                            $SecurityGroupUsers->aliasField('security_group_id') => $securityGroupId,
                            $SecurityGroupUsers->aliasField('security_role_id IN ') => $stepRoles
                        ];
                        $schoolBasedAssigneeQuery = $SecurityGroupUsers
                            ->find('userList', ['where' => $where])
                            ->find('assignedStaff', ['institution_id' => $institutionId, 'security_roles' => $stepRoles]);

                        Log::write('debug', 'School based assignee query:');
                        Log::write('debug', $schoolBasedAssigneeQuery->sql());

                        $schoolBasedAssigneeOptions = $schoolBasedAssigneeQuery->toArray();
                        Log::write('debug', 'School based assignee:');
                        Log::write('debug', $schoolBasedAssigneeOptions);
                        // End

                        // Region based assignee
                        $where = [$SecurityGroupUsers->aliasField('security_role_id IN ') => $stepRoles];
                        $regionBasedAssigneeQuery = $SecurityGroupUsers
                            ->find('userList', ['where' => $where, 'area' => $areaObj]);

                        Log::write('debug', 'Region based assignee query:');
                        Log::write('debug', $regionBasedAssigneeQuery->sql());

                        $regionBasedAssigneeOptions = $regionBasedAssigneeQuery->toArray();
                        Log::write('debug', 'Region based assignee:');
                        Log::write('debug', $regionBasedAssigneeOptions);
                        // End

                        $assigneeOptions = $schoolBasedAssigneeOptions + $regionBasedAssigneeOptions;
                    } else {
                        Log::write('debug', 'Institution Id not found.');
                    }
                } else {
                    $where = [$SecurityGroupUsers->aliasField('security_role_id IN ') => $stepRoles];
                    $assigneeQuery = $SecurityGroupUsers
                        ->find('userList', ['where' => $where]);

                    Log::write('debug', 'Non-School based assignee query:');
                    Log::write('debug', $assigneeQuery->sql());

                    $assigneeOptions = $assigneeQuery->toArray();
                }                
            }
        }
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
