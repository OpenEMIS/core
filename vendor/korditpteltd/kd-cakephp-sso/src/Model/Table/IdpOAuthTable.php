<?php
namespace SSO\Model\Table;

use Cake\ORM\Table;
use Cake\ORM\Entity;
use ArrayObject;

class IdpOauthTable extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->hasOne('SystemAuthentications', ['className' => 'SSO.SystemAuthentications', 'foreignKey' => 'id']);
    }
}
