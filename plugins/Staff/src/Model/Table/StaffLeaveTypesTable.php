<?php
namespace Staff\Model\Table;

use App\Model\Table\ControllerActionTable;

class StaffLeaveTypesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('staff_leave_types');
        parent::initialize($config);

        $this->hasMany('Leave', ['className' => 'Staff.Leave']);

        $this->addBehavior('FieldOption.FieldOption');
    }
}
