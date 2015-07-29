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
use App\Model\Traits\OptionsTrait;

class WorkflowsTable extends AppTable {
	use OptionsTrait;

	private $_fieldOrder = ['workflow_model_id', 'code', 'name'];
	private $_contain = ['Filters'];

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('WorkflowModels', ['className' => 'Workflow.WorkflowModels']);
		$this->hasMany('WorkflowSteps', ['className' => 'Workflow.WorkflowSteps', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->belongsToMany('Filters', [
			'className' => 'FieldOption.FieldOptionValues',
			'joinTable' => 'workflows_filters',
			'foreignKey' => 'workflow_id',
			'targetForeignKey' => 'filter_id',
			'through' => 'Workflow.WorkflowsFilters',
			'dependent' => true
		]);
	}

	public function beforeSave(Event $event, Entity $entity, ArrayObject $options) {
		parent::beforeSave($event, $entity, $options);
		// Auto insert default workflow_steps when add
		if ($entity->isNew()) {
			$data = [
				'workflow_steps' => [
					['name' => __('Open'), 'stage' => 0],
					['name' => __('Closed'), 'stage' => 1]
				]
			];

			$entity = $this->patchEntity($entity, $data);
		}
	}

	public function afterSave(Event $event, Entity $entity, ArrayObject $options) {
		if (isset($entity->apply_to_all) && $entity->apply_to_all == 1) {
			$workflowIds = $this
				->find('list', ['keyField' => 'id', 'valueField' => 'id'])
				->where([
					$this->aliasField('workflow_model_id') => $entity->workflow_model_id
				])
				->toArray();

			$WorkflowsFilters = TableRegistry::get('Workflow.WorkflowsFilters');
			$WorkflowsFilters->deleteAll([
				'OR' => [
					[
						$WorkflowsFilters->aliasField('workflow_id IN') => $workflowIds,
						$WorkflowsFilters->aliasField('filter_id') => 0
					],
					$WorkflowsFilters->aliasField('workflow_id') => $entity->id
				]
			]);

			$filterData = [
				'workflow_id' => $entity->id,
				'filter_id' => 0
			];
			$filterEntity = $WorkflowsFilters->newEntity($filterData);

			if ($WorkflowsFilters->save($filterEntity)) {
			} else {
				$WorkflowsFilters->log($filterEntity->errors(), 'debug');
			}
		}
	}

	public function afterAction(Event $event) {
		$this->ControllerAction->setFieldOrder($this->_fieldOrder);
	}

	public function onGetApplyToAll(Event $event, Entity $entity) {
		if (sizeof($entity->filters) > 0) {
			$value = __('No');
		} else {
			$WorkflowsFilters = TableRegistry::get('Workflow.WorkflowsFilters');
			$results = $WorkflowsFilters
				->find()
				->where([
					$WorkflowsFilters->aliasField('workflow_id') => $entity->id,
					$WorkflowsFilters->aliasField('filter_id') => 0
				])
				->all();

			if ($results->isEmpty()) {
				$value = __('No');
			} else {
				$value = __('Yes');
			}
		}

		return $value;
    }

	public function indexBeforeAction(Event $event) {
		$this->ControllerAction->field('apply_to_all');
		$this->ControllerAction->field('filters', [
			'type' => 'chosenSelect'
		]);

		$this->_fieldOrder = ['workflow_model_id', 'apply_to_all', 'filters', 'code', 'name'];
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$query->contain($this->_contain);
	}

	public function viewEditBeforeQuery(Event $event, Query $query) {
		$query->contain($this->_contain);
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$this->setRequestQuery($entity);

		$this->ControllerAction->field('apply_to_all');
		$this->ControllerAction->field('filters', [
			'type' => 'chosenSelect'
		]);
	}

	public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		//Required by patchEntity for associated data
		$newOptions = [];
		$newOptions['associated'] = $this->_contain;

		$arrayOptions = $options->getArrayCopy();
		$arrayOptions = array_merge_recursive($arrayOptions, $newOptions);
		$options->exchangeArray($arrayOptions);
	}

    public function addEditAfterAction(Event $event, Entity $entity) {
    	list($modelOptions, $selectedModel, $applyToAllOptions, $selectedApplyToAll) = array_values($this->_getSelectOptions());

    	$this->ControllerAction->field('workflow_model_id', [
    		'options' => $modelOptions
    	]);
		$this->ControllerAction->field('apply_to_all', [
    		'options' => $applyToAllOptions
    	]);
		$this->ControllerAction->field('filters', [
			'type' => 'chosenSelect'
		]);
	}

	public function editOnInitialize(Event $event, Entity $entity) {
		$this->setRequestQuery($entity);
	}

