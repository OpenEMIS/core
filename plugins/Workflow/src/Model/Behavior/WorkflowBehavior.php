<?php
namespace Workflow\Model\Behavior;

use ArrayObject;
use Cake\I18n\Time;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\Utility\Inflector;
use Cake\Network\Session;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Log\Log;
use Cake\Routing\Router;
use Cake\Validation\Validator;
use App\Model\Traits\OptionsTrait;
use Workflow\Model\Table\WorkflowStepsTable as WorkflowSteps;

class WorkflowBehavior extends Behavior
{
    use OptionsTrait;

    const AUTO_ASSIGN = -1;
    const STATUS_OPEN = 0;

    protected $_defaultConfig = [
        'model' => null,
        'institution_key' => 'institution_id',
        'models' => [
            'WorkflowModels' => 'Workflow.WorkflowModels',
            'Workflows' => 'Workflow.Workflows',
            'WorkflowsFilters' => 'Workflow.WorkflowsFilters',
            'WorkflowSteps' => 'Workflow.WorkflowSteps',
            'WorkflowStepsRoles' => 'Workflow.WorkflowStepsRoles',
            'WorkflowActions' => 'Workflow.WorkflowActions',
            'WorkflowComments' => 'Workflow.WorkflowComments',
            'WorkflowTransitions' => 'Workflow.WorkflowTransitions',
            'WorkflowStepsParams' => 'Workflow.WorkflowStepsParams'
        ],
        'actions' => [
            'add' => true,
            'edit' => true,
            'remove' => true
        ],
        'disableWorkflow' => false,
        'filter' => [
            'type' => true,
            'category' => true
        ]
    ];

    private $workflowEvents = [
        [
            'value' => 'Workflow.onAssignBack',
            'text' => 'Assign Back to Creator',
            'description' => 'Performing this action will assign the current record back to creator.',
            'method' => 'onAssignBack'
        ],
        [
            'value' => 'Workflow.onAssignBackToScholarshipApplicant',
            'text' => 'Assign back to Scholarship Applicant',
            'description' => 'Performing this action will assign the current record back to scholarship applicant.',
            'method' => 'onAssignBackToScholarshipApplicant'
        ]
    ];

    private $controller;
    private $model = null;
    private $currentAction;

    private $attachWorkflow = false;    // indicate whether which action require workflow
    private $hasWorkflow = false;   // indicate whether workflow is setup
    private $workflowIds = null;

    private $workflowSetup = null;

    public function initialize(array $config)
    {
        parent::initialize($config);
        $models = $this->config('models');
        if (is_null($this->config('model'))) {
            $this->_config['model'] = $this->_table->registryAlias();
        }

        foreach ($models as $key => $model) {
            if (!is_null($model)) {
                $this->{$key} = TableRegistry::get($model);
                $this->{lcfirst($key).'Key'} = Inflector::underscore(Inflector::singularize($this->{$key}->alias())) . '_id';
            } else {
                $this->{$key} = null;
            }
        }

        if ($this->isCAv4()) {
            $actions = $this->config('actions');
            $model = $this->_table;
            foreach ($actions as $key => $value) {
                $model->toggle($key, $value);
            }
        }
    }

    private function isCAv4()
    {
        return isset($this->_table->CAVersion) && $this->_table->CAVersion=='4.0';
    }

    public function implementedEvents()
    {

        $events = parent::implementedEvents();
        // priority has to be set at 1000 so that method(s) in model will be triggered first
        // priority of indexBeforeAction and indexBeforePaginate is set to 1 for it to run first before the event in model
        $events['ControllerAction.Model.beforeAction']          = ['callable' => 'beforeAction', 'priority' => 1000];
        $events['ControllerAction.Model.afterAction']           = ['callable' => 'afterAction', 'priority' => 1];
        $events['ControllerAction.Model.index.beforeAction']    = ['callable' => 'indexBeforeAction', 'priority' => 1];
        if ($this->isCAv4()) {
            $events['ControllerAction.Model.index.beforeQuery']     = ['callable' => 'indexBeforeQuery', 'priority' => 1];
            $events['ControllerAction.Model.processWorkflow']       = ['callable' => 'processWorkflow', 'priority' => 5];
            $events['ControllerAction.Model.processReassign']       = ['callable' => 'processReassign', 'priority' => 5];
        } else {
            $events['ControllerAction.Model.index.beforePaginate']  = ['callable' => 'indexBeforePaginate', 'priority' => 1];
        }
        $events['ControllerAction.Model.index.afterAction']     = ['callable' => 'indexAfterAction', 'priority' => 1000];
        $events['ControllerAction.Model.view.afterAction']      = ['callable' => 'viewAfterAction', 'priority' => 1000];
        $events['ControllerAction.Model.addEdit.afterAction']   = ['callable' => 'addEditAfterAction', 'priority' => 1000];
        $events['ControllerAction.Model.addEdit.beforeAction']  = ['callable' => 'addEditBeforeAction', 'priority' => 1];
        $events['ControllerAction.Model.edit.beforePatch']      = ['callable' => 'editBeforePatch', 'priority' => 1];
        $events['Model.custom.onUpdateToolbarButtons']          = ['callable' => 'onUpdateToolbarButtons', 'priority' => 1000];
        $events['Model.custom.onUpdateActionButtons']           = ['callable' => 'onUpdateActionButtons', 'priority' => 1000];
        $events['Workflow.afterTransition'] = 'workflowAfterTransition';
        $events['Workflow.getEvents'] = 'getWorkflowEvents';
        foreach ($this->workflowEvents as $event) {
            $events[$event['value']] = $event['method'];
        }
        $events['Model.WorkflowSteps.afterSave'] = 'workflowStepAfterSave';
        $events['Model.Validation.getPendingRecords'] = 'getPendingRecords';
        $events['ControllerAction.Model.approve'] = 'approve';
        $events['ControllerAction.Model.onGetFormButtons'] = 'onGetFormButtons';

        $events['Model.buildValidator'] = ['callable' => 'buildValidator', 'priority' => 5];
        return $events;
    }

    public function buildValidator(Event $event, Validator $validator, $name)
    {
        return $validator
            ->notEmpty('assignee_id');
    }

    public function onGetFormButtons(Event $event, ArrayObject $buttons)
    {
        switch ($this->_table->action) {
            case 'approve':
                $buttons[1]['url'] = $this->_table->url('view');
                break;
        }
    }

    public function onDeleteRecord(Event $event, $id, Entity $workflowTransitionEntity)
    {
        $model = $this->_table;

        try {
            $entity = $model->get($id);
            $model->delete($entity);
        } catch (RecordNotFoundException $e) {
            // Do nothing
        }

        // Session is required to show alert after the redirection
        $session = new Session();
        $session->write('Workflow.onDeleteRecord', true);
        $url = '';
        if ($this->isCAv4()) {
            $url = $model->url('index', 'QUERY');
        } else {
            $url = $model->controller->ControllerAction->url('index', 'QUERY');
        }
        return $model->controller->redirect($url);
    }

    public function onAssignBack(Event $event, $id, Entity $workflowTransitionEntity)
    {
        $model = $this->_table;

        $result = $model
                ->find()
                ->where([$model->aliasField('id') => $id])
                ->all();

        if (!$result->isEmpty()) {
            $entity = $result->first();
            $this->setAssigneeAsCreator($entity);
            $model->save($entity);

        } else {
            // exception
            Log::write('error', '---------------------------------------------------------');
            Log::write('error', 'WorkflowBehavior.php >> onAssignBack() : $result is empty');
            Log::write('error', 'WorkflowBehavior.php >> onAssignBack() : model : '.$model);
            Log::write('error', 'WorkflowBehavior.php >> onAssignBack() : model alias : '.$model->alias());
            Log::write('error', '---------------------------------------------------------');
        }
    }

    public function onAssignBackToScholarshipApplicant(Event $event, $id, Entity $workflowTransitionEntity)
    {
        $model = $this->_table;

        $result = $model
                ->find()
                ->where([$model->aliasField('id') => $id])
                ->all();

        if (!$result->isEmpty()) {
            $entity = $result->first();
            $this->setAssigneeAsScholarshipApplicant($entity);
            $model->save($entity);

        } else {
            // exception
            Log::write('error', '---------------------------------------------------------');
            Log::write('error', 'WorkflowBehavior.php >> onAssignBackToScholarshipApplicant() : $result is empty');
            Log::write('error', 'WorkflowBehavior.php >> onAssignBackToScholarshipApplicant() : model : '.$model);
            Log::write('error', 'WorkflowBehavior.php >> onAssignBackToScholarshipApplicant() : model alias : '.$model->alias());
            Log::write('error', '---------------------------------------------------------');
        }
    }

    private function triggerUpdateAssigneeShell($registryAlias, $id = null, $statusId = null, $groupId = null, $userId = null, $roleId = null)
    {
        $args = '';
        $args .= !is_null($id) ? ' '.$id : '';
        $args .= !is_null($statusId) ? ' '.$statusId : '';
        $args .= !is_null($groupId) ? ' '.$groupId : '';
        $args .= !is_null($userId) ? ' '.$userId : '';
        $args .= !is_null($roleId) ? ' '.$roleId : '';

        $cmd = ROOT . DS . 'bin' . DS . 'cake UpdateAssignee '.$registryAlias.$args;
        $logs = ROOT . DS . 'logs' . DS . 'UpdateAssignee.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;

        try {
            $pid = exec($shellCmd);
            Log::write('debug', $shellCmd);
        } catch (\Exception $ex) {
            Log::write('error', __METHOD__ . ' exception when update assignee : '. $ex);
        }
    }

