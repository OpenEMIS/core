<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;

class InstitutionWorkflowsRecordsTable extends AppTable
{
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('InstitutionWorkflows', ['className' => 'Institution.InstitutionWorkflows']);
		$this->belongsTo('StaffBehaviours', ['className' => 'Institution.StaffBehaviours']);
	}
}
