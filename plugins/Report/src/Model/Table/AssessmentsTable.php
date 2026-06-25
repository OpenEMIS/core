<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use Cake\I18n\Time;
use Cake\ORM\TableRegistry;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use Cake\Database\Expression\QueryExpression;

/**
 * Data processing for generating 
 * POCOR-9075
 * Excel reports of student results for assessment items.
 *
 * This includes handling marks, weights, allowed subjects, and 
 * internal tracking for calculations during report generation.
 */

class AssessmentsTable extends AppTable
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
        $this->belongsTo('NextInstitutionClasses', ['className' => 'Institution.InstitutionClasses', 'foreignKey' =>'next_institution_class_id']);
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
        $institutionId = $requestData->institution_id ?? null;
        $areaId = $requestData->area_education_id ?? null;
        $academicPeriodId = $requestData->academic_period_id ?? null;
        $institutionType = $requestData->institution_type ?? null; //POCOR-9451
        $gradeId = $requestData->education_grade_id ?? null;
        $superAdmin = $requestData->super_admin ?? false;
        $userId = $requestData->user_id ?? null;

        $StudentStatuses = $this->StudentStatuses;
        $conditions = [];
        $conditions[$this->aliasField('academic_period_id')] = $academicPeriodId;

        if (!empty($gradeId) && $gradeId > 0) {
            $conditions[$this->aliasField('education_grade_id')] = $gradeId;
        }

        if (!empty($institutionId) && $institutionId > 1) {
            $conditions[$this->aliasField('institution_id')] = $institutionId;
        }
        
        $educationSubjectId = $requestData->education_subject_id ?? null;
        if(!empty($educationSubjectId)){
            $educationSubjectIds = [];
            if (is_array($educationSubjectId)) {
                    foreach ($educationSubjectId as $item) {
                        if (is_object($item) && isset($item->_ids) && is_array($item->_ids)) {
                            $educationSubjectIds = array_merge($educationSubjectIds, $item->_ids);
                        } elseif (!is_array($item)) {
                            $educationSubjectIds[] = $item;
                        }
                    }
            } elseif (is_object($educationSubjectId) && isset($educationSubjectId->_ids)) {
                $educationSubjectIds = $educationSubjectId->_ids;
            } elseif (!empty($educationSubjectId)) {
                $educationSubjectIds = [$educationSubjectId];
            }
        }
        $students = $this->find()->select(['student_id', 'institution_class_id'])
            ->where($conditions)
            ->distinct(['student_id', 'institution_class_id'])
            ->enableHydration(false)
            ->toList();

        $studentIds = array_column($students, 'student_id');
        $classIds = array_unique(array_column($students, 'institution_class_id'));

        $institutionStudentsTable = TableRegistry::getTableLocator()->get('InstitutionClassStudents');
        $AssessmentsTable = TableRegistry::getTableLocator()->get('Assessment.Assessments');
        $AssessmentPeriodsTable = TableRegistry::getTableLocator()->get('Assessment.AssessmentPeriods');
        $AssessmentItemsGradingTypesTable = TableRegistry::getTableLocator()->get('Assessment.AssessmentItemsGradingTypes');
        $AssessmentItemsTable = TableRegistry::getTableLocator()->get('Assessment.AssessmentItems');

        $assessmentsQuery = $AssessmentsTable->find()->where(['academic_period_id' => $academicPeriodId]);

        if ($gradeId != 0) {
            $assessmentsQuery->where(['education_grade_id' => $gradeId]);
        } else {
            $gradeIds = $institutionStudentsTable->find()
                ->distinct()
                ->select(['education_grade_id'])
                ->where(['academic_period_id' => $academicPeriodId])
                ->enableHydration(false)
                ->extract('education_grade_id')
                ->toArray();

            if (!empty($gradeIds)) {
                $assessmentsQuery->where(['education_grade_id IN' => $gradeIds]);
            } else {
                return;
            }
        }

        $assessments = $assessmentsQuery->orderDesc('id')->all();

        if ($assessments->isEmpty()) {
            return;
        }

        $assessmentIds = $assessments->extract('id')->toArray();
        $assessmentSubjectsMap = [];
        if(empty($educationSubjectId)){
            $subjectIds = []; // collect all unique subject IDs 

            $AssessmentItemsTable = TableRegistry::getTableLocator()->get('Assessment.AssessmentItems');

            foreach ($assessmentIds as $assessmentId) {
                $subjects = $AssessmentItemsTable->find()
                    ->distinct(['education_subject_id'])
                    ->where(['assessment_id' => $assessmentId])
                    ->enableHydration(false)
                    ->all()
                    ->extract('education_subject_id')
                    ->toArray();

                $assessmentSubjectsMap[$assessmentId] = $subjects;
                $subjectIds = array_merge($subjectIds, $subjects);
            }

            $subjectIds = array_unique($subjectIds); // final list to pass
        }else{
            $subjectIds = $educationSubjectIds;
        }

        $AssessmentItemResultsTable = TableRegistry::getTableLocator()->get('Assessment.AssessmentItemResults');

        $allResults = $AssessmentItemResultsTable->getAssessmentItemResultsReport(
            $academicPeriodId,
            $assessmentIds, 
            $subjectIds,
            $studentIds,
            $classIds
        );

        $this->assessmentItemResults = $allResults;

        $query
            ->contain([
                'InstitutionClasses.Institutions.Types',//POCOR-9451,
                'AcademicPeriods',
                'EducationGrades',
                'Users.BirthplaceAreas',
            ])
            ->innerJoin(['InstitutionClassGrades' => 'institution_class_grades'], [
                'InstitutionClassGrades.institution_class_id = ' . $this->aliasField('institution_class_id')
            ])
            ->leftJoin(['StudentStatuses' => 'student_statuses'], [
                'StudentStatuses.id = ' . $this->aliasField('student_status_id')
            ])
            ->leftJoin(['UserNationalities' => 'user_nationalities'], [
                'UserNationalities.security_user_id = ' . $this->aliasField('student_id'),
            ])
            ->leftJoin(['Nationalities' => 'nationalities'], [
                'Nationalities.id = UserNationalities.nationality_id',
            ])
            ->select([
                'code' => 'Institutions.code',
                'institution_name' => 'Institutions.name',
                'institution_type_name' => 'Types.name', //POCOR-9451
                'institution_id' => 'Institutions.id',
                'openemis_number' => 'Users.openemis_no',
                'birth_place_area' => 'BirthplaceAreas.name',
                'date_of_birth' => 'Users.date_of_birth',
                'class_name' => 'InstitutionClasses.name',
                'academic_period_name' => 'AcademicPeriods.name',
                'academic_period_id' => 'AcademicPeriods.id',
                'nationality_name' => 'Nationalities.name',
                'student_id' => $this->aliasField('student_id'),
                'education_grade_id' => $this->aliasField('education_grade_id'),
                'institution_class_id' => 'InstitutionClasses.id',
                'education_grade_name' => 'EducationGrades.name',
                'student_name' => $query->func()->concat([
                    'Users.first_name' => 'literal',
                    " ",
                    'Users.last_name' => 'literal'
                ]),
            ])
            ->where($conditions)
            ->formatResults(function (\Cake\Collection\CollectionInterface $results) {
                return $results->map(function ($row) {
                    if ($row['date_of_birth'] instanceof \Cake\I18n\FrozenDate) {
                        $row['date_of_birth'] = $row['date_of_birth']->format('Y-m-d');
                    }
                    return $row;
                });
            });
    }

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, ArrayObject $originalField)
    {
        $requestData = json_decode($settings['process']['params']);
        $institutionId = $requestData->institution_id ?? null;
        $academicPeriodId = $requestData->academic_period_id;
        $assessmentPeriodId = $requestData->assessment_period_id;
        $academicTerm = $requestData->academic_term;
        //POCOR-9484 start
        $educationSubjectId = $requestData->education_subject_id ?? null;
        if(!empty($educationSubjectId)){ 
            $educationSubjectIds = [];
            if (is_array($educationSubjectId)) {
                    foreach ($educationSubjectId as $item) {
                        if (is_object($item) && isset($item->_ids) && is_array($item->_ids)) {
                            $educationSubjectIds = array_merge($educationSubjectIds, $item->_ids);
                        } elseif (!is_array($item)) {
                            $educationSubjectIds[] = $item;
                        }
                    }
            } elseif (is_object($educationSubjectId) && isset($educationSubjectId->_ids)) {
                $educationSubjectIds = $educationSubjectId->_ids;
            } elseif (!empty($educationSubjectId)) {
                $educationSubjectIds = [$educationSubjectId];
            }
        } //POCOR-9484 end

        $fields = new ArrayObject();
        $fields[] = [
            'key' => 'AcademicPeriods.name',
            'field' => 'academic_period_name',
            'type' => 'integer',
            'label' => __('Academic Period'),
        ];
        $fields[] = [
            'key' => 'Users.openemis_no',
            'field' => 'openemis_number',
            'type' => 'string',
            'label' => '',
        ];
        $fields[] = [
            'key' => 'Institutions.code',
            'field' => 'code',
            'type' => 'string',
            'label' => __('Institutions Code'),
        ];

        $fields[] = [
            'key' => 'Institutions.name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institutions Name'),
        ];
        //POCOR-9451
        $fields[] = [
            'key' => 'Types.name',
            'field' => 'institution_type_name',
            'type' => 'string',
            'label' => __('Institution Type'),
        ];

        $fields[] = [
            'key' => '',
            'field' => 'student_name',
            'type' => 'string',
            'label' => __('Student Name'),
        ];

        $fields[] = [
            'key' => 'InstitutionClasses.class_name',
            'field' => 'class_name',
            'type' => 'string',
            'label' => __('Class'),
        ];

        $fields[] = [
            'key' => 'education_grade_name',
            'field' => 'education_grade_name',
            'type' => 'string',
            'label' => __('Education Grade'),
        ];

        $fields[] = [
            'key' => 'nationality_name',
            'field' => 'nationality_name',
            'type' => 'string',
            'label' => __('Nationality'),
        ];

        $fields[] = [
            'key' => 'Users.birthplace_area_id',
            'field' => 'birth_place_area',
            'type' => 'string',
            'label' => '',
        ];

        $fields[] = [
            'key' => 'Users.date_of_birth',
            'field' => 'date_of_birth',
            'type' => 'string',
            'label' => 'Date of Birth',
        ];

        // Initialize table instances
        $institutionStudentsTable = TableRegistry::getTableLocator()->get('InstitutionClassStudents');
        $AssessmentsTable = TableRegistry::getTableLocator()->get('Assessment.Assessments');
        $AssessmentPeriodsTable = TableRegistry::getTableLocator()->get('Assessment.AssessmentPeriods');
        $AssessmentItemsGradingTypesTable = TableRegistry::getTableLocator()->get('Assessment.AssessmentItemsGradingTypes');
        $AssessmentItemsTable = TableRegistry::getTableLocator()->get('Assessment.AssessmentItems');

        // Build assessments query
        $assessmentsQuery = $AssessmentsTable->find()->where(['academic_period_id' => $academicPeriodId]);

        // Case 1: Specific grade
        if ($requestData->education_grade_id != 0) {
            $assessmentsQuery->where(['education_grade_id' => $requestData->education_grade_id]);
        } else {
            // Case 2: All grades based on student enrollment
            $gradeIds = $institutionStudentsTable->find()
                ->distinct()
                ->select(['education_grade_id'])
                ->where(['academic_period_id' => $academicPeriodId])
                ->enableHydration(false)
                ->all()
                ->extract('education_grade_id')
                ->toArray();

            if (!empty($gradeIds)) {
                $assessmentsQuery->where(['education_grade_id IN' => $gradeIds]);
            } else {
                return; // No grades found for the period
            }
        }

        // Fetch all assessments
        $assessments = $assessmentsQuery->orderDesc('id')->all();

        if ($assessments->isEmpty()) {
            return;
        }

        // 1. Fetch all assessment periods for all assessments
        $assessmentIds = $assessments->extract('id')->toArray();
        $assessmentPeriodsMap = [];
        //POCOR-9484
        $checkCondition = [];

        if (!empty($assessmentPeriodId)) {
            //POCOR-9575 start
            if (is_object($assessmentPeriodId) && isset($assessmentPeriodId->_ids)) {
                $ids = $assessmentPeriodId->_ids;

            } elseif (is_array($assessmentPeriodId) && isset($assessmentPeriodId['_ids'])) {
                $ids = $assessmentPeriodId['_ids'];

            } else {
                $ids = $assessmentPeriodId;
            }

            if (is_array($ids)) {
                $checkCondition[$AssessmentPeriodsTable->aliasField('id') . ' IN'] = $ids;
            } else {
                $checkCondition[$AssessmentPeriodsTable->aliasField('id')] = $ids;
            } //POCOR-9575 end
        }
        if($academicTerm > 0){
            $checkCondition[$AssessmentPeriodsTable->aliasField('academic_term')] = $academicTerm;
        }
        $checkCondition[$AssessmentPeriodsTable->aliasField('assessment_id IN')] = $assessmentIds;
        if (!empty($assessmentIds)) {
            $assessmentPeriodsRecords = $AssessmentPeriodsTable->find()
                ->where($checkCondition)
                ->order([$AssessmentPeriodsTable->aliasField('assessment_id')])
                ->all();

            foreach ($assessmentPeriodsRecords as $period) {
                $assessmentPeriodsMap[$period->assessment_id][] = $period; // Organize by assessment_id
            }
        }

        // 2. Fetch  subjects for assessments
        $assessmentSubjectsMap = [];
        $educationSubjectIds = $educationSubjectIds ?? [];
        //POCOR-9484
        if (!empty($educationSubjectIds)) {
            foreach ($assessmentIds as $assessmentId) {
                $assessmentSubjectsMap[$assessmentId] =
                    $AssessmentItemsTable
                        ->find()
                        ->innerJoinWith('EducationSubjects')
                        ->where([
                            'AssessmentItems.assessment_id' => $assessmentId,
                            'AssessmentItems.education_subject_id IN' => $educationSubjectIds
                        ])
                        ->select([
                            'assessment_item_id' => 'AssessmentItems.id',
                            'education_subject_name' => 'EducationSubjects.name',
                            'subject_id' => 'AssessmentItems.education_subject_id',
                            'subject_weight' => 'AssessmentItems.weight',
                        ])
                        ->enableHydration(false)
                        ->toArray();
            }
        } else {
            foreach ($assessmentIds as $assessmentId) {
                $assessmentSubjectsMap[$assessmentId] =
                    $AssessmentItemsTable->getSubjects($assessmentId);
            }
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
                        'institutionId' => $institutionId,
                        'assessmentId' => $assessmentId,
                        'subjectId' => $subjectId,
                        'assessmentPeriodWeight' => $period->weight,
                        'academicPeriodId' => $academicPeriodId,
                        'assessmentPeriodId' => $assessmentPeriodId,
                        'resultType' => $resultType
                    ];
                }
                // Add weighted mark header for each subject
                $fields[] = [
                    'key' => 'assessment_period_weighted_mark',
                    'field' => 'assessment_item',
                    'type' => 'assessment_period_weighted_mark',
                    'label' => __('Weighted Marks') . ' (' . $subject['subject_weight'] . ') ',
                    'subjectWeight' => $subject['subject_weight']
                ];
            }
        }

        // Total mark headers
        $fields[] = [
            'key' => 'total_mark',
            'field' => 'assessment_item',
            'type' => 'total_mark',
            'label' => __('Total Marks')
        ];

        $fields[] = [
            'key' => 'total_weighted_mark',
            'field' => 'assessment_item',
            'type' => 'total_weighted_mark',
            'label' => __('Total Weighted Marks')
        ];

        // Assign fields to the original ArrayObject
        $originalField->exchangeArray($fields);
    }

    public function onExcelRenderSubject(EventInterface $event, Entity $entity, array $attr)
    {
        $subjectId = $attr['subjectId'];
        $assessmentPeriodId = $attr['assessmentPeriodId'];
        $resultType = $attr['resultType'];
        $assessmentId = $attr['assessmentId'];
        $studentId = $entity->student_id;

        // Pull from preloaded assessment item results
        $result = $this->assessmentItemResults[$studentId][$subjectId][$assessmentPeriodId] ?? null;

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


    public function onExcelRenderTotalWeightedMark(EventInterface $event, Entity $entity, array $attr)
    {
        $totalWeightedMark = $this->totalWeightedMark;
        $this->totalWeightedMark = 0;
        if(is_numeric($totalWeightedMark)){
            $totalWeightedMark = number_format($totalWeightedMark, 2);
        }
        return ' '.$totalWeightedMark;
    }

    public function onExcelRenderTotalMark(EventInterface $event, Entity $entity, array $attr)
    {
        $totalMark = $this->totalMark;
        $this->totalMark = 0;
        if(is_numeric($totalMark)){
            $totalMark = number_format($totalMark, 2);
        }
        return ' '.$totalMark;
    }

    /*public function onExcelRenderNationality(EventInterface $event, Entity $entity, array $attr)
    {
        if ($entity->user->nationalities) {
            $nationalities = $entity->user->nationalities;
            $allNationalities = '';
            foreach ($nationalities as $nationality) {
                $allNationalities .= $nationality->nationalities_look_up->name . ', ';
            }
            return rtrim($allNationalities, ', ');
        } else {
            return '';
        }
    }*/

    public function onExcelRenderSubjectbkp(EventInterface $event, Entity $entity, array $attr)
    {
        $subjectId = $attr['subjectId'];
        $assessmentPeriodId = $attr['assessmentPeriodId'];
        $resultType = $attr['resultType'];
        $assessmentId = $attr['assessmentId'];
        $studentId = $entity->student_id;
        $classId = $entity->institution_class_id;
       // $institutionId = $attr['institutionId'] ?? null;
        $institutionId = $entity->institution_id ?? null;
        $academicPeriodId = $entity->academic_period_id ?? null;
        $educationGradeId = $entity->education_grade_id ?? null;
        $SubjectStudents = $this->SubjectStudents;
        $AssessmentsTable = TableRegistry::getTableLocator()->get('Assessment.Assessments');
        $AssessmentItemResultsTable = TableRegistry::getTableLocator()->get('Assessment.AssessmentItemResults');
        if (!empty($assessmentId)) {
            $assessmentSubjects = TableRegistry::getTableLocator()->get('Assessment.AssessmentItems')->getSubjects($assessmentId);
        } else {
            $assessmentSubjects = [];
        }
         $options = [
            'institution_id' => $institutionId,
            'academic_period_id' => $academicPeriodId,
            'institution_class_id' => $classId,
            'assessment_id' => $assessmentId,
            'education_grade_id' => $educationGradeId,
        ];
      
        $results = $SubjectStudents->find('StudentResults', $options)
            ->toArray();
        $student_results = [];
        $student_results = [];
        $this->i = 1;
        foreach ($results as $result){
            $arresult = $result->toArray();
            $arresult['i'] = $this->i;
            $this->i = $this->i + 1;
            $student_id = $result['student_id'];
            $education_subject_id = $result['education_subject_id'];
            $assessment_period_id = $result['assessment_period_id'];
            if(!isset($student_results[$student_id])){
                $student_results[$student_id] = [];
            }
            if(!isset($student_results[$student_id][$education_subject_id])){
                $student_results[$student_id][$education_subject_id] = [];
            }
            if(!isset($student_results[$student_id][$education_subject_id][$assessment_period_id])){
                $student_results[$student_id][$education_subject_id][$assessment_period_id] = $arresult;
            } else {
                //Log::debug('arresult');
                //Log::debug($arresult);
            }
        }

        $AssessmentPeriods = TableRegistry::getTableLocator()->get('Assessment.AssessmentPeriods');
        $getAssessment = $AssessmentPeriods ->find()
                        ->where([$AssessmentPeriods->aliasField('assessment_id') => $assessmentId, $AssessmentPeriods->aliasField('id') => $assessmentPeriodId])->first();
      
        if(!empty($getAssessment)){
            $this->academicTerm = $getAssessment->academic_term;
        }
        $this->results = $student_results;
        $this->assessmentItemResults = $student_results;
        $this->i = 1;
        if (!array_key_exists($studentId, $this->assessmentItemResults)) {
                $this->assessmentItemResults[$studentId] = [];
        }
        if (!array_key_exists($subjectId, $this->assessmentItemResults[$studentId])) {
                $studentResults = $AssessmentItemResultsTable->getAssessmentItemResults($academicPeriodId, $assessmentId, $subjectId, $studentId, $classId);
            if (isset($studentResults[$studentId][$subjectId])) {
                $this->assessmentItemResults[$studentId][$subjectId] = $studentResults[$studentId][$subjectId];
            }
        }
        $renderResult = true;
        if ($renderResult) {
            if (isset($this->assessmentItemResults[$studentId][$subjectId][$assessmentPeriodId])) {
                $result = $this->assessmentItemResults[$studentId][$subjectId][$assessmentPeriodId];
                switch ($resultType) {
                    case 'MARKS':
                        // Add logic to add weighted mark to subjectWeightedMark
                        if ($result['mark'] != 'EXEMPT' && $result['mark'] != 'UNASSIGN') {

                            $this->assessmentPeriodWeightedMark += ((float)$result['marks'] * (float)$attr['assessmentPeriodWeight']);
                            $this->assessmentPeriodWeights[] = $attr['assessmentPeriodWeight'];
                        }
                        $printedResult = $result['mark'];
                        break;
                    case 'GRADES':
                        $printedResult = $result['grade_code'] . ' - ' . $result['grade_name'];
                        break;
                    case 'DURATION':
                        $printedResult = '';
                        if (!is_null($result['marks'])) {
                            $duration = number_format($result['marks'], 2, ':', '');
                            $printedResult = ' '.$duration;
                        }
                        break;
                }
            }
        }

        return $printedResult;
    }

     public function onExcelBeforeQuerybkp(EventInterface $event, ArrayObject $settings, $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $institutionId = $requestData->institution_id ?? null;
        $areaId = $requestData->area_education_id ?? null;
        $academicPeriodId = $requestData->academic_period_id ?? null;
        $gradeId = $requestData->education_grade_id ?? null;
        $superAdmin = $requestData->super_admin ?? false;
        $userId = $requestData->user_id ?? null;

        $StudentStatuses = $this->StudentStatuses;
        $conditions = [];
        $conditions[$this->aliasField('academic_period_id')] = $academicPeriodId;
        // Area filter
        /*if (!empty($areaId) && $areaId > 0) {
            $conditions[$this->aliasField('area_id')] = $areaId;
        }*/

        // Grade filter
        if (!empty($gradeId) && $gradeId > 0) {
            $conditions[$this->aliasField('education_grade_id')] = $gradeId;
        }

        // Institution filter or fallback to accessible institutions
        if (!empty($institutionId) && $institutionId > 1) {
            $conditions[$this->aliasField('institution_id')] = $institutionId;
        }

        // Exclude withdrawn/transferred students
       // $conditions[$StudentStatuses->aliasField('code NOT IN ')] = ['TRANSFERRED', 'WITHDRAWN'];

        $query
            ->contain([
                'InstitutionClasses.Institutions',
                'AcademicPeriods',
                'EducationGrades',
                'Users.BirthplaceAreas',
            ])
            ->innerJoin(['InstitutionClassGrades' => 'institution_class_grades'], [
                'InstitutionClassGrades.institution_class_id = ' . $this->aliasField('institution_class_id')
            ])
            ->leftJoin(['StudentStatuses' => 'student_statuses'], [
                'StudentStatuses.id = ' . $this->aliasField('student_status_id')
            ])
             ->leftJoin(['UserNationalities' => 'user_nationalities'], [
                'UserNationalities.security_user_id = ' . $this->aliasField('student_id'),
            ])
            ->leftJoin(['Nationalities' => 'nationalities'], [
                'Nationalities.id = UserNationalities.nationality_id',
            ])
            ->select([
                'code' => 'Institutions.code',
                'institution_name' => 'Institutions.name',
                'institution_id' => 'Institutions.id',
                'openemis_number' => 'Users.openemis_no',
                'birth_place_area' => 'BirthplaceAreas.name',
                'date_of_birth' => 'Users.date_of_birth',
                'class_name' => 'InstitutionClasses.name',
                'academic_period_name' => 'AcademicPeriods.name',
                'academic_period_id' => 'AcademicPeriods.id',
                'nationality_name' => 'Nationalities.name',
                'student_id' => $this->aliasField('student_id'),
                'education_grade_id' => $this->aliasField('education_grade_id'),
                'institution_class_id' => 'InstitutionClasses.id',
                'education_grade_name' => 'EducationGrades.name',
                'student_name' => $query->func()->concat([
                    'Users.first_name' => 'literal',
                    " ",
                    'Users.last_name' => 'literal'
                ]),
            ])
            ->where($conditions);

            $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
                return $results->map(function ($row) {
                    if ($row['date_of_birth'] instanceof \Cake\I18n\FrozenDate) {
                        $row['date_of_birth'] = $row['date_of_birth']->format('Y-m-d');
                    }
                    return $row;
                });
            });
    }

}
