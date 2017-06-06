<?php
namespace Health\Model\Table;

use Cake\Validation\Validator;

use App\Model\Table\ControllerActionTable;

class MedicationsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('user_health_medications');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);

        $this->addBehavior('Health.Health');
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->allowEmpty('end_date')
            ->add('end_date', 'ruleCompareDateReverse', [
                'rule' => ['compareDateReverse', 'start_date', true]
            ]);
    }
}
