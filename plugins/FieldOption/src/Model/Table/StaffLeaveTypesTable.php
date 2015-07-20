<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class StaffLeaveTypesTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		$this->hasMany('Leaves', ['className' => 'Staff.Leaves', 'dependent' => true, 'cascadeCallbacks' => true]);
	}
}
