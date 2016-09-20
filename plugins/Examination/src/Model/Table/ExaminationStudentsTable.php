<?php
namespace Examination\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;

class ExaminationStudentsTable extends ControllerActionTable {
    use OptionsTrait;

    public function initialize(array $config) {
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Examinations', ['className' => 'Examination.Examinations']);
        $this->belongsTo('ExaminationCentres', ['className' => 'Examination.ExaminationCentres']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);

        $this->addBehavior('Examination.RegisteredStudents');
    }

    public function beforeAction(Event $event, ArrayObject $extra) {
        $this->controller->getStudentsTab();
    }
}
