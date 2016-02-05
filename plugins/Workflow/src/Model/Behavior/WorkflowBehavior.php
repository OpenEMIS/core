<?php
namespace Workflow\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\Utility\Inflector;

class WorkflowBehavior extends Behavior {
	protected $_defaultConfig = [
		'model' => null,
		'models' => [
			'WorkflowModels' => 'Workflow.WorkflowModels',
			'Workflows' => 'Workflow.Workflows',
			'WorkflowsFilters' => 'Workflow.WorkflowsFilters',
			'WorkflowSteps' => 'Workflow.WorkflowSteps',
			'WorkflowStepsRoles' => 'Workflow.WorkflowStepsRoles',
			'WorkflowActions' => 'Workflow.WorkflowActions',
			'WorkflowRecords' => 'Workflow.WorkflowRecords',
			'WorkflowComments' => 'Workflow.WorkflowComments',
			'WorkflowTransitions' => 'Workflow.WorkflowTransitions'
		]
	];

	private $controller;
	private $model = null;
	private $currentAction;

	private $attachWorkflow = false;	// indicate whether which action require workflow
	private $hasWorkflow = false;	// indicate whether workflow is setup
	private $workflowIds = null;

	private $workflowSetup = null;
	private $workflowRecord = null;

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

	private function isCAv4() {
		return isset($this->_table->CAVersion) && $this->_table->CAVersion=='4.0';
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		// priority has to be set at 1000 so that method(s) in model will be triggered first
		// priority of indexBeforeAction and indexBeforePaginate is set to 1 for it to run first before the event in model
		$events['ControllerAction.Model.beforeAction'] 			= ['callable' => 'beforeAction', 'priority' => 1000];
		$events['ControllerAction.Model.afterAction'] 			= ['callable' => 'afterAction', 'priority' => 1];
		$events['ControllerAction.Model.index.beforeAction'] 	= ['callable' => 'indexBeforeAction', 'priority' => 1];
		if ($this->isCAv4()) {
			$events['ControllerAction.Model.index.beforeQuery'] 	= ['callable' => 'indexBeforeQuery', 'priority' => 1];
			$events['ControllerAction.Model.processWorkflow'] 	= ['callable' => 'processWorkflow', 'priority' => 5];
		} else {
			$events['ControllerAction.Model.index.beforePaginate'] 	= ['callable' => 'indexBeforePaginate', 'priority' => 1];
		}
		$events['ControllerAction.Model.index.afterAction'] 	= ['callable' => 'indexAfterAction', 'priority' => 1000];
		$events['ControllerAction.Model.view.afterAction'] 		= ['callable' => 'viewAfterAction', 'priority' => 1000];
		$events['ControllerAction.Model.addEdit.beforeAction'] 		= ['callable' => 'addEditBeforeAction', 'priority' => 1];
		$events['Model.custom.onUpdateToolbarButtons'] 			= ['callable' => 'onUpdateToolbarButtons', 'priority' => 1000];
		$events['Model.custom.onUpdateActionButtons'] 			= ['callable' => 'onUpdateActionButtons', 'priority' => 1000];
		$events['Workflow.afterTransition'] = 'workflowAfterTransition';
		return $events;
	}

	public function afterSave(Event $event, Entity $entity, ArrayObject $options) {
		$this->setStatusId($entity);
	}

	public function afterDelete(Event $event, Entity $entity, ArrayObject $options) {
		// To delete from records and transitions table
		if ($this->attachWorkflow) {
			$workflowRecord = $this->getRecord($this->_table->registryAlias(), $entity);
			if (!empty($workflowRecord)) {
				$workflowRecord = $this->WorkflowRecords->get($workflowRecord->id);
				$this->WorkflowRecords->delete($workflowRecord);
			}
		}
	}

	public function onGetStatusId(Event $event, Entity $entity) {
		return '<span class="status highlight">' . $entity->status->name . '</span>';
	}

	public function onGetWorkflowStatus(Event $event, Entity $entity) {
		return '<span class="status highlight">' . $entity->workflow_status . '</span>';
	}

