<?php

namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\Utility\Inflector;
use App\Model\Traits\MessagesTrait;
use App\Model\Table\ControllerActionTable;

class ReportCardGenerateTable extends ControllerActionTable
{
    use MessagesTrait;

    public function initialize(array $config)
    {
        $this->table('assessment_item_results');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses', 'foreignKey' => 'institution_classes_id']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'student_id']);
        $this->addBehavior('CustomExcel.ExcelReport', [
            'templateTable' => 'Assessment.Assessments',
            'templateTableKey' => 'assessment_id',
            'variables' => [
                'Assessments',
                // 'AssessmentItems',
                // 'AssessmentItemsGradingTypes',
                // 'AssessmentPeriods',
                // 'AssessmentItemResults',
                'GroupAssessmentPeriods',
                'GroupAssessmentPeriodsWithTerms',
                'GroupAssessmentItems',
                'GroupAssessmentItemsGradingTypes',
                'GroupAssessmentItemResults',
                'ClassStudents',
                'Institutions',
                'InstitutionClasses',
                'InstitutionStudentAbsences'
            ]
        ]);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        $validator
            ->notEmpty('academic_period_id')
            ->notEmpty('education_grade_id')
            ->notEmpty('institution_classes_id')
            ->notEmpty('student_status_id')
            ->notEmpty('students');

        return $validator;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('marks', ['visible' => false]);
        $this->field('assessment_grading_option_id', ['visible' => false]);
        $this->field('academic_period_id');
        $this->field('education_grade_id', ['type' => 'select']);
        $this->field('institution_classes_id');
        $this->field('student_status_id', ['type' => 'select']);
        $this->field('students');
        $this->field('list_of_students', [
            'type' => 'chosenSelect',
            'placeholder' => __('-- Select Students --'),
            'visible' => true
        ]);
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        $classId = $this->ControllerAction->getQueryString('class_id');
        $assessmentId = $this->ControllerAction->getQueryString('assessment_id');
        $institutionId = $this->ControllerAction->getQueryString('institution_id');
        $academicPeriodId = $this->ControllerAction->getQueryString('academic_period_id');
//        $this->log('onUpdateFieldAcademicPeriodId', 'debug');
//        $this->log($academicPeriodId, 'debug');
        if ($action == 'add') {
            $periodOptions = $this->AcademicPeriods->getYearList();
            $attr['options'] = $periodOptions;
            if (!$academicPeriodId) {
                $attr['type'] = 'select';
                $attr['onChangeReload'] = true;
            } else {
                $attr['type'] = 'readonly';
                $attr['value'] = $academicPeriodId;
                $attr['attr']['value'] = $this->AcademicPeriods->get($academicPeriodId)->name;
                $attr['default'] = $academicPeriodId;
            }
        }


        return $attr;
    }

    public function getSelectedAcademicPeriod($querystringPeriod)
    {
        $periodOptions = $this->AcademicPeriods->getYearList();

        if ($querystringPeriod) {
            $selectedPeriod = $querystringPeriod;
        } else {
            $selectedPeriod = $this->AcademicPeriods->getCurrent();
        }

        return compact('periodOptions', 'selectedPeriod');
    }


    public function onUpdateFieldInstitutionClassesId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $classId = $this->ControllerAction->getQueryString('class_id');
            $assessmentId = $this->ControllerAction->getQueryString('assessment_id');
            $institutionId = $this->ControllerAction->getQueryString('institution_id');
            $academicPeriodId = $this->ControllerAction->getQueryString('academic_period_id');

