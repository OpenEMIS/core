<?php
namespace Report\Model\Table;

use ArrayObject;
use ZipArchive;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Http\ServerRequest;
use App\Model\Table\AppTable;
use Cake\Utility\Hash;
use Cake\Datasource\ResultSetInterface;
use Cake\Log\Log;

class OutcomesResultTable extends AppTable
{
    public function initialize(array $config): void
    {

        $this->setTable('institution_classes');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Staff', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->hasMany('ClassesSecondaryStaff', ['className' => 'Institution.InstitutionClassesSecondaryStaff', 'saveStrategy' => 'replace', 'foreignKey' => 'institution_class_id']);
        $this->belongsTo('InstitutionShifts', ['className' => 'Institution.InstitutionShifts']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);

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

    public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets)
    {
        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;
        $institutionId = $requestData->institution_id;
        $areaId = $requestData->area_education_id;
        $educationGradeId = $requestData->education_grade_id;
        $selectedArea = $requestData->area_education_id;
        $outcomePeriod = $requestData->outcome_period;

        if (!is_null($academicPeriodId) && !is_null($institutionId) && !is_null($educationGradeId)) {
            $OutcomeCriteriasTable = TableRegistry::getTableLocator()->get('Outcome.OutcomeCriterias');
            $EducationSubjectsTable = TableRegistry::getTableLocator()->get('Education.EducationSubjects');

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
                ->where([
                    $OutcomeCriteriasTable->aliasField('academic_period_id') => $academicPeriodId,
                    $OutcomeCriteriasTable->aliasField('education_grade_id') => $educationGradeId,
                ])
                ->order($OutcomeCriteriasTable->aliasField('education_subject_id'))
                ->toArray();

            $settings['criteria_list_entities'] = $criteriaList;
            $settings['criteria_prefix'] = 'outcome_criteria_';
            $settings['institution_id'] = $institutionId;
            $settings['academic_period_id'] = $academicPeriodId;
            $settings['education_grade_id'] = $educationGradeId;

        } else {
            Log::write('error', 'Outcome excel: No outcome template id found.');
        }
    }