    public function workflowStepAfterSave(Event $event, Entity $workflowStepEntity)
    {
        $id = 0;
        $statusId = $workflowStepEntity->id;
        $WorkflowSteps = TableRegistry::get('Workflow.WorkflowSteps');
        $entity = $WorkflowSteps
            ->find()
            ->matching('Workflows.WorkflowModels')
            ->where([$WorkflowSteps->aliasField('id') => $statusId])
            ->first();

        $workflowModelEntity = $entity->_matchingData['WorkflowModels'];
        // only trigger update assignee shell where the workflow step belongs to
        if ($workflowModelEntity->model == $this->config('model')) {
            $this->triggerUpdateAssigneeShell($this->config('model'), $id, $statusId);
        }
    }

    public function getWorkflowEvents(Event $event, ArrayObject $eventsObject)
    {
        foreach ($this->workflowEvents as $key => $attr) {
            $attr['text'] = __($attr['text']);
            $attr['description'] = __($attr['description']);
            $eventsObject[] = $attr;
        }
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->isNew() && $entity->status_id == self::STATUS_OPEN) {
            $this->setStatusAsOpen($entity);
        }

        if (!$entity->has('assignee_id') || $entity->assignee_id == self::AUTO_ASSIGN) {
            $this->autoAssignAssignee($entity);
        }
    }

    public function afterDelete(Event $event, Entity $entity, ArrayObject $options)
    {
        // To delete from records and transitions table
        if ($this->attachWorkflow) {
            $this->deleteWorkflowTransitions($entity);
        }
    }

    public function onGetStatusId(Event $event, Entity $entity)
    {
        return '<span class="status highlight">' . $entity->status->name . '</span>';
    }

    public function onGetWorkflowStatus(Event $event, Entity $entity)
    {
        return '<span class="status highlight">' . $entity->workflow_status . '</span>';
    }

    public function onGetAssigneeId(Event $event, Entity $entity)
    {
        $model = $this->_table;
        $value = '';
        if (empty($entity->assignee_id)) {
            $value = '<span>&lt;'.$model->getMessage('general.unassigned').'&gt;</span>';
        }

        return $value;
    }

    public function beforeAction(Event $event)
    {
        // Initialize workflow
        $this->controller = $this->_table->controller;
        $this->model = $this->isCAv4() ? $this->_table : $this->controller->ControllerAction->model();
        $this->currentAction = $this->isCAv4() ? $this->_table->action : $this->controller->ControllerAction->action();

        if (!is_null($this->model) && in_array($this->currentAction, ['index', 'view', 'remove', 'processWorkflow', 'processReassign'])) {
            $this->attachWorkflow = true;
            $this->controller->Workflow->attachWorkflow = $this->attachWorkflow;
        }

        $model = $this->_table;
        if ($model->hasField('assignee_id')) {
            $model->fields['assignee_id']['attr']['required'] = true;
        }
    }

    public function afterAction(Event $event)
    {
        if ($this->isCAv4()) {
            $extra = func_get_arg(1);

            $toolbarButtons = $extra['toolbarButtons'];
            $action = $this->_table->action;
            $toolbarAttr = [
                'class' => 'btn btn-xs btn-default',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false
            ];

            $this->setToolbarButtons($toolbarButtons, $toolbarAttr, $action);
            $extra['toolbarButtons'] = $toolbarButtons;
        }
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->isNew()) {
            $WorkflowTransitions = TableRegistry::get('Workflow.WorkflowTransitions');
            $WorkflowTransitions->dispatchEvent('Model.Workflow.add.afterSave', [$entity], $this->_table);
        } elseif (!$entity->isNew() && $entity->dirty('assignee_id')) {
            // Trigger event on the alert log model (status and assignee transition triggered here)
            $AlertLogs = TableRegistry::get('Alert.AlertLogs');
            $event = $AlertLogs->dispatchEvent('Model.Workflow.afterSave', [$entity], $this->_table);
            if ($event->isStopped()) {
                return $event->result;
            }
            // End
        }
    }

    public function afterSaveCommit(Event $event, EntityInterface $entity, ArrayObject $options)
    {
        // for approve action
        if (!$entity->isNew() && $entity->has('validate_approve')) {
            $this->processWorkflow();
        }
    }

    public function indexBeforeAction(Event $event)
    {
        $WorkflowModels = $this->WorkflowModels;
        $registryAlias = $this->config('model');

        // Find from workflows table
        $results = $this->Workflows
            ->find('list', ['keyField' => 'id', 'valueField' => 'id'])
            ->matching('WorkflowModels', function ($q) use ($WorkflowModels, $registryAlias) {
                return $q->where([
                    $WorkflowModels->aliasField('model') => $registryAlias
                ]);
            })
            ->all();

        if ($results->isEmpty()) {
            $this->controller->Alert->warning('Workflows.noWorkflows');
        } else {
            $this->workflowIds = $results->toArray();
            $this->hasWorkflow = true;
            $this->controller->Workflow->hasWorkflow = $this->hasWorkflow;

            if ($this->isCAv4()) {
                $extra = func_get_arg(1);
                $elements = $extra['elements'];
                $elements = ['controls' => ['name' => 'Workflow.controls', 'order' => 1]] + $elements;
                $extra['elements'] = $elements;
            } else {
                $toolbarElements = [
                    ['name' => 'Workflow.controls', 'data' => [], 'options' => []]
                ];
                $this->controller->set('toolbarElements', $toolbarElements);
            }

            $filterOptions = [];
            $selectedFilter = null;
            $workflowModel = $this->getWorkflowSetup($registryAlias);

            $filter = $workflowModel->filter;
            $model = $workflowModel->model;
            $filterConfig = $this->config('filter');

            if ($filterConfig['type'] && !empty($filter)) {
                // Wofkflow Filter Options
                $filterOptions = TableRegistry::get($filter)->getList()->toArray();

                // Trigger event to get the correct wofkflow filter options
                $subject = TableRegistry::get($model);

                $params = [];
                if ($workflowModel->is_school_based) {
                    $session = $this->controller->request->session();
                    if ($session->check('Institution.Institutions.id')) {
                        $params = [
                            'institution_id' => $session->read('Institution.Institutions.id')
                        ];
                    }
                }

                $newEvent = $subject->dispatchEvent('Workflow.getFilterOptions', [$params], $subject);
                if ($newEvent->isStopped()) {
                    return $newEvent->result;
                }
                if (!empty($newEvent->result)) {
                    $filterOptions = $newEvent->result;
                }
                // End

                $filterOptions = ['-1' => '-- ' . __('Select') . ' --'] + $filterOptions;
                $selectedFilter = $this->_table->queryString('filter', $filterOptions);
                $this->_table->advancedSelectOptions($filterOptions, $selectedFilter);
                $this->_table->controller->set(compact('filterOptions', 'selectedFilter'));
                // End
            }

            if ($filterConfig['category']) {
                // Categories Options
                $categoryOptions = ['-1' => '-- ' . __('All Categories') . ' --'] + $this->getSelectOptions('WorkflowSteps.category');
                $selectedCategory = $this->_table->queryString('category', $categoryOptions);
                $this->_table->advancedSelectOptions($categoryOptions, $selectedCategory);
                $this->_table->controller->set(compact('categoryOptions', 'selectedCategory'));
                // End
            }
        }
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $options = $this->isCAv4() ? $extra['options'] : $extra;

        $registryAlias = $this->config('model');
        $workflowModel = $this->getWorkflowSetup($registryAlias);
        $filterConfig = $this->config('filter');

        $filter = $workflowModel->filter;
        if ($filterConfig['type'] && !empty($filter)) {
            $selectedFilter = $this->_table->ControllerAction->getVar('selectedFilter');

            // Filter key
            list(, $base) = pluginSplit($filter);
            $filterKey = Inflector::underscore(Inflector::singularize($base)) . '_id';
            if ($selectedFilter != -1) {
                $query->where([
                    $this->_table->aliasField($filterKey) => $selectedFilter
                ]);
            }
        }

        if ($filterConfig['category']) {
            $selectedCategory = $this->_table->ControllerAction->getVar('selectedCategory');
            if (!is_null($selectedCategory) && $selectedCategory != -1) {
                $query
                    ->matching('Statuses', function ($q) use ($selectedCategory) {
                        return $q->where(['category' => $selectedCategory]);
                    });
            }
        }

        if ($this->isCAv4()) {
            $extra['options'] = $options;
        }
    }

    public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options)
    {
        $this->indexBeforeQuery($event, $query, $options);
    }

    public function indexAfterAction(Event $event, $data)
    {
        $model = $this->_table;
        $session = new Session();
        if ($session->read('Workflow.onDeleteRecord')) {
            if ($this->isCAv4()) {
                $model->Alert->success('general.delete.success', ['reset' => true]);
            } else {
                $model->controller->Alert->success('general.delete.success', ['reset' => true]);
            }
        }
        $session->delete('Workflow.onDeleteRecord');
        $this->reorderFields();
    }

    public function viewAfterAction(Event $event, Entity $entity)
    {
        $ControllerAction = $this->isCAv4() ? $this->_table : $this->_table->ControllerAction;
        $model = $this->_table;

        // setup workflow
        if ($this->attachWorkflow) {
            $workflowStep = $this->getWorkflowStep($entity);
            if (!is_null($workflowStep)) {
                // used to get correct workflow model for StaffTransferIn and StaffTransferOut
                $modelName = $workflowStep->_matchingData['WorkflowModels']->model;
            }

            $workflowModel = isset($modelName) ? $modelName : $this->config('model');
            $workflow = $this->getWorkflow($workflowModel, $entity);

            if (!empty($workflow)) {
                $ControllerAction->field('status_id', ['visible' => false]);

                // Workflow Status - extra field
                $status = $entity->has('status') ? __($entity->status->name) : __('Open');
                $entity->workflow_status = $status;
                $ControllerAction->field('workflow_status', ['attr' => ['label' => __('Status')]]);
                // End

                // Workflow Transitions - extra field
                $tableHeaders[] = __('Transition') . '<i class="fa fa-history fa-lg"></i>';
                $tableHeaders[] = __('Action') . '<i class="fa fa-ellipsis-h fa-2x"></i>';
                $tableHeaders[] = __('Comment') . '<i class="fa fa-comments fa-lg"></i>';
                $tableHeaders[] = __('Last Executer') . '<i class="fa fa-user fa-lg"></i>';
                $tableHeaders[] = __('Last Execution Date') . '<i class="fa fa-calendar fa-lg"></i>';

                $tableCells = [];

                //Get workflow model ids for those related workflow.Eg. StaffTransferIn and StaffTransferOut
                $workflowModelIds = $this->getWorkflowModelIds($workflow->workflow_model_id);

                $transitionResults = $this->WorkflowTransitions
                    ->find()
                    ->contain(['CreatedUser'])
                    ->where([
                        $this->WorkflowTransitions->aliasField('workflow_model_id in') => $workflowModelIds,
                        $this->WorkflowTransitions->aliasField('model_reference') => $entity->id
                    ])
                    ->order([
                        $this->WorkflowTransitions->aliasField('created ASC')
                    ])
                    ->all();

                if (!$transitionResults->isEmpty()) {
                    $transitions = $transitionResults->toArray();
                    foreach ($transitions as $key => $transition) {
                        $transitionDisplay = '<span class="status past">' . __($transition->prev_workflow_step_name) . '</span>';
                        $transitionDisplay .= '<span class="transition-arrow"></span>';
                        if (count($transitions) - 1 == $key) {
                            $transitionDisplay .= '<span class="status highlight">' . __($transition->workflow_step_name) . '</span>';
                        } else {
                            $transitionDisplay .= '<span class="status past">' . __($transition->workflow_step_name) . '</span>';
                        }

                        $rowData = [];
                        $rowData[] = $transitionDisplay;
                        $rowData[] = __($transition->workflow_action_name);
                        $rowData[] = nl2br(htmlspecialchars($transition->comment));
                        $rowData[] = $transition->created_user->name;
                        $rowData[] = $transition->created->format('Y-m-d H:i:s');

                        $tableCells[$key] = $rowData;
                    }
                }

                $ControllerAction->field('workflow_transitions', [
                    'type' => 'element',
                    'element' => 'Workflow.transitions',
                    'override' => true,
                    'rowClass' => 'transition-container',
                    'tableHeaders' => $tableHeaders,
                    'tableCells' => $tableCells
                ]);
                // End

                // Reorder fields
                $fieldOrder = [];
                $fields = $model->fields;
                foreach ($fields as $fieldKey => $fieldAttr) {
                    if (!in_array($fieldKey, ['workflow_status', 'assignee_id', 'workflow_transitions'])) {
                        $fieldOrder[$fieldAttr['order']] = $fieldKey;
                    }
                }
                ksort($fieldOrder);
                array_unshift($fieldOrder, 'assignee_id');  // Set workflow_status to second
                array_unshift($fieldOrder, 'workflow_status');  // Set workflow_status to first
                $fieldOrder[] = 'workflow_transitions'; // Set workflow_transitions to last
                $ControllerAction->setFieldOrder($fieldOrder);
                // End
            } else {
                // Workflow is not configured
            }
        }
    }

    public function addEditBeforeAction(Event $event)
    {
        $model = $this->isCAv4() ? $this->_table : $this->_table->ControllerAction;
        $model->field('status_id');
    }

    public function addEditAfterAction(Event $event, Entity $entity)
    {
        $model = $this->isCAv4() ? $this->_table : $this->_table->ControllerAction;
        $model->field('assignee_id', [
            'entity' => $entity
        ]);
        $this->setFilterNotEditable($entity);
    }

    public function approve(Event $event)
    {
        if ($this->isCAv4()) {
            $model = $this->_table;
            $jsonActionAttr = $model->getQueryString('action_attr');

            if ($jsonActionAttr) {
                $extra = func_get_arg(1);
                $model->field('status_id', ['type' => 'hidden']);

                // edit fields
                $entity = null;
                $event = $model->dispatchEvent('ControllerAction.Model.edit', [$extra], $this);
                if ($event->isStopped()) {
                    return $event->result;
                }
                if ($event->result instanceof Entity) {
                    $entity = $event->result;
                }

                // workflow fields
                $actionAttr = json_decode($jsonActionAttr, true);
                $this->setupWorkflowTransitionFields($entity, $actionAttr);

                // reorder fields
                $order = 0;
                $fieldOrder = [];
                $fields = $model->fields;
                uasort($fields, function ($a, $b) {
                    return $a['order']-$b['order'];
                });

                foreach ($fields as $fieldName => $fieldAttr) {
                    if (!in_array($fieldName, ['workflow_information_header', 'current_step', 'action', 'description', 'next_step', 'workflow_assignee_id', 'workflow_comments'])) {
                        $order = $fieldAttr['order'] > $order ? $fieldAttr['order'] : $order;
                        if (array_key_exists($order, $fieldOrder)) {
                            $order++;
                        }
                        $fieldOrder[$order] = $fieldName;
                    }
                }

                ksort($fieldOrder);
                array_unshift($fieldOrder, 'workflow_comments');
                array_unshift($fieldOrder, 'workflow_assignee_id');
                array_unshift($fieldOrder, 'next_step');
                array_unshift($fieldOrder, 'description');
                array_unshift($fieldOrder, 'action');
                array_unshift($fieldOrder, 'current_step');
                array_unshift($fieldOrder, 'workflow_information_header');
                $this->_table->setFieldOrder($fieldOrder);

                // back button
                $backBtn['type'] = 'button';
                $backBtn['label'] = '<i class="fa kd-back"></i>';
                $backBtn['attr'] = [
                    'class' => 'btn btn-xs btn-default',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'title' => 'Back'
                ];
                $backBtn['url'] = $model->url('view');
                $extra['toolbarButtons']['back'] = $backBtn;

                $model->ControllerAction->renderView('/ControllerAction/edit');
                return $entity;
            } else {
                return $model->controller->redirect($model->url('view'));
            }
        }
    }

    public function setupWorkflowTransitionFields(Entity $entity, array $actionAttr)
    {
        $model = $this->_table;
        $alias = $this->WorkflowTransitions->alias();

        // show postEvent description
        if (!empty($actionAttr['event_description'])) {
            $model->Alert->info($actionAttr['event_description'], ['type' => 'string', 'reset' => true]);
        }

        $workflowStep = $this->getWorkflowStep($entity);
        $workflow = $workflowStep->_matchingData['Workflows'];

        // visible fields
        $model->field('workflow_information_header', [
            'type' => 'section',
            'title' => __('Status')
        ]);
        $model->field('current_step', [
            'type' => 'readonly',
            'fieldName' => $alias.'.prev_workflow_step_name',
            'value' => $workflowStep->name,
            'attr' => ['value' => $workflowStep->name]
        ]);
        $model->field('action', [
            'type' => 'readonly',
            'fieldName' => $alias.'.workflow_action_name',
            'value' => $actionAttr['name'],
            'attr' => ['value' => $actionAttr['name']]
        ]);
        $model->field('description', [
            'type' => 'readonly',
            'attr' => ['value' => $actionAttr['description']]
        ]);
        $model->field('next_step', [
            'type' => 'readonly',
            'fieldName' => $alias.'.workflow_step_name',
            'value' => $actionAttr['next_step_name'],
            'attr' => ['value' => $actionAttr['next_step_name']]
        ]);
        $model->field('workflow_assignee_id', [
            'type' => 'select',
            'entity' => $actionAttr
        ]);
        $model->field('workflow_comments', [
            'type' => 'text',
            'fieldName' => $alias.'.comment'
        ]);


        // hidden fields
        $model->field('prev_workflow_step_id', [
            'type' => 'hidden',
            'fieldName' => $alias.'.prev_workflow_step_id',
            'value' => $workflowStep->id
        ]);
        $model->field('workflow_step_id', [
            'type' => 'hidden',
            'fieldName' => $alias.'.workflow_step_id',
            'value' => $actionAttr['next_step_id']
        ]);
        $model->field('workflow_action_id', [
            'type' => 'hidden',
            'fieldName' => $alias.'.workflow_action_id',
            'value' => $actionAttr['id']
        ]);
        $model->field('workflow_model_id', [
            'type' => 'hidden',
            'fieldName' => $alias.'.workflow_model_id',
            'value' => $workflow->workflow_model_id
        ]);
        $model->field('model_reference', [
            'type' => 'hidden',
            'fieldName' => $alias.'.model_reference',
            'value' => $entity->id
        ]);
        $model->field('validate_approve', [
            'type' => 'hidden',
            'value' => 1
        ]);
    }

    public function editBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $model = $this->isCAv4() ? $this->_table : $this->_table->ControllerAction;

        // for approve action
        if (isset($data[$model->alias()]['validate_approve'])) {
            if (isset($data[$model->alias()]['workflow_assignee_id']) && !empty($data[$model->alias()]['workflow_assignee_id'])) {
                $data['WorkflowTransitions']['assignee_id'] = $data[$model->alias()]['workflow_assignee_id'];
            }
        }
    }

    public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel)
    {
        $this->setToolbarButtons($toolbarButtons, $attr, $action);
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        // check line by line, whether to show / hide the action buttons
        if ($this->attachWorkflow) {
            $model = $this->_table;
            if (!$model->AccessControl->isAdmin()) {
                $buttons = $model->onUpdateActionButtons($event, $entity, $buttons);

                $workflowStep = $this->getWorkflowStep($entity);
                $isEditable = false;
                $isDeletable = false;
                if (!empty($workflowStep)) {
                    $isEditable = $workflowStep->is_editable == 1 ? true : false;
                    $isDeletable = $workflowStep->is_removable == 1 ? true : false;
                }

                if (array_key_exists('edit', $buttons) && !$isEditable) {
                    unset($buttons['edit']);
                }

                if (array_key_exists('remove', $buttons) && !$isDeletable) {
                    unset($buttons['remove']);
                }

                return $buttons;
            }
        }
    }

    public function onUpdateFieldStatusId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'index') {
            $attr['type'] = 'select';
        } else if ($action == 'add') {
            $attr['type'] = 'hidden';
            $attr['value'] = 0;
        } else if ($action == 'edit') {
            $attr['type'] = 'hidden';
        }

        return $attr;
    }

    public function onUpdateFieldAssigneeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'view') {
            $attr['type'] = 'string';
        } elseif ($action == 'add') {
            $model = $this->isCAv4() ? $this->_table : $this->_table->ControllerAction;
            $entity = $attr['entity'];
            $registryAlias = $this->config('model');
            $workflowModelEntity = $this->getWorkflowSetup($registryAlias);
            // find the filter type column key
            $filterKey = null;
            if (!empty($workflowModelEntity->filter)) {
                list(, $base) = pluginSplit($workflowModelEntity->filter);
                $filterKey = Inflector::underscore(Inflector::singularize($base)) . '_id';
            }

            $isSchoolBased = $workflowModelEntity->is_school_based;
            $assignToSelf = false;

            // extract the first step if the workflow can be found:
            // - the workflow model do not have filter key
            // - the worflow has filter key and entity has the filter key
            if (is_null($filterKey) || !is_null($filterKey) && $entity->has($filterKey)) {
                $firstStepEntity = $this->getFirstWorkflowStep($registryAlias, $entity);
                if (empty($firstStepEntity->security_roles)) {
                    $assignToSelf = true;
                } else {
                    $firstStepId = $firstStepEntity->id;
                    $assigneeOptions = $this->getFirstStepAssigneeOptions($entity, $isSchoolBased, $firstStepId, $request);
                }
            }

            if (!$assignToSelf) {
                if (isset($assigneeOptions) && !empty($assigneeOptions)) {
                    $assigneeOptions = ['' => '-- ' . __('Select Assignee') . ' --'] + $assigneeOptions;
                } else {
                    $assigneeOptions = ['' => $model->getMessage('general.select.noOptions')];
                }
            }

            if ($assignToSelf) {
                // if no security roles is set to the workflow open status, assign to self
                $userId = $model->Auth->user('id');
                $userEntity = $this->getAssigneeEntity($userId);

                $attr['type'] = 'readonly';
                $attr['value'] = $userEntity->id;
                $attr['attr']['value'] = $userEntity->name_with_id;
            } else {
                $attr['type'] = 'chosenSelect';
                $attr['attr']['multiple'] = false;
                $attr['options'] = $assigneeOptions;
            }
        } elseif ($action == 'edit') {
            $model = $this->isCAv4() ? $this->_table : $this->_table->ControllerAction;
            $entity = $attr['entity'];

            if ($entity->has('assignee_id') && $entity->assignee_id != 0) {
                $assigneeId = $entity->assignee_id;
                $assigneeEntity = $this->getAssigneeEntity($assigneeId);
                $assigneeName = $assigneeEntity->name_with_id;
            } else {
                $assigneeName = '<'.__('Unassigned').'>';
                $assigneeId = 0;
            }

            $attr['type'] = 'readonly';
            $attr['value'] = $assigneeId;
            $attr['attr']['value'] = $assigneeName;
        } elseif ($action == 'approve') {
            $attr['type'] = 'hidden';
        }

        return $attr;
    }

    public function getFirstStepAssigneeOptions(Entity $entity, $isSchoolBased, $stepId, Request $request)
    {
        $params = [
            'is_school_based' => $isSchoolBased,
            'workflow_step_id' => $stepId
        ];

        if ($isSchoolBased) {
            if ($entity->has('institution_id')) {
                $params['institution_id'] = $entity->institution_id;
            } else {
                $session = $request->session();
                if ($session->check('Institution.Institutions.id')) {
                    $institutionId = $session->read('Institution.Institutions.id');
                    $params['institution_id'] = $institutionId;
                }
            }
        }

        $SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
        $assigneeOptions = $SecurityGroupUsers->getAssigneeList($params);
        return $assigneeOptions;
    }

    public function getFirstWorkflowStep($registryAlias, Entity $entity)
    {
        $model = $this->isCAv4() ? $this->_table : $this->_table->ControllerAction;
        $workflowEntity = $this->getWorkflow($registryAlias, $entity);
        $workflowId = $workflowEntity->id;

        $firstStepEntity = $model->Statuses
            ->find()
            ->matching('Workflows.WorkflowModels', function ($q) use ($workflowId) {
                return $q->where(['Workflows.id' => $workflowId]);
            })
            ->contain(['SecurityRoles'])
            ->where([
                $model->Statuses->aliasField('category') => WorkflowSteps::TO_DO
            ])
            ->first();

        return $firstStepEntity;
    }

    public function onUpdateFieldWorkflowAssigneeId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'approve') {
            $actionAttr = $attr['entity'];

            if ($actionAttr['auto_assign_assignee']) {
                $assigneeOptions = [self::AUTO_ASSIGN => __('Auto Assign')];
                $attr['select'] = false;
            } else {
                $model = $this->_table;
                $session = $model->request->session();
                $institutionId = isset($model->request->params['institutionId']) ? $model->paramsDecode($model->request->params['institutionId'])['id'] : $session->read('Institution.Institutions.id');

                $SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
                $params = [
                    'is_school_based' => $actionAttr['is_school_based'],
                    'workflow_step_id' => $actionAttr['next_step_id'],
                    'institution_id' => $institutionId
                ];

                $assigneeOptions = $SecurityGroupUsers->getAssigneeList($params);
            }

            $attr['options'] = $assigneeOptions;
            return $attr;
        }
    }

    public function reorderFields()
    {
        $order = 0;
        $fieldOrder = [];
        $fields = $this->_table->fields;
        uasort($fields, function ($a, $b) {
            return $a['order']-$b['order'];
        });

        foreach ($fields as $fieldName => $fieldAttr) {
            if (!in_array($fieldName, ['status_id', 'assignee_id'])) {
                $order = $fieldAttr['order'] > $order ? $fieldAttr['order'] : $order;
                if (array_key_exists($order, $fieldOrder)) {
                    $order++;
                }
                $fieldOrder[$order] = $fieldName;
            }
        }

        ksort($fieldOrder);
        array_unshift($fieldOrder, 'assignee_id');  // Set Status to second
        array_unshift($fieldOrder, 'status_id');    // Set Status to first

        if ($this->isCAv4()) {
            $this->_table->setFieldOrder($fieldOrder);
        } else {
            $this->_table->ControllerAction->setFieldOrder($fieldOrder);
        }
    }

    //Function to return ids of related workflow_models
    public function getWorkflowModelIds($workflowModelId) {
        $modelNames = ['%StaffTransfer%','%StudentTransfer%']; //Add in model names here for future releated workflow_models

        $WorkFlowModelTable = $this->WorkflowModels;

        foreach ($modelNames as $modelCondition) {
            $workflowIds = $WorkFlowModelTable
                ->find('list', [
                    'keyField' => 'id',
                    'valueField' => 'id'
               ])
               ->where([$WorkFlowModelTable->aliasField('model LIKE') => $modelCondition])
               ->toArray();

            if (in_array($workflowModelId, $workflowIds)) {
                return $workflowIds;
            }
        }

        return [$workflowModelId];
    }

    public function getWorkflowSetup($registryAlias)
    {
        if (is_null($this->workflowSetup)) {
            $workflowModel = $this->WorkflowModels
                    ->find()
                    ->where([
                        $this->WorkflowModels->aliasField('model') => $registryAlias
                    ])
                    ->first();

            $this->workflowSetup = $workflowModel;
        } else {
            $workflowModel = $this->workflowSetup;
        }

        return $workflowModel;
    }

    public function getWorkflow($registryAlias, $entity = null, $filterId = null)
    {
        $workflowModel = $this->getWorkflowSetup($registryAlias);

        if (!empty($workflowModel)) {
            // Find all Workflow setup for the model
            $workflowIdsQuery = $this->Workflows
                ->find('list', ['keyField' => 'id', 'valueField' => 'id'])
                ->where([
                    $this->Workflows->aliasField('workflow_model_id') => $workflowModel->id
                ]);

            $excludedModels = $this->Workflows->getExcludedModels();
            if (in_array($workflowModel->model, $excludedModels) && !is_null($entity) && $entity->has('workflow_rule_id') && !empty($entity->workflow_rule_id)) {
                $workflowRuleId = $entity->workflow_rule_id;
                $workflowIdsQuery->matching('WorkflowRules', function ($q) use ($workflowRuleId) {
                    return $q->where([
                        'WorkflowRules.id' => $workflowRuleId
                    ]);
                });
            }

            $workflowIds = $workflowIdsQuery->toArray();

            $workflowQuery = $this->Workflows
                ->find()
                ->contain(['WorkflowSteps.WorkflowActions']);

            if (empty($workflowModel->filter)) {
                $workflowQuery->where([
                    $this->Workflows->aliasField('id IN') => $workflowIds
                ]);
            } else {
                // Filter key
                list(, $base) = pluginSplit($workflowModel->filter);
                $filterKey = Inflector::underscore(Inflector::singularize($base)) . '_id';

                $workflowId = 0;
                if (empty($filterId)) {
                    if (!is_null($entity) && $entity->has($filterKey)) {
                        $filterId = $entity->{$filterKey};
                    }
                }

                if (!is_null($filterId)) {
                    $conditions = [$this->WorkflowsFilters->aliasField('workflow_id IN') => $workflowIds];

                    $filterQuery = $this->WorkflowsFilters
                        ->find()
                        ->where($conditions)
                        ->where([$this->WorkflowsFilters->aliasField('filter_id') => $filterId]);

                    $workflowFilterResults = $filterQuery->all();

                    // Use Workflow with filter if found otherwise use Workflow that Apply To All
                    if ($workflowFilterResults->isEmpty()) {
                        $filterQuery
                        ->where($conditions, [], true)
                        ->where([$this->WorkflowsFilters->aliasField('filter_id') => 0]);

                        $workflowResults = $filterQuery->all();
                    } else {
                        $workflowResults = $workflowFilterResults;
                    }

                    if (!$workflowResults->isEmpty()) {
                        $workflowId = $workflowResults->first()->workflow_id;
                    }
                }

                $workflowQuery->where([
                    $this->Workflows->aliasField('id') => $workflowId
                ]);
            }

            return $workflowQuery->first();
        } else {
            return null;
        }
    }

    public function getRecord()
    {
        $ControllerAction = $this->isCAv4() ? $this->_table : $this->_table->ControllerAction;
        $model = $this->_table;

        $ids = $ControllerAction->paramsDecode(current($ControllerAction->paramsPass()));
        $idKey = $ControllerAction->getIdKeys($model, $ids);

        if ($model->exists($idKey)) {
            $entity = $model->get($idKey, ['contain' => ['Statuses']]);
            return $entity;
        } else {
            return null;
        }
    }

    public function getWorkflowStep($entity = null)
    {
        if (!is_null($entity)) {
            $workflowStepId = $entity->has('status') ? $entity->status->id : $entity->status_id;

            $model = $this->_table;
            $userId = $model->Auth->user('id');
            $assigneeId = $entity->assignee_id;

            // user roles
            $roleIds = [];
            $event = $model->dispatchEvent('Workflow.onUpdateRoles', null, $this);
            if ($event->result) {
                $roleIds = $event->result;
            } else {
                $roles = $model->AccessControl->getRolesByUser()->toArray();
                foreach ($roles as $key => $role) {
                    $roleIds[$role->security_role_id] = $role->security_role_id;
                }
            }
            // End

            if ($model->AccessControl->isAdmin()) {
                // super admin allow to see the full list of action buttons
                $query = $this->WorkflowSteps
                    ->find()
                    ->matching('Workflows.WorkflowModels')
                    ->contain(['WorkflowActions' => function ($q) {
                            return $q
                                ->find('visible')
                                ->where(['next_workflow_step_id !=' => 0]);
                    }
                    ])
                    ->contain('WorkflowActions.NextWorkflowSteps')
                    ->where([
                        $this->WorkflowSteps->aliasField('id') => $workflowStepId // Latest Workflow Step
                    ]);

                return $query->first();
            } else {
                // if is not super admin
                if (!empty($roleIds)) {
                    $workflowStepsRoles = $this->WorkflowStepsRoles
                        ->find()
                        ->where([
                            $this->WorkflowStepsRoles->aliasField('workflow_step_id') => $workflowStepId,
                            $this->WorkflowStepsRoles->aliasField('security_role_id IN') => $roleIds
                        ])
                        ->all();

                    if ($workflowStepsRoles->isEmpty()) {
                        // if login user roles is not allow to access current step
                        if ($userId == $assigneeId) {
                            $query = $this->WorkflowSteps
                                ->find()
                                ->matching('Workflows.WorkflowModels')
                                ->contain(['WorkflowActions' => function ($q) {
                                        return $q
                                            ->find('visible')
                                            ->where([
                                                'next_workflow_step_id !=' => 0,
                                                'allow_by_assignee' => 1
                                            ]);
                                }
                                ])
                                ->contain('WorkflowActions.NextWorkflowSteps')
                                ->where([
                                    $this->WorkflowSteps->aliasField('id') => $workflowStepId // Latest Workflow Step
                                ]);

                            return $query->first();
                        }
                    } else {
                        // if login user roles is allow to access current step
                        $query = $this->WorkflowSteps
                            ->find()
                            ->matching('Workflows.WorkflowModels')
                            ->contain(['WorkflowActions' => function ($q) {
                                    return $q
                                        ->find('visible')
                                        ->where(['next_workflow_step_id !=' => 0]);
                            }
                            ])
                            ->contain('WorkflowActions.NextWorkflowSteps')
                            ->where([
                                $this->WorkflowSteps->aliasField('id') => $workflowStepId // Latest Workflow Step
                            ])
                            ->innerJoin(
                                [$this->WorkflowStepsRoles->alias() => $this->WorkflowStepsRoles->table()],
                                [
                                    $this->WorkflowStepsRoles->aliasField('workflow_step_id = ') . $this->WorkflowSteps->aliasField('id'),
                                    $this->WorkflowStepsRoles->aliasField('security_role_id IN') => $roleIds
                                ]
                            );

                        return $query->first();
                    }
                }
            }
        }

        // default return null
        return null;
    }

    public function getReassignModalOptions(Entity $entity)
    {
        $model = $this->_table;
        $step = $this->getWorkflowStep($entity);

        $assigneeUrl = Router::url(['plugin' => 'Workflow', 'controller' => 'Workflows', 'action' => 'ajaxGetAssignees']);

        if ($entity->has('assignee_id') && $entity->assignee_id != 0) {
            $assigneeEntity = $this->getAssigneeEntity($entity->assignee_id);
            $assigneeName = $assigneeEntity->name_with_id;
            $assigneeId = $assigneeEntity->id;
        } else {
            $assigneeName = '<'.__('Unassigned').'>';
            $assigneeId = 0;
        }

        if (!is_null($step)) {
            $workflow = $step->_matchingData['Workflows'];

            $fields = [
                'id' => [
                    'type' => 'hidden',
                    'value' => $entity->id
                ]
            ];

            $contentFields = new ArrayObject([
                'status_id' => [
                    'label' => __('Status'),
                    'type' => 'string',
                    'readonly' => 'readonly',
                    'disabled' => 'disabled',
                    'class'=> 'workflow-reassign-status',
                    'value' => $entity->status->name
                ],
                'current_assignee_name' => [
                    'label' => __('Current Assignee'),
                    'type' => 'string',
                    'readonly' => 'readonly',
                    'disabled' => 'disabled',
                    'class'=> 'workflow-reassign-current-assignee-name',
                    'value' => $assigneeName
                ],
                'current_assignee_id' => [
                    'type' => 'hidden',
                    'class'=> 'workflow-reassign-current-assignee-id',
                    'value' => $assigneeId
                ],
                'assignee_id' => [
                    'label' => __('New Assignee'),
                    'type' => 'select',
                    'class'=> 'workflow-reassign-new-assignee',
                    'assignee-url' => $assigneeUrl
                ],
                'comment' => [
                    'label' => __('Comment'),
                    'type' => 'textarea',
                    'class'=> 'workflow-reassign-comment'
                ]
            ]);

            // $model->dispatchEvent('Workflow.addCustomModalFields', [$entity, $contentFields, $alias], $this);

            $content = '';
            $content = '<style type="text/css">.modal-footer { clear: both; } .modal-body textarea { width: 60%; }</style>';
            $content .= '<div class="input string"><span class="button-label"></span>';
                $content .= '<div class="workflow-reassign-assignee-loading">' . __('Loading') . '</div>';
                $content .= '<div class="workflow-reassign-assignee-no_options">' . __('No options') . '</div>';
                $content .= '<div class="workflow-reassign-assignee-error">' . __('This field cannot be left empty') . '</div>';
                $content .= '<div class="workflow-reassign-assignee-same-error">' . __('New Assignee cannot be the same as Current Assignee') . '</div>';
            $content .= '</div>';
            $content .= '<div class="input string"><span class="button-label"></span><div class="workflow-reassign-assignee-sql-error error-message">' .$model->getMessage('general.error'). '</div></div>';
            $buttons = [
                '<button id="reassign-submit" type="submit" class="btn btn-default" onclick="return Workflow.onSubmit(\'reassign\');">' . __('Reassign') . '</button>'
            ];

            $modal = [
                'id' => 'workflowReassign',
                'title' => __('Reassign'),
                'content' => $content,
                'contentFields' => $contentFields,
                'form' => [
                    'model' => $model,
                    'formOptions' => [
                        'class' => 'form-horizontal',
                        'url' => $this->isCAv4() ? $model->url('processReassign') : $model->ControllerAction->url('processReassign'),
                        'onSubmit' => 'document.getElementById("reassign-submit").disabled=true;'
                    ],
                    'fields' => $fields
                ],
                'buttons' => $buttons,
                'cancelButton' => true
            ];

            return $modal;
        } else {
            return [];
        }
    }

    public function getModalOptions(Entity $entity)
    {
        $model = $this->_table;
        $step = $this->getWorkflowStep($entity);

        $assigneeUrl = Router::url(['plugin' => 'Workflow', 'controller' => 'Workflows', 'action' => 'ajaxGetAssignees']);

        if (!is_null($step)) {
            $workflow = $step->_matchingData['Workflows'];

            $alias = $this->WorkflowTransitions->alias();
            // workflow_step_id is needed for afterSave logic in WorkflowTransitions
            $fields = [
                $alias.'.prev_workflow_step_id' => [
                    'type' => 'hidden',
                    'value' => $step->id
                ],
                $alias.'.prev_workflow_step_name' => [
                    'type' => 'hidden',
                    'value' => $step->name
                ],
                $alias.'.workflow_step_id' => [
                    'type' => 'hidden',
                    'value' => 0,
                    'class' => 'workflowtransition-step-id',
                    'unlockField' => true
                ],
                $alias.'.workflow_step_name' => [
                    'type' => 'hidden',
                    'value' => '',
                    'class' => 'workflowtransition-step-name',
                    'unlockField' => true
                ],
                $alias.'.workflow_action_id' => [
                    'type' => 'hidden',
                    'value' => 0,
                    'class' => 'workflowtransition-action-id',
                    'unlockField' => true
                ],
                $alias.'.workflow_action_name' => [
                    'type' => 'hidden',
                    'value' => '',
                    'class' => 'workflowtransition-action-name',
                    'unlockField' => true
                ],
                $alias.'.workflow_action_description' => [
                    'type' => 'hidden',
                    'value' => '',
                    'class' => 'workflowtransition-action-description',
                    'unlockField' => true
                ],
                $alias.'.workflow_model_id' => [
                    'type' => 'hidden',
                    'value' => $workflow->workflow_model_id
                ],
                $alias.'.model_reference' => [
                    'type' => 'hidden',
                    'value' => $entity->id
                ],
                $alias.'.assignee_required' => [
                    'type' => 'hidden',
                    'value' => 1,
                    'class' => 'workflowtransition-assignee-required',
                    'unlockField' => true
                ],
                $alias.'.comment_required' => [
                    'type' => 'hidden',
                    'value' => 0,
                    'class' => 'workflowtransition-comment-required',
                    'unlockField' => true
                ]
            ];

            $contentFields = new ArrayObject(
                [
                    $alias.'.action_name' => [
                        'label' => __('Action'),
                        'type' => 'string',
                        'readonly' => 'readonly',
                        'disabled' => 'disabled',
                        'class'=> 'workflowtransition-action-name'
                    ],
                    $alias.'.action_description' => [
                        'label' => __('Description'),
                        'type' => 'textarea',
                        'readonly' => 'readonly',
                        'disabled' => 'disabled',
                        'class'=> 'workflowtransition-action-description'
                    ],
                    $alias.'.step_name' => [
                        'label' => __('Next Step'),
                        'type' => 'string',
                        'readonly' => 'readonly',
                        'disabled' => 'disabled',
                        'class'=> 'workflowtransition-step-name'
                    ],
                    $alias.'.assignee_id' => [
                        'label' => __('Assignee'),
                        'type' => 'select',
                        'class'=> 'workflowtransition-assignee-id',
                        'assignee-url' => $assigneeUrl
                    ],
                    $alias.'.comment' => [
                        'label' => __('Comment'),
                        'type' => 'textarea',
                        'class'=> 'workflowtransition-comment'
                    ]
                ]);

            $model->dispatchEvent('Workflow.addCustomModalFields', [$entity, $contentFields, $alias], $this);

            $content = '';
            $content = '<style type="text/css">.modal-footer { clear: both; } .modal-body textarea { width: 60%; }</style>';
            $content .= '<div class="input string"><span class="button-label"></span>';
                $content .= '<div class="workflowtransition-assignee-loading">' . __('Loading') . '</div>';
                $content .= '<div class="workflowtransition-assignee-no_options">' . __('No options') . '</div>';
                $content .= '<div class="workflowtransition-assignee-error">' . __('This field cannot be left empty') . '</div>';
            $content .= '</div>';
            $content .= '<div class="input string"><span class="button-label"></span><div class="workflowtransition-comment-error error-message">' . __('This field cannot be left empty') . '</div></div>';
            $content .= '<div class="input string"><span class="button-label"></span><div class="workflowtransition-assignee-sql-error error-message">' .$model->getMessage('general.error'). '</div></div>';
            $content .= '<div class="input string"><span class="button-label"></span><div class="workflowtransition-event-description error-message"></div></div>';
            $buttons = [
                '<button id="workflow-submit" type="submit" class="btn btn-default" onclick="return Workflow.onSubmit();">' . __('Save') . '</button>'
            ];

            $modal = [
                'id' => 'workflowTransition',
                'title' => __('Add Comment'),
                'content' => $content,
                'contentFields' => $contentFields,
                'form' => [
                    'model' => $model,
                    'formOptions' => [
                        'class' => 'form-horizontal',
                        'url' => $this->isCAv4() ? $model->url('processWorkflow') : $model->ControllerAction->url('processWorkflow'),
                        'onSubmit' => 'document.getElementById("workflow-submit").disabled=true;'
                    ],
                    'fields' => $fields
                ],
                'buttons' => $buttons,
                'cancelButton' => true
            ];

            return $modal;
        } else {
            return [];
        }
    }

    public function getWorkflowStepList()
    {
        $steps = [];

        $query = $this->WorkflowSteps
            ->find('list');

        if (!empty($this->workflowIds)) {
            $query->where([
                $this->WorkflowSteps->aliasField('workflow_id IN') => $this->workflowIds
            ]);
        }

        $steps = $query->toArray();

        return $steps;
    }

    private function setToolbarButtons(ArrayObject $toolbarButtons, array $attr, $action)
    {
        // Unset edit buttons and add action buttons
        // POCOR-4529: Added disableWorkflow for view/index of features to view workflow pages without action buttons
        if ($this->attachWorkflow && !$this->config('disableWorkflow')) {
            if ($action == 'index') {
                if ($this->hasWorkflow == false && $toolbarButtons->offsetExists('add')) {
                    unset($toolbarButtons['add']);
                }
            } elseif ($action == 'view') {
                $isEditable = false;
                $isDeletable = false;

                $entity = $this->getRecord();
                $workflowStep = $this->getWorkflowStep($entity);
                $actionButtons = [];
                if (!empty($workflowStep)) {
                    $isSchoolBased = $workflowStep->_matchingData['WorkflowModels']->is_school_based;

                    // Enabled edit button only when login user in approval role for the step and that step is editable
                    if ($workflowStep->is_editable) {
                        $isEditable = true;
                    }

                    if ($workflowStep->is_removable == 1) {
                        $isDeletable = true;
                    }
                    // End

                    $canAddButtons = $this->checkIfCanAddButtons($isSchoolBased, $entity);

                    if ($canAddButtons) {
                        // reassign button - only super admin and login user is the assignee of the workflow
                        $model = $this->isCAv4() ? $this->_table : $this->_table->ControllerAction;

                        $userId = $model->Auth->user('id');
                        $isSuperAdmin = $model->Auth->user('super_admin');
                        $assigneeId = $entity->assignee_id;

                        if ($isSuperAdmin || $userId == $assigneeId) {
                            $reassignJsonObject = [
                                'step_id' => $workflowStep->id,
                                'is_school_based' => $isSchoolBased,
                                'auto_assign_assignee' => 0,
                            ];

                            $json = json_encode($reassignJsonObject, JSON_NUMERIC_CHECK);

                            $reassignButtonAttr = [
                                'escapeTitle' => false,
                                'escape' => true,
                                'onclick' => 'Workflow.init();Workflow.copy('.$json.', "reassign");return false;',
                                'data-toggle' => 'modal',
                                'data-target' => '#workflowReassign'
                            ];
                            $reassignButtonAttr = array_merge($attr, $reassignButtonAttr);
                            $reassignButton = [];
                            $reassignButton['type'] = 'button';
                            $reassignButton['label'] = '<i class="fa kd-reassign"></i>';
                            $reassignButton['url'] = '#';
                            $reassignButton['attr'] = $reassignButtonAttr;
                            $reassignButton['attr']['title'] = __('Reassign');
                            $toolbarButtons['reassign'] = $reassignButton;

                            $modal = $this->getReassignModalOptions($entity);
                            if (!empty($modal)) {
                                if (!isset($this->_table->controller->viewVars['modals'])) {
                                    $this->_table->controller->set('modals', ['workflowReassign' => $modal]);
                                } else {
                                    $modals = array_merge($this->_table->controller->viewVars['modals'], ['workflowReassign' => $modal]);
                                    $this->_table->controller->set('modals', $modals);
                                }
                            }
                        }
                        // end

                        foreach ($workflowStep->workflow_actions as $actionKey => $actionObj) {
                            $eventKeys = $actionObj->event_key;
                            $eventsObject = new ArrayObject();
                            $subjectEvent = $this->_table->dispatchEvent('Workflow.getEvents', [$eventsObject], $this->_table);
                            if ($subjectEvent->isStopped()) {
                                return $subjectEvent->result;
                            }
                            $eventArray = $eventsObject->getArrayCopy();

                            $eventDescription = '';
                            $events = explode(",", $eventKeys);
                            $actionObj->assignee_required = 1;
                            foreach ($events as $eventKey) {
                                // assignee is required by default unless onAssignBack event is added
                                if ($eventKey == 'Workflow.onAssignBack' || $eventKey == 'Workflow.onAssignBackToScholarshipApplicant') {
                                    $actionObj->assignee_required = 0;
                                }
                                $key = array_search($eventKey, array_column($eventArray, 'value'));
                                if ($key !== false) {
                                    if (isset($eventArray[$key]['description']) && $eventKey != 'Workflow.onAssignBack') {
                                        $eventDescription .= $eventArray[$key]['description'];
                                        $eventDescription .= '<br/>';
                                    }
                                }
                            }

                            $visibleField = [];
                            $actionEvent = $this->_table->dispatchEvent('Workflow.setVisibleCustomModalField', [$eventKeys], $this->_table);
                            if ($actionEvent->result) {
                                $visibleField[] = $actionEvent->result;
                            }

                            $autoAssignAssignee = 0;
                            $event = $this->_table->dispatchEvent('Workflow.setAutoAssignAssigneeFlag', [$actionObj], $this->_table);
                            if (is_int($event->result)) {
                                $autoAssignAssignee = $event->result;
                            }

                            $actionType = $actionObj->action;
                            $button = [
                                'id' => $actionObj->id,
                                'name' => $actionObj->name,
                                'description' => $actionObj->description,
                                'next_step_id' => $actionObj->next_workflow_step_id,
                                'next_step_name' => $actionObj->next_workflow_step->name,
                                'assignee_required' => $actionObj->assignee_required,
                                'comment_required' => $actionObj->comment_required,
                                'event_description' => $eventDescription,
                                'is_school_based' => $isSchoolBased,
                                'auto_assign_assignee' => $autoAssignAssignee,
                                'modal_visible_field' => $visibleField
                            ];

                            $json = json_encode($button, JSON_NUMERIC_CHECK);

                            $buttonAttr = [
                                'escapeTitle' => false,
                                'escape' => true,
                                'onclick' => 'Workflow.init();Workflow.copy('.$json.');return false;',
                                'data-toggle' => 'modal',
                                'data-target' => '#workflowTransition',

                            ];
                            $buttonAttr = array_merge($attr, $buttonAttr);

                            if (is_null($actionType)) {
                                if (array_key_exists('class', $buttonAttr)) {
                                    unset($buttonAttr['class']);
                                }

                                $actionButton = [];
                                $actionButton['label'] = __($actionObj->name);
                                $actionButton['url'] = '#';
                                $actionButton['attr'] = $buttonAttr;
                                $actionButton['attr']['title'] = __($actionObj->name);
                                $actionButton['attr']['role'] = 'menuitem';

                                $actionButtons[] = $actionButton;
                            } else {
                                if ($actionType == 0) { // Approve
                                    $approveButton = [];
                                    $approveButton['type'] = 'button';
                                    $approveButton['label'] = '<i class="fa kd-approve"></i>';

                                    $validateApprove = $this->getWorkflowStepsParamValue($workflowStep->id, 'validate_approve');
                                    if (!$validateApprove) {
                                        $approveButton['url'] = '#';
                                        $approveButton['attr'] = $buttonAttr;
                                    } else {
                                        // approve function
                                        $approveButton['url'] = $this->_table->setQueryString($this->_table->url('approve'), ['action_attr' => $json]);
                                        $approveButton['attr'] = $attr;
                                    }

                                    $approveButton['attr']['title'] = __($actionObj->name);
                                    $toolbarButtons['approve'] = $approveButton;
                                } elseif ($actionType == 1) { // Reject
                                    $rejectButton = [];
                                    $rejectButton['type'] = 'button';
                                    $rejectButton['label'] = '<i class="fa kd-reject"></i>';
                                    $rejectButton['url'] = '#';
                                    $rejectButton['attr'] = $buttonAttr;
                                    $rejectButton['attr']['title'] = __($actionObj->name);

                                    $toolbarButtons['reject'] = $rejectButton;
                                }
                            }
                        }
                    }
                }

                if (!$this->_table->AccessControl->isAdmin() && $toolbarButtons->offsetExists('edit') && !$isEditable) {
                    unset($toolbarButtons['edit']);
                }

                if (!$this->_table->AccessControl->isAdmin() && $toolbarButtons->offsetExists('remove') && !$isDeletable) {
                    unset($toolbarButtons['remove']);
                }

                // More Actions
                $moreButtonLink = [];
                if (!empty($actionButtons)) {
                    $moreButtonLink = [
                        'title' => __('More Actions') . '<span class="caret-down"></span>',
                        'url' => '#',
                        'options' => [
                            'escapeTitle' => false, // Disabled coversion of HTML special characters in $title to HTML entities
                            'id' => 'action-menu',
                            'class' => 'btn btn-default action-toggle outline-btn',
                            'data-toggle' => 'dropdown',
                            'aria-expanded' => true
                        ]
                    ];

                    $moreButton = [];
                    $moreButton['type'] = 'element';
                    $moreButton['element'] = 'Workflow.buttons';
                    $moreButton['data'] = [
                        'buttons' => $actionButtons
                    ];
                    $moreButton['options'] = [];

                    $toolbarButtons['more'] = $moreButton;
                }
                $this->_table->controller->set(compact('moreButtonLink', 'actionButtons'));
                // End

                // Modal
                $modal = $this->getModalOptions($entity);
                if (!empty($modal)) {
                    if (!isset($this->_table->controller->viewVars['modals'])) {
                        $this->_table->controller->set('modals', ['workflowTransition' => $modal]);
                    } else {
                        $modals = array_merge($this->_table->controller->viewVars['modals'], ['workflowTransition' => $modal]);
                        $this->_table->controller->set('modals', $modals);
                    }
                }
                // End
            }
        }
    }

    public function setAssigneeAsCreator(Entity $entity)
    {
        if ($entity->has('created_user_id')) {
            $entity->assignee_id = $entity->created_user_id;
        }
    }

    public function setAssigneeAsScholarshipApplicant(Entity $entity)
    {
        if ($entity->has('applicant_id')) {
            $entity->assignee_id = $entity->applicant_id;
        }
    }

    public function setStatusAsOpen(Entity $entity)
    {
        $model = $this->_table;

        if ($model->hasBehavior('Workflow')) {
            $workflow = $this->getWorkflow($this->config('model'), $entity);
            if (!empty($workflow)) {
                $workflowId = $workflow->id;
                $workflowStep = $this->WorkflowSteps
                    ->find()
                    ->where([
                        $this->WorkflowSteps->aliasField('workflow_id') => $workflowId,
                        $this->WorkflowSteps->aliasField('category') => 1  // Open
                    ])
                    ->first();
                $statusId = $workflowStep->id;

                $entity->status_id = $statusId;
            }
        }
    }

    public function autoAssignAssignee(Entity $entity)
    {
        $stepId = $entity->status_id;

        $workflowStepEntity = $this->WorkflowSteps
            ->find()
            ->matching('Workflows.WorkflowModels')
            ->where([$this->WorkflowSteps->aliasField('id') => $stepId])
            ->first();
        $workflowModelEntity = $workflowStepEntity->_matchingData['WorkflowModels'];

        $isSchoolBased = $workflowModelEntity->is_school_based;
        $category = $workflowStepEntity->category;
        $createdUserId = $entity->created_user_id;

        $params = [
            'is_school_based' => $isSchoolBased,
            'workflow_step_id' => $stepId,
            'category' => $category,
            'created_user_id' => $createdUserId
        ];

        if ($entity->has('institution_id')) {
            $params['institution_id'] = $entity->institution_id;
        }

        $event = $this->_table->dispatchEvent('Workflow.onSetCustomAssigneeParams', [$entity, $params], $this);
        if ($event->result) {
            $params = $event->result;
        }

        $SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
        $assigneeId = $SecurityGroupUsers->getFirstAssignee($params);

        $entity->assignee_id = $assigneeId;
    }

    public function setAssigneeId(Entity $entity, $requestData)
    {
        $model = $this->_table;
        if ($model->hasBehavior('Workflow')) {
            if (array_key_exists($this->WorkflowTransitions->alias(), $requestData)) {
                if (array_key_exists('assignee_id', $requestData[$this->WorkflowTransitions->alias()]) && !empty($requestData[$this->WorkflowTransitions->alias()]['assignee_id'])) {
                    $assigneeId = $requestData[$this->WorkflowTransitions->alias()]['assignee_id'];
                    if ($assigneeId == self::AUTO_ASSIGN) {
                        $this->autoAssignAssignee($entity);
                    } else {
                        $entity->assignee_id = $assigneeId;
                    }
                } else {
                    $entity->assignee_id = 0;
                }

                // change to save instead of update all to trigger after save function.
                $model->save($entity);
            }
        }
    }

    public function setStatusId(Entity $entity, $requestData)
    {
        $model = $this->_table;
        if ($model->hasBehavior('Workflow')) {
            if (array_key_exists($this->WorkflowTransitions->alias(), $requestData)) {
                if (array_key_exists('workflow_step_id', $requestData[$this->WorkflowTransitions->alias()])) {
                    $statusId = $requestData[$this->WorkflowTransitions->alias()]['workflow_step_id'];
                    if ($entity->has('status_id')) {
                        // change to save instead of update all to trigger after save function.
                        $entity->status_id = $statusId;
                        $model->save($entity);
                    }
                }
            }

            //POCOR-5677 starts
            if ($entity->has('status_id') && $entity->status_id == 95) {
                // update in institution_student_transfers table start and end date after change status open to pending approval
                $AcademicPeriods = TableRegistry::get('academic_periods');
                $AcademicData = $AcademicPeriods
                            ->find()
                            ->where([$AcademicPeriods->aliasField('id') => $entity->academic_period_id])
                            ->first();

                $entity->start_date = $AcademicData->start_year.'-01-01';
                $entity->end_date = $AcademicData->start_year.'-12-31';
                $model->save($entity);
            }
            //POCOR-5677 ends
        }
    }

    public function deleteWorkflowTransitions(Entity $entity)
    {
        $model = $this->_table;

        $workflowStep = $this->getWorkflowStep($entity);
        if (!is_null($workflowStep)) {
            // used to get correct workflow model for StaffTransferIn and StaffTransferOut
            $workflowModel = $workflowStep->_matchingData['WorkflowModels'];
        } else {
            $workflowModel = $this->WorkflowModels->find()->where([$this->WorkflowModels->aliasField('model') => $this->config('model')])->first();
        }

        $this->WorkflowTransitions->deleteAll([
            $this->WorkflowTransitions->aliasField('workflow_model_id') => $workflowModel->id,
            $this->WorkflowTransitions->aliasField('model_reference') => $entity->id
        ]);
    }

    public function workflowAfterTransition(Event $event, $id = null, $requestData)
    {
        // use find instead of get to cater for models with composite keys using a hash id
        $model = $this->_table;

        $entity = $model->find()->where([$model->aliasField('id') => $id])->first();
        $this->setStatusId($entity, $requestData);

        // get the latest entity after status is updated
        $entity = $model->find()->where([$model->aliasField('id') => $id])->first();
        $this->setAssigneeId($entity, $requestData);
    }

    public function processWorkflow()
    {
        $request = $this->_table->controller->request;
        if ($request->is(['post', 'put'])) {
            $requestData = $request->data;

            $subject = $this->config('model') == null ? $this->_table : TableRegistry::get($this->config('model'));
            // Trigger workflow before save event here
            $event = $subject->dispatchEvent('Workflow.beforeTransition', [$requestData], $subject);
            if ($event->isStopped()) {
                return $event->result;
            }
            // End

            // Insert into workflow_transitions.
            $entity = $this->WorkflowTransitions->newEntity($requestData, ['validate' => false]);
            $id = $entity->model_reference;

            if ($this->WorkflowTransitions->save($entity)) {
                $this->_table->controller->Alert->success('general.edit.success', ['reset' => true]);

                // Trigger workflow after save event here
                $event = $subject->dispatchEvent('Workflow.afterTransition', [$id, $requestData], $subject);
                if ($event->isStopped()) {
                    return $event->result;
                }
                // End

                // Trigger event here
                $workflowAction = $this->WorkflowActions->get($entity->workflow_action_id);

                if (!empty($workflowAction->event_key)) {
                    $eventKeys = explode(",", $workflowAction->event_key);

                    foreach ($eventKeys as $eventKey) {
                        $event = $subject->dispatchEvent($eventKey, [$id, $entity], $subject);
                        if ($event->isStopped()) {
                            return $event->result;
                        }
                    }
                }
                // End
            } else {
                $this->_table->controller->log($entity->errors(), 'debug');
                $this->_table->controller->Alert->error('general.edit.failed', ['reset' => true]);
            }
            // End

            // Redirect
            if ($this->isCAv4()) {
                $url = $this->_table->url('view');
            } else {
                $url = $this->_table->ControllerAction->url('view');
            }

            return $this->_table->controller->redirect($url);
            // End
        }
    }

    public function processReassign()
    {
        $model = $this->isCAv4() ? $this->_table : $this->_table->ControllerAction;
        $request = $model->controller->request;

        if ($request->is(['post', 'put'])) {
            $requestData = $request->data;

            $workflowModelEntity = $this->getWorkflowSetup($this->config('model'));

            $assigneeId = $requestData['assignee_id'];
            $entity = $model
                ->find()
                ->contain(['Assignees', 'Statuses'])
                ->where([
                    $model->aliasField('id') => $requestData['id']
                ])
                ->first();
            $requestDataComment = null;
            if (isset($requestData['comment'])) {
                $requestDataComment = $requestData['comment'];
            }
            $this->WorkflowTransitions->trackChanges($workflowModelEntity, $entity, $assigneeId, $requestDataComment);

            $entity->assignee_id = $assigneeId;
            $model->save($entity);

            $url = $model->url('view');
            return $this->_table->controller->redirect($url);
        }
    }

    public function getAssigneeEntity($userId)
    {
        $Users = TableRegistry::get('User.Users');

        $userEntity = $Users
            ->find()
            ->select([
                $Users->aliasField('id'),
                $Users->aliasField('openemis_no'),
                $Users->aliasField('first_name'),
                $Users->aliasField('middle_name'),
                $Users->aliasField('third_name'),
                $Users->aliasField('last_name'),
                $Users->aliasField('preferred_name')
            ])
            ->where([$Users->aliasField('id') => $userId])
            ->first();

        return $userEntity;
    }

    public function setFilterNotEditable(Entity $entity)
    {
        $model = $this->_table;

        if ($model->action == 'edit') {
            $WorkflowModels = TableRegistry::get('Workflow.WorkflowModels');
            $results = $WorkflowModels
                ->find()
                ->where([$WorkflowModels->aliasField('model') => $this->config('model')])
                ->first();

            if (!empty($results) && !empty($results->filter)) {
                $filterAlias = $results->filter;
                $modelAlias = $this->config('model');

                $filterKey = $this->getFilterKey($filterAlias, $this->config('model'));
                if (empty($filterKey)) {
                    list($modelplugin, $modelAlias) = explode('.', $filterAlias, 2);
                    $filterKey = Inflector::underscore(Inflector::singularize($modelAlias)) . '_id';
                }

                $filterId = $entity->{$filterKey};
                $filterName = TableRegistry::get($filterAlias)->get($filterId)->name;

                $model->fields[$filterKey]['type'] = 'readonly';
                $model->fields[$filterKey]['value'] = $filterId;
                $model->fields[$filterKey]['attr']['value'] = $filterName;
            }
        }
    }

    private function getFilterKey($filterAlias, $modelAlias)
    {
        $filterKey = '';
        $associations = TableRegistry::get($filterAlias)->associations();
        foreach ($associations as $assoc) {
            if ($assoc->registryAlias() == $modelAlias) {
                $filterKey = $assoc->foreignKey();
                return $filterKey;
            }
        }
        return $filterKey;
    }

    public function getPendingRecords(Event $event, $params = [])
    {
        $institutionKey = $this->config('institution_key');
        $model = $this->_table;
        $doneStatus = WorkflowSteps::DONE;
        $institutionId = $params['institution_id'];

        $count = $model
            ->find()
             ->matching('Statuses', function ($q) use ($doneStatus) {
                return $q->where(['category <> ' => $doneStatus]);
             })
            ->where([
                $model->aliasField($institutionKey) => $institutionId
            ])
            ->count();

        return $count;
    }

    private function checkIfCanAddButtons($isSchoolBased, Entity $entity)
    {
        $canAddButtons = true;

        if ($isSchoolBased) {
            $isActive = true;
            if ($entity->has('institution_id')) {
                $Institutions = TableRegistry::get('Institution.Institutions');
                $institutionId = $entity->institution_id;
                $isActive = $Institutions->isActive($institutionId);
            }

            if (!$isActive) {
                $canAddButtons = false;
            }
        }

        // check additional conditions to show buttons
        $event = $this->_table->dispatchEvent('Workflow.checkIfCanAddButtons', [$entity], $this);
        if (is_bool($event->result)) {
            $canAddButtons = $event->result;
        }

        return $canAddButtons;
    }

    public function getWorkflowStepsParamValue($workflowStepId, $name)
    {
        $value = $this->WorkflowStepsParams->find()
            ->where([
                $this->WorkflowStepsParams->aliasField('workflow_step_id') => $workflowStepId,
                $this->WorkflowStepsParams->aliasField('name') => $name
            ])
            ->extract('value')
            ->first();
        return $value;
    }
}
