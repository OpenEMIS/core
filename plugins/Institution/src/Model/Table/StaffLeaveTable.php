<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\Datasource\ResultSetInterface;

use Workflow\Model\Table\WorkflowStepsTable as WorkflowSteps;
use App\Model\Table\ControllerActionTable;

class StaffLeaveTable extends ControllerActionTable
{
    // Workflow Steps - category
    const TO_DO = 1;
    const IN_PROGRESS = 2;
    const DONE = 3;

    public function initialize(array $config)
    {
        $this->table('institution_staff_leave');
        parent::initialize($config);

        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('StaffLeaveTypes', ['className' => 'Staff.StaffLeaveTypes']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);

        $this->addBehavior('ControllerAction.FileUpload', [
            // 'name' => 'file_name',
            // 'content' => 'file_content',
            'size' => '10MB',
            'contentEditable' => true,
            'allowable_file_types' => 'all',
            'useDefaultName' => true
        ]);
        $this->addBehavior('Workflow.Workflow');
        $this->addBehavior('Institution.InstitutionWorkflowAccessControl');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Dashboard' => ['index']
        ]);

        // POCOR-4047 to get staff profile data
        $this->addBehavior('Institution.StaffProfile');
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('date_to', 'ruleCompareDateReverse', [
                'rule' => ['compareDateReverse', 'date_from', true]
            ])
            ->allowEmpty('file_content')
            ->requirePresence('assignee_id', function ($context) { // assignee_id is mandatory only when it is editable
                if (isset($context['data']['staff_leave_type_id']) && !empty($context['data']['staff_leave_type_id'])) {
                    $model = $context['providers']['table'];
                    $entity = $model->newEntity();
                    $entity = $model->patchEntity($entity, $context['data'], ['validate' => false]);

                    return $model->assigneeEditable($entity);
                }

                return false;
            });
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.InstitutionStaff.afterDelete'] = 'institutionStaffAfterDelete';
        $events['Model.StaffPositionProfiles.getAssociatedModelData'] = 'staffPositionProfilesGetAssociatedModelData';
        return $events;
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $dateFrom = date_create($entity->date_from);
        $dateTo = date_create($entity->date_to);
        $diff = date_diff($dateFrom, $dateTo, true);
        $numberOfDays = $diff->format("%a");
        $entity->number_of_days = ++$numberOfDays;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('number_of_days', [
            'visible' => ['index' => true, 'view' => true, 'edit' => false, 'add' => false]
        ]);
        $this->field('file_name', [
            'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]
        ]);
        $this->field('file_content', [
            'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]
        ]);
        $this->field('staff_id', ['type' => 'hidden']);

        $this->setFieldOrder(['staff_leave_type_id', 'date_from', 'date_to', 'number_of_days', 'comments', 'file_name', 'file_content']);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $userId = $this->getUserId();
        $query->where([
            $this->aliasField('staff_id') => $userId
        ]);
    }

    public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $this->setupTabElements();
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('staff_leave_type_id');
        $this->field('assignee_id', ['entity' => $entity]); //send entity information

        // after $this->field(), field ordering will mess up, so need to reset the field order
        $this->setFieldOrder(['staff_leave_type_id', 'date_from', 'date_to', 'number_of_days', 'comments', 'file_name', 'file_content', 'assignee_id']);
    }

    public function onUpdateFieldFileName(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'view') {
            $attr['type'] = 'hidden';
        } else if ($action == 'add' || $action == 'edit') {
            $attr['type'] = 'hidden';
        }

        return $attr;
    }

    public function onUpdateFieldStaffId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $userId = $this->getUserId();

            $attr['value'] = $userId;
        }

        return $attr;
    }

    public function onUpdateFieldStaffLeaveTypeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $attr['type'] = 'select';
            $attr['onChangeReload'] = 'changeStaffLeaveType';
        }

        return $attr;
    }

    public function onUpdateFieldAssigneeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $entity = $attr['entity'];

            $assigneeEditable = $this->assigneeEditable($entity);

            if ($assigneeEditable && $entity->has('staff_leave_type_id')) {
                $filterId = $entity->staff_leave_type_id;

                $registryAlias = $this->registryAlias();
                $workflowModelEntity = $this->getWorkflowSetup($registryAlias);

                $workflowEntity = $this->getWorkflow($registryAlias, $entity, $filterId);

                $workflowId = $workflowEntity->id;
                $firstStepEntity = $this->getFirstWorkflowStep($workflowId);
                $firstStepId = $firstStepEntity->id;

                $isSchoolBased = $workflowModelEntity->is_school_based;
                $params = [
                    'is_school_based' => $isSchoolBased,
                    'workflow_step_id' => $firstStepId
                ];

                $session = $request->session();
                if ($session->check('Institution.Institutions.id')) {
                    $institutionId = $session->read('Institution.Institutions.id');
                    $params['institution_id'] = $institutionId;
                }

                $SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
                $assigneeOptions = $SecurityGroupUsers->getAssigneeList($params);

                $attr['type'] = 'select';
                $attr['visible'] = true;
                $attr['options'] = $assigneeOptions;
            }
        }

        return $attr;
    }

    private function setupTabElements()
    {
        $options['type'] = 'staff';
        $userId = $this->getUserId();
        if (!is_null($userId)) {
            $options['user_id'] = $userId;
        }

        $tabElements = $this->controller->getCareerTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }

    public function getUserId()
    {
        $session = $this->request->session();
        if ($session->check('Staff.Staff.id')) {
            $userId = $session->read('Staff.Staff.id');
            return $userId;
        }

        return null;
    }

    public function institutionStaffAfterDelete(Event $event, Entity $institutionStaffEntity)
    {
        $staffLeaveData = $this->find()
            ->where([
                $this->aliasField('staff_id') => $institutionStaffEntity->staff_id,
                $this->aliasField('institution_id') => $institutionStaffEntity->institution_id,
            ])
            ->toArray();

        foreach ($staffLeaveData as $key => $staffLeaveEntity) {
            $this->delete($staffLeaveEntity);
        }
    }

    public function findWorkbench(Query $query, array $options)
    {
        $controller = $options['_controller'];
        $session = $controller->request->session();

        $userId = $session->read('Auth.User.id');
        $Statuses = $this->Statuses;
        $doneStatus = self::DONE;

        $query
            ->select([
                $this->aliasField('id'),
                $this->aliasField('status_id'),
                $this->aliasField('staff_id'),
                $this->aliasField('institution_id'),
                $this->aliasField('modified'),
                $this->aliasField('created'),
                $this->Statuses->aliasField('name'),
                $this->Users->aliasField('openemis_no'),
                $this->Users->aliasField('first_name'),
                $this->Users->aliasField('middle_name'),
                $this->Users->aliasField('third_name'),
                $this->Users->aliasField('last_name'),
                $this->Users->aliasField('preferred_name'),
                $this->StaffLeaveTypes->aliasField('name'),
                $this->Institutions->aliasField('code'),
                $this->Institutions->aliasField('name'),
                $this->CreatedUser->aliasField('openemis_no'),
                $this->CreatedUser->aliasField('first_name'),
                $this->CreatedUser->aliasField('middle_name'),
                $this->CreatedUser->aliasField('third_name'),
                $this->CreatedUser->aliasField('last_name'),
                $this->CreatedUser->aliasField('preferred_name')
            ])
            ->contain([$this->Users->alias(), $this->StaffLeaveTypes->alias(), $this->Institutions->alias(), $this->CreatedUser->alias()])
            ->matching($this->Statuses->alias(), function ($q) use ($Statuses, $doneStatus) {
                return $q->where([$Statuses->aliasField('category <> ') => $doneStatus]);
            })
            ->where([$this->aliasField('assignee_id') => $userId])
            ->order([$this->aliasField('created') => 'DESC'])
            ->formatResults(function (ResultSetInterface $results) {
                return $results->map(function ($row) {
                    $url = [
                        'plugin' => 'Institution',
                        'controller' => 'Institutions',
                        'action' => 'StaffLeave',
                        'view',
                        $this->paramsEncode(['id' => $row->id]),
                        'user_id' => $row->staff_id,
                        'institution_id' => $row->institution_id
                    ];

                    if (is_null($row->modified)) {
                        $receivedDate = $this->formatDate($row->created);
                    } else {
                        $receivedDate = $this->formatDate($row->modified);
                    }

                    $row['url'] = $url;
                    $row['status'] = __($row->_matchingData['Statuses']->name);
                    $row['request_title'] = sprintf(__('%s of %s'), $row->staff_leave_type->name, $row->user->name_with_id);
                    $row['institution'] = $row->institution->code_name;
                    $row['received_date'] = $receivedDate;
                    $row['requester'] = $row->created_user->name_with_id;

                    return $row;
                });
            });

        return $query;
    }

    public function getModelAlertData($threshold)
    {
        $thresholdArray = json_decode($threshold, true);

        $conditions = [
            1 => ('DATEDIFF(' . $this->aliasField('date_to') . ', NOW())' . ' BETWEEN 0 AND ' . $thresholdArray['value']), // before
            // 2 => ('DATEDIFF(NOW(), ' . $this->aliasField('date_to') . ')' . ' BETWEEN 0 AND ' . $thresholdArray['value']), // after
        ];

        // will do the comparison with threshold when retrieving the absence data
        $licenseData = $this->find()
            ->select([
                'StaffLeaveTypes.name',
                'date_from',
                'date_to',
                'Institutions.id',
                'Institutions.name',
                'Institutions.code',
                'Institutions.address',
                'Institutions.postal_code',
                'Institutions.contact_person',
                'Institutions.telephone',
                'Institutions.fax',
                'Institutions.email',
                'Institutions.website',
                'Users.id',
                'Users.openemis_no',
                'Users.first_name',
                'Users.middle_name',
                'Users.third_name',
                'Users.last_name',
                'Users.preferred_name',
                'Users.email',
                'Users.address',
                'Users.postal_code',
                'Users.date_of_birth'
            ])
            ->contain(['Statuses', 'Users', 'Institutions', 'StaffLeaveTypes', 'Assignees'])
            ->where([
                $this->aliasField('staff_leave_type_id') => $thresholdArray['staff_leave_type'],
                $this->aliasField('date_to') . ' IS NOT NULL',
                $conditions[$thresholdArray['condition']]
            ])
            ->hydrate(false)
            ;

        return $licenseData->toArray();
    }

    public function assigneeEditable(Entity $entity)
    {
        // by default assignee is not editable
        // if security roles is configured for the first step, then assignee is editable in the first step
        if ($entity->has('staff_leave_type_id')) {
            $registryAlias = $this->registryAlias();

            $isNew = $entity->has('id') ? false : true;
            $filterId = $entity->has('staff_leave_type_id') ? $entity->staff_leave_type_id : null;
            $statusId = $entity->has('status_id') ? $entity->status_id : null;

            $workflowEntity = $this->getWorkflow($registryAlias, $entity, $filterId);

            $workflowId = $workflowEntity->id;
            $firstStepEntity = $this->getFirstWorkflowStep($workflowId);

            $firstStepId = $firstStepEntity->id;
            // if is new or if is existing record and current status is first step
            if ($isNew || !$isNew && $statusId == $firstStepId) {
                if (!empty($firstStepEntity->security_roles)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getFirstWorkflowStep($workflowId)
    {
        $firstStepEntity = $this->Statuses
            ->find()
            ->matching('Workflows.WorkflowModels', function ($q) use ($workflowId) {
                return $q->where(['Workflows.id' => $workflowId]);
            })
            ->contain(['SecurityRoles'])
            ->where([
                $this->Statuses->aliasField('category') => WorkflowSteps::TO_DO
            ])
            ->first();

        return $firstStepEntity;
    }
}
