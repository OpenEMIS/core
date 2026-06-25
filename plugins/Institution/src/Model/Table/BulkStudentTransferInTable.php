<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\EventInterface;
use Cake\Validation\Validator;
use Cake\Http\ServerRequest;
use Cake\Controller\Component;
use Cake\Utility\Hash;
use Cake\Log\Log;
use App\Model\Table\ControllerActionTable;

class BulkStudentTransferInTable extends ControllerActionTable
{
    private $_modelAlias = 'Institution.StudentTransferOut';
    private $_stepsOptions;
    private $_currentData;

    public function initialize(array $config): void
    {
        $this->setTable('workflow_steps');
        parent::initialize($config);

        $this->belongsTo('Workflows', ['className' => 'Workflow.Workflows']);
        $this->hasMany('WorkflowActions', ['className' => 'Workflow.WorkflowActions', 'foreignKey' => 'workflow_step_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentTransferIn', ['className' => 'Institution.InstitutionStudentTransfers', 'foreignKey' => 'status_id', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->toggle('index', false);
        $this->toggle('add', false);
        $this->toggle('view', false);
        $this->toggle('remove', false);
        $this->toggle('search', false);

        $steplists = $this
            ->find('list')
            ->contain([
                'Workflows.WorkflowModels'
            ])
            ->matching('WorkflowActions')
            ->where([
                'WorkflowModels.model' => $this->_modelAlias
            ])
            ->toArray();
        //remove open status because we are not getting start_date, end_date, institution class
        $option = array();
        //POCOR-6362 starts
        foreach ($steplists as $klist => $vlist) {
            if($vlist == 'Pending Approval From Receiving Institution' || $vlist == 'Pending Cancellation'){
                $option[$klist] = $vlist;
            }
        }//POCOR-6362 ends
        $this->_stepsOptions = $option;
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator
            ->notEmpty('status')
            ->notEmpty('action')
            ->notEmpty('assignee_id');
        return $validator;
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['Model.Navigation.breadcrumb'] = 'onGetBreadcrumb';
        $events['ControllerAction.Model.reconfirm'] = 'reconfirm';
        return $events;
    }

    public function onGetBreadcrumb(EventInterface $event, ServerRequest $request, Component $Navigation, $persona=false)
    {
        $url = ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StudentTransferIn'];
        $Navigation->substituteCrumb('Bulk Student Transfer In', 'Student Transfer In', $url);
        $Navigation->addCrumb('Bulk Student Transfer In');
    }

    public function editBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $request = $this->request;
        $session = $this->Session;

        $userId = $session->read('Auth.User.id');
        $superAdmin = $session->read('Auth.User.super_admin');
        //$institutionId = $session->read('Institution.Institutions.id');
        $institutionId = $this->getQueryString('institution_id');

        if ($request->is(['post', 'put'])) {
            $statusId = $request->getData($this->getAlias())['status'];
        } else {
            $statusId = key($this->_stepsOptions);
        }
        if($superAdmin || $this->checkUserAccess($statusId)){
            $stepId = $statusId;
        }else{
            $stepId = 0;
        }

//        $this->log($distinctStepsArray, 'debug');
        $query->contain([
            'WorkflowActions',
            'WorkflowActions.NextWorkflowSteps',
            'StudentTransferIn'=> function ($q) use ( $institutionId) {
                $q->where(['StudentTransferIn.institution_id' => $institutionId])
                    ->contain(['Users', 'Assignees', 'AcademicPeriods', 'EducationGrades', 'InstitutionClasses', 'Statuses','PreviousInstitutions']);
                    return $q;
            },
            'Workflows.WorkflowModels',
        ])
        ->where([
            'WorkflowModels.model' => $this->_modelAlias,
            $this->aliasField('id') => $stepId
        ], [], true);
//        $this->log($query->sql(), 'debug');
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        switch ($this->action) {
            case 'edit':
                $toolbarButtons = $extra['toolbarButtons'];
                $toolbarButtons['back']['url']['action'] = 'StudentTransferIn';
                $toolbarButtons['back']['url'][0] = 'index';
                break;
            case 'reconfirm':
                $sessionKey = $this->getRegistryAlias() . '.confirm';
                if ($this->Session->check($sessionKey)) {
                    $this->_currentData = $this->Session->read($sessionKey);
                }
                break;
        }
    }

    public function editAfterAction(EventInterface $event, $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function onUpdateFieldWorkflowId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        switch ($this->action) {
            case 'edit':
                $requestData = $request->getData($this->getAlias());
                $selectedStatus = isset($requestData['status']) ? $requestData['status'] : null;
            break;

            case 'reconfirm':
                $selectedStatus = $this->_currentData->status;
            break;

            default:
                break;
        }
        $attr['attr']['value'] = $selectedStatus;
        return $attr;
    }

    public function onUpdateFieldStatus(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        switch ($this->action) {
            case 'edit':
                $attr['type'] = 'select';
                $attr['select'] = false;
                $attr['options'] = $this->_stepsOptions;
                $attr['onChangeReload'] = 'changeStatus';
            break;

            case 'reconfirm':
                $selectedStatus = $this->_currentData['status'];
                $attr['attr']['value'] = $this->_stepsOptions[$selectedStatus];
                $attr['type'] = 'readonly';
            break;
        }
        return $attr;
    }

    public function addEditOnChangeStatus(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $data[$this->getAlias()]['action'] = null;
        $data[$this->getAlias()]['next_step'] = null;
        $data[$this->getAlias()]['assignee_id'] = null;
    }

    public function onUpdateFieldAction(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        switch ($this->action) {
            case 'edit':
                $workflowActions = $attr['entity']->workflow_actions;
                $options = Hash::combine($workflowActions, '{n}.id', '{n}.name');
                $attr['type'] = 'select';
                $attr['options'] = $options;
                $attr['onChangeReload'] = 'changeAction';
            break;

            case 'reconfirm':
                $sessionKey = $this->getRegistryAlias() . '.confirm';
                $workflowActionEntity = $this->getWorkflowActionEntity($this->_currentData);
                $attr['type'] = 'readonly';
                $attr['attr']['value'] = $workflowActionEntity['name'];
            break;

            default:
                break;
        }
        return $attr;
    }

    public function addEditOnChangeAction(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $data[$this->getAlias()]['next_step'] = null;
        $data[$this->getAlias()]['assignee_id'] = null;
    }

    public function onUpdateFieldNextStep(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        switch ($this->action) {
            case 'edit':
                $entity = $attr['entity'];
                $workflowActionEntity = $this->getWorkflowActionEntity($entity);
                if (isset($workflowActionEntity->next_workflow_step)) {
                    $attr['value'] = $workflowActionEntity->next_workflow_step->id;
                    $attr['attr']['value'] = $workflowActionEntity->next_workflow_step->name;
                }
                break;

            case 'reconfirm':
                $workflowActionEntity = $this->getWorkflowActionEntity($this->_currentData);
                if (isset($workflowActionEntity->next_workflow_step)) {
                    $attr['attr']['value'] = $workflowActionEntity->next_workflow_step->name;
                }
                break;

            default:
                break;
        }

        return $attr;
    }

    public function onUpdateFieldAssigneeId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        switch ($this->action) {
            case 'edit':
                $entity = $attr['entity'];
                $workflowActionEntity = $this->getWorkflowActionEntity($entity);
                $nextStepId = isset($workflowActionEntity->next_workflow_step) ? $workflowActionEntity->next_workflow_step->id : null;
                $autoAssignAssignee = 0;
                $workflowModelEntity = $this
                    ->find()
                    ->select('WorkflowModels.is_school_based')
                    ->contain([
                        'Workflows.WorkflowModels'
                    ])
                    ->where([
                        'WorkflowModels.model' => $this->_modelAlias
                    ])
                    ->enableHydration(false)
                    ->first();
                $isSchoolBased = $workflowModelEntity['WorkflowModels']['is_school_based'];
                if (!$autoAssignAssignee) {
                    $SecurityGroupUsers = TableRegistry::getTableLocator()->get('Security.SecurityGroupUsers');
                    $params = [
                        'is_school_based' => $isSchoolBased,
                        'workflow_step_id' => $nextStepId
                    ];
                    if ($isSchoolBased) {
                        $institutionId = $this->getQueryString('institution_id');
                        $params['institution_id'] = $institutionId;
                        /*$session = $this->request->getSession();
                        if ($session->check('Institution.Institutions.id')) {
                            $institutionId = $session->read('Institution.Institutions.id');
                            $params['institution_id'] = $institutionId;
                        }*/
                    }
                    // $assigneeOptions = $SecurityGroupUsers->getAssigneeList($params);
                    $assigneeOptions = [$this->Auth->user('id') => __('Auto Assign')]; //POCOR-7080
                }
                $attr['type'] = 'select';
                $attr['options'] = $assigneeOptions;
                break;

            case 'reconfirm':
                $SecurityUsers = TableRegistry::getTableLocator()->get('Security.Users');
                $value = $SecurityUsers
                    ->find()
                    ->where([$SecurityUsers->aliasField('id') => $this->_currentData->assignee_id])
                    ->first();
                $assigneeOptions = 'Auto Assign'; //POCOR-6961
                $attr['type'] = 'readonly';
                $attr['value'] = '-1';
                $attr['attr']['value'] = $assigneeOptions; //POCOR-6961
                break;

            default:
                break;
        }
        return $attr;
    }

    public function onUpdateFieldComment(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        switch ($this->action) {
            case 'reconfirm':
                $attr['attr']['disabled'] = 'disabled';
                break;

            default:
                break;
        }
        return $attr;
    }

    public function onUpdateFieldBulkStudentTransferIn(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        switch ($this->action) {
            case 'edit':
                $entity = $attr['entity'];
                $students = $entity->student_transfer_in;
                break;
            case 'reconfirm':
                $students  = $this->_currentData->student_transfer_in;
                $attr['selectedStudents'] = ($this->_currentData->has('students'))? $this->_currentData->students : [];
                break;
            default:
                break;
        }
        $attr['type'] = 'element';
        $attr['element'] = 'Institution.BulkStudentTransferIn/students';
        $attr['data'] = $students;
        return $attr;
    }

    public function reconfirm()
    {
        $this->Alert->info($this->aliasField('reconfirm'), ['reset' => true]);
        $this->setupFields();
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
        $url = [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'BulkStudentTransferIn',
            'edit'
        ];
        $sessionKey = $this->getRegistryAlias() . '.confirm';
        if ($this->Session->check($sessionKey)) {
            $currentEntity = $this->Session->read($sessionKey);
            $currentData = $this->Session->read($sessionKey.'Data');
        } else {
            $this->Alert->warning('general.notExists');
            return $this->controller->redirect($url);
        }
        if ($currentEntity && !empty($currentEntity)) {
            if ($this->request->is(['post', 'put'])) {
                if ($currentData instanceOf ArrayObject) {
                    $currentData = $currentData->getArrayCopy();
                }
                $currentEntity = $this->patchEntity($currentEntity, $currentData, []);
                return $this->saveBulkStudentTransferIn($currentEntity, new ArrayObject($currentData));
            }
            $this->controller->set('data', $currentEntity);
        } else {
            $this->Alert->warning('general.notExists');
            return $this->controller->redirect($url);
        }
        $this->ControllerAction->renderView('/ControllerAction/edit');
        return true;
    }

    public function editBeforeSave(EventInterface $event, Entity $entity, ArrayObject $data)
    {
        $process = function ($model, $entity) use ($event, $data) {
            $data = $data->getArrayCopy();
            // Removal of some fields that are not in use in the table validation
            $errors = $entity->getErrors();
            if (empty($errors)) {
                if (array_key_exists($this->getAlias(), $data)) {
                    $selectedStudent = false;
                    if (array_key_exists('students', $data[$this->getAlias()])) {
                        foreach ($data[$this->getAlias()]['students'] as $key => $value) {
                            if ($value['selected'] != 0) {
                                $selectedStudent = true;
                                break;
                            }
                        }
                    }
                    if ($selectedStudent) {
                        $queryString = $this->getQueryString();
                        $encodedQueryString = $this->paramsEncode($queryString);
                        // redirects to confirmation page
                        $url = [
                            'plugin' => 'Institution',
                            'controller' => 'Institutions',
                            'action' => 'BulkStudentTransferIn',
                            'reconfirm',
                            $encodedQueryString
                        ];
                        $this->currentEntity = $entity;
                        $session = $this->Session;
                        $session->write($this->getRegistryAlias().'.confirm', $entity);
                        $session->write($this->getRegistryAlias().'.confirmData', $data);
                        $this->currentEvent = $event;
                        $event->stopPropagation();
                        return $this->controller->redirect($url);
                    } else {
                        $this->Alert->warning($this->getAlias().'.noStudentSelected', ['reset' => true]);
                        return false;
                    }
                }
            } else {
                return false;
            }
        };
        return $process;
    }
    public function saveBulkStudentTransferIn(Entity $entity, ArrayObject $data)
    {
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
        $primaryKey = $this->StudentTransferIn->getPrimaryKey();
        $url = [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'StudentTransferIn',
            'index',
            $encodedQueryString
        ];
        $workflowTransitionObj = [];
        foreach ($data[$this->getAlias()]['students'] as $key => $studentObj) {
            if ($studentObj['selected']) {
                unset($studentObj['selected']);
                foreach ($entity->student_transfer_in as $key => $value) {
                    if ($value['id'] == $studentObj['id']) {
                        $existingEntityToUpdate = $value;
                        break;
                    }
                }

                $prevWorkflowStepName = $existingEntityToUpdate->status->name;
                $existingEntityToUpdate->status_id = $data[$this->getAlias()]['next_step'];
                $existingEntityToUpdate->assignee_id = $data[$this->getAlias()]['assignee_id'];
                $workflowModel = $entity->workflow->id;
                $workflowAction = $this->getWorkflowActionEntity($entity);
                if ($this->StudentTransferIn->save($existingEntityToUpdate)) {
                    if (!empty($workflowAction->event_key)) {
                        $id = $existingEntityToUpdate->$primaryKey;
                        $subject = $this->StudentTransferIn;
                        $eventKeys = explode(",", $workflowAction->event_key);

                        foreach ($eventKeys as $eventKey) {
                            $event = $subject->dispatchEvent($eventKey, [$id, $entity], $subject);
                            if ($event->isStopped()) {
                                return $event->getResult();
                            }
                        }
                    }
                    $workflowTransition = [];
                    $workflowTransition['comment'] = $data[$this->getAlias()]['comment'];
                    $workflowTransition['prev_workflow_step_name'] = $prevWorkflowStepName;
                    $workflowTransition['workflow_step_name'] = $workflowAction->next_workflow_step['name'];
                    $workflowTransition['workflow_action_name'] = $workflowAction['name'];
                    $workflowTransition['workflow_model_id'] = $workflowModel;
                    $workflowTransition['model_reference'] = $existingEntityToUpdate->$primaryKey;
                    $workflowTransitionObj[] = $workflowTransition;
                }
            }
        }
        $WorkflowTransitions = TableRegistry::getTableLocator()->get('Workflow.WorkflowTransitions');
        $workflowTransitionEntities = $WorkflowTransitions->newEntities($workflowTransitionObj);
        if ($WorkflowTransitions->saveMany($workflowTransitionEntities)) {
            //$this->Alert->success($this->aliasField('success'), ['reset' => true]);
            $this->Alert->success('general.bulk_student_transfer_in', ['reset' => true]);
            $session = $this->Session;
            $session->delete($this->getRegistryAlias() . '.confirm');
            $session->delete($this->getRegistryAlias() . '.Data');
        } else {
//            $this->log($entity->getErrors(), 'debug');
            $url['action'] = 'BulkStudentTransferIn';
            $url[0] = 'edit';
        }
        return $this->controller->redirect($url);
    }

    public function onGetFormButtons(EventInterface $event, ArrayObject $buttons)
    {
        switch ($this->action) {
            case 'edit':
                $buttons[0]['name'] = '<i class="fa fa-check"></i> ' . __('Next');
                break;

            case 'reconfirm':
                $confirmButton = $buttons[0];
                $cancelButton = $buttons[1];

                $confirmButton['name'] = '<i class="fa fa-check"></i> ' . __('Confirm');
                $cancelUrl = [
                            'plugin' => 'Institution',
                            'controller' => 'Institutions',
                            'action' => 'BulkStudentTransferIn',
                            'edit'
                        ];
                $cancelButton['url'] = $cancelUrl;
                $buttons[0] = $confirmButton;
                $buttons[1] = $cancelButton;
                break;

            default:
                break;
        }
    }

    private function getWorkflowActionEntity(Entity $entity){
        if ($entity->has('action')) {
            $selectedAction = $entity->action;
            $workflowActions = $entity->workflow_actions;
            foreach($workflowActions as $key => $actionEntity){
                if ($actionEntity->id == $selectedAction) {
                    return $actionEntity;
                }
            }
        }
        return null;
    }

    public function setupFields(Entity $entity = null)
    {
        $this->field('name', ['type' => 'hidden']);
        $this->field('category', ['type' => 'hidden']);
        $this->field('is_editable', ['type' => 'hidden']);
        $this->field('is_removable', ['type' => 'hidden']);
        $this->field('is_system_defined', ['type' => 'hidden']);
        $this->field('status', ['entity' => $entity]);
        $this->field('action', ['entity' => $entity]);
        $this->field('workflow_id', ['type' => 'hidden']);
        $this->field('next_step', ['type' => 'readonly', 'entity' => $entity]);
        $this->field('assignee_id', ['entity' => $entity]);
        $this->field('comment', ['type' => 'text']);
        $this->field('bulk_student_transfer_in', ['entity' => $entity]);
    }

    /**
     * @param EventInterface $event
     * @param $statusId
     * @return int
     */
    private function checkUserAccess($statusId)
    {
        $userAccess = false;
        $roleIds = [];
        $event = $this->dispatchEvent('Workflow.onUpdateRoles', null, $this);
        if ($event->getResult()) {
            $roleIds = $event->getResult();
        } else {
            $roles = $this->AccessControl->getRolesByUser()->toArray();
            foreach ($roles as $key => $role) {
                $roleIds[$role->security_role_id] = $role->security_role_id;
            }
        }
        if (empty($roleIds)) {
            $roleIds = [0];
        }
        $all_steps_and_roles = TableRegistry::getTableLocator()->get('Workflow.WorkflowStepsRoles');
        $distinct_step = $all_steps_and_roles->find()
            ->select(['workflow_step_id'])
            ->where(['workflow_step_id' => $statusId,
                'security_role_id IN' => $roleIds])
            ->distinct(['workflow_step_id'])
            ->first();

        if ($distinct_step) {
            $userAccess = true;
        }
        return $userAccess;
    }
}
