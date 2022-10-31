<?php
namespace Health\Model\Table;

use App\Model\Table\ControllerActionTable;

class InsuranceTypesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('insurance_types');
        parent::initialize($config);

        $this->hasMany('UserInsurances', ['className' => 'User.UserInsurances', 'foreignKey' => 'insurance_type_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }
}


