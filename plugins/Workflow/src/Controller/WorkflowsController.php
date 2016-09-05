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

        $nextStepId = $this->request->query('next_step_id');
        $isSchoolBased = true;
        Log::write('debug', 'Next Step Id: ' . $nextStepId);
        Log::write('debug', 'Is School Based: ' . $isSchoolBased);

        $assigneeOptions = [];
        if (!is_null($nextStepId)) {
            $WorkflowStepsRoles = TableRegistry::get('Workflow.WorkflowStepsRoles');
            $stepRoles = $WorkflowStepsRoles->getRolesByStep($nextStepId);
            Log::write('debug', 'Roles By Step:');
            Log::write('debug', $stepRoles);

            if (!empty($stepRoles)) {
                $Assignees = TableRegistry::get('User.Users');
                $SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
                $SecurityGroupInstitutions = TableRegistry::get('Security.SecurityGroupInstitutions');
                $Staff = TableRegistry::get('Institution.Staff');
                $InstitutionPositions = TableRegistry::get('Institution.InstitutionPositions');
                $StaffPositionTitles = TableRegistry::get('Institution.StaffPositionTitles');
                $select = [
                    $Assignees->aliasField('id'),
                    $Assignees->aliasField('openemis_no'),
                    $Assignees->aliasField('first_name'),
                    $Assignees->aliasField('middle_name'),
                    $Assignees->aliasField('third_name'),
                    $Assignees->aliasField('last_name'),
                    $Assignees->aliasField('preferred_name')
                ];
                $group = [$Assignees->aliasField('id')];

                if ($isSchoolBased) {
                    $session = $this->request->session();
                    // if (!$this->AccessControl->isAdmin() && $session->check('Institution.Institutions.id')) {
                    if ($session->check('Institution.Institutions.id')) {
                        $userId = $this->Auth->user('id');
                        $institutionId = $session->read('Institution.Institutions.id');
                        Log::write('debug', 'Login User Id: ' . $userId);
                        Log::write('debug', 'Institution Id: ' . $institutionId);

                        $staffPositionRoles = $StaffPositionTitles
                            ->find('list', ['keyField' => 'security_role_id', 'valueField' => 'security_role_id'])
                            ->where([$StaffPositionTitles->aliasField('security_role_id IN ') => $stepRoles])
                            ->toArray();
                        Log::write('debug', 'Staff Position Roles:');
                        Log::write('debug', $staffPositionRoles);

                        $otherRoles = array_diff($stepRoles, $staffPositionRoles);
                        Log::write('debug', 'Other Roles:');
                        Log::write('debug', $otherRoles);

                        $schoolBasedAssigneeQuery = null;
                        if (!empty($staffPositionRoles)) {
                            $schoolBasedAssigneeQuery = $Assignees->find('list', ['keyField' => 'id', 'valueField' => 'name_with_id'])
                                ->select($select)
                                ->innerJoin(
                                    [$Staff->alias() => $Staff->table()],
                                    [
                                        $Staff->aliasField('staff_id = ') . $Assignees->aliasField('id'),
                                        $Staff->aliasField('institution_id') => $institutionId
                                    ]
                                )
                                ->innerJoin(
                                    [$InstitutionPositions->alias() => $InstitutionPositions->table()],
                                    [
                                        $InstitutionPositions->aliasField('id = ') . $Staff->aliasField('institution_position_id'),
                                        $InstitutionPositions->aliasField('institution_id') => $institutionId
                                    ]
                                )
                                ->innerJoin(
                                    [$StaffPositionTitles->alias() => $StaffPositionTitles->table()],
                                    [
                                        $StaffPositionTitles->aliasField('id = ') . $InstitutionPositions->aliasField('staff_position_title_id'),
                                        $StaffPositionTitles->aliasField('security_role_id IN ') => $staffPositionRoles
                                    ]
                                )
                                ->group($group);

                            Log::write('debug', 'School based assignee query:');
                            Log::write('debug', $schoolBasedAssigneeQuery->sql());
                        }

                        $assigneeQuery = $Assignees->find('list', ['keyField' => 'id', 'valueField' => 'name_with_id'])
                            ->select($select)
                            ->innerJoin(
                                [$SecurityGroupUsers->alias() => $SecurityGroupUsers->table()],
                                [$SecurityGroupUsers->aliasField('security_user_id =') . $Assignees->aliasField('id')]
                            )
                            ->innerJoin(
                                [$SecurityGroupInstitutions->alias() => $SecurityGroupInstitutions->table()],
                                [
                                    $SecurityGroupInstitutions->aliasField('security_group_id =') . $SecurityGroupUsers->aliasField('security_group_id'),
                                    $SecurityGroupInstitutions->aliasField('institution_id') => $institutionId
                                ]
                            )
                            ->group($group);

                        Log::write('debug', 'Non-School based assignee query:');
                        Log::write('debug', $assigneeQuery->sql());

                        if (!is_null($schoolBasedAssigneeQuery)) {
                            $assigneeQuery->unionAll($schoolBasedAssigneeQuery);
                        }

                        Log::write('debug', 'Assignee query after union:');
                        Log::write('debug', $assigneeQuery->sql());

                        $assigneeOptions = $assigneeQuery->toArray();
                    } else {
                        // return empty
                        $assigneeQuery->where([$Assignees->aliasField('id') => -1]);
                    }
                } else {
                    $assigneeQuery = $Assignees->find('list', ['keyField' => 'id', 'valueField' => 'name_with_id'])
                        ->select($select)
                        ->innerJoin(
                            [$SecurityGroupUsers->alias() => $SecurityGroupUsers->table()],
                            [
                                $SecurityGroupUsers->aliasField('security_user_id =') . $Assignees->aliasField('id'),
                                $SecurityGroupUsers->aliasField('security_role_id IN ') => $stepRoles
                            ]
                        )
                        ->group($group);

                    Log::write('debug', 'Non-School based assignee query:');
                    Log::write('debug', $assigneeQuery->sql());

                    $assigneeOptions = $assigneeQuery->toArray();
                }
            }
        }
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
