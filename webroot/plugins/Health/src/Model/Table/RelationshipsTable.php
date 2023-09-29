<?php
namespace Health\Model\Table;

use App\Model\Table\ControllerActionTable;

class RelationshipsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('health_relationships');
        parent::initialize($config);

        $this->hasMany('Families', ['className' => 'Health.Families', 'foreignKey' => 'health_relationship_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }
}
