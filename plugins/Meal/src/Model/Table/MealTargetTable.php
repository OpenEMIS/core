<?php
namespace Meal\Model\Table;

use App\Model\Table\ControllerActionTable;

class MealTargetTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('meal_target_types');
        parent::initialize($config);        
        $this->addBehavior('FieldOption.FieldOption');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'StudentMeals' => ['index', 'view']
        ]);
    }
}
