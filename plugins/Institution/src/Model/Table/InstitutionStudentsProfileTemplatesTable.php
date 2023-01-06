<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use App\Model\Table\ControllerActionTable;

class InstitutionStudentsProfileTemplatesTable extends ControllerActionTable
{
     // for status
     CONST NEW_REPORT = 1;
     CONST IN_PROGRESS = 2;
     CONST GENERATED = 3;
     CONST PUBLISHED = 4;

    public function initialize(array $config)
    {
		$this->table('student_report_cards');
        parent::initialize($config);
        $this->belongsTo('StudentTemplates', ['className' => 'ProfileTemplate.StudentTemplates', 'foreignKey' => 'student_profile_template_id']);
        $this->belongsTo('Students', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);

        $this->addBehavior('CompositeKey');
    }
}
