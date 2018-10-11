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

class BulkStudentAdmissionTable extends ControllerActionTable
{
    private $_modelAlias = 'Institution.StudentAdmission';
    private $_stepsOptions;

    public function initialize(array $config)
    {
        $this->table('workflow_steps');
        parent::initialize($config);

        $this->belongsTo('Workflows', ['className' => 'Workflow.Workflows']);
        $this->hasMany('WorkflowActions', ['className' => 'Workflow.WorkflowActions', 'foreignKey' => 'workflow_step_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentAdmission', ['className' => 'Institution.StudentAdmission', 'foreignKey' => 'status_id', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->toggle('index', false);
        $this->toggle('add', false);
        $this->toggle('view', false);
        $this->toggle('remove', false);
        $this->toggle('search', false);

        $this->_stepsOptions = $this
            ->find('list')
            ->contain([
                'Workflows.WorkflowModels'
            ])
            ->matching('WorkflowActions')
            ->where([
                'WorkflowModels.model' => $this->_modelAlias
            ])
            ->toArray();
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
        $url = ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StudentAdmission'];
        $Navigation->substituteCrumb('Bulk Student Admission', 'Student Admission', $url);
        $Navigation->addCrumb('Bulk Student Admission');
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
            'Workflows',
            'WorkflowActions',
            'WorkflowActions.NextWorkflowSteps',
            'StudentAdmission'=> function ($q) use ($superAdmin, $userId, $institutionId) {
                $q->where(['StudentAdmission.institution_id' => $institutionId])
                    ->contain(['Users', 'Assignees', 'AcademicPeriods', 'EducationGrades', 'InstitutionClasses', 'Statuses']);
                if ($superAdmin) {
                    return $q;
                } else {
                    return $q->where(['StudentAdmission.assignee_id'=> $userId]);
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
                $toolbarButtons['back']['url']['action'] = 'StudentAdmission';
                $toolbarButtons['back']['url'][0] = 'index';
                break;
        }
    }

    public function editAfterAction(Event $event, $entity, ArrayObject $extra)
    {
        $this->field('name', ['type' => 'hidden']);
        $this->field('category', ['type' => 'hidden']);
        $this->field('is_editable', ['type' => 'hidden']);
        $this->field('is_removable', ['type' => 'hidden']);
        $this->field('is_system_defined', ['type' => 'hidden']);
        $this->field('status', ['type' => 'select', 'entity' => $entity]);
        $this->field('action', ['type' => 'select', 'entity' => $entity]);
        $this->field('workflow_id', ['type' => 'hidden']);
        $this->field('next_step', ['type' => 'readonly', 'entity' => $entity]);
        $this->field('assignee_id', ['type' => 'select', 'entity' => $entity]);
        $this->field('comment', ['type' => 'text']);
        $this->field('bulk_student_admission', ['entity' => $entity]);
    }

    public function onUpdateFieldWorkflowId(Event $event, array $attr, $action, Request $request)
    {
        switch ($this->action) {
            case 'edit':
                $selectedStatus = isset($request->data[$this->alias()]['status']) ? $request->data[$this->alias()]['status'] : null;
            break;

            case 'reconfirm':
                $sessionKey = $this->registryAlias() . '.confirm';
                if ($this->Session->check($sessionKey)) {
                    $currentData = $this->Session->read($sessionKey);
                }
                $selectedStatus = $currentData->status;
            break;

            default:
                break;
        }
        $attr['attr']['value'] = $selectedStatus;
        return $attr;
    }

    public function onUpdateFieldStatus(Event $event, array $attr, $action, Request $request)
    {
        /* gets all the workflow_steps in which the workflow model belongs to StudentAdmissionTable & returns a list of key-value pair for populating the dropdown. The dropdown contains statuses which have next step(action) */
        $attr['select'] = false;
        $attr['options'] = $this->_stepsOptions;
        $attr['onChangeReload'] = 'changeStatus';
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
                $attr['options'] = $options;
                $attr['onChangeReload'] = 'changeAction';
            break;

            case 'reconfirm':
                $sessionKey = $this->registryAlias() . '.confirm';
                if ($this->Session->check($sessionKey)) {
                    $currentData = $this->Session->read($sessionKey);
                }
                $workflowActionEntity = $this->getNextWorkflowStepEntity($currentData);
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
                $workflowActionEntity = $this->getNextWorkflowStepEntity($entity);
                if (isset($workflowActionEntity->next_workflow_step)) {
                    $attr['value'] = $workflowActionEntity->next_workflow_step->id;
                    $attr['attr']['value'] = $workflowActionEntity->next_workflow_step->name;
                }
                break;

            case 'reconfirm':
                $sessionKey = $this->registryAlias() . '.confirm';
                if ($this->Session->check($sessionKey)) {
                    $currentData = $this->Session->read($sessionKey);
                    $workflowActionEntity = $this->getNextWorkflowStepEntity($currentData);
                    if (isset($workflowActionEntity->next_workflow_step)) {
                        $attr['attr']['value'] = $workflowActionEntity->next_workflow_step->name;
                    }
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
                $workflowActionEntity = $this->getNextWorkflowStepEntity($entity);
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
                    $defaultKey = empty($assigneeOptions) ? __('No options') : '-- '.__('Select').' --';

                }
                $attr['options'] = $assigneeOptions;
                break;

            case 'reconfirm':
                $SecurityUsers = TableRegistry::get('Security.Users');
                $sessionKey = $this->registryAlias() . '.confirm';
                if ($this->Session->check($sessionKey)) {
                    $currentData = $this->Session->read($sessionKey);
                }
                $value = $SecurityUsers
                    ->find()
                    ->where([$SecurityUsers->aliasField('id') => $currentData->assignee_id])
                    ->first();
                $attr['attr']['value'] = $value->name;
                break;

            default:
                break;
        }
        return $attr;
    }

    public function onUpdateFieldBulkStudentAdmission(Event $event, array $attr, $action, Request $request)
    {
        switch ($this->action) {
            case 'edit':
                $entity = $attr['entity'];
                $students = $entity->student_admission;
                break;
            case 'reconfirm':
                $sessionKey = $this->registryAlias() . '.confirm';
                if ($this->Session->check($sessionKey)) {
                    $currentData = $this->Session->read($sessionKey);
                }
                $students  = $currentData->student_admission;
                $attr['selectedStudents'] = ($currentData->has('students'))? $currentData->students : [];
                break;
            default:
                break;
        }
        $attr['type'] = 'element';
        $attr['element'] = 'Institution.BulkStudentAdmission/students';
        $attr['data'] = $students;
        return $attr;
    }

    public function reconfirm()
    {
        $this->Alert->info($this->aliasField('reconfirm'), ['reset' => true]);

        $sessionKey = $this->registryAlias() . '.confirm';
        if ($this->Session->check($sessionKey)) {
            $currentEntity = $this->Session->read($sessionKey);
            $currentData = $this->Session->read($sessionKey.'Data');
        } else{
            $this->Alert->warning('general.notExists');
        }
        $this->field('name', ['visible' => 'hidden']);
        $this->field('category', ['type' => 'hidden']);
        $this->field('is_editable', ['type' => 'hidden']);
        $this->field('is_removable', ['type' => 'hidden']);
        $this->field('is_system_defined', ['type' => 'hidden']);
        $this->field('status', ['type' => 'readonly']);
        $this->field('action', ['type' => 'readonly']);
        $this->field('workflow_id', ['type' => 'hidden']);
        $this->field('next_step', ['type' => 'readonly']);
        $this->field('assignee_id', ['type' => 'readonly']);
        $this->field('comment', ['type' => 'readonly']);
        $this->field('bulk_student_admission', ['type' => 'readonly']);
        if ($currentEntity && !empty($currentEntity)) {
            if ($this->request->is(['post', 'put'])) {
                if ($currentData instanceOf ArrayObject) {
                    $currentData = $currentData->getArrayCopy();
                }
                $currentEntity = $this->patchEntity($currentEntity, $currentData, []);
                return $this->saveBulkAdmission($currentEntity, new ArrayObject($currentData));
            }
            $this->controller->set('data', $currentEntity);
        } else {
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
                            'action' => 'BulkStudentAdmission',
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
    public function saveBulkAdmission(Entity $entity, ArrayObject $data)
    {
        $primaryKey = $this->StudentAdmission->primaryKey();
        $url = [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'StudentAdmission',
            'index'
        ];
        foreach ($data[$this->alias()]['students'] as $key => $studentObj) {
            if ($studentObj['selected']) {
                unset($studentObj['selected']);
                foreach ($entity->student_admission as $key => $value) {
                    if ($value['id'] == $studentObj['id']) {
                        $existingEntityToUpdate = $value;
                        break;
                    }
                }
                $prevWorkflowStepName = $existingEntityToUpdate->status->name;
                $existingEntityToUpdate->status_id = $data[$this->alias()]['next_step'];
                $existingEntityToUpdate->assignee_id = $data[$this->alias()]['assignee_id'];
                $workflowModel = $entity->workflow->id;
                $workflowAction = $this->getNextWorkflowStepEntity($entity);
                if ($this->StudentAdmission->save($existingEntityToUpdate)) {
                    $this->Alert->success($this->aliasField('success'), ['reset' => true]);
                    $transition['comment'] = $data[$this->alias()]['comment'];
                    $transition['prev_workflow_step_name'] = $prevWorkflowStepName;
                    $transition['workflow_step_name'] = $workflowAction->next_workflow_step['name'];
                    $transition['workflow_action_name'] = $workflowAction['name'];
                    $transition['workflow_model_id'] = $workflowModel;
                    $transition['model_reference'] = $existingEntityToUpdate->$primaryKey;
                    $WorkflowTransitions = TableRegistry::get('Workflow.WorkflowTransitions');
                    $transitionEntity = $WorkflowTransitions->newEntity($transition);
                    if ($WorkflowTransitions->save($transitionEntity)) {
                        $this->Alert->success($this->aliasField('success'), ['reset' => true]);
                    } else {
                        $this->log($entity->errors(), 'debug');
                    }
                } else {
                    $message = 'Bulk student admission failed.';
                    $this->Alert->error($this->aliasField('savingPromotionError'), ['reset' => true]);
                    $this->log($message, 'debug');
                    $url['action'] = 'BulkStudentAdmission';
                    $url[0] = 'add';
                }
            }
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
                            'action' => 'BulkStudentAdmission',
                            'add'
                        ];
                $cancelButton['url'] = $cancelUrl;
                $buttons[0] = $confirmButton;
                $buttons[1] = $cancelButton;
                break;

            default:
                break;
        }
    }

    private function getNextWorkflowStepEntity(Entity $entity){
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
}
