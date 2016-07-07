<?php
namespace Workflow\Model\Table;

use App\Model\Table\AppTable;

class WorkflowStepsRolesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('WorkflowSteps', ['className' => 'Workflow.WorkflowSteps']);
		$this->belongsTo('SecurityRoles', ['className' => 'Security.SecurityRoles']);
	}

	public function getRolesByStep($stepId) {
		$roleList = $this
			->find('list', ['keyField' => 'security_role_id', 'valueField' => 'security_role_id'])
			->where([$this->aliasField('workflow_step_id') => $stepId])
			->toArray();

		return $roleList;
	}
}
