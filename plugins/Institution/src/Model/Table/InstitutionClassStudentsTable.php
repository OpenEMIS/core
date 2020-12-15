<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\I18n\Time;
use Cake\Utility\Text;
use App\Model\Table\AppTable;
use Cake\Datasource\ResultSetInterface;

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

    public function initialize(array $config)
    {
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
            'excludes' => ['id'],
            'pages' => ['index'],
            'orientation' => 'landscape'
        ]);

        $this->addBehavior('Restful.RestfulAccessControl', [
            'OpenEMIS_Classroom' => ['index', 'view'],
            'SubjectStudents' => ['index'],
            'ReportCardComments' => ['index'],
            'StudentCompetencies' => ['index'],
            'StudentOutcomes' => ['index']
        ]);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.Students.afterSave'] = 'studentsAfterSave';
        return $events;
    }

    public function studentsAfterSave(Event $event, $student)
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

            if (!empty($classStudent) && $classStudent->student_status_id != $student->student_status_id) {
                if ($student->next_institution_class_id > 0) {
                    $classStudent->next_institution_class_id = $student->next_institution_class_id;
                }
                $classStudent->student_status_id = $student->student_status_id;
                $this->save($classStudent);
            }
        }
    }

    public function onExcelBeforeGenerate(Event $event, ArrayObject $settings)
    {
        $classId = $settings['class_id'];
        $institutionId = $settings['institution_id'];
        $institutionCode = $this->Institutions->get($institutionId)->code;
        $className = $this->InstitutionClasses->get($classId)->name;
        $settings['file'] = str_replace($this->alias(), str_replace(' ', '_', $institutionCode).'-'.str_replace(' ', '_', $className).'_Results', $settings['file']);
    }

    public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets)
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

        $InstitutionClassesTable = TableRegistry::get('Institution.InstitutionClasses');
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
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $originalField)
    {
        $assessmentId = $settings['sheet']['assessmentId'];
        $assessmentEntity = TableRegistry::get('Assessment.Assessments')->get($assessmentId);
        $AssessmentPeriodsTable = TableRegistry::get('Assessment.AssessmentPeriods');
        $AssessmentItemsGradingTypesTable = TableRegistry::get('Assessment.AssessmentItemsGradingTypes');
        $academicPeriodId = $assessmentEntity->academic_period_id;
        $institutionId = $settings['sheet']['institutionId'];

        $fields = new ArrayObject();
        $fields[] = [
            'key' => 'Users.openemis_no',
            'field' => 'openemis_number',
            'type' => 'string',
            'label' => '',
        ];

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
            ->order([$AssessmentPeriodsTable->aliasField('start_date')])
            ->toArray();

        $assessmentGradeTypes = $AssessmentItemsGradingTypesTable->getAssessmentGradeTypes($assessmentId);
        $assessmentSubjects = TableRegistry::get('Assessment.AssessmentItems')->getSubjects($assessmentId);
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

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, $query)
    {
        $sheet = $settings['sheet'];
        $institutionId = $sheet['institutionId'];
        $allClassesPermission = $sheet['allClassesPermission'];
        $allSubjectsPermission = $sheet['allSubjectsPermission'];
        $myClassesPermission = $sheet['myClassesPermission'];
        $mySubjectsPermission = $sheet['mySubjectsPermission'];
        $assessmentId = $sheet['assessmentId'];
        $staffId = $sheet['staffId'];
        $StudentStatuses = $this->StudentStatuses;
        
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
            ->where([$this->aliasField('institution_id') => $institutionId/*,
                $StudentStatuses->aliasField('code NOT IN ') => ['TRANSFERRED','WITHDRAWN']*/])
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
                }
            }
        }
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->isNew()) {
            $entity->id = Text::uuid();
        }
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if($entity->isNew() || $entity->dirty('student_status_id')) {
            $id = $entity->institution_class_id;
            $countMale = $this->getMaleCountByClass($id);
            $countFemale = $this->getFemaleCountByClass($id);
            $this->InstitutionClasses->updateAll(['total_male_students' => $countMale, 'total_female_students' => $countFemale], ['id' => $id]);
        }
  
        $listeners = [
            TableRegistry::get('Institution.InstitutionSubjectStudents')
        ];
        $this->dispatchEventToModels('Model.InstitutionClassStudents.afterSave', [$entity], $this, $listeners);
    }

    public function onExcelRenderSubject(Event $event, Entity $entity, array $attr)
    {
        $studentId = $entity->student_id;
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
            $AssessmentItemResultsTable = TableRegistry::get('Assessment.AssessmentItemResults');

            $studentResults = $AssessmentItemResultsTable->getAssessmentItemResults($academicPeriodId, $assessmentId, $subjectId, $studentId);
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
            $classId = $entity->institution_class_id;

            if ($this->lastQueriedClass != $classId) {
                $AssessmentItemsTable = TableRegistry::get('Assessment.AssessmentItems');
                $allowedSubjects = $AssessmentItemsTable
                ->find('list', [
                    'keyField' => 'assessment_item_id',
                    'valueField' => 'subject_id'
                ])
                ->find('staffSubjects', ['class_id' => $classId, 'staff_id' => $staffId])
                ->select(['assessment_item_id' => $AssessmentItemsTable->aliasField('id'), 'subject_id' => $AssessmentItemsTable->aliasField('education_subject_id')])
                ->where([$AssessmentItemsTable->aliasField('assessment_id') => $assessmentId])
                ->hydrate(false)
                ->toArray();
                $this->allowedSubjects = $allowedSubjects;
                $this->lastQueriedClass = $classId;
            }

            if (!in_array($subjectId, $this->allowedSubjects)) {
                $printedResult = __('No Access');
                $renderResult = false;
            }
        }

        if ($renderResult) {
            if (isset($this->assessmentItemResults[$studentId][$subjectId][$assessmentPeriodId])) {
                $result = $this->assessmentItemResults[$studentId][$subjectId][$assessmentPeriodId];
                switch ($resultType) {
                    case 'MARKS':
                        // Add logic to add weighted mark to subjectWeightedMark
                        $this->assessmentPeriodWeightedMark += ($result['marks'] * $attr['assessmentPeriodWeight']);
                        $printedResult = ' '.$result['marks'];
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

    public function onExcelRenderNationality(Event $event, Entity $entity, array $attr)
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

    public function onExcelRenderAssessmentPeriodWeightedMark(Event $event, Entity $entity, array $attr)
    {
        $assessmentPeriodWeightedMark = $this->assessmentPeriodWeightedMark;
        $this->totalMark += $assessmentPeriodWeightedMark;
        $this->totalWeightedMark += ($assessmentPeriodWeightedMark * $attr['subjectWeight']);

        // reset the assessmentPeriodWeightedMark mark
        $this->assessmentPeriodWeightedMark = 0;

        return ' '.$assessmentPeriodWeightedMark;
    }

    public function onExcelRenderTotalWeightedMark(Event $event, Entity $entity, array $attr)
    {
        $totalWeightedMark = $this->totalWeightedMark;
        $this->totalWeightedMark = 0;
        return ' '.$totalWeightedMark;
    }

    public function onExcelRenderTotalMark(Event $event, Entity $entity, array $attr)
    {
        $totalMark = $this->totalMark;
        $this->totalMark = 0;
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

    public function afterDelete(Event $event, Entity $entity, ArrayObject $options)
    {
        $id = $entity->institution_class_id;
        $countMale = $this->getMaleCountByClass($id);
        $countFemale = $this->getFemaleCountByClass($id);
        $this->InstitutionClasses->updateAll(['total_male_students' => $countMale, 'total_female_students' => $countFemale], ['id' => $id]);

        $listeners = [
            TableRegistry::get('Institution.InstitutionCompetencyResults'),
            TableRegistry::get('Institution.InstitutionSubjectStudents')
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
            ->matching('StudentStatuses')
            ->leftJoinWith('SubjectStudents', function ($q) use ($institutionSubjectId, $academicPeriodId) {
                return $q
                    ->innerJoin(['EducationGradesSubjects' => 'education_grades_subjects'], [
                        'EducationGradesSubjects.education_grade_id = SubjectStudents.education_grade_id',
                        'EducationGradesSubjects.education_subject_id = SubjectStudents.education_subject_id'
                    ])
                    ->where([
                        'SubjectStudents.institution_subject_id' => $institutionSubjectId,
                        'SubjectStudents.academic_period_id' => $academicPeriodId
                    ]);
            })
            ->where([
                $this->aliasField('institution_class_id').' IN ' => $institutionClassIds,
                $this->aliasField('education_grade_id') => $educationGradeId,
                $this->aliasField('academic_period_id') => $academicPeriodId,
                'SubjectStudents.student_id IS NULL'
            ])
            ->order(['Users.first_name', 'Users.last_name']) // POCOR-2547 sort list of staff and student by name
            ->formatResults(function ($results) {
                $resultArr = [];
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

        $Students = TableRegistry::get('Institution.Students');
        $StudentStatuses = TableRegistry::get('Student.StudentStatuses');
        $StudentAbsences = TableRegistry::get('Institution.InstitutionStudentAbsences');
        $AbsenceTypes = TableRegistry::get('Institution.AbsenceTypes');
        $StudentAbsenceReasons = TableRegistry::get('Institution.StudentAbsenceReasons');
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
                [$Students->alias() => $Students->table()],
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
                [$StudentAbsences->alias() => $StudentAbsences->table()],
                [
                    $StudentAbsences->aliasField('institution_id = ') . $this->aliasField('institution_id'),
                    $StudentAbsences->aliasField('student_id = ') . $this->aliasField('student_id'),
                    $StudentAbsences->aliasField('start_date <=') => $absenceDate,
                    $StudentAbsences->aliasField('end_date >=') => $absenceDate
                ]
            )
            ->leftJoin(
                [$AbsenceTypes->alias() => $AbsenceTypes->table()],
                [
                    $AbsenceTypes->aliasField('id = ') . $StudentAbsences->aliasField('absence_type_id')
                ]
            )
            ->leftJoin(
                [$StudentAbsenceReasons->alias() => $StudentAbsenceReasons->table()],
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

        $StudentReportCards = TableRegistry::get('Institution.InstitutionStudentsReportCards');
        $SubjectStudents = $this->SubjectStudents;
        $StudentStatuses = $this->StudentStatuses;
        $Users = $this->Users;

        $AssessmentItem = TableRegistry::get('Assessment.AssessmentItem');
        $AssessmentItemResults = TableRegistry::get('Assessment.AssessmentItemResults');
        $ReportCards = TableRegistry::get('ReportCard.ReportCards');

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
                [$StudentReportCards->alias() => $StudentReportCards->table()],
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
                $this->aliasField('education_grade_id') => $educationGradeId
            ])
            ->group([
                $this->aliasField('student_id')
            ])
            ->order([
                $Users->aliasField('first_name'), $Users->aliasField('last_name')
            ]);

        if ($type == 'PRINCIPAL') {
            $query
                ->select(['comments' => $StudentReportCards->aliasfield('principal_comments')])
                ->formatResults(function (ResultSetInterface $results) use ($academicPeriodId, $institutionId, $SubjectStudents, $AssessmentItemResults, $educationSubjectId, $ReportCards, $reportCardId) {

                    return $results->map(function ($row) use ($academicPeriodId, $institutionId, $SubjectStudents, $AssessmentItemResults, $educationSubjectId, $ReportCards, $reportCardId) {

                        $studentId = $row->student_id;
                        if (!empty($row['InstitutionStudentsReportCards']['report_card_id'])) {
                            $reportCardId = $row['InstitutionStudentsReportCards']['report_card_id'];
                        }

                        // Get the report card start/end date
                        $reportCardEntity = $ReportCards->find()
                            ->select([
                                $ReportCards->aliasField('start_date'),
                                $ReportCards->aliasField('end_date')
                            ])
                            ->where([
                                $ReportCards->aliasField('id') => $reportCardId
                            ])
                            ->all();

                        if (!$reportCardEntity->isEmpty()) {
                            $row->reportCardStartDate = NULL;
                            $row->reportCardEndDate = NULL;
                            $row->reportCardStartDate = $reportCardEntity->first()['start_date'];
                            $row->reportCardEndDate = $reportCardEntity->first()['end_date'];
                        }

                        // To get the report card template subjects
                        $ReportCardSubjects = TableRegistry::get('ReportCard.ReportCardSubjects');
                        $reportCardSubjectsEntity = $ReportCardSubjects->find()
                            ->select([
                                'education_subject_id'
                            ])
                            ->where([
                                $ReportCardSubjects->aliasField('report_card_id') => $reportCardId
                            ])
                            ->hydrate(false)
                            ->all();

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
                            ->hydrate(false)
                            ->all();

                        // If subjectStudentsEntities is not empty mean the student have a subject
                        if (!$subjectStudentsEntities->isEmpty()) {

                            $total_mark = 0;
                            $subjectTaken = 0;

                            foreach($subjectStudentsEntities->toArray() as $studentEntity) {
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
                                        'weightage' => $AssessmentItemResults->AssessmentPeriods->aliasField('weight')

                                    ])
                                    ->contain([
                                        'AssessmentPeriods'
                                    ])
                                    ->where([
                                        $AssessmentItemResults->aliasField('student_id') => $studentEntity['student_id'],
                                        $AssessmentItemResults->aliasField('education_subject_id') => $studentEntity['education_subject_id'],
                                        $AssessmentItemResults->AssessmentPeriods->aliasField('start_date').' >= ' => $row->reportCardStartDate,
                                        $AssessmentItemResults->AssessmentPeriods->aliasField('end_date').' <= ' => $row->reportCardEndDate,
                                        $AssessmentItemResults->aliasField('marks IS NOT NULL')
                                    ])
                                    ->all();

                                    if (!$assessmentItemResultsEntities->isEmpty()) {
                                    foreach ($assessmentItemResultsEntities as $entity) {
                                        foreach ($reportCardSubjectsEntity as $reportCardSubjectEntity) {
                                            if($entity['education_subject_id'] === $reportCardSubjectEntity['education_subject_id']) {
                                                $total_mark += $entity->marks * $entity->weightage;
                                                // Plus one to the subject so that we can keep track how many subject does this student is taking within the report card template.
                                                $subjectTaken++;
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
        } elseif ($type == 'HOMEROOM_TEACHER') {
            $query
                ->select(['comments' => $StudentReportCards->aliasfield('homeroom_teacher_comments')])
                ->formatResults(function (ResultSetInterface $results) use ($academicPeriodId, $institutionId, $SubjectStudents, $AssessmentItemResults, $educationSubjectId, $ReportCards, $reportCardId) {

                    return $results->map(function ($row) use ($academicPeriodId, $institutionId, $SubjectStudents, $AssessmentItemResults, $educationSubjectId, $ReportCards, $reportCardId) {

                        $studentId = $row->student_id;
                        if (!empty($row['InstitutionStudentsReportCards']['report_card_id'])) {
                            $reportCardId = $row['InstitutionStudentsReportCards']['report_card_id'];
                        }

                        // Get the report card start/end date
                        $reportCardEntity = $ReportCards->find()
                            ->select([
                                $ReportCards->aliasField('start_date'),
                                $ReportCards->aliasField('end_date')
                            ])
                            ->where([
                                $ReportCards->aliasField('id') => $reportCardId
                            ])
                            ->all();

                        if (!$reportCardEntity->isEmpty()) {
                            $row->reportCardStartDate = NULL;
                            $row->reportCardEndDate = NULL;
                            $row->reportCardStartDate = $reportCardEntity->first()['start_date'];
                            $row->reportCardEndDate = $reportCardEntity->first()['end_date'];
                        }

                        // To get the report card template subjects
                        $ReportCardSubjects = TableRegistry::get('ReportCard.ReportCardSubjects');
                        $reportCardSubjectsEntity = $ReportCardSubjects->find()
                            ->select([
                                'education_subject_id'
                            ])
                            ->where([
                                $ReportCardSubjects->aliasField('report_card_id') => $reportCardId
                            ])
                            ->hydrate(false)
                            ->all();

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
                            ->hydrate(false)
                            ->all();

                        // If subjectStudentsEntities is not empty mean the student have a subject
                        if (!$subjectStudentsEntities->isEmpty()) {

                            $total_mark = 0;
                            $subjectTaken = 0;

                            foreach($subjectStudentsEntities->toArray() as $studentEntity) {
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
                                        'weightage' => $AssessmentItemResults->AssessmentPeriods->aliasField('weight')
                                    ])
                                    ->contain([
                                        'AssessmentPeriods'
                                    ])
                                    ->where([
                                        $AssessmentItemResults->aliasField('student_id') => $studentEntity['student_id'],
                                        $AssessmentItemResults->aliasField('education_subject_id') => $studentEntity['education_subject_id'],
                                        $AssessmentItemResults->AssessmentPeriods->aliasField('start_date').' >= ' => $row->reportCardStartDate,
                                        $AssessmentItemResults->AssessmentPeriods->aliasField('end_date').' <= ' => $row->reportCardEndDate,
                                        $AssessmentItemResults->aliasField('marks IS NOT NULL')
                                    ])
                                    ->all();

                                if (!$assessmentItemResultsEntities->isEmpty()) {
                                    foreach ($assessmentItemResultsEntities as $entity) {
                                        foreach ($reportCardSubjectsEntity as $reportCardSubjectEntity) {
                                            if($entity['education_subject_id'] === $reportCardSubjectEntity['education_subject_id']) {
                                                $total_mark += $entity->marks * $entity->weightage;
                                                // Plus one to the subject so that we can keep track how many subject does this student is taking within the report card template.
                                                $subjectTaken++;
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
            $ReportCardsComments = TableRegistry::get('Institution.InstitutionStudentsReportCardsComments');
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
                ->leftJoin([$ReportCardsComments->alias() => $ReportCardsComments->table()], [
                    $ReportCardsComments->aliasField('report_card_id = ') . $StudentReportCards->aliasField('report_card_id'),
                    $ReportCardsComments->aliasField('student_id = ') . $StudentReportCards->aliasField('student_id'),
                    $ReportCardsComments->aliasField('institution_id = ') . $StudentReportCards->aliasField('institution_id'),
                    $ReportCardsComments->aliasField('academic_period_id = ') . $StudentReportCards->aliasField('academic_period_id'),
                    $ReportCardsComments->aliasField('education_grade_id = ') . $StudentReportCards->aliasField('education_grade_id'),
                    $ReportCardsComments->aliasField('education_subject_id') => $educationSubjectId
                ])
                ->leftJoin([$Staff->alias() => $Staff->table()], [
                    $Staff->aliasField('id = ') . $ReportCardsComments->aliasField('staff_id')
                ])
                ->where([$SubjectStudents->aliasField('institution_subject_id') => $institutionSubjectId])
                ->formatResults(function (ResultSetInterface $results) use ($academicPeriodId, $institutionId, $SubjectStudents, $AssessmentItemResults, $educationSubjectId, $ReportCards, $reportCardId,$institutionSubjectId) {

                    return $results->map(function ($row) use ($academicPeriodId, $institutionId, $SubjectStudents, $AssessmentItemResults, $educationSubjectId, $ReportCards, $reportCardId,$institutionSubjectId) {

                        $studentId = $row->student_id;
                        if (!empty($row['InstitutionStudentsReportCards']['report_card_id'])) {
                            $reportCardId = $row['InstitutionStudentsReportCards']['report_card_id'];
                        }

                        // Get the report card start/end date
                        $reportCardEntity = $ReportCards->find()
                            ->select([
                                $ReportCards->aliasField('start_date'),
                                $ReportCards->aliasField('end_date')
                            ])
                            ->where([
                                $ReportCards->aliasField('id') => $reportCardId
                            ])
                            ->all();

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
                            ->hydrate(false)
                            ->all();

                        // If subjectStudentsEntities is not empty mean the student have a subject
                        if (!$subjectStudentsEntities->isEmpty()) {

                            $studentEntity = $subjectStudentsEntities->first();

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
                                    'weightage' => $AssessmentItemResults->AssessmentPeriods->aliasField('weight')

                                ])
                                ->contain([
                                    'AssessmentPeriods'
                                ])
                                ->where([
                                    $AssessmentItemResults->aliasField('student_id') => $studentEntity['student_id'],
                                    $AssessmentItemResults->aliasField('education_subject_id') => $studentEntity['education_subject_id'],
                                    $AssessmentItemResults->AssessmentPeriods->aliasField('start_date').' >= ' => $row->reportCardStartDate,
                                    $AssessmentItemResults->AssessmentPeriods->aliasField('end_date').' <= ' => $row->reportCardEndDate,
                                    $AssessmentItemResults->aliasField('marks IS NOT NULL'),
                                    $AssessmentItemResults->aliasField('education_subject_id') => $studentEntity['education_subject_id']

                                ])
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
}
