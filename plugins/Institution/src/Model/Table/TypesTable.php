<?php
namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;

class TypesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('institution_types');
        parent::initialize($config);

        $this->hasMany('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_type_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }
}
