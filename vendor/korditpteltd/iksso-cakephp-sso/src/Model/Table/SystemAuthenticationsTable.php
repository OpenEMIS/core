<?php
namespace SSO\Model\Table;

use Cake\ORM\Table;
use Cake\ORM\Entity;
use ArrayObject;

class SystemAuthenticationsTable extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->hasOne('Google', ['className' => 'SSO.IdpGoogle', 'foreignKey' => 'system_authentication_id']);
        $this->hasOne('Saml', ['className' => 'SSO.IdpSaml', 'foreignKey' => 'system_authentication_id']);
        $this->hasOne('OAuth', ['className' => 'SSO.IdpOauth', 'foreignKey' => 'system_authentication_id']);
        $this->belongsTo('AuthenticationTypes', ['className' => 'SSO.AuthenticationTypes']);
    }

    public function getActiveAuthentications()
    {
        return $this
            ->find()
            ->innerJoinWith('AuthenticationTypes')
            ->where([$this->aliasField('status') => 1])
            ->select(['authentication_type' => 'AuthenticationTypes.name'])
            ->autoFields(true)
            ->hydrate(false)
            ->toArray();
    }
}
