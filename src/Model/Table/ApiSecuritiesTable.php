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
        $ApiSecuritiesCredentials = TableRegistry::get('ApiSecuritiesCredentials');

        if (!empty($options['querystring'])) {
            $apiCredentialId = $options['querystring']['api_credential_id'];
        }

        if (!empty($apiCredentialId)) {
            $query
                ->leftJoin(
                    [$ApiSecuritiesCredentials->alias() => $ApiSecuritiesCredentials->table()],
                    [
                        [$this->aliasField('id = ') . $ApiSecuritiesCredentials->aliasField('api_security_id')],
                        [$ApiSecuritiesCredentials->aliasField('api_credential_id = ') . $apiCredentialId]
                    ]
                )
                ->where(
                    [$ApiSecuritiesCredentials->aliasField('api_security_id') . ' IS NULL']
                );
        } else {
            $query
                ->where('1 = 0');
        }
        

        return parent::findOptionList($query, $options);
    }
}
