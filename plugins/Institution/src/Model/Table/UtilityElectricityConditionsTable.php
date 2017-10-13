<?php
namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Validation\Validator;

class UtilityElectricityConditionsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('utility_electricity_conditions');
        parent::initialize($config);

        $this->hasMany('InfrastructureUtilityElectricities', ['className' => 'Institution.InfrastructureUtilityElectricities', 'foreignKey' => 'utility_electricity_condition_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }
}
