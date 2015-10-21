<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class LeaveStatusesTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		parent::initialize($config);
		$this->hasMany('Leaves', ['className' => 'Staff.Leaves', 'foreignKey' => 'leave_status_id']);
	}
}
