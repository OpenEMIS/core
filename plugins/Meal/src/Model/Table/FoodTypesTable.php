<?php
namespace Meal\Model\Table;
//POCOR-7363
use App\Model\Table\ControllerActionTable;

class FoodTypesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('food_types');
        parent::initialize($config);        
        $this->addBehavior('FieldOption.FieldOption');
       
    }
}
