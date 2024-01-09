<?php
namespace Meal\Model\Table;
//POCOR-7366
use App\Model\Table\ControllerActionTable;

class MealRatingsTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('meal_ratings');
        parent::initialize($config);        
        $this->addBehavior('FieldOption.FieldOption');
       
    }
}
