<?php
namespace Workflow\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\Utility\Inflector;
use Cake\Datasource\ConnectionManager;
use App\Model\Traits\OptionsTrait;

class WorkflowsTable extends AppTable {
	use OptionsTrait;

	// Workflow Steps - stage
	const OPEN = 0;
	const PENDING = 1;
	const CLOSED = 2;

	// Workflow Actions - action
	const APPROVE = 0;
	const REJECT = 1;

	// Apply To All
	const YES = 1;
	const NO = 0;

	private $WorkflowsFilters = null;
	private $filterClass = [
		'className' => 'FieldOption.FieldOptionValues',
		'joinTable' => 'workflows_filters',
		'foreignKey' => 'workflow_id',
		'targetForeignKey' => 'filter_id',
		'through' => 'Workflow.WorkflowsFilters',
		'dependent' => true
	];

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('WorkflowModels', ['className' => 'Workflow.WorkflowModels']);
		$this->hasMany('WorkflowSteps', ['className' => 'Workflow.WorkflowSteps', 'dependent' => true, 'cascadeCallbacks' => true]);

		$this->WorkflowsFilters = TableRegistry::get('Workflow.WorkflowsFilters');
	}

	public function validationDefault(Validator $validator) {
		$validator->add('code', [
			'ruleUnique' => [
				'rule' => ['validateUnique', ['scope' => 'workflow_model_id']],
				'provider' => 'table'
			]
		]);

		return $validator;
	}

	public function beforeSave(Event $event, Entity $entity, ArrayObject $options) {
		// Auto insert default workflow_steps when add
		if ($entity->isNew()) {
			$data = [
				'workflow_steps' => [
					['name' => __('Open'), 'stage' => self::OPEN, 'is_editable' => 1, 'is_removable' => 1],
					['name' => __('Pending For Approval'), 'stage' => self::PENDING],
					['name' => __('Closed'), 'stage' => self::CLOSED]
				]
			];

			$entity = $this->patchEntity($entity, $data);
		}
	}

	public function afterSave(Event $event, Entity $entity, ArrayObject $options) {
		if ($entity->isNew()) {
			// When add: preinsert default workflow actions
			$this->setWorkflowActions($entity);
		}

		// Only allow one workflow to set as Apply To All
		$this->setApplyToAll($entity);

		$this->resetWorkflowStepId($entity);
	}

	public function indexBeforeAction(Event $event) {
		//Add controls filter to index page
		$toolbarElements = [
            ['name' => 'Workflow.Workflows/controls', 'data' => [], 'options' => []]
        ];
		$this->controller->set('toolbarElements', $toolbarElements);
		// End

		$this->ControllerAction->field('apply_to_all');
		$this->ControllerAction->field('filters');
		$this->ControllerAction->setFieldOrder(['workflow_model_id', 'apply_to_all', 'filters', 'code', 'name']);
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$modelOptions = $this->getWorkflowModel();
		$modelOptions = ['-1' => __('All Workflows')] + $modelOptions;
		$selectedModel = $this->queryString('model', $modelOptions);
		$this->controller->set(compact('modelOptions', 'selectedModel'));
		
		$query
			->matching('WorkflowModels')
			->order([$this->aliasField('workflow_model_id'), $this->aliasField('code'), $this->aliasField('name')]);

		if ($selectedModel != -1) {
			$query->where([$this->aliasField('workflow_model_id') => $selectedModel]);
		}
	}

	public function onGetApplyToAll(Event $event, Entity $entity) {
		if ($this->action == 'index') {
			$entity->filters = [];

			if (!is_null($entity->_matchingData['WorkflowModels']->filter)) {
				$filter = $entity->_matchingData['WorkflowModels']->filter;

				$filterIds = $this->WorkflowsFilters
					->find('list', ['keyField' => 'filter_id', 'valueField' => 'filter_id'])
					->where([
						$this->WorkflowsFilters->aliasField('workflow_id') => $entity->id
					])
					->toArray();

				if (array_key_exists(0, $filterIds)) {
					$value = __('Yes');
				} else {
					$value = __('No');

					$filterModel = TableRegistry::get($filter);
					$filters = $filterModel
						->getList()
						->where([
							$filterModel->aliasField('id IN ') => $filterIds
						])
						->toArray();

					$entity->filters = $filters;
				}

				return $value;
			}

			return '<i class="fa fa-minus"></i>';
		}
	}

	public function onGetFilters(Event $event, Entity $entity) {
		if ($this->action == 'index') {
			if (!is_null($entity->_matchingData['WorkflowModels']->filter)) {
				if (sizeof($entity->filters) > 0) {
					$chosenSelectList = [];
					foreach ($entity->filters as $key => $value) {
						$chosenSelectList[] = $value;
					}
					return implode(', ', $chosenSelectList);
				}
			}

			return '<i class="fa fa-minus"></i>';
		}
	}

	public function viewEditBeforeQuery(Event $event, Query $query) {
		$paramsPass = $this->ControllerAction->paramsPass();
		$workflowId = current($paramsPass);
		$selectedModel = $this->get($workflowId)->workflow_model_id;
		$this->addAssociation($selectedModel);

		$query
			->matching('WorkflowModels')
			->contain(['Filters']);
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$this->setupFields($entity);
	}

	public function addOnInitialize(Event $event, Entity $entity) {
		// always reset
		unset($this->request->query['model']);
	}

	public function addBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		if (array_key_exists($this->alias(), $data)) {
			if (array_key_exists('workflow_model_id', $data[$this->alias()])) {
				$selectedModel = $data[$this->alias()]['workflow_model_id'];
				$this->addAssociation($selectedModel);
			}
		}
	}

	public function addEditAfterAction(Event $event, Entity $entity) {
    	$this->setupFields($entity);
    }

    public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $options) {
		$query->where([
			$this->aliasField('workflow_model_id') => $entity->workflow_model_id
		]);

		if ($query->count() == 1) {
			$this->Alert->warning('general.notTransferrable');
			$event->stopPropagation();
			return $this->controller->redirect($this->ControllerAction->url('index'));
		}

		$options['keyField'] = 'id';
		$options['valueField'] = 'code_name';

		// Convert Step Options
		$convertOptions = $this
			->find('list')
			->where([
				$this->aliasField('workflow_model_id') => $entity->workflow_model_id,
				$this->aliasField('id <>') => $entity->id
			])
			->toArray();
		$entity->transfer_to = $this->queryString('workflow', $convertOptions);

		$convertStepOptions = $this->WorkflowSteps
			->find('list')
			->where([
				$this->WorkflowSteps->aliasField('workflow_id') => $entity->transfer_to
			])
			->toArray();
		// End

		// Steps
		$where = [
			$this->WorkflowSteps->aliasField('workflow_id') => $entity->id
		];

		$steps = $this->WorkflowSteps
			->find()
			->where($where)
			->all();

		$stepIds = $this->WorkflowSteps
			->find('list', ['keyField' => 'id', 'valueField' => 'id'])
			->where($where)
			->toArray();
		// End

		// Apply To
		$tableHeaders = [__('Feature'), __('No of records')];
		$tableCells = [];

		$rowData = [];
		$rowData[] = $this->WorkflowsFilters->alias();
		$rowData[] = $this->WorkflowsFilters->find()->where([$this->WorkflowsFilters->aliasField('workflow_id') => $entity->id])->count();
		$tableCells[] = $rowData;

		$rowData = [];
		$WorkflowRecords = TableRegistry::get('Workflow.WorkflowRecords');
		$rowData[] = $WorkflowRecords->alias();
		$rowData[] = $WorkflowRecords
			->find()
			->where([
				$WorkflowRecords->aliasField('workflow_model_id') => $entity->workflow_model_id,
				$WorkflowRecords->aliasField('workflow_step_id IN') => $stepIds
			])
			->count();
		$tableCells[] = $rowData;

		$rowData = [];
		$registryAlias = $this->WorkflowModels->get($entity->workflow_model_id)->model;
		$targetModel = TableRegistry::get($registryAlias);
		$rowData[] = $targetModel->alias();
		$rowData[] = $targetModel
			->find()
			->where([
				$targetModel->aliasField('status_id IN') => $stepIds
			])
			->count();
		$tableCells[] = $rowData;
		// End

		$this->controller->set(compact('steps', 'convertStepOptions', 'tableHeaders', 'tableCells'));
	}

	public function onBeforeDelete(Event $event, ArrayObject $options, $id) {
		$requestData = $this->request->data;
		$submit = isset($requestData['submit']) ? $requestData['submit'] : 'save';

		if ($submit == 'save') {
			$process = function($model, $id, $options) {
				$entity = $model->get($id);
				// Overwrite $process and skip delete, delete is done in onDeleteTransfer
				return true;
			};

			return $process;
		} else {
			$url = $this->ControllerAction->url('remove');
			$url['workflow'] = $requestData['transfer_to'];
			$event->stopPropagation();
			return $this->controller->redirect($url);
		}
	}

	public function onDeleteTransfer(Event $event, ArrayObject $options, $id) {
		$transferProcess = function($associations, $transferFrom, $transferTo, $model) {
			$conn = ConnectionManager::get('default');
			$conn->begin();

			$requestData = $this->request->data;
			$entity = $model->get($transferFrom);

			// Update workflow_id in workflows_filters
			$filterResults = $this->WorkflowsFilters
				->find()
				->where([
					$this->WorkflowsFilters->aliasField('workflow_id') => $transferTo,
					$this->WorkflowsFilters->aliasField('filter_id') => 0
				])
				->all();

			if ($filterResults->isEmpty()) {
				$this->WorkflowsFilters->updateAll(
					['workflow_id' => $transferTo],
					['workflow_id' => $transferFrom]
				);
			} else {
				$this->WorkflowsFilters->deleteAll([
					'workflow_id' => $transferFrom
				]);
			}
			// End

			// Update workflow_step_id in workflow_records and model table
			$WorkflowRecords = TableRegistry::get('Workflow.WorkflowRecords');
			$WorkflowTransitions = TableRegistry::get('Workflow.WorkflowTransitions');
			$registryAlias = $this->WorkflowModels->get($entity->workflow_model_id)->model;
			$targetModel = TableRegistry::get($registryAlias);
			foreach ($requestData[$this->alias()]['steps'] as $key => $stepObj) {
				$stepFrom = $stepObj['workflow_step_id'];
				$stepTo = $stepObj['convert_workflow_step_id'];
				$step = $this->WorkflowSteps->get($stepTo);

				$records = $WorkflowRecords
					->find()
					->matching('WorkflowSteps')
					->where([
						$WorkflowRecords->aliasField('workflow_step_id') => $stepFrom
					])
					->all();

				foreach ($records as $recordObj) {
					// workflow_step_id is needed for afterSave logic in WorkflowTransitions
					$transitionData = [
						'comment' => '',
						'prev_workflow_step_id' => $recordObj->_matchingData['WorkflowSteps']->id,
						'prev_workflow_step_name' => $recordObj->_matchingData['WorkflowSteps']->name,
						'workflow_step_id' => $step->id,
						'workflow_step_name' => $step->name,
						'workflow_action_id' => NULL,
						'workflow_action_name' => __('Administration - Delete and Transfer Workflow.'),
						'workflow_record_id' => $recordObj->id
					];

					$transitionEntity = $WorkflowTransitions->newEntity($transitionData, ['validate' => false]);
					if( $WorkflowTransitions->save($transitionEntity) ){
					} else {
						$WorkflowTransitions->log($transitionEntity->errors(), 'debug');
					}
				}

				$WorkflowRecords->updateAll(
					['workflow_step_id' => $stepTo],
					['workflow_step_id' => $stepFrom]
				);

				$targetModel->updateAll(
					['status_id' => $stepTo],
					['status_id' => $stepFrom]
				);
			}
			// End

			// delete workflow
			if ($model->delete($entity)) {
				$conn->commit();
			} else {
				$conn->rollback();
			}
			// End
		};

		return $transferProcess;
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		$buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
		if (array_key_exists('remove', $buttons)) {
			// Check by model if filter applied, disabled delete button if the workflow is apply to all.
			$filter = $entity->_matchingData['WorkflowModels']->filter;
			if (!is_null($filter)) {
				$results = $this->WorkflowsFilters
					->find()
					->where([
						$this->WorkflowsFilters->aliasField('workflow_id') => $entity->id,
						$this->WorkflowsFilters->aliasField('filter_id') => 0
					])
					->all();

				if (!$results->isEmpty()) {
					unset($buttons['remove']);
				}
			}
		}

		return $buttons;
	}

    public function onUpdateFieldWorkflowModelId(Event $event, array $attr, $action, $request) {
    	if ($action == 'add') {
    		$modelOptions = $this->getWorkflowModel();

    		// Loop through modelOptions and unset it if the model do not have filter and already created workflow.
			foreach ($modelOptions as $key => $value) {
				$filter = $this->WorkflowModels->get($key)->filter;
				if (empty($filter)) {
					$workflowResults = $this
						->find()
						->where([
							$this->aliasField('workflow_model_id') => $key
						])
						->all();

					if (!$workflowResults->isEmpty()) {
						unset($modelOptions[$key]);
					}
				}
			}
			// End

			// $modelOptions = ['' => __('-- Select Workflow --')] + $modelOptions;
			$selectedModel = !is_null($request->query('model')) ? $request->query('model') : key($modelOptions);
			$this->advancedSelectOptions($modelOptions, $selectedModel);

			$attr['options'] = $modelOptions;
			$attr['onChangeReload'] = 'changeModel';
    	} else if ($action == 'edit') {
    		$workflowModel = $attr['attr']['workflowModel'];

    		$attr['type'] = 'readonly';
    		$attr['value'] = $workflowModel->id;
    		$attr['attr']['value'] = $workflowModel->name;
    	}

    	return $attr;
    }

    public function onUpdateFieldApplyToAll(Event $event, array $attr, $action, $request) {
    	if ($action == 'view') {
    		$applyToAllOptions = $attr['options'];
    		$attr['value'] = $applyToAllOptions[$attr['value']];
    	}

    	return $attr;
    }

    public function onUpdateFieldFilters(Event $event, array $attr, $action, $request) {
    	if ($action == 'view') {
    		$workflowModel = $attr['attr']['workflowModel'];
    		$filter = $workflowModel->filter;
    		list($plugin, $modelAlias) = explode('.', $filter, 2);
    		$labelText = Inflector::underscore(Inflector::singularize($modelAlias));

    		$attr['attr']['label'] = __(Inflector::humanize($labelText));
    	} else if ($action == 'add' || $action == 'edit') {
    		$workflowModel = $attr['attr']['workflowModel'];
    		$selectedModel = $workflowModel->id;
    		$filter = $workflowModel->filter;
    		$model = $workflowModel->model;

    		list($plugin, $modelAlias) = explode('.', $filter, 2);
			$labelText = Inflector::underscore(Inflector::singularize($modelAlias));
			$filterOptions = TableRegistry::get($filter)->getList()->toArray();

			// Trigger event to get the correct wofkflow filter options
			$subject = TableRegistry::get($model);
			$newEvent = $subject->dispatchEvent('Workflow.getFilterOptions', null, $subject);
			if ($newEvent->isStopped()) { return $newEvent->result; }
			if (!empty($newEvent->result)) {
				$filterOptions = $newEvent->result;
			}
			// End
			
			// Logic to remove filter from the list if already in used
			$Workflows = TableRegistry::get('Workflow.Workflows');

			$filterQuery = $this->WorkflowsFilters
				->find('list', ['keyField' => 'filter_id', 'valueField' => 'filter_id'])
				->matching('Workflows', function ($q) use ($Workflows, $selectedModel) {
					return $q->where([
							$Workflows->aliasField('workflow_model_id') => $selectedModel
						]);
				})
				->where([
					$this->WorkflowsFilters->aliasField('filter_id <> ') => 0
				]);

			if ($action == 'edit') {
				$paramsPass = $this->ControllerAction->paramsPass();
				$workflowId = current($paramsPass);
				$filterQuery->where([
					$this->WorkflowsFilters->aliasField('workflow_id <> ') => $workflowId
				]);
			}
			$filterIds = $filterQuery->toArray();

			foreach ($filterOptions as $key => $value) {
				if (array_key_exists($key, $filterIds)) {
					unset($filterOptions[$key]);
				}
			}
			// End

			$attr['placeholder'] = __('Select ') . __(Inflector::humanize($labelText));
			$attr['options'] = $filterOptions;
			$attr['attr']['label'] = __(Inflector::humanize($labelText));
    	}

    	return $attr;
    }

	private function getWorkflowModel() {
		return $this->WorkflowModels->find('list')->toArray();
	}

	private function setupFields(Entity $entity) {
		$selectedModel = $entity->workflow_model_id;

		// for workflow that has filter:
		// If no workflow is added before, show apply_to_all = Yes
		// else show apply_to_all = No and Filters

		if (empty($selectedModel)) {
			$this->ControllerAction->field('workflow_model_id');
			$fieldOrder = ['workflow_model_id'];
		} else {
			$workflowModel = $this->WorkflowModels->get($selectedModel);
			$this->ControllerAction->field('workflow_model_id', [
				'attr' => ['workflowModel' => $workflowModel]
			]);
			$fieldOrder = ['workflow_model_id'];

			$filter = $workflowModel->filter;
			if (!empty($filter)) {
				$showFilters = false;

				$workflows = $this
					->find('list')
					->where([
						$this->aliasField('workflow_model_id') => $selectedModel
					])
					->toArray();

				if (!empty($workflows)) {
					$workflowKeys = array_keys($workflows);
					$workflowIds = array_combine($workflowKeys, $workflowKeys);
					if (isset($entity->id) && array_key_exists($entity->id, $workflowIds)) {
						unset($workflowIds[$entity->id]);
					}

					$filterResults = $this->WorkflowsFilters
						->find()
						->where([
							$this->WorkflowsFilters->aliasField('workflow_id IN ') => $workflowIds,
							$this->WorkflowsFilters->aliasField('filter_id') => 0
						])
						->all();

					if (!$filterResults->isEmpty()) {
						$showFilters = true;
					}
				}

				$applyToAllOptions = $this->getSelectOptions('general.yesno');
				$inputOptions = [
					'type' => 'readonly',
					'options' => $applyToAllOptions
				];

				if ($showFilters) {
					$inputOptions['value'] = self::NO;
					$inputOptions['attr']['value'] = $applyToAllOptions[self::NO];

					$this->ControllerAction->field('apply_to_all', $inputOptions);
					$this->ControllerAction->field('filters', [
						'type' => 'chosenSelect',
						'attr' => ['workflowModel' => $workflowModel]
					]);
					$fieldOrder[] = 'apply_to_all';
					$fieldOrder[] = 'filters';
				} else {
					$inputOptions['value'] = self::YES;
					$inputOptions['attr']['value'] = $applyToAllOptions[self::YES];

					$this->ControllerAction->field('apply_to_all', $inputOptions);
					$fieldOrder[] = 'apply_to_all';
				}
			}
		}

		$fieldOrder[] = 'code';
		$fieldOrder[] = 'name';
		$this->ControllerAction->setFieldOrder($fieldOrder);
	}

	private function addAssociation($selectedModel=null) {
		if (!is_null($selectedModel)) {
			$filter = $this->WorkflowModels->get($selectedModel)->filter;
			if (!is_null($filter)) {
				$this->filterClass['className'] = $filter;
				$this->belongsToMany('Filters', $this->filterClass);
			}
		}
	}

	private function setWorkflowActions($entity) {
		$stepOpen = null;
		$stepPending = null;
		$stepClosed = null;

		foreach ($entity->workflow_steps as $key => $step) {
			switch ($step->stage) {
				case self::OPEN:
					$stepOpen = $step;
					break;
				case self::PENDING:
					$stepPending = $step;
					break;
				case self::CLOSED:
					$stepClosed = $step;
					break;
				default:
					break;
			}
		}

		// Step - Open
		$dataOpen = [
			'id' => $stepOpen->id,
			'workflow_actions' => [
				[
					'name' => __('Submit For Approval'),
					'action' => self::APPROVE,
					'visible' => 1,
					'next_workflow_step_id' => $stepPending->id,
					'comment_required' => 0
				],
				[
					'name' => __('Cancel'),
					'action' => self::REJECT,
					'visible' => 1,
					'next_workflow_step_id' => $stepClosed->id,
					'comment_required' => 0
				]
			]
		];
		$entityOpen = $this->WorkflowSteps->newEntity($dataOpen);
		if ($this->WorkflowSteps->save($entityOpen)) {
		} else {
			$this->WorkflowSteps->log($entityOpen->errors(), 'debug');
		}
		// End

		// Step - Pending
		$dataPending = [
			'id' => $stepPending->id,
			'workflow_actions' => [
				[
					'name' => __('Approve'),
					'action' => self::APPROVE,
					'visible' => 1,
					'next_workflow_step_id' => $stepClosed->id,
					'comment_required' => 0
				],
				[
					'name' => __('Reject'),
					'action' => self::REJECT,
					'visible' => 1,
					'next_workflow_step_id' => $stepOpen->id,
					'comment_required' => 0
				]
			]
		];
		$entityPending = $this->WorkflowSteps->newEntity($dataPending);
		if ($this->WorkflowSteps->save($entityPending)) {
		} else {
			$this->WorkflowSteps->log($entityPending->errors(), 'debug');
		}
		// End

		// Step - Closed
		$dataClosed = [
			'id' => $stepClosed->id,
			'workflow_actions' => [
				[
					'name' => __('Approve'),
					'action' => self::APPROVE,
					'visible' => 0,
					'next_workflow_step_id' => 0,
					'comment_required' => 0
				],
				[
					'name' => __('Reject'),
					'action' => self::REJECT,
					'visible' => 0,
					'next_workflow_step_id' => 0,
					'comment_required' => 0
				],
				[
					'name' => __('Reopen'),
					'action' => null,
					'visible' => 1,
					'next_workflow_step_id' => $stepOpen->id,
					'comment_required' => 0
				]
			]
		];
		$entityClosed = $this->WorkflowSteps->newEntity($dataClosed);
		if ($this->WorkflowSteps->save($entityClosed)) {
		} else {
			$this->WorkflowSteps->log($entityClosed->errors(), 'debug');
		}
		// End
	}

	private function setApplyToAll($entity) {
		if (isset($entity->apply_to_all) && $entity->apply_to_all == self::YES) {
			$workflowIds = $this
				->find('list', ['keyField' => 'id', 'valueField' => 'id'])
				->where([
					$this->aliasField('workflow_model_id') => $entity->workflow_model_id
				])
				->toArray();

			$this->WorkflowsFilters->deleteAll([
				'OR' => [
					[
						$this->WorkflowsFilters->aliasField('workflow_id IN') => $workflowIds,
						$this->WorkflowsFilters->aliasField('filter_id') => 0
					],
					$this->WorkflowsFilters->aliasField('workflow_id') => $entity->id
				]
			]);

			$filterData = [
				'workflow_id' => $entity->id,
				'filter_id' => 0
			];
			$filterEntity = $this->WorkflowsFilters->newEntity($filterData);

			if ($this->WorkflowsFilters->save($filterEntity)) {
			} else {
				$this->WorkflowsFilters->log($filterEntity->errors(), 'debug');
			}
		}
	}

	private function resetWorkflowStepId($entity) {
		$selectedModel = $entity->workflow_model_id;
		$workflowModel = $this->WorkflowModels->get($selectedModel);

		$model = $workflowModel->model;
		$filter = $workflowModel->filter;

		if (!is_null($filter)) {
			$statusKey = 'status_id';
			list($filterPlugin, $filterAlias) = explode(".", $filter, 2);
			$filterKey = Inflector::underscore(Inflector::singularize($filterAlias)) . '_id';

			$filterIds = [];
			$stepIds = [];
			$openStepId = null;

			if ($entity->has('filters')) {
				foreach ($entity->filters as $key => $obj) {
					$filterIds[$obj->id] = $obj->id;
				}

				$steps = $this->WorkflowSteps
					->find()
					->where([
						$this->WorkflowSteps->aliasField('workflow_id') => $entity->id
					])
					->toArray();

				foreach ($steps as $key => $step) {
					$stepIds[$step->id] = $step->id;
					if ($step->stage == self::OPEN) {
						$openStepId = $step->id;
					}
				}
			}

			$subject = TableRegistry::get($model);
			if (empty($filterIds) && !$entity->isNew()) {
				$originalFilters = $entity->extractOriginal(['filters']);
				foreach ($originalFilters['filters'] as $key => $obj) {
					$filterIds[$obj->id] = $obj->id;
				}

				$recordIds = $subject
					->find('list', ['keyField' => 'id', 'valueField' => 'id'])
					->where([
						$subject->aliasField($filterKey . ' IN ') => $filterIds,
						$subject->aliasField($statusKey . ' IN ') => $stepIds
					])
					->toArray();

				$Workflows = TableRegistry::get('Workflow.Workflows');
				$defaultWorkflowId = $this->WorkflowsFilters
					->find('list', ['keyField' => 'workflow_id', 'valueField' => 'workflow_id'])
					->matching('Workflows', function ($q) use ($Workflows, $selectedModel) {
						return $q->where([
								$Workflows->aliasField('workflow_model_id') => $selectedModel
							]);
					})
					->where([
						$this->WorkflowsFilters->aliasField('filter_id') => 0
					])
					->toArray();

				$openStepId = $this->WorkflowSteps
					->find()
					->where([
						$this->WorkflowSteps->aliasField('workflow_id') => $defaultWorkflowId,
						$this->WorkflowSteps->aliasField('stage') => self::OPEN
					])
					->first()
					->id;

				$subject->updateAll(
					[$statusKey => $openStepId],
					['id IN ' => $recordIds]
				);

				$WorkflowRecords = TableRegistry::get('Workflow.WorkflowRecords');
				$WorkflowRecords->updateAll(
					['workflow_step_id' => $openStepId],
					[
						'workflow_model_id' => $selectedModel,
						'model_reference IN ' => $recordIds
					]
				);
			} else {
				$recordIds = $subject
					->find('list', ['keyField' => 'id', 'valueField' => 'id'])
					->where([
						$subject->aliasField($filterKey . ' IN ') => $filterIds,
						$subject->aliasField($statusKey . ' NOT IN ') => $stepIds
					])
					->toArray();

				$subject->updateAll(
					[$statusKey => $openStepId],
					['id IN ' => $recordIds]
				);

				$WorkflowRecords = TableRegistry::get('Workflow.WorkflowRecords');
				$WorkflowRecords->updateAll(
					['workflow_step_id' => $openStepId],
					[
						'workflow_model_id' => $selectedModel,
						'model_reference IN ' => $recordIds
					]
				);
			}
		}
	}
}
