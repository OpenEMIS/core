<?php
namespace SSO\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class IdpOauthTable extends Table
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
            ->notEmpty('redirect_uri')
            ->requirePresence('authorization_endpoint')
            ->notEmpty('authorization_endpoint')
            ->requirePresence('token_endpoint')
            ->notEmpty('token_endpoint')
            ->requirePresence('userinfo_endpoint')
            ->notEmpty('userinfo_endpoint')
            ->requirePresence('issuer')
            ->notEmpty('issuer')
            ->requirePresence('jwks_uri')
            ->notEmpty('jwks_uri');
    }
}
