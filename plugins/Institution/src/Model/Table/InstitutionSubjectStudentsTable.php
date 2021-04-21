<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\I18n\Date;
use Cake\I18n\Time;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\Utility\Text;

class InstitutionSubjectStudentsTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('InstitutionSubjects', ['className' => 'Institution.InstitutionSubjects']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
        $this->belongsTo('InstitutionStudents', [
            'className' => 'Institution.InstitutionStudents',
            'foreignKey' => [
                'education_grade_id',
                'student_id',
                'institution_id',
                'academic_period_id'
            ],
            'bindingKey' => [
                'education_grade_id',
                'student_id',
                'institution_id',
                'academic_period_id'
            ]
        ]);
        $this->belongsTo('ClassStudents', [
            'className' => 'Institution.InstitutionClassStudents',
            'foreignKey' => [
                'institution_class_id',
                'student_id'
            ],
            'bindingKey' => [
                'institution_class_id',
                'student_id'
            ]
        ]);

        $this->addBehavior('CompositeKey');

        $this->addBehavior('Restful.RestfulAccessControl', [
            'Results' => ['index', 'add'],
            'SubjectStudents' => ['index'],
            'OpenEMIS_Classroom' => ['index']
        ]);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.Students.afterSave'] = 'studentsAfterSave';
        $events['Model.InstitutionClassStudents.afterSave'] = 'institutionClassStudentsAfterSave';
        $events['Model.InstitutionClassStudents.afterDelete'] = 'institutionClassStudentsAfterDelete';
        $events['Model.AssessmentResults.afterSave'] = 'assessmentResultsAfterSave';
        return $events;
    }

    public function studentsAfterSave(Event $event, $student)
    {
        // saving of new subject students is handled by institutionClassStudentsAfterSave
        if (!$student->isNew()) {
            // to update student status in subject if student status in school has been changed
            $subjectStudents = $this->find()
                ->where([
                    $this->aliasField('institution_id') => $student->institution_id,
                    $this->aliasField('academic_period_id') => $student->academic_period_id,
                    $this->aliasField('student_id') => $student->student_id,
                    $this->aliasField('education_grade_id') => $student->education_grade_id
                ])->toArray();

            if (!empty($subjectStudents)) {
                foreach ($subjectStudents as $subjectStudentToSave) {
                    if ($subjectStudentToSave->student_status_id != $student->student_status_id) {
                        $subjectStudentToSave->student_status_id = $student->student_status_id;
                        $this->save($subjectStudentToSave);
                    }
                }
            }
        }
    }

    public function institutionClassStudentsAfterSave(Event $event, Entity $student)
    {
        if ($student->isNew()) {
            // to automatically add the student into class subjects when the student is added to a class
            $ClassSubjects = TableRegistry::get('Institution.InstitutionClassSubjects');
            $studentEducationGradeId = $student->education_grade_id;
            $classId = $student->institution_class_id;

            $classSubjectsData = $ClassSubjects->find()
                ->select([
                    'education_subject_id' => 'InstitutionSubjects.education_subject_id',
                    'education_grade_id' => 'InstitutionSubjects.education_grade_id',
                    'institution_subject_id' => 'InstitutionSubjects.id'
                ])
                ->innerJoinWith('InstitutionSubjects')
                ->where([$ClassSubjects->aliasField('institution_class_id') => $classId])
                ->toArray();

            $subjectStudent = $student->toArray();

            foreach ($classSubjectsData as $classSubject) {
                $isAutoAddSubject = $this->isAutoAddSubject($classSubject);
                $subjectEducationGradeId = $classSubject['education_grade_id'];

                // only add subjects that have auto_allocation flag set as true
                if ($isAutoAddSubject && $subjectEducationGradeId == $studentEducationGradeId) {
                    $subjectStudent['education_subject_id'] = $classSubject['education_subject_id'];
                    $subjectStudent['institution_subject_id'] = $classSubject['institution_subject_id'];

                    $entity = $this->newEntity($subjectStudent);
                    $this->save($entity);
                }
            }
        }
    }

    public function institutionClassStudentsAfterDelete(Event $event, Entity $student)
    {
        $deleteSubjectStudent = $this->find()
            ->where([
                $this->aliasField('student_id') => $student->student_id,
                $this->aliasField('institution_class_id') => $student->institution_class_id
            ])
            ->toArray();

        // delete one by one so that afterDelete() will be triggered
        foreach ($deleteSubjectStudent as $key => $value) {
            $this->delete($value);
        }
    }

    public function assessmentResultsAfterSave(Event $event, $results)
    {
        // used to update total mark whenever an assessment mark is added or updated
        $studentId = $results->student_id;
        $academicPeriodId = $results->academic_period_id;
        $educationSubjectId = $results->education_subject_id;
        $educationGradeId = $results->education_grade_id;
        $institutionId = $results->institution_id;

        $ItemResults = TableRegistry::get('Assessment.AssessmentItemResults');
        $totalMark = $ItemResults->getTotalMarks($studentId, $academicPeriodId, $educationSubjectId, $educationGradeId);

        if (!empty($totalMark)) {
            // update all records of student regardless of institution
            $modifiedUserId = (isset($event->data()[0]->modified_user_id) && $event->data()[0]->modified_user_id)?$event->data()[0]->modified_user_id:$event->data()[0]->created_user_id;
            $this->query()
                ->update()
                ->set([
                    'total_mark' => $totalMark->calculated_total,
                    'modified_user_id' => $modifiedUserId,
                    'modified' => Time::now()
                ])
                ->where([
                    'student_id' => $studentId,
                    'academic_period_id' => $academicPeriodId,
                    'education_subject_id' => $educationSubjectId,
                    'education_grade_id' => $educationGradeId
                ])
                ->execute();
        }
    }

    public function findResults(Query $query, array $options)
    {
        $institutionId = $options['institution_id'];
        $classId = $options['class_id'];
        $assessmentId = $options['assessment_id'];
        $periodId = $options['academic_period_id'];
        $subjectId = $options['subject_id'];
        $gradeId = $options['grade_id'];

        $Users = $this->Users;
        $InstitutionSubjects = $this->InstitutionSubjects;
        $StudentStatuses = $this->StudentStatuses;
        $ItemResults = TableRegistry::get('Assessment.AssessmentItemResults');
        
        return $query
            ->select([
                $ItemResults->aliasField('id'),
                $ItemResults->aliasField('marks'),
                $ItemResults->aliasField('assessment_grading_option_id'),
                $ItemResults->aliasField('assessment_period_id'),
                $this->aliasField('student_id'),
				$this->aliasField('institution_id'),
				$this->aliasField('academic_period_id'),
				$this->aliasField('education_grade_id'),
                $this->aliasField('student_status_id'),
                $this->aliasField('total_mark'),
                $Users->aliasField('openemis_no'),
                $Users->aliasField('first_name'),
                $Users->aliasField('middle_name'),
                $Users->aliasField('third_name'),
                $Users->aliasField('last_name'),
                $Users->aliasField('preferred_name'),
                $StudentStatuses->aliasField('code'),
                $StudentStatuses->aliasField('name')
            ])
            ->matching('Users')
            ->contain('StudentStatuses')
            ->innerJoin(
                [$InstitutionSubjects->alias() => $InstitutionSubjects->table()],
                [
                    $InstitutionSubjects->aliasField('id = ') . $this->aliasField('institution_subject_id'),
                    $InstitutionSubjects->aliasField('institution_id') => $institutionId,
                    $InstitutionSubjects->aliasField('academic_period_id') => $periodId,
                    $InstitutionSubjects->aliasField('education_subject_id') => $subjectId
                ]
            )
            ->leftJoin(
                [$ItemResults->alias() => $ItemResults->table()],
                [
                    $ItemResults->aliasField('student_id = ') . $this->aliasField('student_id'),
                    $ItemResults->aliasField('assessment_id') => $assessmentId,
                    $ItemResults->aliasField('academic_period_id') => $periodId,
                    $ItemResults->aliasField('education_subject_id') => $subjectId,
                    $ItemResults->aliasField('education_grade_id') => $gradeId
                ]
            )
            ->leftJoin(
                [$StudentStatuses->alias() => $StudentStatuses->table()],
                [
                   $this->aliasField('student_status_id = ') . $StudentStatuses->aliasField('id')
                ]
            )
            ->where([
                $InstitutionSubjects->aliasField('institution_id') => $institutionId,
                $this->aliasField('institution_class_id') => $classId,
                //$StudentStatuses->aliasField('code NOT IN ') => ['TRANSFERRED','WITHDRAWN']
            ])
            ->group([
                $this->aliasField('student_id'),
                $ItemResults->aliasField('assessment_period_id')
            ])
            ->order([
                $this->aliasField('student_id')
            ])
            ->formatResults(function ($results) {
                $arrResults = is_array($results) ? $results : $results->toArray();
                foreach ($arrResults as &$result) {
					
					$InstitutionStudents = TableRegistry::get('institution_students');
					$StudentStatuses = TableRegistry::get('student_statuses');
					
					$StudentStatusesData = $InstitutionStudents->find()
						->select([
							$InstitutionStudents->aliasField('student_status_id'),
							$StudentStatuses->aliasField('code'),
							$StudentStatuses->aliasField('name')
						])
						->innerJoin(
							[$StudentStatuses->alias() => $StudentStatuses->table()],
							[
							   $InstitutionStudents->aliasField('student_status_id = ') . $StudentStatuses->aliasField('id')
							]
						)
						->order([
							$InstitutionStudents->aliasField('created') => 'DESC'
						])
						->where([
							$InstitutionStudents->aliasField('student_id') => $result['student_id'],
							$InstitutionStudents->aliasField('institution_id') => $result['institution_id'],
							$InstitutionStudents->aliasField('academic_period_id') => $result['academic_period_id'],
							$InstitutionStudents->aliasField('education_grade_id') => $result['education_grade_id'],
						])
						->first();	
                    $result['student_status_id'] = $StudentStatusesData->student_status_id;
                    $result['student_status']['name'] = $StudentStatusesData->student_statuses['name'];
                }
                return $arrResults;
            });
    }

    //copy for POCOR-5758
    public function findStudentResults(Query $query, array $options)
    {   
                $institutionId = $options['institution_id'];
                $classId = $options['class_id'];
                $assessmentId = $options['assessment_id'];
                $periodId = $options['academic_period_id'];
                $subjectId = $options['subject_id'];
                $gradeId = $options['grade_id'];

                $Users = $this->Users;
                $InstitutionSubjects = $this->InstitutionSubjects;
                $StudentStatuses = $this->StudentStatuses;
                $ItemResults = TableRegistry::get('Assessment.AssessmentItemResults');
                
                $educationId = $InstitutionSubjects->find()->select('education_subject_id')->where(['id' => $subjectId])->first();

                return $query
                    ->select([
                        $ItemResults->aliasField('id'),
                        $ItemResults->aliasField('marks'),
                        $ItemResults->aliasField('assessment_grading_option_id'),
                        $ItemResults->aliasField('assessment_period_id'),
                        $this->aliasField('student_id'),
                        $this->aliasField('student_status_id'),
                        $this->aliasField('total_mark'),
                        $Users->aliasField('openemis_no'),
                        $Users->aliasField('first_name'),
                        $Users->aliasField('middle_name'),
                        $Users->aliasField('third_name'),
                        $Users->aliasField('last_name'),
                        $Users->aliasField('preferred_name'),
                        $StudentStatuses->aliasField('code'),
                        $StudentStatuses->aliasField('name')
                    ])
                    ->matching('Users')
                    ->contain('StudentStatuses')
                    ->innerJoin(
                        [$InstitutionSubjects->alias() => $InstitutionSubjects->table()],
                        [
                            $InstitutionSubjects->aliasField('id') => $subjectId,
                            $InstitutionSubjects->aliasField('institution_id') => $institutionId,
                            $InstitutionSubjects->aliasField('academic_period_id') => $periodId,
                        ]
                    )
                    ->leftJoin(
                        [$ItemResults->alias() => $ItemResults->table()],
                        [
                            $ItemResults->aliasField('student_id = ') . $this->aliasField('student_id'),
                            $ItemResults->aliasField('assessment_id') => $assessmentId,
                            $ItemResults->aliasField('academic_period_id') => $periodId,
                            $ItemResults->aliasField('education_subject_id') => $educationId->education_subject_id,
                            $ItemResults->aliasField('education_grade_id') => $gradeId
                        ]
                    )
                    ->leftJoin(
                        [$StudentStatuses->alias() => $StudentStatuses->table()],
                        [
                           $this->aliasField('student_status_id') => $StudentStatuses->aliasField('id')
                        ]
                    )
                    ->where([
                        $this->aliasField('institution_subject_id') => $subjectId,
                        $this->aliasField('institution_class_id') => $classId,
                        $InstitutionSubjects->aliasField('institution_id') => $institutionId,
                        /*$InstitutionSubjects->aliasField('institution_id') => $institutionId,
                        $this->aliasField('institution_class_id') => $classId,*/
                        //$StudentStatuses->aliasField('code NOT IN ') => ['TRANSFERRED','WITHDRAWN']
                    ])
                    ->group([
                        $this->aliasField('student_id')
                    ])
                    ->order([
                        $this->aliasField('student_id')
                    ])
                    ->formatResults(function ($results) {
                        $arrResults = is_array($results) ? $results : $results->toArray();
                        foreach ($arrResults as &$result) {
                            $result['student_status']['name'] = __($result['student_status']['name']);
                        }
                        return $arrResults;
                    });
    }

    public function findAssessmentResults(Query $query, array $options)
    {
        $institutionId = $options['institution_id'];
        $academicPeriodId = $options['academic_period_id'];
        $classId = $options['institution_class_id'];
        $assessmentId = $options['assessment_id'];
        $educationGradeId = $options['education_grade_id'];
        $educationSubjectId = $options['education_subject_id'];
        $assessmentPeriodId = $options['assessment_period_id'];

        $Users = $this->Users;
        $InstitutionSubjects = $this->InstitutionSubjects;
        $ItemResults = TableRegistry::get('Assessment.AssessmentItemResults');
        $GradingOptions = TableRegistry::get('Assessment.AssessmentGradingOptions');

        $query
            ->select([
                $ItemResults->aliasField('id'),
                $ItemResults->aliasField('marks'),
                $ItemResults->aliasField('assessment_grading_option_id'),
                $ItemResults->aliasField('assessment_period_id'),
                $GradingOptions->aliasField('code'),
                $GradingOptions->aliasField('name')
            ])
            ->matching('Users')
            ->innerJoin(
                [$InstitutionSubjects->alias() => $InstitutionSubjects->table()],
                [
                    $InstitutionSubjects->aliasField('id = ') . $this->aliasField('institution_subject_id'),
                    $InstitutionSubjects->aliasField('institution_id') => $institutionId,
                    $InstitutionSubjects->aliasField('academic_period_id') => $academicPeriodId,
                    $InstitutionSubjects->aliasField('education_subject_id') => $educationSubjectId
                ]
            )
            ->leftJoin(
                [$ItemResults->alias() => $ItemResults->table()],
                [
                    $ItemResults->aliasField('student_id = ') . $this->aliasField('student_id'),
                    $ItemResults->aliasField('academic_period_id') => $academicPeriodId,
                    $ItemResults->aliasField('assessment_id') => $assessmentId,
                    $ItemResults->aliasField('education_grade_id') => $educationGradeId,
                    $ItemResults->aliasField('education_subject_id') => $educationSubjectId,
                    $ItemResults->aliasField('assessment_period_id') => $assessmentPeriodId
                ]
            )
            ->leftJoin(
                [$GradingOptions->alias() => $GradingOptions->table()],
                [
                    $GradingOptions->aliasField('id = ') . $ItemResults->aliasField('assessment_grading_option_id')
                ]
            )
            ->where([
                $this->aliasField('institution_class_id') => $classId
            ])
            ->group([
                $this->aliasField('student_id')
            ])
            ->order([
                $Users->aliasField('first_name'), $Users->aliasField('last_name')
            ])
            ->autoFields(true);

        return $query;
    }

    public function getMaleCountBySubject($subjectId)
    {
        $gender_id = 1; // male
        $count = $this
            ->find()
            ->contain('Users')
            ->matching('StudentStatuses', function ($q) {
                return $q->where(['StudentStatuses.code NOT IN' => ['TRANSFERRED', 'WITHDRAWN']]);
            })
            ->where([$this->Users->aliasField('gender_id') => $gender_id])
            ->where([$this->aliasField('institution_subject_id') => $subjectId])
            ->count()
        ;
        return $count;
    }

    public function getFemaleCountBySubject($subjectId)
    {
        $gender_id = 2; // female
        $count = $this
            ->find()
            ->contain('Users')
            ->matching('StudentStatuses', function ($q) {
                return $q->where(['StudentStatuses.code NOT IN' => ['TRANSFERRED', 'WITHDRAWN']]);
            })
            ->where([$this->Users->aliasField('gender_id') => $gender_id])
            ->where([$this->aliasField('institution_subject_id') => $subjectId])
            ->count()
        ;
        return $count;
    }
    
    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if($entity->isNew() || $entity->dirty('student_status_id')) {
            $id = $entity->institution_subject_id;
            $countMale = $this->getMaleCountBySubject($id);
            $countFemale = $this->getFemaleCountBySubject($id);
            $this->InstitutionSubjects->updateAll(['total_male_students' => $countMale, 'total_female_students' => $countFemale], ['id' => $id]);
        }
    }

    public function afterDelete(Event $event, Entity $entity, ArrayObject $options)
    {   
        $id = $entity->institution_subject_id;
        $countMale = $this->getMaleCountBySubject($id);
        $countFemale = $this->getFemaleCountBySubject($id);
        $this->InstitutionSubjects->updateAll(['total_male_students' => $countMale, 'total_female_students' => $countFemale], ['id' => $id]);
    
        // Disabled this logic because results should never be deleted when removing students from subjects

        //PHPOE-2338 - implement afterDelete to delete records in AssessmentItemResultsTable
        // find related classes and grades
        // $InstitutionSubjects = TableRegistry::get('Institution.InstitutionSubjects');
        // $institutionClassData = $InstitutionSubjects->find()
        //     ->contain('Classes.ClassGrades')
        //     ->where([$InstitutionSubjects->aliasField($InstitutionSubjects->primaryKey()) => $entity->institution_subject_id])
        //     ->first()
        //     ;
        // $gradeArray = [];
        // if (!empty($institutionClassData->institution_classes)) {
        //     foreach ($institutionClassData->institution_classes as $skey => $svalue) {
        //         if (!empty($svalue->institution_class_grades)) {
        //             foreach ($svalue->institution_class_grades as $gkey => $gvalue) {
        //                 $gradeArray[] = $gvalue->education_grade_id;
        //             }
        //         }
        //     }
        // }
        // $gradeArray = array_unique($gradeArray);

        // $AssessmentItemResults = TableRegistry::get('Assessment.AssessmentItemResults');
        // conditions: 'assessment_item_results' removing from student_id, institution_id, academic_period_id, assessment_item_id->education_subject_id;
        // $deleteAssessmentItemResults = $AssessmentItemResults->find()
        //     ->where([
        //         $AssessmentItemResults->aliasField('student_id') => $entity->student_id,
        //         $AssessmentItemResults->aliasField('institution_id') => $institutionClassData->institution_id,
        //         $AssessmentItemResults->aliasField('academic_period_id') => $institutionClassData->academic_period_id,
        //         $AssessmentItemResults->aliasField('education_subject_id') => $institutionClassData->education_subject_id,
        //         $AssessmentItemResults->aliasField('education_grade_id') => $entity->education_grade_id
        //     ])
        //     ;

        // foreach ($deleteAssessmentItemResults as $key => $value) {
            // $AssessmentItemResults->delete($value);
        // }
    }

    public function getEnrolledStudentBySubject($period, $class, $subject)
    {
        $StudentStatuses = TableRegistry::get('Student.StudentStatuses');
        $enrolled = $StudentStatuses->getIdByCode('CURRENT');

        $Users = $this->Users;

        $students = $this
                    ->find()
                    ->matching('Users')
                    ->matching('ClassStudents', function ($q) use ($enrolled) {
                        return $q->where([
                            'ClassStudents.student_status_id' => $enrolled
                        ]);
                    })
                    ->where([
                        $this->aliasField('academic_period_id') => $period,
                        $this->aliasField('institution_class_id') => $class,
                        $this->aliasField('education_subject_id') => $subject
                    ])
                    ->select([
                        $this->aliasField('student_id'),
                        $Users->aliasField('openemis_no'),
                        $Users->aliasField('first_name'),
                        $Users->aliasField('middle_name'),
                        $Users->aliasField('third_name'),
                        $Users->aliasField('last_name'),
                        $Users->aliasField('preferred_name')
                    ])
                    ->toArray();

        $studentList = [];
        foreach ($students as $key => $value) {
            $studentList[$value->student_id] = $value->_matchingData['Users']['name_with_id'];
        }

        return $studentList;
    }

    public function getStudentClassGradeDetails($period, $institution, $student, $subject) //function return class and grade of student.
    {
        return  $this
                ->find()
                ->innerJoin(
                    ['InstitutionClassGrades' => 'institution_class_grades'],
                    [
                        'InstitutionClassGrades.institution_class_id = ' . $this->aliasField('institution_class_id'),
                    ]
                )
                ->where([
                    $this->aliasField('student_id') => $student,
                    $this->aliasField('institution_id') => $institution,
                    $this->aliasField('academic_period_id') => $period,
                    $this->aliasField('education_subject_id') => $subject
                ])
                ->select([
                    $this->aliasField('institution_class_id'),
                    'education_grade_id' => 'InstitutionClassGrades.education_grade_id'
                ])
                ->toArray();
    }

    private function isAutoAddSubject($subject)
    {
        $EducationGradesSubjects = TableRegistry::get('Education.EducationGradesSubjects');
        $educationGradeId = $subject['education_grade_id'];
        $educationSubjectId = $subject['education_subject_id'];

        $educationGradesSubjectsData = $EducationGradesSubjects->find()
            ->where([
                $EducationGradesSubjects->aliasField('education_grade_id') => $educationGradeId,
                $EducationGradesSubjects->aliasField('education_subject_id') => $educationSubjectId
            ])
            ->first();
        $autoAddSubject = $educationGradesSubjectsData['auto_allocation'];

        return $autoAddSubject;
    }
}
