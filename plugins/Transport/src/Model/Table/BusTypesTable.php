<?php
namespace Transport\Model\Table;

use App\Model\Table\ControllerActionTable;

class BusTypesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('bus_types');
        parent::initialize($config);

        $this->hasMany('InstitutionBuses', ['className' => 'Institution.InstitutionBuses', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->addBehavior('FieldOption.FieldOption');
        $this->setDeleteStrategy('restrict');
    }
}
