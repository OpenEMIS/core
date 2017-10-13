<?php
namespace Transport\Model\Table;

use App\Model\Table\ControllerActionTable;

class BusTypesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('bus_types');
        parent::initialize($config);

        $this->hasMany('Buses', ['className' => 'Transport.Buses']);

        $this->addBehavior('FieldOption.FieldOption');
        $this->setDeleteStrategy('restrict');
    }
}
