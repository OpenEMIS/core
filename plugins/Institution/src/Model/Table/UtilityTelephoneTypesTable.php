<?php
namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Validation\Validator;

class UtilityTelephoneTypesTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('utility_telephone_types');
        parent::initialize($config);

        $this->hasMany('InfrastructureUtilityTelephones', ['className' => 'Institution.InfrastructureUtilityTelephones', 'foreignKey' => 'utility_telephone_type_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }
}
