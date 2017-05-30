<?php
namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;

class NetworkConnectivitiesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('institution_network_connectivities');
        parent::initialize($config);

        $this->hasMany('Institutions', ['className' => 'Institution.Institutions']);

        $this->addBehavior('FieldOption.FieldOption');
    }
}
