<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class SalaryDeductionTypesTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		parent::initialize($config);

		$this->hasMany('StaffSalaryAdditions', ['className' => 'Staff.StaffSalaryAdditions']);
	}

	public function validationDefault(Validator $validator) {
		return $validator;
	}
}