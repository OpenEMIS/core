<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\I18n\Time;
use Cake\Utility\Text;
use App\Model\Table\AppTable;

class InstitutionClassStudentsTable extends AppTable {

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

    public function initialize(array $config) {
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

        $this->addBehavior('Excel', [
            'excludes' => ['id'],
            'pages' => ['index'],
            'orientation' => 'landscape'
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
                $classStudent->student_status_id = $student->student_status_id;
                $this->save($classStudent);
            }
        }
    }

    public function onExcelBeforeGenerate(Event $event, ArrayObject $settings) {
        $classId = $this->request->query('class_id');
        $institutionId = $this->Session->read('Institution.Institutions.id');
        $institutionCode = $this->Institutions->get($institutionId)->code;
        $className = $this->InstitutionClasses->get($classId)->name;
        $settings['file'] = str_replace($this->alias(), str_replace(' ', '_', $institutionCode).'-'.str_replace(' ', '_', $className).'_Results', $settings['file']);
    }

    public function onExcelBeforeStart (Event $event, ArrayObject $settings, ArrayObject $sheets) {
        $classId = $this->request->query('class_id');
        $AccessControl = $this->AccessControl;
        $userId = $this->Session->read('Auth.User.id');
        $institutionId = $this->Session->read('Institution.Institutions.id');
        $roles = $this->Institutions->getInstitutionRoles($userId, $institutionId);
        $allSubjectsPermission = true;
        $mySubjectsPermission = true;
        if (!$AccessControl->isAdmin())
        {
            if (!$AccessControl->check(['Institutions', 'AllSubjects', 'index'], $roles) ) {
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
            'assessmentId' => $this->request->query('assessment_id'),
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

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $originalField) {
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
        foreach($assessmentSubjects as $subject) {
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

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, $query) {
        $sheet = $settings['sheet'];
        $institutionId = $sheet['institutionId'];
        $allClassesPermission = $sheet['allClassesPermission'];
        $allSubjectsPermission = $sheet['allSubjectsPermission'];
        $myClassesPermission = $sheet['myClassesPermission'];
        $mySubjectsPermission = $sheet['mySubjectsPermission'];
        $assessmentId = $sheet['assessmentId'];
        $staffId = $sheet['staffId'];

        $query
            ->contain([
                'InstitutionClasses.Institutions',
                'Users.BirthplaceAreas',
                'Users.Nationalities.NationalitiesLookUp'
            ])
            ->innerJoin(['InstitutionClassGrades' => 'institution_class_grades'], [
                'InstitutionClassGrades.institution_class_id = '.$this->aliasField('institution_class_id')
            ])
            ->innerJoin(['Assessments' => 'assessments'],[
                'Assessments.education_grade_id = InstitutionClassGrades.education_grade_id',
                'Assessments.id' => $assessmentId
            ])
            ->select(['code' => 'Institutions.code', 'institution_id' => 'Institutions.name', 'openemis_number' => 'Users.openemis_no', 'birth_place_area' => 'BirthplaceAreas.name', 'dob' => 'Users.date_of_birth', 'class_name' => 'InstitutionClasses.name'])
            ->where([$this->aliasField('institution_id') => $institutionId])
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
                    }
                    // If only subject permission is available
                    else {
                        $query->where(['InstitutionSubjectStaff.staff_id' => $staffId]);
                    }
                }
            }
        }
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options) {
        if ($entity->isNew()) {
            $entity->id = Text::uuid();
        }
    }

    public function onExcelRenderSubject(Event $event, Entity $entity, array $attr) {
        $studentId = $entity->student_id;
        $subjectId = $attr['subjectId'];
        $assessmentId = $attr['assessmentId'];
        $academicPeriodId = $attr['academicPeriodId'];
        $institutionId = $attr['institutionId'];
        $resultType = $attr['resultType'];
        $assessmentPeriodId = $attr['assessmentPeriodId'];
        $assessmentItemResults = $this->assessmentItemResults;
        if (!(isset($assessmentItemResults[$institutionId][$studentId][$subjectId][$assessmentPeriodId]))) {
            $AssessmentItemResultsTable = TableRegistry::get('Assessment.AssessmentItemResults');
            $this->assessmentItemResults = $AssessmentItemResultsTable->getAssessmentItemResults($institutionId, $academicPeriodId, $assessmentId, $subjectId);
            $assessmentItemResults = $this->assessmentItemResults;
        }
        $allSubjectsPermission = $this->allSubjectsPermission;
        $mySubjectsPermission = $this->mySubjectsPermission;
        $staffId = $this->staffId;
        $printedResult = '';
        $renderResult = true;
        if (!$allSubjectsPermission && !$mySubjectsPermission) {
            $printedResult = __('No Access');
            $renderResult = false;
        } else if (!$allSubjectsPermission && $mySubjectsPermission) {
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
            if (isset($assessmentItemResults[$institutionId][$studentId][$subjectId][$assessmentPeriodId])) {
                $result = $assessmentItemResults[$institutionId][$studentId][$subjectId][$assessmentPeriodId];
                switch($resultType) {
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

    public function onExcelRenderNationality(Event $event, Entity $entity, array $attr) {
        if ($entity->user->nationalities) {
            $nationalities = $entity->user->nationalities;
            $allNationalities = '';
            foreach($nationalities as $nationality) {
                $allNationalities .= $nationality->nationalities_look_up->name . ', ';
            }
            return rtrim($allNationalities, ', ');
        } else {
            return '';
        }
    }

    public function onExcelRenderAssessmentPeriodWeightedMark(Event $event, Entity $entity, array $attr) {
        $assessmentPeriodWeightedMark = $this->assessmentPeriodWeightedMark;
        $this->totalMark += $assessmentPeriodWeightedMark;
        $this->totalWeightedMark += ($assessmentPeriodWeightedMark * $attr['subjectWeight']);

        // reset the assessmentPeriodWeightedMark mark
        $this->assessmentPeriodWeightedMark = 0;

        return ' '.$assessmentPeriodWeightedMark;
    }

    public function onExcelRenderTotalWeightedMark(Event $event, Entity $entity, array $attr) {
        $totalWeightedMark = $this->totalWeightedMark;
        $this->totalWeightedMark = 0;
        return ' '.$totalWeightedMark;
    }

    public function onExcelRenderTotalMark(Event $event, Entity $entity, array $attr) {
        $totalMark = $this->totalMark;
        $this->totalMark = 0;
        return ' '.$totalMark;
    }

    public function getMaleCountByClass($classId) {
        $gender_id = 1; // male
        $count = $this
            ->find()
            ->contain('Users')
            ->where([$this->Users->aliasField('gender_id') => $gender_id])
            ->where([$this->aliasField('institution_class_id') => $classId])
            ->count()
        ;
        return $count;
    }

    public function getFemaleCountByClass($classId) {
        $gender_id = 2; // female
        $count = $this
            ->find()
            ->contain('Users')
            ->where([$this->Users->aliasField('gender_id') => $gender_id])
            ->where([$this->aliasField('institution_class_id') => $classId])
            ->count()
        ;
        return $count;
    }

    public function autoInsertClassStudent($data) {
        $studentId = $data['student_id'];
        $gradeId = $data['education_grade_id'];
        $classId = $data['institution_class_id'];
        $data['subject_students'] = $this->_setSubjectStudentData($data);
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

    private function _setSubjectStudentData($data) {

        $ClassSubjects = TableRegistry::get('Institution.InstitutionClassSubjects');

        //get the education_subject_id and education_subject_id using the institution_id
        $classSubjectsData = $ClassSubjects->find()
            ->innerJoinWith('InstitutionSubjects')
            ->select([
                'education_subject_id' => 'InstitutionSubjects.education_subject_id',
                'institution_subject_id' => 'InstitutionSubjects.id'
            ])
            ->where([
                $ClassSubjects->aliasField('institution_class_id') => $data['institution_class_id']
            ])
            ->toArray();

        $subjectStudents = [];
        foreach ($classSubjectsData as $classSubjects) {

            $subjectStudents[] = [
                'status' => 1,
                'student_id' => $data['student_id'],
                'institution_subject_id' => $classSubjects['institution_subject_id'],
                'institution_class_id' => $data['institution_class_id'],
                'institution_id' => $data['institution_id'],
                'academic_period_id' => $data['academic_period_id'],
                'education_subject_id' => $classSubjects['education_subject_id'],
            ];
        }

        return $subjectStudents;
    }

    public function afterDelete(Event $event, Entity $entity, ArrayObject $options) {
        // PHPOE-2338 - implement afterDelete in InstitutionClassStudentsTable.php to delete from InstitutionSubjectStudentsTable
        $this->_autoDeleteSubjectStudent($entity);
    }

    private function _autoDeleteSubjectStudent(Entity $entity) {
        $InstitutionSubjectStudentsTable = TableRegistry::get('Institution.InstitutionSubjectStudents');
        $deleteSubjectStudent = $InstitutionSubjectStudentsTable->find()
            ->where([
                $InstitutionSubjectStudentsTable->aliasField('student_id') => $entity->student_id,
                $InstitutionSubjectStudentsTable->aliasField('institution_class_id') => $entity->institution_class_id
            ])
            ->toArray();

        // have to delete one by one so that InstitutionSubjectStudents->afterDelete() will be triggered
        foreach ($deleteSubjectStudent as $key => $value) {
            $InstitutionSubjectStudentsTable->delete($value);
        }
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

}
