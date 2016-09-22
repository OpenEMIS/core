<?php
namespace Examination\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Utility\Text;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;

class ExaminationCentreStudentsTable extends ControllerActionTable {
    use OptionsTrait;

    public function initialize(array $config) {
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Examinations', ['className' => 'Examination.Examinations']);
        $this->belongsTo('ExaminationCentres', ['className' => 'Examination.ExaminationCentres']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
        $this->hasMany('ExaminationItems', ['className' => 'Examination.ExaminationItems', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->belongsToMany('ExaminationCentreSpecialNeeds', ['className' => 'Examination.ExaminationCentreSpecialNeeds']);

        $this->addBehavior('User.AdvancedNameSearch');
        $this->addBehavior('Examination.RegisteredStudents');
        $this->toggle('add', false);
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options) {
        $entity->id = Text::uuid();
    }

    public function beforeAction(Event $event, ArrayObject $extra) {
        $this->controller->getStudentsTab();
    }
}
