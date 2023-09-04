<?php
namespace Training\Model\Table;

use App\Model\Table\ControllerActionTable;

class TrainingRequirementsTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('training_requirements');
        parent::initialize($config);

        $this->hasMany('TrainingCourses', ['className' => 'Training.TrainingCourses', 'foreignKey' => 'training_requirement_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }
}
