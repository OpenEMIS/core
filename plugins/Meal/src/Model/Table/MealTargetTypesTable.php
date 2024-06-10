<?php
namespace Meal\Model\Table;

use App\Model\Table\ControllerActionTable;

class MealTargetTypesTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('meal_target_types');
        parent::initialize($config);
    }
}
