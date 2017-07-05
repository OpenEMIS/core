<?php
namespace SSO\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class IdpGoogleTable extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->hasOne('SystemAuthentications', ['className' => 'SSO.SystemAuthentications', 'foreignKey' => 'id']);
    }

    public function validationDefault(Validator $validator)
    {
        return $validator
            ->requirePresence('client_id')
            ->notEmpty('client_id')
            ->requirePresence('client_secret')
            ->notEmpty('client_secret')
            ->requirePresence('redirect_uri')
            ->notEmpty('redirect_uri');
    }
}
