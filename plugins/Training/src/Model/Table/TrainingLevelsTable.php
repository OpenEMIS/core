<?php
namespace Training\Model\Table;

use App\Model\Table\ControllerActionTable;

class TrainingLevelsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('training_levels');
        parent::initialize($config);

        $this->hasMany('TrainingCourses', ['className' => 'Training.TrainingCourses', 'foreignKey' => 'training_level_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }
}
