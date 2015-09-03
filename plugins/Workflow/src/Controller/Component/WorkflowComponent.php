<?php
namespace Workflow\Controller\Component;

use Cake\ORM\TableRegistry;
use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\Utility\Inflector;
use Cake\Log\LogTrait;

class WorkflowComponent extends Component {
	use LogTrait;
	
	private $controller;
	private $action;
	private $model = null;
	private $currentAction;

	public $Workflows;
	public $WorkflowModels;
	public $WorkflowsFilters;

	public $components = ['Auth', 'ControllerAction', 'AccessControl'];

	public function initialize(array $config) {
		$this->controller = $this->_registry->getController();
		$this->action = $this->request->params['action'];

		$this->Workflows = TableRegistry::get('Workflow.Workflows');
		$this->WorkflowModels = TableRegistry::get('Workflow.WorkflowModels');
		$this->WorkflowsFilters = TableRegistry::get('Workflow.WorkflowsFilters');

		// To bypass the permission
		$session = $this->request->session();
		if ($session->check('Workflow.Workflows.models')) {
			$models = $session->read('Workflow.Workflows.models');
		} else {
			$models = $this->WorkflowModels
				->find('list', ['keyField' => 'id', 'valueField' => 'model'])
				->toArray();

			$session->write('Workflow.Workflows.models', $models);
		}

		foreach ($models as $key => $model) {
			$ignoreList[$model] = ['processWorkflow'];	
		}
		$this->AccessControl->config('ignoreList', $ignoreList);
		// End
	}

	public function startup(Event $event) {
		$controller = $event->subject();
		$this->model = $this->ControllerAction->model();
		$this->currentAction = $this->ControllerAction->action();

		if (in_array($this->currentAction, ['view', 'processWorkflow'])) {
			$alias = $this->model->alias();
			$registryAlias = $this->model->registryAlias();

			$setup = $this->WorkflowModels
				->find()
				->where([
					$this->WorkflowModels->aliasField('model') => $registryAlias
				])
				->first();

			// Trigger WorkflowBehavior if Workflow is applicable for this model.
			if (!empty($setup)) {
				$workflowId = $this->getWorkflow($setup);

				if (!empty($workflowId)) {
					$this->model->addBehavior('Workflow.Workflow', [
						'workflowId' => $workflowId
					]);
				}
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

		// Filter key
		list(, $base) = pluginSplit($workflowModel->filter);
		$filterKey = Inflector::underscore(Inflector::singularize($base)) . '_id';

		$paramsPass = $this->ControllerAction->paramsPass();
		$modelReference = current($paramsPass);
		$entity = $this->model->get($modelReference);

		$workflowId = 0;

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
		return $workflowId;
	}
}
