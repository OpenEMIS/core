<?php
namespace Transport\Model\Table;

use App\Model\Table\AppTable;

class TransportStatusesTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('transport_statuses');
        parent::initialize($config);

        $this->hasMany('InstitutionBuses', ['className' => 'Institution.InstitutionBuses', 'dependent' => true, 'cascadeCallbacks' => true]);
    }
}
