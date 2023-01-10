<?php
namespace Meal\Model\Table;

use App\Model\Table\ControllerActionTable;

class MealImplementerTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('meal_implementers');
        parent::initialize($config);        
        $this->addBehavior('FieldOption.FieldOption');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'StudentMeals' => ['index', 'view']
        ]);
    }
}
