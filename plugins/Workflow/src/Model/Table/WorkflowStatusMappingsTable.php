<?php
namespace Workflow\Model\Table;

use App\Model\Table\AppTable;

class WorkflowStatusMappingTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongTo('WorkflowStatuses', ['className' => 'Workflow.WorkflowStatuses']);
		$this->belongTo('WorkflowSteps', ['className' => 'Workflow.WorkflowSteps']);
	}
}
