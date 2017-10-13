<?php
namespace Transport\Model\Table;

use App\Model\Table\AppTable;

class TransportProvidersTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->hasMany('Buses', ['className' => 'Transport.Buses']);
    }
}
