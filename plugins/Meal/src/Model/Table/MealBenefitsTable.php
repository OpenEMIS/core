<?php
namespace Meal\Model\Table;

use App\Model\Table\ControllerActionTable;

class MealBenefitsTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('meal_benefits');
        parent::initialize($config);        
        $this->addBehavior('FieldOption.FieldOption');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'StudentMeals' => ['index', 'view']
        ]);
    }
}
