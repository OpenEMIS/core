<?php
namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;

class InfrastructureWashSanitationQuantitiesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('InfrastructureWashSanitations', ['className' => 'Institution.InfrastructureWashSanitations']);
    }
}