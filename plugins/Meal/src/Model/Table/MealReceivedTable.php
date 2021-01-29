<?php
namespace Meal\Model\Table;

use App\Model\Table\ControllerActionTable;

class MealReceivedTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('meal_received');
        parent::initialize($config);        
        //$this->addBehavior('FieldOption.FieldOption');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'StudentMeals' => ['index', 'view']
        ]);
    }
}
