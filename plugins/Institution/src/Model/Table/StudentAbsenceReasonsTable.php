<?php
namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;

class StudentAbsenceReasonsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('student_absence_reasons');
        parent::initialize($config);

        $this->hasMany('InstitutionStudentAbsences', ['className' => 'Institution.InstitutionStudentAbsences', 'foreignKey' => 'student_absence_reason_id']);

        $this->addBehavior('FieldOption.FieldOption');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'OpenEMIS_Classroom' => ['index'],
            'StudentAttendances' => ['index', 'view']
        ]);
    }
}
