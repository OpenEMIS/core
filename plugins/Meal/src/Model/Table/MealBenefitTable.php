<?php
namespace Meal\Model\Table;

use App\Model\Table\ControllerActionTable;

class MealBenefitTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('meal_benefits');
        parent::initialize($config);        
        $this->addBehavior('FieldOption.FieldOption');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'OpenEMIS_Classroom' => ['index'],
            'StudentMeals' => ['index', 'view']
        ]);
    }
}
