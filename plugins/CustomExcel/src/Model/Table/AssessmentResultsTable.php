<?php
namespace CustomExcel\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Datasource\ResultSetInterface;
use Cake\Utility\Inflector;
use App\Model\Table\AppTable;

class AssessmentResultsTable extends AppTable
{
    private $groupAssessmentPeriodCount = 0;

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
                // 'AssessmentItems',
                // 'AssessmentItemsGradingTypes',
                // 'AssessmentPeriods',
                // 'AssessmentItemResults',
                'GroupAssessmentItems',
                'GroupAssessmentItemsGradingTypes',
                'GroupAssessmentPeriods',
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
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseAssessments'] = 'onExcelTemplateInitialiseAssessments';
        // $events['ExcelTemplates.Model.onExcelTemplateInitialiseAssessmentItems'] = 'onExcelTemplateInitialiseAssessmentItems';
        // $events['ExcelTemplates.Model.onExcelTemplateInitialiseAssessmentItemsGradingTypes'] = 'onExcelTemplateInitialiseAssessmentItemsGradingTypes';
        // $events['ExcelTemplates.Model.onExcelTemplateInitialiseAssessmentPeriods'] = 'onExcelTemplateInitialiseAssessmentPeriods';
        // $events['ExcelTemplates.Model.onExcelTemplateInitialiseAssessmentItemResults'] = 'onExcelTemplateInitialiseAssessmentItemResults';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseGroupAssessmentItems'] = 'onExcelTemplateInitialiseGroupAssessmentItems';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseGroupAssessmentItemsGradingTypes'] = 'onExcelTemplateInitialiseGroupAssessmentItemsGradingTypes';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseGroupAssessmentPeriods'] = 'onExcelTemplateInitialiseGroupAssessmentPeriods';
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
            $selectedColumns = [
                'subject_classification' => '(
                    CASE
                    WHEN '.$AssessmentItems->aliasField('classification <> \'\'').' THEN '.$AssessmentItems->aliasField('classification').'
                        ELSE '.$EducationSubjects->aliasField('name').'
                        END
                    )',
                'academic_term_value' => '(
                    CASE
                    WHEN '.$AssessmentPeriods->aliasField('academic_term <> \'\'').' THEN '.$AssessmentPeriods->aliasField('academic_term').'
                        ELSE '.$AssessmentPeriods->aliasField('name').'
                        END
                    )',
                'academic_term_total_weighted_max' => $query->func()->sum($AssessmentGradingTypes->aliasField('max * ') . $AssessmentPeriods->aliasField('weight'))
            ];

            $results = $AssessmentItemsGradingTypes->find()
                ->select($selectedColumns)
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

            $assessmentItemsGradingTypeResults = $results->toArray();
            $averageAssessmentItemsGradingTypes = [];
            foreach ($assessmentItemsGradingTypeResults as $key => $obj) {
                $subjectClassification = Inflector::slug($obj['subject_classification']);
                $academicTermTotalWeightedMax = $obj['academic_term_total_weighted_max'];

                if (array_key_exists($subjectClassification, $averageAssessmentItemsGradingTypes)) {
                    $averageAssessmentItemsGradingTypes[$subjectClassification]['group_academic_term_total_weighted_max'] += $academicTermTotalWeightedMax;
                    $averageAssessmentItemsGradingTypes[$subjectClassification]['count'] += 1;
                } else {
                    $averageAssessmentItemsGradingTypes[$subjectClassification] = [
                        'name' => $obj['subject_classification'],
                        'group_academic_term_total_weighted_max' => $academicTermTotalWeightedMax,
                        'count' => 1
                    ];
                }
            }

            foreach ($averageAssessmentItemsGradingTypes as $key => $obj) {
                $assessmentItemsGradingTypeResults[] = [
                    'subject_classification' => $obj['name'],
                    'academic_term_value' => __('Average'),
                    'academic_term_total_weighted_max' => $obj['group_academic_term_total_weighted_max'] / $obj['count']
                ];
            }

            return $assessmentItemsGradingTypeResults;
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
            $this->groupAssessmentPeriodCount = $results->count();

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

    public function onExcelTemplateInitialiseGroupAssessmentItemResults(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('class_id', $params) && array_key_exists('assessment_id', $params) && array_key_exists('institution_id', $params)) {
            $AssessmentItemResults = TableRegistry::get('Assessment.AssessmentItemResults');
            $AssessmentGradingOptions = TableRegistry::get('Assessment.AssessmentGradingOptions');
            $AssessmentPeriods = TableRegistry::get('Assessment.AssessmentPeriods');
            $EducationSubjects = TableRegistry::get('Education.EducationSubjects');
            $AssessmentItems = TableRegistry::get('Assessment.AssessmentItems');

            $query = $AssessmentItemResults->find();
            $selectedColumns = [
                $AssessmentItemResults->aliasField('institution_id'),
                $AssessmentItemResults->aliasField('academic_period_id'),
                $AssessmentItemResults->aliasField('assessment_id'),
                $AssessmentItemResults->aliasField('student_id'),
                'subject_classification' => '(
                    CASE
                    WHEN '.$AssessmentItems->aliasField('classification <> \'\'').' THEN '.$AssessmentItems->aliasField('classification').'
                        ELSE '.$EducationSubjects->aliasField('name').'
                        END
                    )',
                'academic_term_value' => '(
                    CASE
                    WHEN '.$AssessmentPeriods->aliasField('academic_term <> \'\'').' THEN '.$AssessmentPeriods->aliasField('academic_term').'
                        ELSE '.$AssessmentPeriods->aliasField('name').'
                        END
                    )',
                'academic_term_total_weighted_marks' => $query->func()->sum($AssessmentItemResults->aliasField('marks * ') . $AssessmentPeriods->aliasField('weight'))
            ];

            $results = $AssessmentItemResults->find()
                ->select($selectedColumns)
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
                    'subject_classification',
                    'academic_term_value'
                ])
                ->hydrate(false)
                ->all();

            $studentSubjectResults = $results->toArray();
            $averageStudentSubjectResults = [];
            foreach ($studentSubjectResults as $key => $obj) {
                $studentId = $obj['student_id'];
                $subjectClassification = Inflector::slug($obj['subject_classification']);
                $academicTermTotalWeightedMarks = $obj['academic_term_total_weighted_marks'];
                if (array_key_exists($studentId, $averageStudentSubjectResults) && array_key_exists($subjectClassification, $averageStudentSubjectResults[$studentId])) {
                    $averageStudentSubjectResults[$studentId][$subjectClassification]['group_academic_term_total_weighted_marks'] += $academicTermTotalWeightedMarks;
                    $averageStudentSubjectResults[$studentId][$subjectClassification]['count'] += 1;
                } else {
                    $averageStudentSubjectResults[$studentId][$subjectClassification] = [
                        'institution_id' => $obj['institution_id'],
                        'academic_period_id' => $obj['academic_period_id'],
                        'assessment_id' => $obj['assessment_id'],
                        'name' => $obj['subject_classification'],
                        'group_academic_term_total_weighted_marks' => $academicTermTotalWeightedMarks,
                        'count' => 1
                    ];
                }
            }

            foreach ($averageStudentSubjectResults as $studentKey => $studentObj) {
                foreach ($studentObj as $subjectKey => $subjectObj) {
                    $averageMark = $subjectObj['count'] == $this->groupAssessmentPeriodCount ? $subjectObj['group_academic_term_total_weighted_marks'] / $subjectObj['count'] : '';
                    $averageObj = [
                        'institution_id' => $subjectObj['institution_id'],
                        'academic_period_id' => $subjectObj['academic_period_id'],
                        'assessment_id' => $subjectObj['assessment_id'],
                        'student_id' => $studentKey,
                        'subject_classification' => $subjectObj['name'],
                        'academic_term_value' => __('Average'),
                        'academic_term_total_weighted_marks' => $averageMark
                    ];
                    $studentSubjectResults[] = $averageObj;
                }
            }

            return $studentSubjectResults;
        }
    }

    public function onExcelTemplateInitialiseClassStudents(Event $event, array $params, ArrayObject $extra)
    {
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
                ->where([$this->aliasField('institution_class_id') => $params['class_id']])
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
            $studentAbsenceResults = $InstitutionStudentAbsences->find()
                ->find('academicPeriod', ['academic_period_id' => $params['academic_period_id']])
                ->innerJoin(
                    [$this->alias() => $this->table()],
                    [
                        $this->aliasField('institution_id = ') . $InstitutionStudentAbsences->aliasField('institution_id'),
                        $this->aliasField('student_id = ') . $InstitutionStudentAbsences->aliasField('student_id'),
                        $this->aliasField('institution_class_id') => $params['class_id']
                    ]
                )
                ->where([
                    $InstitutionStudentAbsences->aliasField('institution_id') => $params['institution_id']
                ])
                ->formatResults(function (ResultSetInterface $results) {
                    return $results->map(function ($row) {
                        $startDate = $row['start_date'];
                        $endDate = $row['end_date'];
                        $interval = $endDate->diff($startDate);
                        // plus 1 day because if absence for the same day, interval diff return zero
                        $row['number_of_days'] = $interval->days + 1;

                        return $row;
                    });
                })
                ->hydrate(false)
                ->all();

            // sum all number_of_days a student absence in an academic period
            $results = [];
            foreach ($studentAbsenceResults as $key => $obj) {
                $studentId = $obj['student_id'];
                $institutionId = $obj['institution_id'];
                $numberOfDays = $obj['number_of_days'];
                if (isset($results[$studentId])) {
                    $results[$studentId]['number_of_days'] += $numberOfDays;
                } else {
                    $results[$studentId]['student_id'] = $studentId;
                    $results[$studentId]['institution_id'] = $institutionId;
                    $results[$studentId]['number_of_days'] = $numberOfDays;
                }
            }
            $results = array_values($results);

            return $results;
        }
    }
}
