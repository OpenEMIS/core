<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use App\Model\Table\ControllerActionTable;

class InstitutionStudentsReportCardsTable extends ControllerActionTable
{
     // for status
    CONST NEW_REPORT = 1;
    CONST NEW_PROCESS = 1; //POCOR-7989
    CONST IN_PROGRESS = 2;
    CONST RUNNING = 2; //POCOR-7989
    CONST GENERATED = 3;
    CONST COMPLETED = 3; //POCOR-7989
    CONST PUBLISHED = 4;
    CONST ERROR = -1; //POCOR-6788 //POCOR-7989

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('ReportCards', ['className' => 'ReportCard.ReportCards']);
        $this->belongsTo('Students', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
        $this->hasMany('StudentsReportCardsComments', [
            'className' => 'Institution.InstitutionStudentsReportCardsComments',
            'foreignKey' => ['report_card_id', 'student_id', 'institution_id', 'academic_period_id', 'education_grade_id'],
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->addBehavior('CompositeKey');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'ReportCardComments' => ['index', 'add']
        ]);
    }
}
