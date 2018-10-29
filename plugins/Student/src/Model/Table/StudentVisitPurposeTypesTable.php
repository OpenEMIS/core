<?php
namespace Student\Model\Table;

use App\Model\Table\ControllerActionTable;

class StudentVisitPurposeTypesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('student_visit_purpose_types');
        parent::initialize($config);

        $this->hasMany('StudentVisitRequests', ['className' => 'Student.StudentVisitRequests', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentVisits', ['className' => 'Student.StudentVisits', 'dependent' => true, 'cascadeCallbacks' => true]);
        
        $this->addBehavior('FieldOption.FieldOption');
    }
}
