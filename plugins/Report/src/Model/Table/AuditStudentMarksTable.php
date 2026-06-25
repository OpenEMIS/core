<?php

namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Http\ServerRequest;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use Cake\I18n\Time;
use Cake\ORM\TableRegistry;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use Cake\Database\Expression\QueryExpression;
use Cake\I18n\FrozenTime;
use Cake\Event\EventInterface;

//POCOR-9444
class AuditStudentMarksTable extends AppTable
{
    private $assessmentItemResults = [];
    private $lastQueriedClass = null;
    private $allowedSubjects = [];
    private $assessmentPeriodWeightedMark = 0;
    private $totalMark = 0;
    private $totalWeightedMark = 0;
    private $results;
    private $i = 1;
    private $assessmentPeriodWeights = [];
    private $academicTerm = 0;

    public function initialize(array $config): void
    {
        $this->setTable('institution_class_students');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id', 'joinType' => 'INNER']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses', 'joinType' => 'INNER']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades', 'joinType' => 'INNER']);
        $this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses', 'joinType' => 'INNER']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'joinType' => 'INNER']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'joinType' => 'INNER']);
        $this->hasMany('InstitutionClassGrades', ['className' => 'Institution.InstitutionClassGrades']);

        $this->hasMany('SubjectStudents', [
            'className' => 'Institution.InstitutionSubjectStudents',
            'foreignKey' => ['institution_class_id', 'student_id'],
            'bindingKey' => ['institution_class_id', 'student_id']
        ]);
        $this->addBehavior('Excel', [
            'pages' => false,
            'autoFields' => false
        ]);
        $this->addBehavior('Report.ReportList');
    }


    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, $query)
    {

        $requestData = json_decode($settings['process']['params']);
        $startDate = $requestData->report_start_date ?? null;
        $endDate   = $requestData->report_end_date ?? null;
        $dateFilterType = 'created';

        $AssessmentItemResults = TableRegistry::getTableLocator()->get('Assessment.AssessmentItemResults');
        $assessmentGradingOptions = TableRegistry::getTableLocator()->get('Assessment.AssessmentGradingOptions ');
        $assessments = TableRegistry::getTableLocator()->get('Assessment.Assessments');
        $assessmentPeriods = TableRegistry::getTableLocator()->get('Assessment.AssessmentPeriods');

        $resultsWhere = [];
        if (!empty($startDate) && !empty($endDate)) {
            $startDateObj = new FrozenTime($startDate);
            $endDateObj   = new FrozenTime($endDate);

            $resultsWhere[] = [
                $AssessmentItemResults->aliasField($dateFilterType) . ' >=' =>
                    $startDateObj->format('Y-m-d H:i:s'),

                $AssessmentItemResults->aliasField($dateFilterType) . ' <=' =>
                    $endDateObj->format('Y-m-d H:i:s')
            ];
        }
        $students = $AssessmentItemResults->find()
            ->select([
                'student_id',
                'institution_classes_id',
                'education_grade_id',
                'assessment_id',
                'education_subject_id',
                'assessment_period_id',
                'institution_id',
                'academic_period_id'
            ])
            ->where($resultsWhere)
            ->order([
                'academic_period_id' => 'ASC',
                'student_id' => 'ASC',
                'assessment_period_id' => 'ASC',
                'education_subject_id' => 'ASC'
            ])
            ->enableHydration(false)
            ->toList();

        if (empty($students)) {
            return;
        }

        $assessmentIds     = array_unique(array_column($students, 'assessment_id'));
        $classIds          = array_unique(array_column($students, 'institution_classes_id'));

        $uniqueStudents = [];
        foreach ($students as $row) {
            $key = $row['student_id']
                . '_' . $row['academic_period_id']
                . '_' . $row['institution_classes_id'];

            $uniqueStudents[$key] = $row;
        }

        $studentIds = array_values(
            array_unique(array_column($uniqueStudents, 'student_id'))
        );
        $academicPeriodIds = array_unique(array_column($uniqueStudents, 'academic_period_id'));

        $AssessmentItems = TableRegistry::getTableLocator()
            ->get('Assessment.AssessmentItems');

        $subjectIds = $AssessmentItems->find()
            ->distinct(['education_subject_id'])
            ->where(['assessment_id IN' => $assessmentIds])
            ->enableHydration(false)
            ->extract('education_subject_id')
            ->toArray();

       
        $allResults = $AssessmentItemResults->auditAssessmentItemResultsReport(
            $academicPeriodIds,
            $assessmentIds,
            $subjectIds,
            $studentIds,
            $classIds
        );
        $this->assessmentItemResults = $allResults;

        
        $conditions = [];

        if (!empty($studentIds)) {
            $conditions[$this->aliasField('student_id IN')] = $studentIds;
        }

        if (!empty($classIds)) {
            $conditions[$this->aliasField('institution_class_id IN')] = $classIds;
        }
        if (!empty($academicPeriodIds)) {
            $conditions[$this->aliasField('academic_period_id IN')] = $academicPeriodIds;
        }

        $conditions['AssessmentItemResults.id IS NOT'] = null;

        $query
            ->select([
                'code' => 'Institutions.code',
                'institution_name' => 'Institutions.name',
                'institution_id' => 'Institutions.id',
                'openemis_number' => 'Users.openemis_no',
                'class_name' => 'InstitutionClasses.name',
                'academic_period_name' => 'AcademicPeriods.name',
                'academic_period_id' => 'AcademicPeriods.id',
                'student_id' => $this->aliasField('student_id'),
                'id' => $this->aliasField('id'),
                'education_grade_id' => $this->aliasField('education_grade_id'),
                'institution_class_id' => 'InstitutionClasses.id',
                'education_grade_name' => 'EducationGrades.name',
                'grading_option_name' => 'AssessmentGradingOptions.name',
                'assessment_name' => 'Assessments.name',
                'assessment_period_name' => 'AssessmentPeriods.name',
                'student_name' => $query->func()->concat([
                    'Users.first_name' => 'literal',
                    ' ',
                    'Users.last_name' => 'literal'
                ]),
                'modified_user'      => $query->func()->concat([
                    'ModifiedUser.first_name' => 'literal',
                    ' ',
                    'ModifiedUser.last_name' => 'literal'
                ]),
                'modified'           => 'AssessmentItemResults.modified',
                'created_user'       => $query->func()->concat([
                    'CreatedUser.first_name' => 'literal',
                    ' ',
                    'CreatedUser.last_name' => 'literal'
                ]),
                'created'            => 'AssessmentItemResults.created',
            ])
            ->contain([
                'InstitutionClasses.Institutions',
                'AcademicPeriods',
                'EducationGrades',
                'Users',
            ])
            ->innerJoin(['InstitutionClassGrades' => 'institution_class_grades'], [
                'InstitutionClassGrades.institution_class_id = ' . $this->aliasField('institution_class_id')
            ])
            ->leftJoin(['StudentStatuses' => 'student_statuses'], [
                'StudentStatuses.id = ' . $this->aliasField('student_status_id')
            ])
            ->innerJoin(['AssessmentItemResults' => 'assessment_item_results'], [
                'AssessmentItemResults.student_id = ' . $this->aliasField('student_id'),
                'AssessmentItemResults.academic_period_id = ' . $this->aliasField('academic_period_id'),
                'AssessmentItemResults.institution_classes_id = ' . $this->aliasField('institution_class_id')
            ])

            ->leftJoin(['Assessments' => 'assessments'], [
                'Assessments.id = AssessmentItemResults.assessment_id'
            ])
            ->leftJoin(['AssessmentPeriods' => 'assessment_periods'], [
                'AssessmentPeriods.id = AssessmentItemResults.assessment_period_id'
            ])
            ->leftJoin(['AssessmentGradingOptions' => 'assessment_grading_options'], [
                'AssessmentGradingOptions.id = AssessmentItemResults.assessment_grading_option_id'
            ])
            ->leftJoin(['CreatedUser' => 'security_users'], [
                'CreatedUser.id = AssessmentItemResults.created_user_id'
            ])
            ->leftJoin(['ModifiedUser' => 'security_users'], [
                'ModifiedUser.id = AssessmentItemResults.modified_user_id'
            ])
            ->where($conditions)
            ->group([
                $this->aliasField('student_id'),
                $this->aliasField('academic_period_id'),
                $this->aliasField('institution_class_id')
            ]);

    }

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, ArrayObject $originalField)
    {
        $requestData = json_decode($settings['process']['params']);
        $startDate = $requestData->report_start_date ?? null;
        $endDate   = $requestData->report_end_date ?? null;
        $dateFilterType = 'created';

        $fields = new ArrayObject();
        
        $fields[] = [
            'key' => 'academic_period_name',
            'field' => 'academic_period_name',
            'type' => 'string',
            'label' => __('Academic Period'),
        ];

        $fields[] = [
            'key' => 'code',
            'field' => 'code',
            'type' => 'string',
            'label' => __('Institution Code'),
        ];

        $fields[] = [
            'key' => 'institution_name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institution Name'),
        ];

        $fields[] = [
            'key' => 'assessment_name',
            'field' => 'assessment_name',
            'type' => 'string',
            'label' => __('Assessment Name'),
        ];

        $fields[] = [
            'key' => 'assessment_period_name',
            'field' => 'assessment_period_name',
            'type' => 'string',
            'label' => __('Assessment Period'),
        ];

        $fields[] = [
            'key' => 'education_grade_name',
            'field' => 'education_grade_name',
            'type' => 'string',
            'label' => __('Education Grade'),
        ];

        $fields[] = [
            'key' => 'openemis_number',
            'field' => 'openemis_number',
            'type' => 'string',
            'label' => __('OpenEMIS ID'),
        ];
        $fields[] = [
            'key' => 'student_name',
            'field' => 'student_name',
            'type' => 'string',
            'label' => __('Student'),
        ];

        $fields[] = [
            'key' => 'class_name',
            'field' => 'class_name',
            'type' => 'string',
            'label' => __('Institution Classes'),
        ];

        $fields[] = [
            'key' => 'grading_option_name',
            'field' => 'grading_option_name',
            'type' => 'string',
            'label' => __('Assessment Grading Option'),
        ];

        // Initialize table instances
        $institutionStudentsTable = TableRegistry::getTableLocator()->get('InstitutionClassStudents');
        $AssessmentsTable = TableRegistry::getTableLocator()->get('Assessment.Assessments');
        $AssessmentPeriodsTable = TableRegistry::getTableLocator()->get('Assessment.AssessmentPeriods');
        $AssessmentItemsGradingTypesTable = TableRegistry::getTableLocator()->get('Assessment.AssessmentItemsGradingTypes');
        $AssessmentItemsTable = TableRegistry::getTableLocator()->get('Assessment.AssessmentItems');
        $AssessmentItemResults = TableRegistry::getTableLocator()->get('Assessment.AssessmentItemResults');
        $resultsWhere = [];
        if (!empty($startDate) && !empty($endDate)) {

            $startDateObj = new FrozenTime($startDate);
            $endDateObj   = new FrozenTime($endDate);

            $resultsWhere[] = [
                $AssessmentItemResults->aliasField($dateFilterType) . ' >=' =>
                    $startDateObj->format('Y-m-d H:i:s'),

                $AssessmentItemResults->aliasField($dateFilterType) . ' <=' =>
                    $endDateObj->format('Y-m-d H:i:s')
            ];
        }

        $students = $AssessmentItemResults->find()
            ->select([
                'student_id',
                'institution_classes_id',
                'education_grade_id',
                'assessment_id',
                'education_subject_id',
                'assessment_period_id',
                'institution_id',
                'academic_period_id'
            ])
            ->where($resultsWhere)
            ->order([
                'academic_period_id' => 'ASC',
                'student_id' => 'ASC',
                'assessment_period_id' => 'ASC',
                'education_subject_id' => 'ASC'
            ])
            ->enableHydration(false)
            ->toList();
        $assessmentIds     = array_unique(array_column($students, 'assessment_id'));
        $classIds          = array_unique(array_column($students, 'institution_classes_id'));
        $educationGradeIds = array_unique(array_column($students, 'education_grade_id'));

        $AssessmentItems = TableRegistry::getTableLocator()
            ->get('Assessment.AssessmentItems');

        $allResults = [];

        if (!empty($educationGradeIds)) {
               $assessmentsQuery =  $AssessmentsTable->find()->where(['education_grade_id IN' => $educationGradeIds]);
        } else {
            return; // No grades found for the period
        }

        // Fetch all assessments
        $assessments = $assessmentsQuery->orderDesc('id')->all();
        if ($assessments->isEmpty()) {
            return;
        }

        $assessmentPeriodsMap = [];
        if (!empty($assessmentIds)) {
            $assessmentPeriodsRecords = $AssessmentPeriodsTable->find()
                ->where(['assessment_id IN' => $assessmentIds])
                ->order([$AssessmentPeriodsTable->aliasField('assessment_id')])
                ->all();

            foreach ($assessmentPeriodsRecords as $period) {
                $assessmentPeriodsMap[$period->assessment_id][] = $period; // Organize by assessment_id
            }
        }

        // 2. Fetch all subjects for all assessments
        $assessmentSubjectsMap = [];
        foreach ($assessmentIds as $assessmentId) {
            $assessmentSubjectsMap[$assessmentId] = $AssessmentItemsTable->getSubjects($assessmentId);
        }

        // 3. Fetch all grading types for all assessments
        $assessmentGradeTypesMap = [];
        foreach ($assessmentIds as $assessmentId) {
            $assessmentGradeTypesMap[$assessmentId] = $AssessmentItemsGradingTypesTable->getAssessmentGradeTypes($assessmentId);
        }

        // 4. Loop through assessments
        foreach ($assessments as $assessment) {
            $assessmentId = $assessment->id;
            $assessmentPeriods = $assessmentPeriodsMap[$assessmentId] ?? [];
            $assessmentSubjects = $assessmentSubjectsMap[$assessmentId] ?? [];
            $assessmentGradeTypes = $assessmentGradeTypesMap[$assessmentId] ?? [];
            // Loop through subjects and periods
            foreach ($assessmentSubjects as $subject) {
                $subjectId = $subject['subject_id'];
                for ($i = 0; $i < count($assessmentPeriods); $i++) {
                    $period = $assessmentPeriods[$i];
                    $assessmentPeriodId = $period->id;
                    $academicTerm = $period->academic_term;
                    $resultType = $assessmentGradeTypes[$subjectId][$assessmentPeriodId] ?? 'MARKS';

                    // Construct label for header
                    $label = __($subject['education_subject_name']) . ' - ' . $period->name . ' - ' . $period->academic_term;
                    if ($resultType == 'MARKS') {
                        $label .= ' (' . $period->weight . ') ';
                    }

                    // Append header info for each subject-period combination
                    $fields[] = [
                        'key' => $subject['assessment_item_id'],
                        'field' => 'assessment_item',
                        'type' => 'subject',
                        'label' => $label,
                     //   'institutionId' => $institutionId,
                        'assessmentId' => $assessmentId,
                        'subjectId' => $subjectId,
                        'assessmentPeriodWeight' => $period->weight,
                       // 'academicPeriodId' => $academicPeriodId,
                        'assessmentPeriodId' => $assessmentPeriodId,
                        'resultType' => $resultType
                    ];
                }
            }
        }

        $fields[] = [
            'key' => 'modified_user',
            'field' => 'modified_user',
            'type' => 'string',
            'label' => __('Modified User')
        ];
        $fields[] = [
            'key' => 'modified',
            'field' => 'modified',
            'type' => 'string',
            'label' => __('Modified')
        ];
        $fields[] = [
            'key' => 'created_user',
            'field' => 'created_user',
            'type' => 'string',
            'label' => __('Created User')
        ];
        $fields[] = [
            'key' => 'created',
            'field' => 'created',
            'type' => 'string',
            'label' => __('Created')
        ];

        $originalField->exchangeArray($fields);
    }


    

    public function onExcelRenderSubject(EventInterface $event, Entity $entity, array $attr)
    {
        $subjectId = $attr['subjectId'];
        $assessmentPeriodId = $attr['assessmentPeriodId'];
        $academicPeriodId = $entity->academic_period_id;
        $resultType = $attr['resultType'];
        $assessmentId = $attr['assessmentId'];
        $studentId = $entity->student_id;
        // Pull from preloaded assessment item results
        $result = $this->assessmentItemResults[$studentId][$academicPeriodId][$subjectId][$assessmentPeriodId] ?? null;
        if (empty($result)) {
            return '';
        }
        switch ($resultType) {
            case 'MARKS':
                $mark = $result['marks'] ?? null;

                if (!in_array($mark, ['EXEMPT', 'UNASSIGN']) && is_numeric($mark)) {
                    $this->assessmentPeriodWeightedMark += ((float)$mark * (float)$attr['assessmentPeriodWeight']);
                    $this->assessmentPeriodWeights[] = $attr['assessmentPeriodWeight'];
                }

                return is_numeric($mark) ? number_format($mark, 2) : $mark;

            case 'GRADES':
                return $result['grade_code'] . ' - ' . $result['grade_name'];

            case 'DURATION':
                return !is_null($result['marks']) ? ' ' . number_format($result['marks'], 2, ':', '') : '';

            default:
                return '';
        }
    }

    //POCOR-9305
    public function onExcelRenderAssessmentPeriodWeightedMark(EventInterface $event, Entity $entity, array $attr)
    {
        $weightsum = array_sum($this->assessmentPeriodWeights);
        $assessmentPeriodWeightedMark = $this->assessmentPeriodWeightedMark;

        //remove the average/weight re-calculation
        $this->assessmentPeriodWeights = [];

        if (is_numeric($assessmentPeriodWeightedMark)) {
            $this->totalMark += $assessmentPeriodWeightedMark;
            $this->totalWeightedMark += ($assessmentPeriodWeightedMark * $attr['subjectWeight']);
        }

        // reset
        $this->assessmentPeriodWeightedMark = 0;

        if (is_numeric($assessmentPeriodWeightedMark)) {
            $assessmentPeriodWeightedMark = number_format($assessmentPeriodWeightedMark, 2);
        }

        return ' ' . $assessmentPeriodWeightedMark;
    }

    public function onExcelGetModified(EventInterface $event, Entity $entity) {
        if (!empty($entity->modified)) {
            return $this->formatDate($entity->modified);
        }
    }
    public function onExcelGetCreated(EventInterface $event, Entity $entity) {
        if (!empty($entity->created)) {
            return $this->formatDate($entity->created);
        }
    }
    
}
