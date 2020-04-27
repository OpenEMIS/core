<?php
namespace Examination\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Utility\Text;
use Cake\I18n\Time;
use Cake\Network\Request;
use Cake\Controller\Component;
use App\Model\Traits\OptionsTrait;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;
use Cake\Utility\Security;

class ExaminationCentresExaminationsStudentsTable extends ControllerActionTable {
    use OptionsTrait;

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('Examinations', ['className' => 'Examination.Examinations']);
        $this->belongsTo('ExaminationCentres', ['className' => 'Examination.ExaminationCentres']);
        $this->belongsTo('ExaminationCentresExaminations', [
            'className' => 'Examination.ExaminationCentresExaminations',
            'foreignKey' => ['examination_centre_id', 'examination_id']
        ]);
        $this->belongsToMany('ExaminationCentresExaminationsSubjects', [
            'className' => 'Examination.ExaminationCentresExaminationsSubjects',
            'joinTable' => 'examination_centres_examinations_subjects_students',
            'foreignKey' => ['examination_centre_id', 'examination_id', 'student_id'],
            'targetForeignKey' => ['examination_centre_id', 'examination_item_id'],
            'through' => 'Examination.ExaminationCentresExaminationsSubjectsStudents',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->hasMany('ExaminationCentreRoomsExaminationsStudents', [
            'className' => 'Examination.ExaminationCentreRoomsExaminationsStudents',
            'foreignKey' => ['examination_centre_id', 'examination_id', 'student_id'],
            'bindingKey' => ['examination_centre_id', 'examination_id', 'student_id'],
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->addBehavior('Examination.RegisteredStudents');
        $this->addBehavior('OpenEmis.Section');
        $this->addBehavior('CompositeKey');
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        return $validator
            ->allowEmpty('registration_number')
            ->add('registration_number', 'ruleUnique', [
                'rule' => ['validateUnique', ['scope' => ['examination_id']]],
                'provider' => 'table'
            ])
            ->add('student_id', 'ruleUnique', [
                'rule' => ['validateUnique', ['scope' => ['examination_id']]],
                'provider' => 'table'
            ])
            ->add('student_id', 'ruleNotInvigilator',  [
                'rule' => ['checkNotInvigilator'],
                'provider' => 'table'
            ])
            ->requirePresence('auto_assign_to_room');
    }

    public function implementedEvents() {
        $events = parent::implementedEvents();
        $events['Model.Navigation.breadcrumb'] = 'onGetBreadcrumb';
        $events['Model.Examinations.afterUnregister'] = 'examinationsAfterUnregister';
        return $events;
    }

    public function examinationsAfterUnregister(Event $event, $students, $examinationId, $examinationCentres)
    {
        $conditions = [
            'examination_id' => $examinationId
        ];

        if (is_array($students)) {
            $conditions['student_id IN'] = $students;
        } else {
            $conditions['student_id'] = $students;
        }

        // delete student(s) from exam centre room
        $ExaminationCentreRoomStudents = TableRegistry::get('Examination.ExaminationCentreRoomsExaminationsStudents');
        $ExaminationCentreRoomStudents->deleteAll($conditions);

        // delete results for student(s)
        $ExaminationItemResults = TableRegistry::get('Examination.ExaminationItemResults');
        $ExaminationItemResults->deleteAll($conditions);

        $examinationCentreIds = is_array($examinationCentres) ? $examinationCentres : array($examinationCentres);

        // update affected exam centre(s) total registered count
        foreach ($examinationCentreIds as $centreId) {
            $studentCount = $this->find()
                ->where([
                    $this->aliasField('examination_centre_id') => $centreId,
                    $this->aliasField('examination_id') => $examinationId
                ])
                ->group([$this->aliasField('student_id')])
                ->count();
            $this->ExaminationCentresExaminations->updateAll(['total_registered' => $studentCount], ['examination_centre_id' => $centreId, 'examination_id' => $examinationId]);
        }
    }

    public function onGetBreadcrumb(Event $event, Request $request, Component $Navigation, $persona)
    {
        if ($this->action == 'add') {
            $Navigation->substituteCrumb('Registered Students', 'Single Student Registration');
        }
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
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
        $button['label'] = '<i class="fa kd-add-multiple"></i>';
        $button['attr'] = $toolbarAttr;
        $button['attr']['title'] = __('Bulk Register');
        $extra['toolbarButtons']['bulkAdd'] = $button;

         // single registration button
        if (isset($extra['toolbarButtons']['add']['url'])) {
            $extra['toolbarButtons']['add']['url']['action'] = 'RegistrationDirectory';
            $extra['toolbarButtons']['add']['url'][0] = 'index';
            $extra['toolbarButtons']['add']['attr']['title'] = __('Single Register');
        }
    }

    public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        // Set the header of the page
        $this->controller->set('contentHeader', __('Examination') . ' - ' .__('Single Student Registration'));

        $query = $this->ControllerAction->getQueryString();

        // back button goes back to RegistrationDirectory
        if (isset($extra['toolbarButtons']['back']['url'])) {
            $extra['toolbarButtons']['back']['url']['action'] = 'RegistrationDirectory';
            unset($extra['toolbarButtons']['back']['url']['queryString']);
        }

        if ($query) {
            $userId = $query['user_id'];
            $studentEntity = $this->Users->get($userId, [
                'contain' => ['Genders', 'SpecialNeeds.SpecialNeedsTypes', 'SpecialNeeds.SpecialNeedDifficulties']
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
                $this->field('registration_number', ['type' => 'string', 'length' => 20]);
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
            $needsArray = [];
            if ($attr['entity']->has('special_needs') && !empty($attr['entity']->special_needs)) {
                $specialNeeds = $attr['entity']->special_needs;

                foreach ($specialNeeds as $key => $need) {
                    $needsArray[] = ['special_need' => $need->special_needs_type->name, 'special_need_difficulty' => $need->special_need_difficulty->name];
                }
            }

            $attr['type'] = 'element';
            $attr['data'] = $needsArray;
            $attr['element'] = 'Examination.special_needs';

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
                $Examinations = $this->Examinations;
                $examinationOptions = $Examinations->getExaminationOptions($selectedAcademicPeriod);

                $todayDate = Time::now();
                $examinationId = isset($request->data[$this->alias()]['examination_id']) ? $request->data[$this->alias()]['examination_id'] : null;
                $this->advancedSelectOptions($examinationOptions, $examinationId, [
                    'message' => '{{label}} - ' . $this->getMessage('InstitutionExaminationStudents.notAvailableForRegistration'),
                    'selectOption' => false,
                    'callable' => function($id) use ($Examinations, $todayDate) {
                        return $Examinations
                            ->find()
                            ->where([
                                $Examinations->aliasField('id') => $id,
                                $Examinations->aliasField('registration_start_date <=') => $todayDate,
                                $Examinations->aliasField('registration_end_date >=') => $todayDate
                            ])
                            ->count();
                    }
                ]);
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
                $examCentreOptions = $this->ExaminationCentresExaminations
                    ->find('list', [
                        'keyField' => 'examination_centre.id',
                        'valueField' => 'examination_centre.code_name'
                    ])
                    ->contain('ExaminationCentres')
                    ->where([$this->ExaminationCentresExaminations->aliasField('examination_id') => $selectedExam])
                    ->order(['ExaminationCentres.code'])
                    ->toArray();
            }

            $attr['options'] = !empty($examCentreOptions)? $examCentreOptions: [];
            $attr['type'] = 'chosenSelect';
            $attr['select'] = true;
            $attr['attr']['multiple'] = false;
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
                        'valueField' => 'special_needs_type.name'
                    ])
                    ->contain('SpecialNeedsTypes')
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
    }

    public function addBeforeSave(Event $event, $entity, $requestData, $extra)
    {
        $process = function ($model, $entity) use ($requestData) {
            if (empty($entity->errors())) {
                // get subjects for exam centre
                $selectedExaminationCentre = $requestData[$this->alias()]['examination_centre_id'];
                $selectedExamination = $requestData[$this->alias()]['examination_id'];
                $examCentreSubjects = $this->ExaminationCentresExaminationsSubjects->getExaminationCentreSubjects($selectedExaminationCentre, $selectedExamination);
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

                $obj = [];
                $obj['examination_centre_id'] = $selectedExaminationCentre;
                $obj['student_id'] = $requestData[$this->alias()]['student_id'];
                $obj['academic_period_id'] = $requestData[$this->alias()]['academic_period_id'];
                $obj['auto_assign_to_room'] = $autoAssignToRoom;
                $obj['examination_id'] = $selectedExamination;

                if (!empty($requestData[$this->alias()]['registration_number'])) {
                    $obj['registration_number'] = $requestData[$this->alias()]['registration_number'];
                }

                // if current student
                if (!empty($existInInstitution)) {
                    $obj['institution_id'] = $existInInstitution->institution_id;
                }

                // subject students logic
                foreach ($examCentreSubjects as $examItemId => $subjectId) {
                    $obj['examination_centres_examinations_subjects'][] = [
                        'examination_centre_id' => $requestData[$this->alias()]['examination_centre_id'],
                        'examination_item_id' => $examItemId,
                        '_joinData' => [
                            'education_subject_id' => $subjectId,
                            'examination_centre_id' => $selectedExaminationCentre,
                            'examination_id' => $selectedExamination,
                            'student_id' => $requestData[$this->alias()]['student_id'],
                            'examination_item_id' => $examItemId
                        ]
                    ];

                }

                $patchOptions['associated'] = [
                    'ExaminationCentresExaminationsSubjects' => ['validate' => false]
                ];

                $success = $this->connection()->transactional(function() use ($obj, $entity, $patchOptions) {
                    $examCentreStudentEntity = $this->newEntity($obj, $patchOptions);
                    if ($examCentreStudentEntity->errors('student_id')) {
                        $entity->errors('student_id', $examCentreStudentEntity->errors('student_id'));
                    }
                    if ($examCentreStudentEntity->errors('registration_number')) {
                        $entity->errors('registration_number', $examCentreStudentEntity->errors('registration_number'));
                    }
                    if (!$this->save($examCentreStudentEntity)) {
                        return false;
                    }
                    return true;
                });

                if ($success) {
                    $studentCount = $this->find()
                        ->where([$this->aliasField('examination_centre_id') => $entity->examination_centre_id])
                        ->group([$this->aliasField('student_id')])
                        ->count();

                    $this->ExaminationCentresExaminations->updateAll(['total_registered' => $studentCount],['examination_centre_id' => $entity->examination_centre_id, 'examination_id' => $entity->examination_id]);
                }

                // auto assignment to a room
                if ($autoAssignToRoom) {
                    if ($success) {
                        $examCentreRooms = $this->ExaminationCentres->ExaminationCentreRooms
                            ->find()
                            ->leftJoin(['ExaminationCentreRoomsExaminationsStudents' => 'examination_centre_rooms_examinations_students'],
                                [
                                    'ExaminationCentreRoomsExaminationsStudents.examination_centre_room_id = '.$this->ExaminationCentres->ExaminationCentreRooms->aliasField('id'),
                                    'ExaminationCentreRoomsExaminationsStudents.examination_id = '.$selectedExamination
                                ]
                            )
                            ->order([$this->ExaminationCentres->ExaminationCentreRooms->aliasField('id')])
                            ->select([
                                $this->ExaminationCentres->ExaminationCentreRooms->aliasField('id'),
                                $this->ExaminationCentres->ExaminationCentreRooms->aliasField('number_of_seats'),
                                'seats_taken' => 'COUNT(ExaminationCentreRoomsExaminationsStudents.student_id)'])
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
                                    'examination_id' => $selectedExamination,
                                    'examination_centre_id' => $selectedExaminationCentre
                                ];

                                $ExaminationCentreRoomStudents = TableRegistry::get('Examination.ExaminationCentreRoomsExaminationsStudents');
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
}
