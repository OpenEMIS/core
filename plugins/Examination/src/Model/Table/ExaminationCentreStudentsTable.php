<?php
namespace Examination\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Utility\Text;
use Cake\I18n\Time;
use App\Model\Traits\OptionsTrait;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;
use Cake\Utility\Security;

class ExaminationCentreStudentsTable extends ControllerActionTable {
    use OptionsTrait;

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Examinations', ['className' => 'Examination.Examinations']);
        $this->belongsTo('ExaminationCentres', ['className' => 'Examination.ExaminationCentres']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);

        $this->addBehavior('User.AdvancedNameSearch');
        $this->addBehavior('Examination.RegisteredStudents');
        $this->addBehavior('OpenEmis.Section');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'ExamResults' => ['index', 'add']
        ]);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        return $validator
            ->allowEmpty('registration_number')
            ->add('registration_number', 'ruleUnique', [
                'rule' => ['validateUnique', ['scope' => ['examination_id', 'education_subject_id']]],
                'provider' => 'table'
            ])
            ->add('student_id', 'ruleUnique', [
                'rule' => ['validateUnique', ['scope' => ['examination_id', 'education_subject_id']]],
                'provider' => 'table'
            ])
            ->add('student_id', 'ruleNotInvigilator',  [
                'rule' => ['checkNotInvigilator'],
                'provider' => 'table'
            ])
            ->requirePresence('auto_assign_to_room');
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
        $this->fields['total_mark']['visible'] = false;
        $this->controller->getStudentsTab();
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        // bulk registration button
        $toolbarAttr = [
            'class' => 'btn btn-xs btn-default',
            'data-toggle' => 'tooltip',
            'data-placement' => 'bottom',
            'escape' => false
        ];
        $button['url'] = ['plugin' => 'Examination', 'controller' => 'Examinations', 'action' => 'BulkStudentRegistration', 'add'];
        $button['type'] = 'button';
        $button['label'] = '<i class="fa kd-add"></i>';
        $button['attr'] = $toolbarAttr;
        $button['attr']['title'] = __('Bulk Add');
        $extra['toolbarButtons']['bulkAdd'] = $button;

         // single registration button
        if (isset($extra['toolbarButtons']['add']['url'])) {
            $extra['toolbarButtons']['add']['url']['action'] = 'RegistrationDirectory';
            $extra['toolbarButtons']['add']['url'][0] = 'index';
            $extra['toolbarButtons']['add']['attr']['title'] = __('Single Registration');
        }
    }

    public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $query = $this->ControllerAction->getQueryString();

        // back button goes back to RegistrationDirectory
        if (isset($extra['toolbarButtons']['back']['url'])) {
            $extra['toolbarButtons']['back']['url']['action'] = 'RegistrationDirectory';
            unset($extra['toolbarButtons']['back']['url']['queryString']);
        }

        if ($query) {
            $userId = $query['user_id'];
            $studentEntity = $this->Users->get($userId, [
                'contain' => ['Genders', 'SpecialNeeds.SpecialNeedTypes']
            ]);

            if (!empty($studentEntity)) {
                $this->fields = [];
                $this->field('student_id', ['entity' => $studentEntity]);
                $this->field('date_of_birth', ['entity' => $studentEntity]);
                $this->field('gender_id', ['entity' => $studentEntity]);
                $this->field('special_needs', ['entity' => $studentEntity]);
                $this->field('exam_details_header', ['type' => 'section', 'title' => __('Register for Examination')]);
                $this->field('academic_period_id');
                $this->field('examination_id');
                $this->field('education_grade_id');
                $this->field('examination_centre_id');
                $this->field('special_need_accommodations');
                $this->field('registration_number', ['type' => 'string']);
                $this->field('auto_assign_to_room', ['type' => 'select', 'options' => $this->getSelectOptions('general.yesno')]);

                $this->setFieldOrder([
                    'student_id', 'date_of_birth', 'gender_id', 'special_needs', 'exam_details_header', 'academic_period_id', 'examination_id', 'education_grade_id', 'examination_centre_id', 'special_need_accommodations', 'registration_number', 'auto_assign_to_room'
                ]);

            } else {
                $this->Alert->error('general.notExists', ['reset' => 'override']);
                $url = $this->url('index');
                $event->stopPropagation();
                return $this->controller->redirect($url);
            }

        } else {
            $url = $this->url('index');
            $event->stopPropagation();
            return $this->controller->redirect($url);
        }
    }

    public function onUpdateFieldStudentId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            $attr['type'] = 'readonly';
            $attr['attr']['value'] = $attr['entity']->name_with_id;
            $attr['value'] = $attr['entity']->id;
        }

        return $attr;
    }

    public function onUpdateFieldDateOfBirth(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            $dob = $attr['entity']->date_of_birth;
            $attr['type'] = 'readonly';
            $attr['attr']['value'] = $this->formatDate($dob);
        }

        return $attr;
    }

    public function onUpdateFieldGenderId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            if ($attr['entity']->has('gender') && !empty($attr['entity']->gender)) {
                $gender = $attr['entity']->gender->name;
            }

            $attr['attr']['value'] = !empty($gender)? $gender: '';
            $attr['type'] = 'readonly';
        }

        return $attr;
    }

    public function onUpdateFieldSpecialNeeds(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            if ($attr['entity']->has('special_needs') && !empty($attr['entity']->special_needs)) {
                $specialNeeds = $attr['entity']->special_needs;

                foreach ($specialNeeds as $key => $need) {
                    $needsArray[] = $need->special_need_type->name;
                }
                $value = implode(', ', $needsArray);
            }

            $attr['attr']['value'] = !empty($value)? $value: '';
            $attr['type'] = 'readonly';
        }

        return $attr;
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            $periodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);

            $attr['type'] = 'select';
            $attr['options'] = $periodOptions;
            $attr['onChangeReload'] = 'changeAcademicPeriodId';
        }

        return $attr;
    }

    public function addOnChangeAcademicPeriodId(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        if (array_key_exists($this->alias(), $data)) {
            if (array_key_exists('examination_id', $data[$this->alias()])) {
                unset($data[$this->alias()]['examination_id']);
            }
            if (array_key_exists('examination_centre_id', $data[$this->alias()])) {
                unset($data[$this->alias()]['examination_centre_id']);
            }
        }
    }

    public function onUpdateFieldExaminationId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            if (!empty($request->data[$this->alias()]['academic_period_id'])) {
                $selectedAcademicPeriod = $request->data[$this->alias()]['academic_period_id'];
                $examinationOptions = $this->Examinations
                    ->find('list')
                    ->where([$this->Examinations->aliasField('academic_period_id') => $selectedAcademicPeriod])
                    ->toArray();
            }

            $attr['options'] = !empty($examinationOptions)? $examinationOptions: [];
            $attr['onChangeReload'] = 'changeExaminationId';
            $attr['type'] = 'select';
        }

        return $attr;
    }

    public function addOnChangeExaminationId(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        if (array_key_exists($this->alias(), $data)) {
            if (array_key_exists('examination_centre_id', $data[$this->alias()])) {
                unset($data[$this->alias()]['examination_centre_id']);
            }
        }
    }

    public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            if (!empty($request->data[$this->alias()]['examination_id'])) {
                $selectedExamination = $request->data[$this->alias()]['examination_id'];
                $Examinations = $this->Examinations
                    ->get($selectedExamination, [
                        'contain' => ['EducationGrades']
                    ])
                    ->toArray();

                $gradeName = $Examinations['education_grade']['name'];
                $gradeId = $Examinations['education_grade']['id'];
            }

            $attr['attr']['value'] = !empty($gradeName)? $gradeName: '';
            $attr['value'] = !empty($gradeId)? $gradeId: '';
            $attr['type'] = 'readonly';
        }

        return $attr;
    }

    public function onUpdateFieldExaminationCentreId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            if (!empty($request->data[$this->alias()]['examination_id'])) {
                $selectedExam = $request->data[$this->alias()]['examination_id'];
                $examCentreOptions = $this->ExaminationCentres
                    ->find('list')
                    ->where([$this->ExaminationCentres->aliasField('examination_id') => $selectedExam])
                    ->toArray();
            }

            $attr['options'] = !empty($examCentreOptions)? $examCentreOptions: [];
            $attr['type'] = 'select';
            $attr['onChangeReload'] = true;
        }

        return $attr;
    }

    public function onUpdateFieldSpecialNeedAccommodations(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            if (!empty($request->data[$this->alias()]['examination_centre_id'])) {
                $selectedExamCentre = $request->data[$this->alias()]['examination_centre_id'];
                $ExaminationCentreSpecialNeeds = TableRegistry::get('Examination.ExaminationCentreSpecialNeeds');
                $query = $ExaminationCentreSpecialNeeds
                    ->find('list', [
                        'keyField' => 'special_need_type_id',
                        'valueField' => 'special_need_type.name'
                    ])
                    ->contain('SpecialNeedTypes')
                    ->where([$ExaminationCentreSpecialNeeds->aliasField('examination_centre_id') => $selectedExamCentre])
                    ->toArray();

                if (!empty($query)) {
                    $specialNeeds = implode(', ', $query);
                }
            }

            $attr['attr']['value'] = !empty($specialNeeds)? $specialNeeds: '';
            $attr['type'] = 'readonly';
        }

        return $attr;
    }

    public function addBeforePatch(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra)
    {
        // unset hash querystring when redirect to index page
        if (isset($extra['redirect']['queryString'])) {
            unset($extra['redirect']['queryString']);
        }

        $requestData[$this->alias()]['education_subject_id'] = 0;
    }

    public function addBeforeSave(Event $event, $entity, $requestData, $extra)
    {
        $process = function ($model, $entity) use ($requestData) {
            if (empty($entity->errors())) {
                // get subjects for exam centre
                $selectedExaminationCentre = $requestData[$this->alias()]['examination_centre_id'];
                $ExaminationCentreSubjects = $this->ExaminationCentres->ExaminationCentreSubjects->getExaminationCentreSubjects($selectedExaminationCentre);
                $autoAssignToRoom = $requestData[$this->alias()]['auto_assign_to_room'];

                // check if candidate is a current student
                $enrolledStatus = TableRegistry::get('Student.StudentStatuses')->getIdByCode('CURRENT');
                $Students = TableRegistry::get('Institution.Students');
                $existInInstitution = $Students
                    ->find()
                    ->where([
                        $Students->aliasField('student_id') => $requestData[$this->alias()]['student_id'],
                        $Students->aliasField('education_grade_id') => $requestData[$this->alias()]['education_grade_id'],
                        $Students->aliasField('academic_period_id') => $requestData[$this->alias()]['academic_period_id'],
                        $Students->aliasField('student_status_id') => $enrolledStatus
                    ])
                    ->first();

                $newEntities = [];
                foreach ($ExaminationCentreSubjects as $subjectId => $name) {
                    $obj['examination_centre_id'] = $requestData[$this->alias()]['examination_centre_id'];
                    $obj['student_id'] = $requestData[$this->alias()]['student_id'];
                    $obj['education_subject_id'] = $subjectId;
                    $obj['education_grade_id'] = $requestData[$this->alias()]['education_grade_id'];
                    $obj['academic_period_id'] = $requestData[$this->alias()]['academic_period_id'];
                    $obj['examination_id'] = $requestData[$this->alias()]['examination_id'];
                    $obj['auto_assign_to_room'] = $autoAssignToRoom;

                    if (!empty($requestData[$this->alias()]['registration_number'])) {
                        $obj['registration_number'] = $requestData[$this->alias()]['registration_number'];
                    }

                    // if current student
                    if (!empty($existInInstitution)) {
                        $obj['institution_id'] = $existInInstitution->institution_id;
                    }

                    $newEntities[] = $obj;
                }

                $success = $this->connection()->transactional(function() use ($newEntities, $entity) {
                    $return = true;
                    foreach ($newEntities as $key => $newEntity) {
                        $examCentreStudentEntity = $this->newEntity($newEntity);
                        if ($examCentreStudentEntity->errors('student_id')) {
                            $entity->errors('student_id', $examCentreStudentEntity->errors('student_id'));
                        }
                        if ($examCentreStudentEntity->errors('registration_number')) {
                            $entity->errors('registration_number', $examCentreStudentEntity->errors('registration_number'));
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

                // auto assignment to a room
                if ($autoAssignToRoom) {
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

                        $assigned = false;
                        foreach ($examCentreRooms as $room) {
                            $counter = $room->number_of_seats - $room->seats_taken;
                            if ($counter > 0) {
                                $newEntity = [
                                    'examination_centre_room_id' => $room->id,
                                    'student_id' => $requestData[$this->alias()]['student_id'],
                                    'education_grade_id' => $requestData[$this->alias()]['education_grade_id'],
                                    'academic_period_id' => $requestData[$this->alias()]['academic_period_id'],
                                    'examination_id' => $requestData[$this->alias()]['examination_id'],
                                    'examination_centre_id' => $requestData[$this->alias()]['examination_centre_id']
                                ];

                                // if current student
                                if (!empty($existInInstitution)) {
                                    $newEntity['institution_id'] = $existInInstitution->institution_id;
                                }

                                $ExaminationCentreRoomStudents = TableRegistry::get('Examination.ExaminationCentreRoomStudents');
                                $examCentreRoomStudentEntity = $ExaminationCentreRoomStudents->newEntity($newEntity);
                                if ($ExaminationCentreRoomStudents->save($examCentreRoomStudentEntity)) {
                                    $assigned = true;
                                    break;
                                }
                            }
                        }

                        // if student was not added to any room
                        if (!$assigned) {
                            $model->Alert->warning($this->aliasField('notAssignedRoom'));
                        }
                        return true;
                    } else {
                        return false;
                    }
                } else {
                    return $success;
                }
            }
            return false;
        };

        return $process;
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
