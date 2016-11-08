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
    }

    public function validationDefault(Validator $validator) {
        $validator = parent::validationDefault($validator);
        return $validator
            ->allowEmpty('registration_number')
            ->add('registration_number', 'ruleUnique', [
                'rule' => ['validateUnique', ['scope' => ['examination_centre_id', 'student_id', 'education_subject_id']]],
                'provider' => 'table'
            ]);
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $entity->id = Text::uuid();
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->controller->getStudentsTab();
    }

    public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('academic_period_id', ['type' => 'select']);
        $this->field('examination_id', ['type' => 'select', 'onChangeReload' => true]);
        $this->field('examination_education_grade', ['type' => 'readonly']);
        $this->field('special_needs_required', ['type' => 'chosenSelect', 'onChangeReload' => true]);

        $this->field('examination_centre_id', ['type' => 'select', 'onChangeReload' => true]);
        $this->field('available_capacity', ['type' => 'readonly']);
        $this->field('special_needs', ['type' => 'readonly']);
        $this->field('institution_id', ['type' => 'select', 'onChangeReload' => true, 'entity' => $entity]);
        $this->field('student_id', ['entity' => $entity]);
        $this->field('education_grade_id', ['type' => 'hidden']);

        $this->setFieldOrder([
            'academic_period_id', 'examination_id', 'examination_education_grade', 'special_needs_required', 'examination_centre_id', 'available_capacity', 'special_needs', 'institution_id', 'student_id'
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

    public function onUpdateFieldExaminationId(Event $event, array $attr, $action, $request) {
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

    public function onUpdateFieldExaminationEducationGrade(Event $event, array $attr, $action, $request) {
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

    public function onUpdateFieldSpecialNeedsRequired(Event $event, array $attr, $action, $request) {
        $specialNeedOptions = [];

        if ($action == 'add') {
            $SpecialNeedTypes = TableRegistry::get('FieldOption.SpecialNeedTypes');
            $specialNeedOptions = $SpecialNeedTypes->findVisibleNeedTypes();
        }

        $attr['options'] = $specialNeedOptions;
        return $attr;
    }

    public function onUpdateFieldExaminationCentreId(Event $event, array $attr, $action, $request) {
        if ($action == 'add') {
            if (!empty($request->data[$this->alias()]['examination_id'])) {
                $selectedExamination = $request->data[$this->alias()]['examination_id'];
                $selectedSpecialNeeds = $request->data[$this->alias()]['special_needs_required']['_ids'];

                $query = $this->ExaminationCentres
                    ->find('list' ,['keyField' => 'id', 'valueField' => 'code_name'])
                    ->where([$this->ExaminationCentres->aliasField('examination_id') => $selectedExamination]);

                if (!empty($selectedSpecialNeeds)) {
                    $query->find('bySpecialNeeds', ['selectedSpecialNeeds' => $selectedSpecialNeeds]);
                }

                $attr['options'] = $query->toArray();
            }
        }
        return $attr;
    }

    public function onUpdateFieldAvailableCapacity(Event $event, array $attr, $action, $request) {
        $capacity = '';

        if (!empty($request->data[$this->alias()]['examination_centre_id'])) {
            $examinationCentreId = $request->data[$this->alias()]['examination_centre_id'];
            $examinationCentres = $this->ExaminationCentres
                ->get($examinationCentreId)
                ->toArray();

            $capacity = $examinationCentres['total_capacity'] - $examinationCentres['total_registered'];
            $attr['attr']['value'] = $capacity;
            $attr['value'] = $capacity;
        }

        return $attr;
    }

    public function onUpdateFieldSpecialNeeds(Event $event, array $attr, $action, $request) {
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

    public function onUpdateFieldInstitutionId(Event $event, array $attr, $action, $request) {
        $institutions = [];

        if ($action == 'add') {
            if (!empty($request->data[$this->alias()]['examination_id'])) {
                $examinationId = $request->data[$this->alias()]['examination_id'];
                $educationGradeId = $this->Examinations->get($examinationId)->education_grade_id;
                $academicPeriodId = $request->data[$this->alias()]['academic_period_id'];

                $InstitutionClassesTable = $this->Institutions->InstitutionClasses;
                $institutionsData = $InstitutionClassesTable
                    ->find()
                    ->matching('Institutions')
                    ->innerJoinWith('ClassGrades', function ($q) use ($educationGradeId) {
                        return $q->where(['ClassGrades.education_grade_id' => $educationGradeId]);
                    })
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

    public function onUpdateFieldStudentId(Event $event, array $attr, $action, $request) {
        $students = [];

        if ($action == 'add') {
            if (!empty($request->data[$this->alias()]['examination_id']) && !empty($request->data[$this->alias()]['institution_id'])) {
                $institutionId = $request->data[$this->alias()]['institution_id'];
                $academicPeriodId = $request->data[$this->alias()]['academic_period_id'];
                $examinationId = $request->data[$this->alias()]['examination_id'];
                $enrolledStatus = TableRegistry::get('Student.StudentStatuses')->getIdByCode('CURRENT');
                $examinationCentreId = $request->data[$this->alias()]['examination_centre_id'];

                $InstitutionStudents = $this->Institutions->Students;
                $students = $InstitutionStudents->find()
                    ->matching('EducationGrades')
                    ->leftJoin(['InstitutionExaminationStudents' => 'examination_centre_students'], [
                        'InstitutionExaminationStudents.examination_id' => $examinationId,
                        'InstitutionExaminationStudents.student_id = '.$InstitutionStudents->aliasField('student_id')
                    ])
                    ->contain('Users.SpecialNeeds.SpecialNeedTypes')
                    ->where([
                        $InstitutionStudents->aliasField('institution_id') => $institutionId,
                        $InstitutionStudents->aliasField('academic_period_id') => $academicPeriodId,
                        $InstitutionStudents->aliasField('student_status_id') => $enrolledStatus,
                        'InstitutionExaminationStudents.student_id IS NULL'
                    ])
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
        $requestData[$this->alias()]['student_id'] = 0;
        $requestData[$this->alias()]['education_subject_id'] = 0;
    }

    public function addBeforeSave(Event $event, $entity, $requestData, $extra)
    {
        $process = function ($model, $entity) use ($requestData) {
            if (!empty($requestData[$this->alias()]['examination_students'])) {
                $students = $requestData[$this->alias()]['examination_students'];
                $newEntities = [];

                $selectedExaminationCentre = $requestData[$this->alias()]['examination_centre_id'];
                $ExaminationCentreSubjects = TableRegistry::get('Examination.ExaminationCentreSubjects')->getExaminationCentreSubjects($selectedExaminationCentre);
                $studentCount = 0;
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
                        $studentCount++;
                        foreach($ExaminationCentreSubjects as $subject => $name) {
                            $obj['id'] = Text::uuid();
                            $obj['education_subject_id'] = $subject;
                            $newEntities[] = $model->newEntity($obj);
                        }
                    }
                }
                if (empty($newEntities)) {
                    $model->Alert->warning($this->aliasField('noStudentSelected'));
                    $entity->errors('student_id', __('There are no students selected'));
                }

                if ($model->saveMany($newEntities)) {
                    return true;
                } else {
                    return false;
                }
            } else {
                $model->Alert->warning($this->aliasField('noStudentSelected'));
                $entity->errors('student_id', __('There are no students selected'));
                return false;
            }
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
