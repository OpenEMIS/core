<?php
namespace Training\Model\Table;

use App\Model\Table\ControllerActionTable;

class TrainingCourseTypesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('training_course_types');
        parent::initialize($config);

        $this->hasMany('TrainingCourses', ['className' => 'Training.TrainingCourses', 'foreignKey' => 'training_course_type_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }
}
