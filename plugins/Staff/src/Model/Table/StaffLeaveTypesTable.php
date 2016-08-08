<?php
namespace Staff\Model\Table;

use App\Model\Table\ControllerActionTable;

class StaffLeaveTypesTable extends ControllerActionTable {
    public function initialize(array $config)
    {
        $this->addBehavior('FieldOption.FieldOption');
        $this->table('staff_leave_types');
        parent::initialize($config);

        $this->hasMany('Leaves', ['className' => 'Staff.Leaves']);

        $this->behaviors()->get('ControllerAction')->config('actions.remove', 'transfer');
    }
}
