<?php
namespace Staff\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class SalaryDeductionsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('staff_salary_deductions');
		parent::initialize($config);
		
		$this->belongsTo('SalaryDeductionTypes', ['className' => 'Staff.SalaryDeductionTypes', 'foreignKey' => 'salary_deduction_type_id']);
		$this->belongsTo('Salaries', ['className' => 'Staff.Salaries']);
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);

		return $validator
			->add('salary_deduction_type_id', [
			])
			->add('deduction_amount', 'ruleMoney',  [
				'rule' => ['money']
			])
		;
	}

}
