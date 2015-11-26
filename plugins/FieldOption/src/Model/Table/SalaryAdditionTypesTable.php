<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class SalaryAdditionTypesTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		parent::initialize($config);

		$this->hasMany('StaffSalaryAdditions', ['className' => 'Staff.StaffSalaryAdditions', 'foreignKey' => 'salary_addition_type_id']);
	}
}
