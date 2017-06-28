<?php
namespace SSO\Model\Table;

use Cake\ORM\Table;
use Cake\ORM\Entity;
use ArrayObject;

class AuthenticationTypesTable extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->hasMany('SystemAuthentications', ['className' => 'SSO.SystemAuthentications']);
    }

    public function getId($authenticationName)
    {
        return $this
            ->find()
            ->select([
                $this->aliasField('id')
            ])
            ->where([
                $this->aliasField('name') => $authenticationName
            ])
            ->first()
            ->id;
    }
}
