<?php
namespace Examination\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Controller\Component;
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
        $this->addBehavior('Restful.RestfulAccessControl', [
            'ExamResults' => ['index', 'add']
        ]);
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

    public function implementedEvents() {
        $events = parent::implementedEvents();
        $events['Model.Navigation.breadcrumb'] = 'onGetBreadcrumb';
        return $events;
    }

    public function onGetBreadcrumb(Event $event, Request $request, Component $Navigation, $persona)
    {
        $queryString = $request->query['queryString'];
        $indexUrl = ['plugin' => 'Examination', 'controller' => 'Examinations', 'action' => 'ExamCentres'];
        $overviewUrl = ['plugin' => 'Examination', 'controller' => 'Examinations', 'action' => 'ExamCentres', 'view', 'queryString' => $queryString];

        $Navigation->substituteCrumb('Examination', 'Examination', $indexUrl);
        $Navigation->substituteCrumb('Exam Centre Students', 'Exam Centres', $overviewUrl);
        $Navigation->addCrumb('Students');
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->isNew()) {
            $hashString = $entity->examination_centre_id . ',' . $entity->student_id . ',' . $entity->education_subject_id;
            $entity->id = Security::hash($hashString, 'sha256');
        }
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->controller->getExamCentresTab();
        $this->examCentreId = $this->ControllerAction->getQueryString('examination_centre_id');

        // Set the header of the page
        $examCentreName = $this->ExaminationCentres->get($this->examCentreId)->name;
        $this->controller->set('contentHeader', $examCentreName. ' - ' .__('Students'));

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
        $button['label'] = '<i class="fa kd-add-multiple"></i>';
        $button['attr'] = $toolbarAttr;
        $button['attr']['title'] = __('Bulk Register');
        $extra['toolbarButtons']['bulkAdd'] = $button;
        $this->field('room');
        $this->setFieldOrder(['registration_number', 'student_id', 'institution_id', 'room']);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $extra['auto_contain_fields'] = ['Institutions' => ['code']];

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

    public function onGetInstitutionId(Event $event, Entity $entity)
    {
        if ($entity->institution_id) {
            return $entity->institution->code_name;
        } else {
            return __('Private Candidate');
        }
    }

    public function findResults(Query $query, array $options) {
        $academicPeriodId = $options['academic_period_id'];
        $examinationId = $options['examination_id'];
        $examinationCentreId = $options['examination_centre_id'];
        $educationSubjectId = $options['education_subject_id'];

        $Users = $this->Users;
        $ItemResults = TableRegistry::get('Examination.ExaminationItemResults');

        return $query
            ->select([
                $ItemResults->aliasField('id'),
                $ItemResults->aliasField('marks'),
                $ItemResults->aliasField('examination_grading_option_id'),
                $ItemResults->aliasField('academic_period_id'),
                $this->aliasField('registration_number'),
                $this->aliasField('student_id'),
                $this->aliasField('institution_id'),
                $this->aliasField('education_grade_id'),
                $this->aliasField('total_mark'),
                $Users->aliasField('openemis_no'),
                $Users->aliasField('first_name'),
                $Users->aliasField('middle_name'),
                $Users->aliasField('third_name'),
                $Users->aliasField('last_name'),
                $Users->aliasField('preferred_name')
            ])
            ->matching('Users')
            ->leftJoin(
                [$ItemResults->alias() => $ItemResults->table()],
                [
                    $ItemResults->aliasField('academic_period_id = ') . $this->aliasField('academic_period_id'),
                    $ItemResults->aliasField('examination_id = ') . $this->aliasField('examination_id'),
                    $ItemResults->aliasField('examination_centre_id = ') . $this->aliasField('examination_centre_id'),
                    $ItemResults->aliasField('education_subject_id = ') . $this->aliasField('education_subject_id'),
                    $ItemResults->aliasField('student_id = ') . $this->aliasField('student_id')
                ]
            )
            ->where([
                $this->aliasField('academic_period_id') => $academicPeriodId,
                $this->aliasField('examination_id') => $examinationId,
                $this->aliasField('examination_centre_id') => $examinationCentreId,
                $this->aliasField('education_subject_id') => $educationSubjectId
            ])
            ->group([
                $this->aliasField('student_id'),
                $this->aliasField('academic_period_id'),
                $this->aliasField('examination_id')
            ])
            ->order([
                $Users->aliasField('first_name'), $Users->aliasField('last_name')
            ]);
    }
}
