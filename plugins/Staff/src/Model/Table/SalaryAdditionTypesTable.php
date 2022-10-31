<?php
namespace Staff\Model\Table;

use App\Model\Table\ControllerActionTable;

class SalaryAdditionTypesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('salary_addition_types');
        parent::initialize($config);

        $this->hasMany('StaffSalaryAdditions', ['className' => 'Staff.StaffSalaryAdditions', 'foreignKey' => 'salary_addition_type_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }
}
