<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class StaffSalaryTransactionsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('staff_salary_transactions');
		parent::initialize($config);
		
		$this->belongsTo('SalaryAdditionTypes', ['className' => 'Staff.SalaryAdditionTypes']);
		$this->belongsTo('SalaryDeductionTypes', ['className' => 'Staff.SalaryDeductionTypes']);
		$this->belongsTo('Salaries', ['className' => 'Staff.Salaries']);
	}


	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);

		return $validator
			->add('salary_addition_type_id', [
			])
			->add('addition_amount', 'ruleMoney',  [
				'rule' => ['money']
			])
			->add('salary_deduction_type_id', [
			])
			->add('deduction_amount', 'ruleMoney',  [
				'rule' => ['money']
			])
		;
	}
}