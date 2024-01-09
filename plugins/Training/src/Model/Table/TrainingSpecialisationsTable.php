<?php
namespace Training\Model\Table;

use App\Model\Table\ControllerActionTable;

class TrainingSpecialisationsTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('training_specialisations');
        parent::initialize($config);

        $this->hasMany('TrainingCoursesSpecialisations', ['className' => 'Training.TrainingCoursesSpecialisations', 'foreignKey' => 'training_specialisation_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }
}
