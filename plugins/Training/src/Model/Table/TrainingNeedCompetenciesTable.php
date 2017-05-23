<?php
namespace Training\Model\Table;

use App\Model\Table\ControllerActionTable;

class TrainingNeedCompetenciesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        // $this->hasMany('TrainingNeeds', ['className' => 'Staff.TrainingNeeds', 'foreignKey' => 'training_need_competency_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }
}
