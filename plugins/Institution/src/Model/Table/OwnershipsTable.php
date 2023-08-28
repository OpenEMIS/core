<?php
namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;

class OwnershipsTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('institution_ownerships');
        parent::initialize($config);

        $this->hasMany('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_ownership_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }
}
