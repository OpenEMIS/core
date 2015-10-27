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

	public $WorkflowModels;
	public $attachWorkflow = false;	// indicate whether the model require workflow
	public $hasWorkflow = false;	// indicate whether workflow is setup
	public $components = ['Auth', 'ControllerAction', 'AccessControl'];

	public function initialize(array $config) {
		$this->controller = $this->_registry->getController();
		$this->action = $this->request->params['action'];

		$this->WorkflowModels = TableRegistry::get('Workflow.WorkflowModels');

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

	/**
	 *	Function to get the list of the workflow statuses base on the model name
	 *
	 *	@param $modelName The name of the model e.g. Institution.InstitutionSurveys
	 *	@return array The list of the workflow statuses
	 */
	public function getWorkflowStatuses($modelName) {
		$WorkflowModelTable = $this->WorkflowModels;
		return $WorkflowModelTable
			->find('list')
			->matching('WorkflowStatuses')
			->where([$WorkflowModelTable->aliasField('model') => $modelName])
			->select(['id' => 'WorkflowStatuses.id', 'name' => 'WorkflowStatuses.name'])
			->toArray();
	}

	/**
	 *	Function to get the list of the workflow steps from the workflow status mappings table
	 *	by a given workflow status
	 *
	 *	@param $workflowStatusId The workflow status id
	 *	@return array The list of the workflow steps
	 */
	public function getWorkflowSteps($workflowStatusId) {
		$WorkflowStatusesStepsTable = $this->WorkflowModels->WorkflowStatuses->WorkflowStatusesSteps;
		return $WorkflowStatusesStepsTable->getWorkflowSteps($workflowStatusId);
	}

	/**
	 *	Function to get the list of the workflow steps and workflow status name mapping
	 *	by a given model id 
	 *
	 *	@param string $modelName The name of the model e.g. Institution.InstitutionSurveys
	 *	@return array The list of workflow steps status name mapping (key => workflow_step_id, value=>workflow_status_name)
	 */
	public function getWorkflowStepStatusNameMappings($modelName) {
		$WorkflowStatusesTable = $this->WorkflowModels->WorkflowStatuses;
		return $WorkflowStatusesTable->getWorkflowStepStatusNameMappings($modelName);
	}
}
