<?php
namespace Transport\Model\Table;

use App\Model\Table\ControllerActionTable;

class TransportStatusesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('transport_statuses');
        parent::initialize($config);

        $this->hasMany('Buses', ['className' => 'Transport.Buses']);

        $this->addBehavior('FieldOption.FieldOption');
        $this->setDeleteStrategy('restrict');
    }
}
