<?php
namespace Staff\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\Event\Event;

class EmploymentsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('staff_employments');
		parent::initialize($config);
		
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('EmploymentTypes', ['className' => 'FieldOption.EmploymentTypes']);
	}

	public function beforeAction() {
		$this->fields['employment_type_id']['type'] = 'select';

		if (strtolower($this->action) != 'index') {
			$this->Navigation->addCrumb($this->getHeader($this->action));
		}
	}
	
	public function indexBeforeAction(Event $event) {
		$order = 0;
		$this->ControllerAction->setFieldOrder('employment_type_id', $order++);
		$this->ControllerAction->setFieldOrder('employment_date', $order++);
		$this->ControllerAction->setFieldOrder('comment', $order++);
	}

	public function addEditBeforeAction(Event $event) {
		$order = 0;
		$this->ControllerAction->setFieldOrder('employment_type_id', $order++);
		$this->ControllerAction->setFieldOrder('employment_date', $order++);
		$this->ControllerAction->setFieldOrder('comment', $order++);
	}

	public function validationDefault(Validator $validator) {
		return $validator;
	}
}
