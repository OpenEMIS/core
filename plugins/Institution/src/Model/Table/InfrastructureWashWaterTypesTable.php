<?php
namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Validation\Validator;

class InfrastructureWashWaterTypesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('infrastructure_wash_water_types');
        parent::initialize($config);

        $this->hasMany('InfrastructureWashWaters', ['className' => 'Institution.InfrastructureWashWaters', 'foreignKey' => 'infrastructure_wash_water_type_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }
}
