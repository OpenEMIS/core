<?php
namespace Training\Model\Table;

use App\Model\Table\ControllerActionTable;

class TrainingCourseCategoriesTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {   //POCOR-5695 starts
        $this->setTable('training_course_categories');
        parent::initialize($config);

        $this->hasMany('TrainingCourses', ['className' => 'Training.TrainingCourses', 'foreignKey' => 'training_course_category_id']);

        $this->addBehavior('FieldOption.FieldOption');
        //POCOR-5695 ends
    }
}
