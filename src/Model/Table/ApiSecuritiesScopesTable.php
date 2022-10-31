<?php
namespace App\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class ApiSecuritiesScopesTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('ApiScopes', [
            'className' => 'ApiScopes',
            'foreignKey' => 'api_scope_id'
        ]);

        $this->belongsTo('ApiSecurities', [
            'className' => 'ApiSecurities',
            'foreignKey' => 'api_security_id'
        ]);
    }
}
