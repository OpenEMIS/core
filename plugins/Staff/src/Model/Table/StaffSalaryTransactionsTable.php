<?php
namespace Staff\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\Event\EventInterface;
use Cake\ORM\Query;

class StaffSalaryTransactionsTable extends AppTable {
	public function initialize(array $config): void {
		$this->setTable('staff_salary_transactions');
		parent::initialize($config);

		$this->belongsTo('SalaryAdditionTypes', ['className' => 'Staff.SalaryAdditionTypes']);
		$this->belongsTo('SalaryDeductionTypes', ['className' => 'Staff.SalaryDeductionTypes']);
		$this->belongsTo('Salaries', ['className' => 'Staff.Salaries']);
	}

	//POCOR-9584: Log all queries executed on this table to debug condition issues
	public function beforeFind(EventInterface $event, Query $query): void
	{
		error_log('[POCOR-9584] StaffSalaryTransactions beforeFind - SQL: ' . $query->sql());
	}


	public function validationDefault(Validator $validator): Validator {
		$validator = parent::validationDefault($validator);

		return $validator
			->add('salary_addition_type_id', [
			])
			->add('salary_deduction_type_id', [
			])
			//POCOR-9584: Validate 'amount' field - same field is used for both additions and deductions
			->add('amount', 'ruleMoney',  [
				'rule' => ['money']
			])
		;
	}
}