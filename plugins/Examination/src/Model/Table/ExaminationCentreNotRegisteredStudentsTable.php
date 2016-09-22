<?php
namespace Examination\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;

class ExaminationCentreNotRegisteredStudentsTable extends ControllerActionTable {
    use OptionsTrait;

    public function initialize(array $config)
    {
        $this->table('institution_students');
        parent::initialize($config);
        $this->belongsTo('Users',           ['className' => 'Security.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('Institutions',    ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);

        $this->addBehavior('User.AdvancedNameSearch');
        $this->addBehavior('Examination.NotRegisteredStudents');
    }

    public function beforeAction(Event $event, ArrayObject $extra) {
        $this->controller->getStudentsTab();
    }
}
