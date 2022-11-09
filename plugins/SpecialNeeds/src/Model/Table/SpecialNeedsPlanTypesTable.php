<?php
namespace SpecialNeeds\Model\Table;

use App\Model\Table\ControllerActionTable;

class SpecialNeedsPlanTypesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->addBehavior('FieldOption.FieldOption');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'PlanTypes' => ['index', 'view']
        ]);
    }

    public function getPlanTypeList($params = [])
    {

        $data = $this
            ->find('list')
            ->toArray();
        return $data;
    }
}
