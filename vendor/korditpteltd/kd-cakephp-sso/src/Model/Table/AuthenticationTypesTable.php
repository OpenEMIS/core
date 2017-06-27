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
}
