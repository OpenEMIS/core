<?php
namespace Examination\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Utility\Text;
use App\Model\Table\ControllerActionTable;
use Cake\I18n\Time;
use App\Model\Traits\OptionsTrait;
use Cake\Validation\Validator;
use Cake\Log\Log;

class ExamCentreStudentsTable extends ControllerActionTable {
    use OptionsTrait;

    private $examCentreId;
    private $examCentreRoomStudents = [];

    public function initialize(array $config) {
        $this->table('examination_centre_students');
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Examinations', ['className' => 'Examination.Examinations']);
        $this->belongsTo('ExaminationCentres', ['className' => 'Examination.ExaminationCentres']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
        $this->addBehavior('User.AdvancedNameSearch');
        $this->toggle('add', false);
        $this->toggle('edit', false);
    }

    public function validationDefault(Validator $validator) {
        $validator = parent::validationDefault($validator);
        return $validator
            ->allowEmpty('registration_number')
            ->add('registration_number', 'ruleUnique', [
                'rule' => ['validateUnique', ['scope' => ['examination_id', 'education_subject_id']]],
                'provider' => 'table'
            ]);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->controller->getExamCentresTab();
        $this->examCentreId = $this->ControllerAction->getQueryString('examination_centre_id');
        $this->fields['total_mark']['visible'] = false;
        $this->fields['student_id']['visible'] = true;
        $this->fields['education_grade_id']['visible'] = false;
        $this->fields['academic_period_id']['visible'] = false;
        $this->fields['examination_id']['visible'] = false;
        $this->fields['student_id']['type'] = 'string';
    }

    public function afterDelete(Event $event, Entity $entity, ArrayObject $options)
    {
        $examCentreId = $entity->examination_centre_id;
        $studentId = $entity->student_id;
        $this->deleteAll([
            'examination_centre_id' => $examCentreId,
            'student_id' => $studentId
        ]);

        TableRegistry::get('Examination.ExaminationCentreRoomStudents')->deleteAll([
            'examination_centre_id' => $examCentreId,
            'student_id' => $studentId
        ]);

        $studentCount = $this->find()
            ->where([$this->aliasField('examination_centre_id') => $entity->examination_centre_id])
            ->group([$this->aliasField('student_id')])
            ->count();

        $this->ExaminationCentres->updateAll(['total_registered' => $studentCount],['id' => $entity->examination_centre_id]);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $toolbarAttr = [
            'class' => 'btn btn-xs btn-default',
            'data-toggle' => 'tooltip',
            'data-placement' => 'bottom',
            'escape' => false
        ];
        $button['url'] = ['plugin' => 'Examination', 'controller' => 'Examinations', 'action' => 'LinkedInstitutionAddStudents', 'add', 'queryString' => $this->request->query('queryString')];
        $button['type'] = 'button';
        $button['label'] = '<i class="fa kd-add"></i>';
        $button['attr'] = $toolbarAttr;
        $button['attr']['title'] = __('Bulk Add');
        $extra['toolbarButtons']['bulkAdd'] = $button;
        $this->field('room');
        $this->setFieldOrder(['registration_number', 'student_id', 'institution_id', 'room']);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query
            ->where([$this->aliasField('examination_centre_id').' = '.$this->examCentreId])
            ->group([$this->aliasField('student_id')]);

        $ExamCentreRoomStudents = TableRegistry::get('Examination.ExaminationCentreRoomStudents');

        $this->examCentreRoomStudents = $ExamCentreRoomStudents->find('list', [
                'keyField' => 'student_id',
                'valueField' => 'room_name'
            ])
            ->innerJoinWith('ExaminationCentreRooms')
            ->select([$ExamCentreRoomStudents->aliasField('student_id'), 'room_name' => 'ExaminationCentreRooms.name'])
            ->where([$ExamCentreRoomStudents->aliasField('examination_centre_id') => $this->examCentreId])
            ->toArray();
    }

    public function onGetRoom(Event $event, Entity $entity)
    {
        return isset($this->examCentreRoomStudents[$entity->student_id]) ? $this->examCentreRoomStudents[$entity->student_id] : '';
    }
}
