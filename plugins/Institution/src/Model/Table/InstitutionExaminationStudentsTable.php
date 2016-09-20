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

class InstitutionExaminationStudentsTable extends ControllerActionTable {

    public function initialize(array $config) {
        $this->table('examination_students');
        parent::initialize($config);
        //$this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
        // $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Examinations', ['className' => 'Examination.Examinations']);
        $this->belongsTo('ExaminationCentres', ['className' => 'Examination.ExaminationCentres']);
        $this->hasMany('ExaminationItems', ['className' => 'Examination.ExaminationItems', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ExaminationCentreSubjects', ['className' => 'Examination.ExaminationCentreSubjects']);
        $this->hasOne('InstitutionSubjectStudents', ['className' => 'Institution.InstitutionSubjectStudents']);
    }

    public function afterAction(Event $event, ArrayObject $extra) {
        $entity = $extra['entity'];
        $this->field('academic_period_id', ['type' => 'select', 'empty' => true]);
        $this->field('examination_id', ['type' => 'select', 'onChangeReload' => true]);
        $this->field('special_needs_required', ['type' => 'chosenSelect', 'onChangeReload' => true]);
        $this->field('examination_centre_id', ['type' => 'select', 'onChangeReload' => true]);
        $this->field('capacity', ['type' => 'readonly']);
        $this->field('special_needs', ['type' => 'readonly']);
        $this->field('education_subject_id', ['type' => 'select', 'onChangeReload' => true]);
        $this->field('institution_class_id', ['type' => 'select', 'onChangeReload' => true, 'entity' => $entity]);
        $this->field('student_id', ['entity' => $entity]);

        $this->setFieldOrder([
            'academic_period_id', 'examination_id', 'special_needs_required', 'examination_centre_id', 'capacity', 'special_needs', 'education_subject_id', 'institution_class_id', 'student'
        ]);
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
                $attr['onChangeReload'] = 'changeAcademicPeriodId';
        }

        return $attr;
    }

    public function onUpdateFieldExaminationId(Event $event, array $attr, $action, $request) {
        $examinationOptions = [];

        if ($action == 'add') {
            if(isset($request->data[$this->alias()]['academic_period_id'])) {
                $selectedAcademicPeriod = $request->data[$this->alias()]['academic_period_id'];
            }

            if (!empty($selectedAcademicPeriod)) {
                $Examinations = $this->Examinations;
                $examinationOptions = $Examinations->find('list')
                    ->where([$Examinations->aliasField('academic_period_id') => $selectedAcademicPeriod])
                    ->toArray();
            }
        }

        $attr['options'] = $examinationOptions;
        return $attr;
    }

    public function onUpdateFieldSpecialNeedsRequired(Event $event, array $attr, $action, $request) {
        $types = [];

        if ($action == 'add') {
            $SpecialNeedTypes = TableRegistry::get('FieldOption.SpecialNeedTypes');
            $types = $SpecialNeedTypes->findVisibleNeedTypes();

        }

        $attr['options'] = $types;
        return $attr;
    }

    public function onUpdateFieldExaminationCentreId(Event $event, array $attr, $action, $request) {
        $examinationCentreOptions = [];

        if ($action == 'add') {
            if(isset($request->data[$this->alias()]['academic_period_id']) && isset($request->data[$this->alias()]['examination_id'])) {
                $selectedAcademicPeriod = $request->data[$this->alias()]['academic_period_id'];
                $selectedExamination = $request->data[$this->alias()]['examination_id'];
                $selectedSpecialNeeds = $request->data[$this->alias()]['special_needs_required']['_ids'];
            }

            if (!empty($selectedAcademicPeriod)) {
                $ExaminationCentres = $this->ExaminationCentres;

                if(!empty($selectedSpecialNeeds)) {
                    $examinationCentreOptions = $ExaminationCentres
                        ->find('list')
                        ->select([
                            'count' => $this->find()->func()->count('*')
                        ])
                        ->matching('ExaminationCentreSpecialNeeds')
                        ->where([
                            $ExaminationCentres->aliasField('academic_period_id') => $selectedAcademicPeriod,
                            $ExaminationCentres->aliasField('examination_id') => $selectedExamination,
                            $this->ExaminationCentres->ExaminationCentreSpecialNeeds->aliasField('special_need_type_id IN') => $selectedSpecialNeeds
                        ])
                        ->group($ExaminationCentres->aliasField('id'))
                        ->having(['count =' => count($selectedSpecialNeeds)])
                        ->toArray();
                } else {
                    $examinationCentreOptions = $ExaminationCentres
                        ->find('list')
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
            if (!empty($examinationCentres)) {
                $capacity = $examinationCentres['capacity'];
            }
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

    public function onUpdateFieldEducationSubjectId(Event $event, array $attr, $action, $request) {
        $subjects = [];

        if ($action == 'add') {
            if (isset($request->data[$this->alias()]['examination_centre_id'])) {
                $selectedExaminationCentre = $request->data[$this->alias()]['examination_centre_id'];

                if (!empty($selectedExaminationCentre)) {
                    $subjects = $this->ExaminationCentreSubjects->getExaminationCentreSubjects($selectedExaminationCentre);
                }
            }
        }

        $attr['options'] = $subjects;
        return $attr;
    }

    public function onUpdateFieldInstitutionClassId(Event $event, array $attr, $action, $request) {
        $classes = [];

        if ($action == 'add') {
            if (!empty($request->data[$this->alias()]['examination_id'])) {
                $institutionId = $attr['entity']->institution_id;
                $examinationId = $request->data[$this->alias()]['examination_id'];
                $selectedExam = $this->Examinations->get($examinationId);
                if (!empty($selectedExam)) {
                    $selectedGrade = $selectedExam->education_grade_id;
                }
                $selectedAcademicPeriod = $request->data[$this->alias()]['academic_period_id'];

                $InstitutionClass = TableRegistry::get('Institution.InstitutionClasses');
                $classes = $InstitutionClass
                    ->find('list')
                    ->matching('ClassGrades')
                    ->where([$InstitutionClass->aliasField('institution_id') => $institutionId,
                        $InstitutionClass->aliasField('academic_period_id') => $selectedAcademicPeriod,
                        'ClassGrades.education_grade_id' => $selectedGrade])
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
                $examinationId = $request->data[$this->alias()]['examination_id'];
                $selectedAcademicPeriod = $request->data[$this->alias()]['academic_period_id'];
                $selectedClassId = $request->data[$this->alias()]['institution_class_id'];
                $selectedSubjectId = $request->data[$this->alias()]['education_subject_id'];
                $enrolledStatus = TableRegistry::get('Student.StudentStatuses')->getIdByCode('CURRENT');

                $SubjectStudents = $this->InstitutionSubjectStudents;
                $students = $SubjectStudents->find()
                    ->matching('Users')
                    ->matching('Students.EducationGrades')
                    ->where([$SubjectStudents->aliasField('institution_id') => $institutionId,
                        $SubjectStudents->aliasField('academic_period_id') => $selectedAcademicPeriod,
                        $SubjectStudents->aliasField('institution_class_id') => $selectedClassId,
                        $SubjectStudents->aliasField('education_subject_id') => $selectedSubjectId,
                        'Students.student_status_id' => $enrolledStatus
                        ])
                    ->toArray();
            }

            $attr['type'] = 'element';
            $attr['element'] = 'Examination.students';
            $attr['data'] = $students;
        }

        return $attr;
    }

}
