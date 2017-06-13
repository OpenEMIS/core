<?php
namespace Training\Model\Table;

use App\Model\Table\ControllerActionTable;

class TrainingNeedCompetenciesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->hasMany('TrainingNeeds', ['className' => 'Staff.TrainingNeeds']);
        
        $this->addBehavior('FieldOption.FieldOption');

        $this->setDeleteStrategy('restrict');
    }
}
