<?php
namespace Staff\Model\Table;

use App\Model\Table\ControllerActionTable;

class GuidanceTypesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('guidance_types');
        parent::initialize($config);

        $this->hasMany('Counselors', ['className' => 'Counselors']);

        $this->addBehavior('FieldOption.FieldOption');
        $this->setDeleteStrategy('restrict');
    }
}
