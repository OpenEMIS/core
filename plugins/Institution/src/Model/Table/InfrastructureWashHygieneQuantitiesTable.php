<?php
namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;

class InfrastructureWashHygieneQuantitiesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('InfrastructureWashHygienes', ['className' => 'Institution.InfrastructureWashHygienes']);
    }
}