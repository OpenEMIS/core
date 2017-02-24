<?php
namespace Student\Model\Table;

use App\Model\Table\ControllerActionTable;

class StudentBehaviourCategoriesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('student_behaviour_categories');
        parent::initialize($config);

        $this->hasMany('StudentBehaviours', ['className' => 'Student.StudentBehaviours', 'foreignKey' => 'student_behaviour_category_id']);

        $this->addBehavior('FieldOption.FieldOption');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'OpenEMIS_Classroom' => ['index']
        ]);
    }
}
