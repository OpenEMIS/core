<?php
namespace Transport\Model\Table;

use App\Model\Table\AppTable;

class TransportStatusesTable extends AppTable
{
	const OPERATING = 1;
	const NOT_OPERATING = 2;

    public function initialize(array $config)
    {
        $this->table('transport_statuses');
        parent::initialize($config);

        $this->hasMany('InstitutionBuses', ['className' => 'Institution.InstitutionBuses', 'dependent' => true, 'cascadeCallbacks' => true]);
    }
}
