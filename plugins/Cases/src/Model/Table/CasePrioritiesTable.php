<?php

namespace Cases\Model\Table;
//POCOR-7613
use App\Model\Table\ControllerActionTable;

class CasePrioritiesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->addBehavior('FieldOption.FieldOption');
    }
}
