<?php
namespace Staff\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class LeavesTable extends AppTable {
	public function initialize(array $config) {
		$this->table('staff_leaves');
		parent::initialize($config);

		$this->belongsTo('StaffLeaveTypes', ['className' => 'Staff.StaffLeaveTypes']);
		$this->belongsTo('LeaveStatuses', ['className' => 'Staff.LeaveStatuses']);	
	}

	public function validationDefault(Validator $validator) {
		return $validator;
	}

}
