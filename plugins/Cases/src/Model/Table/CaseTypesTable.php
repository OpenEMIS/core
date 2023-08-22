<?php

namespace Cases\Model\Table;
//POCOR-7313
use App\Model\Table\ControllerActionTable;

class CaseTypesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->addBehavior('FieldOption.FieldOption');
    }
}
