<?php
namespace CustomExcel\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Datasource\ResultSetInterface;
use Cake\Utility\Inflector;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;

class AssessmentResultsTable extends AppTable
{
    use OptionsTrait;
    private $groupAssessmentPeriodCount = 0;
    const STUDENT_ENROLLED_STATUS = 1;

    public function initialize(array $config)
    {
        $this->table('institution_class_students');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('NextInstitutionClasses', ['className' => 'Institution.InstitutionClasses', 'foreignKey' =>'next_institution_class_id']);
        $this->hasMany('InstitutionClassGrades', ['className' => 'Institution.InstitutionClassGrades']);

        $this->hasMany('SubjectStudents', [
            'className' => 'Institution.InstitutionSubjectStudents',
            'foreignKey' => [
                'institution_class_id',
                'student_id'
            ],
            'bindingKey' => [
                'institution_class_id',
                'student_id'
            ]
        ]);

        $this->addBehavior('CustomExcel.ExcelReport', [
            'templateTable' => 'Assessment.Assessments',
            'templateTableKey' => 'assessment_id',
            'variables' => [
                'Assessments',
                'EducationGrades',
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

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseEducationGrades'] = 'onExcelTemplateInitialiseEducationGrades';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseGroupAssessmentItems'] = 'onExcelTemplateInitialiseGroupAssessmentItems';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseGroupAssessmentItemsGradingTypes'] = 'onExcelTemplateInitialiseGroupAssessmentItemsGradingTypes';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseGroupAssessmentPeriods'] = 'onExcelTemplateInitialiseGroupAssessmentPeriods';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseGroupAssessmentPeriodsWithTerms'] = 'onExcelTemplateInitialiseGroupAssessmentPeriodsWithTerms';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseGroupAssessmentItemResults'] = 'onExcelTemplateInitialiseGroupAssessmentItemResults';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseClassStudents'] = 'onExcelTemplateInitialiseClassStudents';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutions'] = 'onExcelTemplateInitialiseInstitutions';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionClasses'] = 'onExcelTemplateInitialiseInstitutionClasses';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionStudentAbsences'] = 'onExcelTemplateInitialiseInstitutionStudentAbsences';
        return $events;
    }

    public function onExcelTemplateInitialiseAssessments(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('assessment_id', $params)) {
            $Assessments = TableRegistry::get('Assessment.Assessments');
            $entity = $Assessments->get($params['assessment_id'], [
                'contain' => ['AcademicPeriods', 'EducationGrades']
            ]);

            return $entity->toArray();
        }
    }

    public function onExcelTemplateInitialiseAssessmentItems(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('assessment_id', $params)) {
            $AssessmentItems = TableRegistry::get('Assessment.AssessmentItems');
            $results = $AssessmentItems->find()
                ->contain(['EducationSubjects'])
                ->where([$AssessmentItems->aliasField('assessment_id') => $params['assessment_id']])
                ->order(['EducationSubjects.order', 'EducationSubjects.code', 'EducationSubjects.name'])
                ->hydrate(false)
                ->all();

            return $results->toArray();
        }
    }

    public function onExcelTemplateInitialiseAssessmentItemsGradingTypes(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('assessment_id', $params)) {
            $AssessmentItemsGradingTypes = TableRegistry::get('Assessment.AssessmentItemsGradingTypes');
            $results = $AssessmentItemsGradingTypes->find()
                ->contain(['AssessmentGradingTypes', 'AssessmentPeriods', 'EducationSubjects'])
                ->where([$AssessmentItemsGradingTypes->aliasField('assessment_id') => $params['assessment_id']])
                ->formatResults(function (ResultSetInterface $results) {
                    return $results->map(function ($row) {
                        $resultType = $row['assessment_grading_type']['result_type'];
                        $max = $row['assessment_grading_type']['max'];

                        switch ($resultType) {
                            case 'MARKS':
                            case 'GRADES':
                                $row['assessment_grading_type']['max_formatted'] = number_format($max, 2);
                                break;
                            case 'DURATION':
                                if (strlen($max) > 0) {
                                    $duration = number_format($max/60, 2);

                                    list($minutes, $seconds) = explode(".", $duration, 2);
                                    $row['assessment_grading_type']['max_formatted'] = $minutes . " : " . $seconds;
                                    break;
                                }
                            default:
                                $row['assessment_grading_type']['max_formatted'] = number_format($max, 2);
                                break;
                        }

                        return $row;
                    });
                })
                ->hydrate(false)
                ->all();

            return $results->toArray();
        }
    }

    public function onExcelTemplateInitialiseAssessmentPeriods(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('assessment_id', $params)) {
            $AssessmentPeriods = TableRegistry::get('Assessment.AssessmentPeriods');
            $results = $AssessmentPeriods->find()
                ->where([$AssessmentPeriods->aliasField('assessment_id') => $params['assessment_id']])
                ->hydrate(false)
                ->all();

            return $results->toArray();
        }
    }

    public function onExcelTemplateInitialiseAssessmentItemResults(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('class_id', $params) && array_key_exists('assessment_id', $params) && array_key_exists('institution_id', $params)) {
            $AssessmentItemResults = TableRegistry::get('Assessment.AssessmentItemResults');
            $results = $AssessmentItemResults->find()
                ->innerJoin(
                    [$this->alias() => $this->table()],
                    [
                        $this->aliasField('institution_id = ') . $AssessmentItemResults->aliasField('institution_id'),
                        $this->aliasField('academic_period_id = ') . $AssessmentItemResults->aliasField('academic_period_id'),
                        $this->aliasField('student_id = ') . $AssessmentItemResults->aliasField('student_id'),
                        $this->aliasField('institution_class_id') => $params['class_id']
                    ]
                )
                ->contain(['AssessmentGradingOptions.AssessmentGradingTypes'])
                ->where([
                    $AssessmentItemResults->aliasField('assessment_id') => $params['assessment_id']
                ])
                ->formatResults(function (ResultSetInterface $results) {
                    return $results->map(function ($row) {
                        $resultType = $row['assessment_grading_option']['assessment_grading_type']['result_type'];

                        switch ($resultType) {
                            case 'MARKS':
                                $row['marks_formatted'] = number_format($row['marks'], 2);
                                break;
                            case 'GRADES':
                                $row['marks_formatted'] = $row['assessment_grading_option']['code'] . ' - ' . $row['assessment_grading_option']['name'];
                                break;
                            case 'DURATION':
                                if (strlen($row['marks']) > 0) {
                                    $duration = number_format($row['marks'], 2);

                                    list($minutes, $seconds) = explode(".", $duration, 2);
                                    $row['marks_formatted'] = $minutes . " : " . $seconds;
                                    break;
                                }
                            default:
                                $row['marks_formatted'] = '';
                                break;
                        }

                        return $row;
                    });
                })
                ->hydrate(false)
                ->all();

            return $results->toArray();
        }
    }

    public function onExcelTemplateInitialiseGroupAssessmentItems(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('assessment_id', $params) && array_key_exists('class_id', $params)) {
            $AssessmentItems = TableRegistry::get('Assessment.AssessmentItems');
            $EducationSubjects = TableRegistry::get('Education.EducationSubjects');
            $ClassSubjects = TableRegistry::get('Institution.InstitutionClassSubjects');
            $InstitutionSubjects = TableRegistry::get('Institution.InstitutionSubjects');

            $query = $AssessmentItems->find();
            $selectedColumns = [
                'subject_classification' => '(
                    CASE
                    WHEN '.$AssessmentItems->aliasField('classification <> \'\'').' THEN '.$AssessmentItems->aliasField('classification').'
                        ELSE '.$EducationSubjects->aliasField('name').'
                        END
                    )',
                'subject_order' => $query->func()->min($EducationSubjects->aliasField('order')),
                'total_subject_weight' => $query->func()->sum($AssessmentItems->aliasField('weight'))
            ];

            $results = $AssessmentItems->find()
                ->select($selectedColumns)
                ->contain([$EducationSubjects->alias()])
                ->innerJoin([$InstitutionSubjects->alias() => $InstitutionSubjects->table()], [
                             $InstitutionSubjects->aliasField('education_subject_id = ') . $AssessmentItems->aliasField('education_subject_id')
                            ])
                ->innerJoin([$ClassSubjects->alias() => $ClassSubjects->table()], [
                            $InstitutionSubjects->aliasField('id = ') . $ClassSubjects->aliasField('institution_subject_id'),
                            $ClassSubjects->aliasField('institution_class_id') => $params['class_id']
                        ])
                ->where([$AssessmentItems->aliasField('assessment_id') => $params['assessment_id']])
                ->order(['subject_order', 'subject_classification', $EducationSubjects->aliasField('code'), $EducationSubjects->aliasField('name')])
                ->group(['subject_classification'])
                ->hydrate(false)
                ->all();

            return $results->toArray();
        }
    }

    public function onExcelTemplateInitialiseGroupAssessmentItemsGradingTypes(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('assessment_id', $params)) {
            $AssessmentItemsGradingTypes = TableRegistry::get('Assessment.AssessmentItemsGradingTypes');
            $AssessmentGradingTypes = TableRegistry::get('Assessment.AssessmentGradingTypes');
            $AssessmentPeriods = TableRegistry::get('Assessment.AssessmentPeriods');
            $EducationSubjects = TableRegistry::get('Education.EducationSubjects');
            $AssessmentItems = TableRegistry::get('Assessment.AssessmentItems');

            $query = $AssessmentItemsGradingTypes->find();

            $withoutTerm = $query
                ->select([
                    'subject_classification' => '(
                    CASE
                    WHEN '.$AssessmentItems->aliasField('classification <> \'\'').' THEN '.$AssessmentItems->aliasField('classification').'
                        ELSE '.$EducationSubjects->aliasField('name').'
                        END
                    )',
                    'academic_term_value' => $AssessmentPeriods->aliasField('name'),
                    'academic_term_total_weighted_max' => $query->func()->sum($AssessmentGradingTypes->aliasField('max * ') . $AssessmentPeriods->aliasField('weight'))
                ])
                ->contain([$AssessmentGradingTypes->alias(), $AssessmentPeriods->alias(), $EducationSubjects->alias()])
                ->leftJoin(
                    [$AssessmentItems->alias() => $AssessmentItems->table()],
                    [
                        $AssessmentItems->aliasField('assessment_id = ') . $AssessmentItemsGradingTypes->aliasField('assessment_id'),
                        $AssessmentItems->aliasField('education_subject_id = ') . $AssessmentItemsGradingTypes->aliasField('education_subject_id')
                    ]
                )
                ->where([$AssessmentItemsGradingTypes->aliasField('assessment_id') => $params['assessment_id']])
                ->group(['subject_classification', 'academic_term_value'])
                ->hydrate(false)
                ->all();

            $withTerm = $query
                ->select([
                    'subject_classification' => '(
                    CASE
                    WHEN '.$AssessmentItems->aliasField('classification <> \'\'').' THEN '.$AssessmentItems->aliasField('classification').'
                        ELSE '.$EducationSubjects->aliasField('name').'
                        END
                    )',
                    'academic_term_value' => $AssessmentPeriods->aliasField('academic_term'),
                    'academic_term_total_weighted_max' => $query->func()->sum($AssessmentGradingTypes->aliasField('max * ') . $AssessmentPeriods->aliasField('weight'))
                ])
                ->contain([$AssessmentGradingTypes->alias(), $AssessmentPeriods->alias(), $EducationSubjects->alias()])
                ->leftJoin(
                    [$AssessmentItems->alias() => $AssessmentItems->table()],
                    [
                        $AssessmentItems->aliasField('assessment_id = ') . $AssessmentItemsGradingTypes->aliasField('assessment_id'),
                        $AssessmentItems->aliasField('education_subject_id = ') . $AssessmentItemsGradingTypes->aliasField('education_subject_id')
                    ]
                )
                ->where([
                    $AssessmentItemsGradingTypes->aliasField('assessment_id') => $params['assessment_id'],
                    $AssessmentPeriods->aliasField('academic_term <> ') => ""
                ])
                ->group(['subject_classification'])
                ->hydrate(false)
                ->all();

            if (!$withTerm->isEmpty()) { // If academic_term is setup, to use the academic_term to calculate the average
                $recordsToUse = $withTerm->toArray();
            } else { // else, to calculate the average by subject_classification
                $recordsToUse = $withoutTerm->toArray(); 
            }

            $sumRecordBySubjects = [];
            foreach ($recordsToUse as $record) {
                $subjectClassification = $record['subject_classification'];

                if (!array_key_exists($subjectClassification, $sumRecordBySubjects)) {
                    $sumRecordBySubjects[$subjectClassification] = [
                        'subject_classification' => $record['subject_classification'],
                        'total_weight' => $record['academic_term_total_weighted_max'],
                        'count' => 1
                    ];
                } else {
                    $sumRecordBySubjects[$subjectClassification]['total_weight'] += $record['academic_term_total_weighted_max'];
                    ++$sumRecordBySubjects[$subjectClassification]['count'];
                }
            }

            $averageRecords = [];
            foreach ($sumRecordBySubjects as $subjectClassification => $subjectObj) {

                $averageRecords[] = [
                    'subject_classification' => $subjectClassification,
                    'academic_term_value' => 'Average',
                    'academic_term_total_weighted_max' => ($this->groupAssessmentPeriodCount > 0) ? $subjectObj['total_weight'] / $this->groupAssessmentPeriodCount : ''
                ];
            }

            $groupAssessmentItemsGradingTypes = array_merge($withoutTerm->toArray(), $withTerm->toArray(), $averageRecords);
            
            return $groupAssessmentItemsGradingTypes;
        }
    }

    public function onExcelTemplateInitialiseGroupAssessmentPeriods(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('assessment_id', $params)) {
            $AssessmentPeriods = TableRegistry::get('Assessment.AssessmentPeriods');
            $query = $AssessmentPeriods->find();
            $selectedColumns = [
                'academic_term_value' => '(
                    CASE
                    WHEN '.$AssessmentPeriods->aliasField('academic_term <> \'\'').' THEN '.$AssessmentPeriods->aliasField('academic_term').'
                        ELSE '.$AssessmentPeriods->aliasField('name').'
                        END
                    )',
                'total_period_weight' => $query->func()->sum($AssessmentPeriods->aliasField('weight'))
            ];

            $results = $AssessmentPeriods->find()
                ->select($selectedColumns)
                ->where([$AssessmentPeriods->aliasField('assessment_id') => $params['assessment_id']])
                ->group(['academic_term_value'])
                ->hydrate(false)
                ->all();

            $academicTermResults = $results->toArray();
            // this value is use to decide whether to show the average or not, only show average when all academic term got mark
            if (!$results->isEmpty()) {
                $countList = $results->toArray();
                foreach ($countList as $record) {
                    if ($record['total_period_weight'] > 0) {
                        ++$this->groupAssessmentPeriodCount;
                    }
                }
            }

            $totalPeriodWeight = 0;
            foreach ($academicTermResults as $key => $obj) {
                $totalPeriodWeight += $obj['total_period_weight'];
            }

            $academicTermResults[] = [
                'academic_term_value' => __('Average'),
                'total_period_weight' => $totalPeriodWeight
            ];

            return $academicTermResults;
        }
    }

    public function onExcelTemplateInitialiseGroupAssessmentPeriodsWithTerms(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('assessment_id', $params)) {
            $AssessmentPeriods = TableRegistry::get('Assessment.AssessmentPeriods');
            $query = $AssessmentPeriods->find();

            $withoutTerm = $query
                ->select([
                    'academic_term_value' => $AssessmentPeriods->aliasField('name'),
                    'academic_term' => $AssessmentPeriods->aliasField('academic_term'),
                    'total_period_weight' => $AssessmentPeriods->aliasField('weight')
                ])
                ->where([$AssessmentPeriods->aliasField('assessment_id') => $params['assessment_id']])
                ->hydrate(false)
                ->all();

            $withTerm = $query
                ->select([
                    'academic_term_value' => $AssessmentPeriods->aliasField('academic_term'),
                    'total_period_weight' => $query->func()->sum($AssessmentPeriods->aliasField('weight'))
                ])
                ->where([
                    $AssessmentPeriods->aliasField('assessment_id') => $params['assessment_id'],
                    $AssessmentPeriods->aliasField('academic_term <> ') => ""
                ])
                ->group(['academic_term_value'])
                ->hydrate(false)
                ->all();

            $periodsWithTermOrders = [];
            $academic_term_total_weighted = 0;

            if (!$withTerm->isEmpty()) {
                foreach ($withTerm as $key => $objWithTerm) {
                    foreach ($withoutTerm as $objWithoutTerm) {
                        if (isset($objWithoutTerm['academic_term']) && isset($objWithTerm['academic_term_value'])) {
                            if ($objWithoutTerm['academic_term'] == $objWithTerm['academic_term_value']) {
                                $periodsWithTermOrders[] = $objWithoutTerm;
                                $academic_term_total_weighted += $objWithoutTerm['total_period_weight'];
                            }
                        }
                    }
                    $periodsWithTermOrders[] = $objWithTerm;
                }
            } else {
                $periodsWithTermOrders = $withoutTerm->toArray();
            }

            // Add Average Column
            $academicTermResults = [
                'academic_term_value' => __('Average'),
                'total_period_weight' => $academic_term_total_weighted
            ];
            $periodsWithTermOrders[] = $academicTermResults;
            return $periodsWithTermOrders;
        }
    }

    public function onExcelTemplateInitialiseGroupAssessmentItemResults(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('class_id', $params) && array_key_exists('assessment_id', $params) && array_key_exists('institution_id', $params)) {
            $AssessmentItemResults = TableRegistry::get('Assessment.AssessmentItemResults');
            $AssessmentGradingOptions = TableRegistry::get('Assessment.AssessmentGradingOptions');
            $AssessmentPeriods = TableRegistry::get('Assessment.AssessmentPeriods');
            $EducationSubjects = TableRegistry::get('Education.EducationSubjects');
            $AssessmentItems = TableRegistry::get('Assessment.AssessmentItems');

            $query = $AssessmentItemResults->find()
            ->where([
                $AssessmentItemResults->aliasField('academic_period_id') => $params['academic_period_id'],
                $AssessmentItemResults->aliasField('institution_id') => $params['institution_id'],
                $AssessmentItemResults->aliasField('institution_classes_id') => $params['class_id'],
                $AssessmentItemResults->aliasField('education_grade_id') => $params['grade_id']
            ])
            ->group([$AssessmentItemResults->aliasField('assessment_period_id')]);
            //With Term
            $withTerm = $AssessmentItemResults->find()
                ->select([
                    $AssessmentItemResults->aliasField('institution_id'),
                    $AssessmentItemResults->aliasField('academic_period_id'),
                    $AssessmentItemResults->aliasField('assessment_id'),
                    $AssessmentItemResults->aliasField('student_id'),
                    $AssessmentItemResults->aliasField('education_subject_id'),//POCOR-6479
                    $AssessmentItemResults->aliasField('education_grade_id'),//POCOR-6479
                    $AssessmentItemResults->aliasField('assessment_period_id'),//POCOR-6479
                    $AssessmentItemResults->aliasField('assessment_id'),
                    $AssessmentItemResults->aliasField('student_id'),
                    $AssessmentItemResults->aliasField('marks'),
                    $AssessmentPeriods->aliasField('weight'),
                    'subject_classification' => '(
                        CASE
                        WHEN '.$AssessmentItems->aliasField('classification <> \'\'').' THEN '.$AssessmentItems->aliasField('classification').'
                            ELSE '.$EducationSubjects->aliasField('name').'
                            END
                    )',
                    'academic_term_value' => $AssessmentPeriods->aliasField('academic_term'),
                    'academic_term_total_weighted_marks' => $query->func()->sum($AssessmentItemResults->aliasField('marks * ') . $AssessmentPeriods->aliasField('weight')),
                ])
                ->innerJoin(
                    [$this->alias() => $this->table()],
                    [
                        $this->aliasField('academic_period_id = ') . $AssessmentItemResults->aliasField('academic_period_id'),
                        $this->aliasField('student_id = ') . $AssessmentItemResults->aliasField('student_id'),
                        $this->aliasField('institution_class_id = ') . $AssessmentItemResults->aliasField('institution_classes_id'), //POCOR-7201
                        $this->aliasField('institution_class_id') => $params['class_id']
                    ]
                )
                ->leftJoin(
                    [$AssessmentItems->alias() => $AssessmentItems->table()],
                    [
                        $AssessmentItems->aliasField('assessment_id = ') . $AssessmentItemResults->aliasField('assessment_id'),
                        $AssessmentItems->aliasField('education_subject_id = ') . $AssessmentItemResults->aliasField('education_subject_id')
                    ]
                )
                ->contain([$AssessmentGradingOptions->alias(), $AssessmentPeriods->alias(), $EducationSubjects->alias()])
                ->where([
                    $AssessmentItemResults->aliasField('assessment_id') => $params['assessment_id'],
                    $AssessmentPeriods->aliasField('academic_term <> ') => ""
                ])
                ->group([
                    $AssessmentItemResults->aliasField('academic_period_id'),
                    $AssessmentItemResults->aliasField('assessment_id'),
                    $AssessmentItemResults->aliasField('student_id'),
                    $AssessmentItemResults->aliasField('assessment_period_id'),
                    'subject_classification',
                    'academic_term_value'
                ])
                ->hydrate(false)
                ->toArray();

                $sum = 0;
                foreach($withTerm AS $key => $value){
                    //POCOR-6586 starts
                    $assessmentItemResults = TableRegistry::get('assessment_item_results');
                    $assessmentItem = TableRegistry::get('Assessment.AssessmentItems');
                    $assessmentItemResultsData = $assessmentItemResults->find()
                            ->select([
                                $assessmentItemResults->aliasField('institution_id'),
                                $assessmentItemResults->aliasField('academic_period_id'),
                                $assessmentItemResults->aliasField('education_subject_id'),
                                $assessmentItemResults->aliasField('education_grade_id'),
                                $assessmentItemResults->aliasField('assessment_period_id'),
                                $assessmentItemResults->aliasField('assessment_id'),
                                $assessmentItemResults->aliasField('student_id'),
                                $assessmentItemResults->aliasField('marks'),
                            ])
                            ->leftJoin(
                                [$assessmentItem->alias() => $assessmentItem->table()],
                                [
                                    $assessmentItem->aliasField('assessment_id = ') . $assessmentItemResults->aliasField('assessment_id'),
                                    $assessmentItem->aliasField('education_subject_id = ') . $assessmentItemResults->aliasField('education_subject_id')
                                ]
                            )
                            ->order([
                                $assessmentItemResults->aliasField('created') => 'DESC',
                                $assessmentItemResults->aliasField('modified') => 'DESC',
                            ])
                            ->where([
                                $assessmentItemResults->aliasField('student_id') => $value['student_id'],
                                $assessmentItemResults->aliasField('academic_period_id') => $value['academic_period_id'],
                                $assessmentItemResults->aliasField('education_grade_id') => $value['education_grade_id'],
                                $assessmentItemResults->aliasField('assessment_period_id') => $value['assessment_period_id'],
                                $assessmentItem->aliasField('classification') => $value['subject_classification']
                            ])
                            ->group([
                                $assessmentItem->aliasField('education_subject_id'),
                            ])
                            ->toArray();
                    if(!empty($assessmentItemResultsData)){
                        $withTerm_sum_marks = [];
                        $withTerm_sum_assesment_weight = [];
                        foreach ($assessmentItemResultsData as $item_key => $item_val) {
                            $assessmentItemResultsArr = $assessmentItemResults->find()
                                    ->select([
                                        $assessmentItemResults->aliasField('marks')
                                    ])
                                    ->leftJoin(
                                        [$assessmentItem->alias() => $assessmentItem->table()],
                                        [
                                            $assessmentItem->aliasField('assessment_id = ') . $assessmentItemResults->aliasField('assessment_id'),
                                            $assessmentItem->aliasField('education_subject_id = ') . $assessmentItemResults->aliasField('education_subject_id')
                                        ]
                                    )
                                    ->order([
                                        $assessmentItemResults->aliasField('modified') => 'DESC',
                                        $assessmentItemResults->aliasField('created') => 'DESC',
                                    ])
                                    ->where([
                                        $assessmentItemResults->aliasField('student_id') => $item_val['student_id'],
                                        $assessmentItemResults->aliasField('academic_period_id') => $item_val['academic_period_id'],
                                        $assessmentItemResults->aliasField('education_grade_id') => $item_val['education_grade_id'],
                                        $assessmentItemResults->aliasField('assessment_period_id') => $item_val['assessment_period_id'],
                                        $assessmentItemResults->aliasField('education_subject_id') => $item_val['education_subject_id'],
                                    ])
                                    ->first();
                                
                                $withTerm_sum_marks[] = $assessmentItemResultsArr->marks;   
                                $withTerm_sum_assesment_weight[] = $assessmentItemResultsArr->marks*$value['assessment_period']['weight'];
                        }
                        //$withTerm[$key]['marks'] = $assessmentItemResultsData->marks;
                        //$withTerm[$key]['academic_term_total_weighted_marks'] = $assessmentItemResultsData->marks*$value['assessment_period']['weight'];
                        $withTerm[$key]['marks'] = array_sum($withTerm_sum_marks); 
                        $withTerm[$key]['academic_term_total_weighted_marks'] = array_sum($withTerm_sum_assesment_weight);
                    } 
                    //POCOR-6586 ends
                }
            if (!empty($withTerm)) { // If academic_term is setup, to use the academic_term to calculate the average
                // $recordsToUse = $withTerm->toArray();
                $recordsToUse = $withTerm;
                $withoutTerm = []; //POCOR-6911
            } else { // else, to calculate the average by subject_classification
                //Without Term
                $withoutTerm = $AssessmentItemResults->find()
                    ->select([
                        $AssessmentItemResults->aliasField('institution_id'),
                        $AssessmentItemResults->aliasField('academic_period_id'),
                        $AssessmentItemResults->aliasField('education_subject_id'),//POCOR-6479
                        $AssessmentItemResults->aliasField('education_grade_id'),//POCOR-6479
                        $AssessmentItemResults->aliasField('assessment_period_id'),//POCOR-6479
                        $AssessmentItemResults->aliasField('assessment_id'),
                        $AssessmentItemResults->aliasField('student_id'),
                        $AssessmentItemResults->aliasField('marks'),
                        $AssessmentPeriods->aliasField('weight'),
                        'subject_classification' => '(
                            CASE
                            WHEN '.$AssessmentItems->aliasField('classification <> \'\'').' THEN '.$AssessmentItems->aliasField('classification').'
                                ELSE '.$EducationSubjects->aliasField('name').'
                                END
                        )',
                        'academic_term_value' => $AssessmentPeriods->aliasField('name'),
                        'academic_term_total_weighted_marks' => $query->func()->sum($AssessmentItemResults->aliasField('marks * ') . $AssessmentPeriods->aliasField('weight')),
                    ])
                    ->innerJoin(
                        [$this->alias() => $this->table()],
                        [
                            $this->aliasField('academic_period_id = ') . $AssessmentItemResults->aliasField('academic_period_id'),
                            $this->aliasField('student_id = ') . $AssessmentItemResults->aliasField('student_id'),
                            $this->aliasField('institution_class_id') => $params['class_id']
                        ]
                    )
                    ->leftJoin(
                        [$AssessmentItems->alias() => $AssessmentItems->table()],
                        [
                            $AssessmentItems->aliasField('assessment_id = ') . $AssessmentItemResults->aliasField('assessment_id'),
                            $AssessmentItems->aliasField('education_subject_id = ') . $AssessmentItemResults->aliasField('education_subject_id')
                        ]
                    )
                    ->contain([$AssessmentGradingOptions->alias(), $AssessmentPeriods->alias(), $EducationSubjects->alias()])
                    ->where([
                        $AssessmentItemResults->aliasField('assessment_id') => $params['assessment_id']
                    ])
                    ->group([
                        $AssessmentItemResults->aliasField('academic_period_id'),
                        $AssessmentItemResults->aliasField('assessment_id'),
                        $AssessmentItemResults->aliasField('student_id'),
                        $AssessmentItemResults->aliasField('assessment_period_id'),
                        'subject_classification',
                        'academic_term_value'
                    ])
                    ->hydrate(false)
                    ->toArray();
                   
                    foreach($withoutTerm AS $key => $value){
                        //POCOR-6586 starts
                        $assessmentItemResults = TableRegistry::get('assessment_item_results');
                        $assessmentItem = TableRegistry::get('Assessment.AssessmentItems');
                        $assessmentItemResultsData = $assessmentItemResults->find()
                                ->select([
                                    $assessmentItemResults->aliasField('institution_id'),
                                    $assessmentItemResults->aliasField('academic_period_id'),
                                    $assessmentItemResults->aliasField('education_subject_id'),
                                    $assessmentItemResults->aliasField('education_grade_id'),
                                    $assessmentItemResults->aliasField('assessment_period_id'),
                                    $assessmentItemResults->aliasField('assessment_id'),
                                    $assessmentItemResults->aliasField('student_id'),
                                    $assessmentItemResults->aliasField('marks'),
                                ])
                                ->leftJoin(
                                    [$assessmentItem->alias() => $assessmentItem->table()],
                                    [
                                        $assessmentItem->aliasField('assessment_id = ') . $assessmentItemResults->aliasField('assessment_id'),
                                        $assessmentItem->aliasField('education_subject_id = ') . $assessmentItemResults->aliasField('education_subject_id')
                                    ]
                                )
                                ->order([
                                    $assessmentItemResults->aliasField('created') => 'DESC',
                                    $assessmentItemResults->aliasField('modified') => 'DESC',
                                    
                                ])
                                ->where([
                                    $assessmentItemResults->aliasField('student_id') => $value['student_id'],
                                    $assessmentItemResults->aliasField('academic_period_id') => $value['academic_period_id'],
                                    $assessmentItemResults->aliasField('education_grade_id') => $value['education_grade_id'],
                                    $assessmentItemResults->aliasField('assessment_period_id') => $value['assessment_period_id'],
                                    $assessmentItem->aliasField('classification') => $value['subject_classification']
                                ])
                                ->group([
                                    $assessmentItem->aliasField('education_subject_id'),
                                ])
                                ->toArray();
                        if(!empty($assessmentItemResultsData)){
                            $withoutTerm_sum_marks = [];
                            $withoutTerm_sum_assesment_weight = [];
                            foreach ($assessmentItemResultsData as $item_key => $item_val) {
                                $assessmentItemResultsArr = $assessmentItemResults->find()
                                        ->select([
                                            $assessmentItemResults->aliasField('marks')
                                        ])
                                        ->leftJoin(
                                            [$assessmentItem->alias() => $assessmentItem->table()],
                                            [
                                                $assessmentItem->aliasField('assessment_id = ') . $assessmentItemResults->aliasField('assessment_id'),
                                                $assessmentItem->aliasField('education_subject_id = ') . $assessmentItemResults->aliasField('education_subject_id')
                                            ]
                                        )
                                        ->order([
                                            $assessmentItemResults->aliasField('modified') => 'DESC',
                                            $assessmentItemResults->aliasField('created') => 'DESC',
                                        ])
                                        ->where([
                                            $assessmentItemResults->aliasField('student_id') => $item_val['student_id'],
                                            $assessmentItemResults->aliasField('academic_period_id') => $item_val['academic_period_id'],
                                            $assessmentItemResults->aliasField('education_grade_id') => $item_val['education_grade_id'],
                                            $assessmentItemResults->aliasField('assessment_period_id') => $item_val['assessment_period_id'],
                                            $assessmentItemResults->aliasField('education_subject_id') => $item_val['education_subject_id'],
                                        ])
                                        ->first();
                                    
                                    $withoutTerm_sum_marks[] = $assessmentItemResultsArr->marks;   
                                    $withoutTerm_sum_assesment_weight[] = $assessmentItemResultsArr->marks*$value['assessment_period']['weight'];
                            }
                            //$withoutTerm[$key]['marks'] = $assessmentItemResultsData->marks;
                            //$withoutTerm[$key]['academic_term_total_weighted_marks'] += $assessmentItemResultsArr->marks*$value['assessment_period']['weight'];
                            $withoutTerm[$key]['marks'] = array_sum($withoutTerm_sum_marks); 
                            $withoutTerm[$key]['academic_term_total_weighted_marks'] = array_sum($withoutTerm_sum_assesment_weight);
                        } 
                        //POCOR-6586 ends
                    }
                // $recordsToUse = $withoutTerm->toArray();
                $recordsToUse = $withoutTerm; 
                $withTerm = []; //POCOR-6911
            }
            //POCOR-6506[START]
            foreach ($recordsToUse as $record) {
                $studentId = $record['student_id'];
                $academic_term_value = $record['academic_term_value'];
                $subjectClassification = Inflector::slug($record['subject_classification']);
                $totalSum[$studentId][$subjectClassification][$academic_term_value][] = $record;
            }
 
            $mainArray = [];
            $i = 0;
            if(!empty($totalSum)){
                foreach ($totalSum as $tkey => $tval) {
                    $subjectArr = [];
                    foreach ($tval as $subkey => $subval) {
                        $mainArray[$i][$tkey][$subkey] = $subval; 
                        $halfArr = [];
                        foreach ($subval as $halfkey => $halfval) {
                            $mainArray[$i][$tkey][$subkey][$halfkey] = $halfval; 
                            $sum = 0;
                            $weighted_marks = 0;
                            foreach ($halfval as $markkey => $markval) {
                                $sum = $sum + $markval['marks'];
                                $weighted_marks = $weighted_marks + $markval['marks'] * $markval['assessment_period']['weight'];
                                $mainArray[$i] = $markval;
                            } 
                            $mainArray[$i]['marks'] = $sum;
                            $mainArray[$i]['academic_term_total_weighted_marks'] = $weighted_marks;
                            $i++; 
                        }  
                    }
                }
            }
            
            $withTerm = $mainArray;
            //POCOR-6506[END]
            $averageStudentSubjectResults = [];
            foreach ($recordsToUse as $record) {
                $studentId = $record['student_id'];
                $subjectClassification = Inflector::slug($record['subject_classification']);
                $academicTermTotalWeightedMarks = $record['academic_term_total_weighted_marks'];
                if (array_key_exists($studentId, $averageStudentSubjectResults) && array_key_exists($subjectClassification, $averageStudentSubjectResults[$studentId])) {
                    $averageStudentSubjectResults[$studentId][$subjectClassification]['group_academic_term_total_weighted_marks'] += $academicTermTotalWeightedMarks;
                } else {
                    $averageStudentSubjectResults[$studentId][$subjectClassification] = [
                        'institution_id' => $record['institution_id'],
                        'academic_period_id' => $record['academic_period_id'],
                        'assessment_id' => $record['assessment_id'],
                        'subject_classification' => $record['subject_classification'],
                        'group_academic_term_total_weighted_marks' => $academicTermTotalWeightedMarks,
                    ];
                }
            }

            $averageRecords = [];
            foreach ($averageStudentSubjectResults as $studentId => $studentRecord) {
                foreach ($studentRecord as $subjectId => $result) {

                    $averageRecords[] = [
                        'institution_id' => $result['institution_id'],
                        'academic_period_id' => $result['academic_period_id'],
                        'assessment_id' => $result['assessment_id'],
                        'student_id' => $studentId,
                        'subject_classification' => $result['subject_classification'],
                        'academic_term_value' => __('Average'),
                        'academic_term_total_weighted_marks' => ($this->groupAssessmentPeriodCount > 0) ? $result['group_academic_term_total_weighted_marks'] / $this->groupAssessmentPeriodCount : ''
                        // 'academic_term_total_weighted_marks' => 1111
                    ];
                }
            }
            
            // $studentSubjectResults = array_merge($withoutTerm->toArray(), $withTerm->toArray(), $averageRecords);
            $studentSubjectResults = array_merge($withoutTerm, $withTerm, $averageRecords);
            return $studentSubjectResults;
        }
    }

    public function onExcelTemplateInitialiseClassStudents(Event $event, array $params, ArrayObject $extra)
    { 
        $where = [];
        $ids = [];
        if ($params['students'] != 0) {
            foreach ($params['list_of_students']['_ids'] as $value) {
                $ids[] = $value;
            }
           $where[$this->aliasField('student_id IN')] = $ids; 
        }
        if ($params['student_status_id'] != 0) {
            $where[$this->aliasField('student_status_id')] = $params['student_status_id'];
        }
    
        if (array_key_exists('class_id', $params)) {
            $entity = $this->find()
                ->contain([
                    'Users' => [
                        'fields' => [
                            'id',
                            'username',
                            'openemis_no',
                            'first_name',
                            'middle_name',
                            'third_name',
                            'last_name',
                            'preferred_name',
                            'email',
                            'address',
                            'postal_code',
                            'address_area_id',
                            'birthplace_area_id',
                            'gender_id',
                            'date_of_birth',
                            'date_of_death',
                            'nationality_id',
                            'identity_type_id',
                            'identity_number',
                            'external_reference',
                            'super_admin',
                            'status',
                            'last_login',
                            'photo_name',
                            'photo_content',
                            'preferred_language',
                            'is_student',
                            'is_staff',
                            'is_guardian'
                        ],
                        'Genders' => [
                            'fields' => [
                                'id',
                                'name',
                                'code',
                                'order'
                            ]
                        ],
                        'BirthplaceAreas' => [
                            'fields' => [
                                'id',
                                'code',
                                'name',
                                'is_main_country',
                                'parent_id',
                                'lft',
                                'rght',
                                'area_administrative_level_id',
                                'order',
                                'visible'
                            ]
                        ],
                        'MainNationalities' => [
                            'fields' => [
                                'id',
                                'name',
                                'order',
                                'visible',
                                'editable',
                                'identity_type_id',
                                'default',
                                'international_code',
                                'national_code'
                            ]
                        ]
                    ]
                ])
                ->where([
                    $this->aliasField('institution_class_id') => $params['class_id'],
                    $where
                ])
                ->order(['Users.first_name', 'Users.last_name'])
                // ->hydrate(false)
                ->all();

            return $entity->toArray();
        }
    }

    public function onExcelTemplateInitialiseInstitutions(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params)) {
            $Institutions = TableRegistry::get('Institution.Institutions');
            $entity = $Institutions->get($params['institution_id'], [
                'contain' => ['Areas', 'AreaAdministratives']
            ]);

            return $entity->toArray();
        }
    }

    public function onExcelTemplateInitialiseInstitutionClasses(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('class_id', $params)) {
            $InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
            $entity = $InstitutionClasses->get($params['class_id']);
            return $entity->toArray();
        }
    }

    public function onExcelTemplateInitialiseInstitutionStudentAbsences(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('class_id', $params) && array_key_exists('assessment_id', $params) && array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $InstitutionStudentAbsences = TableRegistry::get('Institution.InstitutionStudentAbsences');
            $studentAbsenceResults = $InstitutionStudentAbsences
                ->find()
                ->innerJoin(
                    [$this->alias() => $this->table()],
                    [
                        $this->aliasField('institution_id = ') . $InstitutionStudentAbsences->aliasField('institution_id'),
                        $this->aliasField('student_id = ') . $InstitutionStudentAbsences->aliasField('student_id'),
                        $this->aliasField('institution_class_id') => $params['class_id']
                    ]
                )
                ->where([
                    $InstitutionStudentAbsences->aliasField('institution_id') => $params['institution_id'],
                    $InstitutionStudentAbsences->aliasField('academic_period_id') => $params['academic_period_id']
                ])
                ->hydrate(false)
                ->all();

            $results = [];
            foreach ($studentAbsenceResults as $key => $obj) {
                $studentId = $obj['student_id'];
                if (!array_key_exists($studentId, $results)) {
                    $results[$studentId] = [
                        'student_id' => $studentId,
                        'institution_id' => $obj['institution_id'],
                        'number_of_days' => 0
                    ];
                }

                $results[$studentId]['number_of_days']++;
            }

            $results = array_values($results);
            return $results;
        }
    }

    /*POCOR-6355 starts*/
    public function onExcelTemplateInitialiseEducationGrades(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('grade_id', $params)) {
            $EducationGrades = TableRegistry::get('Education.EducationGrades');
            $entity = $EducationGrades->get($params['grade_id']);
            return $entity->toArray();
        }
    }
    /*POCOR-6355 ends*/
}
