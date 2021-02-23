<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\Network\Request;
use Cake\Controller\Component;
use Cake\Utility\Hash;
use Cake\Log\Log;
use App\Model\Table\ControllerActionTable;

class BulkStudentTransferInTable extends ControllerActionTable
{
    private $_modelAlias = 'Institution.StudentTransferOut';
    private $_stepsOptions;
    private $_currentData;

    public function initialize(array $config)
    {
        $this->table('workflow_steps');
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
        foreach ($steplists as $klist => $vlist) {
            if($vlist == 'Pending Approval From Receiving Institution'){
                $option[$klist] = $vlist;
            }
        } 
        $this->_stepsOptions = $option;
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        $validator
            ->notEmpty('status')
            ->notEmpty('action')
            ->notEmpty('assignee_id');
        return $validator;
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.Navigation.breadcrumb'] = 'onGetBreadcrumb';
        $events['ControllerAction.Model.reconfirm'] = 'reconfirm';
        return $events;
    }

    public function onGetBreadcrumb(Event $event, Request $request, Component $Navigation, $persona=false)
    {
        $url = ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StudentTransferIn'];
        $Navigation->substituteCrumb('Bulk Student Transfer In', 'Student Transfer In', $url);
        $Navigation->addCrumb('Bulk Student Transfer In');
    }

    public function editBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $request = $this->request;
        $session = $this->Session;

        $userId = $session->read('Auth.User.id');
        $superAdmin = $session->read('Auth.User.super_admin');
        $institutionId = $session->read('Institution.Institutions.id');

        if ($request->is(['post', 'put'])) {
            $statusId = $request->data[$this->alias()]['status'];
        } else {
            $statusId = key($this->_stepsOptions);
        }

