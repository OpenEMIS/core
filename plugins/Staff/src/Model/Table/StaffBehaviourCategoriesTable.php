<?php
namespace Staff\Model\Table;

use App\Model\Table\ControllerActionTable;

class StaffBehaviourCategoriesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('staff_behaviour_categories');
        parent::initialize($config);

        $this->hasMany('StaffBehaviours', ['className' => 'Staff.StaffBehaviours', 'foreignKey' => 'staff_behaviour_category_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }
}
