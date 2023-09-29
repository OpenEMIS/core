<?php
namespace Staff\Model\Table;

use App\Model\Table\ControllerActionTable;

class SalaryDeductionTypesTable extends ControllerActionTable {
    public function initialize(array $config)
    {
        $this->table('salary_deduction_types');
        parent::initialize($config);

        $this->hasMany('StaffSalaryDeductions', ['className' => 'Staff.SalaryDeductions', 'foreignKey' => 'salary_deduction_type_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }
}
