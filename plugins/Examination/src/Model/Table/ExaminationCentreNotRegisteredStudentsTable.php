<?php
namespace Examination\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;

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

        $this->ExaminationCentreStudents = TableRegistry::get('Examination.ExaminationCentresExaminationsStudents');
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        if ($this->action == 'index' || $this->action == 'view') {
            $this->field('identity_number', ['after' => 'date_of_birth']);
            $this->field('repeated');
            $this->setFieldOrder('openemis_no', 'student_id', 'gender_id', 'date_of_birth', 'identity_number', 'repeated');
        }
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.onGetFieldLabel'] = 'onGetFieldLabel';
        return $events;
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true) {
        if ($field == 'identity_number') {
            return __(TableRegistry::get('FieldOption.IdentityTypes')->find()->find('DefaultIdentityType')->first()->name);
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function onGetIdentityNumber(Event $event, Entity $entity)
    {
        return $entity->user->identity_number;
    }

    public function onGetRepeated(Event $event, Entity $entity)
    {
        return $this->ExaminationCentreStudents->onGetRepeated($event, $entity);
    }

    public function beforeAction(Event $event, ArrayObject $extra) {
        $extra['config']['selectedLink'] = ['controller' => 'Examinations', 'action' => 'RegisteredStudents'];
        $this->controller->getStudentsTab();
    }
}
