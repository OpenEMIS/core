<?php
namespace Staff\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\Event\Event;

class LeavesTable extends AppTable {
	public function initialize(array $config) {
		$this->table('staff_leaves');
		parent::initialize($config);

		$this->belongsTo('StaffLeaveTypes', ['className' => 'FieldOption.StaffLeaveTypes']);
		$this->belongsTo('LeaveStatuses', ['className' => 'FieldOption.LeaveStatuses']);	
	}

	public function beforeAction(Event $event) {
		$this->fields['staff_leave_type_id']['type'] = 'select';
		$this->fields['leave_status_id']['type'] = 'select';

		$order = 0;
		$this->ControllerAction->setFieldOrder('staff_leave_type_id', $order++);
		$this->ControllerAction->setFieldOrder('leave_status_id', $order++);
		$this->ControllerAction->setFieldOrder('date_from', $order++);
		$this->ControllerAction->setFieldOrder('date_to', $order++);
		$this->ControllerAction->setFieldOrder('number_of_days', $order++);
		$this->ControllerAction->setFieldOrder('comments', $order++);
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);
		
		return $validator->add('date_from', 'ruleCompareDate', [
				'rule' => ['compareDate', 'date_to', false]
			])
			->add('date_to', [
			])
		;
	}
}
