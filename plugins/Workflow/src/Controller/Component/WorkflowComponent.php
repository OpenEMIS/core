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
}
