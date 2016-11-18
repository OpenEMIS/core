<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\Utility\Text;
use Cake\I18n\Time;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;
use Cake\Utility\Security;

class InstitutionExaminationStudentsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('examination_centre_students');
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Examinations', ['className' => 'Examination.Examinations']);
        $this->belongsTo('ExaminationCentres', ['className' => 'Examination.ExaminationCentres']);
        $this->hasMany('ExaminationCentreSubjects', ['className' => 'Examination.ExaminationCentreSubjects']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);

        $this->addBehavior('User.AdvancedNameSearch');
        $this->addBehavior('Examination.RegisteredStudents');
        $this->addBehavior('Excel', [
            'excludes' => ['id', 'education_subject_id'],
            'pages' => ['index'],
            'filename' => 'RegisteredStudents',
            'orientation' => 'landscape'
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
            ]);
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->isNew()) {
            $hashString = $entity->examination_centre_id . ',' . $entity->student_id . ','. $entity->education_subject_id;
            $entity->id = Security::hash($hashString, 'sha256');
        }
    }

    public function onExcelBeforeStart (Event $event, ArrayObject $settings, ArrayObject $sheets)
    {
        $sheets[] = [
            'name' => $this->alias(),
            'table' => $this,
            'query' => $this->find(),
            'orientation' => 'landscape'
        ];
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $institutionId = $this->Session->read('Institution.Institutions.id');
        $examinationId = $this->request->query('examination_id');

        $query
            ->contain(['Users.Genders', 'Institutions'])
            ->select(['openemis_no' => 'Users.openemis_no', 'gender_name' => 'Genders.name', 'dob' => 'Users.date_of_birth', 'code' => 'Institutions.code'])
            ->where([$this->aliasField('institution_id') => $institutionId,
                $this->aliasField('examination_id') => $examinationId])
            ->group([$this->aliasField('student_id')]);
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $newFields = [];

        $newFields[] = [
            'key' => 'Institutions.code',
            'field' => 'code',
            'type' => 'string',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'InstitutionExaminationStudents.institution_id',
            'field' => 'institution_id',
            'type' => 'integer',
            'label' => 'Institution',
        ];

        $newFields[] = [
            'key' => 'InstitutionExaminationStudents.education_grade_id',
            'field' => 'education_grade_id',
            'type' => 'integer',
            'label' => 'Education Grade',
        ];

        $newFields[] = [
            'key' => 'Users.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'InstitutionExaminationStudents.student_id',
            'field' => 'student_id',
            'type' => 'integer',
            'label' => 'Student',
        ];

        $newFields[] = [
            'key' => 'Users.gender_id',
            'field' => 'gender_name',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Users.date_of_birth',
            'field' => 'dob',
            'type' => 'date',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'InstitutionExaminationStudents.academic_period_id',
            'field' => 'academic_period_id',
            'type' => 'integer',
            'label' => 'Academic Period',
        ];

        $newFields[] = [
            'key' => 'InstitutionExaminationStudents.examination_id',
            'field' => 'examination_id',
            'type' => 'integer',
            'label' => 'Examination',
        ];

        $newFields[] = [
            'key' => 'InstitutionExaminationStudents.examination_centre_id',
            'field' => 'examination_centre_id',
            'type' => 'integer',
            'label' => 'Examination Centre',
        ];

        $fields->exchangeArray($newFields);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        //work around for export button showing in pages not specified
        if ($this->action != 'index') {
            if (isset($extra['toolbarButtons']['export']))
            {
                unset($extra['toolbarButtons']['export']);
            }
        }
        $this->fields['total_mark']['visible'] = false;
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();

        if (array_key_exists('add', $toolbarButtonsArray)) {
            $toolbarButtonsArray['add']['attr']['title'] = __('Register');
        }

        $undoButton['url'] = [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'UndoExaminationRegistration',
            'add'
        ];
        $undoButton['type'] = 'button';
        $undoButton['label'] = '<i class="fa fa-undo"></i>';
        $undoButton['attr']['class'] = 'btn btn-xs btn-default icon-big';
        $undoButton['attr']['data-toggle'] = 'tooltip';
        $undoButton['attr']['data-placement'] = 'bottom';
        $undoButton['attr']['escape'] = false;
        $undoButton['attr']['title'] = __('Unregister');
        $toolbarButtonsArray['undo'] = $undoButton;

        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);

        $examinationId = $this->request->query('examination_id');

        if ($examinationId == -1 || !$examinationId || !$this->AccessControl->check(['Institutions', 'ExaminationStudents', 'excel']))
        {
            if (isset($extra['toolbarButtons']['export']))
            {
                unset($extra['toolbarButtons']['export']);
            }
        }
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $extra['elements']['controls'] = ['name' => 'Examination.controls', 'data' => [], 'options' => [], 'order' => 1];
    }

    public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('academic_period_id', ['type' => 'select']);
        $this->field('examination_id', ['type' => 'select', 'onChangeReload' => true]);
        $this->field('examination_education_grade', ['type' => 'readonly']);
        $this->field('examination_centre_id', ['type' => 'select', 'onChangeReload' => true, 'entity' => $entity]);
        $this->field('special_needs', ['type' => 'readonly']);
        $this->field('institution_class_id', ['type' => 'select', 'onChangeReload' => true, 'entity' => $entity]);
        $this->field('student_id', ['entity' => $entity]);
        $this->field('education_grade_id', ['type' => 'hidden']);
        $this->field('total_mark', ['visible' => false]);
        $this->field('registration_number', ['visible' => false]);

        $this->field('registration_number' , ['visible' => false]);
        $this->field('total_mark' , ['visible' => false]);

        $this->setFieldOrder([
            'academic_period_id', 'examination_id', 'examination_education_grade', 'examination_centre_id', 'special_needs', 'institution_class_id', 'student_id'
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
            if (array_key_exists('institution_class_id', $data[$this->alias()])) {
                unset($data[$this->alias()]['institution_class_id']);
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
            if (array_key_exists('institution_class_id', $data[$this->alias()])) {
                unset($data[$this->alias()]['institution_class_id']);
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

    public function onUpdateFieldExaminationCentreId(Event $event, array $attr, $action, $request)
    {
        $attr['options'] = [];
        if ($action == 'add') {
            if (!empty($request->data[$this->alias()]['examination_id'])) {
                $institutionId = $attr['entity']->institution_id;
                $selectedExamination = $request->data[$this->alias()]['examination_id'];
                $query = $this->ExaminationCentres
                    ->find('list' ,['keyField' => 'id', 'valueField' => 'code_name'])
                    ->innerJoinWith('LinkedInstitutions')
                    ->where([
                        $this->ExaminationCentres->aliasField('examination_id') => $selectedExamination,
                        'ExaminationCentresInstitutions.institution_id' => $institutionId
                    ]);

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
                    'valueField' => 'special_need_type.name'
                ])
                ->contain('SpecialNeedTypes')
                ->where([$ExaminationCentreSpecialNeeds->aliasField('examination_centre_id') => $examinationCentreId])
                ->toArray();

            if (!empty($query)) {
                $specialNeeds = implode(', ', $query);
            }

            $attr['attr']['value'] = $specialNeeds;
        }

        return $attr;
    }

    public function onUpdateFieldInstitutionClassId(Event $event, array $attr, $action, $request)
    {
        $classes = [];

        if ($action == 'add') {
            if (!empty($request->data[$this->alias()]['examination_id'])) {
                $institutionId = $attr['entity']->institution_id;
                $examinationId = $request->data[$this->alias()]['examination_id'];
                $educationGradeId = $this->Examinations->get($examinationId)->education_grade_id;
                $academicPeriodId = $request->data[$this->alias()]['academic_period_id'];

                $InstitutionClass = TableRegistry::get('Institution.InstitutionClasses');
                $classes = $InstitutionClass
                    ->find('list')
                    ->matching('ClassGrades')
                    ->where([$InstitutionClass->aliasField('institution_id') => $institutionId,
                        $InstitutionClass->aliasField('academic_period_id') => $academicPeriodId,
                        'ClassGrades.education_grade_id' => $educationGradeId])
                    ->order($InstitutionClass->aliasField('name'))
                    ->toArray();
            }

            $attr['options'] = $classes;
        }

        return $attr;
    }

    public function onUpdateFieldStudentId(Event $event, array $attr, $action, $request)
    {
        $students = [];

        if ($action == 'add') {
            if (!empty($request->data[$this->alias()]['examination_id']) && !empty($request->data[$this->alias()]['institution_class_id'])) {
                $institutionId = $attr['entity']->institution_id;
                $academicPeriodId = $request->data[$this->alias()]['academic_period_id'];
                $examinationId = $request->data[$this->alias()]['examination_id'];
                $institutionClassId = $request->data[$this->alias()]['institution_class_id'];
                $enrolledStatus = TableRegistry::get('Student.StudentStatuses')->getIdByCode('CURRENT');
                $examinationCentreId = $request->data[$this->alias()]['examination_centre_id'];

                $ClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
                $students = $ClassStudents->find()
                    ->matching('EducationGrades')
                    ->leftJoin(['InstitutionExaminationStudents' => 'examination_centre_students'], [
                        'InstitutionExaminationStudents.examination_id' => $examinationId,
                        'InstitutionExaminationStudents.student_id = '.$ClassStudents->aliasField('student_id')
                    ])
                    ->contain('Users.SpecialNeeds.SpecialNeedTypes')
                    ->where([
                        $ClassStudents->aliasField('institution_id') => $institutionId,
                        $ClassStudents->aliasField('academic_period_id') => $academicPeriodId,
                        $ClassStudents->aliasField('institution_class_id') => $institutionClassId,
                        $ClassStudents->aliasField('student_status_id') => $enrolledStatus,
                        'InstitutionExaminationStudents.student_id IS NULL'
                    ])
                    ->group($ClassStudents->aliasField('student_id'))
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
        $requestData[$this->alias()]['student_id'] = 0;
        $requestData[$this->alias()]['education_subject_id'] = 0;
    }

    public function addBeforeSave(Event $event, $entity, $requestData, $extra)
    {
        $process = function ($model, $entity) use ($requestData) {
            $errors = $entity->errors();
            if (!empty($errors)) {
                return false;
            }

            if (!empty($requestData[$this->alias()]['examination_students']) && !empty($requestData[$this->alias()]['examination_centre_id'])) {
                $students = $requestData[$this->alias()]['examination_students'];
                $examinationCentre = $this->ExaminationCentres->get($requestData[$this->alias()]['examination_centre_id']);
                $newEntities = [];

                $selectedExaminationCentre = $requestData[$this->alias()]['examination_centre_id'];
                $ExaminationCentreSubjects = $this->ExaminationCentreSubjects->getExaminationCentreSubjects($selectedExaminationCentre);
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
                        $obj['counterNo'] = $key;
                        $roomStudents[] = $obj;
                        $studentCount++;
                        foreach($ExaminationCentreSubjects as $subject => $name) {
                            $obj['education_subject_id'] = $subject;
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

                if ($entity->auto_assign_to_rooms) {
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
                            return false;
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
