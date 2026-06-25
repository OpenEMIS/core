<?php

namespace Institution\Model\Table;
use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use Cake\Validation\Validator;
use Cake\Utility\Inflector;
use App\Model\Traits\MessagesTrait;
use App\Model\Table\ControllerActionTable;
use Cake\Routing\Router;

class ReportCardGenerateTable extends ControllerActionTable
{
    use MessagesTrait;
    public function initialize(array $config): void
    {
        $this->setTable('assessment_item_results');
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

    public function validationDefault(Validator $validator): Validator
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

    public function beforeAction(EventInterface $event, ArrayObject $extra)
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

    public function onUpdateFieldAcademicPeriodId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $classId = $this->getQueryString('class_id');
        $assessmentId = $this->getQueryString('assessment_id');
        $institutionId = $this->getQueryString('institution_id');
        $academicPeriodId = $this->getQueryString('academic_period_id');
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


    public function onUpdateFieldInstitutionClassesId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $classId = $this->getQueryString('class_id');
            $assessmentId = $this->getQueryString('assessment_id');
            $institutionId = $this->getQueryString('institution_id');
            $academicPeriodId = $this->getQueryString('academic_period_id');

            $session = $this->request->getSession();
            $periodId = empty($academicPeriodId) ? $request->getData($this->getAlias())['academic_period_id'] : $academicPeriodId;
            $educationGradeId = $request->getData($this->getAlias())['education_grade_id'];
            //$institutionId = $session->read('Institution.Institutions.id');
            $InstitutionGrades = TableRegistry::getTableLocator()->get('Institution.InstitutionGrades');
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

    public function onUpdateFieldStudents(EventInterface $event, array $attr, $action, ServerRequest $request)
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

    public function onUpdateFieldEducationGradeId(EventInterface $event, array $attr, $action, $request)
    {
        $classId = $this->getQueryString('class_id');
        $assessmentId = $this->getQueryString('assessment_id');
        $institutionId = $this->getQueryString('institution_id');
        $academicPeriodId = $this->getQueryString('academic_period_id');

        if ($action == 'add') {
            if (!$academicPeriodId) {
                $academicPeriodId = $request->getData()['ReportCardGenerate']['academic_period_id'];
            }
            if (!$institutionId) {
                $institutionId = $request->getData()['ReportCardGenerate']['institution_id'];
            }
            if ($classId) {
                $grades = TableRegistry::getTableLocator()->get('Institution.InstitutionClassGrades');
            }
            if(!empty( $academicPeriodId)) {
                $where = [
                    'EducationSystems.academic_period_id' => $academicPeriodId,
                ];
            }

            if (!empty($classId)) {
                //$where[$grades->aliasField('institution_id')] = $institutionId;
            }
            if ($classId) {
                $where[$grades->aliasField('institution_class_id')] = $classId;
            }
            $EducationGrades = TableRegistry::getTableLocator()->get('Education.EducationGrades');
            $periodGrades = $EducationGrades->find('list', ['keyField' => 'id',
                'valueField' => 'programme_grade_name'])
                ->find('visible')
                ->contain(['EducationProgrammes.EducationCycles.EducationLevels.EducationSystems'])
                ->LeftJoin([$grades->getAlias() => $grades->getTable()], [
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

    public function onUpdateFieldStudentStatusId(EventInterface $event, array $attr, $action, $request)
    {

        $statusNames = $this->StudentStatuses->find('list')->toArray();
        $attr['options'] = [0 => __('All Statuses')] + $statusNames;
        $attr['onChangeReload'] = true;

        return $attr;
    }

    public function onUpdateFieldListOfStudents(EventInterface $event, array $attr, $action, $request)
    {
        if ($action == 'add') {

            $class_id = $this->getQueryString('class_id');
            $assessmentId = $this->getQueryString('assessment_id');
            $institutionId = $this->getQueryString('institution_id');
            $academic_period_id = $this->getQueryString('academic_period_id');
            // POCOR-8578: start
            $Users = self::getDynamicTableInstance('security_users');
            $session = $request->getSession();
            $alias = $this->getAlias();
            $data = $request->getData($alias);
            if ($data['students'] == 0) {
                $attr['visible'] = false;
                return $attr;
            }else{
                $attr['visible'] = true;
            }
            if (!$academic_period_id) {
                $academic_period_id = $data['academic_period_id'];
            }
            if (!$class_id) {
                $class_id = $data['institution_classes_id'];
            }
            if (!$institutionId) {
                $institutionId = $session->read('Institution.Institutions.id');
            }
            $educationGradeId = $data['education_grade_id'];
            $statusId = $data['student_status_id'];
            $InstitutionStudents = TableRegistry::getTableLocator()->get('Institution.Students');
            $InstitutionClassStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionClassStudents');
            $where = [];
            if ($statusId > 0) {
                $where[] = $InstitutionClassStudents->aliasField("student_status_id = ") . $statusId;
            }

            if (!empty($educationGradeId)) {
                $where[] = $InstitutionStudents->aliasField("education_grade_id = ") . $educationGradeId;
            }

            $studentOptionsQuery = $InstitutionStudents
                ->find()
                ->select([
                    'user_id' => $Users->aliasField('id'),
                    'openemis_no' => $Users->aliasField('openemis_no'),
                    'first_name' => $Users->aliasField('first_name'),
                    'middle_name' => $Users->aliasField('middle_name'),
                    'third_name' => $Users->aliasField('third_name'),
                    'last_name' => $Users->aliasField('last_name'),
                    'preferred_name' => $Users->aliasField('preferred_name')
                ])
                ->leftJoin([$Users->getAlias() => $Users->getTable()], [
                    $Users->aliasField('id =') . $InstitutionStudents->aliasField('student_id')
                ])
                ->leftJoin([$InstitutionClassStudents->getAlias() => $InstitutionClassStudents->getTable()], [
                    $InstitutionClassStudents->aliasField('student_id =') . $InstitutionStudents->aliasField('student_id'),
                    $InstitutionClassStudents->aliasField('education_grade_id =') . $InstitutionStudents->aliasField('education_grade_id'),
                    $InstitutionClassStudents->aliasField('academic_period_id =') . $InstitutionStudents->aliasField('academic_period_id'),
                    $InstitutionClassStudents->aliasField('institution_id =') . $InstitutionStudents->aliasField('institution_id')
                ])
                ->where([
                    $InstitutionStudents->aliasField('academic_period_id =') . $academic_period_id,
                    $InstitutionClassStudents->aliasField('institution_class_id =') . $class_id,
                    //$where[ $InstitutionStudents->aliasField('education_grade_id')] = $educationGradeId;
                    $InstitutionStudents->aliasField('institution_id =') . $institutionId,
                    $where
                ])
                ->group([$Users->aliasField('id')]);
            $studentOptions = $studentOptionsQuery->toArray();
            $options = [];
            if (!empty($studentOptions)) {
                foreach ($studentOptions as $value) {
                    $studentName = [];
                    ($value['first_name']) ? $studentName[] = $value['first_name'] : '';
                    ($value['middle_name']) ? $studentName[] = $value['middle_name'] : '';
                    ($value['third_name']) ? $studentName[] = $value['third_name'] : '';
                    ($value['last_name']) ? $studentName[] = $value['last_name'] : '';
                    $name = implode(' ', $studentName);
                    $options[$value['user_id']] = trim(sprintf('%s - %s', $value['openemis_no'], $name));
                }
            }

            if ($data['students'] == 0) {
                $attr['visible'] = false;
            }
            // POCOR-8578: end
            $attr['options'] = $options;
        }

        return $attr;
    }

    public function addAfterSave(EventInterface $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra)
    {
        $data = $requestData['ReportCardGenerate'];
        $queryString = $this->request->getQuery('queryString');
        $assessmentId = $this->paramsDecode($queryString)['assessment_id'];
        $url = [
            'plugin' => 'CustomExcel',
            'controller' => 'CustomExcels',
            'action' => 'export',
            0 => 'AssessmentResults'
        ];
        $customUrl = $this->setQueryString($url, [
            'class_id' => $data['institution_classes_id'],
            'assessment_id' => $assessmentId,
            'institution_id' => $data['institution_id'],
            'academic_period_id' => $data['academic_period_id'],
            'student_status_id' => $data['student_status_id'],
            'grade_id' => $data['education_grade_id'],
            'students' => $data['students'],
            'list_of_students' => $data['list_of_students']
        ]);
        $customUrl = self::secureUrl($customUrl);
        return $this->controller->redirect($customUrl);
    }
    private static function secureUrl($url, $fullBase = true) {
        $fullUrl = Router::url($url, $fullBase);
        return preg_replace("/^http:/i", "https:", $fullUrl);
    }

    public function addBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $queryString = $this->request->getQuery('queryString');
        $button = [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'Results',
            'queryString' => $queryString
        ];

        $extra['toolbarButtons']['back']['url'] = $button;
    }

    /**
     * POCOR-8391 added
     * Get a dynamic table instance with all associations.
     *
     * @param string $tableName
     * @return \Cake\ORM\Table
     */
    private static function getDynamicTableInstance(string $tableName): Table
    {
        // Parse plugin and table names if dot notation is used
        $locator = TableRegistry::getTableLocator();
        try {
            return $locator->get($tableName);
        } catch (\Exception $exception) {

        }
        $parts = explode('.', $tableName);
        $plugin = count($parts) > 1 ? $parts[0] : null;
        $table = count($parts) > 1 ? $parts[1] : $parts[0];

        // Convert the table name to camel case as expected by CakePHP conventions
        $tableFullAlias = Inflector::camelize($tableName);
        $tableAlias = Inflector::camelize($table);

        // Create the fully qualified class name if a plugin is specified
        if ($plugin) {
            $className = $plugin . '\\Model\\Table\\' . $tableAlias . 'Table';
        } else {
            $className = 'App\\Model\\Table\\' . $tableAlias . 'Table';
        }
        // Check if the table instance already exists
        if (!$locator->exists($tableFullAlias)) {
            // Check if the specific table class exists
            if (!class_exists($className)) {
                $className = Table::class; // Fallback to generic Table class
            }

            // Configure a new table instance
            $locator->setConfig($tableAlias, [
                'className' => $className,
                'table' => $table,
                'alias' => $tableAlias,
            ]);
        }

        // Return the table instance
        return $locator->get($tableFullAlias);
    }
}
