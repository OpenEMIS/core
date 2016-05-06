<?php
namespace Workflow\Model\Table;

use App\Model\Table\AppTable;

class WorkflowStatusesStepsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('WorkflowStatuses', ['className' => 'Workflow.WorkflowStatuses']);
		$this->belongsTo('WorkflowSteps', ['className' => 'Workflow.WorkflowSteps']);
	}
}
