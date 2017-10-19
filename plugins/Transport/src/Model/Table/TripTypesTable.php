<?php
namespace Transport\Model\Table;

use App\Model\Table\ControllerActionTable;

class TripTypesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('trip_types');
        parent::initialize($config);

        $this->hasMany('InstitutionTrips', ['className' => 'Institution.InstitutionTrips', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->addBehavior('FieldOption.FieldOption');
        $this->setDeleteStrategy('restrict');
    }
}
