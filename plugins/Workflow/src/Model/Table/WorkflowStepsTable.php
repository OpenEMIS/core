<?php
namespace Workflow\Model\Table;

use App\Model\Table\AppTable;

class WorkflowStepsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('Workflows', ['className' => 'Workflow.Workflows']);
		$this->hasMany('WorkflowActions', ['className' => 'Workflow.WorkflowActions']);
	}
}
