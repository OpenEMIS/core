<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\EventInterface;
use App\Model\Table\AppTable;
use Cake\Utility\Hash;
use Cake\Datasource\ResultSetInterface;
use Cake\Log\Log;

//POCOR-9076
class OutcomesResultTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->setTable('institution_classes');
        parent::initialize($config);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' => 'academic_period_id']);
        $this->belongsTo('Staff', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->hasMany('ClassesSecondaryStaff', ['className' => 'Institution.InstitutionClassesSecondaryStaff', 'saveStrategy' => 'replace', 'foreignKey' => 'institution_class_id']);
        $this->belongsTo('InstitutionShifts', ['className' => 'Institution.InstitutionShifts']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);

        $this->hasMany('ClassGrades', ['className' => 'Institution.InstitutionClassGrades']);
        $this->hasMany('ClassStudents', ['className' => 'Institution.InstitutionClassStudents']);
        $this->hasMany('SubjectStudents', ['className' => 'Institution.InstitutionSubjectStudents']);

        $this->belongsToMany('EducationGrades', [
            'className' => 'Education.EducationGrades',
            'through' => 'Institution.InstitutionClassGrades',
            'foreignKey' => 'institution_class_id',
            'targetForeignKey' => 'education_grade_id'
        ]);
        $this->belongsToMany('Students', [
            'className' => 'User.Users',
            'through' => 'Institution.InstitutionClassStudents',
            'foreignKey' => 'institution_class_id',
            'targetForeignKey' => 'student_id'
        ]);
        $this->belongsToMany('InstitutionSubjects', [
            'className' => 'Institution.InstitutionSubjects',
            'through' => 'Institution.InstitutionClassSubjects',
            'foreignKey' => 'institution_class_id',
            'targetForeignKey' => 'institution_subject_id'
        ]);       
        $this->addBehavior('Excel', [
            'excludes' => ['is_student', 'photo_name', 'is_staff', 'is_guardian',  'super_admin', 'status'],
            'pages' => false,
            'autoFields' => false
        ]);
        $this->addBehavior('Report.ReportList');

    }

    public function onExcelBeforeStart(EventInterface $event, ArrayObject $settings, ArrayObject $sheets)
    {
        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;
        $educationGradeId = $requestData->education_grade_id;

        if (!is_null($academicPeriodId)) {
            $OutcomeCriteriasTable = TableRegistry::getTableLocator()->get('Outcome.OutcomeCriterias');
            $EducationSubjectsTable = TableRegistry::getTableLocator()->get('Education.EducationSubjects');
            $conditions = [];
            $conditions['OutcomeCriterias.academic_period_id'] = $academicPeriodId;
            if ($educationGradeId != 0) {
                $conditions['OutcomeCriterias.education_grade_id'] = $educationGradeId;

            }
            $criteriaList = $OutcomeCriteriasTable
                ->find()
                ->select([
                    'id' => $OutcomeCriteriasTable->aliasField('id'),
                    'education_subject_id' => $OutcomeCriteriasTable->aliasField('education_subject_id'),
                    'criteria_name' => $OutcomeCriteriasTable->aliasField('name'),
                    'education_subject_name' => $EducationSubjectsTable->aliasField('name')
                ])
                ->contain([
                    $EducationSubjectsTable->getAlias()
                ])
                ->where($conditions)
                ->order($OutcomeCriteriasTable->aliasField('education_subject_id'))
                ->toArray();

            $settings['criteria_list_entities'] = $criteriaList;
            $settings['criteria_prefix'] = 'outcome_criteria_';
            $settings['academic_period_id'] = $academicPeriodId;

        } else {
            Log::write('error', 'Outcome excel: No outcome template id found.');
        }
    }

    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;
        $institutionId = $requestData->institution_id;
        $areaId = $requestData->area_education_id;
        $educationGradeId = $requestData->education_grade_id;
        $selectedArea = $requestData->area_education_id;
        $outcomePeriod = $requestData->outcome_period;
        $criteriaList =  $settings['criteria_list_entities'];
        $conditions = [];
       if (!empty($areaId) && $areaId != 1) {
            $areaIds = [];
            $allgetArea = $this->getChildren($selectedArea, $areaIds);
            $allselectedAreas = array_merge([$selectedArea], $allgetArea ?? []);
            $allselectedAreas = array_unique(array_filter($allselectedAreas));
            if (!empty($allselectedAreas)) {
                $conditions['Institutions.area_id IN'] = $allselectedAreas;
            }
        }
        $conditions[$this->aliasField('academic_period_id')] = $academicPeriodId;
        if (!empty($institutionId) && $institutionId != 1) {
            $conditions[$this->aliasField('institution_id')] = $institutionId;
        }
        if (!empty($educationGradeId) && $educationGradeId != 0) {
            $conditions['InstitutionClassStudents.education_grade_id'] = $educationGradeId;
        }
        $InstitutionClassStudentsTable = TableRegistry::getTableLocator()->get('Institution.InstitutionClassStudents');
        $UsersTable = TableRegistry::getTableLocator()->get('User.Users');
        $InstitutionOutcomeResultsTable = TableRegistry::getTableLocator()->get('Institution.InstitutionOutcomeResults');
        $OutcomeCriteriasTable = TableRegistry::getTableLocator()->get('Outcome.OutcomeCriterias');
        $OutcomeGradingOptionsTable = TableRegistry::getTableLocator()->get('Outcome.OutcomeGradingOptions');
        $OutcomePeriodsTable = TableRegistry::getTableLocator()->get('Outcome.OutcomePeriods');
        $outcomeCommentTable = TableRegistry::getTableLocator()->get('Institution.InstitutionOutcomeSubjectComments');
        $where = [];
        $where[$InstitutionClassStudentsTable->aliasField('academic_period_id')] = $academicPeriodId;
        if (!empty($institutionId) && $institutionId != 1) {
            $where[$InstitutionClassStudentsTable->aliasField('institution_id')] = $institutionId;
        }
        if (!empty($educationGradeId) && $educationGradeId != 0) {
            $where[$InstitutionClassStudentsTable->aliasField('education_grade_id')] = $educationGradeId;
        }
        $studentList = $InstitutionClassStudentsTable
            ->find()
            ->select([
                $InstitutionClassStudentsTable->aliasField('student_id'),
                $UsersTable->aliasField('first_name'),
                $UsersTable->aliasField('middle_name'),
                $UsersTable->aliasField('third_name'),
                $UsersTable->aliasField('last_name'),
                $UsersTable->aliasField('preferred_name')
            ])
            ->contain($UsersTable->getAlias())
            ->where($where)
            ->toArray();

        $studentIdList = Hash::extract($studentList, '{n}.student_id');

        $InstitutionSubjectStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjectStudents');
        $whereClause = [];
        // Always filter by academic period and outcome period
        $whereClause[$InstitutionOutcomeResultsTable->aliasField('academic_period_id')] = $academicPeriodId;
        $whereClause[$InstitutionOutcomeResultsTable->aliasField('outcome_period_id')] = $outcomePeriod;
        // Filter by institution only if a specific one is selected
        if (!empty($institutionId) && $institutionId != 1) {
            $whereClause[$InstitutionOutcomeResultsTable->aliasField('institution_id')] = $institutionId;
        }
        // Filter by grade only if a specific one is selected
        if (!empty($educationGradeId) && $educationGradeId != 0) {
            $whereClause[$InstitutionOutcomeResultsTable->aliasField('education_grade_id')] = $educationGradeId;
        }
        if (empty($studentIdList)) {
            $studentOutcomeResultList = [];
        }else {
            $whereClause[$InstitutionOutcomeResultsTable->aliasField('student_id IN')] = $studentIdList;
            $studentOutcomeResultList = $InstitutionOutcomeResultsTable
                ->find()
                ->select([
                    $InstitutionOutcomeResultsTable->aliasField('student_id'),
                    'outcome_criteria_id' => $OutcomeCriteriasTable->aliasField('id'),
                    'criteria_name' => $OutcomeCriteriasTable->aliasField('name'),
                    'outcome_period_id' => $OutcomePeriodsTable->aliasField('id'),
                    'outcome_period_name' => $OutcomePeriodsTable->aliasField('name'),
                    'final_result' => $InstitutionSubjectStudents->aliasField('outcome_result'),
                    'comments' => $outcomeCommentTable->aliasField('comments'),
                    'subject_id' => $InstitutionSubjectStudents->aliasField('education_subject_id'),
                    'grading_option_name' => $OutcomeGradingOptionsTable->aliasField('name'),
                    'grading_option_code' => $OutcomeGradingOptionsTable->aliasField('code'),
                ])
                ->contain([
                    $OutcomeCriteriasTable->getAlias(),
                    $OutcomeGradingOptionsTable->getAlias(),
                    $OutcomePeriodsTable->getAlias()
                ])
                ->join([
                    'InstitutionSubjectStudents' => [
                        'table' => 'institution_subject_students',
                        'type' => 'LEFT',
                        'conditions' => [
                            'InstitutionSubjectStudents.student_id = ' . $InstitutionOutcomeResultsTable->aliasField('student_id'),
                            'InstitutionSubjectStudents.academic_period_id = ' . $InstitutionOutcomeResultsTable->aliasField('academic_period_id'),
                            'InstitutionSubjectStudents.institution_id = ' . $InstitutionOutcomeResultsTable->aliasField('institution_id')
                        ]
                    ]
                ])->join([
                    'InstitutionOutcomeSubjectComments' => [
                        'table' => 'institution_outcome_subject_comments',
                        'type' => 'LEFT',
                        'conditions' => [
                            'InstitutionOutcomeSubjectComments.student_id = ' . $InstitutionOutcomeResultsTable->aliasField('student_id'),
                            'InstitutionOutcomeSubjectComments.academic_period_id = ' . $InstitutionOutcomeResultsTable->aliasField('academic_period_id'),
                            'InstitutionOutcomeSubjectComments.institution_id = ' . $InstitutionOutcomeResultsTable->aliasField('institution_id')
                        ]
                    ]
                ])
                ->where($whereClause)
                ->enableAutoFields(false)
                ->toArray();
        }
        $outcomeResults = [];
        $prefix = $settings['criteria_prefix'];
        $finalResults = [];
        $commentResults = [];
        foreach ($studentOutcomeResultList as $entity) {
            $studentId = $entity->student_id;
            $result = $entity->final_result;
            $subjectId = $entity->subject_id;
           if (!empty($entity->final_result)) {
                $finalResults[$studentId][$subjectId] = $entity->final_result;
            }

            // Comment (per student > subject)
            if (!empty($entity->comments)) {
                $commentResults[$studentId][$subjectId] = $entity->comments;
            }

            if (!array_key_exists($studentId, $outcomeResults)) {
                $outcomeResults[$studentId] = [];
            }

            $periodId = $entity->outcome_period_id;
            if (!array_key_exists($periodId, $outcomeResults[$studentId])) {
                $outcomeResults[$studentId][$periodId] = [];
            }
            $criteriaId = $entity->outcome_criteria_id ?? null;

            if (!$criteriaId) {
                continue; // skip if no valid criteria ID
            }
            $criteriaFieldId = $prefix . $criteriaId;
            $gradingOptions = $entity->grading_option_name ?? '';
            $outcomeResults[$studentId][$periodId][$criteriaFieldId] = $gradingOptions;
        }
        $allOutcomeResults = [];
        $studentEntityList = [];

        foreach ($studentList as $studentEntity) {
            $studentId = $studentEntity->student_id;
            $studentEntityList[$studentId] = $studentEntity->user;
            if (!array_key_exists($studentId, $allOutcomeResults)) {
                $allOutcomeResults[$studentId] = [];
            }
            $outcomePeriodId = $outcomePeriod; 
            if (!array_key_exists($studentId, $allOutcomeResults)) {
                $allOutcomeResults[$studentId] = [];
            }
            foreach ($criteriaList as $criteriaEntity) {
                $criteriaId = $criteriaEntity->id;
                $criteriaFieldId = $prefix . $criteriaId;
                $extractField = $studentId . '.' . $outcomePeriodId . '.' . $criteriaFieldId;
                $result = Hash::get($outcomeResults, $extractField);
                if (!is_null($result)) {
                    $allOutcomeResults[$studentId][$outcomePeriodId][$criteriaFieldId] = $result;
                }
            }
        }
        $query
            ->select([
                'class' => $this->aliasField('name'),
                'student_id' => 'Students.id',
                'openemis_no' => 'Students.openemis_no',
                'outcome_period' => 'OutcomePeriods.name',
                'outcome_period_id' => 'OutcomePeriods.id',
                'institution_name' => 'Institutions.name',
                'institution_code' => 'Institutions.code',
                'education_grade_name' => 'EducationGrades.name',
                'academic_period_name' => 'AcademicPeriods.name',
                'education_subject_name' => 'EducationSubjects.name',
            ])
            ->contain(['Institutions']) 
            ->contain(['AcademicPeriods']) 
            ->innerJoin(['InstitutionClassStudents' => 'institution_class_students'], [
                $this->aliasField('id = ') . 'InstitutionClassStudents.institution_class_id'
            ])
            ->innerJoin(['Students' => 'security_users'], [
                'InstitutionClassStudents.student_id = Students.id'
            ])
            ->innerJoin(['OutcomePeriods' => 'outcome_periods'], [
                'OutcomePeriods.academic_period_id = ' . $academicPeriodId,
            ])
            ->innerJoin(['StudentStatuses' => 'student_statuses'],[
                'InstitutionClassStudents.student_status_id = StudentStatuses.id'
            ])
            ->innerJoin(['Institutions' => 'institutions'],[
                $this->aliasField('institution_id = ') . 'Institutions.id'
            ])
            ->leftJoin(['EducationGrades' => 'education_grades'],[
                'InstitutionClassStudents.education_grade_id = EducationGrades.id'
            ])
            ->innerJoin(['InstitutionSubjectStudents' => 'institution_subject_students'], [
                'InstitutionSubjectStudents.student_id = InstitutionClassStudents.student_id',
                'InstitutionSubjectStudents.institution_id = InstitutionClassStudents.institution_id',
                'InstitutionSubjectStudents.academic_period_id = InstitutionClassStudents.academic_period_id',
                'InstitutionSubjectStudents.institution_class_id = InstitutionClassStudents.institution_class_id',
            ])
            ->leftJoin(['InstitutionSubjects' => 'institution_subjects'], [
                'InstitutionSubjects.id = InstitutionSubjectStudents.institution_subject_id'
            ])
            ->leftJoin(['EducationSubjects' => 'education_subjects'], [
                'EducationSubjects.id = InstitutionSubjectStudents.education_subject_id'
            ])
            ->leftJoin(
                ['InstitutionOutcomeResults' => 'institution_outcome_results'],
                function ($exp, $q) use ($educationGradeId, $institutionId,  $academicPeriodId) {
                    $on = [
                        'InstitutionOutcomeResults.student_id = InstitutionClassStudents.student_id',
                        
                        'InstitutionOutcomeResults.institution_id = InstitutionClassStudents.institution_id',
                        'InstitutionOutcomeResults.academic_period_id = InstitutionClassStudents.academic_period_id',
                        'InstitutionOutcomeResults.education_grade_id  = InstitutionClassStudents.education_grade_id ',
                    ];

                    if (!empty($institutionId) && $institutionId != 1) {
                    $on[] = $exp->eq('InstitutionOutcomeResults.institution_id', $institutionId);
                    }
                    if (!empty($educationGradeId) && $educationGradeId != 0) {
                         $on[] = $exp->eq('InstitutionOutcomeResults.education_grade_id', $educationGradeId);
                    }
                    $on[] = $exp->eq('InstitutionOutcomeResults.academic_period_id', $academicPeriodId);
                    return $on;
                        return $on;
                }
            )
           ->where($conditions)
           ->group('InstitutionClassStudents.student_id')
            ->formatResults(function(ResultSetInterface $results) use ($allOutcomeResults, $studentEntityList, $finalResults, $commentResults) {
                return $results->map(function ($row) use ($allOutcomeResults, $studentEntityList, $finalResults, $commentResults) {
                    $studentId = $row->student_id;
                    $outcomePeriodId = $row->outcome_period_id;
                    $studentName = $studentEntityList[$studentId]->name;
                    foreach ($allOutcomeResults[$studentId][$outcomePeriodId] ?? [] as $field => $value) {
                        $row->{$field} = $value;  
                    }
                    // Add per-criteria outcome results
                    /*foreach ($outcomeResults[$studentId][$periodId] ?? [] as $field => $value) {
                        $row->{$field} = $value;
                    }*/
                    // Inject final_result per subject
                    foreach ($finalResults[$studentId] ?? [] as $subjectId => $result) {
                        $row->{'final_result' . $subjectId} = $result;
                    }
                    // Inject comment per subject
                    foreach ($commentResults[$studentId] ?? [] as $subjectId => $comment) {
                        $row->{'comment' . $subjectId} = $comment;
                    }
                    $row->student = $studentName;
                    return $row;
                });
            });
    }

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, $fields)
    {
        $criteriaList =  $settings['criteria_list_entities'];
        $prefix = $settings['criteria_prefix'];

        $newFields = [];

        $newFields[] = [
            'key' => 'AcademicPeriods.name',
            'field' => 'academic_period_name',
            'type' => 'string',
            'label' => __('Academic Periods')
        ];
        $newFields[] = [
            'key' => 'Institutions.name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institution')
        ];

       $newFields[] = [
            'key' => 'Institutions.institution_code',
            'field' => 'institution_code',
            'type' => 'string',
            'label' => __('Institution') . " " . __('Code')
        ];

        $newFields[] = [
            'key' => 'EducationGrades.name',
            'field' => 'education_grade_name',
            'type' => 'string',
            'label' => __('Grade')
        ];

        $newFields[] = [
            'key' => 'EducationSubject.name',
            'field' => 'education_subject_name',
            'type' => 'string',
            'label' => __('Education Subject')
        ];

        $newFields[] = [
            'key' => 'StudentOutcomes.class',
            'field' => 'class',
            'type' => 'string',
        ];

        $newFields[] = [
            'key' => 'Student.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('OpenEMIS ID')
        ];

        $newFields[] = [
            'key' => 'StudentOutcomes.student',
            'field' => 'student',
            'type' => 'string',
            'label' => __('Student Name')
        ];

        $newFields[] = [
            'key' => 'Outcome.outcome_period',
            'field' => 'outcome_period',
            'type' => 'string'
        ];

        $groupedSubjects = [];

        
        foreach ($criteriaList as $entity) {
            $subjectId = $entity->education_subject_id;
            $subjectName = $entity->education_subject_name;

            if (!isset($groupedSubjects[$subjectId])) {
                $groupedSubjects[$subjectId] = [
                    'name' => $subjectName,
                    'criteria' => []
                ];
            }

            $groupedSubjects[$subjectId]['criteria'][] = $entity;
        }

        foreach ($groupedSubjects as $subjectId => $subject) {
            foreach ($subject['criteria'] as $entity) {
                $newFields[] = [
                    'key' => $subject['name'] . 'OutcomeCriteria.id_' . $entity->id,
                    'field' => $prefix . $entity->id,
                    'type' => 'string',
                    'label' => $entity->criteria_name,
                    'group' => $subject['name']
                ];
            }
            // Add comment after all criteria for the subject
            $newFields[] = [
                'key' => $subject['name'] . '_comment',
                'field' => 'comment' . $subjectId,
                'type' => 'string',
                'label' => __('Comment'),
                'group' => $subject['name']
            ];
            // Add final result after comment
            $newFields[] = [
                'key' => $subject['name'] . '_final_result',
                'field' => 'final_result' . $subjectId,
                'type' => 'string',
                'label' => __('Final Result'),
                'group' => $subject['name']
            ];
        }

        $fields->exchangeArray($newFields);
    }

    public function getChildren($id, $idArray) {
        $Areas = TableRegistry::getTableLocator()->get('Area.Areas');
        $result = $Areas->find()
                            ->where([
                                $Areas->aliasField('parent_id') => $id
                            ]) 
                             ->toArray();
        foreach ($result as $key => $value) {
            $idArray[] = $value['id'];
           $idArray = $this->getChildren($value['id'], $idArray);
        }
        return $idArray;
    }
}
