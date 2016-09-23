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

    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $extra['elements']['controls'] = ['name' => 'Examination.controls', 'data' => [], 'options' => [], 'order' => 1];
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra) {
        $toolbarButtons = $extra['toolbarButtons'];
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
        $undoButton['attr']['title'] = __('Undo');

        $toolbarButtons['undo'] = $undoButton;
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        return $validator->add('available_capacity', 'ruleAvailable', [
                        'rule' => ['checkAvailableCapacity']
                    ])
                    ->allowEmpty('available_capacity');
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
        $this->field('institution_class_id', ['type' => 'select', 'onChangeReload' => true, 'entity' => $entity]);
        $this->field('student_id', ['entity' => $entity]);
        $this->field('education_grade_id', ['type' => 'hidden']);

        $this->setFieldOrder([
            'academic_period_id', 'examination_id', 'examination_education_grade', 'special_needs_required', 'examination_centre_id', 'available_capacity', 'special_needs', 'institution_class_id', 'student_id'
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
            if (array_key_exists('institution_class_id', $data[$this->alias()])) {
                unset($data[$this->alias()]['institution_class_id']);
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

            $attr['options'] = $classes;
        }

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

                $ClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
                $students = $ClassStudents->find()
                    ->matching('EducationGrades')
                    ->leftJoin(['InstitutionExaminationStudents' => 'examination_centre_students'], [
                        'InstitutionExaminationStudents.examination_centre_id' => $examinationCentreId,
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
            if (!empty($requestData[$this->alias()]['examination_students'])) {
                $students = $requestData[$this->alias()]['examination_students'];
                $examinationCentre = $this->ExaminationCentres->get($requestData[$this->alias()]['examination_centre_id']);
                $remainingCapacity = $examinationCentre->total_capacity - $examinationCentre->total_registered;
                $newEntities = [];

                $selectedExaminationCentre = $requestData[$this->alias()]['examination_centre_id'];
                $ExaminationCentreSubjects = $this->ExaminationCentreSubjects->getExaminationCentreSubjects($selectedExaminationCentre);
                $studentCount = 0;
                foreach ($students as $key => $student) {
                    $obj = [];
                    if ($student['selected'] == 1) {
                        $obj['student_id'] = $student['student_id'];
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
                } else if ($remainingCapacity < $studentCount) {
                    $entity->errors('available_capacity', __('Total students exceeded examination centre capacity'));
                }

                if ($model->saveMany($newEntities)) {
                    $studentCount = $this->find()
                        ->where([$this->aliasField('examination_centre_id') => $entity->examination_centre_id])
                        ->group([$this->aliasField('student_id')])
                        ->count();
                    $this->ExaminationCentres->updateAll(['total_registered' => $studentCount],['id' => $entity->examination_centre_id]);
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
}
