<?php
namespace User\Model\Table;

use ArrayObject;

use Cake\Validation\Validator;

use App\Model\Table\AppTable;

class UserInsurancesTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('user_insurances');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'security_user_id']);
        $this->belongsTo('InsuranceProviders', ['className' => 'Health.InsuranceProviders', 'foreignKey' => 'insurance_provider_id']);
        $this->belongsTo('InsuranceTypes', ['className' => 'Health.InsuranceTypes', 'foreignKey' => 'insurance_type_id']);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('start_date', 'ruleCompareDate', [
                'rule' => ['compareDate', 'end_date', true]
            ])
        ;
    }
}
