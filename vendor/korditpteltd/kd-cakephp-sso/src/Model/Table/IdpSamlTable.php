<?php
namespace SSO\Model\Table;

use Cake\ORM\Table;
use Cake\ORM\Entity;
use ArrayObject;

class IdpSamlTable extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('SystemAuthentications', ['className' => 'SSO.SystemAuthentications']);
    }

    public function getAttributes($systemAuthenticationId)
    {
        return $this
            ->get($systemAuthenticationId, ['contain' => 'SystemAuthentications'])
            ->toArray();
    }
}
