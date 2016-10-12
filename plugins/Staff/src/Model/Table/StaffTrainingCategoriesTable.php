<?php
namespace Staff\Model\Table;

use App\Model\Table\ControllerActionTable;

class StaffTrainingCategoriesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('staff_training_categories');
        parent::initialize($config);

        $this->hasMany('StaffTrainings', ['className' => 'Staff.StaffTrainings', 'foreignKey' => 'staff_training_category_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }
}
