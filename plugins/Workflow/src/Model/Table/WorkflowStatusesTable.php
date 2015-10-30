<?php
namespace Workflow\Model\Table;

use App\Model\Table\AppTable;

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
}
