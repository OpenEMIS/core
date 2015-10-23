<?php
namespace Workflow\Model\Table;

use App\Model\Table\AppTable;

class WorkflowStatusesMappingTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongTo('WorkflowStatus', ['className' => 'Workflow.WorkflowStatus']);
		$this->belongTo('WorkflowSteps', ['className' => 'Workflow.WorkflowSteps']);
	}
}
