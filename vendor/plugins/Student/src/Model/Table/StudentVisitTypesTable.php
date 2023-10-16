<?php
namespace Student\Model\Table;

use App\Model\Table\AppTable;

class StudentVisitTypesTable extends AppTable
{
    const INSTITUTION_VISIT = 1;
    const HOME_VISIT = 2;

    public function initialize(array $config)
    {
        $this->table('student_visit_types');
        parent::initialize($config);

        $this->hasMany('StudentVisitRequests', ['className' => 'Student.StudentVisitRequests', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentVisits', ['className' => 'Student.StudentVisits', 'dependent' => true, 'cascadeCallbacks' => true]);
    }
}
