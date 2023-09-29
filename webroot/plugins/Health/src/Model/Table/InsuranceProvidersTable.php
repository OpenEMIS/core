<?php
namespace Health\Model\Table;

use App\Model\Table\ControllerActionTable;

class InsuranceProvidersTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('insurance_providers');
        parent::initialize($config);

        $this->hasMany('UserInsurances', ['className' => 'User.UserInsurances', 'foreignKey' => 'insurance_provider_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }
}
