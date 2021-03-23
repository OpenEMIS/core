<?php
namespace ReportCard\Model\Table;

use App\Model\Table\ControllerActionTable;

class StudentReportCardProcessesTable extends ControllerActionTable
{
    const NEW_PROCESS = 1;
    const RUNNING = 2;
    const COMPLETED = 3;
    const ERROR = -1;

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('StudentTemplates', ['className' => 'ProfileTemplate.StudentTemplates', 'foreignKey' => 'student_profile_template_id']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
        $this->belongsTo('Students', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
    }
}
