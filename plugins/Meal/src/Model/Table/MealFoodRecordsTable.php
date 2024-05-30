<?php
namespace Meal\Model\Table;
//POCOR-7363
use App\Model\Table\ControllerActionTable;

class MealFoodRecordsTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('meal_food_records');
        parent::initialize($config);        
       
    }
}