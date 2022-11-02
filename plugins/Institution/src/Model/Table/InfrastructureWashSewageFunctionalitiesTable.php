<?php
namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Validation\Validator;

class InfrastructureWashSewageFunctionalitiesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('infrastructure_wash_sewage_functionalities');
        parent::initialize($config);

        $this->hasMany('InfrastructureWashSewages', ['className' => 'Institution.InfrastructureWashSewages', 'foreignKey' => 'infrastructure_wash_sewage_functionality_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }
}