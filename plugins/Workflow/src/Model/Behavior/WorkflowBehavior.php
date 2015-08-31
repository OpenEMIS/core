<?php
namespace Workflow\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Utility\Inflector;

class WorkflowBehavior extends Behavior {
    protected $_defaultConfig = [
        'models' => [
            'WorkflowModels' => 'Workflow.WorkflowModels',
            'Workflows' => 'Workflow.Workflows',
            'WorkflowsFilters' => 'Workflow.WorkflowsFilters',
            'WorkflowSteps' => 'Workflow.WorkflowSteps',
            'WorkflowActions' => 'Workflow.WorkflowActions',
            'WorkflowRecords' => 'Workflow.WorkflowRecords',
            'WorkflowComments' => 'Workflow.WorkflowComments',
            'WorkflowTransitions' => 'Workflow.WorkflowTransitions'
        ],
        'setup' => null
    ];

    private $modelReference;
    private $workflow;
    private $workflowRecord;

	public function initialize(array $config) {
        parent::initialize($config);
        $models = $this->config('models');
        foreach ($models as $key => $model) {
            if (!is_null($model)) {
                $this->{$key} = TableRegistry::get($model);
                $this->{lcfirst($key).'Key'} = Inflector::underscore(Inflector::singularize($this->{$key}->alias())) . '_id';
            } else {
                $this->{$key} = null;
            }
        }
	}

	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	// priority has to be set at 100 so that onUpdateToolbarButtons in model will be triggered first
        $events['ControllerAction.Model.view.beforeAction'] = ['callable' => 'viewBeforeAction', 'priority' => 100];
    	$events['ControllerAction.Model.view.afterAction'] = ['callable' => 'viewAfterAction', 'priority' => 101];
        $events['Model.custom.onUpdateToolbarButtons'] = ['callable' => 'onUpdateToolbarButtons', 'priority' => 102];
    	return $events;
    }

    public function viewBeforeAction(Event $event) {
        $request = $this->_table->controller->request;
        if ($request->is(['post', 'put'])) {
            $requestData = $request->data;
            $submit = isset($request->data['submit']) ? $request->data['submit'] : 'save';
        }
    }

    public function viewAfterAction(Event $event, Entity $entity) {
        $setup = $this->config('setup');

        if (!is_null($setup)) {
            // Find all Workflow setup for the model
            $workflowIds = $this->Workflows
                ->find('list', ['keyField' => 'id', 'valueField' => 'id'])
                ->where([
                    $this->Workflows->aliasField('workflow_model_id') => $setup->id
                ])
                ->toArray();

            // Filter key
            $modelInfo = explode('.', $setup->filter);
            $base = count($modelInfo) == 1 ? $modelInfo[0] : $modelInfo[1];
            $filterKey = Inflector::underscore(Inflector::singularize($base)) . '_id';

            if (isset($entity->$filterKey)) {
                $filterId = $entity->$filterKey;

                $workflowFilterResults = $this->WorkflowsFilters
                    ->find()
                    ->where([
                        $this->WorkflowsFilters->aliasField('workflow_id IN') => $workflowIds,
                        $this->WorkflowsFilters->aliasField('filter_id') => $filterId   // By Filter
                    ])
                    ->all();

                // Use Workflow with filter if found otherwise use Workflow that Apply To All
                if ($workflowFilterResults->isEmpty()) {
                    $workflowResults = $this->WorkflowsFilters
                        ->find()
                        ->where([
                            $this->WorkflowsFilters->aliasField('workflow_id IN') => $workflowIds,
                            $this->WorkflowsFilters->aliasField('filter_id') => 0   // Apply To All
                        ])
                        ->all();
                } else {
                    $workflowResults = $workflowFilterResults;
                }

                if (!$workflowResults->isEmpty()) {
                    $workflowId = $workflowResults->first()->workflow_id;
                    $this->modelReference = $entity->id;
                    $this->workflow = $this->Workflows
                        ->find()
                        ->contain([
                            'WorkflowSteps.WorkflowActions'
                        ])
                        ->where([
                            $this->Workflows->aliasField('id') => $workflowId
                        ])
                        ->first();

                    $this->workflowRecord = $this->getRecord();

                    // Workflow Status - extra field
                    $status = isset($this->workflowRecord->workflow_step->name) ? $this->workflowRecord->workflow_step->name : __('Open');
                    $this->_table->ControllerAction->field('workflow_status', [
                        'type' => 'element',
                        'element' => 'Workflow.status',
                        'valueClass' => 'table-full-width',
                        'attr' => [
                            'label' => __('Status'),
                            'status' => $status
                        ]
                    ]);
                    // End

                    // Workflow Transitions - extra field
                    $tableHeaders[] = __('Transition') . '<i class="fa fa-history fa-lg"></i>';
                    $tableHeaders[] = __('Action') . '<i class="fa fa-ellipsis-h fa-2x"></i>';
                    $tableHeaders[] = __('Comment') . '<i class="fa fa-comments fa-lg"></i>';
                    $tableHeaders[] = __('Last Executer') . '<i class="fa fa-user fa-lg"></i>';
                    $tableHeaders[] = __('Last Execution Date') . '<i class="fa fa-calendar fa-lg"></i>';
                    $tableCells = [];
                    $this->_table->ControllerAction->field('workflow_transitions', [
                        'type' => 'element',
                        'element' => 'Workflow.transitions',
                        'element' => 'Workflow.transitions',
                        'override' => true,
                        'rowClass' => 'transition-container',
                        'tableHeaders' => $tableHeaders,
                        'tableCells' => $tableCells
                    ]);
                    // End

                    // Reset field order
                    $fields = $this->_table->fields;
                    $fieldOrder = ['workflow_status'];  // Set workflow_status to first
                    foreach ($fields as $fieldKey => $fieldAttr) {
                        if (!in_array($fieldKey, ['workflow_status', 'workflow_transitions'])) {
                            $fieldOrder[] = $fieldKey;
                        }
                    }
                    $fieldOrder[] = 'workflow_transitions';  // Set workflow_transitions to last
                    $this->_table->ControllerAction->setFieldOrder($fieldOrder);
                    // End
                } else {
                    // Workflow not configured
                }
            }
        }
    }

    public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
        if ($toolbarButtons->offsetExists('edit')) {
            unset($toolbarButtons['edit']);
        }

        $workflowStepId = $this->workflowRecord->workflow_step->id;
        $workflowStep = $this->WorkflowSteps
            ->find()
            ->contain(['WorkflowActions' => function ($q) {
                    return $q
                        ->find('visible');
                }
            ])
            ->where([
                $this->WorkflowSteps->aliasField('id') => $workflowStepId
            ])
            ->first();

        $actionButtons = [];
        foreach ($workflowStep->workflow_actions as $actionKey => $actionObj) {
            $action = $actionObj->action;
            $button = [
                'id' => $actionObj->id,
                'text' => $actionObj->name,
                'value' => $actionObj->next_workflow_step_id,
                'comment_required' => $actionObj->comment_required,
                // 'action' => $actionObj->action
            ];
            $buttonAttr = [
                // ' . json_encode($button) . '
                'onclick' => 'Workflow.init();Workflow.copy();return false;',
                'data-toggle' => 'modal',
                'data-target' => '#transitionComment'
            ];
            $buttonAttr = array_merge($attr, $buttonAttr);

            if (is_null($action)) {
                $actionButtons[] = $button;
                // Temporary
                // $actionButton['type'] = 'button';
                // $actionButton['label'] = $actionObj->name;
                // $actionButton['url'] = '#';
                // $actionButton['attr'] = $buttonAttr;
                // $actionButton['attr']['title'] = __($actionObj->name);

                // $toolbarButtons[$actionKey] = $actionButton;
                // End
            } else {
                if ($action == 0) { // Approve
                    $approveButton = [];
                    $approveButton['type'] = 'button';
                    $approveButton['label'] = '<i class="fa kd-approve"></i>';
                    $approveButton['url'] = '#';
                    $approveButton['attr'] = $buttonAttr;
                    $approveButton['attr']['title'] = __($actionObj->name);

                    $toolbarButtons['approve'] = $approveButton;
                } else if ($action == 1) { // Reject
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

        // More Actions
        if (!empty($actionButtons)) {
            $moreButton = [];
            $moreButton['type'] = 'element';
            $moreButton['element'] = 'Workflow.buttons';
            $moreButton['data'] = [
                'buttons' => $actionButtons
            ];
            $moreButton['options'] = [];

            // $toolbarButtons['more'] = $moreButton;
        }
        // End

        // Modal
        $alias = $this->WorkflowTransitions->alias();
        $fields = [
            $alias.'.prev_workflow_step_id' => [
                'type' => 'hidden',
                'value' => ''
            ],
            $alias.'.workflow_step_id' => [
                'type' => 'hidden',
                'value' => ''
            ],
            $alias.'.workflow_action_id' => [
                'type' => 'hidden',
                'value' => ''
            ],
            $alias.'.workflow_record_id' => [
                'type' => 'hidden',
                'value' => ''
            ],
            $alias.'.comment_required' => [
                'type' => 'hidden',
                'value' => '0'
            ]
        ];

        $content = 'Action Name:<BR><BR>Comment:';

        $modal = [
            'id' => 'transitionComment',
            'fields' => $fields,
            'title' => __('Add Comment'),
            'content' => $content,
            'formOptions' => [
                'type' => 'workflow',
                'url' => $this->_table->ControllerAction->url('view')
            ],
            'buttons' => [
                '<button type="submit" class="btn btn-default">' . __('Save') . '</button>'
            ]
        ];
        $this->_table->controller->set('modal', $modal);
        // End
    }

    public function getRecord() {
        $modelReference = $this->modelReference;
        $workflowId = $this->workflow->id;
        $workflowModelId = $this->workflow->workflow_model_id;
        $where = [
            $this->WorkflowRecords->aliasField('model_reference') => $modelReference,
            $this->WorkflowRecords->aliasField('workflow_model_id') => $workflowModelId
        ];

        $recordResults = $this->WorkflowRecords
            ->find()
            ->where($where)
            ->all();

        // Insert a new record if doesn't exists
        if ($recordResults->isEmpty()) {
            $workflowStep = $this->WorkflowSteps
                ->find()
                ->where([
                    $this->WorkflowSteps->aliasField('workflow_id') => $workflowId,
                    $this->WorkflowSteps->aliasField('stage') => 0  // Open
                ])
                ->first();

            $data = [
                'model_reference' => $modelReference,
                'workflow_model_id' => $workflowModelId,
                'workflow_step_id' => $workflowStep->id
            ];
            $entity = $this->WorkflowRecords->newEntity($data);
            if ($this->WorkflowRecords->save($entity)) {
            } else {
                $this->log($entity->errors(), 'debug');
            }
        }

        return $this->WorkflowRecords
            ->find()
            ->contain(['WorkflowModels', 'WorkflowSteps'])
            ->where($where)
            ->first();
    }
}
