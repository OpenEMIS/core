<?php
namespace Examination\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Utility\Text;
use Cake\Network\Request;
use Cake\Controller\Component;
use App\Model\Table\ControllerActionTable;
use Cake\I18n\Time;
use App\Model\Traits\OptionsTrait;
use Cake\Validation\Validator;
use Cake\Utility\Security;

class LinkedInstitutionAddStudentsTable extends ControllerActionTable {
    use OptionsTrait;

    private $examCentreId = null;

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
        $this->belongsTo('ExaminationItems', ['className' => 'Examination.ExaminationItems']);
        $this->belongsToMany('ExaminationCentreSpecialNeeds', ['className' => 'Examination.ExaminationCentreSpecialNeeds']);

        $this->addBehavior('CompositeKey');

        $this->toggle('index', false);
        $this->toggle('edit', false);
        $this->toggle('view', false);
    }

    public function validationDefault(Validator $validator) {
        $validator = parent::validationDefault($validator);
        return $validator
            ->allowEmpty('registration_number')
            ->add('registration_number', 'ruleUnique', [
                'rule' => ['validateUnique', ['scope' => ['examination_id', 'examination_item_id']]],
                'provider' => 'table'
            ])
            ->requirePresence('institution_id')
            ->requirePresence('auto_assign_to_rooms');
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
        $Navigation->substituteCrumb('Linked Institution Add Students', 'Exam Centres', $overviewUrl);
        $Navigation->addCrumb('Students');
    }

    public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->controller->getExamCentresTab('ExamCentreStudents');
        $this->examCentreId = $this->ControllerAction->getQueryString('examination_centre_id');

        // Set the header of the page
        $examCentreName = $this->ExaminationCentres->get($this->examCentreId)->name;
        $this->controller->set('contentHeader', $examCentreName. ' - ' .__('Students'));

        $examCentre = $this->ExaminationCentres->get($this->examCentreId, ['contain' => ['Examinations.EducationGrades', 'AcademicPeriods']]);
        $this->field('academic_period_id', ['type' => 'readonly', 'value' => $examCentre->academic_period_id, 'attr' => ['value' => $examCentre->academic_period->name]]);
        $this->field('examination_id',  ['type' => 'readonly', 'value' => $examCentre->examination_id, 'attr' => ['value' => $examCentre->examination->name]]);
        $this->field('examination_centre_id',  ['type' => 'readonly', 'value' => $examCentre->id, 'attr' => ['value' => $examCentre->code_name]]);
        $this->field('institution_id', ['type' => 'select', 'onChangeReload' => true, 'education_grade_id' => $examCentre->examination->education_grade_id, 'academic_period_id' => $examCentre->academic_period_id]);
        $this->field('auto_assign_to_rooms', ['type' => 'select', 'options' => $this->getSelectOptions('general.yesno')]);
        $this->field('student_id', ['entity' => $entity]);
        $this->field('education_grade_id', ['type' => 'hidden', 'value' => $examCentre->examination->education_grade_id]);
        $this->field('total_mark', ['visible' => false]);
        $this->field('registration_number', ['visible' => false]);
        $this->field('education_subject_id', ['visible' => false]);

        $extra['toolbarButtons']['back']['url'] = ['plugin' => 'Examination', 'controller' => 'Examinations', 'action' => 'ExamCentreStudents', 'queryString' => $this->request->query('queryString')];

        $this->setFieldOrder([
            'academic_period_id', 'examination_id', 'examination_education_grade', 'examination_centre_id', 'auto_assign_to_rooms', 'institution_id', 'student_id'
        ]);
    }

    public function onUpdateFieldInstitutionId(Event $event, array $attr, $action, $request) {
        $institutions = [];

        if ($action == 'add') {
            $educationGradeId = $attr['education_grade_id'];
            $academicPeriodId = $attr['academic_period_id'];

            $InstitutionGradesTable = $this->Institutions->InstitutionGrades;
            $institutionsData = $InstitutionGradesTable
                ->find()
                ->matching('Institutions.ExamCentres', function ($q) {
                    return $q->where(['ExamCentres.id' => $this->examCentreId]);
                })
                ->where([$InstitutionGradesTable->aliasField('education_grade_id') => $educationGradeId])
                ->select(['institution_id' => 'Institutions.id', 'institution_name' => 'Institutions.name', 'institution_code' => 'Institutions.code'])
                ->group('institution_id')
                ->hydrate(false)
                ->toArray();
            foreach ($institutionsData as $data) {
                $institutions[$data['institution_id']] = $data['institution_code']. ' - ' . $data['institution_name'];
            }
            $attr['options'] = $institutions;
        }

        return $attr;
    }

    public function onUpdateFieldStudentId(Event $event, array $attr, $action, $request) {
        $students = [];

        if ($action == 'add') {
            if (!empty($request->data[$this->alias()]['examination_id']) && !empty($request->data[$this->alias()]['institution_id'])) {
                $institutionId = $request->data[$this->alias()]['institution_id'];
                $academicPeriodId = $request->data[$this->alias()]['academic_period_id'];
                $examinationId = $request->data[$this->alias()]['examination_id'];
                $educationGradeId = $request->data[$this->alias()]['education_grade_id'];
                $enrolledStatus = TableRegistry::get('Student.StudentStatuses')->getIdByCode('CURRENT');
                $examinationCentreId = $request->data[$this->alias()]['examination_centre_id'];

                $InstitutionStudents = $this->Institutions->Students;
                $students = $InstitutionStudents->find()
                    ->matching('EducationGrades')
                    ->leftJoin(['InstitutionExaminationStudents' => 'examination_centre_students'], [
                        'InstitutionExaminationStudents.examination_id' => $examinationId,
                        'InstitutionExaminationStudents.student_id = '.$InstitutionStudents->aliasField('student_id')
                    ])
                    ->contain('Users.SpecialNeeds.SpecialNeedsTypes')
                    ->leftJoinWith('Users.SpecialNeeds')
                    ->where([
                        $InstitutionStudents->aliasField('institution_id') => $institutionId,
                        $InstitutionStudents->aliasField('academic_period_id') => $academicPeriodId,
                        $InstitutionStudents->aliasField('student_status_id') => $enrolledStatus,
                        $InstitutionStudents->aliasField('education_grade_id') => $educationGradeId,
                        'InstitutionExaminationStudents.student_id IS NULL'
                    ])
                    ->order(['SpecialNeeds.id' => 'DESC'])
                    ->group($InstitutionStudents->aliasField('student_id'))
                    ->toArray();
            }

            $attr['type'] = 'element';
            $attr['element'] = 'Examination.students';
            $attr['data'] = $students;
        }

        return $attr;
    }

    public function addBeforePatch(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra)
    {
        $extra['redirect'] = ['plugin' => 'Examination', 'controller' => 'Examinations', 'action' => 'ExamCentreStudents', 'queryString' => $this->request->query('queryString')];
        $requestData[$this->alias()]['student_id'] = 0;
        $requestData[$this->alias()]['education_subject_id'] = 0;
        $requestData[$this->alias()]['examination_item_id'] = 0;
    }

    public function addBeforeSave(Event $event, $entity, $requestData, $extra)
    {
        $process = function ($model, $entity) use ($requestData) {
            if (!empty($requestData[$this->alias()]['examination_students']) && !empty($requestData[$this->alias()]['examination_centre_id'])) {
                $students = $requestData[$this->alias()]['examination_students'];
                $newEntities = [];

                $selectedExaminationCentre = $requestData[$this->alias()]['examination_centre_id'];
                $ExaminationCentreSubjects = TableRegistry::get('Examination.ExaminationCentreSubjects');
                $examCentreSubjects = $ExaminationCentreSubjects->getExaminationCentreSubjects($selectedExaminationCentre);
                $autoAssignToRooms = $entity->auto_assign_to_rooms;
                $studentCount = 0;
                $roomStudents = [];
                foreach ($students as $key => $student) {
                    $obj = [];
                    if ($student['selected'] == 1) {
                        $obj['student_id'] = $student['student_id'];
                        $obj['registration_number'] = $student['registration_number'];
                        $obj['institution_id'] = $requestData[$this->alias()]['institution_id'];
                        $obj['education_grade_id'] = $requestData[$this->alias()]['education_grade_id'];
                        $obj['academic_period_id'] = $requestData[$this->alias()]['academic_period_id'];
                        $obj['examination_id'] = $requestData[$this->alias()]['examination_id'];
                        $obj['examination_centre_id'] = $requestData[$this->alias()]['examination_centre_id'];
                        $obj['auto_assign_to_rooms'] = $autoAssignToRooms;
                        $obj['counterNo'] = $key;
                        $roomStudents[] = $obj;
                        $studentCount++;
                        foreach($examCentreSubjects as $examItemId => $subjectId) {
                            $obj['examination_item_id'] = $examItemId;
                            $obj['education_subject_id'] = $subjectId;
                            $newEntities[] = $obj;
                        }
                    }
                }
                if (empty($newEntities)) {
                    $model->Alert->warning($this->aliasField('noStudentSelected'));
                    $entity->errors('student_id', __('There are no students selected'));
                    return false;
                }

                $success = $this->connection()->transactional(function() use ($newEntities, $entity) {
                    $return = true;
                    foreach ($newEntities as $key => $newEntity) {
                        $examCentreStudentEntity = $this->newEntity($newEntity);
                        if ($examCentreStudentEntity->errors('registration_number')) {
                            $counterNo = $newEntity['counterNo'];
                            $entity->errors("examination_students.$counterNo", ['registration_number' => $examCentreStudentEntity->errors('registration_number')]);
                        }
                        if (!$this->save($examCentreStudentEntity)) {
                            $return = false;
                        }
                    }
                    return $return;
                });

                if ($success) {
                    $studentCount = $this->find()
                        ->where([$this->aliasField('examination_centre_id') => $entity->examination_centre_id])
                        ->group([$this->aliasField('student_id')])
                        ->count();
                    $this->ExaminationCentres->updateAll(['total_registered' => $studentCount],['id' => $entity->examination_centre_id]);
                }

                if ($autoAssignToRooms) {
                    if ($success) {
                        $examCentreRooms = $this->ExaminationCentres->ExaminationCentreRooms
                            ->find()
                            ->leftJoin(['ExaminationCentreRoomStudents' => 'examination_centre_room_students'], [
                                'ExaminationCentreRoomStudents.examination_centre_room_id = '.$this->ExaminationCentres->ExaminationCentreRooms->aliasField('id')
                            ])
                            ->order([$this->ExaminationCentres->ExaminationCentreRooms->aliasField('id')])
                            ->select([
                                $this->ExaminationCentres->ExaminationCentreRooms->aliasField('id'),
                                $this->ExaminationCentres->ExaminationCentreRooms->aliasField('number_of_seats'),
                                'seats_taken' => 'COUNT(ExaminationCentreRoomStudents.student_id)'])
                            ->where([$this->ExaminationCentres->ExaminationCentreRooms->aliasField('examination_centre_id') => $selectedExaminationCentre])
                            ->group([$this->ExaminationCentres->ExaminationCentreRooms->aliasField('id')])
                            ->toArray();

                        foreach ($examCentreRooms as $room) {
                            $counter = $room->number_of_seats - $room->seats_taken;
                            while ($counter > 0) {
                                $examCentreRoomStudent = array_shift($roomStudents);
                                $newEntity = [
                                    'examination_centre_room_id' => $room->id,
                                    'student_id' => $examCentreRoomStudent['student_id'],
                                    'institution_id' => $examCentreRoomStudent['institution_id'],
                                    'education_grade_id' => $examCentreRoomStudent['education_grade_id'],
                                    'academic_period_id' => $examCentreRoomStudent['academic_period_id'],
                                    'examination_id' => $examCentreRoomStudent['examination_id'],
                                    'examination_centre_id' => $examCentreRoomStudent['examination_centre_id']
                                ];
                                $ExaminationCentreRoomStudents = TableRegistry::get('Examination.ExaminationCentreRoomStudents');
                                $examCentreRoomStudentEntity = $ExaminationCentreRoomStudents->newEntity($newEntity);
                                $saveSucess = $ExaminationCentreRoomStudents->save($examCentreRoomStudentEntity);
                                $counter--;
                            }
                        }
                        if (!empty($roomStudents)) {
                            $model->Alert->warning($this->aliasField('notAssignedRoom'));
                            return true;
                        }
                        return true;
                    } else {
                        return false;
                    }
                } else {
                    return $success;
                }
            } else {
                $model->Alert->warning($this->aliasField('noStudentSelected'));
                $entity->errors('student_id', __('There are no students selected'));
                return false;
            }
        };

        return $process;
    }
}
