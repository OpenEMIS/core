<?php
namespace SSO\Model\Table;

use Cake\ORM\Table;
use Cake\ORM\Entity;
use ArrayObject;

class IdpGoogleTable extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);
    }

    public function getAttributes($systemAuthenticationId)
    {
        return $this
            ->get($systemAuthenticationId)
            ->hydrate(false)
            ->toArray();
    }
}
