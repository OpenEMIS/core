<?php
namespace Staff\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class SalaryDeductionsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('staff_salary_deductions');
		parent::initialize($config);
		
		$this->belongsTo('SalaryDeductionTypes', ['className' => 'Staff.SalaryDeductionTypes']);
		$this->belongsTo('Salaries', ['className' => 'Staff.Salaries']);
	}

	public function beforeAction() {
	}

	public function indexBeforeAction(Event $event) {
	}

	public function addEditBeforeAction(Event $event) {
	}

	public function validationDefault(Validator $validator) {
		
	}

}
