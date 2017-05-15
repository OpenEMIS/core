<?php
namespace Examination\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\ResultSet;

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

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.onGetFieldLabel'] = 'onGetFieldLabel';
        return $events;
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain([
            'Users.Nationalities.NationalitiesLookUp',
            'Users.IdentityTypes'
        ]);
    }

    public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {   
        $this->field('nationality');
        $this->field('identity_type');
        $this->setFieldOrder(['openemis_no', 'student_id', 'date_of_birth', 'nationality', 'identity_type', 'identity_number', 'gender_id', 'repeated', 'institution_id']);
    }

    public function viewAfterAction(Event $event, Entity $entity)
    {
        $this->field('nationalities', [
            'type' => 'element',
            'element' => 'nationalities',
            'visible' => ['view'=>true],
            'data' => $entity->user->nationalities
        ]);

        $this->setFieldOrder(['openemis_no', 'academic_period_id', 'examination_id', 'student_id', 'date_of_birth', 'nationalities', 'identity_number', 'gender_id', 'repeated']);

        if ($entity->user->has('identity_type') && !empty($entity->user->identity_type)) {
            $this->identityType = $entity->user->identity_type->name;
        }
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true) 
    {
        if ($this->action == 'view') {
            if ($field == 'identity_number') {
                if ($this->identityType) {
                    return __($this->identityType);
                } else {
                    return __(TableRegistry::get('FieldOption.IdentityTypes')->find()->find('DefaultIdentityType')->first()->name);
                }
            } 
        }

        return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
    }

    public function onGetNationality(Event $event, Entity $entity)
    {   
        if ($this->action == 'index') {
            if (!empty($entity)) {
                if ($entity->user->has('main_nationality') && !empty($entity->user->main_nationality)) {
                    return $entity->user->main_nationality->name;
                }
            }
        }
    }

    public function onGetIdentityType(Event $event, Entity $entity)
    {   
        if ($this->action == 'index') {
            if (!empty($entity)) {
                if ($entity->user->has('identity_type') && !empty($entity->user->identity_type)) {
                    return $entity->user->identity_type->name;
                }
            }
        }
    }

    public function onGetIdentityNumber(Event $event, Entity $entity)
    {
        if (!empty($entity)) {
            if ($entity->user->has('identity_number') && !empty($entity->user->identity_number)) {
                return $entity->user->identity_number;
            }
        }
    }

    public function onGetRepeated(Event $event, Entity $entity)
    {
        return $this->ExaminationCentreStudents->onGetRepeated($event, $entity);
    }

    public function beforeAction(Event $event, ArrayObject $extra) {
        $extra['config']['selectedLink'] = ['controller' => 'Examinations', 'action' => 'RegisteredStudents'];
        $this->controller->getStudentsTab();

        if ($this->action == 'index' || $this->action == 'view') {
            $this->field('identity_number');
            $this->field('repeated');
        }
    }
}
