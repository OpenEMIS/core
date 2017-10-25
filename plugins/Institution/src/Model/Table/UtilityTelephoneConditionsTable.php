<?php
namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Validation\Validator;

class UtilityTelephoneConditionsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('utility_telephone_conditions');
        parent::initialize($config);

        $this->hasMany('InfrastructureUtilityTelephones', ['className' => 'Institution.InfrastructureUtilityTelephones', 'foreignKey' => 'utility_telephone_condition_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }
}