	public function onUpdateFieldWorkflowModelId(Event $event, array $attr, $action, $request) {
		if ($action == 'add' || $action == 'edit') {
			$modelOptions = $attr['options'];
			$selectedModel = !is_null($request->query('model')) ? $request->query('model') : key($modelOptions);
			$this->advancedSelectOptions($modelOptions, $selectedModel);

			$attr['options'] = $modelOptions;
			$attr['onChangeReload'] = 'changeModel';
		}

		return $attr;
	}

	public function onUpdateFieldApplyToAll(Event $event, array $attr, $action, $request) {
		if ($action == 'add' || $action == 'edit') {
			$applyToAllOptions = $attr['options'];
			$selectedApplyToAll = !is_null($request->query('apply_all')) ? $request->query('apply_all') : key($applyToAllOptions);
			$this->advancedSelectOptions($applyToAllOptions, $selectedApplyToAll);

			$attr['options'] = $applyToAllOptions;
			$attr['onChangeReload'] = 'changeApplyToAll';
		}

		return $attr;
	}

	public function onUpdateFieldFilters(Event $event, array $attr, $action, $request) {
		if ($action == 'view') {
			$selectedModel = $request->query('model');
			$selectedApplyToAll = $request->query('apply_all');
		} else if ($action == 'add' || $action == 'edit') {
			$modelOptions = $this->fields['workflow_model_id']['options'];
			$selectedModel = !is_null($request->query('model')) ? $request->query('model') : key($modelOptions);

			$applyToAllOptions = $this->fields['apply_to_all']['options'];
			$selectedApplyToAll = !is_null($request->query('apply_all')) ? $request->query('apply_all') : key($applyToAllOptions);
		}

		if (isset($selectedModel) && !is_null($selectedModel)) {
			$filter = $this->WorkflowModels->get($selectedModel)->filter;
			if (empty($filter)) {
				$this->fields['apply_to_all']['visible'] = false;
				$attr['visible'] = false;
			} else {
				$this->fields['apply_to_all']['visible'] = true;

				if ($selectedApplyToAll == 1) {
					$attr['visible'] = false;
					$this->_fieldOrder = ['workflow_model_id', 'apply_to_all', 'code', 'name'];
				} else {
					$modelAlias = $this->ControllerAction->getModel($filter)['model'];
					$labelText = Inflector::underscore(Inflector::singularize($modelAlias));
					$filterOptions = TableRegistry::get($filter)->getList()->toArray();

					$attr['placeholder'] = __('Select ') . __(Inflector::humanize($labelText));
					$attr['options'] = $filterOptions;
					$attr['attr']['label'] = __(Inflector::humanize($labelText));
					$attr['visible'] = true;

					$this->_fieldOrder = ['workflow_model_id', 'apply_to_all', 'filters', 'code', 'name'];
				}
			}
		}

		return $attr;
	}

	public function addEditOnChangeModel(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$request = $this->request;
		unset($request->query['model']);
		unset($request->query['apply_all']);

		if ($request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('workflow_model_id', $request->data[$this->alias()])) {
					$request->query['model'] = $request->data[$this->alias()]['workflow_model_id'];
				}
			}
		}
	}

	public function addEditOnChangeApplyToAll(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$request = $this->request;
		unset($request->query['model']);
		unset($request->query['apply_all']);

		if ($request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('workflow_model_id', $request->data[$this->alias()])) {
					$request->query['model'] = $request->data[$this->alias()]['workflow_model_id'];
				}
				if (array_key_exists('apply_to_all', $request->data[$this->alias()])) {
					$request->query['apply_all'] = $request->data[$this->alias()]['apply_to_all'];
				}
			}
		}
	}

	public function setRequestQuery(Entity $entity) {
		// Set model and apply_all
		$this->request->query['model'] = $entity->workflow_model_id;

		if (sizeof($entity->filters) > 0) {
			$this->request->query['apply_all'] = 0;
		} else {
			$WorkflowsFilters = TableRegistry::get('Workflow.WorkflowsFilters');
			$results = $WorkflowsFilters
				->find()
				->where([
					$WorkflowsFilters->aliasField('workflow_id') => $entity->id,
					$WorkflowsFilters->aliasField('filter_id') => 0
				])
				->all();

			if ($results->isEmpty()) {
				$this->request->query['apply_all'] = 0;
			} else {
				$this->request->query['apply_all'] = 1;
			}
		}
		// End
	}

	public function _getSelectOptions() {
		//Return all required options and their key
		$modelOptions = $this->WorkflowModels
			->find('list')
			->toArray();
		$selectedModel = !is_null($this->request->query('model')) ? $this->request->query('model') : key($modelOptions);

		$applyToAllOptions = $this->getSelectOptions('general.yesno');
		$selectedApplyToAll = !is_null($this->request->query('apply_all')) ? $this->request->query('apply_all') : key($applyToAllOptions);

		return compact('modelOptions', 'selectedModel', 'applyToAllOptions', 'selectedApplyToAll');
	}
}
