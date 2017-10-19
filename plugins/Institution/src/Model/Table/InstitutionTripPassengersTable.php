<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;

class InstitutionTripPassengersTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('InstitutionTrips', ['className' => 'Institution.InstitutionTrips']);
    }
}
