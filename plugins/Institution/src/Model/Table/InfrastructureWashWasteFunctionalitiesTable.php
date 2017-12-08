<?php
namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Validation\Validator;

class InfrastructureWashWasteFunctionalitiesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('infrastructure_wash_waste_functionalities');
        parent::initialize($config);

        $this->hasMany('InfrastructureWashWastes', ['className' => 'Institution.InfrastructureWashWastes', 'foreignKey' => 'infrastructure_wash_waste_functionality_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }
}
