<?php
namespace FieldOption\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Validation\Validator;

class LeaveStatusesTable extends ControllerActionTable {
	public function initialize(array $config)
    {
		$this->addBehavior('FieldOption.FieldOption');
		parent::initialize($config);
		$this->hasMany('Leaves', ['className' => 'Staff.Leaves', 'foreignKey' => 'leave_status_id']);
	}
}
