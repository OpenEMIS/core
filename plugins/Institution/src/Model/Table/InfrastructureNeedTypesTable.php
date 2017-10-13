<?php
namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Validation\Validator;

class InfrastructureNeedTypesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('infrastructure_need_types');
        parent::initialize($config);

        $this->hasMany('InfrastructureNeeds', ['className' => 'Institution.InfrastructureNeeds', 'foreignKey' => 'infrastructure_need_type_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }
}
