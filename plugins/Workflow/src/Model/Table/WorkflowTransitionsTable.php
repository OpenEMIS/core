<?php
namespace Workflow\Model\Table;

use App\Model\Table\AppTable;

class WorkflowTransitionsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('PreviousWorkflowSteps', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'prev_workflow_step_id']);
		$this->belongsTo('WorkflowSteps', ['className' => 'Workflow.WorkflowSteps']);
		$this->belongsTo('WorkflowActions', ['className' => 'Workflow.WorkflowActions']);
		$this->belongsTo('WorkflowRecords', ['className' => 'Workflow.WorkflowRecords']);
	}
}
