<?php
namespace Workflow\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Event\Event;
use Cake\ORM\Entity;

class WorkflowStatusesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('WorkflowModels', ['className' => 'Workflow.WorkflowModels']);
		$this->hasMany('WorkflowStatusMappings', ['className' => 'Workflow.WorkflowStatusMappings', 'dependent' => true, 'cascadeCallbacks' => true]);
	}

	public function getWorkflowStepStatusNameMappings($modelName) {
		return $this
			->find('list')
			->matching('WorkflowStatusMappings')
			->matching('WorkflowModels')
			->where(['WorkflowModels.model' => $modelName])
			->select(['id' => 'WorkflowStatusMappings.workflow_step_id', 'name' => $this->aliasField('name')])
			->toArray();
	}

	public function beforeAction(Event $event) {
		$this->ControllerAction->field('name');
	}

	public function editAfterAction(Event $event, Entity $entity) {
		$this->request->data[$this->alias()]['workflow_model_id'] = $entity->workflow_model_id;
		$this->ControllerAction->field('workflow_model_id', ['type' => 'readonly', 'value' => $entity->workflow_model_id]);
	}

	public function addBeforeAction(Event $event) {
		$this->ControllerAction->field('workflow_model_id', ['type' => 'select']);
	}

	public function onUpdateFieldWorkflowModelId(Event $event, array $attr, $action, $request) {
		if ($action == 'edit') {
			$workflowModelId = $this->request->data[$this->alias()]['workflow_model_id'];
			$attr['attr']['value'] = $this->WorkflowModels->get($workflowModelId)->name;
			return $attr;
		}
	}
}
