<?php
namespace Workflow\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\Validation\Validator;

class WorkflowActionsTable extends AppTable
{
    // Workflow Actions - action
    const APPROVE = 0;
    const REJECT = 1;

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('WorkflowSteps', ['className' => 'Workflow.WorkflowSteps']);
        $this->belongsTo('NextWorkflowSteps', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'next_workflow_step_id']);
    }

    public function onGetNextWorkflowStepId(Event $event, Entity $entity)
    {
        $value = '';
        if (empty($entity->next_workflow_step_id)) {
            $value = '<span>&lt;'.$this->getMessage('general.notConfigured').'&gt;</span>';
        }

        return $value;
    }

    public function onGetCommentRequired(Event $event, Entity $entity)
    {
        return $entity->comment_required == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-close"></i>';
    }

    public function onGetAllowByAssignee(Event $event, Entity $entity)
    {
        return $entity->allow_by_assignee == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-close"></i>';
    }

    public function onGetPostEvents(Event $event, Entity $entity)
    {
        $workflowSteps = $entity->workflow_step;
        $selectedWorkflow = $workflowSteps->workflow_id;
        $eventOptions = $this->getEvents($selectedWorkflow);

        $events = $this->convertEventKeysToEvents($entity);
        $eventArray = [];
        foreach ($events as $key => $event) {
            $eventArray[$event] = $eventOptions[$event];
        }

        return implode(', ', $eventArray);
    }

    public function beforeAction(Event $event)
    {
        $this->ControllerAction->field('action', ['visible' => false]);
        $this->ControllerAction->field('event_key', ['visible' => false]);
    }

    public function indexBeforeAction(Event $event)
    {
        $this->ControllerAction->field('post_events');
        $this->ControllerAction->setFieldOrder(['visible', 'name', 'description', 'workflow_step_id', 'next_workflow_step_id', 'comment_required', 'allow_by_assignee', 'post_events']);
    }

    public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options)
    {
        $where = [];
        $where[$this->aliasField('workflow_step_id')] = -1;

        $modelOptions = $this->getWorkflowModelOptions();
        $selectedModel = $this->queryString('model', $modelOptions);
        $this->advancedSelectOptions($modelOptions, $selectedModel);

        $workflowOptions = $this->getWorkflowOptions($selectedModel);
        if (!empty($workflowOptions)) {
            $selectedWorkflow = $this->queryString('workflow', $workflowOptions);
            $this->advancedSelectOptions($workflowOptions, $selectedWorkflow);

            $workflowStepOptions = $this->getWorkflowStepOptions($selectedWorkflow);

            if (!empty($workflowStepOptions)) {
                $selectedWorkflowStep = $this->queryString('workflow_step', $workflowStepOptions);
                $this->advancedSelectOptions($workflowStepOptions, $selectedWorkflowStep);

                $where[$this->aliasField('workflow_step_id')] = $selectedWorkflowStep;
            }
        } else {
            $workflowStepOptions = [];
        }

        //Add controls filter to index page
        $toolbarElements = [
            ['name' => 'Workflow.WorkflowActions/controls', 'data' => compact('modelOptions', 'workflowOptions', 'workflowStepOptions'), 'options' => []]
        ];
        $this->controller->set('toolbarElements', $toolbarElements);

        $query->where($where);
    }

    public function indexAfterAction(Event $event, $data)
    {
        $session = $this->request->session();

        $sessionKey = $this->registryAlias() . '.warning';
        if ($session->check($sessionKey)) {
            $warningKey = $session->read($sessionKey);
            $this->Alert->warning($warningKey);
            $session->delete($sessionKey);
        }
    }

    public function viewEditBeforeQuery(Event $event, Query $query)
    {
        $query->matching('WorkflowSteps');
    }

    public function addOnInitialize(Event $event, Entity $entity)
    {
        unset($this->request->query['model']);
        unset($this->request->query['workflow']);
        unset($this->request->query['workflow_step']);
    }

    public function editOnInitialize(Event $event, Entity $entity)
    {
        $events = $this->convertEventKeysToEvents($entity);
        if (!empty($events)) {
            $eventsData = [];
            foreach ($events as $key => $event) {
                $eventsData[] = ['event_key' => $event];
            }
            $entity->post_events = $eventsData;
        }
    }

    public function onBeforeDelete(Event $event, ArrayObject $options, $ids)
    {
        $entity = $this->get($ids);
        list($isEditable, $isDeletable) = array_values($this->checkIfCanEditOrDelete($entity));

        if (!$isDeletable) {
            $session = $this->request->session();
            $sessionKey = $this->registryAlias() . '.warning';
            $session->write($sessionKey, $this->aliasField('restrictDelete'));

            $url = $this->ControllerAction->url('index');
            $event->stopPropagation();
            return $this->controller->redirect($url);
        }
    }

    public function viewAfterAction(Event $event, Entity $entity)
    {
        $this->setupFields($entity);
    }

    public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $eventKeys = $this->convertEventsToEventKeys($data);
        $data[$this->alias()]['event_key'] = $eventKeys;
    }

    public function addEditAfterAction(Event $event, Entity $entity)
    {
        $this->setupFields($entity);
    }

    public function onUpdateFieldWorkflowModelId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'view' || $action == 'edit') {
            $attr['visible'] = false;
        } else if ($action == 'add') {
            $modelOptions = $this->getWorkflowModelOptions();

            $attr['type'] = 'select';
            $attr['options'] = $modelOptions;
            $attr['onChangeReload'] = 'changeModel';
        }

        return $attr;
    }

    public function onUpdateFieldWorkflowId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'view' || $action == 'edit') {
            $attr['visible'] = false;
        } else if ($action == 'add') {
            $selectedModel = $request->query('model');
            $workflowOptions = $this->getWorkflowOptions($selectedModel);

            $attr['type'] = 'select';
            $attr['options'] = $workflowOptions;
            $attr['onChangeReload'] = 'changeWorkflow';
        }

        return $attr;
    }

    public function onUpdateFieldWorkflowStepId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $selectedWorkflow = $request->query('workflow');
            $workflowStepOptions = $this->getWorkflowStepOptions($selectedWorkflow);

            $attr['type'] = 'select';
            $attr['options'] = $workflowStepOptions;
            $attr['onChangeReload'] = 'changeWorkflowStep';
        } else if ($action == 'edit') {
            $entity = $attr['attr']['entity'];
            $workflowSteps = $entity->_matchingData['WorkflowSteps'];

            $attr['type'] = 'readonly';
            $attr['value'] = $workflowSteps->id;
            $attr['attr']['value'] = $workflowSteps->name;
        }

        return $attr;
    }

    public function onUpdateFieldNextWorkflowStepId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            if ($action == 'add') {
                $selectedWorkflow = $request->query('workflow');
                $selectedWorkflowStep = $request->query('workflow_step');
            } else if ($action == 'edit') {
                $entity = $attr['attr']['entity'];
                $workflowSteps = $entity->_matchingData['WorkflowSteps'];
                $selectedWorkflow = $workflowSteps->workflow_id;
                $selectedWorkflowStep = $workflowSteps->id;
            }
            $nextWorkflowStepOptions = $this->getNextWorkflowStepOptions($selectedWorkflow, $selectedWorkflowStep);

            $attr['type'] = 'select';
            $attr['options'] = $nextWorkflowStepOptions;
        }

        return $attr;
    }

    public function onUpdateFieldCommentRequired(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $attr['type'] = 'select';
            $attr['select'] = false;
            $attr['options'] = $this->getSelectOptions('general.yesno');
        }

        return $attr;
    }

    public function onUpdateFieldAllowByAssignee(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $attr['type'] = 'select';
            $attr['select'] = false;
            $attr['options'] = $this->getSelectOptions('general.yesno');
        }

        return $attr;
    }

    public function onUpdateFieldPostEvents(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'view') {
            $entity = $attr['attr']['entity'];
            $workflowSteps = $entity->_matchingData['WorkflowSteps'];
            $selectedWorkflow = $workflowSteps->workflow_id;
            $eventOptions = $this->getEvents($selectedWorkflow, true);

            $tableHeaders = [];
            $tableHeaders[] = $this->getMessage('general.name');

            $tableCells = [];
            $events = $this->convertEventKeysToEvents($entity);
            foreach ($events as $key => $event) {
                $tableCells[$key] = [$eventOptions[$event]];
            }

            $attr['attr']['tableHeaders'] = $tableHeaders;
            $attr['attr']['tableCells'] = $tableCells;
        } else if ($action == 'add' || $action == 'edit') {
            if ($action == 'add') {
                $selectedWorkflow = $request->query('workflow');
            } else if ($action == 'edit') {
                $entity = $attr['attr']['entity'];
                $workflowSteps = $entity->_matchingData['WorkflowSteps'];
                $selectedWorkflow = $workflowSteps->workflow_id;
            }

            $eventOptions = $this->getEvents($selectedWorkflow, true);
            $attr['attr']['eventOptions'] = $eventOptions;
            $eventSelectOptions = $this->getEvents($selectedWorkflow, true, true);

            $selectedEventKeys = [];
            if ($request->is(['get'])) {
                if ($action == 'edit') {
                    $entity = $attr['attr']['entity'];
                    $selectedEventKeys = $this->convertEventKeysToEvents($entity);
                }
            } else if ($request->is(['post', 'put'])) {
                $requestData = $request->data;

                if (array_key_exists($this->alias(), $requestData)) {
                    if (array_key_exists('post_events', $requestData[$this->alias()])) {
                        $postEvents = $requestData[$this->alias()]['post_events'];
                        if (count($postEvents) > 1) {
                            $this->Alert->clear();
                            $this->Alert->error('WorkflowActions.no_two_post_event');

                            // Getting the first selected post event that is selected
                            $postEventSelectedElementValue = $postEvents[0]['event_key'];

                            // Remove the selected post event that is selected only
                            unset($eventSelectOptions[$postEventSelectedElementValue]);

                            // Remove away from the main entity
                            unset($attr['attr']['entity']['post_events'][count($attr['attr']['entity']['post_events']) - 1]);

                        } else {
                            foreach ($requestData[$this->alias()]['post_events'] as $key => $event) {
                                $selectedEventKeys[] = $event['event_key'];
                            }
                        }
                    }
                }
            }

            foreach ($selectedEventKeys as $key => $value) {
                unset($eventSelectOptions[$value]);
            }

            $attr['attr']['eventSelectOptions'] = $eventSelectOptions;
        }

        return $attr;
    }

    public function addEditOnChangeModel(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;
        unset($request->query['model']);
        unset($request->query['workflow']);
        unset($request->query['workflow_step']);

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('workflow_model_id', $request->data[$this->alias()])) {
                    $request->query['model'] = $request->data[$this->alias()]['workflow_model_id'];
                }
            }
        }
    }

    public function addEditOnChangeWorkflow(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;
        unset($request->query['workflow']);
        unset($request->query['workflow_step']);

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('workflow_id', $request->data[$this->alias()])) {
                    $request->query['workflow'] = $request->data[$this->alias()]['workflow_id'];
                }
            }
        }
    }

    public function addEditOnChangeWorkflowStep(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;
        unset($request->query['workflow_step']);

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('workflow_step_id', $request->data[$this->alias()])) {
                    $request->query['workflow_step'] = $request->data[$this->alias()]['workflow_step_id'];
                }
            }
        }
    }

    public function addEditOnAddEvent(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        if (array_key_exists($this->alias(), $data)) {
            if (array_key_exists('event_method_key', $data[$this->alias()])) {
                $methodKey = $data[$this->alias()]['event_method_key'];
                if (!empty($methodKey)) {
                    $data[$this->alias()]['post_events'][] = [
                        'event_key' => $methodKey
                    ];
                }
                $data[$this->alias()]['event_method_key'] = '';
            }
        }
    }

    private function setupFields(Entity $entity)
    {
        $this->ControllerAction->field('workflow_model_id');
        $this->ControllerAction->field('workflow_id');
        $this->ControllerAction->field('workflow_step_id', [
            'attr' => ['entity' => $entity]
        ]);
        $this->ControllerAction->field('next_workflow_step_id', [
            'attr' => ['entity' => $entity]
        ]);
        $this->ControllerAction->field('comment_required');
        $this->ControllerAction->field('allow_by_assignee');
        $this->ControllerAction->field('visible');
        $this->ControllerAction->field('post_events', [
            'type' => 'element',
            'element' => 'Workflow.WorkflowActions/events',
            'valueClass' => 'table-full-width',
            'attr' => ['entity' => $entity]
        ]);

        $this->ControllerAction->setFieldOrder(['workflow_model_id', 'workflow_id', 'workflow_step_id', 'name', 'description', 'next_workflow_step_id', 'comment_required', 'allow_by_assignee', 'visible', 'post_events']);
    }

    private function checkIfCanEditOrDelete($entity)
    {
        $isEditable = true;
        $isDeletable = true;

        // not allow to edit name for Open, Pending For Approval and Closed
        if (!is_null($entity->action) && in_array($entity->action, [self::APPROVE, self::REJECT])) {
            $isDeletable = false;
        }

        return compact('isEditable', 'isDeletable');
    }

    public function getEventTriggeringStep($selectedModel = null, $eventKey = null)
    {
        if(!empty($selectedModel) && !empty($eventKey)) {
            $Workflows = TableRegistry::get('Workflow.Workflows');
            $workflowResult = $Workflows
                ->find()
                ->select([
                    'next_workflow_step_id' => 'NextWorkflowSteps.next_workflow_step_id'
                ])
                ->matching('WorkflowModels', function ($q) use ($selectedModel) {
                    return $q->where(['WorkflowModels.model' => $selectedModel]);
                })
                ->matching('WorkflowSteps.NextWorkflowSteps', function ($q) use ($eventKey) {
                    return $q->where(['NextWorkflowSteps.event_key LIKE' => '%'.$eventKey.'%']);  
                })
                ->first();
        
            return $workflowResult->next_workflow_step_id;    
        }
    }

    public function getWorkflowModelOptions()
    {
        $WorkflowModels = TableRegistry::get('Workflow.WorkflowModels');
        $modelOptions = $WorkflowModels->getList()->toArray();

        return $modelOptions;
    }

    public function getWorkflowOptions($selectedModel = null)
    {
        if (is_null($selectedModel)) {
            return [];
        } else {
            $Workflows = TableRegistry::get('Workflow.Workflows');
            $workflowOptions = $Workflows
                ->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
                ->where([
                    $Workflows->aliasField('workflow_model_id') => $selectedModel
                ])
                ->order([
                    $Workflows->aliasField('code')
                ])
                ->toArray();

            return $workflowOptions;
        }
    }

    public function getWorkflowStepOptions($selectedWorkflow = null)
    {
        if (is_null($selectedWorkflow)) {
            return [];
        } else {
            $workflowStepOptions = $this->WorkflowSteps
                ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
                ->where([
                    $this->WorkflowSteps->aliasField('workflow_id') => $selectedWorkflow
                ])
                ->toArray();

            return $workflowStepOptions;
        }
    }

    public function getNextWorkflowStepOptions($selectedWorkflow = null, $selectedWorkflowStep = null)
    {
        if (is_null($selectedWorkflow) || is_null($selectedWorkflowStep)) {
            return [];
        } else {
            $nextWorkflowStepOptions = $this->WorkflowSteps
                ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
                ->where([
                    $this->WorkflowSteps->aliasField('workflow_id') => $selectedWorkflow,
                    $this->WorkflowSteps->aliasField('id !=') => $selectedWorkflowStep
                ])
                ->toArray();

            return $nextWorkflowStepOptions;
        }
    }

    public function getEvents($selectedWorkflow = null, $listOnly = true, $filterUniqueEvents = false)
    {
        $emptyOptions = [
            0 => [
                'value' => '',
                'text' => $this->ControllerAction->Alert->getMessage('general.select.noOptions')
            ]
        ];

        // trigger Workflow.getEvents to retrieve the list of available events for the model
        if (is_null($selectedWorkflow) || empty($selectedWorkflow)) {
            return $emptyOptions;
        } else {
            $Workflows = TableRegistry::get('Workflow.Workflows');
            $workflow = $Workflows
                ->find()
                ->matching('WorkflowModels')
                ->where([
                    $Workflows->aliasField('id') => $selectedWorkflow
                ])
                ->first();

            $registryAlias = $workflow->_matchingData['WorkflowModels']->model;
            $subject = TableRegistry::get($registryAlias);
            $eventsObject = new ArrayObject();
            $subjectEvent = $subject->dispatchEvent('Workflow.getEvents', [$eventsObject], $subject);
            if ($subjectEvent->isStopped()) {
                return $subjectEvent->result;
            }

            $events = $eventsObject;
            if (empty($events)) {
                return $emptyOptions;
            } else {
                $eventOptions = [];

                if ($listOnly) {
                    $eventOptions = [
                        0 => __('-- Select Event --')
                    ];
                    foreach ($events as $event) {
                        if ($filterUniqueEvents && array_key_exists('unique', $event) && $event['unique']) {
                            if ($this->checkEventNotAddedBefore($selectedWorkflow, $event)) {
                                $eventOptions[$event['value']] = $event['text'];
                            }
                        } else {
                            $eventOptions[$event['value']] = $event['text'];
                        }
                    }
                } else {
                    $eventOptions = [
                        0 => [
                            'value' => '',
                            'text' => __('-- Select Event --')
                        ]
                    ];
                    foreach ($events as $event) {
                        if ($filterUniqueEvents && array_key_exists('unique', $event) && $event['unique']) {
                            if ($this->checkEventNotAddedBefore($selectedWorkflow, $event)) {
                                $eventOptions[] = $event;
                            }
                        } else {
                            $eventOptions[] = $event;
                        }
                    }
                }

                return $eventOptions;
            }
        }
    }

    private function convertEventsToEventKeys($data)
    {
        $eventKeys = [];
        if (array_key_exists($this->alias(), $data)) {
            if (array_key_exists('post_events', $data[$this->alias()])) {
                $eventKeys = [];
                foreach ($data[$this->alias()]['post_events'] as $key => $event) {
                    if (!in_array($event['event_key'], $eventKeys)) {
                        $eventKeys[] = $event['event_key'];
                    }
                }
            }
        }

        return implode(",", $eventKeys);
    }

    private function convertEventKeysToEvents($entity)
    {
        $events = [];
        if ($entity->has('event_key') && !empty($entity->event_key)) {
            $events = explode(",", $entity->event_key);
        }

        return $events;
    }

    private function checkEventNotAddedBefore($selectedWorkflow, $event)
    {
        $eventName = $event['value'];

        $WorkflowSteps = TableRegistry::get('Workflow.WorkflowSteps');
        $existingEventCount = $WorkflowSteps->find()
            ->matching('WorkflowActions', function ($q) use ($eventName) {
                return $q->where(['event_key LIKE ' => '%' . $eventName . '%']);
            })
            ->where([$WorkflowSteps->aliasField('workflow_id') => $selectedWorkflow])
            ->count();

        return ($existingEventCount == 0);
    }
}
