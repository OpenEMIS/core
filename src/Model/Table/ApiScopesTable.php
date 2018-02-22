<?php
namespace App\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class ApiScopesTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsToMany('ApiCredentials', [
            'className' => 'ApiCredentials',
            'joinTable' => 'api_credentials_scopes',
            'foreignKey' => 'api_scope_id',
            'targetForeignKey' => 'api_credential_id',
            'through' => 'ApiCredentialsScopes',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
    }
}
