<?php
namespace Meal\Model\Table;

use App\Model\Table\ControllerActionTable;

class MealImplementersTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('meal_implementers');
        parent::initialize($config);
    }
}
