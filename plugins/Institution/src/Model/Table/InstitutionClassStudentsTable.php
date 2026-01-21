<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\I18n\Time;
use Cake\Utility\Text;
use App\Model\Table\AppTable;
use Cake\Datasource\ResultSetInterface;
use Cake\Log\Log;
use Cake\ORM\Table;
use Cake\Utility\Inflector;

class InstitutionClassStudentsTable extends AppTable
{

    // For reports
    private $assessmentItemResults = [];
    private $lastQueriedClass = null;
    private $allowedSubjects = [];
    private $assessmentPeriodWeightedMark = 0;
    private $totalMark = 0;
    private $totalWeightedMark = 0;

    // Report permission
    private $allSubjectsPermission = true;
    private $mySubjectsPermission = true;
    private $staffId = 0;

    // POCOR-8224-EXEMPT
    private $institution_id;
    private $institution;
    private $institution_class_id;
    private $institution_class;
    private $assessment_id;
    private $assessment;
    private $academic_period_id;
    private $academic_period;
    private $education_grade_id;
    private $education_grade;

    private $results;
    private $i = 1;

    private $assessmentPeriodWeights = [];


    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id', 'joinType' => 'INNER']);
//        $this->belongsTo('Students', ['className' => 'API.Students', 'foreignKey' => 'student_id', 'joinType' => 'INNER']);
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
            'excludes' => ['id'],
            'pages' => ['index'],
            'orientation' => 'landscape'
        ]);

        $this->addBehavior('Restful.RestfulAccessControl', [
            'OpenEMIS_Classroom' => ['index', 'view'],
            'SubjectStudents' => ['index'],
            'ReportCardComments' => ['index'],
            'StudentCompetencies' => ['index'],
            'StudentOutcomes' => ['index'],
            'AssessmentItemStudentExemptions' => ['index', 'edit'], // POCOR-8224
        ]);
        $this->addBehavior('Configuration.CallWebhook', // POCOR-9403
            [
                'entity_create' => 'class_student_create',
                'entity_delete' => 'class_student_delete',
                'entity_update' => 'class_student_update',
                'table_alias' => 'Institution.InstitutionClassStudents',
                'contain' => []
            ]
        ); // for webhook
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['Model.Students.afterSave'] = 'studentsAfterSave';
        return $events;
    }

    public function studentsAfterSave(EventInterface $event, $student)
    {
        if ($student->isNew()) {
            if ($this->StudentStatuses->get($student->student_status_id)->code == 'CURRENT') {
                // to automatically add the student into a specific class when the student is successfully added to a school
                if ($student->has('class') && $student->class > 0) {
                    $classData = [];
                    $classData['student_id'] = $student->student_id;
                    $classData['education_grade_id'] = $student->education_grade_id;
                    $classData['institution_class_id'] = $student->class;
                    $classData['student_status_id'] = $student->student_status_id;
                    $classData['institution_id'] = $student->institution_id;
                    $classData['academic_period_id'] = $student->academic_period_id;

                    $this->autoInsertClassStudent($classData);
                } elseif ($student->has('next_institution_class_id') && $student->next_institution_class_id > 0) {
                    $classData = [];
                    $classData['student_id'] = $student->student_id;
                    $classData['education_grade_id'] = $student->education_grade_id;
                    $classData['institution_class_id'] = $student->next_institution_class_id;
                    $classData['student_status_id'] = $student->student_status_id;
                    $classData['institution_id'] = $student->institution_id;
                    $classData['academic_period_id'] = $student->academic_period_id;
                    $this->autoInsertClassStudent($classData);
                }
            }
        } else {
            // to update student status in class if student status in school has been changed
            $classStudent = $this->find()
                ->matching('InstitutionClasses')
                ->where([
                    'InstitutionClasses.institution_id' => $student->institution_id,
                    'InstitutionClasses.academic_period_id' => $student->academic_period_id,
                    $this->aliasField('education_grade_id') => $student->education_grade_id,
                    $this->aliasField('student_id') => $student->student_id,
                ])->first();
                // echo "<pre>"; print_r($student); die();

            if (!empty($classStudent) && $classStudent->student_status_id != $student->student_status_id) {
                if ($student->next_institution_class_id > 0) {
                    $classStudent->next_institution_class_id = $student->next_institution_class_id;
                }
                $classStudent->student_status_id = $student->student_status_id;
                $this->save($classStudent);
            }
            else{
                $results = $this->find()
                ->matching('InstitutionClasses')
                ->where([
                    'InstitutionClasses.academic_period_id' => $student->academic_period_id,
                    $this->aliasField('education_grade_id') => $student->education_grade_id,
                    $this->aliasField('student_id') => $student->student_id,
                ])->first();

                //POCOR-6500 starts
                if(!empty($results) && $student->student_status_id==4){ //POCOR-6958
                   $results->student_status_id = 4;
                   $this->save($results);
                }elseif(!empty($results)){
                   $results->student_status_id = 1;
                   $this->save($results);
                }
                //POCOR-6500 ends
            }
        }
    }

    public function onExcelBeforeGenerate(EventInterface $event, ArrayObject $settings)
    {
        $this->institution_class_id = $settings['class_id'];
        $this->institution_id = $settings['institution_id'];
        if (isset($settings['assessment_id']) && is_numeric($settings['assessment_id']) && $settings['assessment_id'] > 0) {
            $assessment_id = $settings['assessment_id'];
            $assessmentsTable = self::getDynamicTableInstance('assessments');
            $this->assessment_id = $assessment_id;
            $this->assessment = $assessmentsTable->get($assessment_id);

            $this->academic_period_id = $this->assessment->academic_period_id;
            $this->academic_period = $this->AcademicPeriods->get($this->academic_period_id);

            $this->education_grade_id = $this->assessment->education_grade_id;
            $this->education_grade = $this->EducationGrades->get($this->education_grade_id); // Fixed assignment
        }

        $this->institution = $this->Institutions->get($this->institution_id);
        $this->institution_class = $this->InstitutionClasses->get($this->institution_class_id);

        // Prepare file name for export
        $institution_code = $this->institution->code;
        $className = $this->institution_class->name;
        $settings['file'] = str_replace($this->getAlias(), str_replace(' ', '_', $institution_code) . '-' . str_replace(' ', '_', $className) . '_Results', $settings['file']);
    }

    public function onExcelBeforeStart(EventInterface $event, ArrayObject $settings, ArrayObject $sheets)
    {
        $classId = $settings['class_id'];
        $assessmentId = $settings['assessment_id'];

        $AccessControl = $settings['AccessControl'];
        $userId = $settings['user_id'];
        $institutionId = $settings['institution_id'];
        $roles = $this->Institutions->getInstitutionRoles($userId, $institutionId);

        $allSubjectsPermission = true;
        $mySubjectsPermission = true;
        if (!$AccessControl->isAdmin()) {
            if (!$AccessControl->check(['Institutions', 'AllSubjects', 'index'], $roles)) {
                $allSubjectsPermission = false;
                $mySubjectsPermission = $AccessControl->check(['Institutions', 'Subjects', 'index'], $roles);
            }
        }

        $allClassesPermission = true;
        $myClassesPermission = true;
        if (!$AccessControl->check(['Institutions', 'AllClasses', 'index'], $roles)) {
            $allClassesPermission = false;
            $myClassesPermission = $AccessControl->check(['Institutions', 'Classes', 'index'], $roles);
        }

        $InstitutionClassesTable = self::getDynamicTableInstance('Institution.InstitutionClasses');
        $name = $InstitutionClassesTable
            ->find()
            ->where([$InstitutionClassesTable->aliasField('id') => $classId])
            ->first();

        $sheets[] = [
            'name' => isset($name['name']) ? $name['name'] : __('Class Not Found'),
            'table' => $this,
            'query' => $this->find(),
            'assessmentId' => $assessmentId,
            'classId' => $classId,
            'staffId' => $userId,
            'institutionId' => $institutionId,
            'allSubjectsPermission' => $allSubjectsPermission,
            'mySubjectsPermission' => $mySubjectsPermission,
            'allClassesPermission' => $allClassesPermission,
            'myClassesPermission' => $myClassesPermission,
            'orientation' => 'landscape'
        ];
        $this->allSubjectsPermission = $allSubjectsPermission;
        $this->mySubjectsPermission = $mySubjectsPermission;
        $this->staffId = $userId;
        $SubjectStudents = $this->SubjectStudents;
        $options = [
            'institution_id' => $this->institution_id,
            'academic_period_id' => $this->academic_period_id,
            'institution_class_id' => $this->institution_class_id,
            'assessment_id' => $this->assessment_id,
            'education_grade_id' => $this->education_grade_id,
        ];
        $results = $SubjectStudents->find('StudentResults', $options)
            ->toArray();
        $student_results = [];
        $this->i = 1;
        foreach ($results as $result){
            $arresult = $result->toArray();
            $arresult['i'] = $this->i;
            $this->i = $this->i + 1;
//                    Log::debug($arresult);
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
//                Log::debug('arresult');
//                Log::debug($arresult);
            }
        }
        $this->results = $student_results;
        $this->assessmentItemResults = $student_results;

        $this->i = 1;
    }

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, ArrayObject $originalField)
    {
        $assessmentId = $this->assessment_id;
        $academicPeriodId = $this->academic_period_id;
        $institutionId = $this->institution_id;

        $AssessmentPeriodsTable = self::getDynamicTableInstance('Assessment.AssessmentPeriods');
        $AssessmentItemsGradingTypesTable = self::getDynamicTableInstance('Assessment.AssessmentItemsGradingTypes');


        $fields = new ArrayObject();
        $fields[] = [
            'key' => 'Users.openemis_no',
            'field' => 'openemis_number',
            'type' => 'string',
            'label' => '',
        ];

        // Start:POCOR-6854
        $fields[] = [
            'key' => 'Institutions.code',
            'field' => 'code',
            'type' => 'string',
            'label' => '',
        ];

        $fields[] = [
            'key' => 'Institutions.name',
            'field' => 'institution_id',
            'type' => 'string',
            'label' => '',
        ];
        // End:POCOR-6854

        $fields[] = [
            'key' => 'InstitutionClassStudents.student_id',
            'field' => 'student_id',
            'type' => 'string',
            'label' => '',
        ];

        $fields[] = [
            'key' => 'InstitutionClasses.class_name',
            'field' => 'class_name',
            'type' => 'string',
            'label' => __('Class'),
        ];

        $fields[] = [
            'key' => 'UserNationalities.nationality_id',
            'field' => 'nationality',
            'type' => 'nationality',
            'label' => '',
        ];

        $fields[] = [
            'key' => 'Users.birthplace_area_id',
            'field' => 'birth_place_area',
            'type' => 'string',
            'label' => '',
        ];

        $fields[] = [
            'key' => 'Users.date_of_birth',
            'field' => 'dob',
            'type' => 'date',
            'label' => '',
        ];

        $sheet = $settings['sheet'];

        $this->allSubjectsPermission = $sheet['allSubjectsPermission'];
        $this->mySubjectsPermission = $sheet['mySubjectsPermission'];
        $this->staffId = $sheet['staffId'];

        $assessmentPeriods = $AssessmentPeriodsTable
            ->find()
            ->where([$AssessmentPeriodsTable->aliasField('assessment_id') => $assessmentId])
            ->order([$AssessmentPeriodsTable->aliasField('academic_term'), $AssessmentPeriodsTable->aliasField('start_date')])
            ->toArray();

        $assessmentGradeTypes = $AssessmentItemsGradingTypesTable->getAssessmentGradeTypes($assessmentId);
        $assessmentSubjects = self::getDynamicTableInstance('Assessment.AssessmentItems')->getSubjects($assessmentId);
        foreach ($assessmentSubjects as $subject) {
            foreach ($assessmentPeriods as $period) {
                $subjectId = $subject['subject_id'];
                $assessmentPeriodId = $period->id;
                $resultType = $assessmentGradeTypes[$subjectId][$assessmentPeriodId];

                $label = __($subject['education_subject_name']).' - '.$period->name;
                if ($resultType == 'MARKS') {
                    $label = $label.' ('.$period->weight.') ';
                }
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

            $fields[] = [
                'key' => 'assessment_period_weighted_mark',
                'field' => 'assessment_item',
                'type' => 'assessment_period_weighted_mark',
                'label' => __('Weighted Marks').' ('.$subject['subject_weight'].') ',
                'subjectWeight' => $subject['subject_weight']
            ];
        }

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

        $originalField->exchangeArray($fields);
    }

    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, $query)
    {
//        $enrolledStatus = self::getDynamicTableInstance('Student.StudentStatuses')->getIdByCode('CURRENT');
        $sheet = $settings['sheet'];
        $institutionId = $sheet['institutionId'];
        $allClassesPermission = $sheet['allClassesPermission'];
        $allSubjectsPermission = $sheet['allSubjectsPermission'];
        $myClassesPermission = $sheet['myClassesPermission'];
        $mySubjectsPermission = $sheet['mySubjectsPermission'];
        $assessmentId = $sheet['assessmentId'];
        $staffId = $sheet['staffId'];
        $StudentStatuses = $this->StudentStatuses;
        // POCOR-6837 start
        $where = [];
        $getUrl = $settings['path'];
        $url = substr(strrchr(rtrim($getUrl, '/'), '/'), 1);
        $where[$this->aliasField('institution_id')] = $institutionId;
        if($url =='export'){
            $where[$StudentStatuses->aliasField('code NOT IN ')] = ['TRANSFERRED','WITHDRAWN'];
        }
       // POCOR-6837 end

        $query
            ->contain([
                'InstitutionClasses.Institutions',
                'Users.BirthplaceAreas',
                'Users.Nationalities.NationalitiesLookUp'
            ])
            ->innerJoin(['InstitutionClassGrades' => 'institution_class_grades'], [
                'InstitutionClassGrades.institution_class_id = '.$this->aliasField('institution_class_id')
            ])
            ->innerJoin(['Assessments' => 'assessments'], [
                'Assessments.education_grade_id = InstitutionClassGrades.education_grade_id',
                'Assessments.id' => $assessmentId
            ])
            ->leftJoin(['StudentStatuses' => 'student_statuses'], [
                'StudentStatuses.id = '.$this->aliasField('student_status_id')
            ])
            ->select(['code' => 'Institutions.code', 'institution_id' => 'Institutions.name', 'openemis_number' => 'Users.openemis_no', 'birth_place_area' => 'BirthplaceAreas.name', 'dob' => 'Users.date_of_birth', 'class_name' => 'InstitutionClasses.name'])
            ->where($where)
            ->order(['class_name']);

        if (isset($sheet['classId'])) {
            $query->where([$this->aliasField('institution_class_id') => $sheet['classId']]);
        }

        if (!$allClassesPermission && !$allSubjectsPermission) {
            if (!$myClassesPermission && !$mySubjectsPermission) {
                $query->where(['1 = 0']);
            } else {
                $query->innerJoin(['InstitutionClasses' => 'institution_classes'], [
                        'InstitutionClasses.id = '.$this->aliasField('institution_class_id'),
                    ]);

                if ($myClassesPermission && !$mySubjectsPermission) {
                    $query->where(['InstitutionClasses.staff_id' => $staffId]);
                } else {
                    $query
                        ->innerJoin(['InstitutionClassSubjects' => 'institution_class_subjects'], [
                            'InstitutionClassSubjects.institution_class_id = InstitutionClasses.id',
                            'InstitutionClassSubjects.status =   1'
                        ])
                        ->leftJoin(['InstitutionSubjectStaff' => 'institution_subject_staff'], [
                            'InstitutionSubjectStaff.institution_subject_id = InstitutionClassSubjects.institution_subject_id'
                        ]);

                    // If both class and subject permission is available
                    if ($myClassesPermission && $mySubjectsPermission) {
                        $query->where([
                            'OR' => [
                                ['InstitutionClasses.staff_id' => $staffId],
                                ['InstitutionSubjectStaff.staff_id' => $staffId]
                            ]
                        ]);
                    } // If only subject permission is available
                    else {
                        $query->where(['InstitutionSubjectStaff.staff_id' => $staffId]);
                    }
                    $query->group([$this->aliasField('student_id')]);//POCOR-7900
                }
            }
        }
    }

    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->isNew()) {
            $entity->id = Text::uuid();
        }
    }

    public function afterSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        if($entity->isNew() || $entity->getDirty('student_status_id')) {
            $institution_class_id = $entity->institution_class_id;
            $countMale = $this->getMaleCountByClass($institution_class_id);
            $countFemale = $this->getFemaleCountByClass($institution_class_id);
            $this->InstitutionClasses->updateAll(['total_male_students' => $countMale, 'total_female_students' => $countFemale], ['id' => $institution_class_id]);
        }

        $listeners = [
            self::getDynamicTableInstance('Institution.InstitutionSubjectStudents')
        ];
        $this->dispatchEventToModels('Model.InstitutionClassStudents.afterSave', [$entity], $this, $listeners);
    }

    public function onExcelRenderSubject(EventInterface $event, Entity $entity, array $attr)
    {
        $studentId = $entity->student_id;
        $classId = $entity->institution_class_id;
        $subjectId = $attr['subjectId'];
        $assessmentId = $attr['assessmentId'];
        $academicPeriodId = $attr['academicPeriodId'];
        $institutionId = $attr['institutionId'];
        $resultType = $attr['resultType'];
        $assessmentPeriodId = $attr['assessmentPeriodId'];

        if (!array_key_exists($studentId, $this->assessmentItemResults)) {
            $this->assessmentItemResults[$studentId] = [];
        }

        if (!array_key_exists($subjectId, $this->assessmentItemResults[$studentId])) {
            $AssessmentItemResultsTable = self::getDynamicTableInstance('Assessment.AssessmentItemResults');

            $studentResults = $AssessmentItemResultsTable->getAssessmentItemResults($academicPeriodId, $assessmentId, $subjectId, $studentId, $classId);
            if (isset($studentResults[$studentId][$subjectId])) {
                $this->assessmentItemResults[$studentId][$subjectId] = $studentResults[$studentId][$subjectId];
            }
        }
        $allSubjectsPermission = $this->allSubjectsPermission;
        $mySubjectsPermission = $this->mySubjectsPermission;
        $staffId = $this->staffId;
        $printedResult = '';
        $renderResult = true;
        if (!$allSubjectsPermission && !$mySubjectsPermission) {
            $printedResult = __('No Access');
            $renderResult = false;
        } elseif (!$allSubjectsPermission && $mySubjectsPermission) {
            $classId = $this->institution_class_id;

            if ($this->lastQueriedClass != $classId) {
                $AssessmentItemsTable = self::getDynamicTableInstance('Assessment.AssessmentItems');
                $allowedSubjects = $AssessmentItemsTable
                ->find('list', [
                    'keyField' => 'assessment_item_id',
                    'valueField' => 'subject_id'
                ])
                ->find('staffSubjects', ['class_id' => $classId, 'staff_id' => $staffId])
                ->select(['assessment_item_id' => $AssessmentItemsTable->aliasField('id'), 'subject_id' => $AssessmentItemsTable->aliasField('education_subject_id')])
                ->where([$AssessmentItemsTable->aliasField('assessment_id') => $assessmentId])
                ->enableHydration(false)
                ->toArray();
                $this->allowedSubjects = $allowedSubjects;
                $this->lastQueriedClass = $classId;
            }
            //POCOR-7900 comment this condition as per requirement
            /*if (!in_array($subjectId, $this->allowedSubjects)) {
                $printedResult = __('No Access');
                $renderResult = false;
            }*/
        }

        if ($renderResult) {
            if (isset($this->assessmentItemResults[$studentId][$subjectId][$assessmentPeriodId])) {
                $result = $this->assessmentItemResults[$studentId][$subjectId][$assessmentPeriodId];
                switch ($resultType) {
                    case 'MARKS':
                        // Add logic to add weighted mark to subjectWeightedMark
                        if ($result['mark'] != 'EXEMPT' && $result['mark'] != 'UNASSIGN') {//POCOR-9042 add 'UNASSIGN' condition
                            $this->assessmentPeriodWeightedMark += ((float)$result['marks'] * (float)$attr['assessmentPeriodWeight']);
                            $this->assessmentPeriodWeights[] = $attr['assessmentPeriodWeight'];
                        }
                        $printedResult = $result['mark'];
//                        $printedResult = print_r($result, true);//' '.$result['marks'];
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

    public function onExcelRenderNationality(EventInterface $event, Entity $entity, array $attr)
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
    }

    public function onExcelRenderAssessmentPeriodWeightedMark(EventInterface $event, Entity $entity, array $attr)
    {
//        $marksum = array_sum($this->assessmentMarks);
        $weightsum = array_sum($this->assessmentPeriodWeights);
        $assessmentPeriodWeightedMark = $this->assessmentPeriodWeightedMark;
//        Log::debug($assessmentPeriodWeightedMark);
        if ($weightsum > 0) {
            $assessmentPeriodWeightedMark = $assessmentPeriodWeightedMark / $weightsum;
        }
//        Log::debug($weightsum);
//        Log::debug($assessmentPeriodWeightedMark);
//        Log::debug($entity->user->name);
        $this->assessmentPeriodWeights = [];

//        $assessmentPeriodWeightedMark = $this->assessmentPeriodWeightedMark;
        if (is_numeric($assessmentPeriodWeightedMark)) {
            $this->totalMark += $assessmentPeriodWeightedMark;
            $this->totalWeightedMark += ($assessmentPeriodWeightedMark * $attr['subjectWeight']);
        }
        // reset the assessmentPeriodWeightedMark mark
        $this->assessmentPeriodWeightedMark = 0;
        if(is_numeric($assessmentPeriodWeightedMark)){
            $assessmentPeriodWeightedMark = number_format($assessmentPeriodWeightedMark, 2);
        }
        return ' '.$assessmentPeriodWeightedMark;
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

    public function getStudentCountByClass($classId)
    {
        $count = $this
            ->find()
            ->where([$this->aliasField('institution_class_id') => $classId])
            ->count()
        ;
        return $count;
    }

    public function getMaleCountByClass($classId)
    {
        $gender_id = 1; // male
        $count = $this
            ->find()
            ->contain('Users')
            ->matching('StudentStatuses', function ($q) {
                return $q->where(['StudentStatuses.code' => 'CURRENT']);
            })
            ->where([$this->Users->aliasField('gender_id') => $gender_id])
            ->where([$this->aliasField('institution_class_id') => $classId])
            ->count()
        ;
        return $count;
    }

    public function getFemaleCountByClass($classId)
    {
        $gender_id = 2; // female
        $count = $this
            ->find()
            ->contain('Users')
            ->matching('StudentStatuses', function ($q) {
                return $q->where(['StudentStatuses.code' => 'CURRENT']);
            })
            ->where([$this->Users->aliasField('gender_id') => $gender_id])
            ->where([$this->aliasField('institution_class_id') => $classId])
            ->count()
        ;
        return $count;
    }

    public function autoInsertClassStudent($data)
    {
        $studentId = $data['student_id'];
        $gradeId = $data['education_grade_id'];
        $classId = $data['institution_class_id'];
        $data['id'] = Text::uuid();
        $entity = $this->newEntity($data);

        $existingData = $this
            ->find()
            ->where(
                [
                    $this->aliasField('student_id') => $studentId,
                    $this->aliasField('education_grade_id') => $gradeId,
                    $this->aliasField('institution_class_id') => $classId
                ]
            )
            ->first()
        ;

        if (!empty($existingData)) {
            $entity->id = $existingData->id;
        }
        $this->save($entity);
    }

    public function afterDelete(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $institution_class_id = $entity->institution_class_id;
        $countMale = $this->getMaleCountByClass($institution_class_id);
        $countFemale = $this->getFemaleCountByClass($institution_class_id);
        $this->InstitutionClasses->updateAll(['total_male_students' => $countMale, 'total_female_students' => $countFemale], ['id' => $institution_class_id]);

        $listeners = [
            self::getDynamicTableInstance('Institution.InstitutionCompetencyResults'),
            self::getDynamicTableInstance('Institution.InstitutionSubjectStudents')
        ];
        $this->dispatchEventToModels('Model.InstitutionClassStudents.afterDelete', [$entity], $this, $listeners);
    }

    public function findUnassignedSubjectStudents(Query $query, array $options)
    {
        $institutionSubjectId = $options['institution_subject_id'];
        $educationGradeId = $options['education_grade_id'];
        $academicPeriodId = $options['academic_period_id'];
        // POCOR-4371 to encode the array of ids as comma separated values in restfulv2component is not support, will throw error
        // $institutionClassIds = $options['institution_class_ids'];
        $institutionClassIds = explode(',', $this->urlsafeB64Decode($options['institution_class_ids']));
        $institutionSubjects = self::getDynamicTableInstance('Institution.InstitutionSubjects');
        $education_subject_id=$institutionSubjects->find()->select(['education_subject_id'])->where(['id'=>$institutionSubjectId,'education_grade_id' =>$educationGradeId,'academic_period_id'=>$academicPeriodId])->first();
        $education_subject_id=$education_subject_id['education_subject_id'];
        return $query
            ->contain('InstitutionClasses')
            ->matching('Users', function ($q) {
                return $q->select(['Users.openemis_no',
                    'Users.first_name',
                    'Users.middle_name',
                    'Users.third_name',
                    'Users.last_name',
                    'Users.preferred_name']);
            })
            ->matching('Users.Genders')
            ->matching('StudentStatuses', function ($q) {
                        return $q->where(['StudentStatuses.code NOT IN' => ['TRANSFERRED', 'WITHDRAWN', 'GRADUATED', 'PROMOTED', 'REPEATED']]);
                    })
            ->leftJoinWith('SubjectStudents', function ($q) use ($education_subject_id, $academicPeriodId) {
                return $q
                    ->innerJoin(['EducationGradesSubjects' => 'education_grades_subjects'], [
                        'EducationGradesSubjects.education_grade_id = SubjectStudents.education_grade_id',
                        'EducationGradesSubjects.education_subject_id = SubjectStudents.education_subject_id'
                    ])
                    ->where([
                        'SubjectStudents.education_subject_id' => $education_subject_id,
                        'SubjectStudents.academic_period_id' => $academicPeriodId
                    ]);
            })
            ->where([
                $this->aliasField('institution_class_id').' IN ' => $institutionClassIds,
                //POCOR-7503 start
                'OR' => [
                    ['SubjectStudents.student_id IS NULL'],
                    ['SubjectStudents.student_status_id IN' => [3,4]]
                ]
                //POCOR-7503 end
                // //$this->aliasField('education_grade_id') => $educationGradeId,//POCOR-6463
                // //'SubjectStudents.education_subject_id' => $educationSubjectId['education_subject_id'],

            ])
            ->order(['Users.first_name', 'Users.last_name'])// POCOR-2547 sort list of staff and student by name
            ->formatResults(function ($results) {
                $resultArr = [];

              // echo "<pre>"; print_r($results); exit;
                foreach ($results as $result) {
                    $resultArr[] = [
                        'openemis_no' => $result->_matchingData['Users']->openemis_no,
                        'name' => $result->_matchingData['Users']->name,
                        'gender' => __($result->_matchingData['Genders']->name),
                        'gender_id' => $result->_matchingData['Genders']->id,
                        'student_status' => __($result->_matchingData['StudentStatuses']->name),
                        'student_id' => $result->student_id,
                        'institution_class_id' => $result->institution_class_id,
                        'education_grade_id' => $result->education_grade_id,
                        'academic_period_id' => $result->academic_period_id,
                        'institution_id' => $result->institution_id,
                        'student_status_id' => $result->student_status_id,
                        'institution_class' => $result->institution_class->name
                    ];
                }

                return $resultArr;
            });
    }

    public function findAbsencesByDate(Query $query, array $options)
    {
        $classId = $options['institution_class_id'];
        $absenceDate = $options['absence_date'];

        $Students = self::getDynamicTableInstance('Institution.Students');
        $StudentStatuses = self::getDynamicTableInstance('Student.StudentStatuses');
        $StudentAbsences = self::getDynamicTableInstance('Institution.InstitutionStudentAbsences');
        $AbsenceTypes = self::getDynamicTableInstance('Institution.AbsenceTypes');
        $StudentAbsenceReasons = self::getDynamicTableInstance('Institution.StudentAbsenceReasons');
        $currentStatus = $StudentStatuses->getIdByCode('CURRENT');

        $query
            ->select([
                $StudentAbsences->aliasField('id'),
                $StudentAbsences->aliasField('start_date'),
                $StudentAbsences->aliasField('end_date'),
                $StudentAbsences->aliasField('full_day'),
                $StudentAbsences->aliasField('start_time'),
                $StudentAbsences->aliasField('end_time'),
                $StudentAbsences->aliasField('comment'),
                $StudentAbsences->aliasField('absence_type_id'),
                $StudentAbsences->aliasField('student_absence_reason_id'),
                $AbsenceTypes->aliasField('code'),
                $AbsenceTypes->aliasField('name'),
                $StudentAbsenceReasons->aliasField('name'),
                $StudentAbsenceReasons->aliasField('international_code'),
                $StudentAbsenceReasons->aliasField('national_code')
            ])
            ->innerJoin(
                [$Students->getAlias() => $Students->getTable()],
                [
                    $Students->aliasField('institution_id = ') . $this->aliasField('institution_id'),
                    $Students->aliasField('student_id = ') . $this->aliasField('student_id'),
                    $Students->aliasField('academic_period_id = ') . $this->aliasField('academic_period_id'),
                    $Students->aliasField('student_status_id') => $currentStatus,
                    $Students->aliasField('start_date <=') => $absenceDate,
                    $Students->aliasField('end_date >=') => $absenceDate
                ]
            )
            ->leftJoin(
                [$StudentAbsences->getAlias() => $StudentAbsences->getTable()],
                [
                    $StudentAbsences->aliasField('institution_id = ') . $this->aliasField('institution_id'),
                    $StudentAbsences->aliasField('student_id = ') . $this->aliasField('student_id'),
                    $StudentAbsences->aliasField('start_date <=') => $absenceDate,
                    $StudentAbsences->aliasField('end_date >=') => $absenceDate
                ]
            )
            ->leftJoin(
                [$AbsenceTypes->getAlias() => $AbsenceTypes->getTable()],
                [
                    $AbsenceTypes->aliasField('id = ') . $StudentAbsences->aliasField('absence_type_id')
                ]
            )
            ->leftJoin(
                [$StudentAbsenceReasons->getAlias() => $StudentAbsenceReasons->getTable()],
                [
                    $StudentAbsenceReasons->aliasField('id = ') . $StudentAbsences->aliasField('student_absence_reason_id')
                ]
            )
            ->where([
                $this->aliasField('institution_class_id') => $classId
            ])
            ->autoFields(true);

        return $query;
    }

    public function getStudentsList($academicPeriodId, $institutionId, $classId)
    {
        $studentResults = $this->find()
            ->where([
                $this->aliasField('academic_period_id') => $academicPeriodId,
                $this->aliasField('institution_id') => $institutionId,
                $this->aliasField('institution_class_id') => $classId,
            ])
            ->all();

        $studentList = [];
        if (!$studentResults->isEmpty()) {
            foreach ($studentResults as $key => $obj) {
                $studentList[$obj->student_id] = $obj->student_id;
            }
        }

        return $studentList;
    }

    public function findReportCardComments(Query $query, array $options)
    {
        $academicPeriodId = $options['academic_period_id'];
        $institutionId = $options['institution_id'];
        $classId = $options['institution_class_id'];
        $educationGradeId = $options['education_grade_id'];
        $reportCardId = $options['report_card_id'];
        $educationSubjectId = $options['education_subject_id'];
        $institutionSubjectId = $options['institution_subject_id'];
        $type = $options['type'];
        $StudentReportCards = self::getDynamicTableInstance('Institution.InstitutionStudentsReportCards');
        $SubjectStudents = $this->SubjectStudents;
        $StudentStatuses = $this->StudentStatuses;
        $Users = $this->Users;

        $AssessmentItem = self::getDynamicTableInstance('Assessment.AssessmentItems');
        $AssessmentItemResults = self::getDynamicTableInstance('Assessment.AssessmentItemResults');
        $ReportCards = self::getDynamicTableInstance('ReportCard.ReportCards');
        $query
            ->select([
                $this->aliasField('student_id'),
                $Users->aliasField('openemis_no'),
                $Users->aliasField('first_name'),
                $Users->aliasField('middle_name'),
                $Users->aliasField('third_name'),
                $Users->aliasField('last_name'),
                $Users->aliasField('preferred_name'),
                $StudentStatuses->aliasField('name'),
                $StudentReportCards->aliasField('report_card_id')
            ])
            ->matching('Users')
            ->contain('StudentStatuses')
            ->leftJoin(
                [$StudentReportCards->getAlias() => $StudentReportCards->getTable()],
                [
                    $StudentReportCards->aliasField('student_id = ') . $this->aliasField('student_id'),
                    $StudentReportCards->aliasField('institution_id = ') . $this->aliasField('institution_id'),
                    $StudentReportCards->aliasField('academic_period_id = ') . $this->aliasField('academic_period_id'),
                    $StudentReportCards->aliasField('education_grade_id = ') . $this->aliasField('education_grade_id'),
                    $StudentReportCards->aliasField('institution_class_id = ') . $this->aliasField('institution_class_id'),
                    $StudentReportCards->aliasField('report_card_id') => $reportCardId
                ]
            )
            ->where([
                $this->aliasField('academic_period_id') => $academicPeriodId,
                $this->aliasField('institution_id') => $institutionId,
                $this->aliasField('institution_class_id') => $classId,
                $this->aliasField('education_grade_id') => $educationGradeId,
                $this->aliasField('student_status_id NOT IN') => [3],
            ])
            ->group([
                $this->aliasField('student_id')
            ])
            ->order([
                $Users->aliasField('first_name'), $Users->aliasField('last_name')
            ]);
        if (!empty($row['InstitutionStudentsReportCards']['report_card_id'])) {
            $reportCardId = $row['InstitutionStudentsReportCards']['report_card_id'];
        }

        // Get the report card start/end date
        $reportCardEntity = $ReportCards->find()
            ->select([
                $ReportCards->aliasField('start_date'),
                $ReportCards->aliasField('end_date'),
                $ReportCards->aliasField('overall_result')
            ])
            ->where([
                $ReportCards->aliasField('id') => $reportCardId
            ])
            ->all();
        // POCOR-9233 start
        $ReportCardSubjects = self::getDynamicTableInstance('ReportCard.ReportCardSubjects');
        $reportCardSubjectsEntity = $ReportCardSubjects->find()
            ->select([
                'education_subject_id'
            ])
            ->where([
                $ReportCardSubjects->aliasField('report_card_id') => $reportCardId
            ])
            ->disableHydration()
            ->all();
        //POCOR-6501 starts
        $Assessments = self::getDynamicTableInstance('Assessment.Assessments');
        $assessmentResults = $Assessments
            ->find()
            ->where([
                $Assessments->aliasField('academic_period_id') => $academicPeriodId,
                $Assessments->aliasField('education_grade_id') => $educationGradeId
            ])
            ->first();
        $assessment_id = 0;
        if(!empty($assessmentResults)){
            $assessment_id = $assessmentResults->id;
        }//POCOR-6501 ends
        if ($type == 'PRINCIPAL') {
            $query
                ->select(['comments' => $StudentReportCards->aliasfield('principal_comments')])
                ->formatResults(function (ResultSetInterface $results) use (
                    $academicPeriodId,
                    $institutionId,
                    $SubjectStudents,
                    $AssessmentItemResults,
                    $educationSubjectId,
                    $ReportCards,
                    $reportCardId,
                    $educationGradeId,
                    $classId,
                    $reportCardEntity,
                    $reportCardSubjectsEntity,
                    $assessment_id
                ) {//add $educationGradeId in params POCOR-6501 // POCOR-6750: added $classId to filter correct data

                    return $results->map(function ($row) use (
                        $academicPeriodId,
                        $institutionId,
                        $SubjectStudents,
                        $AssessmentItemResults,
                        $educationSubjectId,
                        $ReportCards,
                        $reportCardId,
                        $educationGradeId,
                        $classId,
                        $reportCardEntity,
                        $reportCardSubjectsEntity,
                        $assessment_id
                    ) {//add $educationGradeId in params POCOR-6501 // POCOR-6750: added $classId to filter correct data

                        $studentId = $row->student_id;
// POCOR-9233 end
                        if (!$reportCardEntity->isEmpty()) {
                            $row->reportCardStartDate = NULL;
                            $row->reportCardEndDate = NULL;
                            $row->overallResult = NULL;
                            $row->reportCardStartDate = $reportCardEntity->first()['start_date'];
                            $row->reportCardEndDate = $reportCardEntity->first()['end_date'];
                            $row->overallResult = $reportCardEntity->first()['overall_result'];
                        }
                        // To get the report card template subjects
// POCOR-9233 moved up
                        // Check if the student belongs to any subject
                        $subjectStudentsEntities = $SubjectStudents->find()
                            ->select([
                                $SubjectStudents->aliasField('student_id'),
                                $SubjectStudents->aliasField('education_subject_id')
                            ])
                            ->where([
                                $SubjectStudents->aliasField('student_id') => $studentId,
                                $SubjectStudents->aliasField('academic_period_id') => $academicPeriodId,
                                $SubjectStudents->aliasField('institution_id') => $institutionId,
                            ])
                            ->group([
                                'education_subject_id'
                            ])
                            ->disableHydration() // POCOR-9233
                            ->all();
// POCOR-9233 moved up

                        // If subjectStudentsEntities is not empty mean the student have a subject
                        if (!$subjectStudentsEntities->isEmpty()) {

                            $total_mark = 0;
                            $subjectTaken = 0;
                            foreach($subjectStudentsEntities->toArray() as $studentEntity) {
                                //POCOR-9201[START]
                                if ($row->overallResult == 0) {
                                    $conditions = [
                                        $AssessmentItemResults->aliasField('student_id') => $studentEntity['student_id'],
                                        $AssessmentItemResults->aliasField('education_subject_id') => $studentEntity['education_subject_id'],
                                        $AssessmentItemResults->aliasField('assessment_id') => $assessment_id, //POCOR-6501
                                        $AssessmentItemResults->aliasField('institution_classes_id') => $classId, // POCOR-6750
                                        /*POCOR-6443 starts - commented code was hiding overall marks*/
                                        $AssessmentItemResults->AssessmentPeriods->aliasField('start_date').' >= ' => $row->reportCardStartDate,
                                        $AssessmentItemResults->AssessmentPeriods->aliasField('end_date').' <= ' => $row->reportCardEndDate,
                                        /*POCOR-6443 ends*/
                                        $AssessmentItemResults->aliasField('marks IS NOT NULL')
                                    ];
                                }else{
                                    $conditions = [
                                        $AssessmentItemResults->aliasField('student_id') => $studentEntity['student_id'],
                                        $AssessmentItemResults->aliasField('education_subject_id') => $studentEntity['education_subject_id'],
                                        $AssessmentItemResults->aliasField('assessment_id') => $assessment_id, //POCOR-6501
                                        $AssessmentItemResults->aliasField('institution_classes_id') => $classId, // POCOR-6750
                                        /*POCOR-6443 starts - commented code was hiding overall marks*/
                                        // $AssessmentItemResults->AssessmentPeriods->aliasField('start_date').' >= ' => $row->reportCardStartDate,
                                        // $AssessmentItemResults->AssessmentPeriods->aliasField('end_date').' <= ' => $row->reportCardEndDate,
                                        /*POCOR-6443 ends*/
                                        $AssessmentItemResults->aliasField('marks IS NOT NULL')
                                    ];
                                }
                                //POCOR-9201[END]
                                // Getting all the subject marks based on report card start/end date
                                $AssessmentItemResultsQuery = $AssessmentItemResults->find();
                                $assessmentItemResultsEntities = $AssessmentItemResultsQuery
                                    ->select([
                                        $AssessmentItemResults->aliasField('student_id'),
                                        $AssessmentItemResults->aliasField('marks'),
                                        $AssessmentItemResults->aliasField('education_subject_id'),
                                        $AssessmentItemResults->aliasField('education_grade_id'),
                                        $AssessmentItemResults->aliasField('academic_period_id'),
                                        $AssessmentItemResults->aliasField('institution_id'),
                                        $AssessmentItemResults->aliasField('institution_classes_id'), // POCOR-6750
                                        'weightage' => $AssessmentItemResults->AssessmentPeriods->aliasField('weight')

                                    ])
                                    ->contain([
                                        'AssessmentPeriods'
                                    ])
                                    ->where($conditions)
                                    ->all();
                                    $studentSubArray = [];//POCOR-6501
                                    if (!$assessmentItemResultsEntities->isEmpty()) {
                                    foreach ($assessmentItemResultsEntities as $entity) {
                                        foreach ($reportCardSubjectsEntity as $reportCardSubjectEntity) {
                                            if($entity['education_subject_id'] === $reportCardSubjectEntity['education_subject_id']) {
                                                $total_mark += $entity->marks * $entity->weightage;
                                                // Plus one to the subject so that we can keep track how many subject does this student is taking within the report card template.
                                                //POCOR-6501 starts
                                                if((!in_array($entity['education_subject_id'], $studentSubArray))){
                                                    $studentSubArray [] = $entity['education_subject_id'];
                                                    $subjectTaken++;
                                                }//POCOR-6501 ends
                                            }
                                        }

                                    }
                                }
                            }
                        }
                        // echo "<pre>";print_r($studentSubArray);die;
                        $row->subjectTaken = NULL;
                        $row->total_mark = NULL;
                        $row->average_mark = NULL;


                        $row->subjectTaken = $subjectTaken;
                        $row->total_mark = $total_mark;

                        if ($subjectTaken == 0) {
                            $subjectTaken = 1;
                        }

                        $row->average_mark = number_format($total_mark / $subjectTaken, 2);
                        return $row;
                    });
                });
        } elseif ($type == 'HOMEROOM_TEACHER') {
            $query
                ->select(['comments' => $StudentReportCards->aliasfield('homeroom_teacher_comments')])
                ->formatResults(function (ResultSetInterface $results) use ( // POCOR-9233 start
                    $academicPeriodId,
                    $institutionId,
                    $SubjectStudents,
                    $AssessmentItemResults,
                    $educationSubjectId,
                    $ReportCards,
                    $reportCardId,
                    $educationGradeId,
                    $classId,
                    $reportCardEntity,
                    $reportCardSubjectsEntity,
                    $assessment_id
                ) {//add $educationGradeId in params POCOR-6501 // POCOR-6750: added $classId to filter correct data

                    return $results->map(function ($row) use (
                        $academicPeriodId,
                        $institutionId,
                        $SubjectStudents,
                        $AssessmentItemResults,
                        $educationSubjectId,
                        $ReportCards,
                        $reportCardId,
                        $educationGradeId,
                        $classId,
                        $reportCardEntity,
                        $reportCardSubjectsEntity,
                        $assessment_id
                    ) {//add $educationGradeId in params POCOR-6501 // POCOR-6750: added $classId to filter correct data // POCOR-9233 end

                        $studentId = $row->student_id;
// POCOR-9233 moved up
                        if (!$reportCardEntity->isEmpty()) {
                            $row->reportCardStartDate = NULL;
                            $row->reportCardEndDate = NULL;
                            $row->reportCardStartDate = $reportCardEntity->first()['start_date'];
                            $row->reportCardEndDate = $reportCardEntity->first()['end_date'];
                        }

// POCOR-9233 moved up
                        // Check if the student belongs to any subject
                        $subjectStudentsEntities = $SubjectStudents->find()
                            ->select([
                                $SubjectStudents->aliasField('student_id'),
                                $SubjectStudents->aliasField('education_subject_id')
                            ])
                            ->where([
                                $SubjectStudents->aliasField('student_id') => $studentId,
                                $SubjectStudents->aliasField('academic_period_id') => $academicPeriodId,
                                $SubjectStudents->aliasField('institution_id') => $institutionId,
                            ])
                            ->group([
                                'education_subject_id'
                            ])
                            ->enableHydration(false)
                            ->all();
// POCOR-9233 moved up
                        // If subjectStudentsEntities is not empty mean the student have a subject
                        if (!$subjectStudentsEntities->isEmpty()) {

                            $total_mark = 0;
                            $subjectTaken = 0;

                            foreach($subjectStudentsEntities->toArray() as $studentEntity) {
                                // Getting all the subject marks based on report card start/end date
                                //POCOR-9201[START]
                                if ($row->overallResult == 0) {
                                    $conditions = [
                                        $AssessmentItemResults->aliasField('student_id') => $studentEntity['student_id'],
                                        $AssessmentItemResults->aliasField('education_subject_id') => $studentEntity['education_subject_id'],
                                        $AssessmentItemResults->aliasField('assessment_id') => $assessment_id, //POCOR-6501
                                        $AssessmentItemResults->aliasField('institution_classes_id') => $classId, // POCOR-6750
                                        /*POCOR-6443 starts - commented code was hiding overall marks*/
                                        $AssessmentItemResults->AssessmentPeriods->aliasField('start_date').' >= ' => $row->reportCardStartDate,
                                        $AssessmentItemResults->AssessmentPeriods->aliasField('end_date').' <= ' => $row->reportCardEndDate,
                                        /*POCOR-6443 ends */
                                        $AssessmentItemResults->aliasField('marks IS NOT NULL')
                                    ];
                                }else{
                                    $conditions = [
                                        $AssessmentItemResults->aliasField('student_id') => $studentEntity['student_id'],
                                        $AssessmentItemResults->aliasField('education_subject_id') => $studentEntity['education_subject_id'],
                                        $AssessmentItemResults->aliasField('assessment_id') => $assessment_id, //POCOR-6501
                                        $AssessmentItemResults->aliasField('institution_classes_id') => $classId, // POCOR-6750
                                        /*POCOR-6443 starts - commented code was hiding overall marks*/
                                        //$AssessmentItemResults->AssessmentPeriods->aliasField('start_date').' >= ' => $row->reportCardStartDate,
                                        //$AssessmentItemResults->AssessmentPeriods->aliasField('end_date').' <= ' => $row->reportCardEndDate,
                                        /*POCOR-6443 ends */
                                        $AssessmentItemResults->aliasField('marks IS NOT NULL')
                                    ];
                                }
                                //POCOR-9201[END]
                                $AssessmentItemResultsQuery = $AssessmentItemResults->find();
                                $assessmentItemResultsEntities = $AssessmentItemResultsQuery
                                    ->select([
                                        $AssessmentItemResults->aliasField('student_id'),
                                        $AssessmentItemResults->aliasField('marks'),
                                        $AssessmentItemResults->aliasField('education_subject_id'),
                                        $AssessmentItemResults->aliasField('education_grade_id'),
                                        $AssessmentItemResults->aliasField('academic_period_id'),
                                        $AssessmentItemResults->aliasField('institution_id'),
                                        $AssessmentItemResults->aliasField('institution_classes_id'), // POCOR-6750
                                        'weightage' => $AssessmentItemResults->AssessmentPeriods->aliasField('weight')
                                    ])
                                    ->contain([
                                        'AssessmentPeriods'
                                    ])
                                    ->where($conditions)
                                    ->all();
                                $studentSubArray = [];//POCOR-6501
                                if (!$assessmentItemResultsEntities->isEmpty()) {
                                    foreach ($assessmentItemResultsEntities as $entity) {
                                        foreach ($reportCardSubjectsEntity as $reportCardSubjectEntity) {
                                            if($entity['education_subject_id'] === $reportCardSubjectEntity['education_subject_id']) {
                                                $total_mark += $entity->marks * $entity->weightage;
                                                // Plus one to the subject so that we can keep track how many subject does this student is taking within the report card template.
                                                //POCOR-6501 starts
                                                if((!in_array($entity['education_subject_id'], $studentSubArray))){
                                                    $studentSubArray [] = $entity['education_subject_id'];
                                                    $subjectTaken++;
                                                }//POCOR-6501 ends
                                            }
                                        }

                                    }
                                }
                            }
                        }

                        $row->subjectTaken = NULL;
                        $row->total_mark = NULL;
                        $row->average_mark = NULL;

                        $row->subjectTaken = $subjectTaken;
                        $row->total_mark = $total_mark;

                        if ($subjectTaken == 0) {
                            $subjectTaken = 1;
                        }

                        $row->average_mark = number_format($total_mark / $subjectTaken, 2);
                        return $row;
                    });
                });

        } elseif ($type == 'TEACHER') {
            $ReportCardsComments = self::getDynamicTableInstance('Institution.InstitutionStudentsReportCardsComments');
            $Staff = $ReportCardsComments->Staff;
            $query
                ->select([
                    'comments' => $ReportCardsComments->aliasField('comments'),
                    'comment_code' => $ReportCardsComments->aliasField('report_card_comment_code_id'),
                    'total_mark' => $SubjectStudents->aliasField('total_mark'),
                    $Staff->aliasField('first_name'),
                    $Staff->aliasField('last_name')
                ])
                ->matching('SubjectStudents')
                ->leftJoin([$ReportCardsComments->getAlias() => $ReportCardsComments->getTable()], [
                    $ReportCardsComments->aliasField('report_card_id = ') . $StudentReportCards->aliasField('report_card_id'),
                    $ReportCardsComments->aliasField('student_id = ') . $StudentReportCards->aliasField('student_id'),
                    $ReportCardsComments->aliasField('institution_id = ') . $StudentReportCards->aliasField('institution_id'),
                    $ReportCardsComments->aliasField('academic_period_id = ') . $StudentReportCards->aliasField('academic_period_id'),
                    $ReportCardsComments->aliasField('education_grade_id = ') . $StudentReportCards->aliasField('education_grade_id'),
                    $ReportCardsComments->aliasField('education_subject_id') => $educationSubjectId
                ])
                ->leftJoin([$Staff->getAlias() => $Staff->getTable()], [
                    $Staff->aliasField('id = ') . $ReportCardsComments->aliasField('staff_id')
                ])
                ->where([$SubjectStudents->aliasField('institution_subject_id') => $institutionSubjectId])
                ->formatResults(function (ResultSetInterface $results) use ( // POCOR-9233 start
                    $academicPeriodId,
                    $institutionId,
                    $SubjectStudents,
                    $AssessmentItemResults,
                    $educationSubjectId,
                    $ReportCards,
                    $reportCardId,
                    $institutionSubjectId,
                    $educationGradeId,
                    $classId,
                    $reportCardEntity,
                    $reportCardSubjectsEntity,
                    $assessment_id
                ) {//add $educationGradeId in params POCOR-6501 // POCOR-6750: added $classId to filter correct data
                    return $results->map(function ($row) use (
                        $academicPeriodId,
                        $institutionId,
                        $SubjectStudents,
                        $AssessmentItemResults,
                        $educationSubjectId,
                        $ReportCards,
                        $reportCardId,
                        $institutionSubjectId,
                        $educationGradeId,
                        $classId,
                        $reportCardEntity,
                        $reportCardSubjectsEntity,
                        $assessment_id
                    ) {//add $educationGradeId in params POCOR-6501 // POCOR-6750: added $classId to filter correct data // POCOR-9233 end

                        $studentId = $row->student_id;
// POCOR-9233 moved up
                        if (!$reportCardEntity->isEmpty()) {
                            $row->reportCardStartDate = NULL;
                            $row->reportCardEndDate = NULL;
                            $row->reportCardStartDate = $reportCardEntity->first()['start_date'];
                            $row->reportCardEndDate = $reportCardEntity->first()['end_date'];
                        }

                        // Check if the student belongs to any subject
                        $subjectStudentsEntities = $SubjectStudents->find()
                            ->select([
                                $SubjectStudents->aliasField('student_id'),
                                $SubjectStudents->aliasField('institution_subject_id'),
                                $SubjectStudents->aliasField('education_subject_id')
                            ])
                            ->where([
                                $SubjectStudents->aliasField('student_id') => $studentId,
                                $SubjectStudents->aliasField('academic_period_id') => $academicPeriodId,
                                $SubjectStudents->aliasField('institution_id') => $institutionId,
                                $SubjectStudents->aliasField('institution_subject_id') => $institutionSubjectId
                            ])
                            ->group([
                                'institution_subject_id'
                            ])
                            ->enableHydration(false)
                            ->all();

                        // If subjectStudentsEntities is not empty mean the student have a subject
                        if (!$subjectStudentsEntities->isEmpty()) {

                            $studentEntity = $subjectStudentsEntities->first();
// POCOR-9233 moved up
                            // Getting all the subject marks based on report card start/end date
                            //POCOR-9201[START]
                            if ($row->overallResult == 0) {
                                $conditions = [
                                    $AssessmentItemResults->aliasField('student_id') => $studentEntity['student_id'],
                                    $AssessmentItemResults->aliasField('education_subject_id') => $studentEntity['education_subject_id'],
                                    $AssessmentItemResults->aliasField('assessment_id') => $assessment_id, //POCOR-6501
                                    $AssessmentItemResults->aliasField('institution_classes_id') => $classId, // POCOR-6750
                                    /*POCOR-6443 starts - commented code was hiding overall marks*/
                                    $AssessmentItemResults->AssessmentPeriods->aliasField('start_date').' >= ' => $row->reportCardStartDate,
                                    $AssessmentItemResults->AssessmentPeriods->aliasField('end_date').' <= ' => $row->reportCardEndDate,
                                    /*POCOR-6443 ends*/
                                    $AssessmentItemResults->aliasField('marks IS NOT NULL'),
                                    $AssessmentItemResults->aliasField('education_subject_id') => $studentEntity['education_subject_id']
                                ];
                            }else{
                                $conditions = [
                                    $AssessmentItemResults->aliasField('student_id') => $studentEntity['student_id'],
                                    $AssessmentItemResults->aliasField('education_subject_id') => $studentEntity['education_subject_id'],
                                    $AssessmentItemResults->aliasField('assessment_id') => $assessment_id, //POCOR-6501
                                    $AssessmentItemResults->aliasField('institution_classes_id') => $classId, // POCOR-6750
                                    /*POCOR-6443 starts - commented code was hiding overall marks*/
                                    //$AssessmentItemResults->AssessmentPeriods->aliasField('start_date').' >= ' => $row->reportCardStartDate,
                                    //$AssessmentItemResults->AssessmentPeriods->aliasField('end_date').' <= ' => $row->reportCardEndDate,
                                    /*POCOR-6443 ends*/
                                    $AssessmentItemResults->aliasField('marks IS NOT NULL'),
                                    $AssessmentItemResults->aliasField('education_subject_id') => $studentEntity['education_subject_id']
                                ];
                            }
                            //POCOR-9201[END]
                            $AssessmentItemResultsQuery = $AssessmentItemResults->find();

                            $assessmentItemResultsEntities = $AssessmentItemResultsQuery
                                ->select([
                                    $AssessmentItemResults->aliasField('student_id'),
                                    $AssessmentItemResults->aliasField('marks'),
                                    $AssessmentItemResults->aliasField('education_subject_id'),
                                    $AssessmentItemResults->aliasField('education_grade_id'),
                                    $AssessmentItemResults->aliasField('academic_period_id'),
                                    $AssessmentItemResults->aliasField('institution_id'),
                                    $AssessmentItemResults->aliasField('institution_classes_id'), // POCOR-6750
                                    'weightage' => $AssessmentItemResults->AssessmentPeriods->aliasField('weight')

                                ])
                                ->contain([
                                    'AssessmentPeriods'
                                ])
                                ->where($conditions)
                                ->all();

                            $total_mark = 0;
                            if (!$assessmentItemResultsEntities->isEmpty()) {
                                foreach ($assessmentItemResultsEntities as $entity) {
                                    $total_mark += $entity->marks * $entity->weightage;
                                }

                                $row->total_mark = $total_mark;
                            }else {
                                $row->total_mark = '';

                            }
                        }

                        return $row;
                    });
                });
        }
        return $query;
    }

    //POCOR-9195 -- Updated function to include logic to display only selected subjects and students with no marks
    // POCOR-9289 stashed
    public function findExemptStudentsNoMarks(Query $query, array $options): Query
    {

        // Extract the parameters from the options array
        $assessment_item_id = preg_replace("/[^a-fA-F0-9\-]/", "", $options['assessment_item_id']);  // Still using assessment_item_id for reference
        $assessment_period_id = intval($options['assessment_period_id']);
        $institution_class_id = intval($options['institution_class_id']);

        // Prepare period IDs & names
        $assessment_period_names_string = null;
        $assessment_period_ids = [];
        $selectedSubject = null;
        $selectedSubjectId = null;

        if (!empty($assessment_item_id)) {
            $AssessmentItemsTable = self::getDynamicTableInstance('Assessment.AssessmentItems');
            $result = $AssessmentItemsTable->find()
                ->select([
                    'education_subject_id' => 'AssessmentItems.education_subject_id',
                    'education_subject_name' => 'education_subjects.name'
                ])
                ->leftJoin(
                    ['education_subjects' => 'education_subjects'],
                    ['education_subjects.id = AssessmentItems.education_subject_id']
                )
                ->where(['AssessmentItems.id' => $assessment_item_id])
                ->first();
            if ($result) {
                $nameParts = explode('-', $result->education_subject_name);
                $selectedSubject = isset($nameParts[1]) ? trim($nameParts[1]) : trim($nameParts[0]);
                $selectedSubjectId = $result->education_subject_id;
            }
        }


        //POCOR-9114 -- START Check if the assessment_period_ids are multiple
        if (!empty($options['assessment_period_combo'])) {
            $assessment_period_ids = array_filter(
                array_map('intval', explode('_', $options['assessment_period_combo']))
            );
        }
        //POCOR-9114 -- END

//        Log::debug(print_r([$assessment_item_id, $assessment_period_id, $institution_class_id], true));
        $where = [
            'institution_classes.id = ' . $institution_class_id,
            'student_statuses.code NOT IN ("TRANSFERRED", "WITHDRAWN", "GRADUATED", "PROMOTED", "REPEATED")',
        ];

        if (!empty($assessment_period_ids)) {
            $AssessmentItemResults = self::getDynamicTableInstance('Assessment.AssessmentItemResults');

            $markedStudentsRaw = $AssessmentItemResults->find()
                ->select(['student_id'])
                ->where([
                    'education_subject_id' => $selectedSubjectId,
                    'assessment_period_id IN' => $assessment_period_ids,
                    'student_id IS NOT' => null
                ])
                ->disableHydration()
                ->toArray();
            $markedStudentIds = array_unique(array_column($markedStudentsRaw, 'student_id'));
        }

        // Building the query
        $query = $query->find('all')
            ->enableAutoFields()
            ->leftJoin(
                ['assessment_item_student_exemptions' => 'assessment_item_student_exemptions'],
                [
                    $this->aliasField('student_id') . ' = assessment_item_student_exemptions.student_id',
                    $this->aliasField('institution_class_id') . ' = assessment_item_student_exemptions.institution_class_id',
                    $this->aliasField('education_grade_id') . ' = assessment_item_student_exemptions.education_grade_id',
                    //'assessment_item_student_exemptions.assessment_period_id = ' . $assessment_period_id
                    'assessment_item_student_exemptions.assessment_period_id IN (' . implode(',', $assessment_period_ids) . ')' //POCOR-9114
                ]
            )
            ->leftJoin(
                ['assessment_items' => 'assessment_items'],
                [
                    //'assessment_items.id = "' . $assessment_item_id . '"',
                    'assessment_items.id' => $assessment_item_id,
                    'assessment_item_student_exemptions.assessment_id = assessment_items.assessment_id',
                    'assessment_item_student_exemptions.education_subject_id = assessment_items.education_subject_id'
                ]
            )
            ->leftJoin(['assessment_periods' => 'assessment_periods'], ['assessment_periods.id = assessment_item_student_exemptions.assessment_period_id'])
            ->leftJoin(['education_subjects' => 'education_subjects'], ['education_subjects.id = assessment_item_student_exemptions.education_subject_id'])
            ->innerJoin(
                ['security_users' => 'security_users'],
                [$this->aliasField('student_id') . ' = security_users.id']
            )
            ->innerJoin(
                ['institution_classes' => 'institution_classes'],
                [$this->aliasField('institution_class_id') . ' = institution_classes.id']
            )
            ->innerJoin(
                ['genders' => 'genders'],
                ['genders.id = security_users.gender_id']
            )
            ->innerJoin(
                ['student_statuses' => 'student_statuses'],
                [$this->aliasField('student_status_id') . ' = student_statuses.id']
            )
            ->where($where)
            ->andWhere(function ($exp, $q) use ($selectedSubjectId) {
                return $exp->or_([
                    'assessment_item_student_exemptions.education_subject_id IS' => null,
                    'assessment_item_student_exemptions.education_subject_id' => '',
                    'assessment_item_student_exemptions.education_subject_id' => $selectedSubjectId
                ]);
            });
        // echo "<pre>";print_r($query->sql());die;
        // echo "<pre>";print_r($markedStudentIds);exit;
        if (!empty($markedStudentIds)) {
            $query->andWhere(function ($exp, $q) use ($markedStudentIds) {
                return $exp->notIn($this->aliasField('student_id'), $markedStudentIds);
            });
        }
        $query->select([
            'student_id' => $this->aliasField('student_id'),
            'openemis_no' => 'security_users.openemis_no',
            'first_name' => 'security_users.first_name',
            'middle_name' => 'security_users.middle_name',
            'third_name' => 'security_users.third_name',
            'last_name' => 'security_users.last_name',
            'institution_class_id' => $this->aliasField('institution_class_id'),
            'institution_id' => $this->aliasField('institution_id'),
            'assessment_id' => 'assessment_item_student_exemptions.assessment_id',
            'education_subject_id' => 'assessment_item_student_exemptions.education_subject_id',
            'education_subject_name' => 'education_subjects.name',
            'assessment_period_id' => 'assessment_item_student_exemptions.assessment_period_id',
            'assessment_period_name' => 'assessment_periods.name',
            'institution_class_student_id' => $this->aliasField('id'),
            'education_grade_id' => $this->aliasField('education_grade_id'),
            'gender' => 'genders.name',
            'gender_id' => 'genders.id',
            'student_status_name' => 'student_statuses.name',
            'assessment_items.assessment_id',
            'assessment_items.education_subject_id',
            'assessment_items.classification',
            'type' => 'assessment_item_student_exemptions.type',//POCOR-9042
        ])
            ->disableHydration();
//        Log::debug($query->sql());
        // Format the results
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) // POCOR-9289
        use ($selectedSubject, $assessment_period_names_string) { // POCOR-9289
            return $results->map(function ($row) use ($selectedSubject, $assessment_period_names_string) { // POCOR-9289
                $fullName = [];
                ($row['first_name']) ? $fullName[] = $row['first_name'] : '';
                ($row['middle_name']) ? $fullName[] = $row['middle_name'] : '';
                ($row['third_name']) ? $fullName[] = $row['third_name'] : '';
                ($row['last_name']) ? $fullName[] = $row['last_name'] : '';
                $row['is_exempt'] = ($row['assessment_id'] && $row['type'] == 1) ? true : false;//POCOR-9042
                $row['is_unassign'] = ($row['assessment_id'] && $row['type'] == 2) ? true : false;//POCOR-9042
                $row['is_unassign'] = $row['type'];//POCOR-9042

                $name = implode(' ', $fullName);

                return [
                    'openemis_no' => $row['openemis_no'],
                    'name' => $name,
                    'gender' => __($row['gender']),
                    'gender_id' => intval($row['gender_id']),
                    'student_id' => $row['student_id'],
                    'education_grade_id' => $row['education_grade_id'],
                    'institution_class_id' => $row['institution_class_id'],
                    'institution_class_student_id' => $row['institution_class_student_id'],
                    'assessment_period_id' => $row['assessment_period_id'],
                    'assessment_item_id' => $row['assessment_id'],  // Use assessment_id now
                    'is_exempt' => $row['is_exempt'],
                    'is_unassign' => $row['is_unassign'],//POCOR-9042
                    'type' => $row['type'],//POCOR-9042
                    'education_subject_id' => $row['education_subject_id'],
                    'education_subject_name' => $row['education_subject_name'] ?: $selectedSubject,
                    'assessment_period_name' => $row['assessment_period_name'] ?: $assessment_period_names_string,
                    'student_status_name' => __($row['student_status_name'])
                ];
            });
        });

        return $query;
    }

    //POCOR-9289 -- Updated function to include logic to display only selected subject (one) regardless of marks
    public function findExemptStudents(Query $query, array $options): Query
    {

        // Extract the parameters from the options array
        $assessment_item_id = preg_replace("/[^a-fA-F0-9\-]/", "", $options['assessment_item_id']);  // Still using assessment_item_id for reference
        $assessment_period_id = intval($options['assessment_period_id']);
        $institution_class_id = intval($options['institution_class_id']);
        $studentStatusId = intval($options['studentstatus_id']); //POCOR-9428
        // Prepare period IDs & names
        $assessment_period_names_string = null;
        $assessment_period_ids = [];
        $selectedSubject = null;
        $selectedSubjectId = null;

        if (!empty($assessment_item_id)) {
            $AssessmentItemsTable = self::getDynamicTableInstance('Assessment.AssessmentItems');
            $result = $AssessmentItemsTable->find()
                ->select([
                    'education_subject_id' => 'AssessmentItems.education_subject_id',
                    'education_subject_name' => 'education_subjects.name'
                ])
                ->leftJoin(
                    ['education_subjects' => 'education_subjects'],
                    ['education_subjects.id = AssessmentItems.education_subject_id']
                )
                ->where(['AssessmentItems.id' => $assessment_item_id])
                ->first();
            if ($result) {
                $nameParts = explode('-', $result->education_subject_name);
                $selectedSubject = isset($nameParts[1]) ? trim($nameParts[1]) : trim($nameParts[0]);
                $selectedSubjectId = $result->education_subject_id;
            }
        }


        //POCOR-9114 -- START Check if the assessment_period_ids are multiple
        if (!empty($options['assessment_period_combo'])) {
            $assessment_period_ids = array_filter(
                array_map('intval', explode('_', $options['assessment_period_combo']))
            );
        }
        //POCOR-9114 -- END

//        Log::debug(print_r([$assessment_item_id, $assessment_period_id, $institution_class_id], true));
        $where = [
            'institution_classes.id = ' . $institution_class_id,
            'student_statuses.code NOT IN ("WITHDRAWN", "REPEATED")',
            $this->aliasField('student_status_id') => $studentStatusId //POCOR-9428
        ];

        // Building the query
        $query = $query->find('all')
            ->enableAutoFields()
            ->leftJoin(
                ['assessment_item_student_exemptions' => 'assessment_item_student_exemptions'],
                [
                    $this->aliasField('student_id') . ' = assessment_item_student_exemptions.student_id',
                    $this->aliasField('institution_class_id') . ' = assessment_item_student_exemptions.institution_class_id',
                    $this->aliasField('education_grade_id') . ' = assessment_item_student_exemptions.education_grade_id',
                    'assessment_item_student_exemptions.education_subject_id = ' . $selectedSubjectId,
                    'assessment_item_student_exemptions.assessment_period_id IN (' . implode(',', $assessment_period_ids) . ')' //POCOR-9114
                ]
            )
            ->leftJoin(
                ['assessment_items' => 'assessment_items'],
                [
                    'assessment_items.id = "' . $assessment_item_id . '"', // for debugging
                    'assessment_item_student_exemptions.assessment_id = assessment_items.assessment_id',
                    'assessment_item_student_exemptions.education_subject_id = assessment_items.education_subject_id'
                ]
            )
            ->leftJoin(['assessment_periods' => 'assessment_periods'], ['assessment_periods.id = assessment_item_student_exemptions.assessment_period_id'])
            ->leftJoin(['education_subjects' => 'education_subjects'], ['education_subjects.id = assessment_item_student_exemptions.education_subject_id'])
            ->innerJoin(
                ['security_users' => 'security_users'],
                [$this->aliasField('student_id') . ' = security_users.id']
            )
            ->innerJoin(
                ['institution_subject_students' => 'institution_subject_students'],
                [
                    $this->aliasField('student_id') . ' = institution_subject_students.student_id',
                    $this->aliasField('institution_class_id') . ' = institution_subject_students.institution_class_id',
                    $this->aliasField('academic_period_id') . ' = institution_subject_students.academic_period_id',
                    'institution_subject_students.education_subject_id' . ' = ' . $selectedSubjectId, // include only selected subject
                    $this->aliasField('education_grade_id') . ' = institution_subject_students.education_grade_id',
                    $this->aliasField('student_status_id') . ' = institution_subject_students.student_status_id',
                    ]
            )
            ->innerJoin(
                ['institution_classes' => 'institution_classes'],
                [$this->aliasField('institution_class_id') . ' = institution_classes.id']
            )
            ->innerJoin(
                ['genders' => 'genders'],
                ['genders.id = security_users.gender_id']
            )
            ->innerJoin(
                ['student_statuses' => 'student_statuses'],
                [$this->aliasField('student_status_id') . ' = student_statuses.id']
            )
            ->where($where)
            ->andWhere(function ($exp, $q) use ($selectedSubjectId, $assessment_period_ids) {
                return $exp->or_([
                    'assessment_item_student_exemptions.education_subject_id = ""',
                    'assessment_item_student_exemptions.education_subject_id IS NULL',
                    ['assessment_item_student_exemptions.education_subject_id = ' . $selectedSubjectId,
                        'assessment_item_student_exemptions.assessment_period_id IN (' . implode(',', $assessment_period_ids) . ')' ]
                ]);
            })
        ;
//        Log::debug(print_r([
//            $where,
//            $assessment_period_ids,
//            $query->sql()
//        ], true));
            $query->select([
                'student_id' => $this->aliasField('student_id'),
                'openemis_no' => 'security_users.openemis_no',
                'first_name' => 'security_users.first_name',
                'middle_name' => 'security_users.middle_name',
                'third_name' => 'security_users.third_name',
                'last_name' => 'security_users.last_name',
                'institution_class_id' => $this->aliasField('institution_class_id'),
                'institution_id' => $this->aliasField('institution_id'),
                'assessment_id' => 'assessment_item_student_exemptions.assessment_id',
                'education_subject_id' => 'assessment_item_student_exemptions.education_subject_id',
                'education_subject_name' => 'education_subjects.name',
                'assessment_period_id' => 'assessment_item_student_exemptions.assessment_period_id',
                'assessment_period_name' => 'assessment_periods.name',
                'institution_class_student_id' => $this->aliasField('id'),
                'education_grade_id' => $this->aliasField('education_grade_id'),
                'gender' => 'genders.name',
                'gender_id' => 'genders.id',
                'student_status_name' => 'student_statuses.name',
                'assessment_items.assessment_id',
                'assessment_items.education_subject_id',
                'assessment_items.classification',
                'type' => 'assessment_item_student_exemptions.type',//POCOR-9042
            ])
            ->disableHydration();
//        Log::debug($query->sql());
        // Format the results
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) use ($selectedSubject, $assessment_period_names_string) {
            return $results->map(function ($row) use ($selectedSubject, $assessment_period_names_string) {
                $fullName = [];
                ($row['first_name']) ? $fullName[] = $row['first_name'] : '';
                ($row['middle_name']) ? $fullName[] = $row['middle_name'] : '';
                ($row['third_name']) ? $fullName[] = $row['third_name'] : '';
                ($row['last_name']) ? $fullName[] = $row['last_name'] : '';
                $row['is_exempt'] = ($row['assessment_id'] && $row['type'] == 1) ? true : false;//POCOR-9042
                $row['is_unassign'] = ($row['assessment_id'] && $row['type'] == 2) ? true : false;//POCOR-9042


                $name = implode(' ', $fullName);

                return [
                    'openemis_no' => $row['openemis_no'],
                    'name' => $name,
                    'gender' => __($row['gender']),
                    'gender_id' => intval($row['gender_id']),
                    'student_id' => $row['student_id'],
                    'education_grade_id' => $row['education_grade_id'],
                    'institution_class_id' => $row['institution_class_id'],
                    'institution_class_student_id' => $row['institution_class_student_id'],
                    'assessment_period_id' => $row['assessment_period_id'],
                    'assessment_item_id' => $row['assessment_id'],  // Use assessment_id now
                    'is_exempt' => $row['is_exempt'],
                    'is_unassign' => $row['is_unassign'],//POCOR-9042
                    'type' => $row['type'],//POCOR-9042
                    'education_subject_id' => $row['education_subject_id'],
                    'education_subject_name' => $row['education_subject_name'] ?: $selectedSubject,
                    'assessment_period_name' => $row['assessment_period_name'] ?: $assessment_period_names_string,
                    'student_status_name' => __($row['student_status_name'])
                ];
            });
        });

        return $query;
    }
    // POCOR-9289 end


    public function findExemptStudentsOrg(Query $query, array $options): Query
    {

        // Extract the parameters from the options array
        $assessment_item_id = preg_replace("/[^a-fA-F0-9\-]/", "", $options['assessment_item_id']);  // Still using assessment_item_id for reference
        $assessment_period_id = intval($options['assessment_period_id']);
        $institution_class_id = intval($options['institution_class_id']);

        //POCOR-9114 -- START Check if the assessment_period_ids are multiple
        if (!empty($options['assessment_period_combo'])) {
            $assessment_period_ids = array_filter(
                array_map('intval', explode('_', $options['assessment_period_combo']))
            );
        }
        //POCOR-9114 -- END

//        Log::debug(print_r([$assessment_item_id, $assessment_period_id, $institution_class_id], true));
        $where = [
            'institution_classes.id = ' . $institution_class_id,
            'student_statuses.code NOT IN ("TRANSFERRED", "WITHDRAWN", "GRADUATED", "PROMOTED", "REPEATED")',
        ];

        // Building the query
        $query = $query->find('all')
            ->enableAutoFields()
            ->leftJoin(
                ['assessment_item_student_exemptions' => 'assessment_item_student_exemptions'],
                [
                    $this->aliasField('student_id') . ' = assessment_item_student_exemptions.student_id',
                    $this->aliasField('institution_class_id') . ' = assessment_item_student_exemptions.institution_class_id',
                    $this->aliasField('education_grade_id') . ' = assessment_item_student_exemptions.education_grade_id',
                    //'assessment_item_student_exemptions.assessment_period_id = ' . $assessment_period_id
                    'assessment_item_student_exemptions.assessment_period_id IN (' . implode(',', $assessment_period_ids) . ')' //POCOR-9114
                ]
            )
            ->leftJoin(
                ['assessment_items' => 'assessment_items'],
                [
                    'assessment_items.id = "' . $assessment_item_id . '"',
                    'assessment_item_student_exemptions.assessment_id = assessment_items.assessment_id',
                    'assessment_item_student_exemptions.education_subject_id = assessment_items.education_subject_id'
                ]
            )
            ->innerJoin(
                ['security_users' => 'security_users'],
                [$this->aliasField('student_id') . ' = security_users.id']
            )
            ->innerJoin(
                ['institution_classes' => 'institution_classes'],
                [$this->aliasField('institution_class_id') . ' = institution_classes.id']
            )
            ->innerJoin(
                ['genders' => 'genders'],
                ['genders.id = security_users.gender_id']
            )
            ->innerJoin(
                ['student_statuses' => 'student_statuses'],
                [$this->aliasField('student_status_id') . ' = student_statuses.id']
            )
            ->where($where)
            ->select([
                'student_id' => $this->aliasField('student_id'),
                'openemis_no' => 'security_users.openemis_no',
                'first_name' => 'security_users.first_name',
                'middle_name' => 'security_users.middle_name',
                'third_name' => 'security_users.third_name',
                'last_name' => 'security_users.last_name',
                'institution_class_id' => $this->aliasField('institution_class_id'),
                'institution_id' => $this->aliasField('institution_id'),
                'assessment_id' => 'assessment_item_student_exemptions.assessment_id',
                'education_subject_id' => 'assessment_item_student_exemptions.education_subject_id',
                'assessment_period_id' => 'assessment_item_student_exemptions.assessment_period_id',
                'institution_class_student_id' => $this->aliasField('id'),
                'education_grade_id' => $this->aliasField('education_grade_id'),
                'gender' => 'genders.name',
                'gender_id' => 'genders.id',
                'student_status_name' => 'student_statuses.name',
                'assessment_items.assessment_id',
                'assessment_items.education_subject_id',
                'assessment_items.classification',
                'type' => 'assessment_item_student_exemptions.type',//POCOR-9042
            ])
            ->disableHydration();
//        Log::debug($query->sql());
        // Format the results
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {
                $fullName = [];
                ($row['first_name']) ? $fullName[] = $row['first_name'] : '';
                ($row['middle_name']) ? $fullName[] = $row['middle_name'] : '';
                ($row['third_name']) ? $fullName[] = $row['third_name'] : '';
                ($row['last_name']) ? $fullName[] = $row['last_name'] : '';
                $row['is_exempt'] = ($row['assessment_id'] && $row['type'] == 1) ? true : false;//POCOR-9042
                $row['is_unassign'] = ($row['assessment_id'] && $row['type'] == 2) ? true : false;//POCOR-9042
                $row['is_unassign'] = $row['type'];//POCOR-9042

                $name = implode(' ', $fullName);

                return [
                    'openemis_no' => $row['openemis_no'],
                    'name' => $name,
                    'gender' => __($row['gender']),
                    'gender_id' => intval($row['gender_id']),
                    'student_id' => $row['student_id'],
                    'education_grade_id' => $row['education_grade_id'],
                    'institution_class_id' => $row['institution_class_id'],
                    'institution_class_student_id' => $row['institution_class_student_id'],
                    'assessment_period_id' => $row['assessment_period_id'],
                    'assessment_item_id' => $row['assessment_id'],  // Use assessment_id now
                    'is_exempt' => $row['is_exempt'],
                    'is_unassign' => $row['is_unassign'],//POCOR-9042
                    'type' => $row['type'],//POCOR-9042
                    'student_status_name' => __($row['student_status_name'])
                ];
            });
        });

        return $query;
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
