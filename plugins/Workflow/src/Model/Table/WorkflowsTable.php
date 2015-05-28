<?php
namespace Workflow\Model\Table;

use App\Model\Table\AppTable;

class WorkflowsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('WorkflowModels', ['className' => 'Workflow.WorkflowModels']);
		$this->hasMany('WorkflowSteps', ['className' => 'Workflow.WorkflowSteps']);
	}
}
