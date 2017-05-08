<?php
namespace Examination\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;

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

            if ($this->action == 'index') {
                $this->field('nationality');

                $this->setFieldOrder(['registration_number', 'openemis_no', 'student_id', 'date_of_birth', 'gender_id', 'nationality', 'identity_number', 'institution_id', 'repeated', 'transferred']);
                $this->setFieldOrder('openemis_no', 'student_id', 'date_of_birth', 'nationality', 'identity_number', 'gender_id', 'repeated');

            } else if ($this->action == 'view') {
                
                $this->setFieldOrder('openemis_no', 'academic_period_id', 'examination_id', 'student_id', 'date_of_birth', 'nationalities', 'identity_number', 'gender_id', 'repeated');
            }
        }
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain([
            'Users.Nationalities.NationalitiesLookUp'
        ]);
    }

    public function viewAfterAction(Event $event, Entity $entity)
    {
        $this->field('nationalities', [
            'type' => 'element',
            'element' => 'nationalities',
            'visible' => ['view'=>true],
            'data' => $entity->user->nationalities
        ]);
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

    public function onGetNationality(Event $event, Entity $entity)
    {   
        if ($this->action == 'index') {
            if ($entity) {
                if ($entity->extractOriginal(['student_id'])) {
                    $studentId = $entity->extractOriginal(['student_id'])['student_id'];
                }

                $query = $this->Users
                        ->find()
                        ->contain('MainNationalities')
                        ->where([
                            $this->Users->aliasField('id') => $studentId
                        ])
                        ->first();

                if (!empty($query)) {
                    if ($query->has('main_nationality') && !empty($query->main_nationality)) {
                        return $query->main_nationality->name;
                    }
                }
            }
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
