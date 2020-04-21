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

class BulkStudentRegistrationTable extends ControllerActionTable
{
    use OptionsTrait;

    public function initialize(array $config)
    {
        $this->table('examination_centres_examinations_students');
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
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

        $this->addBehavior('CompositeKey');

        $this->toggle('index', false);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.Navigation.breadcrumb'] = 'onGetBreadcrumb';
        return $events;
    }

    public function onGetBreadcrumb(Event $event, Request $request, Component $Navigation, $persona)
    {
        $indexUrl = ['plugin' => 'Examination', 'controller' => 'Examinations', 'action' => 'RegisteredStudents'];
        $Navigation->substituteCrumb('Examination', 'Examination', $indexUrl);
    }

    public function validationDefault(Validator $validator) {
        $validator = parent::validationDefault($validator);
        return $validator
            ->allowEmpty('registration_number')
            ->add('registration_number', 'ruleUnique', [
                'rule' => ['validateUnique', ['scope' => ['examination_id']]],
                'provider' => 'table'
            ])
            ->requirePresence('institution_id')
            ->requirePresence('auto_assign_to_rooms');
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $extra['config']['selectedLink'] = ['controller' => 'Examinations', 'action' => 'RegisteredStudents'];
        $this->controller->getStudentsTab('RegisteredStudents');
    }

    public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('academic_period_id', ['type' => 'select']);
        $this->field('examination_id', ['type' => 'select', 'onChangeReload' => true]);
        $this->field('examination_education_grade', ['type' => 'readonly']);
        $this->field('special_needs_required', ['type' => 'chosenSelect', 'onChangeReload' => true]);
        $this->field('examination_centre_id', ['type' => 'select', 'onChangeReload' => true]);
        $this->field('special_needs', ['type' => 'readonly']);
        $this->field('institution_id', ['type' => 'select', 'onChangeReload' => true, 'entity' => $entity]);
        $this->field('auto_assign_to_rooms', ['type' => 'select', 'options' => $this->getSelectOptions('general.yesno')]);
        $this->field('student_id', ['entity' => $entity]);
        $this->field('registration_number', ['visible' => false]);

        $extra['toolbarButtons']['back']['url'] = ['plugin' => 'Examination', 'controller' => 'Examinations', 'action' => 'RegisteredStudents'];

        $this->setFieldOrder([
            'academic_period_id', 'examination_id', 'examination_education_grade', 'special_needs_required', 'examination_centre_id', 'special_needs', 'auto_assign_to_rooms', 'institution_id', 'student_id'
        ]);
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            $selectedAcademicPeriod = $this->AcademicPeriods->getCurrent();

            $attr['default'] = $selectedAcademicPeriod;
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
        $examinationOptions = [];

        if ($action == 'add') {
            $todayDate = Time::now();

            if(!empty($request->data[$this->alias()]['academic_period_id'])) {
                $selectedAcademicPeriod = $request->data[$this->alias()]['academic_period_id'];
            } else {
                $selectedAcademicPeriod = $this->AcademicPeriods->getCurrent();
            }

            $Examinations = $this->Examinations;
            $examinationOptions = $Examinations->find('list')
                ->where([$Examinations->aliasField('academic_period_id') => $selectedAcademicPeriod])
                ->toArray();
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

            $attr['options'] = $examinationOptions;
            $attr['onChangeReload'] = 'changeExaminationId';
        }