	public function beforeAction(Event $event) {
		// Initialize workflow
		$this->controller = $this->_table->controller;
		$this->model = $this->isCAv4() ? $this->_table : $this->controller->ControllerAction->model();
		$this->currentAction = $this->isCAv4() ? $this->_table->action : $this->controller->ControllerAction->action();

		if (!is_null($this->model) && in_array($this->currentAction, ['index', 'view', 'remove', 'processWorkflow'])) {
			$this->attachWorkflow = true;
			$this->controller->Workflow->attachWorkflow = $this->attachWorkflow;
		}
	}

	public function afterAction(Event $event) {
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

	public function indexBeforeAction(Event $event) {
		$WorkflowModels = $this->WorkflowModels;
		$registryAlias = $this->_table->registryAlias();

		// Find from workflows table
		$results = $this->Workflows
			->find('list', ['keyField' => 'id', 'valueField' => 'id'])
			->matching('WorkflowModels', function($q) use ($WorkflowModels, $registryAlias) {
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

			$workflowId = 0;
			if (!empty($filter)) {
				// Wofkflow Filter Options
				$filterOptions = TableRegistry::get($filter)->getList()->toArray();

				// Trigger event to get the correct wofkflow filter options
				$subject = TableRegistry::get($model);
				$newEvent = $subject->dispatchEvent('Workflow.getFilterOptions', null, $subject);
				if ($newEvent->isStopped()) { return $newEvent->result; }
				if (!empty($newEvent->result)) {
					$filterOptions = $newEvent->result;
				}
				// End

				$filterOptions = ['-1' => '-- ' . __('Select') . ' --'] + $filterOptions;
				$selectedFilter = $this->_table->queryString('filter', $filterOptions);
				$this->_table->advancedSelectOptions($filterOptions, $selectedFilter);
				$this->_table->controller->set(compact('filterOptions', 'selectedFilter'));
				// End

				// Set Workflow Id
				if ($selectedFilter != -1) {
					$workflow = $this->getWorkflow($registryAlias, null, $selectedFilter);
					if (!empty($workflow)) {
						$workflowId = $workflow->id;
					}
				}
				// End
			} else {
				$workflow = $this->getWorkflow($registryAlias, null, $selectedFilter);
				if (!empty($workflow)) {
					$workflowId = $workflow->id;
				}
			}

			// Status Options
			if (!empty($workflowId)) {
				$statusQuery = $this->WorkflowSteps
					->find('list')
					->where([
						$this->WorkflowSteps->aliasField('workflow_id') => $workflowId
					]);

				$statusOptions = $statusQuery->toArray();
				$statusOptions = ['-1' => '-- ' . __('All Statuses') . ' --'] + $statusOptions;
				$selectedStatus = $this->_table->queryString('status', $statusOptions);
				$this->_table->advancedSelectOptions($statusOptions, $selectedStatus);
				$this->_table->controller->set(compact('statusOptions', 'selectedStatus'));
			}
			// End
		}
	}

	public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
		$options = $this->isCAv4() ? $extra['options'] : $extra;

		$registryAlias = $this->_table->registryAlias();
		$workflowModel = $this->getWorkflowSetup($registryAlias);

		$filter = $workflowModel->filter;

		$selectedStatus = null;
		if (!empty($filter)) {
			$selectedFilter = $this->_table->ControllerAction->getVar('selectedFilter');

			// Filter key
			list(, $base) = pluginSplit($filter);
			$filterKey = Inflector::underscore(Inflector::singularize($base)) . '_id';
			if ($selectedFilter != -1) {
				$query->where([
					$this->_table->aliasField($filterKey) => $selectedFilter
				]);

				$selectedStatus = $this->_table->ControllerAction->getVar('selectedStatus');
			}
		} else {
			$selectedStatus = $this->_table->ControllerAction->getVar('selectedStatus');
		}

		if (!is_null($selectedStatus) && $selectedStatus != -1) {
			$query->where([
				$this->_table->aliasField('status_id') => $selectedStatus
			]);
		}

		if ($this->isCAv4()) { $extra['options'] = $options; }
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$this->indexBeforeQuery($event, $query, $options);
	}

	public function indexAfterAction(Event $event, $data) {
		$this->reorderFields();
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$model = $this->isCAv4() ? $this->_table : $this->_table->ControllerAction;

		// setup workflow
		if ($this->attachWorkflow) {
			$this->workflowRecord = $this->getRecord($this->config('model'), $entity);
			if (!empty($this->workflowRecord)) {
				$model->field('status_id', ['visible' => false]);

				// Workflow Status - extra field
				$status = isset($this->workflowRecord->workflow_step->name) ? $this->workflowRecord->workflow_step->name : __('Open');
				$entity->workflow_status = $status;
				$model->field('workflow_status', ['attr' => ['label' => __('Status')]]);
				// End

				// Workflow Transitions - extra field
				$tableHeaders[] = __('Transition') . '<i class="fa fa-history fa-lg"></i>';
				$tableHeaders[] = __('Action') . '<i class="fa fa-ellipsis-h fa-2x"></i>';
				$tableHeaders[] = __('Comment') . '<i class="fa fa-comments fa-lg"></i>';
				$tableHeaders[] = __('Last Executer') . '<i class="fa fa-user fa-lg"></i>';
				$tableHeaders[] = __('Last Execution Date') . '<i class="fa fa-calendar fa-lg"></i>';

				$tableCells = [];
				$transitionResults = $this->WorkflowTransitions
					->find()
					->contain(['ModifiedUser', 'CreatedUser'])
					->where([
						$this->WorkflowTransitions->aliasField('workflow_record_id') => $this->workflowRecord->id
					])
					->order([
						$this->WorkflowTransitions->aliasField('created ASC')
					])
					->all();
				if (!$transitionResults->isEmpty()) {
					$transitions = $transitionResults->toArray();
					foreach ($transitions as $key => $transition) {
						$transitionDisplay = '<span class="status past">' . $transition->prev_workflow_step_name . '</span>';
						$transitionDisplay .= '<span class="transition-arrow"></span>';
						if (count($transitions) - 1 == $key) {
							$transitionDisplay .= '<span class="status highlight">' . $transition->workflow_step_name . '</span>';
						} else {
							$transitionDisplay .= '<span class="status past">' . $transition->workflow_step_name . '</span>';
						}

						$rowData = [];
						$rowData[] = $transitionDisplay;
						$rowData[] = $transition->workflow_action_name;
						$rowData[] = nl2br($transition->comment);
						$rowData[] = $transition->created_user->name;
						$rowData[] = $transition->created->format('Y-m-d H:i:s');

						$tableCells[$key] = $rowData;
					}
				}

				$model->field('workflow_transitions', [
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
				$fields = $this->_table->fields;
				foreach ($fields as $fieldKey => $fieldAttr) {
					if (!in_array($fieldKey, ['workflow_status', 'workflow_transitions'])) {
						$fieldOrder[$fieldAttr['order']] = $fieldKey;
					}
				}
				ksort($fieldOrder);
				array_unshift($fieldOrder, 'workflow_status');	// Set workflow_status to first
				$fieldOrder[] = 'workflow_transitions';	// Set workflow_transitions to last
				$model->setFieldOrder($fieldOrder);
				// End
			} else {
				// Workflow is not configured
			}
		}
	}

	public function addEditBeforeAction(Event $event) {
		$model = $this->isCAv4() ? $this->_table : $this->_table->ControllerAction;
		$model->field('status_id');
	}

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		$this->setToolbarButtons($toolbarButtons, $attr, $action);
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		// check line by line, whether to show / hide the action buttons
		if ($this->attachWorkflow) {
			$model = $this->_table;
			if (!$model->AccessControl->isAdmin()) {
				$buttons = $model->onUpdateActionButtons($event, $entity, $buttons);

				$workflowRecord = $this->getRecord($this->config('model'), $entity);
				if (!empty($workflowRecord)) {
					$isEditable = false;
					$isRemovable = false;
					$workflowStep = $this->getWorkflowStep($workflowRecord);
					if (!empty($workflowStep)) {
						$isEditable = $workflowStep->is_editable == 1 ? true : false;
						$isRemovable = $workflowStep->is_removable == 1 ? true : false;
					}

					if (array_key_exists('edit', $buttons) && !$isEditable) {
						unset($buttons['edit']);
					}
					if (array_key_exists('remove', $buttons) && !$isRemovable) {
						unset($buttons['remove']);
					}
				} else {
					// Workflow is not configured
				}

				return $buttons;
			}
		}
	}

	public function onUpdateFieldStatusId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'index') {
			$attr['type'] = 'select';
		} else if ($action == 'add' || $action == 'edit') {
			$attr['type'] = 'hidden';
			$attr['value'] = 0;
		}

		return $attr;
	}

