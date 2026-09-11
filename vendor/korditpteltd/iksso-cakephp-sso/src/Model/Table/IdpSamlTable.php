<?php
namespace SSO\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class IdpSamlTable extends Table
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
            ->requirePresence('idp_entity_id')
            ->notEmptyString('idp_entity_id')
            ->requirePresence('idp_sso')
            ->notEmptyString('idp_sso')
            ->requirePresence('idp_slo')
            ->notEmptyString('idp_slo')
            ->requirePresence('idp_x509cert')
            ->notEmptyString('idp_x509cert')
            ->requirePresence('sp_entity_id')
            ->notEmptyString('sp_entity_id')
            ->requirePresence('sp_acs')
            ->notEmptyString('sp_acs')
            ->requirePresence('sp_slo')
            ->notEmptyString('sp_slo');
    }
}