    public function onExcelBeforeQuerybkp(Event $event, ArrayObject $settings, $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;
        $institutionId = $requestData->institution_id;
        $areaId = $requestData->area_education_id;
        $educationGradeId = $requestData->education_grade_id;
        $selectedArea = $requestData->area_education_id;
        $outcomePeriod = $requestData->outcome_period;
        $conditions = [];
        if ($areaId != 1 && $areaId != '') {
            $areaIds = [];
            $allgetArea = $this->getChildren($selectedArea, $areaIds);
            $selectedArea1[]= $selectedArea;
            if(!empty($allgetArea)){
                $allselectedAreas = array_merge($selectedArea1, $allgetArea);
            }else{
                $allselectedAreas = $selectedArea1;
            }
            $conditions['Institutions.area_id IN'] = $allselectedAreas;
        }
        if ($institutionId > 0) {
            $conditions[$this->aliasField('institution_id')] = $institutionId;
        } else {
            $conditions = [];
        }

        
        $InstitutionClassStudentsTable = TableRegistry::getTableLocator()->get('Institution.InstitutionClassStudents');
        $UsersTable = TableRegistry::getTableLocator()->get('User.Users');
        $InstitutionOutcomeResultsTable = TableRegistry::getTableLocator()->get('Institution.InstitutionOutcomeResults');
        $OutcomeCriteriasTable = TableRegistry::getTableLocator()->get('Outcome.OutcomeCriterias');
        $OutcomeGradingOptionsTable = TableRegistry::getTableLocator()->get('Outcome.OutcomeGradingOptions');
        $OutcomePeriodsTable = TableRegistry::getTableLocator()->get('Outcome.OutcomePeriods');
        $outcomeCommentTable = TableRegistry::getTableLocator()->get('Institution.InstitutionOutcomeSubjectComments');

        // Class Student table - get all students in the class for the outcome
        
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
            ->where([
                $InstitutionClassStudentsTable->aliasField('institution_id IS') => $institutionId,
                $InstitutionClassStudentsTable->aliasField('academic_period_id IS') => $academicPeriodId,
                $InstitutionClassStudentsTable->aliasField('education_grade_id IS') => $educationGradeId,
            ])
            ->toArray();
          //  dd($studentList);

        $studentIdList = Hash::extract($studentList, '{n}.student_id');

        // Get all student outcome results for the students found in above query
        $studentOutcomeResultList = $InstitutionOutcomeResultsTable
            ->find()
            ->select([
                $InstitutionOutcomeResultsTable->aliasField('student_id'),
                $OutcomeCriteriasTable->aliasField('id'),
                $OutcomeGradingOptionsTable->aliasField('name'),
                $OutcomeGradingOptionsTable->aliasField('code'),
                $OutcomeCriteriasTable->aliasField('name'),
                $OutcomePeriodsTable->aliasField('id'),
                $OutcomePeriodsTable->aliasField('name')

            ])
            ->contain([
                $OutcomeCriteriasTable->getAlias(),
                $OutcomeGradingOptionsTable->getAlias(),
                $OutcomePeriodsTable->getAlias()
            ])
            ->where([
                $InstitutionOutcomeResultsTable->aliasField('student_id IN') => $studentIdList,
                $InstitutionOutcomeResultsTable->aliasField('institution_id IS') => $institutionId,
                $InstitutionOutcomeResultsTable->aliasField('academic_period_id IS') => $academicPeriodId,
                $InstitutionOutcomeResultsTable->aliasField('outcome_period_id IS') => $outcomePeriod
            ])
            ->toArray();

        // Massage data to the required format for formatResults()
        $outcomeResults = [];
        $prefix = $settings['criteria_prefix'];

        foreach ($studentOutcomeResultList as $entity) {
            $studentId = $entity->student_id;
            if (!array_key_exists($studentId, $outcomeResults)) {
                $outcomeResults[$studentId] = [];
            }

            $periodId = $entity->outcome_period->id;
            if (!array_key_exists($periodId, $outcomeResults[$studentId])) {
                $outcomeResults[$studentId][$periodId] = [];
            }

            $criteriaId = $entity->outcome_criteria->id;
            $criteriaFieldId = $prefix . $criteriaId;
            $gradingOptions = $entity->outcome_grading_option->name;
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

            foreach ($periodList as $outcomePeriodId) {
                $outcomePeriodId = $outcomePeriodId;
                if (!array_key_exists($outcomePeriodId, $allOutcomeResults)) {
                    $allOutcomeResults[$studentId][$outcomePeriodId] = [];
                }

                foreach ($criteriaList as $criteriaEntity) {
                    $criteriaId = $criteriaEntity->id;
                    $criteriaFieldId = $prefix . $criteriaId;
                    $extractField = $studentId . '.' . $outcomePeriodId . '.' . $criteriaFieldId;
                    $result = Hash::get($outcomeResults, $extractField);
                    if (!is_null($result)) {
                        $allOutcomeResults[$studentId][$outcomePeriodId][$criteriaFieldId] = $result;
                    } else {
                        $allOutcomeResults[$studentId][$outcomePeriodId][$criteriaFieldId] = '';
                    }
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
                'education_grade_name' => 'EducationGrades.name'
            ])
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
            ->innerJoin(['EducationGrades' => 'education_grades'],[
                'InstitutionClassStudents.education_grade_id = EducationGrades.id'
            ])
            ->leftJoin(['EducationGrades' => 'education_grades'],[
                'InstitutionClassStudents.education_grade_id = EducationGrades.id'
            ])
            ->where([
                'InstitutionClassStudents.education_grade_id' =>$educationGradeId,
                $this->aliasField('institution_id IS') => $institutionId,
                $this->aliasField('academic_period_id IS') => $academicPeriodId,
                'OR' => [['StudentStatuses.code' => 'CURRENT'], ['StudentStatuses.code' => 'PROMOTED'],
                            ['StudentStatuses.code' => 'TRANSFERRED'],['StudentStatuses.code' => 'GRADUATED']]
            ])
            ->formatResults(function(ResultSetInterface $results) use ($allOutcomeResults, $studentEntityList) {
                return $results->map(function ($row) use ($allOutcomeResults, $studentEntityList) {

                    $studentId = $row->student_id;
                    $outcomePeriodId = $row->outcome_period_id;
                    $outcomeResults = $allOutcomeResults[$studentId][$outcomePeriodId];
                    $studentName = $studentEntityList[$studentId]->name;

                    foreach ($outcomeResults as $field => $value) {
                        $row->{$field} = $value;
                    }

                    $row->student = $studentName;

                    return $row;
                });
            });
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, $query)
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
        if ($areaId != 1 && $areaId != '') {
            $areaIds = [];
            $allgetArea = $this->getChildren($selectedArea, $areaIds);
            $selectedArea1[]= $selectedArea;
            if(!empty($allgetArea)){
                $allselectedAreas = array_merge($selectedArea1, $allgetArea);
            }else{
                $allselectedAreas = $selectedArea1;
            }
            $conditions['Institutions.area_id IN'] = $allselectedAreas;
        }
        if ($institutionId > 0) {
            $conditions[$this->aliasField('institution_id')] = $institutionId;
        } else {
            $conditions = [];
        }
        if ($institutionId > 0) {
            $conditions[$this->aliasField('education_grade_id')] = $educationGradeId;
        } else {
            $conditions = [];
        }

        
        $InstitutionClassStudentsTable = TableRegistry::getTableLocator()->get('Institution.InstitutionClassStudents');
        $UsersTable = TableRegistry::getTableLocator()->get('User.Users');
        $InstitutionOutcomeResultsTable = TableRegistry::getTableLocator()->get('Institution.InstitutionOutcomeResults');
        $OutcomeCriteriasTable = TableRegistry::getTableLocator()->get('Outcome.OutcomeCriterias');
        $OutcomeGradingOptionsTable = TableRegistry::getTableLocator()->get('Outcome.OutcomeGradingOptions');
        $OutcomePeriodsTable = TableRegistry::getTableLocator()->get('Outcome.OutcomePeriods');
        $outcomeCommentTable = TableRegistry::getTableLocator()->get('Institution.InstitutionOutcomeSubjectComments');
        

        // Class Student table - get all students in the class for the outcome
        
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
            ->where([
                $InstitutionClassStudentsTable->aliasField('institution_id IS') => $institutionId,
                $InstitutionClassStudentsTable->aliasField('academic_period_id IS') => $academicPeriodId,
                $InstitutionClassStudentsTable->aliasField('education_grade_id IS') => $educationGradeId,
            ])
            ->toArray();
          //  dd($studentList);

        $studentIdList = Hash::extract($studentList, '{n}.student_id');

        // Get all student outcome results for the students found in above query
            $InstitutionSubjectStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjectStudents');
            $studentOutcomeResultList = $InstitutionOutcomeResultsTable
                ->find()
                ->select([
                    $InstitutionOutcomeResultsTable->aliasField('student_id'),
                    'outcome_criteria_id' => $OutcomeCriteriasTable->aliasField('id'),
                    'name' => $OutcomeGradingOptionsTable->aliasField('name'),
                    'code' => $OutcomeGradingOptionsTable->aliasField('code'),
                    'criteria_name' => $OutcomeCriteriasTable->aliasField('name'),
                    'outcome_period_id' => $OutcomePeriodsTable->aliasField('id'),
                    'outcome_period_name' => $OutcomePeriodsTable->aliasField('name'),
                    'final_result' => $InstitutionSubjectStudents->aliasField('outcome_result'),
                    'comments' => $outcomeCommentTable->aliasField('comments'),
                    'subject_id' => $InstitutionSubjectStudents->aliasField('education_subject_id')
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
                ->where([
                    $InstitutionOutcomeResultsTable->aliasField('student_id IN') => $studentIdList,
                    $InstitutionOutcomeResultsTable->aliasField('institution_id') => $institutionId,
                    $InstitutionOutcomeResultsTable->aliasField('academic_period_id') => $academicPeriodId,
                    $InstitutionOutcomeResultsTable->aliasField('outcome_period_id') => $outcomePeriod
                ])
                ->enableAutoFields(false)
                ->toArray();
//echo "<pre>"; print_r($studentOutcomeResultList); 
        // Massage data to the required format for formatResults()
        $outcomeResults = [];
        $prefix = $settings['criteria_prefix'];
        $finalResults = [];
        $commentResults = [];
        foreach ($studentOutcomeResultList as $entity) {
            $studentId = $entity->student_id;
            $result = $entity->final_result;
            $comments = $entity->comments;
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

            $periodId = $entity->outcome_period->id;
            if (!array_key_exists($periodId, $outcomeResults[$studentId])) {
                $outcomeResults[$studentId][$periodId] = [];
            }

            $criteriaId = $entity->outcome_criteria->id;
            $criteriaFieldId = $prefix . $criteriaId;
            $gradingOptions = $entity->outcome_grading_option->name ?? '';
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

            foreach ($periodList as $outcomePeriodId) {
                $outcomePeriodId = $outcomePeriodId;
                if (!array_key_exists($outcomePeriodId, $allOutcomeResults)) {
                    $allOutcomeResults[$studentId][$outcomePeriodId] = [];
                }

                foreach ($criteriaList as $criteriaEntity) {
                    echo "<pre>"; print_r($criteriaList); echo 'kjpk';
                    $criteriaId = $criteriaEntity->id;
                    $criteriaFieldId = $prefix . $criteriaId;
                    $extractField = $studentId . '.' . $outcomePeriodId . '.' . $criteriaFieldId;
                    $result = Hash::get($outcomeResults, $extractField);
                    if (!is_null($result)) {
                        $allOutcomeResults[$studentId][$outcomePeriodId][$criteriaFieldId] = $result;
                    } else {
                        $allOutcomeResults[$studentId][$outcomePeriodId][$criteriaFieldId] = '';
                    }
                }
            }
        }

echo "<pre>"; print_r($allOutcomeResults);
        $query
            ->select([
                'class' => $this->aliasField('name'),
                'student_id' => 'Students.id',
                'openemis_no' => 'Students.openemis_no',
                'outcome_period' => 'OutcomePeriods.name',
                'outcome_period_id' => 'OutcomePeriods.id',
                'institution_name' => 'Institutions.name',
                'institution_code' => 'Institutions.code',
                'education_grade_name' => 'EducationGrades.name'
            ])
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
            ->innerJoin(['EducationGrades' => 'education_grades'],[
                'InstitutionClassStudents.education_grade_id = EducationGrades.id'
            ])
            ->leftJoin(['EducationGrades' => 'education_grades'],[
                'InstitutionClassStudents.education_grade_id = EducationGrades.id'
            ])
            ->where([
                'InstitutionClassStudents.education_grade_id' =>$educationGradeId,
                $this->aliasField('institution_id IS') => $institutionId,
                $this->aliasField('academic_period_id IS') => $academicPeriodId,
                'OR' => [['StudentStatuses.code' => 'CURRENT'], ['StudentStatuses.code' => 'PROMOTED'],
                            ['StudentStatuses.code' => 'TRANSFERRED'],['StudentStatuses.code' => 'GRADUATED']]
            ])
            ->formatResults(function(ResultSetInterface $results) use ($allOutcomeResults, $studentEntityList, $finalResults, $commentResults) {
                return $results->map(function ($row) use ($allOutcomeResults, $studentEntityList, $finalResults, $commentResults) {

                    $studentId = $row->student_id;
                    $outcomePeriodId = $row->outcome_period_id;
                    $outcomeResults = $allOutcomeResults[$studentId][$outcomePeriodId];
                    $studentName = $studentEntityList[$studentId]->name;

                    /*foreach ($outcomeResults as $field => $value) {
                        $row->{$field} = $value;
                    }*/
                    // Add per-criteria outcome results
                     foreach ($outcomeResults[$studentId][$periodId] ?? [] as $field => $value) {
                        $row->{$field} = $value;
                    }

                    // Inject final_result per subject
                    foreach ($finalResults[$studentId] ?? [] as $subjectId => $result) {
                        $row->{'final_result' . $subjectId} = $result;
                    }

                    // Inject comment per subject
                    foreach ($commentResults[$studentId] ?? [] as $subjectId => $comment) {
                        $row->{'comment' . $subjectId} = $comment;
                    }
                    $row->student = $studentName;
                    //echo "<pre>"; print_r($row);
                    return $row;
                });
            });
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $criteriaList =  $settings['criteria_list_entities'];
        $prefix = $settings['criteria_prefix'];

        $newFields = [];

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
            'key' => 'EducationSubjects.name',
            'field' => 'education_grade_name',
            'type' => 'string',
            'label' => __('Grade')
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
        $Areas = TableRegistry::get('Area.Areas');
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
