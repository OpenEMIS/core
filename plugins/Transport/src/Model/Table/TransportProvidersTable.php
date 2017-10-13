<?php
namespace Transport\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class TransportProvidersTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->hasMany('Buses', ['className' => 'Transport.Buses']);
    }

	public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

		return $validator
			->add('name', 'ruleUnique', [
				'rule' => 'validateUnique',
				'provider' => 'table'
			]);
    }
}