	public function reorderFields() {
		$fieldOrder = [];
		$fields = $this->_table->fields;
		foreach ($fields as $fieldKey => $fieldAttr) {
			if (!in_array($fieldKey, ['status_id'])) {
				$fieldOrder[$fieldAttr['order']] = $fieldKey;
			}
		}
		ksort($fieldOrder);
		array_unshift($fieldOrder, 'status_id');	// Set Status to first
		if ($this->isCAv4()) {
			$this->_table->setFieldOrder($fieldOrder);
		} else {
			$this->_table->ControllerAction->setFieldOrder($fieldOrder);
		}
	}

	public function getWorkflowSetup($registryAlias) {
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

	public function getWorkflow($registryAlias, $entity=null, $filterId=null) {
		$workflowModel = $this->getWorkflowSetup($registryAlias);

		if (!empty($workflowModel)) {
			// Find all Workflow setup for the model
			$workflowIds = $this->Workflows
				->find('list', ['keyField' => 'id', 'valueField' => 'id'])
				->where([
					$this->Workflows->aliasField('workflow_model_id') => $workflowModel->id
				])
				->toArray();

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
						$filterId = $entity->$filterKey;
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

	public function getRecord($registryAlias, $entity=null) {
		$workflow = $this->getWorkflow($registryAlias, $entity);
		if (!empty($workflow)) {
			$workflowId = $workflow->id;
			$workflowModelId = $workflow->workflow_model_id;
			$where = [
				$this->WorkflowRecords->aliasField('model_reference') => $entity->id,
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
					'model_reference' => $entity->id,
					'workflow_model_id' => $workflowModelId,
					'workflow_step_id' => $workflowStep->id
				];
				$entity = $this->WorkflowRecords->newEntity($data, ['validate' => false]);
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
		} else {
			return null;
		}
	}

	public function getWorkflowStep($workflowRecord) {
		if (!empty($workflowRecord)) {
			$workflowRecordId = $workflowRecord->id;  // Current Workflow Record
			$workflowStepId = $workflowRecord->workflow_step_id; // Latest Workflow Step

			$query = $this->WorkflowSteps
				->find()
				->contain(['WorkflowActions' => function ($q) {
						return $q
							->find('visible')
							->where(['next_workflow_step_id !=' => 0]);
					}
				])
				->contain('WorkflowActions.NextWorkflowSteps')
				->where([
					$this->WorkflowSteps->aliasField('id') => $workflowStepId
				]);

			if (!$this->_table->AccessControl->isAdmin()) {
				$roles = $this->_table->AccessControl->getRolesByUser()->toArray();
				$roleIds = [];
				foreach ($roles as $key => $role) {
					$roleIds[$role->security_role_id] = $role->security_role_id;
				}

				$query
					->innerJoin(
						[$this->WorkflowStepsRoles->alias() => $this->WorkflowStepsRoles->table()],
						[
							$this->WorkflowStepsRoles->aliasField('workflow_step_id = ') . $this->WorkflowSteps->aliasField('id'),
							$this->WorkflowStepsRoles->aliasField('security_role_id IN') => $roleIds
						]
					);
			}

			return $query->first();
		} else {
			return null;
		}
	}

	public function getModalOptions() {
		$record = $this->workflowRecord;  // Current Workflow Record
		$step = $this->workflowRecord->workflow_step; // Latest Workflow Step

		$alias = $this->WorkflowTransitions->alias();
		// workflow_step_id is needed for afterSave logic in WorkflowTransitions
		$fields = [
			$alias.'.prev_workflow_step_id' => [
				'type' => 'hidden',
				'value' => $step->id,
			],
			$alias.'.prev_workflow_step_name' => [
				'type' => 'hidden',
				'value' => $step->name
			],
			$alias.'.workflow_step_id' => [
				'type' => 'hidden',
				'value' => 0,
				'class' => 'workflowtransition-step-id'
			],
			$alias.'.workflow_step_name' => [
				'type' => 'hidden',
				'value' => '',
				'class' => 'workflowtransition-step-name'
			],
			$alias.'.workflow_action_id' => [
				'type' => 'hidden',
				'value' => 0,
				'class' => 'workflowtransition-action-id'
			],
			$alias.'.workflow_action_name' => [
				'type' => 'hidden',
				'value' => '',
				'class' => 'workflowtransition-action-name'
			],
			$alias.'.workflow_record_id' => [
				'type' => 'hidden',
				'value' => $record->id
			],
			$alias.'.comment_required' => [
				'type' => 'hidden',
				'value' => 0,
				'class' => 'workflowtransition-comment-required'
			]
		];

		$content = '';
		$content = '<style type="text/css">.modal-footer { clear: both; } .modal-body textarea { width: 60%; }</style>';
		$content .= '<div class="input string"><label>'.__('Action').'</label><input name="WorkflowTransitions[action_name]" maxlength="250" value="" type="string" class="workflowtransition-action-name" readonly="readonly" disabled="disabled"></div>';
		$content .= '<BR><BR>';
		$content .= '<div class="input string"><label>'.__('Next Step').'</label><input name="WorkflowTransitions[step_name]" maxlength="250" value="" type="string" class="workflowtransition-step-name" readonly="readonly" disabled="disabled"></div>';
		$content .= '<BR><BR>';
		$content .= '<div class="input textarea"><label>'.__('Comment').'</label><textarea name="WorkflowTransitions[comment]" rows="5" class="workflowtransition-comment"></textarea></div>';
		$content .= '<div class="input string"><span class="button-label"></span><div class="workflowtransition-comment-error error-message">' . __('This field cannot be left empty') . '</div></div>';

		$buttons = [
			'<button type="submit" class="btn btn-default" onclick="return Workflow.onSubmit();">' . __('Save') . '</button>'
		];

		$modal = [
			'id' => 'workflowTansition',
			'fields' => $fields,
			'title' => __('Add Comment'),
			'content' => $content,
			'formOptions' => [
				'class' => 'form-horizontal',
				'url' => $this->isCAv4() ? $this->_table->url('processWorkflow') : $this->_table->ControllerAction->url('processWorkflow')
			],
			'buttons' => $buttons
		];

		return $modal;
	}

	public function getWorkflowStepList() {
		$steps = [];

		$query = $this->WorkflowSteps
			->find('list');

		if (!$this->_table->AccessControl->isAdmin()) {
			$roles = $this->_table->AccessControl->getRolesByUser()->toArray();
			$roleIds = [];
			foreach ($roles as $key => $role) {
				$roleIds[$role->security_role_id] = $role->security_role_id;
			}

			$WorkflowStepsRoles = $this->WorkflowStepsRoles;
			$query->innerJoin(
				[$this->WorkflowStepsRoles->alias() => $this->WorkflowStepsRoles->table()],
				[
					$this->WorkflowStepsRoles->aliasField('workflow_step_id = ') . $this->WorkflowSteps->aliasField('id'),
					$this->WorkflowStepsRoles->aliasField('security_role_id IN') => $roleIds
				]
			);
		}

		if (!empty($this->workflowIds)) {
			$query->where([
				$this->WorkflowSteps->aliasField('workflow_id IN') => $this->workflowIds
			]);
		}

		$steps = $query->toArray();

		return $steps;
	}

	public function setToolbarButtons(ArrayObject $toolbarButtons, array $attr, $action) {
		// Unset edit buttons and add action buttons
		if ($this->attachWorkflow) {
			$isEditable = false;

			if (is_null($this->workflowRecord)) {
				// In index page, unset add buttons if Workflows is not configured
				if ($action == 'index') {
					if ($this->hasWorkflow == false && $toolbarButtons->offsetExists('add')) {
						unset($toolbarButtons['add']);
					}
				}
			} else {
				$workflowStep = $this->getWorkflowStep($this->workflowRecord);

				$actionButtons = [];
				if (!empty($workflowStep)) {
					// Enabled edit button only when login user in approval role for the step and that step is editable
					if ($workflowStep->is_editable == 1) {
						$isEditable = true;
					}
					// End

					foreach ($workflowStep->workflow_actions as $actionKey => $actionObj) {
						$actionType = $actionObj->action;
						$button = [
							'id' => $actionObj->id,
							'name' => $actionObj->name,
							'next_step_id' => $actionObj->next_workflow_step_id,
							'next_step_name' => $actionObj->next_workflow_step->name,
							'comment_required' => $actionObj->comment_required
						];
						$json = json_encode($button, JSON_NUMERIC_CHECK);

						$buttonAttr = [
							'escapeTitle' => false,
							'escape' => true,
							'onclick' => 'Workflow.init();Workflow.copy('.$json.');return false;',
							'data-toggle' => 'modal',
							'data-target' => '#workflowTansition'
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
								$approveButton['url'] = '#';
								$approveButton['attr'] = $buttonAttr;
								$approveButton['attr']['title'] = __($actionObj->name);

								$toolbarButtons['approve'] = $approveButton;
							} else if ($actionType == 1) { // Reject
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

				if (!$this->_table->AccessControl->isAdmin() && $toolbarButtons->offsetExists('edit') && !$isEditable) {
					unset($toolbarButtons['edit']);
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
				$modal = $this->getModalOptions();
				$this->_table->controller->set('modal', $modal);
				// End
			}
		}
	}

	public function setStatusId(Entity $entity) {
		if($this->_table->hasBehavior('Workflow')) {
			$workflowRecord = $this->getRecord($this->_table->registryAlias(), $entity);
			if (!empty($workflowRecord)) {
				if ($entity->has('status_id')) {
					$this->_table->updateAll(
						['status_id' => $workflowRecord->workflow_step_id],
						['id' => $entity->id]
					);
				}
			}
		}
	}

	public function workflowAfterTransition(Event $event, $id=null) {
		$entity = $this->_table->get($id);
		$this->setStatusId($entity);
	}

	public function processWorkflow() {
		$request = $this->_table->controller->request;
		if ($request->is(['post', 'put'])) {
			$requestData = $request->data;

			// Insert into workflow_transitions.
			$entity = $this->WorkflowTransitions->newEntity($requestData, ['validate' => false]);
			if ($this->WorkflowTransitions->save($entity)) {
				$this->_table->controller->Alert->success('general.edit.success', ['reset' => true]);

				$subject = $this->_table;
				$workflowRecord = $this->WorkflowRecords->get($entity->workflow_record_id);
				$id = $workflowRecord->model_reference;

				// Trigger workflow after save event here
				$event = $subject->dispatchEvent('Workflow.afterTransition', [$id, $entity], $subject);
				if ($event->isStopped()) { return $event->result; }
				// End

				// Trigger event here
				$workflowAction = $this->WorkflowActions->get($entity->workflow_action_id);

				if (!empty($workflowAction->event_key)) {
					$eventKey = $workflowAction->event_key;

					$event = $subject->dispatchEvent($eventKey, [$id, $entity], $subject);
					if ($event->isStopped()) { return $event->result; }
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
}
