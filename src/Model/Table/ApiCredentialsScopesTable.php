<?php
namespace App\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class ApiCredentialsScopesTable extends AppTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->belongsTo('ApiCredentials', [
            'className' => 'ApiCredentials',
            'foreignKey' => 'api_credential_id'
        ]);

        $this->belongsTo('ApiScopes', [
            'className' => 'ApiScopes',
            'foreignKey' => 'api_scope_id'
        ]);
    }
}
