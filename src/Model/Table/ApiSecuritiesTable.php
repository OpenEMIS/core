<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;

class ApiSecuritiesTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsToMany('ApiCredentials', [
            'through' => 'ApiSecuritiesCredentials',
            'foreignKey' => 'api_security_id',
            'targetForeignKey' => 'api_credential_id'
        ]);
    }

    public function findOptionList(Query $query, array $options)
    {
        $ApiSecuritiesScopes = TableRegistry::get('ApiSecuritiesScopes');

        if (!empty($options['querystring'])) {
            $apiScopeId = $options['querystring']['api_scope_id'];
        }

        if (!empty($apiScopeId)) {
            $query
                ->leftJoin(
                    [$ApiSecuritiesScopes->alias() => $ApiSecuritiesScopes->table()],
                    [
                        [$this->aliasField('id = ') . $ApiSecuritiesScopes->aliasField('api_security_id')],
                        [$ApiSecuritiesScopes->aliasField('api_scope_id = ') . $apiScopeId]
                    ]
                )
                ->where(
                    [$ApiSecuritiesScopes->aliasField('api_security_id') . ' IS NULL']
                );
        } else {
            $query
                ->where('1 = 0');
        }
        

        return parent::findOptionList($query, $options);
    }
}
