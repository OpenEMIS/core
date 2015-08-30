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
    private $workflowId;
    private $workflowModelId;

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
    	$events['Model.custom.onUpdateToolbarButtons'] = ['callable' => 'onUpdateToolbarButtons', 'priority' => 100];
    	$events['ControllerAction.Model.view.afterAction'] = ['callable' => 'viewAfterAction', 'priority' => 101];
    	return $events;
    }

    public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
    }

    public function viewAfterAction(Event $event, Entity $entity) {
        $setup = $this->config('setup');

        if (!is_null($setup)) {
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
                    $workflow = $this->Workflows
                        ->find()
                        ->contain([
                            'WorkflowSteps.WorkflowActions'
                        ])
                        ->where([
                            $this->Workflows->aliasField('id') => $workflowId
                        ])
                        ->first();

                    $this->modelReference = $entity->id;
                    $this->workflowId = $workflow->id;
                    $this->workflowModelId = $workflow->workflow_model_id;

                    // Workflow Status - extra field
                    $status = __('Open');
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
}
