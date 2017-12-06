<?php
namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Validation\Validator;

class InfrastructureWashSewageTypesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('infrastructure_wash_sewage_types');
        parent::initialize($config);

        $this->hasMany('InfrastructureWashSewages', ['className' => 'Institution.InfrastructureWashSewages', 'foreignKey' => 'infrastructure_wash_sewage_type_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }
}
