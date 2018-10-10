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
use Cake\I18n\Time;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use Cake\ORM\RulesChecker;
use App\Model\Table\ControllerActionTable;
use Workflow\Model\Behavior\WorkflowBehavior;
use Cake\Log\Log;

class BulkStudentAdmissionTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('workflow_steps');
        parent::initialize($config);
        $this->belongsTo('Workflows', ['className' => 'Workflow.Workflows']);
        $this->hasMany('WorkflowActions', ['className' => 'Workflow.WorkflowActions', 'foreignKey' => 'workflow_step_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('NextWorkflowSteps', ['className' => 'Workflow.WorkflowActions', 'foreignKey' => 'next_workflow_step_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentAdmission', ['className' => 'Institution.StudentAdmission', 'foreignKey' => 'status_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->toggle('remove', false);
        $this->toggle('index', false);
        $this->toggle('edit', false);
        $this->toggle('search', false);
        $this->toggle('view', false);
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

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        switch ($this->action) {
            case 'add':
                $toolbarButtons = $extra['toolbarButtons'];
                $toolbarButtons['back']['url']['action'] = 'StudentAdmission';
                break;
        }
    }

    public function addOnInitialize(Event $event, Entity $entity)
    {
        // To clear the query string from the previous page to prevent logic conflict on this page
        $this->request->query = [];
    }

    public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        // might need to change the type to visibility false.
        $this->field('name', ['visible' => 'hidden']);
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
            case 'add':
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
        // gets all the workflow_steps in which the workflow model belongs to StudentAdmissionTable & returns a list of key-value pair for populating the dropdown. The dropdown contains statuses which have next step(action)
        $model = $this->StudentAdmission->registryAlias();
        $options = $this
            ->find('list')
            ->contain([
                'Workflows.WorkflowModels'
            ])
            ->matching('WorkflowActions')
            ->where([
                'WorkflowModels.model' => $model
            ])
            ->toArray();
        $attr['options'] = $options;
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
        $query = $this->WorkflowActions->find('list');
        switch ($this->action) {
            case 'add':
                $status = null;
                if (isset($request->data[$this->alias()]['status'])) {
                    $status = $request->data[$this->alias()]['status'];
                }
                $options = $query
                    ->where([
                        $this->WorkflowActions->aliasField('workflow_step_id') => $status
                    ])
                    ->toArray();
                $attr['options'] = $options;
                $attr['onChangeReload'] = 'changeAction';
            break;

            case 'reconfirm':
                $sessionKey = $this->registryAlias() . '.confirm';
                if ($this->Session->check($sessionKey)) {
                    $currentData = $this->Session->read($sessionKey);
                }
                $value = $query
                    ->where([
                        $this->WorkflowActions->aliasField('id') => $currentData->action
                    ])
                    ->hydrate(false)
                    ->first();
                $attr['attr']['value'] = $value;
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
            case 'add':
                $nextWorkflowStepId = null;
                if (isset($request->data[$this->alias()]['action']) && $request->data[$this->alias()]['action']) {
                    $action = $request->data[$this->alias()]['action'];
                    $actionObj = $this->WorkflowActions->find()
                        ->where([
                            $this->WorkflowActions->aliasField('id') => $action
                        ])
                        ->first();
                    $nextWorkflowStepId = $actionObj->next_workflow_step_id;
                }
                break;

            case 'reconfirm':
                $sessionKey = $this->registryAlias() . '.confirm';
                if ($this->Session->check($sessionKey)) {
                    $currentData = $this->Session->read($sessionKey);
                }
                $nextWorkflowStepId = $currentData->next_step;
                break;

            default:
                break;
        }
        $nextWorkflowStepObj = $this
            ->find()
            ->where([
                $this->aliasField('id') => $nextWorkflowStepId
            ])
            ->first();
        if ($nextWorkflowStepObj) {
            $attr['value'] = $nextWorkflowStepObj->id;
            $attr['attr']['value'] = $nextWorkflowStepObj->name;
            $attr['entity']['next_step'] = $nextWorkflowStepObj->id;
        }
        return $attr;
    }

    public function onUpdateFieldAssigneeId(Event $event, array $attr, $action, Request $request)
    {
        switch ($this->action) {
            case 'add':
                $nextStepId = isset($attr['entity']['next_step']) ? $attr['entity']['next_step'] : null;
                $autoAssignAssignee = 0;
                $StudentAdmission = TableRegistry::get('Institution.StudentAdmission');
                $model = $StudentAdmission->registryAlias();
                $workflowModelEntity = $this
                    ->find()
                    ->select('WorkflowModels.is_school_based')
                    ->contain([
                        'Workflows.WorkflowModels'
                    ])
                    ->where([
                        'WorkflowModels.model' => $model
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
        $session = $this->request->session();
        $userId = $session->read('Auth.User.id');
        $superAdmin = $this->Session->read('Auth.User.super_admin');
        $institutionId = $session->read('Institution.Institutions.id');
        switch ($this->action) {
            case 'add':
                $currentStatus = isset($request->data[$this->alias()]['status']) ? $request->data[$this->alias()]['status'] : null;
                // $currentActionId = isset($request->data[$this->alias()]['status']) ? $request->data[$this->alias()]['status'] : null;
                // $currentAssigneeId = isset($request->data[$this->alias()]['status']) ? $request->data[$this->alias()]['status'] : null;
                break;
            case 'reconfirm':
                $sessionKey = $this->registryAlias() . '.confirm';
                if ($this->Session->check($sessionKey)) {
                    $currentData = $this->Session->read($sessionKey);
                    $entity = $currentData;
                }
                $currentStatus  = $entity->status;
                $attr['selectedStudents'] = ($currentData->has('students'))? $currentData->students: [];
                break;
            default:
                break;
        }

        $where[$this->StudentAdmission->aliasField('status_id')] = $currentStatus;
        $where[$this->StudentAdmission->aliasField('institution_id')] = $institutionId;
        if (!$superAdmin) {
            $where[$this->StudentAdmission->aliasField('assignee_id')] = $userId;
        }
        $students = [];
        $session = $this->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $students = $this->StudentAdmission
            ->find()
            ->contain(['Users', 'Assignees', 'AcademicPeriods', 'EducationGrades', 'InstitutionClasses', 'Statuses'])
            ->where($where)
            ->toArray();
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

    public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data)
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
                $existingEntityToUpdate = $this->StudentAdmission
                    ->find()
                    ->where([
                        $this->StudentAdmission->aliasField('id') => $studentObj['id']
                    ])
                    ->first();
                $existingEntityToUpdate->status_id = $data[$this->alias()]['next_step'];
                $existingEntityToUpdate->assignee_id = $data[$this->alias()]['assignee_id'];
                $prevWorkflowStepName = $this
                    ->find()
                    ->where([$this->aliasField($primaryKey) => $data[$this->alias()]['status']])
                    ->hydrate(false)
                    ->first();
                $workflowAction = $this->WorkflowActions
                    ->find()
                    ->where([
                        $this->WorkflowActions->aliasField('id') => $data[$this->alias()]['action']
                    ])
                    ->hydrate(false)
                    ->first();
                $another = $this
                    ->find()
                    ->where([
                        $this->aliasField('id') => $data[$this->alias()]['next_step']
                    ])
                    ->hydrate(false)
                    ->first();

                $WorkflowModels = TableRegistry::get('Workflow.WorkflowModels');
                $model = $this->StudentAdmission->registryAlias();
                $workflowModel = $WorkflowModels
                    ->find()
                    ->select('id')
                    ->where([$WorkflowModels->aliasField('model') => $model])
                    ->hydrate(false)
                    ->first();
                if ($this->StudentAdmission->save($existingEntityToUpdate)) {
                    $this->Alert->success($this->aliasField('success'), ['reset' => true]);

                    $transition['comment'] = $data[$this->alias()]['comment'];
                    $transition['prev_workflow_step_name'] = $prevWorkflowStepName['name'];
                    $transition['workflow_step_name'] = $another['name'];
                    $transition['workflow_action_name'] = $workflowAction['name'];
                    $transition['workflow_model_id'] = $workflowModel['id'];
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
            case 'add':
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
                // $cancelUrl = array_diff_key($cancelUrl, $this->request->data[$this->alias()]);
                $cancelButton['url'] = $cancelUrl;
                $buttons[0] = $confirmButton;
                $buttons[1] = $cancelButton;
                break;

            default:
                break;
        }
    }
}
