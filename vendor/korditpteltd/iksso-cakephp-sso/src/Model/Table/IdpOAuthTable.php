<?php
namespace SSO\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class IdpOauthTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->hasOne('SystemAuthentications', [
            'className' => 'SSO.SystemAuthentications',
            'foreignKey' => 'id',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        return $validator
            ->requirePresence('client_id')
            ->notEmptyString('client_id')
            ->requirePresence('client_secret')
            ->notEmptyString('client_secret')
            ->requirePresence('redirect_uri')
            ->notEmptyString('redirect_uri')
            ->requirePresence('authorization_endpoint')
            ->notEmptyString('authorization_endpoint')
            ->requirePresence('token_endpoint')
            ->notEmptyString('token_endpoint')
            ->requirePresence('userinfo_endpoint')
            ->notEmptyString('userinfo_endpoint')
            ->requirePresence('issuer')
            ->notEmptyString('issuer')
            ->requirePresence('jwks_uri')
            ->notEmptyString('jwks_uri');
    }
}