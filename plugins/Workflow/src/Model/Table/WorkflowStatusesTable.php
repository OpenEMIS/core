<?php
namespace Workflow\Model\Table;

use App\Model\Table\AppTable;

class WorkflowStatusesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('WorkflowModel', ['className' => 'Workflow.WorkflowModel']);
		$this->hasMany('WorkflowStatusMappings', ['className' => 'Workflow.WorkflowStatusMappings', 'dependent' => true, 'cascadeCallbacks' => true]);
	}
}
