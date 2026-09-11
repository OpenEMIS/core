<?php
namespace SSO\Model\Table;

use Cake\ORM\Table;

class AuthenticationTypesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->hasMany('SystemAuthentications', [
            'className' => 'SSO.SystemAuthentications',
        ]);
    }

    public function getId(string $authenticationName): ?int
    {
        $entity = $this
            ->find()
            ->select([
                $this->aliasField('id'),
            ])
            ->where([
                $this->aliasField('name') => $authenticationName,
            ])
            ->first();

        return $entity ? (int)$entity->id : null;
    }
}