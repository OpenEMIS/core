<?php
namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Validation\Validator;

class InfrastructureWashHygieneTypesTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('infrastructure_wash_hygiene_types');
        parent::initialize($config);

        $this->hasMany('InfrastructureWashHygienes', ['className' => 'Institution.InfrastructureWashHygienes', 'foreignKey' => 'infrastructure_wash_hygiene_type_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }
}
