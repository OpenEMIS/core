<?php
namespace Workflow\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
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
		],
		'workflowId' => null
	];

	private $controller;
	private $model = null;
	private $currentAction;
	private $attachWorkflow = false;

	private $modelReference = null;
	private $workflowId = null;
	private $workflow = null;
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

	public function implementedEvents() {
		$events = parent::implementedEvents();
		// priority has to be set at 100 so that method(s) in model will be triggered first
		$events['ControllerAction.Model.beforeAction'] = ['callable' => 'beforeAction', 'priority' => 100];
		$events['ControllerAction.Model.view.afterAction'] = ['callable' => 'viewAfterAction', 'priority' => 100];
		$events['Model.custom.onUpdateToolbarButtons'] = ['callable' => 'onUpdateToolbarButtons', 'priority' => 100];
		$events['Model.custom.onUpdateActionButtons'] = ['callable' => 'onUpdateActionButtons', 'priority' => 100];
		return $events;
	}

	public function afterDelete(Event $event, Entity $entity, ArrayObject $options) {
		// to delete from records and transitions table
	}

	public function onGetWorkflowStatus(Event $event, Entity $entity) {
		return '<span class="status highlight">' . $entity->workflow_status . '</span>';
	}

	public function beforeAction(Event $event) {
		$this->controller = $this->_table->controller;
		$this->model = $this->controller->ControllerAction->model();
		$this->currentAction = $this->controller->ControllerAction->action();
		$attachWorkflow = $this->controller->Workflow->attachWorkflow;

		if ($attachWorkflow) {
			if (!is_null($this->model)) {
				if (in_array($this->currentAction, ['index', 'view', 'processWorkflow'])) {
					$this->attachWorkflow = true;

					if ($this->currentAction == 'view') {
						$this->setupWorkflow();
					}
				}
			}
		}
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$workflowId = $this->workflowId;
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
		$entity->workflow_status = $status;
		$this->_table->ControllerAction->field('workflow_status', ['attr' => ['label' => __('Status')]]);
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
			->contain(['PreviousWorkflowSteps', 'WorkflowSteps', 'WorkflowActions', 'ModifiedUser', 'CreatedUser'])
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
				$transitionDisplay = '<span class="status past">' . $transition->previous_workflow_step->name . '</span>';
				$transitionDisplay .= '<span class="transition-arrow"></span>';
				if (count($transitions) - 1 == $key) {
					$transitionDisplay .= '<span class="status highlight">' . $transition->workflow_step->name . '</span>';
				} else {
					$transitionDisplay .= '<span class="status past">' . $transition->workflow_step->name . '</span>';
				}

				$rowData = [];
				$rowData[] = $transitionDisplay;
				$rowData[] = $transition->workflow_action->name;
				$rowData[] = nl2br($transition->comment);
				$rowData[] = $transition->created_user->name;
				$rowData[] = $transition->created->format('Y-m-d H:i:s');

				$tableCells[$key] = $rowData;
			}
		}

		$this->_table->ControllerAction->field('workflow_transitions', [
			'type' => 'element',
			'element' => 'Workflow.transitions',
			'override' => true,
			'rowClass' => 'transition-container',
			'tableHeaders' => $tableHeaders,
			'tableCells' => $tableCells
		]);
		// End

		// Reorder fields
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
	}

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		$isEditable = false;
		$workflowStep = null;
		if (!is_null($this->workflowRecord)) {
			$workflowStep = $this->getWorkflowStep();
		}

		$actionButtons = [];
		if (!empty($workflowStep)) {
			// Enabled edit button only when login user in approval role for the step and that step is editable
			if ($workflowStep->is_editable) {
				$isEditable = true;
			}
			// End

			foreach ($workflowStep->workflow_actions as $actionKey => $actionObj) {
				$action = $actionObj->action;
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

				if (is_null($action)) {
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
		}

		if (!$isEditable) {
			if ($toolbarButtons->offsetExists('edit')) {
				unset($toolbarButtons['edit']);
			}
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
		if (!is_null($this->workflowRecord)) {
			$modal = $this->getModalOptions();
			$this->_table->controller->set('modal', $modal);
		}
		// End
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		if ($this->attachWorkflow) {
			$buttons = $this->_table->onUpdateActionButtons($event, $entity, $buttons);

			return $buttons;
		}
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

	public function getWorkflowStep() {
		$workflowRecordId = $this->workflowRecord->id;  // Current Workflow Record
		$workflowStepId = $this->workflowRecord->workflow_step->id; // Latest Workflow Step

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

		$workflowStep = $query->first();

		return $workflowStep;
	}

	public function getModalOptions() {
		$workflowRecordId = $this->workflowRecord->id;  // Current Workflow Record
		$workflowStepId = $this->workflowRecord->workflow_step->id; // Latest Workflow Step

		$alias = $this->WorkflowTransitions->alias();
		$fields = [
			$alias.'.prev_workflow_step_id' => [
				'type' => 'hidden',
				'value' => $workflowStepId,
			],
			$alias.'.workflow_step_id' => [
				'type' => 'hidden',
				'value' => 0,
				'class' => 'workflowtransition-step-id'
			],
			$alias.'.workflow_action_id' => [
				'type' => 'hidden',
				'value' => 0,
				'class' => 'workflowtransition-action-id'
			],
			$alias.'.workflow_record_id' => [
				'type' => 'hidden',
				'value' => $workflowRecordId
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
				'url' => $this->_table->ControllerAction->url('processWorkflow')
			],
			'buttons' => $buttons
		];

		return $modal;
	}

	public function processWorkflow() {
		$request = $this->_table->controller->request;
		if ($request->is(['post', 'put'])) {
			$requestData = $request->data;

			// Insert into workflow_transitions.
			$entity = $this->WorkflowTransitions->newEntity($requestData);
			if ($this->WorkflowTransitions->save($entity)) {
				// Trigger event here
				$workflowAction = $this->WorkflowActions
					->find()
					->where([
						$this->WorkflowActions->aliasField('id') => $entity->workflow_action_id
					])
					->first();

				if (!empty($workflowAction->event_key)) {
					$eventKey = $workflowAction->event_key;
					$subject = $this->_table;

					$workflowRecord = $this->WorkflowRecords->get($entity->workflow_record_id);
					$id = $workflowRecord->model_reference;

					$event = $subject->dispatchEvent($eventKey, [$id, $entity], $subject);
					if ($event->isStopped()) { return $event->result; }
				}
			} else {
				$this->log($entity->errors(), 'debug');
			}
			// End

			// Update workflow_step_id in workflow_records.
			$workflowStepId = $entity->workflow_step_id;
			$workflowRecordId = $entity->workflow_record_id;
			$this->WorkflowRecords->updateAll(
				['workflow_step_id' => $workflowStepId],
				['id' => $workflowRecordId]
			);
			// End

			// Redirect
			$action = $this->_table->ControllerAction->url('view');
			return $this->_table->controller->redirect($action);
			// End
		}
	}

	public function setupWorkflow() {
		if (!is_null($this->config('model'))) {
			$setup = $this->WorkflowModels
				->find()
				->where([
					$this->WorkflowModels->aliasField('model') => $this->config('model')
				])
				->first();

			if (!empty($setup)) {
				$this->workflowId = $this->getWorkflow($setup);
			}
		}
	}

	public function getWorkflow($workflowModel) {
		// Find all Workflow setup for the model
		$workflowIds = $this->Workflows
			->find('list', ['keyField' => 'id', 'valueField' => 'id'])
			->where([
				$this->Workflows->aliasField('workflow_model_id') => $workflowModel->id
			])
			->toArray();

		$workflowId = 0;
		if (empty($workflowModel->filter)) {
			$workflowResults = $this->Workflows
				->find()
				->where([$this->Workflows->aliasField('id IN') => $workflowIds])
				->all();

			if (!$workflowResults->isEmpty()) {
				$workflowId = $workflowResults->first()->id;
			}
		} else {
			// Filter key
			list(, $base) = pluginSplit($workflowModel->filter);
			$filterKey = Inflector::underscore(Inflector::singularize($base)) . '_id';

			$paramsPass = $this->_table->ControllerAction->paramsPass();
			if (!empty($paramsPass)) {
				$modelReference = current($paramsPass);
				$entity = $this->model->get($modelReference);

				if (isset($entity->$filterKey)) {
					$filterId = $entity->$filterKey;
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
			}
		}

		return $workflowId;
	}
}