            $session = $this->request->session();
            $periodId = $request->data[$this->alias()]['academic_period_id'];
            $educationGradeId = $request->data[$this->alias()]['education_grade_id'];
            $institutionId = $session->read('Institution.Institutions.id');
            $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
            $userId = $this->Auth->user('id');
            $classQuery = $this->InstitutionClasses
                ->find('list', ['keyField' => 'id',
                    'valueField' => 'name'
                ])
                ->find('byGrades', [
                    'education_grade_id' => $educationGradeId,
                ])
                ->find('byAccess', ['userId' => $userId, 'accessControl' => $this->AccessControl, 'controller' => $this->controller])
                ->where([
                    $this->InstitutionClasses->aliasField('academic_period_id') => $periodId,
                    $this->InstitutionClasses->aliasField('institution_id') => $institutionId
                ]);
            $options = $classQuery->toArray();
            $attr['options'] = $options;
            if (!$classId) {
                $attr['type'] = 'select';
                $attr['onChangeReload'] = true;
            }
            if ($classId) {
                $attr['type'] = 'readonly';
                $attr['value'] = $classId;
                $attr['attr']['value'] = $this->InstitutionClasses->get($classId)->name;
            }

        }
        return $attr;
    }

    public function onUpdateFieldStudents(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $studentsOptions = [
                0 => __('All Students'),
                1 => __('Select Students')
            ];

            $attr['type'] = 'select';
            $attr['selected'] =  __('All Students');
            $attr['options'] = $studentsOptions;
            $attr['onChangeReload'] = true;
        }

        return $attr;
    }

    public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, $request)
    {
        $classId = $this->ControllerAction->getQueryString('class_id');
        $assessmentId = $this->ControllerAction->getQueryString('assessment_id');
        $institutionId = $this->ControllerAction->getQueryString('institution_id');
        $academicPeriodId = $this->ControllerAction->getQueryString('academic_period_id');

        if ($action == 'add') {
            if (!$academicPeriodId) {
                $academicPeriodId = $request->data['ReportCardGenerate']['academic_period_id'];
            }
            if (!$institutionId) {
                $institutionId = $request->data['ReportCardGenerate']['institution_id'];
            }
            if ($classId) {
                $grades = TableRegistry::get('Institution.InstitutionClassGrades');
            }
            $where = [
                'EducationSystems.academic_period_id' => $academicPeriodId,
            ];
            if (!$classId) {
                $where[$grades->aliasField('institution_id')] = $institutionId;
            }
            if ($classId) {
                $where[$grades->aliasField('institution_class_id')] = $classId;
            }
            $EducationGrades = TableRegistry::get('Education.EducationGrades');
            $periodGrades = $EducationGrades->find('list', ['keyField' => 'id',
                'valueField' => 'programme_grade_name'])
                ->find('visible')
                ->contain(['EducationProgrammes.EducationCycles.EducationLevels.EducationSystems'])
                ->LeftJoin([$grades->alias() => $grades->table()], [
                    $EducationGrades->aliasField('id') . ' = ' . $grades->aliasField('education_grade_id')
                ])
                ->where($where)
                ->order([$EducationGrades->aliasField('id')])
                ->toArray();

            $attr['options'] = $periodGrades;
            $attr['onChangeReload'] = true;
            if (sizeof($periodGrades) == 1) {
                foreach ($periodGrades as $periodGradeId => $periodGradesName) {
                    $attr['value'] = $periodGradeId;
                    $attr['attr']['value'] = $periodGradesName;

                }
                $attr['type'] = 'readonly';
            }

        }
        return $attr;
    }

    public function onUpdateFieldStudentStatusId(Event $event, array $attr, $action, $request)
    {

        $statusNames = $this->StudentStatuses->find('list')->toArray();
        $attr['options'] = [0 => __('All Statuses')] + $statusNames;
        $attr['onChangeReload'] = true;

        return $attr;
    }

    public function onUpdateFieldListOfStudents(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            $class_id = $this->ControllerAction->getQueryString('class_id');
            $assessmentId = $this->ControllerAction->getQueryString('assessment_id');
            $institutionId = $this->ControllerAction->getQueryString('institution_id');
            $academic_period_id = $this->ControllerAction->getQueryString('academic_period_id');

            $session = $this->request->session();
            if (!$academic_period_id) {
                $academic_period_id = $request->data[$this->alias()]['academic_period_id'];
            }
            if (!$class_id) {
                $class_id = $request->data[$this->alias()]['institution_classes_id'];
            }
            if (!$institutionId) {
                $institutionId = $session->read('Institution.Institutions.id');
            }
            $educationGradeId = $request->data[$this->alias()]['education_grade_id'];
            $statusId = $request->data[$this->alias()]['student_status_id'];
            $InstitutionStudents = TableRegistry::get('Institution.Students');
            $InstitutionClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
            $where = [];
            if ($statusId > 0) {
                $where[$InstitutionClassStudents->aliasField('student_status_id')] = $statusId;
            }
            $studentOptions = $InstitutionStudents
                ->find()
                ->select([
                    $this->Users->aliasField('id'),
                    $this->Users->aliasField('openemis_no'),
                    $this->Users->aliasField('first_name'),
                    $this->Users->aliasField('middle_name'),
                    $this->Users->aliasField('third_name'),
                    $this->Users->aliasField('last_name'),
                    $this->Users->aliasField('preferred_name')
                ])
                ->leftJoin([$this->Users->alias() => $this->Users->table()], [
                    $this->Users->aliasField('id =') . $InstitutionStudents->aliasField('student_id')
                ])
                ->leftJoin([$InstitutionClassStudents->alias() => $InstitutionClassStudents->table()], [
                    $InstitutionClassStudents->aliasField('student_id =') . $InstitutionStudents->aliasField('student_id'),
                    $InstitutionClassStudents->aliasField('education_grade_id =') . $InstitutionStudents->aliasField('education_grade_id'),
                    $InstitutionClassStudents->aliasField('academic_period_id =') . $InstitutionStudents->aliasField('academic_period_id'),
                    $InstitutionClassStudents->aliasField('institution_id =') . $InstitutionStudents->aliasField('institution_id')
                ])
                ->where([
                    $InstitutionStudents->aliasField('academic_period_id') => $academic_period_id,
                    $InstitutionClassStudents->aliasField('institution_class_id') => $class_id,
                    $InstitutionStudents->aliasField('education_grade_id') => $educationGradeId,
                    $InstitutionStudents->aliasField('institution_id') => $institutionId,
                    $where
                ])
                ->group([$this->Users->aliasField('id')])
                ->toArray();
            $options = [];
            if (!empty($studentOptions)) {
                foreach ($studentOptions as $value) {
                    $studentName = [];
                    ($value->Users['first_name']) ? $studentName[] = $value->Users['first_name'] : '';
                    ($value->Users['middle_name']) ? $studentName[] = $value->Users['middle_name'] : '';
                    ($value->Users['third_name']) ? $studentName[] = $value->Users['third_name'] : '';
                    ($value->Users['last_name']) ? $studentName[] = $value->Users['last_name'] : '';
                    $name = implode(' ', $studentName);
                    $options[$value->Users['id']] = trim(sprintf('%s - %s', $value->Users['openemis_no'], $name));
                }
            }

            if ($request->data[$this->alias()]['students'] == 0) {
                $attr['visible'] = false;
            }
            $attr['options'] = $options;
        }

        return $attr;
    }

    public function addAfterSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra)
    {
        $data = $requestData['ReportCardGenerate'];
        $queryString = $this->request->query('queryString');
        $assessmentId = $this->paramsDecode($queryString)['assessment_id'];
        $url = [
            'plugin' => 'CustomExcel',
            'controller' => 'CustomExcels',
            'action' => 'export',
            0 => 'AssessmentResults'
        ];
        $customUrl = $this->ControllerAction->setQueryString($url, [
            'class_id' => $data['institution_classes_id'],
            'assessment_id' => $assessmentId,
            'institution_id' => $data['institution_id'],
            'academic_period_id' => $data['academic_period_id'],
            'student_status_id' => $data['student_status_id'],
            'grade_id' => $data['education_grade_id'],
            'students' => $data['students'],
            'list_of_students' => $data['list_of_students']
        ]);
        return $this->controller->redirect($customUrl);
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $queryString = $this->request->query('queryString');
        $button = [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'Results',
            'queryString' => $queryString
        ];

        $extra['toolbarButtons']['back']['url'] = $button;
    }
}