        return $attr;
    }

    public function addOnChangeExaminationId(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        if (array_key_exists($this->alias(), $data)) {
            if (array_key_exists('examination_centre_id', $data[$this->alias()])) {
                unset($data[$this->alias()]['examination_centre_id']);
            }
            if (array_key_exists('institution_id', $data[$this->alias()])) {
                unset($data[$this->alias()]['institution_id']);
            }
        }
    }

    public function onUpdateFieldExaminationEducationGrade(Event $event, array $attr, $action, $request)
    {
        $educationGrade = '';

        if (!empty($request->data[$this->alias()]['examination_id'])) {
            $selectedExamination = $request->data[$this->alias()]['examination_id'];
            $Examinations = $this->Examinations
                ->get($selectedExamination, [
                    'contain' => ['EducationGrades']
                ])
                ->toArray();

            $educationGrade = $Examinations['education_grade']['name'];
            $request->data[$this->alias()]['education_grade_id'] = $Examinations['education_grade']['id'];
            $attr['attr']['value'] = $educationGrade;
        }

        return $attr;
    }

    public function onUpdateFieldSpecialNeedsRequired(Event $event, array $attr, $action, $request)
    {
        $specialNeedOptions = [];

        if ($action == 'add') {
            $SpecialNeedTypes = TableRegistry::get('SpecialNeeds.SpecialNeedsTypes');
            $specialNeedOptions = $SpecialNeedTypes->getVisibleNeedTypes();
        }

        $attr['options'] = $specialNeedOptions;
        return $attr;
    }

    public function onUpdateFieldExaminationCentreId(Event $event, array $attr, $action, $request)
    {
        $attr['options'] = [];
        if ($action == 'add') {
            if (!empty($request->data[$this->alias()]['examination_id'])) {
                if(!empty($request->data[$this->alias()]['academic_period_id'])) {
                    $selectedAcademicPeriod = $request->data[$this->alias()]['academic_period_id'];
                } else {
                    $selectedAcademicPeriod = $this->AcademicPeriods->getCurrent();
                }

                $selectedExamination = $request->data[$this->alias()]['examination_id'];
                $selectedSpecialNeeds = $request->data[$this->alias()]['special_needs_required']['_ids'];

                $query = $this->ExaminationCentres
                    ->find('list' ,['keyField' => 'id', 'valueField' => 'code_name'])
                    ->matching('Examinations')
                    ->where([
                        $this->Examinations->aliasField('id') => $selectedExamination,
                        $this->ExaminationCentres->aliasField('academic_period_id') => $selectedAcademicPeriod
                    ])
                    ->order([$this->ExaminationCentres->aliasField('code')]);

                if (!empty($selectedSpecialNeeds)) {
                    $query->find('bySpecialNeeds', ['selectedSpecialNeeds' => $selectedSpecialNeeds]);
                }

                $attr['options'] = $query->toArray();
            }
        }
        return $attr;
    }

    public function onUpdateFieldSpecialNeeds(Event $event, array $attr, $action, $request)
    {
        $specialNeeds = [];

        if (!empty($request->data[$this->alias()]['examination_centre_id'])) {
            $examinationCentreId = $request->data[$this->alias()]['examination_centre_id'];
            $ExaminationCentreSpecialNeeds = TableRegistry::get('Examination.ExaminationCentreSpecialNeeds');
            $query = $ExaminationCentreSpecialNeeds
                ->find('list', [
                    'keyField' => 'special_need_type_id',
                    'valueField' => 'special_needs_type.name'
                ])
                ->contain('SpecialNeedsTypes')
                ->where([$ExaminationCentreSpecialNeeds->aliasField('examination_centre_id') => $examinationCentreId])
                ->toArray();

            if (!empty($query)) {
                $specialNeeds = implode(', ', $query);
            }

            $attr['attr']['value'] = $specialNeeds;
        }

        return $attr;
    }

    public function onUpdateFieldInstitutionId(Event $event, array $attr, $action, $request)
    {
        $institutions = [];

        if ($action == 'add') {
            if (!empty($request->data[$this->alias()]['examination_id'])) {
                $examinationId = $request->data[$this->alias()]['examination_id'];
                $educationGradeId = $this->Examinations->get($examinationId)->education_grade_id;
                $academicPeriodId = $request->data[$this->alias()]['academic_period_id'];

                $Institutions = TableRegistry::get('Institution.Institutions');
                $InstitutionGradesTable = $this->Institutions->InstitutionGrades;
                $institutionsData = $InstitutionGradesTable
                    ->find()
                    ->matching('Institutions', function($q) use ($Institutions) {
                        return $q->where(['Institutions.classification' => $Institutions::ACADEMIC]);
                    })
                    ->where([$InstitutionGradesTable->aliasField('education_grade_id') => $educationGradeId])
                    ->select(['institution_id' => 'Institutions.id', 'institution_name' => 'Institutions.name', 'institution_code' => 'Institutions.code'])
                    ->group('institution_id')
                    ->hydrate(false)
                    ->toArray();
                foreach ($institutionsData as $data) {
                    $institutions[$data['institution_id']] = $data['institution_code']. ' - ' . $data['institution_name'];
                }
            }
            $attr['options'] = $institutions;
        }

        return $attr;
    }

    public function onUpdateFieldStudentId(Event $event, array $attr, $action, $request)
    {
        $students = [];

        if ($action == 'add') {
            if (!empty($request->data[$this->alias()]['examination_id']) && !empty($request->data[$this->alias()]['institution_id'])) {
                $institutionId = $request->data[$this->alias()]['institution_id'];
                $academicPeriodId = $request->data[$this->alias()]['academic_period_id'];
                $examinationId = $request->data[$this->alias()]['examination_id'];
                $educationGradeId = $this->Examinations->get($examinationId)->education_grade_id;
                $enrolledStatus = TableRegistry::get('Student.StudentStatuses')->getIdByCode('CURRENT');
                $examinationCentreId = $request->data[$this->alias()]['examination_centre_id'];

                $InstitutionStudents = $this->Institutions->Students;
                $students = $InstitutionStudents->find()
                    ->matching('EducationGrades')
                    ->leftJoin(['ExaminationCentresExaminationsStudents' => 'examination_centres_examinations_students'], [
                        'ExaminationCentresExaminationsStudents.examination_id' => $examinationId,
                        'ExaminationCentresExaminationsStudents.student_id = '.$InstitutionStudents->aliasField('student_id')
                    ])
                    ->contain('Users.SpecialNeeds.SpecialNeedsTypes')
                    ->leftJoinWith('Users.SpecialNeeds')
                    ->where([
                        $InstitutionStudents->aliasField('institution_id') => $institutionId,
                        $InstitutionStudents->aliasField('academic_period_id') => $academicPeriodId,
                        $InstitutionStudents->aliasField('student_status_id') => $enrolledStatus,
                        $InstitutionStudents->aliasField('education_grade_id') => $educationGradeId,
                        'ExaminationCentresExaminationsStudents.student_id IS NULL'
                    ])
                    ->order(['SpecialNeeds.id' => 'desc'])
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
        $extra['redirect'] = ['plugin' => 'Examination', 'controller' => 'Examinations', 'action' => 'RegisteredStudents', 'index'];
        $requestData[$this->alias()]['student_id'] = 0;
    }

    public function addBeforeSave(Event $event, $entity, $requestData, $extra)
    {
        $process = function ($model, $entity) use ($requestData) {
            if (!empty($requestData[$this->alias()]['examination_students']) && !empty($requestData[$this->alias()]['examination_centre_id'])) {
                $students = $requestData[$this->alias()]['examination_students'];
                $newEntities = [];

                $selectedExaminationCentre = $requestData[$this->alias()]['examination_centre_id'];
                $selectedExamination = $requestData[$this->alias()]['examination_id'];
                $ExaminationCentreSubjects = TableRegistry::get('Examination.ExaminationCentresExaminationsSubjects');
                $examCentreSubjects = $ExaminationCentreSubjects->getExaminationCentreSubjects($selectedExaminationCentre, $selectedExamination);

                $studentCount = 0;
                $roomStudents = [];
                foreach ($students as $key => $student) {
                    $obj = [];
                    if ($student['selected'] == 1) {
                        $obj['student_id'] = $student['student_id'];
                        $obj['registration_number'] = $student['registration_number'];
                        $obj['institution_id'] = $requestData[$this->alias()]['institution_id'];
                        $obj['academic_period_id'] = $requestData[$this->alias()]['academic_period_id'];
                        $obj['examination_id'] = $requestData[$this->alias()]['examination_id'];
                        $obj['examination_centre_id'] = $requestData[$this->alias()]['examination_centre_id'];
                        $obj['auto_assign_to_rooms'] = $entity->auto_assign_to_rooms;
                        $obj['counterNo'] = $key;
                        $roomStudents[] = $obj;
                        $studentCount++;

                        foreach($examCentreSubjects as $examItemId => $subjectId) {
                            $obj['examination_centres_examinations_subjects'][] = [
                                'examination_centre_id' => $selectedExaminationCentre,
                                'examination_item_id' => $examItemId,
                                '_joinData' => [
                                    'education_subject_id' => $subjectId,
                                    'examination_item_id' => $examItemId,
                                    'examination_centre_id' => $selectedExaminationCentre,
                                    'student_id' => $student['student_id'],
                                    'examination_id' => $selectedExamination

                                ]
                            ];
                        }
                        $newEntities[] = $obj;
                    }
                }
                if (empty($newEntities)) {
                    $model->Alert->warning($this->aliasField('noStudentSelected'));
                    $entity->errors('student_id', __('There are no students selected'));
                    return false;
                }

                $success = $this->connection()->transactional(function() use ($newEntities, $entity) {
                    $patchOptions['associated'] = ['ExaminationCentresExaminationsSubjects' => ['validate' => false]];
                    $return = true;

                    foreach ($newEntities as $key => $newEntity) {
                        $examCentreStudentEntity = $this->newEntity($newEntity, $patchOptions);
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
                        ->where([
                            $this->aliasField('examination_centre_id') => $entity->examination_centre_id,
                            $this->aliasField('examination_id') => $entity->examination_id
                        ])
                        ->group([$this->aliasField('student_id')])
                        ->count();
                    $this->ExaminationCentresExaminations->updateAll(['total_registered' => $studentCount],['examination_centre_id' => $entity->examination_centre_id, 'examination_id' => $entity->examination_id]);
                }

                if ($entity->auto_assign_to_rooms) {
                    if ($success) {
                        $examCentreRooms = $this->ExaminationCentres->ExaminationCentreRooms
                            ->find()
                            ->leftJoin(['ExaminationCentreRoomsExaminationsStudents' => 'examination_centre_rooms_examinations_students'], [
                                'ExaminationCentreRoomsExaminationsStudents.examination_centre_room_id = '.$this->ExaminationCentres->ExaminationCentreRooms->aliasField('id'),
                                'ExaminationCentreRoomsExaminationsStudents.examination_id = '.$selectedExamination
                            ])
                            ->order([$this->ExaminationCentres->ExaminationCentreRooms->aliasField('id')])
                            ->select([
                                $this->ExaminationCentres->ExaminationCentreRooms->aliasField('id'),
                                $this->ExaminationCentres->ExaminationCentreRooms->aliasField('number_of_seats'),
                                'seats_taken' => 'COUNT(ExaminationCentreRoomsExaminationsStudents.student_id)'])
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
                                    'examination_id' => $examCentreRoomStudent['examination_id'],
                                    'examination_centre_id' => $examCentreRoomStudent['examination_centre_id']
                                ];

                                $ExaminationCentreRoomStudents = TableRegistry::get('Examination.ExaminationCentreRoomsExaminationsStudents');
                                $examCentreRoomStudentEntity = $ExaminationCentreRoomStudents->newEntity($newEntity);
                                $saveSucess = $ExaminationCentreRoomStudents->save($examCentreRoomStudentEntity);
                                $counter--;
                            }
                        }
                        if (!empty($roomStudents)) {
                            $model->Alert->warning($this->aliasField('notAssignedRoom'));
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
