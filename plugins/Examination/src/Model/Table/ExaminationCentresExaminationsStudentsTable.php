<?php

namespace Examination\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\EventInterface;
use Cake\Utility\Text;
use Cake\I18n\Time;
use Cake\Controller\Component;
use App\Model\Traits\OptionsTrait;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;
use Cake\Utility\Security;
use Cake\Http\ServerRequest;
use Cake\I18n\FrozenTime;

class ExaminationCentresExaminationsStudentsTable extends ControllerActionTable
{
    use OptionsTrait;

    public function initialize(array $config): void
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
            'targetForeignKey' => ['examination_centre_id', 'examination_subject_id'],
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

    /*public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        //POCOR-7512 start
        if($this->action == "edit"){
            return  $validator
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
            ]);   
        }
         //POCOR-7512 end
        return  $validator
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
    }*/

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['Model.Navigation.breadcrumb'] = 'onGetBreadcrumb';
        $events['Model.Examinations.afterUnregister'] = 'examinationsAfterUnregister';
        return $events;
    }

    public function examinationsAfterUnregister(EventInterface $event, $students, $examinationId, $examinationCentres)
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
        $ExaminationCentreRoomStudents = TableRegistry::getTableLocator()->get('Examination.ExaminationCentreRoomsExaminationsStudents');
        $ExaminationCentreRoomStudents->deleteAll($conditions);

        // delete results for student(s)
        $ExaminationStudentSubjectResults = TableRegistry::getTableLocator()->get('Examination.ExaminationStudentSubjectResults');
        $ExaminationStudentSubjectResults->deleteAll($conditions);

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

    // public function onGetBreadcrumb(EventInterface $event, ServerRequest $request, Component $Navigation, ServerRequest $persona)
    public function onGetBreadcrumb(EventInterface $event, ServerRequest $request, Component $Navigation)
    {
        if ($this->action == 'add') {
            $Navigation->substituteCrumb('Registered Students', 'Single Student Registration');
        }
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->controller->getStudentsTab();
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
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


        // Start POCOR-5188
        $is_manual_exist = $this->getManualUrl('Administration', 'Registered Students', 'Examinations');
        if (!empty($is_manual_exist)) {
            $btnAttr = [
                'class' => 'btn btn-xs btn-default icon-big',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'target' => '_blank'
            ];

            $helpBtn['url'] = $is_manual_exist['url'];
            $helpBtn['type'] = 'button';
            $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
            $helpBtn['attr'] = $btnAttr;
            $helpBtn['attr']['title'] = __('Help');
            $extra['toolbarButtons']['help'] = $helpBtn;
        }
        // End POCOR-5188

        //POCOR-7509 start

        $syncUserConfigured = TableRegistry::getTableLocator()->get('Configuration.ConfigExternalDataSourceExam')->getOpenemisExamConfiguration();

        if ($syncUserConfigured) {
            $examinationId = $this->request->getQuery('examination_id');
            if (($this->AccessControl->check(['Examinations', 'syncResultFromExam', 'execute']) || $this->AccessControl->isAdmin())
                && !empty($examinationId) && $examinationId != -1
            ) {

                $syncParams = [
                    'examination_id' => $examinationId,
                    'academic_period_id' => $this->request->getQuery('academic_period_id'),
                    'referrer' => $this->request->getRequestTarget()
                ];
                $encodedParams = $this->ControllerAction->paramsEncode($syncParams);

                $syncUrl = [
                    'plugin' => 'Examination',
                    'controller' => 'Examinations',
                    'plugin' => 'Examination',
                    'controller' => 'Examinations',
                    'action' => 'syncStudentsToExam',
                    '?' => ['queryString' => $encodedParams]
                ];

                $syncButton =  [
                    'url' => $syncUrl,
                    'type' => 'button',
                    'label' => '<i class="kd-process"></i>',
                    'attr' => [
                        'class' => 'btn btn-xs btn-default icon-big',
                        'data-toggle' => 'tooltip',
                        'data-placement' => 'bottom',
                        'escape' => false,
                        'title' => __('Sync')
                    ]
                ];

                $extra['toolbarButtons']['sync'] = $syncButton;
            }
        }

        //POCOR-7509 end
    }


    public function addAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        // Set the header of the page
        $this->controller->set('contentHeader', __('Examination') . ' - ' . __('Single Student Registration'));

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
                $this->field('subject_id'); //POCOR-7512
                $this->setFieldOrder([
                    'student_id',
                    'date_of_birth',
                    'gender_id',
                    'special_needs',
                    'exam_details_header',
                    'academic_period_id',
                    'examination_id',
                    'education_grade_id',
                    'examination_centre_id',
                    'special_need_accommodations',
                    'registration_number',
                    'auto_assign_to_room',
                    'subject_id'
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

    public function onUpdateFieldStudentId(EventInterface $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            $attr['type'] = 'readonly';
            $attr['attr']['value'] = $attr['entity']->name_with_id;
            $attr['value'] = $attr['entity']->id;
        }

        return $attr;
    }

    public function onUpdateFieldDateOfBirth(EventInterface $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            $dob = $attr['entity']->date_of_birth;
            $attr['type'] = 'readonly';
            $attr['attr']['value'] = $this->formatDate($dob);
        }

        return $attr;
    }

    public function onUpdateFieldGenderId(EventInterface $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            if ($attr['entity']->has('gender') && !empty($attr['entity']->gender)) {
                $gender = $attr['entity']->gender->name;
            }

            $attr['attr']['value'] = !empty($gender) ? $gender : '';
            $attr['type'] = 'readonly';
        }

        return $attr;
    }

    public function onUpdateFieldSpecialNeeds(EventInterface $event, array $attr, $action, $request)
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

    public function onUpdateFieldAcademicPeriodId(EventInterface $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            $periodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);

            $attr['type'] = 'select';
            $attr['options'] = $periodOptions;
            $attr['onChangeReload'] = 'changeAcademicPeriodId';
        }

        return $attr;
    }

    public function addOnChangeAcademicPeriodId(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        if ($data->offsetExists($this->getAlias())) {
            if (array_key_exists('examination_id', $data[$this->getAlias()])) {
                unset($data[$this->getAlias()]['examination_id']);
            }
            if (array_key_exists('examination_centre_id', $data[$this->getAlias()])) {
                unset($data[$this->getAlias()]['examination_centre_id']);
            }
        }
    }

    public function onUpdateFieldExaminationId(EventInterface $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            if (!empty($request->getData()[$this->getAlias()]['academic_period_id'])) {
                $selectedAcademicPeriod = $request->getData()[$this->getAlias()]['academic_period_id'];
                $Examinations = $this->Examinations;
                $examinationOptions = $Examinations->getExaminationOptions($selectedAcademicPeriod);

                $todayDate = Time::now();
                $examinationId = isset($request->getData()[$this->getAlias()]['examination_id']) ? $request->getData()[$this->getAlias()]['examination_id'] : null;
                $this->advancedSelectOptions($examinationOptions, $examinationId, [
                    'message' => '{{label}} - ' . $this->getMessage('InstitutionExaminationStudents.notAvailableForRegistration'),
                    'selectOption' => false,
                    'callable' => function ($id) use ($Examinations, $todayDate) {
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

            $attr['options'] = !empty($examinationOptions) ? $examinationOptions : [];
            $attr['onChangeReload'] = 'changeExaminationId';
            $attr['type'] = 'select';
        }

        return $attr;
    }

    public function addOnChangeExaminationId(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        if ($data->offsetExists($this->getAlias())) {
            if (array_key_exists('examination_centre_id', $data[$this->getAlias()])) {
                unset($data[$this->getAlias()]['examination_centre_id']);
            }
        }
    }

    public function onUpdateFieldEducationGradeId(EventInterface $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            if (!empty($request->getData()[$this->getAlias()]['examination_id'])) {
                $selectedExamination = $request->getData()[$this->getAlias()]['examination_id'];
                $Examinations = $this->Examinations
                    ->get($selectedExamination, [
                        'contain' => ['EducationGrades']
                    ])
                    ->toArray();

                $gradeName = $Examinations['education_grade']['name'];
                $gradeId = $Examinations['education_grade']['id'];
            }

            $attr['attr']['value'] = !empty($gradeName) ? $gradeName : '';
            $attr['value'] = !empty($gradeId) ? $gradeId : '';
            $attr['type'] = 'readonly';
        }

        return $attr;
    }

    public function onUpdateFieldExaminationCentreId(EventInterface $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            if (!empty($request->getData()[$this->getAlias()]['examination_id'])) {
                $selectedExam = $request->getData()[$this->getAlias()]['examination_id'];
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

            $attr['options'] = !empty($examCentreOptions) ? $examCentreOptions : [];
            $attr['type'] = 'chosenSelect';
            $attr['select'] = true;
            $attr['attr']['multiple'] = false;
            $attr['onChangeReload'] = true;
        }

        return $attr;
    }

    public function onUpdateFieldSpecialNeedAccommodations(EventInterface $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            if (!empty($request->getData()[$this->getAlias()]['examination_centre_id'])) {
                $selectedExamCentre = $request->getData()[$this->getAlias()]['examination_centre_id'];
                $ExaminationCentreSpecialNeeds = TableRegistry::getTableLocator()->get('Examination.ExaminationCentreSpecialNeeds');
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

            $attr['attr']['value'] = !empty($specialNeeds) ? $specialNeeds : '';
            $attr['type'] = 'readonly';
        }

        return $attr;
    }

    public function addBeforePatch(EventInterface $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra)
    {
        // unset hash querystring when redirect to index page
        if (isset($extra['redirect']['queryString'])) {
            unset($extra['redirect']['queryString']);
        }
    }

    public function addBeforeSave(EventInterface $event, $entity, $requestData, $extra)
    {
        $process = function ($model, $entity) use ($requestData) {
            if (empty($entity->getErrors())) {
                // get subjects for exam centre
                $selectedExaminationCentre = $requestData[$this->getAlias()]['examination_centre_id'];
                $selectedExamination = $requestData[$this->getAlias()]['examination_id'];
                $examCentreSubjects = $this->ExaminationCentresExaminationsSubjects->getExaminationCentreSubjects($selectedExaminationCentre, $selectedExamination);
                $autoAssignToRoom = $requestData[$this->getAlias()]['auto_assign_to_room'];

                // check if candidate is a current student
                $enrolledStatus = TableRegistry::getTableLocator()->get('Student.StudentStatuses')->getIdByCode('CURRENT');
                $Students = TableRegistry::getTableLocator()->get('Institution.Students');
                $existInInstitution = $Students
                    ->find()
                    ->where([
                        $Students->aliasField('student_id') => $requestData[$this->getAlias()]['student_id'],
                        $Students->aliasField('education_grade_id') => $requestData[$this->getAlias()]['education_grade_id'],
                        $Students->aliasField('academic_period_id') => $requestData[$this->getAlias()]['academic_period_id'],
                        $Students->aliasField('student_status_id') => $enrolledStatus
                    ])
                    ->first();

                $obj = [];
                $obj['examination_centre_id'] = $selectedExaminationCentre;
                $obj['student_id'] = $requestData[$this->getAlias()]['student_id'];
                $obj['academic_period_id'] = $requestData[$this->getAlias()]['academic_period_id'];
                $obj['auto_assign_to_room'] = $autoAssignToRoom;
                $obj['examination_id'] = $selectedExamination;

                if (!empty($requestData[$this->getAlias()]['registration_number'])) {
                    $obj['registration_number'] = $requestData[$this->getAlias()]['registration_number'];
                }

                // if current student
                if (!empty($existInInstitution)) {
                    $obj['institution_id'] = $existInInstitution->institution_id;
                } else { //POCOR-7393 starts (4th case)
                    $result = $Students
                        ->find()
                        ->where([
                            $Students->aliasField('student_id') => $requestData[$this->getAlias()]['student_id'],
                            // $Students->aliasField('academic_period_id') => $requestData[$this->getAlias()]['academic_period_id'],
                        ])
                        ->order([$Students->aliasField('created') => "DESC"])
                        ->first();
                    $obj['institution_id'] = $result !== null ? $result->institution_id : null;
                }
                //POCOR-7393 ends (4th case)
                // subject students logic
                foreach ($examCentreSubjects as $examItemId => $subjectId) {
                    $obj['examination_centres_examinations_subjects'][] = [
                        'examination_centre_id' => $requestData[$this->getAlias()]['examination_centre_id'],
                        'examination_subject_id' => $examItemId,
                        '_joinData' => [
                            'education_subject_id' => $subjectId,
                            'examination_centre_id' => $selectedExaminationCentre,
                            'examination_id' => $selectedExamination,
                            'student_id' => $requestData[$this->getAlias()]['student_id'],
                            'examination_subject_id' => $examItemId
                        ]
                    ];
                }

                $patchOptions['associated'] = [
                    'ExaminationCentresExaminationsSubjects' => ['validate' => false]
                ];

                $success = $this->getConnection()->transactional(function () use ($obj, $entity, $patchOptions) {
                    $examCentreStudentEntity = $this->newEntity($obj, $patchOptions);
                    if ($examCentreStudentEntity->getError('student_id')) {
                        $entity->setError('student_id', $examCentreStudentEntity->getError('student_id'));
                    }
                    if ($examCentreStudentEntity->getError('registration_number')) {
                        $entity->setError('registration_number', $examCentreStudentEntity->getError('registration_number'));
                    }
                    if (!$this->save($examCentreStudentEntity)) {
                        return false;
                    }
                    return true;
                });

                if ($success) {
                    //POCOR-7512 start
                    if ($entity->examination_subjects) {
                        $examinationStudentSubjects = TableRegistry::getTableLocator()->get('Examination.ExaminationStudentSubjects');
                        if (!empty($requestData[$this->getAlias()]['student_id'])) {
                            foreach ($entity->examination_subjects as $Key => $value) {
                                if ($value['selected'] == 1) {
                                    $data = $examinationStudentSubjects->newEntity([
                                        'student_id' => $requestData[$this->getAlias()]['student_id'],
                                        'examination_subject_id' => $value['subject_id']
                                    ]);
                                    $result = $examinationStudentSubjects->save($data);
                                }
                            }
                        }
                    }
                    //POCOR-7512 end
                    $studentCount = $this->find()
                        ->where([$this->aliasField('examination_centre_id') => $entity->examination_centre_id])
                        ->group([$this->aliasField('student_id')])
                        ->count();

                    $this->ExaminationCentresExaminations->updateAll(['total_registered' => $studentCount], ['examination_centre_id' => $entity->examination_centre_id, 'examination_id' => $entity->examination_id]);
                }

                // auto assignment to a room
                if ($autoAssignToRoom) {
                    if ($success) {
                        $examCentreRooms = $this->ExaminationCentres->ExaminationCentreRooms
                            ->find()
                            ->leftJoin(
                                ['ExaminationCentreRoomsExaminationsStudents' => 'examination_centre_rooms_examinations_students'],
                                [
                                    'ExaminationCentreRoomsExaminationsStudents.examination_centre_room_id = ' . $this->ExaminationCentres->ExaminationCentreRooms->aliasField('id'),
                                    'ExaminationCentreRoomsExaminationsStudents.examination_id = ' . $selectedExamination
                                ]
                            )
                            ->order([$this->ExaminationCentres->ExaminationCentreRooms->aliasField('id')])
                            ->select([
                                $this->ExaminationCentres->ExaminationCentreRooms->aliasField('id'),
                                $this->ExaminationCentres->ExaminationCentreRooms->aliasField('number_of_seats'),
                                'seats_taken' => 'COUNT(ExaminationCentreRoomsExaminationsStudents.student_id)'
                            ])
                            ->where([$this->ExaminationCentres->ExaminationCentreRooms->aliasField('examination_centre_id') => $selectedExaminationCentre])
                            ->group([$this->ExaminationCentres->ExaminationCentreRooms->aliasField('id')])
                            ->toArray();

                        $assigned = false;
                        foreach ($examCentreRooms as $room) {
                            $counter = $room->number_of_seats - $room->seats_taken;
                            if ($counter > 0) {
                                $newEntity = [
                                    'examination_centre_room_id' => $room->id,
                                    'student_id' => $requestData[$this->getAlias()]['student_id'],
                                    'examination_id' => $selectedExamination,
                                    'examination_centre_id' => $selectedExaminationCentre
                                ];

                                $ExaminationCentreRoomStudents = TableRegistry::getTableLocator()->get('Examination.ExaminationCentreRoomsExaminationsStudents');
                                $examCentreRoomStudentEntity = $ExaminationCentreRoomStudents->newEntity($newEntity);
                                if ($ExaminationCentreRoomStudents->save($examCentreRoomStudentEntity)) {
                                    $assigned = true;
                                    break;
                                }
                            }
                        }

                        // if student was not added to any room
                        // if (!$assigned) {
                        //     $model->Alert->warning($this->aliasField('notAssignedRoom'));
                        // }
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
    //POCOR-7512 start
    public function onUpdateFieldSubjectId(EventInterface $event, array $attr, $action, $request)
    {
        $subjects = [];
        if ($action == 'add') {
            if (!empty($request->getData()[$this->getAlias()]['examination_id'])) {
                $ExaminationSubjects = TableRegistry::getTableLocator()->get('Examination.ExaminationSubjects');
                $subjects = $ExaminationSubjects->find()->where([
                    $ExaminationSubjects->aliasField('examination_id') => $request->getData()[$this->getAlias()]['examination_id']
                ])->toArray();
            }
            $attr['label'] = "Education Subjects";
            $attr['type'] = 'element';
            $attr['element'] = 'Examination.institution_examination_subjects';
            $attr['data'] = $subjects;
            return $attr;
        }
    }
    public function editAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {

        $subjectTable = TableRegistry::getTableLocator()->get('Examination.ExaminationSubjects');
        $subjectData = $subjectTable->find('all')->select([
            'id' => $subjectTable->aliasField('id'),
            'name' => $subjectTable->aliasField('name'),
            'code' => $subjectTable->aliasField('code'),
        ])->where([$subjectTable->aliasField('examination_id') => $entity->examination_id])->toArray();
        $entity['examination_subjects'] = $subjectData;

        $this->field('academic_period_id', ['type' => 'readonly']);
        $this->field('examination_id', ['type' => 'readonly']);
        $this->field('openemis_no', ['type' => 'readonly']);
        $this->field('student_id', ['type' => 'readonly']);


        $this->field('examination_subjects', [
            'type' => 'element',
            'element' => 'Examination.institution_examination_subjects',
            'data' => $entity['examination_subjects']
        ]);
        $this->setFieldOrder(['academic_period_id', 'examination_id', 'registration_number', 'openemis_no', 'student_id', 'examination_subjects']);
    }
    public function editBeforeSave(EventInterface $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra)
    {

        $examinationStudentSubjects = TableRegistry::getTableLocator()->get('Examination.ExaminationStudentSubjects');
        $examinationSubjects = $examinationStudentSubjects->find()
            ->where(
                ['student_id' => $entity->student_id]
            )->toArray();
        if ($examinationSubjects) {
            foreach ($examinationSubjects as $data) {
                $deleteEntity =   $examinationStudentSubjects->delete($examinationStudentSubjects->get($data->id));
            }
        }

        $entitydata = [];
        foreach ($entity->examination_subjects as $Key => $value) {

            if ($value['selected'] == 1) {
                $studSubArr = array(
                    'student_id' => $entity->student_id,
                    'examination_subject_id' => $value['subject_id']
                );
                $new = $examinationStudentSubjects->newEntity($studSubArr);
                $new =  $examinationStudentSubjects->patchEntity($new, $studSubArr);

                $examinationStudentSubjects->save($new);
            };
        }
    }
    //POCOR-7512 end

    //POCOR-7509 start
    //POCOR-7509
    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $query->select([
            'ExaminationCentresExaminationsStudents.id',
            'ExaminationCentresExaminationsStudents.student_id',
            'ExaminationCentresExaminationsStudents.academic_period_id',
            'ExaminationCentresExaminationsStudents.examination_id',
            'ExaminationCentresExaminationsStudents.registration_number',
            'ExaminationCentresExaminationsStudents.examination_centre_id',
            'ExaminationCentresExaminationsStudents.sync_status',
            'ExaminationCentresExaminationsStudents.last_synced',

            'Users.openemis_no',
            'Users.first_name',
            'Users.middle_name',
            'Users.third_name',
            'Users.last_name',
            'Users.preferred_name',
            'Users.date_of_birth',
            'Users.identity_number',

            'MainIdentityTypes.name',
            'Genders.name',
            'MainNationalities.name',

            'Institutions.code',
            'Institutions.name'
        ]);
    }

    //POCOR-7509 start
    public function onUpdateActionButtons(EventInterface $event, Entity $entity, array $buttons)
    {
        $referrerUrl = $this->request->referer();
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

        if ($this->AccessControl->check(['Examinations', 'syncResultFromExam', 'execute'])) {

            $syncUserConfigured = TableRegistry::getTableLocator()->get('Configuration.ConfigExternalDataSourceExam')->getOpenemisExamConfiguration();

            if (!empty($syncUserConfigured)) {

                $params = [
                    'institution_id' => $entity->institution->id,
                    'academic_period_id' => $entity->academic_period_id,
                    'examination_id' => $entity->examination_id,
                    'examination_centre_id' => $entity->examination_centre_id,
                    'openemis_no' => $entity->openemis_no,
                    'referrer' => $referrerUrl,
                ];

                $url = [
                    'plugin' => 'Examination',
                    'controller' => 'Examinations',
                    'action' => 'syncStudentsToExam',
                ];


                $buttons['sync'] = [
                    'label' => '<i class="kd-process"></i>' . __('Sync'),
                    'attr' => [
                        'role' => 'menuitem',
                        'tabindex' => '-1',
                        'escape' => false
                    ],
                    'url' => $this->setQueryString($url, $params),
                ];
            }
        }

        return $buttons;
    }

    /**
     * Returns the sync status as a human-readable string.
     *
     * @param \Cake\Event\EventInterface $event The event instance.
     * @param \Cake\ORM\Entity $entity The entity object.
     * @return string|null The human-readable sync status.
     */
    public function onGetSyncStatus(EventInterface $event, Entity $entity)
    {

        switch ($entity->sync_status) {
            case 1:
                return 'Completed'; // Sync completed
            case -1:
                return 'Error'; // Error during sync
            default:
                return null;
        }
    }

    /**
     * Returns the last synced timestamp as a formatted string.
     *
     * @param \Cake\Event\EventInterface $event The event instance.
     * @param \Cake\ORM\Entity $entity The entity object.
     * @return string|null The formatted date/time of the last sync.
     */

    public function onGetLastSynced(EventInterface $event, Entity $entity)
    {

        if ($entity->last_synced instanceof FrozenTime || $entity->last_synced instanceof \DateTime) {
            return $entity->last_synced->format('Y-m-d H:i:s');
        }

        return null;
    }
    public function indexAfterAction(EventInterface $event, $data)
    {
        $this->field('date_of_birth', ['visible' => false]);
        $this->field('gender_id', ['visible' => false]);
        $this->field('identity_type', ['visible' => false]);
        $this->field('nationality', ['visible' => false]);
        $this->field('identity_number', ['visible' => false]);
        $this->field('repeated', ['visible' => false]);
        $this->field('transferred', ['visible' => false]);
        $this->field('sync_status', ['visible' => true]);
        $this->field('last_synced', ['visible' => true]);
    }

    //POCOR-7509 end
}
