<?php
namespace User\Model\Table;

use User\Model\Table\UsersTable as BaseTable;
use Cake\Event\Event;
use Cake\Validation\Validator;

class AccountsTable extends BaseTable {
	public function initialize(array $config) {
		parent::initialize($config);

		// $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		// $this->belongsTo('BankBranches', ['className' => 'FieldOption.BankBranches']);
	}

	public function editBeforeAction($event)  {
		parent::editBeforeAction($event);

		$fieldsNeeded = ['username', 'password'];
		foreach ($this->fields as $key => $value) {
			if (!in_array($key, $fieldsNeeded)) {
				$this->fields[$key]['visible'] = false;
			} else {
				$this->fields[$key]['visible'] = true;
			}
		}
	}

	public function validationDefault(Validator $validator) {
		parent::validationDefault($validator);
		
		return $validator
			->requirePresence('gender_id', false)
			;
	}

}