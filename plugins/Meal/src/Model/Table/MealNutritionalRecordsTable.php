<?php
namespace Meal\Model\Table;

use App\Model\Table\ControllerActionTable;

class MealNutritionalRecordsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('meal_nutritional_records');
        parent::initialize($config);        
        $this->belongsTo('MealNutritions', ['className' => 'Meal.MealNutritions','foreignKey' => 'nutritional_content_id']);
    }
}