        $query->contain([
            'WorkflowActions',
            'WorkflowActions.NextWorkflowSteps',
            'StudentTransferIn'=> function ($q) use ($superAdmin, $userId, $institutionId) {
                $q->where(['StudentTransferIn.institution_id' => $institutionId])
                    ->contain(['Users', 'Assignees', 'AcademicPeriods', 'EducationGrades', 'InstitutionClasses', 'Statuses','PreviousInstitutions']);
                if ($superAdmin) {
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

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        switch ($this->action) {
            case 'edit':
                $toolbarButtons = $extra['toolbarButtons'];
                $toolbarButtons['back']['url']['action'] = 'StudentTransferIn';
                $toolbarButtons['back']['url'][0] = 'index';
                break;
            case 'reconfirm':
                $sessionKey = $this->registryAlias() . '.confirm';
                if ($this->Session->check($sessionKey)) {
                    $this->_currentData = $this->Session->read($sessionKey);
                }
                break;
        }
    }

    public function editAfterAction(Event $event, $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function onUpdateFieldWorkflowId(Event $event, array $attr, $action, Request $request)
    {
        switch ($this->action) {
            case 'edit':
                $selectedStatus = isset($request->data[$this->alias()]['status']) ? $request->data[$this->alias()]['status'] : null;
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

    public function onUpdateFieldStatus(Event $event, array $attr, $action, Request $request)
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

    public function addEditOnChangeStatus(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $data[$this->alias()]['action'] = null;
        $data[$this->alias()]['next_step'] = null;
        $data[$this->alias()]['assignee_id'] = null;
    }

    public function onUpdateFieldAction(Event $event, array $attr, $action, Request $request)
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
                $sessionKey = $this->registryAlias() . '.confirm';
                $workflowActionEntity = $this->getWorkflowActionEntity($this->_currentData);
                $attr['type'] = 'readonly';
                $attr['attr']['value'] = $workflowActionEntity['name'];
            break;

            default:
                break;
        }
        return $attr;
    }

    public function addEditOnChangeAction(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $data[$this->alias()]['next_step'] = null;
        $data[$this->alias()]['assignee_id'] = null;
    }

    public function onUpdateFieldNextStep(Event $event, array $attr, $action, Request $request)
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

    public function onUpdateFieldAssigneeId(Event $event, array $attr, $action, Request $request)
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
                    ->hydrate(false)
                    ->first();
                $isSchoolBased = $workflowModelEntity['WorkflowModels']['is_school_based'];
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
                }
                $attr['type'] = 'select';
                $attr['options'] = $assigneeOptions;
                break;

            case 'reconfirm':
                $SecurityUsers = TableRegistry::get('Security.Users');
                $value = $SecurityUsers
                    ->find()
                    ->where([$SecurityUsers->aliasField('id') => $this->_currentData->assignee_id])
                    ->first();
                $attr['type'] = 'readonly';
                $attr['attr']['value'] = $value->name;
                break;

            default:
                break;
        }
        return $attr;
    }

    public function onUpdateFieldComment(Event $event, array $attr, $action, Request $request)
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

    public function onUpdateFieldBulkStudentTransferIn(Event $event, array $attr, $action, Request $request)
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
        $url = [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'BulkStudentTransferIn',
            'edit'
        ];
        $sessionKey = $this->registryAlias() . '.confirm';
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

    public function editBeforeSave(Event $event, Entity $entity, ArrayObject $data)
    {
        $process = function ($model, $entity) use ($event, $data) {
            // Removal of some fields that are not in use in the table validation
            $errors = $entity->errors();
            if (empty($errors)) {
                if (array_key_exists($this->alias(), $data)) {
                    $selectedStudent = false;
                    if (array_key_exists('students', $data[$this->alias()])) {
                        foreach ($data[$this->alias()]['students'] as $key => $value) {
                            if ($value['selected'] != 0) {
                                $selectedStudent = true;
                                break;
                            }
                        }
                    }
                    if ($selectedStudent) {
                        // redirects to confirmation page
                        $url = [
                            'plugin' => 'Institution',
                            'controller' => 'Institutions',
                            'action' => 'BulkStudentTransferIn',
                            'reconfirm'
                        ];
                        $this->currentEntity = $entity;
                        $session = $this->Session;
                        $session->write($this->registryAlias().'.confirm', $entity);
                        $session->write($this->registryAlias().'.confirmData', $data);
                        $this->currentEvent = $event;
                        $event->stopPropagation();
                        return $this->controller->redirect($url);
                    } else {
                        $this->Alert->warning($this->alias().'.noStudentSelected', ['reset' => true]);
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
        $primaryKey = $this->StudentTransferIn->primaryKey();
        $url = [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'StudentTransferIn',
            'index'
        ];
        $workflowTransitionObj = [];
        foreach ($data[$this->alias()]['students'] as $key => $studentObj) {
            if ($studentObj['selected']) {
                unset($studentObj['selected']);
                foreach ($entity->student_transfer_in as $key => $value) {
                    if ($value['id'] == $studentObj['id']) {
                        $existingEntityToUpdate = $value;
                        break;
                    }
                }

                $prevWorkflowStepName = $existingEntityToUpdate->status->name;
                $existingEntityToUpdate->status_id = $data[$this->alias()]['next_step'];
                $existingEntityToUpdate->assignee_id = $data[$this->alias()]['assignee_id'];
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
                                return $event->result;
                            }
                        }
                    }
                    $workflowTransition = [];
                    $workflowTransition['comment'] = $data[$this->alias()]['comment'];
                    $workflowTransition['prev_workflow_step_name'] = $prevWorkflowStepName;
                    $workflowTransition['workflow_step_name'] = $workflowAction->next_workflow_step['name'];
                    $workflowTransition['workflow_action_name'] = $workflowAction['name'];
                    $workflowTransition['workflow_model_id'] = $workflowModel;
                    $workflowTransition['model_reference'] = $existingEntityToUpdate->$primaryKey;
                    $workflowTransitionObj[] = $workflowTransition;
                }
            }
        }
        $WorkflowTransitions = TableRegistry::get('Workflow.WorkflowTransitions');
        $workflowTransitionEntities = $WorkflowTransitions->newEntities($workflowTransitionObj);
        if ($WorkflowTransitions->saveMany($workflowTransitionEntities)) {
            $this->Alert->success($this->aliasField('success'), ['reset' => true]);
            $session = $this->Session;
            $session->delete($this->registryAlias() . '.confirm');
            $session->delete($this->registryAlias() . '.Data');
        } else {
            $this->log($entity->errors(), 'debug');
            $url['action'] = 'BulkStudentTransferIn';
            $url[0] = 'edit';
        }
        return $this->controller->redirect($url);
    }

    public function onGetFormButtons(Event $event, ArrayObject $buttons)
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
}
