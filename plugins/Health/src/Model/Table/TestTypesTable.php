<?php
namespace Health\Model\Table;

use App\Model\Table\ControllerActionTable;

class TestTypesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('health_test_types');
        parent::initialize($config);

        $this->hasMany('Tests', ['className' => 'Health.Tests', 'foreignKey' => 'health_test_type_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }
}
