<?php
namespace Workflow\Model\Table;

use App\Model\Table\AppTable;

class WorkflowActionsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('WorkflowSteps', ['className' => 'Workflow.WorkflowSteps']);
		$this->belongsTo('NextWorkflowSteps', [
			'className' => 'Workflow.WorkflowSteps',
			'foreignKey' => 'next_workflow_step_id'
		]);
	}
}
