<?php
namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;

class StatusesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('institution_statuses');
        parent::initialize($config);

        $this->hasMany('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_status_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }
}
