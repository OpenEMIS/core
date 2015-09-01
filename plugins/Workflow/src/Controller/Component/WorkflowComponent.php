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

	public $components = ['Auth', 'ControllerAction'];

	public function initialize(array $config) {
		$this->controller = $this->_registry->getController();
		$this->action = $this->request->params['action'];
	}

	public function startup(Event $event) {
		$controller = $event->subject();
		$this->model = $this->ControllerAction->model();
		$this->currentAction = $this->ControllerAction->action();

		if (in_array($this->currentAction, ['view', 'processWorkflow'])) {
			$alias = $this->model->alias();
			$registryAlias = $this->model->registryAlias();

			$WorkflowModels = TableRegistry::get('Workflow.WorkflowModels');
			$setup = $WorkflowModels
				->find()
				->where([
					$WorkflowModels->aliasField('model') => $registryAlias
				])
				->first();

			// Trigger WorkflowBehavior if Workflow is applicable for this model.
			if (!empty($setup)) {
				$this->model->addBehavior('Workflow.Workflow', [
					'setup' => $setup
				]);
			}
		}
	}
}
