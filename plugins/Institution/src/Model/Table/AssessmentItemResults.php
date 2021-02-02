<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

class AssessmentItemResultsTable extends AppTable
{
    public function initialize(array $config)
    {
    	parent::initialize($config);
        $this->belongsTo('Students', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades', 'foreignKey' => 'education_grade_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' => 'academic_period_id']);
        $this->belongsTo('AssessmentPeriods', ['className' => 'Assessment.AssessmentPeriods', 'foreignKey' => 'assessment_period_id']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects', 'foreignKey' => 'education_subject_id']);
        $this->belongsTo('Assessments', ['className' => 'Assessment.Assessments', 'foreignKey' => 'assessment_id']);
    }
}