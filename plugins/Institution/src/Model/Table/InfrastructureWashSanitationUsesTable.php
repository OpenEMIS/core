<?php
namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Validation\Validator;

class InfrastructureWashSanitationUsesTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('infrastructure_wash_sanitation_uses');
        parent::initialize($config);

        $this->hasMany('InfrastructureWashSanitations', ['className' => 'Institution.InfrastructureWashSanitations', 'foreignKey' => 'infrastructure_wash_sanitation_use_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }
}