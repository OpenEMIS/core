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

    public function getWorkflowEvents(Event $event, ArrayObject $eventsObject)
    {
        foreach ($this->workflowEvents as $key => $attr) {
            $attr['text'] = __($attr['text']);
            $attr['description'] = __($attr['description']);
            $eventsObject[] = $attr;
        }
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Workflow.getEvents'] = 'getWorkflowEvents';
        foreach ($this->workflowEvents as $event) {
            $events[$event['value']] = $event['method'];
        }
        return $events;
    }

    public function onWithdrawTrainingSession(Event $event, $id, Entity $workflowTransitionEntity)
    {
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

    /**
     * POCOR-8033
     * @param Event $event
     * @param Entity $entity
     * @param ArrayObject $extra
     */
    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('institution_id', ['type' => 'select', 'after' => '', 'entity' => $entity]);
        $this->field('assignee_id', ['type' => 'select', 'after' => 'staff_id', 'entity' => $entity]);
        $this->field('staff_id', ['type' => 'select', 'entity' => $entity, 'after' => 'institution_id', 'visible' => true]);
        $this->field('training_session_id', ['type' => 'select', 'after' => 'assignee_id', 'entity' => $entity]);
    }

    public function onAssignTrainingSession(Event $event, $id, Entity $workflowTransitionEntity)
    {
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
        $is_manual_exist = $this->getManualUrl('Administration', 'Applications', 'Trainings');
        if (!empty($is_manual_exist)) {
            $btnAttr = [
                'class' => 'btn btn-xs btn-default icon-big',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'target' => '_blank'
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
        $query->contain(['Sessions.Courses', 'Staff', 'Assignees', 'Institutions']);
        $search = $this->getSearchKey();
        if (!empty($search)) {
            $extra['OR'] = [
                [$this->Sessions->Courses->aliasField('name') . ' LIKE' => '%' . $search . '%'],
                [$this->Institutions->aliasField('name') . ' LIKE' => $search . '%']
            ];
        }
        // POCOR-8033:start
        $sortList = [
            'Institutions.name',
            'Assignees.first_name',
            'Staff.first_name',
            'Courses.name',
            'Sessions.name'];
        if (array_key_exists('sortWhitelist', $extra['options'])) {
            $sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
        }
        $extra['options']['sortWhitelist'] = $sortList;
        // POCOR-8033:end
    }

    public function indexbeforeAction(Event $event, ArrayObject $extra)
    {

        $this->field('status_id');
        // POCOR-8033:start
        $this->field('assignee_id', ['type' => 'integer', 'sort' => ['field' => 'Assignees.first_name']]);
        $this->field('staff_id', ['type' => 'integer', 'sort' => ['field' => 'Staff.first_name']]);
        $this->field('institution_id', ['type' => 'integer', 'sort' => ['field' => 'Institutions.name']]);
        $this->field('training_course_id', ['type' => 'integer', 'sort' => ['field' => 'Courses.name']]);
        $this->field('training_session_id', ['type' => 'integer', 'sort' => ['field' => 'Sessions.name']]);
        // POCOR-8033:end
        $this->setFieldOrder([
            'status_id',
            'staff_id',
            'institution_id',
            'training_course_id',
            'training_session_id',
            'assignee_id'
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
            'status_id',
            'staff_id',
            'institution_id',
            'training_session_id',
            'assignee_id'
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
            ->contain([$this->Staff->alias(), 'Sessions.Courses', $this->Institutions->alias(), $this->CreatedUser->alias(), 'Assignees'])
            ->matching($this->Statuses->alias(), function ($q) use ($Statuses, $doneStatus) {
                return $q->where([$Statuses->aliasField('category <> ') => $doneStatus]);
            })
            ->where([$this->aliasField('assignee_id') => $userId,
                'Assignees.super_admin IS NOT' => 1])//POCOR-7102
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

    //POCOR-8033 start
    /**
     * @param Event $event
     * @param array $attr
     * @param $action
     * @param Request $request
     * @return array
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public function onUpdateFieldAssigneeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action != 'add' && $action != 'edit') {
            return $attr;
        }
        if ($action == 'edit') {
            $entity = $attr['entity'];
            $attr['type'] = 'readonly';
            $attr['value'] = $entity->assignee_id;
            $attr['attr']['value'] = $entity->assignee->name_with_id;
        }
        if ($action == 'add') {
            $alias = $this->alias();
            $areaList = isset($request->data) ? $request->data[$alias]['area_id']['_ids'] : null;
            $institutionList = $this->getInstitutionOptions($areaList);
            $keysArray = array_keys($institutionList);
            $institution_id = $keysArray[0];
            $assigneesOptions = $this->getAssigneesOptions($request, $institution_id);
            $attr['type'] = 'chosenSelect';
            $attr['attr']['multiple'] = false;
            $attr['select'] = false;
            $attr['options'] = $assigneesOptions;
        }
        return $attr;
    }


    /**
     * @param Request $request
     * @param $institutionId
     * @return array
     */
    private function getAssigneesOptions(Request $request, $institutionId)
    {
        $alias = $this->alias();
        $filter_id = isset($request['data'][$alias]['training_application_type_id']) ? $request['data'][$alias]['training_application_type_id'] : 0;
        if ($institutionId == 0) {
            $institutionId = isset($request['data'][$alias]['institution_id']) ? $request['data'][$alias]['institution_id'] : 0;
        }
        $assignees_all = $this->findAssigneeOptions(0, $institutionId);
        $assignees_filtered = $this->findAssigneeOptions($filter_id, $institutionId);
        $options = ['' => '-- ' . __('Select Assignee') . ' --'] + $assignees_all + $assignees_filtered;
        return $options;
    }



    /**
     * @param Event $event
     * @param array $attr
     * @param $action
     * @param Request $request
     * @return array
     * @author Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public function onUpdateFieldInstitutionId(Event $event, array $attr, $action, Request $request)
    {
        $alias = $this->alias();
//        $this->log($request['data'][$alias], 'debug');

        $areaList = isset($request->data) ? $request->data[$alias]['area_id']['_ids'] : null;
        if ($action == 'edit') {
            $entity = $attr['entity'];
            $attr['type'] = 'readonly';
            $attr['value'] = $entity->institution_id;
            $attr['attr']['value'] = $entity->institution->code_name;
            return $attr;
        }
        if ($action == 'add') {
            $institutionList = $this->getInstitutionOptions($areaList);
            $attr['type'] = 'chosenSelect';
            $attr['attr']['multiple'] = false;
            $attr['options'] = $institutionList;
            $attr['onChangeReload'] = 'changeStatus';
        }
        return $attr;
    }

    public function onUpdateFieldStaffId(Event $event, array $attr, $action, Request $request)
    {
        $entity = $attr['entity'];
        if ($action == 'add') {
            $attr['type'] = 'select';
            $attr['options'] = $this->getStaffOptions($entity);
        } elseif ($action == 'edit') {
            $attr['type'] = 'readonly';
            $attr['value'] = $entity->staff_id;
            $attr['attr']['value'] = $entity->staff->name_with_id;
        }
        return $attr;
    }

    /**
     * @param null $areaList
     * @return array
     * @author Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private function getInstitutionOptions($areaList = null)
    {
        $Institutions = TableRegistry::get('Institution.Institutions');
        $InstitutionStatuses = TableRegistry::get('institution_statuses');
        $institutionQuery = $Institutions
            ->find('list', [
                'keyField' => 'id',
                'valueField' => 'code_name'
            ])
            ->innerJoin([$InstitutionStatuses->alias() => $InstitutionStatuses->table()],
                [$InstitutionStatuses->aliasField('id = ')
                    . $Institutions->aliasField('institution_status_id')])
            ->where([$InstitutionStatuses->aliasField('code') => 'ACTIVE'])
            ->order([
                $Institutions->aliasField('code') => 'ASC',
                $Institutions->aliasField('name') => 'ASC'
            ]);
        if ($areaList) {
            $areaIds = $areaList;
            $allgetArea = $this->getChildren($areaList, $areaIds);
            if (empty($allgetArea)) {
                $allgetArea = [-1];
            }
            $allgetArea = array_unique($allgetArea);
            $institutionQuery->where([$Institutions->aliasField('area_id IN') => $allgetArea]);
        }
        $institutionList = $institutionQuery->toArray();

        return $institutionList;
    }

    /**
     * @param Entity $entity
     * @return array
     */
    private function getStaffOptions(Entity $entity)
    {
        $StaffStatuses = TableRegistry::get('Staff.StaffStatuses');
        $assignedStatus = $StaffStatuses->getIdByCode('ASSIGNED');

        $institutionId = isset($entity['institution_id']) ? $entity['institution_id'] : 0;
        $Staff = TableRegistry::get('Institution.Staff');
        $staffOptions = $Staff
            ->find('list', ['keyField' => 'staff_id', 'valueField' => 'staff_name'])
            ->matching('Users')
            ->where([$Staff->aliasField('institution_id') => $institutionId,
                $Staff->aliasField('staff_status_id') => $assignedStatus])
            ->order(['Users.first_name', 'Users.last_name'])// POCOR-2547 sort list of staff and student by name
            ->toArray();

        return $staffOptions;
    }

    /**
     * POCOR-8015, POCOR-8033
     * @param $filter_id
     * @param $institutionId
     * @return array
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private function findAssigneeOptions($filter_id, $institutionId = 0)
    {
        $workflowModel = 'Administration > Training > Applications';
        $workflowModelsTable = TableRegistry::get('workflow_models');
        $workflowStepsTable = TableRegistry::get('workflow_steps');
        $workflowFiltersTable = TableRegistry::get('workflows_filters');
        $Workflows = TableRegistry::get('Workflow.Workflows');


        $workModels = $Workflows
            ->find()
            ->select(['id' => $workflowModelsTable->aliasField('id'),
                'workflow_id' => $Workflows->aliasField('id'),
                'is_school_based' => $workflowModelsTable->aliasField('is_school_based')])
            ->LeftJoin([$workflowModelsTable->alias() => $workflowModelsTable->table()],
                [
                    $workflowModelsTable->aliasField('id') . ' = ' . $Workflows->aliasField('workflow_model_id')
                ])
            ->where([$workflowModelsTable->aliasField('name') => $workflowModel]);
        if ($filter_id != 0) {
            $workModels = $workModels->innerJoin([$workflowFiltersTable->alias() => $workflowFiltersTable->table()],
                [$workflowFiltersTable->aliasField('workflow_id') . ' = ' . $Workflows->aliasField('id'),
                    $workflowFiltersTable->aliasField('filter_id') . ' = ' . $filter_id
                ]);
        }
        $workModelId = $workModels->first();

        $workflowId = $workModelId->workflow_id;
        $isSchoolBased = $workModelId->is_school_based;
        $workflowStepsOptions = $workflowStepsTable
            ->find()
            ->select([
                'stepId' => $workflowStepsTable->aliasField('id'),
            ])
            ->where([$workflowStepsTable->aliasField('workflow_id') => $workflowId])
            ->first();
        $stepId = $workflowStepsOptions->stepId;

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
        return $assigneeOptions;
    }

    public function editBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query
            ->contain([
                'Institutions', 'Staff', 'Assignees'
            ]);
    }
    //POCOR-8033: end
}
