<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;

class InstitutionCasesRecordsTable extends AppTable
{
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('InstitutionCases', ['className' => 'Institution.InstitutionCases']);
		$this->belongsTo('StaffBehaviours', ['className' => 'Institution.StaffBehaviours']);
	}
}
