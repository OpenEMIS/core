<?php
namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Validation\Validator;

class UtilityElectricityTypesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('utility_electricity_types');
        parent::initialize($config);

        $this->hasMany('InfrastructureUtilityElectricities', ['className' => 'Institution.InfrastructureUtilityElectricities', 'foreignKey' => 'utility_electricity_type_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }
}
