<?php

namespace CustomExcel\Model\Table;

use ArrayObject;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Datasource\ResultSetInterface;
//use Cake\Utility\Inflector;
use Cake\Utility\Text;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use Cake\ORM\Table; // POCOR-8578
use Cake\Utility\Inflector; // POCOR-8578

class AssessmentResultsTable extends AppTable
{
    use OptionsTrait;
    private $groupAssessmentPeriodCount = 0;
    private $groupAssessmentItemsGradingTypes = []; // POCOR-8224 to excape double calculation
    private $groupAssessmentItems = []; // POCOR-8224 to excape double calculation

    const STUDENT_ENROLLED_STATUS = 1;

    public function initialize(array $config): void
    {
        $this->setTable('institution_class_students');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'API.Students', 'foreignKey' => 'student_id']); // POCOR-8578
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
//                // 'AssessmentItems',
//                // 'AssessmentItemsGradingTypes',
//                // 'AssessmentPeriods',
//                // 'AssessmentItemResults',
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

    public function implementedEvents(): array
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

            $AssessmentItemResults = self::getDynamicTableInstance('Assessment.AssessmentItemResults'); // POCOR-8578
            $institution_class_id = $params['class_id'];
            $assessment_id = $params['assessment_id'];
            $institution_id = $params['institution_id'];
            $results = $AssessmentItemResults->find()
                ->innerJoin(
                    [$this->getAlias() => $this->getTable()],
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
                ->disableHydration() // POCOR-8578
                ->all();

            return $results->toArray();
        }
    }

    /**
     * POCOR-8224 refactured
     * @param Event $event
     * @param array $params
     * @param ArrayObject $extra
     * @return array|null
     */
    public function onExcelTemplateInitialiseGroupAssessmentItems(Event $event, array $params, ArrayObject $extra)
    {
        $groupAssessmentItems = $this->groupAssessmentItems;
        if(empty($groupAssessmentItems)){
            $groupAssessmentItems = $this->getGroupAssessmentItems($params);
        }
        $this->groupAssessmentItems = [];
        return $groupAssessmentItems;
    }

    /**
     * @param Event $event
     * @param array $params
     * @param ArrayObject $extra
     * @return array|null
     *  POCOR-8224 refactured
     */
    public function onExcelTemplateInitialiseGroupAssessmentItemsGradingTypes(Event $event, array $params, ArrayObject $extra)
    {

        $groupAssessmentItemsGradingTypes = $this->groupAssessmentItemsGradingTypes;
        if(empty($groupAssessmentItemsGradingTypes)){
            $groupAssessmentItemsGradingTypes = $this->getGroupAssessmentItemsGradingTypes($params);
        }
        return $groupAssessmentItemsGradingTypes;
    }

    public function onExcelTemplateInitialiseGroupAssessmentPeriods(Event $event, array $params, ArrayObject $extra)
    {
        if (isset($params['assessment_id'])) {
//            $start_time = microtime(true);
            $AssessmentPeriods = self::getDynamicTableInstance('Assessment.AssessmentPeriods'); // POCOR-8578
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
                ->enableHydration(false)
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
            $AssessmentPeriods = self::getDynamicTableInstance('Assessment.AssessmentPeriods'); // POCOR-8578
            $query = $AssessmentPeriods->find();

            $withoutTerm = $query
                ->select([
                    'academic_term_value' => $AssessmentPeriods->aliasField('name'),
                    'academic_term' => $AssessmentPeriods->aliasField('academic_term'),
                    'total_period_weight' => $AssessmentPeriods->aliasField('weight')
                ])
                ->where([$AssessmentPeriods->aliasField('assessment_id') => $params['assessment_id']])
                ->enableHydration(false)
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
                ->enableHydration(false)
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
            $groupAssessmentItemResults = $this->getGroupAssessmentItemResults($options);
        }
        return $groupAssessmentItemResults;
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
                    'Users'
                    => [
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
                ->enableAutoFields() // POCOR-8578
                ->order(['Users.first_name', 'Users.last_name'])
//                ->disableHydration() // POCOR-8578
                ->all();
//            dd($entity);
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
            $Institutions = self::getDynamicTableInstance('Institution.Institutions'); // POCOR-8578
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
            $InstitutionClasses = self::getDynamicTableInstance('Institution.InstitutionClasses'); // POCOR-8578
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
            $InstitutionStudentAbsences = self::getDynamicTableInstance('Institution.InstitutionStudentAbsences'); // POCOR-8578
            $studentAbsenceResults = $InstitutionStudentAbsences
                ->find()
                ->innerJoin(
                    [$this->getAlias() => $this->getTable()],
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
                ->enableHydration(false)
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
            $EducationGrades = self::getDynamicTableInstance('Education.EducationGrades'); // POCOR-8578
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
    private static function getMarksForClass(array $params)
    {
        $academic_period_id = self::getFromArray($params, 'academic_period_id');
        $institution_id = self::getFromArray($params, 'institution_id');
        $institution_class_id = self::getFromArray($params, 'institution_class_id');
        $assessment_id = self::getFromArray($params, 'assessment_id');
        $education_grade_id = self::getFromArray($params, 'grade_id');
        $student_id = self::getFromArray($params, 'student_id');
//        $student_ids = self::getDistinctStudents($institution_class_id);
        $Results = self::getDynamicTableInstance('Assessment.AssessmentItemResults'); // POCOR-8578
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

        $marks = $Results::getLastMark($options);
        return $marks;
    }

    /**
     * @param array $params
     * @return array
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private static function getExemptsForClass(array $params)
    {

        $start_time = microtime(true);
        $academic_period_id = self::getFromArray($params, 'academic_period_id');
        $institution_id = self::getFromArray($params, 'institution_id');
        $institution_class_id = self::getFromArray($params, 'institution_class_id');
        $assessment_id = self::getFromArray($params, 'assessment_id');
        $education_grade_id = self::getFromArray($params, 'grade_id');
        $student_id = self::getFromArray($params, 'student_id');
//        $student_ids = self::getDistinctStudents($institution_class_id);
        $Results = self::getDynamicTableInstance('Assessment.AssessmentItemResults');
        $exemptions = [];
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
        $exemptions = $Results::getLastExemptions($options);
        return $exemptions;
    }
    // POCOR-8618 end

    /**
     * POCOR-8618 refactured
     * @param array $marks
     * @param array $exempt
     * @return array
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private static function getMarksWithSubjectClassificationWeight(array $marks, array $exempts = [])
    {
//        $start_time = microtime(true);
        $exemptions = [];
        foreach ($exempts as $exempt){
            $exempt['exemption'] = 'EXEMPT';
            $exemptions[] = $exempt;
        }
        $new_marks = [];
        // POCOR-8330 START
        $uniqueCombinations = [];
        $marks_exempts = array_merge($marks, $exemptions);
        foreach ($marks_exempts as $mark) {
            $combination = [
                'assessment_id' => $mark['assessment_id'],
                'education_subject_id' => $mark['education_subject_id'],
            ];
            $assessment_ids = $mark['assessment_id'];
            $assessment_period_ids[] = $mark['assessment_period_id'];
            $education_subject_ids[] = $mark['education_subject_id'];
            $uniqueCombinations[] = $combination;
            $student_ids[] = $mark['student_id'];
        }
        $uniqueCombinations = array_unique($uniqueCombinations, SORT_REGULAR);
        $assessment_period_ids = array_unique($assessment_period_ids, SORT_REGULAR);
        $education_subject_ids = array_unique($education_subject_ids, SORT_REGULAR);
        $student_ids = array_unique($student_ids, SORT_REGULAR);

        $assessmentItems = [];
        $educationSubjects = [];
        $assessmentPeriods = [];


// Remove duplicate combinations
        foreach ($uniqueCombinations as $combination) {
            $where = [
                'education_subject_id' => $combination['education_subject_id'],
                'assessment_id' => $combination['assessment_id']
            ];
            $assessmentItems[$combination['assessment_id']][$combination['education_subject_id']] = self::getRecordByOptions('assessment_items', $where);
        }
        foreach ($assessment_period_ids as $assessment_period_id) {
            $assessmentPeriods[$assessment_period_id] = self::getRelatedRecord('assessment_periods', $assessment_period_id);
        }
        foreach ($education_subject_ids as $education_subject_id) {
            $educationSubjects[$education_subject_id] = self::getRelatedRecord('education_subjects', $education_subject_id);
        }
        $marks_absents = [];
        $sep = [];
        foreach ($marks_exempts as $mark) {
            $sep[$mark['student_id']]
            [$mark['education_subject_id']]
            [$mark['assessment_period_id']] = 1;
        }

        foreach ($assessmentItems as $assessment_id => $subjects) {
            foreach ($subjects as $education_subject_id => $assessment_item) {
                foreach ($assessmentPeriods as $assessment_period_id => $assessment_period) {
                    foreach ($student_ids as $student_id) {
                        if (!isset($sep[$student_id][$education_subject_id][$assessment_period_id])) {
                            //POCOR-9239 -- Commenting $missingMark code as it substituted 0 for missing values
                            // $missingMark = [
                            //     'student_id' => $student_id,
                            //     'assessment_id' => $assessment_id,
                            //     'education_subject_id' => $education_subject_id,
                            //     'assessment_period_id' => $assessment_period_id,
                            // ];
                            // $marks_exempts[] = $missingMark;
                            continue;
                        }
                    }
                }

            }
        }
        foreach ($marks_exempts as $mark) {
            $assessment_id = $mark['assessment_id'];
            $education_subject_id = $mark['education_subject_id'];
            $assessment_period_id = $mark['assessment_period_id'];

            // Use pre-fetched records
            $assessment_item = $assessmentItems[$assessment_id][$education_subject_id];
            $education_subject = $educationSubjects[$education_subject_id];
            $assessment_period = $assessmentPeriods[$assessment_period_id];
            $simple_mark = 0;
            if (isset($mark['marks'])) {
                $simple_mark = floatval($mark['marks']);
            }
            $weight = floatval($assessment_period['weight']);
            // POCOR-8330 END
            $weighted_mark = $simple_mark * $weight;
            $assessment_period_name = $assessment_period['name'];
            $academic_term = trim($assessment_period['academic_term']);
            if(!$academic_term){
                $academic_term = $assessment_period_name;
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
            $mark['assessment_period_name'] = $assessment_period_name;
            $new_marks[] = $mark;
        }
        return $new_marks;
    }

    /**
     * @param array $marksWithSubjectClassificationWeight
     * @return array
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private static function getMarksPerStudentPerTermArray(array $marksWithSubjectClassificationWeight)
    {
        $marksPerStudent = [];
        foreach ($marksWithSubjectClassificationWeight as $record) {
            $studentId = $record['student_id'];
            $academic_term = $record['academic_term'];
            $education_subject_id = $record['education_subject_id'];
            $assessment_period_id = $record['assessment_period_id'];
            $subjectClassification = Text::slug($record['subject_classification']);
            $key = $education_subject_id . '_' . $assessment_period_id;
            if(!isset($marksPerStudent[$studentId][$subjectClassification][$academic_term][$key])){
                if($record['exemption'] == 'EXEMPT'){
                    $marksPerStudent[$studentId][$subjectClassification][$academic_term][$key] = $record;
                }
            }
        }
        foreach ($marksWithSubjectClassificationWeight as $record) {
            $studentId = $record['student_id'];
            $academic_term = $record['academic_term'];
            $education_subject_id = $record['education_subject_id'];
            $assessment_period_id = $record['assessment_period_id'];
            $subjectClassification = Text::slug($record['subject_classification']);
            $key = $education_subject_id . '_' . $assessment_period_id;
            if(!isset($marksPerStudent[$studentId][$subjectClassification][$academic_term][$key])){
                $marksPerStudent[$studentId][$subjectClassification][$academic_term][$key] = $record;
            }
        }

        return $marksPerStudent;
    }

    /**
     * @param array $marksPerStudent
     * @param array $assessmentItemsTotalMarks
     * @return array
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private static function getTotalMarksPerStudent(array $marksPerStudent, array $assessmentItemsTotalMarks): array
    {
//        $start_time = microtime(true);
        $totalMarksPerStudent = [];
//        POCOR-8010:start
        $assessmentI = 0;
        if (!empty($marksPerStudent)) {
            foreach ($marksPerStudent as $student_id => $student_marks) {
                $subjectArr = [];
                foreach ($student_marks as $subject_classification => $subject_classification_marks) {
                    $totalMarksPerStudent[$assessmentI][$student_id][$subject_classification] = $subject_classification_marks;
                    $halfArr = [];
                    $terms = 0;
                    $weighted_marks_term_sum = 0;
                    $weighted_marks_term = 0;
                    foreach ($subject_classification_marks as $academic_term => $academic_term_marks) {
                        $terms = $terms + 1;
                        $total_weight = intval($assessmentItemsTotalMarks[$subject_classification][$academic_term]);
//                        Log::debug(print_r(["assessmentItemsTotalMarks[$subject_classification][$academic_term]" => $total_weight], true));
//                        $totalMarksPerStudent[$assessmentI][$student_id][$subject_classification][$academic_term] = $academic_term_marks;
                        $simple_marks_sum = 0;
                        $weighted_marks_sum = 0;
                        $weight_sum = 0;

                        foreach ($academic_term_marks as $markkey => $markval) {
                            $weighted_marks_term = 0;
                            $totalMarksPerStudent[$assessmentI] = $markval;
                            $totalMarksPerStudent[$assessmentI]['academic_term_value'] = $markval['assessment_period_name'];

                            if(!isset($markval['exemption'])) {

                                $totalMarksPerStudent[$assessmentI]['marks'] = $markval['simple_mark'];
                                $totalMarksPerStudent[$assessmentI]['academic_term_total_marks'] = $markval['simple_mark'];
                                $totalMarksPerStudent[$assessmentI]['academic_term_total_weighted_marks'] = $markval['weighted_mark'];
                                if (is_numeric($markval['simple_mark'])) {
                                    if(!is_numeric($simple_marks_sum)){
                                        $simple_marks_sum = 0;
                                    }
                                    $simple_marks_sum = $simple_marks_sum + $markval['simple_mark'];
                                }
                                if (is_numeric($markval['weighted_mark'])) {
                                    if(!is_numeric($weighted_marks_sum)){
                                        $weighted_marks_sum = 0;
                                    }
                                    $weighted_marks_sum = $weighted_marks_sum + $markval['weighted_mark'];
                                }
                                if (is_numeric($markval['weight'])) {
                                    if(!is_numeric($weight_sum)){
                                        $weight_sum = 0;
                                    }
                                    $weight_sum = $weight_sum + $markval['weight'];
                                }
                                if(isset($markval['absence'])) {

                                }
                            }else{
                                //POCOR-9042 starts
                                if($totalMarksPerStudent[$assessmentI]['type'] == 2){
                                    $totalMarksPerStudent[$assessmentI]['simple_mark'] = 'UNASSIGN';
                                    $totalMarksPerStudent[$assessmentI]['marks'] = 'UNASSIGN';
                                    $totalMarksPerStudent[$assessmentI]['academic_term_total_marks'] = 'UNASSIGN';
                                    $totalMarksPerStudent[$assessmentI]['academic_term_total_weighted_marks'] = 'UNASSIGN';
                                }else{//POCOR-9042 ends
                                    $totalMarksPerStudent[$assessmentI]['simple_mark'] = 'EXEMPT';
                                    $totalMarksPerStudent[$assessmentI]['marks'] = 'EXEMPT';
                                    $totalMarksPerStudent[$assessmentI]['academic_term_total_marks'] = 'EXEMPT';
                                    $totalMarksPerStudent[$assessmentI]['academic_term_total_weighted_marks'] = 'EXEMPT';
                                }                                
                            }
//                            $totalMarksPerStudent[$assessmentI][$markkey] = $markval;
                            $assessmentI++;
                        }
                        $totalMarksPerStudent[$assessmentI] = $markval;
                        $totalMarksPerStudent[$assessmentI]['academic_term_value'] = $academic_term;
                        $totalMarksPerStudent[$assessmentI]['marks'] = $simple_marks_sum;
                        $totalMarksPerStudent[$assessmentI]['academic_term_total_marks'] = $simple_marks_sum;
                        // Setting Total Weighted Mark;
                        if ($weight_sum > 0) {
                            $weighted_marks_term = ($weighted_marks_sum / ($weight_sum * 100)) * $total_weight;
                            $totalMarksPerStudent[$assessmentI]['academic_term_total_weighted_marks'] = $weighted_marks_term;
                        } else {
                            $totalMarksPerStudent[$assessmentI]['academic_term_total_weighted_marks'] = $simple_marks_sum;
                        }
                        $assessmentI++;
                        if(is_numeric($weighted_marks_term)) {
                            if(!is_numeric($weighted_marks_term_sum)){
                                $weighted_marks_term_sum = 0;
                            }
                            $weighted_marks_term_sum = $weighted_marks_term_sum + $weighted_marks_term;
                        }
                    }
                    // Setting Average;
                    $totalMarksPerStudent[$assessmentI] = $markval;
                    $totalMarksPerStudent[$assessmentI]['academic_term_value'] = __('Average');
                    if ($terms > 0) {
                        if (is_numeric($weighted_marks_term_sum)) {
                            $totalMarksPerStudent[$assessmentI]['academic_term_total_weighted_marks'] = $weighted_marks_term_sum / $terms;
                        } else {
                            $totalMarksPerStudent[$assessmentI]['academic_term_total_weighted_marks'] = 0;
                        }
                    } else {
                        $totalMarksPerStudent[$assessmentI]['academic_term_total_weighted_marks'] = "";
                    }

                    $assessmentI++;
                }
            }
        }

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
        $AssessmentPeriods = self::getDynamicTableInstance('Assessment.AssessmentPeriods'); // POCOR-8578
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
            ->enableHydration(false)
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
            $subjectClassification = Text::slug($record['subject_classification']);//Text::slug($record['subject_classification']);
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
     * @return array
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public function getGroupAssessmentItemResults(array $params)
    {
        $groupAssessmentItemsGradingTypes = $this->groupAssessmentItemsGradingTypes;
        if(empty($groupAssessmentItemsGradingTypes)){
            $groupAssessmentItemsGradingTypes = $this->getGroupAssessmentItemsGradingTypes($params);
        }
        $assessmentItemsTotalMarks = [];
        foreach ($groupAssessmentItemsGradingTypes as $groupAssessmentItemsGradingType){
            $subject_classification = Text::slug($groupAssessmentItemsGradingType['subject_classification']);
            $academic_term_value = $groupAssessmentItemsGradingType['academic_term_value'];
            $assessmentItemsTotalMarks[$subject_classification][$academic_term_value] = $groupAssessmentItemsGradingType['academic_term_total_weighted_max'];
        }
        $marks = self::getMarksForClass($params);
        $exempts = self::getExemptsForClass($params);
//        Log::debug(print_r($marks, true));
//        Log::debug(print_r($exempts, true));
        $marksWithSubjectClassificationWeight = self::getMarksWithSubjectClassificationWeight($marks, $exempts);

        $marksPerStudent = self::getMarksPerStudentPerTermArray($marksWithSubjectClassificationWeight);
        $totalMarksPerStudent = self::getTotalMarksPerStudent($marksPerStudent, $assessmentItemsTotalMarks);
        return $totalMarksPerStudent;
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
        $Table = self::getDynamicTableInstance($tableName); // POCOR-8578
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
        $Table = self::getDynamicTableInstance($tableName);
        try {
            $related = $Table->find()->where($where)->first();
            //POCOR-8483[START]
            // return $related->toArray();
            return $related;
            //POCOR-8483[END]
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
            $Assessments = self::getDynamicTableInstance('Assessment.Assessments'); // POCOR-8578
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
            $AssessmentItems = self::getDynamicTableInstance('Assessment.AssessmentItems'); // POCOR-8578
            $assessment_id = $params['assessment_id'];
            $results = $AssessmentItems->find()
                ->contain(['EducationSubjects'])
                ->where([$AssessmentItems->aliasField('assessment_id') => $assessment_id])
                ->order(['EducationSubjects.order', 'EducationSubjects.code', 'EducationSubjects.name'])
                ->enableHydration(false)
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
            $AssessmentItemsGradingTypes = self::getDynamicTableInstance('Assessment.AssessmentItemsGradingTypes'); // POCOR-8578
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
                ->enableHydration(false)
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
            $AssessmentPeriods = self::getDynamicTableInstance('Assessment.AssessmentPeriods'); // POCOR-8578
            $assessment_id = $params['assessment_id'];
            $results = $AssessmentPeriods->find()
                ->where([$AssessmentPeriods->aliasField('assessment_id') => $assessment_id])
                ->enableHydration(false)
                ->all();
            return $results->toArray();
        }
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


    /**
     * POCOR-8618
     * @param array $params
     * @return array|void
     */
    private function getGroupAssessmentItemsGradingTypes(array $params)
    {
        if (isset($params['assessment_id'])) {
//            $start_time = microtime(true);
            $AssessmentItemsGradingTypes = self::getDynamicTableInstance('Assessment.AssessmentItemsGradingTypes');
            $AssessmentGradingTypes = self::getDynamicTableInstance('Assessment.AssessmentGradingTypes');
            $AssessmentPeriods = self::getDynamicTableInstance('Assessment.AssessmentPeriods');
            $EducationSubjects = self::getDynamicTableInstance('Education.EducationSubjects');
            $AssessmentItems = self::getDynamicTableInstance('Assessment.AssessmentItems');

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
                    'academic_term_type' => '"period"',
                    'academic_term_total_weighted_max' => $query->func()->sum($AssessmentGradingTypes->aliasField('max * ') . $AssessmentPeriods->aliasField('weight'))
                ])
                ->contain([$AssessmentGradingTypes->getAlias(), $AssessmentPeriods->getAlias(), $EducationSubjects->getAlias()])
                ->leftJoin(
                    [$AssessmentItems->getAlias() => $AssessmentItems->getTable()],
                    [
                        $AssessmentItems->aliasField('assessment_id = ') . $AssessmentItemsGradingTypes->aliasField('assessment_id'),
                        $AssessmentItems->aliasField('education_subject_id = ') . $AssessmentItemsGradingTypes->aliasField('education_subject_id')
                    ]
                )
                ->where([$AssessmentItemsGradingTypes->aliasField('assessment_id') => $params['assessment_id']])
                ->group(['subject_classification', 'academic_term_value'])
                ->disableHydration()
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
                    'academic_term_type' => '"term"',
                    'academic_term_total_weighted_max' => $query->func()->sum($AssessmentGradingTypes->aliasField('max * ') . $AssessmentPeriods->aliasField('weight'))
                ])
                ->contain([$AssessmentGradingTypes->getAlias(), $AssessmentPeriods->getAlias(), $EducationSubjects->getAlias()])
                ->leftJoin(
                    [$AssessmentItems->getAlias() => $AssessmentItems->getTable()],
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
                ->disableHydration()
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
                    'academic_term_value' => __('Average'), // POCOR-8066
                    'academic_term_type' => 'average',
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

    /**
     * @param $params
     * @return array|void
     */
    private function getGroupAssessmentItems($params)
    {
        if (isset($params['assessment_id']) && isset($params['class_id'])) {
//            $start_time = microtime(true);
            $AssessmentItems = self::getDynamicTableInstance('Assessment.AssessmentItems');
            $EducationSubjects = self::getDynamicTableInstance('Education.EducationSubjects');
            $ClassSubjects = self::getDynamicTableInstance('Institution.InstitutionClassSubjects');
            $InstitutionSubjects = self::getDynamicTableInstance('Institution.InstitutionSubjects');

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
                ->contain([$EducationSubjects->getAlias()])
                ->innerJoin([$InstitutionSubjects->getAlias() => $InstitutionSubjects->getTable()], [
                    $InstitutionSubjects->aliasField('education_subject_id = ') . $AssessmentItems->aliasField('education_subject_id')
                ])
                ->innerJoin([$ClassSubjects->getAlias() => $ClassSubjects->getTable()], [
                    $InstitutionSubjects->aliasField('id = ') . $ClassSubjects->aliasField('institution_subject_id'),
                    $ClassSubjects->aliasField('institution_class_id') => $params['class_id']
                ])
                ->where([$AssessmentItems->aliasField('assessment_id') => $params['assessment_id']])
                ->order(['subject_order', 'subject_classification', $EducationSubjects->aliasField('code'), $EducationSubjects->aliasField('name')])
                ->group(['subject_classification'])
                ->disableHydration()
                ->all();
//            $functionName = __FUNCTION__;
//            $end_time = microtime(true);
//            $executionTimeMs = ($end_time - $start_time) * 1000;
//            $this->log("{$functionName}\n
//            Function execution time: {$executionTimeMs} ms", 'debug');

            return $results->toArray();
        }
    }

}
