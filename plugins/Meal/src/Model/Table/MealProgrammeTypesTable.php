<?php
namespace Meal\Model\Table;
//POCOR-7363
use App\Model\Table\ControllerActionTable;

class MealProgrammeTypesTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('meal_programme_types');
        parent::initialize($config);        
        $this->addBehavior('FieldOption.FieldOption');
       
    }
}
