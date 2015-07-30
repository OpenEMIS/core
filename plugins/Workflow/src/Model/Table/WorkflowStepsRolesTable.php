<?php
namespace Workflow\Model\Table;

use App\Model\Table\AppTable;

class WorkflowStepsRolesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('WorkflowSteps', ['className' => 'Workflow.WorkflowSteps']);
		$this->belongsTo('SecurityRoles', ['className' => 'Security.SecurityRoles']);
	}
}
