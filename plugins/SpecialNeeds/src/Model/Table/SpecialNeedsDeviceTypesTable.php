<?php
namespace SpecialNeeds\Model\Table;

use App\Model\Table\ControllerActionTable;

class SpecialNeedsDeviceTypesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        // associations - to add
        
        $this->addBehavior('FieldOption.FieldOption');
    }
}
