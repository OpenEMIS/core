<?php
namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;
use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\Utility\Text;
use Cake\I18n\Time;

class InstitutionExaminationStudentsTable extends ControllerActionTable {

    public function initialize(array $config) {
        $this->table('examination_centre_students');
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Examinations', ['className' => 'Examination.Examinations']);
        $this->belongsTo('ExaminationCentres', ['className' => 'Examination.ExaminationCentres']);
        $this->hasMany('ExaminationCentreSubjects', ['className' => 'Examination.ExaminationCentreSubjects']);
        $this->hasOne('InstitutionSubjectStudents', ['className' => 'Institution.InstitutionSubjectStudents', 'dependent' => false]);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
    }

    public function indexBeforeAction(Event $event) {
        $this->field('education_subject_id', ['type' => 'select']);
        $this->field('student_id', ['type' => 'select']);
    }

    public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('academic_period_id', ['type' => 'select']);
        $this->field('examination_id', ['type' => 'select', 'onChangeReload' => true]);
        $this->field('examination_education_grade', ['type' => 'readonly']);
        $this->field('special_needs_required', ['type' => 'chosenSelect', 'onChangeReload' => true]);

        $this->field('examination_centre_id', ['type' => 'select', 'onChangeReload' => true]);
        $this->field('capacity', ['type' => 'readonly']);
        $this->field('special_needs', ['type' => 'readonly']);
        $this->field('institution_class_id', ['type' => 'select', 'onChangeReload' => true, 'entity' => $entity]);
        $this->field('student_id', ['entity' => $entity]);
        $this->field('education_grade_id', ['type' => 'hidden']);

