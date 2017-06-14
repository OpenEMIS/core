<?php
namespace Training\Model\Table;

use App\Model\Table\ControllerActionTable;

class TrainingNeedStandardsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->hasMany('TrainingNeedSubStandards', ['className' => 'Training.TrainingNeedSubStandards', 'foreignKey' => 'training_need_standard_id']);

        $this->addBehavior('FieldOption.FieldOption');

        $this->setDeleteStrategy('restrict');
    }
}
