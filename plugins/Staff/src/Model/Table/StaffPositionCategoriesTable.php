<?php
namespace Staff\Model\Table;

use App\Model\Table\ControllerActionTable;

class StaffPositionCategoriesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->addBehavior('FieldOption.FieldOption');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'StaffPositionCategories' => ['index', 'view']
        ]);
    }
}