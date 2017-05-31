<?php
namespace Training\Model\Table;

use App\Model\Table\ControllerActionTable;

class TrainingNeedCompetenciesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->hasMany('StaffTrainingNeeds', ['className' => 'Institution.StaffTrainingNeeds']);
        
        $this->addBehavior('FieldOption.FieldOption');

        $this->setDeleteStrategy('restrict');
    }
}
