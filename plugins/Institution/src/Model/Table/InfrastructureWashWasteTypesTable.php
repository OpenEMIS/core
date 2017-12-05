<?php
namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Validation\Validator;

class InfrastructureWashWasteTypesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('infrastructure_wash_waste_types');
        parent::initialize($config);

        $this->hasMany('InfrastructureWashWastes', ['className' => 'Institution.InfrastructureWashWastes', 'foreignKey' => 'infrastructure_wash_waste_type_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }
}
