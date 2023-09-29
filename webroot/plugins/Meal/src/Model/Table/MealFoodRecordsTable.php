<?php
namespace Meal\Model\Table;
//POCOR-7363
use App\Model\Table\ControllerActionTable;

class MealFooRecordsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('meal_food_records');
        parent::initialize($config);        
       
    }
}