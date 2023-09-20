<?php
namespace Meal\Model\Table;
//POCOR-7363
use App\Model\Table\ControllerActionTable;

class MealStatusTypesTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
    }
}
