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
                $Users = TableRegistry::get('User.Users');
                $UserGroups = TableRegistry::get('Security.UserGroups');
                $SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
                $SecurityGroupAreas = TableRegistry::get('Security.SecurityGroupAreas');
                $Areas = TableRegistry::get('Area.Areas');

                $Institutions = TableRegistry::get('Institution.Institutions');
                $StaffStatuses = TableRegistry::get('Staff.StaffStatuses');
                $Staff = TableRegistry::get('Institution.Staff');
                $InstitutionPositions = TableRegistry::get('Institution.InstitutionPositions');
                $StaffPositionTitles = TableRegistry::get('Institution.StaffPositionTitles');

                $select = [
                    $Users->aliasField('id'),
                    $Users->aliasField('openemis_no'),
                    $Users->aliasField('first_name'),
                    $Users->aliasField('middle_name'),
                    $Users->aliasField('third_name'),
                    $Users->aliasField('last_name'),
                    $Users->aliasField('preferred_name')
                ];
                $group = [$Users->aliasField('id')];

                if ($isSchoolBased) {
                    $session = $this->request->session();

                    if ($session->check('Institution.Institutions.id')) {
                        $userId = $this->Auth->user('id');
                        $institutionId = $session->read('Institution.Institutions.id');
                        $institutionObj = $Institutions->find()->where([$Institutions->aliasField('id') => $institutionId])->contain(['Areas'])->first();
                        $securityGroupId = $institutionObj->security_group_id;
                        $areaObj = $institutionObj->area;
                        $assignedStatus = $StaffStatuses->getIdByCode('ASSIGNED');
                        $today = date('Y-m-d');

                        Log::write('debug', $areaObj);
                        Log::write('debug', 'Login User Id: ' . $userId);
                        Log::write('debug', 'Institution Id: ' . $institutionId);
                        Log::write('debug', 'Security Group Id: ' . $securityGroupId);
                        Log::write('debug', 'Assigned Status Id: ' . $assignedStatus);

                        // School based assignee
                        $schoolBasedAssigneeQuery = $SecurityGroupUsers
                            ->find('list', ['keyField' => $Users->aliasField('id'), 'valueField' => $Users->aliasField('name_with_id')])
                            ->select($select)
                            ->contain([$Users->alias()])
                            ->innerJoin(
                                [$Staff->alias() => $Staff->table()],
                                [
                                    $Staff->aliasField('staff_id = ') . $SecurityGroupUsers->aliasField('security_user_id'),
                                    $Staff->aliasField('institution_id') => $institutionId,
                                    $Staff->aliasField('staff_status_id') => $assignedStatus,
                                    'OR' => [
                                        [
                                            $Staff->aliasField('end_date IS NULL'),
                                            $Staff->aliasField('start_date <= ') => $today
                                        ],
                                        [
                                            $Staff->aliasField('end_date IS NOT NULL'),
                                            $Staff->aliasField('start_date <= ') => $today,
                                            $Staff->aliasField('end_date >= ') => $today
                                        ]
                                    ]
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
                                    $StaffPositionTitles->aliasField('security_role_id IN ') => $stepRoles
                                ]
                            )
                            ->where([
                                $SecurityGroupUsers->aliasField('security_group_id') => $securityGroupId,
                                $SecurityGroupUsers->aliasField('security_role_id IN ') => $stepRoles
                            ])
                            ->group($group);

                        Log::write('debug', 'School based assignee query:');
                        Log::write('debug', $schoolBasedAssigneeQuery->sql());

                        $schoolBasedAssigneeOptions = $schoolBasedAssigneeQuery->toArray();
                        Log::write('debug', 'School based assignee:');
                        Log::write('debug', $schoolBasedAssigneeOptions);
                        // End

                        // Region based assignee
                        Log::write('debug', $Users->alias());
                        $regionBasedAssigneeQuery = $SecurityGroupUsers
                            ->find('list', ['keyField' => $Users->aliasField('id'), 'valueField' => $Users->aliasField('name_with_id')])
                            ->select($select)
                            ->contain([$Users->alias()])
                            ->matching('SecurityGroups.Areas', function ($q) use ($areaObj) {
                                return $q->where([
                                    'Areas.lft <= ' => $areaObj->lft,
                                    'Areas.rght >= ' => $areaObj->lft
                                ]);
                            })
                            ->where([$SecurityGroupUsers->aliasField('security_role_id IN ') => $stepRoles])
                            ->group($group);

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
                    $assigneeQuery = $SecurityGroupUsers
                        ->find('list', ['keyField' => $Users->aliasField('id'), 'valueField' => $Users->aliasField('name_with_id')])
                        ->select($select)
                        ->contain([$Users->alias()])
                        ->where([$SecurityGroupUsers->aliasField('security_role_id IN ') => $stepRoles])
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
