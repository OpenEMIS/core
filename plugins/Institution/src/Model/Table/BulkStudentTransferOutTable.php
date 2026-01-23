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

class BulkStudentTransferOutTable extends ControllerActionTable
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
            if($vlist == 'Pending Student Transfer' || $vlist == 'Open' || $vlist == 'Pending Approval' || $vlist == 'Transferred' || $vlist == 'Rejected' || $vlist == 'Cancelled'){
                $option[$klist] = $vlist;
            }
        } //POCOR-6362 ends
        $this->_stepsOptions = $option;
        $this->addBehavior('Institution.InstitutionTab'/*,
            ['appliedAction' => ['BulkStudentTransferOut'=>
                ['id']]]*/
        );
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
        $events['UpdateAssignee.onSetSchoolBasedConditions'] = 'onSetSchoolBasedConditions';
        $events['ControllerAction.Model.reconfirm'] = 'reconfirm';
        return $events;
    }

    public function onGetBreadcrumb(EventInterface $event, ServerRequest $request, Component $Navigation, $persona=false)
    {
        $url = ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StudentTransferOut'];
        $Navigation->substituteCrumb('Bulk Student Transfer Out', 'Student Transfer Out', $url);
        $Navigation->addCrumb('Bulk Student Transfer Out');
    }

    public function editBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $request = $this->request;
        $session = $this->Session;

        $userId = $session->read('Auth.User.id');
        $superAdmin = $session->read('Auth.User.super_admin');
        $institutionId = $this->getQueryString('institution_id');
        //$institutionId = $session->read('Institution.Institutions.id');

        if ($request->is(['post', 'put'])) {
            $statusId = $request->getData($this->getAlias())['status'];
        } else {
            $statusId = key($this->_stepsOptions);
        }

        $query->contain([
            'WorkflowActions',
            'WorkflowActions.NextWorkflowSteps',
            'StudentTransferIn'=> function ($q) use ($superAdmin, $userId, $institutionId) {
                $q->where(['StudentTransferIn.previous_institution_id' => $institutionId])
                    ->contain(['Users', 'Assignees', 'AcademicPeriods', 'EducationGrades', 'InstitutionClasses', 'Statuses','PreviousInstitutions']);
                /**POCOR-6946 - "if" condition has been updated to fetch list of students*/
                if ($this->AccessControl->check(['Institutions', 'StudentTransferOut', 'edit'])) {
                    return $q;
                } else {
                    return $q->where(['StudentTransferIn.assignee_id'=> $userId]);
                }
            },
            'Workflows.WorkflowModels'
        ])
        ->where([
            'WorkflowModels.model' => $this->_modelAlias,
            $this->aliasField('id') => $statusId
        ], [], true);
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        switch ($this->action) {
            case 'edit':
                $toolbarButtons = $extra['toolbarButtons'];
                $toolbarButtons['back']['url']['action'] = 'StudentTransferOut';
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
                $selectedStatus = isset($request->getData($this->getAlias())['status']) ? $request->getData($this->getAlias())['status'] : null;
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
                        $session = $this->request->getSession();
                        if ($session->check('Institution.Institutions.id')) {
                            $institutionId = $session->read('Institution.Institutions.id');
                            $params['institution_id'] = $institutionId;
                        }
                    }
                    // $assigneeOptions = $SecurityGroupUsers->getAssigneeList($params); //POCOR-6923
                    //echo "<pre>"; print_r($entity['student_transfer_in'][0]);die;
                    if($entity['name']=='Open' && $entity['workflow_actions'][0]['next_workflow_step']['name']=='Pending Approval'){ //POCOR-6961
                        $assigneeOptions = $SecurityGroupUsers->getAssigneeList($params); //POCOR-6961
                    }elseif($entity['student_transfer_in'][0]['status']['name']=='Pending Student Transfer'){
                        $assigneeOptions = $SecurityGroupUsers->getAssigneeList($params); //POCOR-6961
                    }else{
                        $assigneeOptions = [$this->Auth->user('id') => __('Auto Assign')];//POCOR-7080
                    }

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
                if($this->_currentData->assignee_id==-1){
                    $assigneeOptions = 'Auto Assign'; //POCOR-6961
                    $attr['type'] = 'readonly';
                    $attr['value'] = '-1';
                    $attr['attr']['value'] = $assigneeOptions; //POCOR-6961
                    break;
                }else{
                    $assigneeOptions = $assigneeOptions;
                    $attr['type'] = 'readonly';
                    $attr['attr']['value'] = $value->name;
                    break;
                }
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

    public function onUpdateFieldBulkStudentTransferOut(EventInterface $event, array $attr, $action, ServerRequest $request)
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
        $attr['element'] = 'Institution.BulkStudentTransferOut/students';
        $attr['data'] = $students;
        return $attr;
    }

    public function reconfirm()
    {
        $getQueryString = $this->getQueryString(); //POCOR-8624
        $this->Alert->info($this->aliasField('reconfirm'), ['reset' => true]);
        $this->setupFields();
        $url = [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'BulkStudentTransferOut',
                0 =>'edit',
                1 => $getQueryString //POCOR-8624
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
                return $this->saveBulkStudentTransferOut($currentEntity, new ArrayObject($currentData));
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
        $getQueryString = $this->getQueryString(); //POCOR-8624

        $process = function ($model, $entity) use ($event, $data) {
            $data = $data->getArrayCopy();
            $errors = $entity->getErrors();

            if (!empty($errors)) {
                return false;
            }
            //POCOR-7969  Start
            $alias = $this->getAlias();

            $selectedStudent = false;

            if (!empty($data[$alias]['students']) && is_array($data[$alias]['students'])) {
                foreach ($data[$alias]['students'] as $student) {
                    if (!empty($student['selected'])) {
                        $selectedStudent = true;
                        break;
                    }
                }
            }
            //POCOR-7969  END
            $encodedQueryParams = $this->request->getParam('pass')[1];

            if ($selectedStudent) {
                $url = [
                    'plugin' => 'Institution',
                    'controller' => 'Institutions',
                    'action' => 'BulkStudentTransferOut',
                    0 => 'reconfirm',
                    1 => $encodedQueryParams //POCOR-8624
                ];

                $this->currentEntity = $entity;
                $session = $this->Session;
                $session->write($this->getRegistryAlias() . '.confirm', $entity);
                $session->write($this->getRegistryAlias() . '.confirmData', $data);
                $this->currentEvent = $event;
                $event->stopPropagation();
                return $this->controller->redirect($url);
            } else {
                $this->Alert->warning($alias . '.noStudentSelected', ['reset' => true]);
                return false;
            }
        };

        return $process;
    }

    public function saveBulkStudentTransferOut(Entity $entity, ArrayObject $data)
    {
        $getQueryString = $this->getQueryString(); //POCOR-8624
        $encodedQueryString = $this->paramsEncode([
            'id' => $getQueryString['institution_id'],
            'institution_id' => $getQueryString['institution_id']
        ]); //POCOR-8624
        $primaryKey = $this->StudentTransferIn->getPrimaryKey();
        $url = [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'StudentTransferOut',
                0 =>    'index',
                1 => $encodedQueryString //POCOR-8624
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
            $this->Alert->success('general.bulk_student_transfer_out', ['reset' => true]);
            $session = $this->Session;
            $session->delete($this->getRegistryAlias() . '.confirm');
            $session->delete($this->getRegistryAlias() . '.Data');
        } else {
            $this->log($entity->getErrors(), 'debug');
            $url['action'] = 'BulkStudentTransferOut';
            $url[0] = 'edit';
            $url[1] = $getQueryString; //POCOR-8624
        }
        return $this->controller->redirect($url);
    }

    public function onGetFormButtons(EventInterface $event, ArrayObject $buttons)
    {
        $getQueryString = $this->getQueryString();
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
                            'action' => 'BulkStudentTransferOut',
                                0 => 'edit',
                                1 => $getQueryString //POCOR-8624
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
        $this->field('bulk_student_transfer_out', ['entity' => $entity]);
    }

}