        $this->setFieldOrder([
            'academic_period_id', 'examination_id', 'examination_education_grade', 'special_needs_required', 'examination_centre_id', 'capacity', 'special_needs', 'institution_class_id', 'student_id'
        ]);
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, $request) {
        if ($action == 'add') {
            $selectedAcademicPeriod = $this->AcademicPeriods->getCurrent();

            $attr['default'] = $selectedAcademicPeriod;
            $attr['onChangeReload'] = 'changeAcademicPeriodId';
        }

        return $attr;
    }

    public function addOnChangeAcademicPeriodId(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {

        if ($this->request->is(['post', 'put'])) {
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
    }

    public function onUpdateFieldExaminationId(Event $event, array $attr, $action, $request) {
        $examinationOptions = [];

        if ($action == 'add') {
            $todayDate = Time::now()->format('Y-m-d');

            if(!empty($request->data[$this->alias()]['academic_period_id'])) {
                $selectedAcademicPeriod = $request->data[$this->alias()]['academic_period_id'];
            } else {
                $selectedAcademicPeriod = $this->AcademicPeriods->getCurrent();
            }

            $Examinations = $this->Examinations;
            $examinationOptions = $Examinations->find('list')
                ->where([$Examinations->aliasField('academic_period_id') => $selectedAcademicPeriod,
                    $Examinations->aliasField('registration_start_date <=') => $todayDate,
                    $Examinations->aliasField('registration_end_date >=') => $todayDate])
                ->toArray();
        }

        $attr['options'] = $examinationOptions;
        $attr['onChangeReload'] = 'changeExaminationId';
        return $attr;
    }

    public function addOnChangeExaminationId(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {

        if ($this->request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $data)) {
                if (array_key_exists('examination_centre_id', $data[$this->alias()])) {
                    unset($data[$this->alias()]['examination_centre_id']);
                }
                if (array_key_exists('institution_class_id', $data[$this->alias()])) {
                    unset($data[$this->alias()]['institution_class_id']);
                }
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
            $this->request->data[$this->alias()]['education_grade_id'] = $Examinations['education_grade']['id'];
        }

        $attr['attr']['value'] = $educationGrade;
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
        $examinationCentreOptions = [];

        if ($action == 'add') {
            if(!empty($request->data[$this->alias()]['academic_period_id']) && !empty($request->data[$this->alias()]['examination_id'])) {
                $selectedAcademicPeriod = $request->data[$this->alias()]['academic_period_id'];
                $selectedExamination = $request->data[$this->alias()]['examination_id'];
                $selectedSpecialNeeds = $request->data[$this->alias()]['special_needs_required']['_ids'];
                $ExaminationCentres = $this->ExaminationCentres;

                if(!empty($selectedSpecialNeeds)) {
                    $examinationCentreOptions = $ExaminationCentres
                        ->find('list' ,[
                                'keyField' => 'id',
                                'valueField' => 'code_name'
                            ])
                        ->select([
                            'count' => $this->find()->func()->count('*')
                        ])
                        ->matching('ExaminationCentreSpecialNeeds')
                        ->where([
                            $ExaminationCentres->aliasField('academic_period_id') => $selectedAcademicPeriod,
                            $ExaminationCentres->aliasField('examination_id') => $selectedExamination,
                            $this->ExaminationCentres->ExaminationCentreSpecialNeeds->aliasField('special_need_type_id IN') => $selectedSpecialNeeds
                        ])
                        ->autoFields(true)
                        ->group($ExaminationCentres->aliasField('id'))
                        ->having(['count =' => count($selectedSpecialNeeds)])
                        ->toArray();

                } else {
                    $examinationCentreOptions = $ExaminationCentres
                        ->find('list' ,[
                                'keyField' => 'id',
                                'valueField' => 'code_name'
                        ])
                        ->where([$ExaminationCentres->aliasField('academic_period_id') => $selectedAcademicPeriod, $ExaminationCentres->aliasField('examination_id') => $selectedExamination])
                        ->toArray();
                }
            }
        }

        $attr['options'] = $examinationCentreOptions;
        return $attr;
    }

    public function onUpdateFieldCapacity(Event $event, array $attr, $action, $request) {
        $capacity = '';

        if (!empty($request->data[$this->alias()]['examination_centre_id'])) {
            $examinationCentreId = $request->data[$this->alias()]['examination_centre_id'];
            $examinationCentres = $this->ExaminationCentres
                ->get($examinationCentreId)
                ->toArray();

            $capacity = $examinationCentres['capacity'];
        }

        $attr['attr']['value'] = $capacity;
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
        }

        $attr['attr']['value'] = $specialNeeds;
        return $attr;
    }

    public function onUpdateFieldInstitutionClassId(Event $event, array $attr, $action, $request) {
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
        }
        $attr['options'] = $classes;
        return $attr;
    }

    public function onUpdateFieldStudentId(Event $event, array $attr, $action, $request) {
        $students = [];

        if ($action == 'add') {
            if (!empty($request->data[$this->alias()]['examination_id']) && !empty($request->data[$this->alias()]['institution_class_id'])) {
                $institutionId = $attr['entity']->institution_id;
                $academicPeriodId = $request->data[$this->alias()]['academic_period_id'];
                $institutionClassId = $request->data[$this->alias()]['institution_class_id'];
                $enrolledStatus = TableRegistry::get('Student.StudentStatuses')->getIdByCode('CURRENT');
                $examinationCentreId = $request->data[$this->alias()]['examination_centre_id'];

                $SubjectStudents = $this->InstitutionSubjectStudents;
                $students = $SubjectStudents->find()
                    ->matching('ClassStudents.EducationGrades')
                    ->leftJoin(['InstitutionExaminationStudents' => 'examination_centre_students'], [
                        'InstitutionExaminationStudents.examination_centre_id' => $examinationCentreId,
                        'InstitutionExaminationStudents.student_id = '.$SubjectStudents->aliasField('student_id')
                    ])
                    ->contain('Users.SpecialNeeds.SpecialNeedTypes')
                    ->where([
                        $SubjectStudents->aliasField('institution_id') => $institutionId,
                        $SubjectStudents->aliasField('academic_period_id') => $academicPeriodId,
                        $SubjectStudents->aliasField('institution_class_id') => $institutionClassId,
                        $SubjectStudents->aliasField('status') => 1,
                        'ClassStudents.student_status_id' => $enrolledStatus,
                        'InstitutionExaminationStudents.student_id IS NULL'
                    ])
                    ->group($SubjectStudents->aliasField('student_id'))
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
            if ($entity->has('examination_students')) {
                $students = $entity->examination_students;
                $newEntities = [];

                $selectedExaminationCentre = $requestData[$this->alias()]['examination_centre_id'];
                $ExaminationCentreSubjects = $this->ExaminationCentreSubjects->getExaminationCentreSubjects($selectedExaminationCentre);

                foreach ($students as $key => $student) {
                    if ($student['selected'] == 1) {
                        $requestData['student_id'] = $student['student_id'];
                        $requestData['institution_id'] = $entity->institution_id;
                        $requestData['education_grade_id'] = $entity->education_grade_id;
                        $requestData['academic_period_id'] = $entity->academic_period_id;
                        $requestData['examination_id'] = $entity->examination_id;
                        $requestData['examination_centre_id'] = $entity->examination_centre_id;

                        foreach($ExaminationCentreSubjects as $subject => $name) {
                            $requestData['id'] = Text::uuid();
                            $requestData['education_subject_id'] = $subject;
                            $newEntities[] = $model->newEntity($requestData->getArrayCopy());
                        }
                    }
                }
                return $model->saveMany($newEntities);
            } else {
                return $model->save($entity);
            }
        };

        return $process;
    }
}
