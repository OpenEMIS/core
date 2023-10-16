<?php
namespace Student\Model\Table;

use App\Model\Table\ControllerActionTable;

class GuidanceTypesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('guidance_types');
        parent::initialize($config);

        $this->hasMany('Counsellings', ['className' => 'Institution.Counsellings']);

        $this->addBehavior('FieldOption.FieldOption');
        $this->setDeleteStrategy('restrict');
    }
}
