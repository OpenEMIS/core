<?php
namespace FieldOption\Model\Table;

use App\Model\Table\ControllerActionTable;

class FieldTypesTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('field_types');
        parent::initialize($config);
    }
}
