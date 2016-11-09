<?php
namespace Examination\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
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
        // $this->toggle('add', false);
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options) {
        $entity->id = Text::uuid();
    }

    public function beforeAction(Event $event, ArrayObject $extra) {
        $this->controller->getStudentsTab();
    }

    public function indexbeforeAction(Event $event, ArrayObject $extra)
    {
        // add button to course catalogue
        if (isset($extra['toolbarButtons']['add']['url'])) {
            $extra['toolbarButtons']['add']['url']['action'] = 'IndividualRegistration';
            $extra['toolbarButtons']['add']['url'][0] = 'index';
            $extra['toolbarButtons']['add']['attr']['title'] = 'Register';
        }
    }

        // public function addBeforeAction(Event $event, ArrayObject $extra)
    // {
    //     $query = $this->ControllerAction->getQueryString();
    //     if (isset($extra['redirect']['query'])) {
    //         unset($extra['redirect']['query']);
    //     }

    //     if ($query) {
    //         $userId = $query['id'];

    //         $this->fields = [];
    //         $this->field('student', ['userId' => $userId]);

    //         $this->field('exam_details_header', ['type' => 'section', 'title' => __('Register to Exam')]);

    //         $this->field('academic_period_id');
    //         $this->field('examination_id');
    //         $this->field('examination_centre_id');

    //         // name emis num, acad, exam,
    //     } else {
    //         // return $this->controller->redirect([
    //         //     'plugin' => 'Institution',
    //         //     'controller' => 'Institutions',
    //         //     'action' => 'StaffTrainingApplications',
    //         //     '0' => 'index'
    //         // ]);
    //     }

    //     // $event->stopPropagation();
    //     // return $this->controller->redirect($extra['redirect']);
    // }

    // public function onUpdateFieldStudent(Event $event, array $attr, $action, $request)
    // {
    //     $selectedStudent = $attr['userId'];

    //     $attr['type'] = 'readonly';
    //     $attr['attr']['value'] = $this->get($selectedStudent)->name_with_id;

    //     return $attr;
    // }

    // public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, $request)
    // {
    //     $periodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);

    //     $attr['type'] = 'select';
    //     $attr['options'] = $periodOptions;
    //     $attr['onChangeReload'] = true;
    //     return $attr;
    // }

    // public function onUpdateFieldExaminationId(Event $event, array $attr, $action, $request)
    // {
    //     if (!empty($request->data[$this->alias()]['academic_period_id'])) {
    //         $selectedAcademicPeriod = $request->data[$this->alias()]['academic_period_id'];
    //         $Examinations = TableRegistry::get('Examination.Examinations');
    //         $examinationOptions = $Examinations
    //             ->find('list')
    //             ->where([$Examinations->aliasField('academic_period_id') => $selectedAcademicPeriod])
    //             ->toArray();
    //     }

    //     $attr['type'] = 'select';
    //     $attr['options'] = !empty($examinationOptions)? $examinationOptions: [];
    //     $attr['onChangeReload'] = true;
    //     return $attr;
    // }

    // public function onUpdateFieldExaminationCentreId(Event $event, array $attr, $action, $request)
    // {
    //     if (!empty($request->data[$this->alias()]['examination_id'])) {
    //         $selectedExam = $request->data[$this->alias()]['examination_id'];
    //         $ExamCentres = TableRegistry::get('Examination.ExaminationCentres');
    //         $examCentreOptions = $ExamCentres
    //             ->find('list')
    //             ->where([$ExamCentres->aliasField('examination_id') => $selectedExam])
    //             ->toArray();
    //     }

    //     $attr['type'] = 'select';
    //     $attr['options'] = !empty($examCentreOptions)? $examCentreOptions: [];

    //     return $attr;
    // }

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
                $this->aliasField('student_id'),
                $this->aliasField('institution_id'),
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
                    $ItemResults->aliasField('student_id = ') . $this->aliasField('student_id'),
                    $ItemResults->aliasField('academic_period_id = ') . $this->aliasField('academic_period_id'),
                    $ItemResults->aliasField('examination_id = ') . $this->aliasField('examination_id'),
                    $ItemResults->aliasField('examination_centre_id = ') . $this->aliasField('examination_centre_id'),
                    $ItemResults->aliasField('education_subject_id = ') . $this->aliasField('education_subject_id')
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
                $ItemResults->aliasField('academic_period_id')
            ])
            ->order([
                $this->aliasField('student_id')
            ]);
    }
}
