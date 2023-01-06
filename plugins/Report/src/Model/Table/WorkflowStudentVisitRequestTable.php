<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use App\Model\Table\AppTable;

class WorkflowStudentVisitRequestTable extends AppTable  
{
    public function initialize(array $config) 
    {
        $this->table("institution_student_visit_requests");
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Evaluator', ['className' => 'Security.Users']);
        $this->belongsTo('Students', ['className' => 'Security.Users']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('StudentVisitTypes', ['className' => 'Student.StudentVisitTypes']);
        $this->belongsTo('StudentVisitPurposeTypes', ['className' => 'Student.StudentVisitPurposeTypes']);

        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Report.WorkflowReport');
        $this->addBehavior('Excel', [
            'excludes' => ['file_name'],
            'pages' => false,
            'autoFields' => false
        ]);
    }
}
