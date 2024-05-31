<?php
namespace SSO\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class IdpSamlTable extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->hasOne('SystemAuthentications', ['className' => 'SSO.SystemAuthentications', 'foreignKey' => 'id']);
    }

    public function validationDefault(Validator $validator)
    {
        return $validator
            ->requirePresence('idp_entity_id')
            ->notEmpty('idp_entity_id')
            ->requirePresence('idp_sso')
            ->notEmpty('idp_sso_binding')
            ->requirePresence('idp_slo')
            ->notEmpty('idp_slo_binding')
            ->requirePresence('idp_x509cert')
            ->notEmpty('idp_x509cert')
            ->requirePresence('sp_entity_id')
            ->notEmpty('sp_entity_id')
            ->requirePresence('sp_acs')
            ->notEmpty('sp_acs')
            ->requirePresence('sp_slo')
            ->notEmpty('sp_slo');
    }
}
