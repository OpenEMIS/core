<?php
namespace FieldOption\Model\Table;

use App\Model\Table\ControllerActionTable;

class FieldOptionsTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('field_options');
        parent::initialize($config);
    }
}
