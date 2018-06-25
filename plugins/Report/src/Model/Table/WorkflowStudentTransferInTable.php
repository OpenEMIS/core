<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use App\Model\Table\AppTable;

class WorkflowStudentTransferInTable extends AppTable  
{
    public function initialize(array $config) 
    {
        $this->table("institution_student_transfers");
        parent::initialize($config);

        // Mandatory data
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users', 'foreignKey' => 'assignee_id']);
        // New institution data
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' => 'academic_period_id']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades', 'foreignKey' => 'education_grade_id']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses', 'foreignKey' => 'institution_class_id']);
        // Previous institution data
        $this->belongsTo('PreviousInstitutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'previous_institution_id']);
        $this->belongsTo('PreviousAcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' => 'previous_academic_period_id']);
        $this->belongsTo('PreviousEducationGrades', ['className' => 'Education.EducationGrades', 'foreignKey' => 'previous_education_grade_id']);
        $this->belongsTo('StudentTransferReasons', ['className' => 'Student.StudentTransferReasons', 'foreignKey' => 'student_transfer_reason_id']);

        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Report.WorkflowReport');
        $this->addBehavior('Excel', [
            'excludes' => ['all_visible'],
            'pages' => false,
            'autoFields' => false
        ]);
    }
}
