<?php
namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;

class GendersTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('institution_genders');
        parent::initialize($config);

        $this->hasMany('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_gender_id']);

        // $this->addBehavior('FieldOption.FieldOption');
    }
}
