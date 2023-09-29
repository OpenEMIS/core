<?php
namespace Training\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Datasource\ResultSetInterface;
use Cake\Event\Event;
use Cake\Log\Log;
use App\Model\Table\ControllerActionTable;
use Cake\Network\Request;

class TrainingApplicationsTable extends ControllerActionTable
{
    // Workflow Steps - category
    const TO_DO = 1;
    const IN_PROGRESS = 2;
    const DONE = 3;

    public function initialize(array $config)
    {
        $this->table('staff_training_applications');
        parent::initialize($config);
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Sessions', ['className' => 'Training.TrainingSessions', 'foreignKey' => 'training_session_id']);
        $this->belongsTo('Staff', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users', 'foreignKey' => 'assignee_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);

        $this->addBehavior('Institution.InstitutionWorkflowAccessControl');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Dashboard' => ['index']
        ]);
        $this->addBehavior('Workflow.Workflow');
        $this->toggle('add', true);
        $this->toggle('edit', true);
        $this->toggle('remove', false);
    }

    private $workflowEvents = [
        [
            'value' => 'Workflow.onAssignTrainingSession',
            'text' => 'Assign Trainees to Training Sessions',
            'description' => 'Performing this action will assign the trainee to the training sessions.',
            'method' => 'onAssignTrainingSession'
        ],
        [
            'value' => 'Workflow.onWithdrawTrainingSession',
            'text' => 'Withdrawal from Training Sessions',
            'description' => 'Performing this action will withdraw the trainee from assigned training sessions of a particular course.',
            'method' => 'onWithdrawTrainingSession'
        ]
    ];

    public function getWorkflowEvents(Event $event, ArrayObject $eventsObject) {
        foreach ($this->workflowEvents as $key => $attr) {
            $attr['text'] = __($attr['text']);
            $attr['description'] = __($attr['description']);
            $eventsObject[] = $attr;
        }
    }

    public function implementedEvents() {
        $events = parent::implementedEvents();
        $events['Workflow.getEvents'] = 'getWorkflowEvents';
        foreach($this->workflowEvents as $event) {
            $events[$event['value']] = $event['method'];
        }
        return $events;
    }

    public function onWithdrawTrainingSession(Event $event, $id, Entity $workflowTransitionEntity) {
        $entity = $this->get($id);
        $staffId = $entity->staff_id;
        $sessionId = $entity->training_session_id;
        $TrainingSessionsTraineesTable = TableRegistry::get('Training.TrainingSessionsTrainees');
        $trainingSessionsTraineeArr = [
            'training_session_id' => $sessionId,
            'trainee_id' => $staffId,
            'status' => 2
        ];
        $newEntity = $TrainingSessionsTraineesTable->newEntity($trainingSessionsTraineeArr);
        $TrainingSessionsTraineesTable->save($newEntity);
    }

    public function onAssignTrainingSession(Event $event, $id, Entity $workflowTransitionEntity) {
        $entity = $this->get($id);
        $staffId = $entity->staff_id;
        $sessionId = $entity->training_session_id;
        $trainingSessionsTraineeArr = [
            'training_session_id' => $sessionId,
            'trainee_id' => $staffId,
            'status' => 1
        ];
        $TrainingSessionsTraineesTable = TableRegistry::get('Training.TrainingSessionsTrainees');
        $newEntity = $TrainingSessionsTraineesTable->newEntity($trainingSessionsTraineeArr);
        $TrainingSessionsTraineesTable->save($newEntity);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->setupTabElements();

        // Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Administration','Applications','Trainings');       
		if(!empty($is_manual_exist)){
			$btnAttr = [
				'class' => 'btn btn-xs btn-default icon-big',
				'data-toggle' => 'tooltip',
				'data-placement' => 'bottom',
				'escape' => false,
				'target'=>'_blank'
			];

			$helpBtn['url'] = $is_manual_exist['url'];
			$helpBtn['type'] = 'button';
			$helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
			$helpBtn['attr'] = $btnAttr;
			$helpBtn['attr']['title'] = __('Help');
			$extra['toolbarButtons']['help'] = $helpBtn;
		}
		// End POCOR-5188
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['Sessions.Courses']);
        $search = $this->getSearchKey();
        if (!empty($search)) {
            $extra['OR'] = [
                [$this->Sessions->Courses->aliasField('name').' LIKE' => '%' . $search . '%'],
                [$this->Institutions->aliasField('name').' LIKE' => $search . '%']
            ];
        }
    }

    public function indexbeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('status_id');
        $this->field('assignee_id', ['visible' => false]);
        $this->field('training_course_id');
        $this->setFieldOrder([
            'status_id', 'staff_id', 'institution_id', 'training_course_id', 'training_session_id','assignee_id'
        ]);
    }

    public function onGetTrainingCourseId(Event $event, Entity $entity)
    {
        if ($this->action == 'index') {
            return $entity->session->course->name;
        }
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('assignee_id', ['visible' => true]);
        $this->setFieldOrder([
            'status_id', 'staff_id', 'institution_id', 'training_session_id','assignee_id'
        ]);
    }

    private function setupTabElements()
    {
        $tabElements = $this->controller->getSessionTabElements();
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'Applications');
    }

    public function findWorkbench(Query $query, array $options)
    {
        $controller = $options['_controller'];
        $controller->loadComponent('AccessControl');
        $session = $controller->request->session();

        $userId = $session->read('Auth.User.id');
        $Statuses = $this->Statuses;
        $doneStatus = self::DONE;
        $InstitutionsTable = $this->Institutions;
        $AccessControl = $controller->AccessControl;

        $query
            ->select([
                $this->aliasField('id'),
                $this->aliasField('status_id'),
                $this->aliasField('institution_id'),
                $this->aliasField('modified'),
                $this->aliasField('created'),
                $this->Statuses->aliasField('name'),
                $this->Staff->aliasField('openemis_no'),
                $this->Staff->aliasField('first_name'),
                $this->Staff->aliasField('middle_name'),
                $this->Staff->aliasField('third_name'),
                $this->Staff->aliasField('last_name'),
                $this->Staff->aliasField('preferred_name'),
                $this->Sessions->aliasField('code'),
                $this->Sessions->aliasField('name'),
                $this->Sessions->Courses->aliasField('code'),
                $this->Sessions->Courses->aliasField('name'),
                $this->Institutions->aliasField('code'),
                $this->Institutions->aliasField('name'),
                $this->CreatedUser->aliasField('openemis_no'),
                $this->CreatedUser->aliasField('first_name'),
                $this->CreatedUser->aliasField('middle_name'),
                $this->CreatedUser->aliasField('third_name'),
                $this->CreatedUser->aliasField('last_name'),
                $this->CreatedUser->aliasField('preferred_name')
            ])
            ->contain([$this->Staff->alias(), 'Sessions.Courses', $this->Institutions->alias(), $this->CreatedUser->alias(),'Assignees'])
            ->matching($this->Statuses->alias(), function ($q) use ($Statuses, $doneStatus) {
                return $q->where([$Statuses->aliasField('category <> ') => $doneStatus]);
            })
            ->where([$this->aliasField('assignee_id') => $userId,
                    'Assignees.super_admin IS NOT'=>1]) //POCOR-7102
            ->order([$this->aliasField('created') => 'DESC'])
            ->formatResults(function (ResultSetInterface $results) use ($userId, $AccessControl, $InstitutionsTable) {

                return $results->map(function ($row) use ($userId, $AccessControl, $InstitutionsTable) {
                    $roleIds = $InstitutionsTable->getInstitutionRoles($userId, $row->institution_id);
                    if ($AccessControl->isAdmin() || $AccessControl->check(['controller' => 'Institutions', 'action' => 'StaffTrainingApplications', 'view'], $roleIds)) {
                        $url = [
                            'plugin' => 'Institution',
                            'controller' => 'Institutions',
                            'action' => 'StaffTrainingApplications',
                            'view',
                            $this->paramsEncode(['id' => $row->id]),
                            'institution_id' => $row->institution_id
                        ];
                    } else {
                        $url = [
                            'plugin' => 'Training',
                            'controller' => 'Trainings',
                            'action' => 'Applications',
                            'view',
                            $this->paramsEncode(['id' => $row->id])
                        ];
                    }

                    if (is_null($row->modified)) {
                        $receivedDate = $this->formatDate($row->created);
                    } else {
                        $receivedDate = $this->formatDate($row->modified);
                    }

                    $row['url'] = $url;
                    $row['status'] = __($row->_matchingData['Statuses']->name);
                    $row['request_title'] = sprintf(__('%s applying for session %s in %s'), $row->staff->name_with_id, $row->session->code_name, $row->session->course->code_name);
                    $row['institution'] = $row->institution->code_name;
                    $row['received_date'] = $receivedDate;
                    $row['requester'] = $row->created_user->name_with_id;

                    return $row;
                });
            });

        return $query;
    }

    //POCOR-6925
    public function onUpdateFieldAssigneeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $workflowModel = 'Administration > Training > Applications';
            $workflowModelsTable = TableRegistry::get('workflow_models');
            $workflowStepsTable = TableRegistry::get('workflow_steps');
            $Workflows = TableRegistry::get('Workflow.Workflows');
            $workModelId = $Workflows
                            ->find()
                            ->select(['id'=>$workflowModelsTable->aliasField('id'),
                            'workflow_id'=>$Workflows->aliasField('id'),
                            'is_school_based'=>$workflowModelsTable->aliasField('is_school_based')])
                            ->LeftJoin([$workflowModelsTable->alias() => $workflowModelsTable->table()],
                                [
                                    $workflowModelsTable->aliasField('id') . ' = '. $Workflows->aliasField('workflow_model_id')
                                ])
                            ->where([$workflowModelsTable->aliasField('name')=>$workflowModel])->first();
            $workflowId = $workModelId->workflow_id;
            $isSchoolBased = $workModelId->is_school_based;
            $workflowStepsOptions = $workflowStepsTable
                            ->find()
                            ->select([
                                'stepId'=>$workflowStepsTable->aliasField('id'),
                            ])
                            ->where([$workflowStepsTable->aliasField('workflow_id') => $workflowId])
                            ->first();
            $stepId = $workflowStepsOptions->stepId;
            $session = $request->session();
            if ($session->check('Institution.Institutions.id')) {
                $institutionId = $session->read('Institution.Institutions.id');
            }
            $institutionId = $institutionId;
            $assigneeOptions = [];
            if (!is_null($stepId)) {
                $WorkflowStepsRoles = TableRegistry::get('Workflow.WorkflowStepsRoles');
                $stepRoles = $WorkflowStepsRoles->getRolesByStep($stepId);
                if (!empty($stepRoles)) {
                    $SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
                    $Areas = TableRegistry::get('Area.Areas');
                    $Institutions = TableRegistry::get('Institution.Institutions');
                    if ($isSchoolBased) {
                        if (is_null($institutionId)) {                        
                            Log::write('debug', 'Institution Id not found.');
                        } else {
                            $institutionObj = $Institutions->find()->where([$Institutions->aliasField('id') => $institutionId])->contain(['Areas'])->first();
                            $securityGroupId = $institutionObj->security_group_id;
                            $areaObj = $institutionObj->area;
                            // School based assignee
                            $where = [
                                'OR' => [[$SecurityGroupUsers->aliasField('security_group_id') => $securityGroupId],
                                        ['Institutions.id' => $institutionId]],
                                $SecurityGroupUsers->aliasField('security_role_id IN ') => $stepRoles
                            ];
                            $schoolBasedAssigneeQuery = $SecurityGroupUsers
                                    ->find('userList', ['where' => $where])
                                    ->leftJoinWith('SecurityGroups.Institutions');
                            $schoolBasedAssigneeOptions = $schoolBasedAssigneeQuery->toArray();
                            
                            // Region based assignee
                            $where = [$SecurityGroupUsers->aliasField('security_role_id IN ') => $stepRoles];
                            $regionBasedAssigneeQuery = $SecurityGroupUsers
                                        ->find('UserList', ['where' => $where, 'area' => $areaObj]);
                            
                            $regionBasedAssigneeOptions = $regionBasedAssigneeQuery->toArray();
                            // End
                            $assigneeOptions = $schoolBasedAssigneeOptions + $regionBasedAssigneeOptions;
                        }
                    } else {
                        $where = [$SecurityGroupUsers->aliasField('security_role_id IN ') => $stepRoles];
                        $assigneeQuery = $SecurityGroupUsers
                                ->find('userList', ['where' => $where])
                                ->order([$SecurityGroupUsers->aliasField('security_role_id') => 'DESC']);
                        $assigneeOptions = $assigneeQuery->toArray();
                    }
                }
            }
            $attr['type'] = 'chosenSelect';
            $attr['attr']['multiple'] = false;
            $attr['select'] = false;
            $attr['options'] = ['' => '-- ' . __('Select Assignee') . ' --'] + $assigneeOptions;
            $attr['onChangeReload'] = 'changeStatus';
            return $attr;
        }
    }
}
