<?php
namespace Attendance\Model\Table;

use App\Model\Table\AppTable;

class StudentAttendanceTypesTable extends AppTable
{
    const DAY = 1;
    const SUBJECT = 2;

    public function initialize(array $config)
    {
        $this->table('student_attendance_types');
        parent::initialize($config);

        $this->hasMany('StudentAttendanceMarkTypes', ['className' => 'Attendance.StudentAttendanceMarkTypes', 'dependent' => true, 'cascadeCallbacks' => true]);
    }
}
