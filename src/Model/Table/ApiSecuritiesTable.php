<?php
namespace App\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;

class ApiSecuritiesTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsToMany('ApiScopes', [
            'className' => 'ApiScopes',
            'joinTable' => 'api_securities_scopes',
            'foreignKey' => 'api_security_id',
            'targetForeignKey' => 'api_scope_id',
            'through' => 'ApiSecuritiesScopes'
        ]);
    }

    public function findIndex(Query $query, array $options)
    {
        $query->contain(['ApiScopes']);
        return $query;
    }

    public function findView(Query $query, array $options)
    {
        $query->contain(['ApiScopes']);
        return $query;
    }

    public function findEdit(Query $query, array $options)
    {
        $query->contain(['ApiScopes']);
        return $query;
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $tempScopeName = 'scopes';

        $apiSecuritiesScopes = TableRegistry::get('ApiSecuritiesScopes');
        $entity->{$tempScopeName}['api_security_id'] = $entity->id;

        $scopeEntity = $apiSecuritiesScopes->newEntity($entity->{$tempScopeName});
        $apiSecuritiesScopes->save($scopeEntity);
    }
}
