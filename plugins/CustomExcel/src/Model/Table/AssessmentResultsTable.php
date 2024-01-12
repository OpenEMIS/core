<?php

namespace CustomExcel\Model\Table;

use ArrayObject;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Log\Log;
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
        $this->belongsTo('NextInstitutionClasses', ['className' => 'Institution.InstitutionClasses', 'foreignKey' => 'next_institution_class_id']);
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

    /**
     * @param Event $event
     * @param array $params
     * @param ArrayObject $extra
     * @return array
     */
    public function onExcelTemplateInitialiseAssessments(Event $event, array $params, ArrayObject $extra)
    {

        return $this->initialiseAssessments('initialiseAssessments', $params);
    }

    public function onExcelTemplateInitialiseAssessmentItems(Event $event, array $params, ArrayObject $extra)
    {
        return $this->initialiseAssessmentItems($params);
    }

    public function onExcelTemplateInitialiseAssessmentItemsGradingTypes(Event $event, array $params, ArrayObject $extra)
    {

        return $this->initialiseAssessmentItemsGradingTypes($params);
    }

    public function onExcelTemplateInitialiseAssessmentPeriods(Event $event, array $params, ArrayObject $extra)
    {
        return $this->initialiseAssessmentPeriods($params);
    }

    public function onExcelTemplateInitialiseAssessmentItemResults(Event $event, array $params, ArrayObject $extra)
    {

        if (isset($params['class_id'])
            && isset($params['assessment_id'])
            && isset($params['institution_id'])) {

            $AssessmentItemResults = TableRegistry::get('Assessment.AssessmentItemResults');
            $institution_class_id = $params['class_id'];
            $assessment_id = $params['assessment_id'];
            $institution_id = $params['institution_id'];
            $results = $AssessmentItemResults->find()
                ->innerJoin(
                    [$this->alias() => $this->table()],
                    [
                        $this->aliasField('institution_id = ') . $AssessmentItemResults->aliasField('institution_id'),
                        $this->aliasField('academic_period_id = ') . $AssessmentItemResults->aliasField('academic_period_id'),
                        $this->aliasField('student_id = ') . $AssessmentItemResults->aliasField('student_id'),
                        $this->aliasField('institution_class_id') => $institution_class_id
                    ]
                )
                ->contain(['AssessmentGradingOptions.AssessmentGradingTypes'])
                ->where([
                    $AssessmentItemResults->aliasField('assessment_id') => $assessment_id,
// to have marks from other institutions                   $AssessmentItemResults->aliasField('institution_class_id') => $institution_class_id,
// to have marks from other institutions                   $AssessmentItemResults->aliasField('institution_id') => $institution_id
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
        if (isset($params['assessment_id']) && isset($params['class_id'])) {
//            $start_time = microtime(true);
            $AssessmentItems = TableRegistry::get('Assessment.AssessmentItems');
            $EducationSubjects = TableRegistry::get('Education.EducationSubjects');
            $ClassSubjects = TableRegistry::get('Institution.InstitutionClassSubjects');
            $InstitutionSubjects = TableRegistry::get('Institution.InstitutionSubjects');

            $query = $AssessmentItems->find();
            $selectedColumns = [
                'subject_classification' => '(
                    CASE
                    WHEN ' . $AssessmentItems->aliasField('classification <> \'\'') . ' THEN ' . $AssessmentItems->aliasField('classification') . '
                        ELSE ' . $EducationSubjects->aliasField('name') . '
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
//            $functionName = __FUNCTION__;
//            $end_time = microtime(true);
//            $executionTimeMs = ($end_time - $start_time) * 1000;
//            $this->log("{$functionName}\n
//            Function execution time: {$executionTimeMs} ms", 'debug');

            return $results->toArray();
        }
    }

    public function onExcelTemplateInitialiseGroupAssessmentItemsGradingTypes(Event $event, array $params, ArrayObject $extra)
    {
        if (isset($params['assessment_id'])) {
//            $start_time = microtime(true);
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
                    WHEN ' . $AssessmentItems->aliasField('classification <> \'\'') . ' THEN ' . $AssessmentItems->aliasField('classification') . '
                        ELSE ' . $EducationSubjects->aliasField('name') . '
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
                    WHEN ' . $AssessmentItems->aliasField('classification <> \'\'') . ' THEN ' . $AssessmentItems->aliasField('classification') . '
                        ELSE ' . $EducationSubjects->aliasField('name') . '
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

                if (!isset($sumRecordBySubjects[$subjectClassification])) {
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
                    'academic_term_value' =>  __('Average'), // POCOR-8066
                    'academic_term_total_weighted_max' => ($this->groupAssessmentPeriodCount > 0) ? $subjectObj['total_weight'] / $this->groupAssessmentPeriodCount : ''
                ];
            }

            $groupAssessmentItemsGradingTypes = array_merge($withoutTerm->toArray(), $withTerm->toArray(), $averageRecords);
//            $functionName = __FUNCTION__;
//            $end_time = microtime(true);
//            $executionTimeMs = ($end_time - $start_time) * 1000;
//            $this->log("{$functionName}\n
//            Function execution time: {$executionTimeMs} ms", 'debug');

            return $groupAssessmentItemsGradingTypes;
        }
    }

    public function onExcelTemplateInitialiseGroupAssessmentPeriods(Event $event, array $params, ArrayObject $extra)
    {
        if (isset($params['assessment_id'])) {
//            $start_time = microtime(true);
            $AssessmentPeriods = TableRegistry::get('Assessment.AssessmentPeriods');
            $query = $AssessmentPeriods->find();
            $selectedColumns = [
                'academic_term_value' => '(
                    CASE
                    WHEN ' . $AssessmentPeriods->aliasField('academic_term <> \'\'') . ' THEN ' . $AssessmentPeriods->aliasField('academic_term') . '
                        ELSE ' . $AssessmentPeriods->aliasField('name') . '
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
//            $functionName = __FUNCTION__;
//            $end_time = microtime(true);
//            $executionTimeMs = ($end_time - $start_time) * 1000;
//            $this->log("{$functionName}\n
//            Function execution time: {$executionTimeMs} ms", 'debug');

            return $academicTermResults;
        }
    }

    public function onExcelTemplateInitialiseGroupAssessmentPeriodsWithTerms(Event $event, array $params, ArrayObject $extra)
    {
        if (isset($params['assessment_id'])) {
//            $start_time = microtime(true);
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
//            $functionName = __FUNCTION__;
//            $end_time = microtime(true);
//            $executionTimeMs = ($end_time - $start_time) * 1000;
//            $this->log("{$functionName}\n
//            Function execution time: {$executionTimeMs} ms", 'debug');
            return $periodsWithTermOrders;
        }
    }
    public function onExcelTemplateInitialiseGroupAssessmentItemResults(Event $event,
                                                                        array $params,
                                                                        ArrayObject $extra)
    {

//        $this->log('onExcelTemplateInitialiseGroupAssessmentItemResults', 'debug');
        $archive = false;
        if (isset($params['class_id'])
            && isset($params['assessment_id'])
            && isset($params['institution_id'])) {
//            $start_time = microtime(true);
            $options = [];
            $options['institution_class_id'] = $params['class_id'];
            $options['assessment_id'] = $params['assessment_id'];
            $options['institution_id'] = $params['institution_id'];
            if(isset( $params['academic_period_id'])){
                $options['academic_period_id'] = $params['academic_period_id'];
            }
            if(isset( $params['grade_id'])){
                $options['grade_id'] = $params['grade_id'];
            }
            $groupAssessmentItemResults = self::getGroupAssessmentItemResults($options, $archive);
            
//            $functionName = __FUNCTION__;
//            $end_time = microtime(true);
//            $executionTimeMs = ($end_time - $start_time) * 1000;
//            Log::write('debug', "{$functionName}\n
//            Function execution time: {$executionTimeMs} ms");
            return $groupAssessmentItemResults;
        }
    }

    public function onExcelTemplateInitialiseClassStudents(Event $event, array $params, ArrayObject $extra)
    {
//        $this->log('onExcelTemplateInitialiseClassStudents', 'debug');
//        $this->log($params, 'debug');

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

        if (isset($params['class_id'])) {
//            $start_time = microtime(true);
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
//            $functionName = __FUNCTION__;
//            $end_time = microtime(true);
//            $executionTimeMs = ($end_time - $start_time) * 1000;
//            Log::write('debug', "{$functionName}\n
//            Function execution time: {$executionTimeMs} ms");
            return $entity->toArray();
        }
    }

    public function onExcelTemplateInitialiseInstitutions(Event $event, array $params, ArrayObject $extra)
    {
        if (isset($params['institution_id'])) {
//            $start_time = microtime(true);
            $Institutions = TableRegistry::get('Institution.Institutions');
            $entity = $Institutions->get($params['institution_id'], [
                'contain' => ['Areas', 'AreaAdministratives']
            ]);
//            $functionName = __FUNCTION__;
//            $end_time = microtime(true);
//            $executionTimeMs = ($end_time - $start_time) * 1000;
//            Log::write('debug', "{$functionName}\n
//            Function execution time: {$executionTimeMs} ms");
            return $entity->toArray();
        }
    }

    public function onExcelTemplateInitialiseInstitutionClasses(Event $event, array $params, ArrayObject $extra)
    {
        if (isset($params['class_id'])) {
//            $start_time = microtime(true);
            $InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
            $entity = $InstitutionClasses->get($params['class_id']);
//            $functionName = __FUNCTION__;
//            $end_time = microtime(true);
//            $executionTimeMs = ($end_time - $start_time) * 1000;
//            Log::write('debug', "{$functionName}\n
//            Function execution time: {$executionTimeMs} ms");

            return $entity->toArray();
        }
    }

    public function onExcelTemplateInitialiseInstitutionStudentAbsences(Event $event, array $params, ArrayObject $extra)
    {
        if (isset($params['class_id']) && 
            isset($params['assessment_id']) && 
            isset($params['institution_id']) &&
            isset($params['institution_id'])) {
//            $start_time = microtime(true);
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
                if (!isset($results[$studentId])) {
                    $results[$studentId] = [
                        'student_id' => $studentId,
                        'institution_id' => $obj['institution_id'],
                        'number_of_days' => 0
                    ];
                }

                $results[$studentId]['number_of_days']++;
            }

            $results = array_values($results);
//            $functionName = __FUNCTION__;
//            $end_time = microtime(true);
//            $executionTimeMs = ($end_time - $start_time) * 1000;
//            Log::write('debug', "{$functionName}\n
//            Function execution time: {$executionTimeMs} ms");
            return $results;
        }
    }

    /*POCOR-6355 starts*/
    public function onExcelTemplateInitialiseEducationGrades(Event $event, array $params, ArrayObject $extra)
    {
        if (isset($params['grade_id'])) {
//            $start_time = microtime(true);
            $EducationGrades = TableRegistry::get('Education.EducationGrades');
            $entity = $EducationGrades->get($params['grade_id']);
//            $functionName = __FUNCTION__;
//            $end_time = microtime(true);
//            $executionTimeMs = ($end_time - $start_time) * 1000;
//            Log::write('debug', "{$functionName}\n
//            Function execution time: {$executionTimeMs} ms");
            return $entity->toArray();
        }
    }
    /*POCOR-6355 ends*/

    /**
     * @param $array
     * @param $key
     * @return |null
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private static function getFromArray($array, $key)
    {
        return isset($array[$key]) ? $array[$key] : null;
    }

    /**
     * @param array $params
     * @return array
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private static function getMarksForClass(array $params, $archive=false)
    {

        $start_time = microtime(true);
        $academic_period_id = self::getFromArray($params, 'academic_period_id');
        $institution_id = self::getFromArray($params, 'institution_id');
        $institution_class_id = self::getFromArray($params, 'institution_class_id');
        $assessment_id = self::getFromArray($params, 'assessment_id');
        $education_grade_id = self::getFromArray($params, 'grade_id');
        $student_id = self::getFromArray($params, 'student_id');
//        $student_ids = self::getDistinctStudents($institution_class_id);
        $Results = TableRegistry::get('Assessment.AssessmentItemResults');
        $marks = [];
//        foreach ($student_ids as $student_id) {
        $education_subject_id = -1;
        $assessment_period_id = -1;
        $assessment_grading_option_id = -1;
        if ($education_grade_id == null) {
            $education_grade_id = -1;
        }
        if ($academic_period_id == null) {
            $academic_period_id = -1;
        }
        if ($institution_id == null) {
            $institution_id = -1;
        }
        if ($assessment_id == null) {
            $assessment_id = -1;
        }
        if ($student_id == null) {
            $student_id = -1;
        }
        $id = -1;
        $options = ["student_id" => $student_id,
            "institution_id" => $institution_id,
            "institution_class_id" => $institution_class_id,
            "academic_period_id" => $academic_period_id,
            "education_grade_id" => $education_grade_id,
            "education_subject_id" => $education_subject_id,
            "id" => $id,
            'assessment_grading_option_id' => $assessment_grading_option_id,
            "assessment_period_id" => $assessment_period_id,
            'assessment_id' => $assessment_id];
//        $functionName = __FUNCTION__;
//        Log::write('debug', "{$functionName}");
//        Log::write('debug', $options);
        $marks = $Results::getLastMark($options, $archive);
//        $end_time = microtime(true);
//        $executionTimeMs = ($end_time - $start_time) * 1000;
//        Log::write('debug', "{$functionName}\n
//            Function execution time: {$executionTimeMs} ms");
        return $marks;
    }

    /**
     * @param array $marks
     * @return array
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private static function getMarksWithSubjectClassificationWeight(array $marks)
    {
//        $start_time = microtime(true);
        $new_marks = [];
        foreach ($marks as $mark) {
            $assessment_id = $mark['assessment_id'];
            $education_subject_id = $mark['education_subject_id'];
            $assessment_period_id = $mark['assessment_period_id'];
            $where = ['education_subject_id' => $education_subject_id,
                'assessment_id' => $assessment_id];
            $assessment_item = self::getRecordByOptions('assessment_items', $where);
            $education_subject = self::getRelatedRecord('education_subjects', $education_subject_id);
            $assessment_period = self::getRelatedRecord('assessment_periods', $assessment_period_id);
            $weight = floatval($assessment_period['weight']);
            $simple_mark = floatval($mark['marks']);
            $weighted_mark = $simple_mark * $weight;
            $academic_term = trim($assessment_period['academic_term']);
            if(!$academic_term){
                $academic_term = $assessment_period['name'];
            }
            $education_subject_name = $education_subject['name'];
            $classification = $assessment_item['classification'];
            if (!$classification or trim($classification) == "") {
                $classification = $education_subject_name;
            }
            $mark['subject_classification'] = $classification;
            $mark['weight'] = $weight;
            $mark['simple_mark'] = $simple_mark;
            $mark['weighted_mark'] = $weighted_mark;
            $mark['academic_term'] = $academic_term;
            $new_marks[] = $mark;
        }
//        }
//        $functionName = __FUNCTION__;
//        $end_time = microtime(true);
//        $executionTimeMs = ($end_time - $start_time) * 1000;
//        Log::write('debug', "{$functionName}\n
//            Function execution time: {$executionTimeMs} ms");
        return $new_marks;
    }

    /**
     * @param array $marksWithSubjectClassificationWeight
     * @return array
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private static function getMarksPerStudentArray(array $marksWithSubjectClassificationWeight)
    {
//        $start_time = microtime(true);
        $marksPerStudent = [];
        foreach ($marksWithSubjectClassificationWeight as $record) {
            $studentId = $record['student_id'];
            $academic_term = $record['academic_term'];
            $subjectClassification = Inflector::slug($record['subject_classification']);
            $marksPerStudent[$studentId][$subjectClassification][$academic_term][] = $record;
        }
//        $functionName = __FUNCTION__;
//        $end_time = microtime(true);
//        $executionTimeMs = ($end_time - $start_time) * 1000;
//        Log::write('debug', "{$functionName}\n
//            Function execution time: {$executionTimeMs} ms");
        return $marksPerStudent;
    }

    /**
     * @param array $marksPerStudent
     * @return array
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private static function getTotalMarksPerStudent(array $marksPerStudent)
    {
//        $start_time = microtime(true);
        $totalMarksPerStudent = [];
        $i = 0;
        if (!empty($marksPerStudent)) {
            foreach ($marksPerStudent as $student_id => $student_marks) {
                $subjectArr = [];
                foreach ($student_marks as $subject_classification => $subject_classification_marks) {
                    $totalMarksPerStudent[$i][$student_id][$subject_classification] = $subject_classification_marks;
                    $halfArr = [];
                    foreach ($subject_classification_marks as $academic_term => $academic_term_marks) {
                        $totalMarksPerStudent[$i][$student_id][$subject_classification][$academic_term] = $academic_term_marks;
                        $simple_marks_sum = 0;
                        $weighted_marks_sum = 0;
                        foreach ($academic_term_marks as $markkey => $markval) {
                            $simple_marks_sum = $simple_marks_sum + $markval['simple_mark'];
                            $weighted_marks_sum = $weighted_marks_sum + $markval['weighted_mark'];
                            $totalMarksPerStudent[$i] = $markval;
                        }
                        $totalMarksPerStudent[$i]['academic_term_total_marks'] = $simple_marks_sum;
                        $totalMarksPerStudent[$i]['academic_term_value'] = $totalMarksPerStudent[$i]['academic_term'];
                        $totalMarksPerStudent[$i]['marks'] = $simple_marks_sum;
                        unset($totalMarksPerStudent[$i]['simple_mark']);
                        unset($totalMarksPerStudent[$i]['weighted_mark']);
                        unset($totalMarksPerStudent[$i]['academic_term']);
                        $totalMarksPerStudent[$i]['academic_term_total_weighted_marks'] = $weighted_marks_sum;
                        $i++;
                    }
                }
            }
        }
//        $functionName = __FUNCTION__;
//        $end_time = microtime(true);
//        $executionTimeMs = ($end_time - $start_time) * 1000;
//        Log::write('debug', "{$functionName}\n
//            Function execution time: {$executionTimeMs} ms");

        return $totalMarksPerStudent;
    }

    /**
     * @param array $marks
     * @return int
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private static function getGroupAssessmentPeriodCount(array $marks)
    {
//        $start_time = microtime(true);
        $assessment_ids = array_unique(array_column($marks, 'assessment_id'));
        $AssessmentPeriods = TableRegistry::get('Assessment.AssessmentPeriods');
        $query = $AssessmentPeriods->find();
        $selectedColumns = [
            'academic_term_value' => '(
                    CASE
                    WHEN ' . $AssessmentPeriods->aliasField('academic_term <> \'\'') . ' THEN ' . $AssessmentPeriods->aliasField('academic_term') . '
                        ELSE ' . $AssessmentPeriods->aliasField('name') . '
                        END
                    )',
            'total_period_weight' => $query->func()->sum($AssessmentPeriods->aliasField('weight'))
        ];
        if(!empty($assessment_ids)){
            $assessment_periods_per_assessment_id = $AssessmentPeriods->find()
            ->select($selectedColumns)
            ->where([$AssessmentPeriods->aliasField('assessment_id IN') => $assessment_ids])
            ->group(['academic_term_value'])
            ->hydrate(false)
            ->all();
        }

        $groupAssessmentPeriodCount = 0;
        // if (!$assessment_periods_per_assessment_id->isEmpty()) {
        if (!empty($assessment_periods_per_assessment_id)) { // POCOR-7904
            $countList = $assessment_periods_per_assessment_id->toArray();
            foreach ($countList as $record) {
                if ($record['total_period_weight'] > 0) {
                    ++$groupAssessmentPeriodCount;
                }
            }
        }
//        $functionName = __FUNCTION__;
//        $end_time = microtime(true);
//        $executionTimeMs = ($end_time - $start_time) * 1000;
//        Log::write('debug', "{$functionName}\n
//            Function execution time: {$executionTimeMs} ms");

        return $groupAssessmentPeriodCount;
    }

    /**
     * @param array $averageStudentSubjectResults
     * @param $groupAssessmentPeriodCount
     * @return array
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private static function getAverageRecords(array $averageStudentSubjectResults, $groupAssessmentPeriodCount)
    {
//        $start_time = microtime(true);
        $averageRecords = [];
        foreach ($averageStudentSubjectResults as $studentId => $studentRecord) {
            foreach ($studentRecord as $subjectId => $result) {
                $average_academic_term_total_weighted_marks = ($groupAssessmentPeriodCount > 0)
                    ? $result['group_academic_term_total_weighted_marks'] / $groupAssessmentPeriodCount : '';
                $averageRecords[] = [
                    'institution_id' => $result['institution_id'],
                    'academic_period_id' => $result['academic_period_id'],
                    'assessment_id' => $result['assessment_id'],
                    'student_id' => $studentId,
                    'subject_classification' => $result['subject_classification'],
                    'academic_term_value' => __('Average'),
                    'academic_term_total_weighted_marks' => $average_academic_term_total_weighted_marks,
                    // 'academic_term_total_weighted_marks' => 1111
                ];
            }
        }
//        $functionName = __FUNCTION__;
//        $end_time = microtime(true);
//        $executionTimeMs = ($end_time - $start_time) * 1000;
//        Log::write('debug', "{$functionName}\n
//            Function execution time: {$executionTimeMs} ms");
        return $averageRecords;
    }

    /**
     * @param array $marksWithSubjectClassificationWeight
     * @return array
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private static function getAverageStudentSubjectResults(array $marksWithSubjectClassificationWeight)
    {
//        $start_time = microtime(true);
        $averageStudentSubjectResults = [];

        foreach ($marksWithSubjectClassificationWeight as $record) {
            $studentId = $record['student_id'];
            $subjectClassification = Inflector::slug($record['subject_classification']);
            $academicTermTotalWeightedMarks = $record['weighted_mark'];

            if (isset($averageStudentSubjectResults[$studentId])
                && isset($averageStudentSubjectResults[$studentId][$subjectClassification])) {
                $averageStudentSubjectResults[$studentId][$subjectClassification]
                ['group_academic_term_total_weighted_marks'] += $academicTermTotalWeightedMarks;
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
//        $functionName = __FUNCTION__;
//        $end_time = microtime(true);
//        $executionTimeMs = ($end_time - $start_time) * 1000;
//        Log::write('debug', "{$functionName}\n
//            Function execution time: {$executionTimeMs} ms");
        return $averageStudentSubjectResults;
    }

    /**
     * @param array $params
     * @param bool $archive
     * @return array
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public static function getGroupAssessmentItemResults(array $params,$archive=false)
    {
//        $start_time = microtime(true);
        $marks = self::getMarksForClass($params, $archive);
        $marksWithSubjectClassificationWeight = self::getMarksWithSubjectClassificationWeight($marks);
        $marksPerStudent = self::getMarksPerStudentArray($marksWithSubjectClassificationWeight);
        $totalMarksPerStudent = self::getTotalMarksPerStudent($marksPerStudent);
        $averageStudentSubjectResults = self::getAverageStudentSubjectResults($marksWithSubjectClassificationWeight);
//        print_r($averageStudentSubjectResults);
        $groupAssessmentPeriodCount = self::getGroupAssessmentPeriodCount($marks);
        $averageRecords = self::getAverageRecords($averageStudentSubjectResults, $groupAssessmentPeriodCount);
//        print_r($averageRecords);
        $studentsSubjectResults = array_merge($totalMarksPerStudent, $averageRecords);
//        $functionName = __FUNCTION__;
//        $end_time = microtime(true);
//        $executionTimeMs = ($end_time - $start_time) * 1000;
//        Log::write('debug', "{$functionName}\n
//            Function execution time: {$executionTimeMs} ms");
        return $studentsSubjectResults;
    }

    /**
     * @param $tableName
     * @param $relatedField
     * @return array|null
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public static function getRelatedRecord($tableName, $relatedField)
    {
        if (!$relatedField) {
            return null;
        }
        $Table = TableRegistry::get($tableName);
        try {
            $related = $Table->get($relatedField);
            return $related->toArray();
        } catch (RecordNotFoundException $e) {
            return null;
        }
        return null;
    }

    /**
     * @param $tableName
     * @param $where
     * @return array|null
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public static function getRecordByOptions($tableName, $where)
    {
        if (!$where) {
            return null;
        }
        $Table = TableRegistry::get($tableName);
        try {
            $related = $Table->find()->where($where)->first();
            return $related->toArray();
        } catch (RecordNotFoundException $e) {
            return null;
        }
        return null;
    }

    /**
     * @param $function
     * @param $args
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private static function measureExecutionTime($function, $args) {
        $startTime = microtime(true);
        call_user_func_array($function, $args);
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        Log::write('debug', "{$function}\n
            Function execution time: {$executionTime} ms");
    }

    /**
     * @param array $params
     * @return array
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private function initialiseAssessments(array $params)
    {
        if (isset($params['assessment_id'])) {
            $Assessments = TableRegistry::get('Assessment.Assessments');
            $assessment_id = $params['assessment_id'];
            $entity = $Assessments->get($assessment_id, [
                'contain' => ['AcademicPeriods', 'EducationGrades']
            ]);
            return $entity->toArray();
        }
        return [];
    }

    /**
     * @param array $params
     * @return array
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private function initialiseAssessmentItems(array $params)
    {
        if (isset($params['assessment_id'])) {
            $AssessmentItems = TableRegistry::get('Assessment.AssessmentItems');
            $assessment_id = $params['assessment_id'];
            $results = $AssessmentItems->find()
                ->contain(['EducationSubjects'])
                ->where([$AssessmentItems->aliasField('assessment_id') => $assessment_id])
                ->order(['EducationSubjects.order', 'EducationSubjects.code', 'EducationSubjects.name'])
                ->hydrate(false)
                ->all();
            return $results->toArray();
        }
    }

    /**
     * @param array $params
     * @return array
     */
    private function initialiseAssessmentItemsGradingTypes(array $params)
    {
        if (isset($params['assessment_id'])) {
//            $start_time = microtime(true);
            $AssessmentItemsGradingTypes = TableRegistry::get('Assessment.AssessmentItemsGradingTypes');
            $assessment_id = $params['assessment_id'];
            $results = $AssessmentItemsGradingTypes->find()
                ->contain(['AssessmentGradingTypes', 'AssessmentPeriods', 'EducationSubjects'])
                ->where([$AssessmentItemsGradingTypes->aliasField('assessment_id') => $assessment_id])
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
                                    $duration = number_format($max / 60, 2);
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
//            $functionName = __FUNCTION__;
//            $end_time = microtime(true);
//            $executionTimeMs = ($end_time - $start_time) * 1000;
//            $this->log("{$functionName}\n
//        Function execution time: {$executionTimeMs} ms", 'debug');

            return $results->toArray();
        }
    }

    /**
     * @param array $params
     * @return array
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private function initialiseAssessmentPeriods(array $params)
    {
        if (isset($params['assessment_id'])) {
            $AssessmentPeriods = TableRegistry::get('Assessment.AssessmentPeriods');
            $assessment_id = $params['assessment_id'];
            $results = $AssessmentPeriods->find()
                ->where([$AssessmentPeriods->aliasField('assessment_id') => $assessment_id])
                ->hydrate(false)
                ->all();
            return $results->toArray();
        }
    }

}
