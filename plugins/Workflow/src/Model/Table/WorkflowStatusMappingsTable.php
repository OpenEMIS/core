<?php
namespace Workflow\Model\Table;

use App\Model\Table\AppTable;

class WorkflowStatusMappingsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('WorkflowStatuses', ['className' => 'Workflow.WorkflowStatuses']);
		$this->belongsTo('WorkflowSteps', ['className' => 'Workflow.WorkflowSteps']);
	}

	public function getWorkflowSteps($workflowStatusId) {
		return $this
			->find('list', [
				'keyField' => 'id',
				'valueField' => 'id'
			])
			->where([$this->aliasField('workflow_status_id') => $workflowStatusId])
			->select(['id' => $this->aliasField('workflow_step_id')])
			->toArray();
	}
}
