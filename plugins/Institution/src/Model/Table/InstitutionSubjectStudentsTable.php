<?php

namespace Institution\Model\Table;

use ArrayObject;
use Cake\Datasource\ResultSetInterface;
use Cake\I18n\Date;
use Cake\Datasource\Exception\RecordNotFoundException;
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
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects', 'foreignKey' => 'education_subject_id']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses', 'foreignKey' => 'student_status_id']);
        $this->belongsTo('InstitutionClassStudents', ['className' => 'Institution.InstitutionClassStudents']);

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
        }/*POCOR-6542 starts*/ else {
            //update student status in subject when student transferred to another intitution and have graducate status
            $subjectStudent = $this->find()
                ->matching('InstitutionSubjects')
                ->where([
                    'InstitutionSubjects.institution_id' => $student->institution_id,
                    'InstitutionSubjects.academic_period_id' => $student->academic_period_id,
                    $this->aliasField('education_grade_id') => $student->education_grade_id,
                    $this->aliasField('student_id') => $student->student_id,
                ])->first();
            $StudentStatuses = TableRegistry::get('Student.StudentStatuses');
            $statuses = $StudentStatuses->findCodeList();

            if (!empty($subjectStudent) && $subjectStudent->student_status_id != $student->student_status_id) {
                if ($subjectStudent->student_status_id == $statuses['GRADUATED']) {
                    $subjectStudent->student_status_id = $statuses['GRADUATED'];
                } else {
                    $subjectStudent->student_status_id = $student->student_status_id;
                }
                $this->save($subjectStudent);
            }
        }
        /*POCOR-6542 ends*/
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
                    $countMale = $this->getMaleCountBySubject($classSubject['institution_subject_id']);
                    $countFemale = $this->getFemaleCountBySubject($classSubject['institution_subject_id']);
                    $this->InstitutionSubjects->updateAll(['total_male_students' => $countMale, 'total_female_students' => $countFemale], ['id' => $classSubject['institution_subject_id']]);
                }
            }
        }
    }

    public function institutionClassStudentsAfterDelete(Event $event, Entity $student)
    {   //comment delete code for POCOR-6468 starts
        /*$deleteSubjectStudent = $this->find()
            ->where([
                $this->aliasField('student_id') => $student->student_id,
                $this->aliasField('institution_class_id') => $student->institution_class_id
            ])
            ->toArray();

        // delete one by one so that afterDelete() will be triggered
        foreach ($deleteSubjectStudent as $key => $value) {
            $this->delete($value);
        }*///comment delete code for POCOR-6468 ends
    }

    public function assessmentResultsAfterSave(Event $event, $results)
    {
        // used to update total mark whenever an assessment mark is added or updated
        $studentId = $results->student_id;
        $academicPeriodId = $results->academic_period_id;
        $educationSubjectId = $results->education_subject_id;
        $educationGradeId = $results->education_grade_id;
        $institutionId = $results->institution_id;
        $institutionClassesId = $results->institution_classes_id; //POCOR-6479
        $assessmentPeriodId = $results->assessment_period_id; //POCOR-6479

        $ItemResults = TableRegistry::get('Assessment.AssessmentItemResults');
        $totalMark = $ItemResults->getTotalMarks($studentId, $academicPeriodId, $educationSubjectId, $educationGradeId, $institutionClassesId, $assessmentPeriodId, $institutionId);//POCOR-6479

        if (!empty($totalMark)) {
            // update all records of student regardless of institution
            $modifiedUserId = (isset($event->data()[0]->modified_user_id) && $event->data()[0]->modified_user_id) ? $event->data()[0]->modified_user_id : $event->data()[0]->created_user_id;

            $this->query()
                ->update()
                ->set([
                    // 'total_mark' => $totalMark->calculated_total,//POCOR-6479 comment code
                    'total_mark' => $totalMark,//POCOR-6479
                    'modified_user_id' => $modifiedUserId,
                    'modified' => Time::now()
                ])
                ->where([
                    'student_id' => $studentId,
                    'academic_period_id' => $academicPeriodId,
                    'education_subject_id' => $educationSubjectId,
                    'education_grade_id' => $educationGradeId,
                    //'institution_class_id' => $institutionClassesId,//POCOR-6479 comment this code of POCOR-6468  
                ])
                ->execute();
            //update created date in assesment_item_results table  //POCOR-6573 starts  
            $ItemResults->updateAll(['created' => Time::now()],
                [
                    'student_id' => $studentId,
                    'academic_period_id' => $academicPeriodId,
                    'education_subject_id' => $educationSubjectId,
                    'education_grade_id' => $educationGradeId,
                    'institution_classes_id' => $institutionClassesId,
                    'assessment_period_id' => $assessmentPeriodId,
                    'institution_id' => $institutionId
                ]); //POCOR-6573 ends
        }
    }

    public function findInstitutionSubjectStudentsTableResults(Query $query, array $options)
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
        $InstitutionClassStudents = TableRegistry::get('institution_class_students');//POCOR-6468 starts
        $enrolledStatus = TableRegistry::get('Student.StudentStatuses')->getIdByCode('CURRENT');//POCOR-6468 starts

        $query
            ->select([
                $ItemResults->aliasField('id'),
                $ItemResults->aliasField('marks'),
                $ItemResults->aliasField('assessment_grading_option_id'),
                $ItemResults->aliasField('assessment_period_id'),
                $this->aliasField('student_id'),
                $this->aliasField('institution_id'),
                $this->aliasField('academic_period_id'),
                $this->aliasField('education_grade_id'),
                $this->aliasField('education_subject_id'),//POCOR-6479 
                $this->aliasField('student_status_id'),
                $this->aliasField('total_mark'),
                $Users->aliasField('id'),
                $Users->aliasField('openemis_no'),
                $Users->aliasField('first_name'),
                $Users->aliasField('middle_name'),
                $Users->aliasField('third_name'),
                $Users->aliasField('last_name'),
                $Users->aliasField('preferred_name'),
                $StudentStatuses->aliasField('code'),
                $StudentStatuses->aliasField('name')
            ])
            ->matching('Users')->formatResults(function (\Cake\Collection\CollectionInterface $results) {
                return $results->map(function ($row) {
                    unset($row['Users']['name_with_id']);
                    unset($row['Users']['name_with_id_role']);
                    unset($row['Users']['default_identity_type']);
                    unset($row['Users']['has_special_needs']);
                    // Remove any other virtual fields you want to exclude
                    return $row;
                });
            })
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
            )//POCOR-6468 starts
            ->innerJoin(
                [$InstitutionClassStudents->alias() => $InstitutionClassStudents->table()],
                [
                    $InstitutionClassStudents->aliasField('student_id = ') . $this->aliasField('student_id'),
                    $InstitutionClassStudents->aliasField('institution_class_id =') . $this->aliasField('institution_class_id')
                    //POCOR-6503[START]
                    //$InstitutionClassStudents->aliasField('student_status_id') => $enrolledStatus,
                    //POCOR-6503[END]
                ]
            )//POCOR-6468 ends
            ->where([
                $InstitutionSubjects->aliasField('institution_id') => $institutionId,
                $this->aliasField('education_grade_id') => $gradeId,
                $this->aliasField('institution_class_id') => $classId, //POCOR-6479 
                //$StudentStatuses->aliasField('code NOT IN ') => ['TRANSFERRED','WITHDRAWN']//POCOR-6479 
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
                            $InstitutionStudents->aliasField('student_status_id') => $result['student_status_id'],
                        ])
                        ->first();
                    $result['student_status_id'] = $StudentStatusesData->student_status_id;
                    $result['student_status']['name'] = $StudentStatusesData->student_statuses['name'];
                }
                return $arrResults;
            });
        //POCOR-6479 starts    
        $query->formatResults(function ($results1) {
            $arrResults1 = is_array($results1) ? $results1 : $results1->toArray();
            foreach ($arrResults1 as &$result) {
                $assessmentItemResults = TableRegistry::get('assessment_item_results');
                $assessmentItemResultsData = $assessmentItemResults->find()
                    ->select([
                        $assessmentItemResults->aliasField('marks')
                    ])
                    ->order([
                        $assessmentItemResults->aliasField('modified') => 'DESC',
                        $assessmentItemResults->aliasField('created') => 'DESC'
                    ])
                    ->where([
                        $assessmentItemResults->aliasField('student_id') => $result['student_id'],
                        $assessmentItemResults->aliasField('academic_period_id') => $result['academic_period_id'],
                        $assessmentItemResults->aliasField('education_grade_id') => $result['education_grade_id'],
                        $assessmentItemResults->aliasField('assessment_period_id') => $result['AssessmentItemResults']['assessment_period_id'],
                        $assessmentItemResults->aliasField('education_subject_id') => $result['education_subject_id'],
                    ])
                    ->first();
                $result['AssessmentItemResults']['marks'] = $assessmentItemResultsData->marks;
            }
            return $arrResults1;
        }); //POCOR-6479 ends     
        return $query;
    }

    /*
     * $isst = Cake\ORM\TableRegistry::get('Institution.InstitutionSubjectStudents');
     * $query = $isst->find('studentResults', ['institution_class_id' => 583, 'education_subject_id' => 60]);
     * $array = $query->toArray();
     */
    public function findStudentResults(Query $query, array $options)
    {
// POCOR-7419-KHINDOL
//        $this->log('findStudentResults', 'debug');
        $institution_id = self::getFromArray($options, 'institution_id');
        $institution_class_id = self::getFromArray($options, 'institution_class_id'); //568
        $assessment_id = self::getFromArray($options, 'assessment_id');
        $academic_period_id = self::getFromArray($options, 'academic_period_id');
        $education_subject_id = self::getFromArray($options, 'education_subject_id'); //60
        $institution_subject_id = self::getFromArray($options, 'institution_subject_id'); //60
        $education_grade_id = self::getFromArray($options, 'education_grade_id');
        $archive = self::getFromArray($options, 'archive');
        if($archive){
            $archive = true;
        }
//        $this->log('findStudentResults', 'debug');
//        $this->log($archive, 'debug');
//        $this->log($institution_subject_id, 'debug');
//        $this->log($query->sql(), 'debug');

        if ($institution_subject_id) {
            if (!$education_subject_id) {
                $institution_subject = self::getRelatedRecord('institution_subjects', $institution_subject_id);
                $education_subject_id = $institution_subject['education_subject_id'];
            }
        }
//        $this->log("$institution_id = $institution_class_id = $assessment_id = $academic_period_id = $education_subject_id ", 'debug');
//        $this->log("$institution_subject_id = $education_grade_id = $archive", 'debug');
        $Results = TableRegistry::get('Assessment.AssessmentItemResults');
        $AssessmentPeriods = TableRegistry::get('assessment_periods');
        $where = [
            $this->aliasField('education_subject_id') => $education_subject_id,
            $this->aliasField('institution_class_id') => $institution_class_id,
            $this->aliasField('institution_id') => $institution_id,
            $this->aliasField('education_grade_id') => $education_grade_id,
            $this->aliasField('academic_period_id') => $academic_period_id,
        ];
        if(!$archive){
            $where[$this->StudentStatuses->aliasField('code NOT IN ')] = ['TRANSFERRED', 'WITHDRAWN', 'REPEATED'];
        }
        return $query
            ->select([
                $this->aliasField('student_id'),
                $this->aliasField('student_status_id'),
                $this->aliasField('total_mark'),
                $this->aliasField('academic_period_id'),
                $this->aliasField('education_grade_id'),
                $this->aliasField('education_subject_id'),
                $this->StudentStatuses->aliasField('name'),
                $this->StudentStatuses->aliasField('code'),
                'assessment_id' => $AssessmentPeriods->aliasField('assessment_id'),
                'assessment_period_id' => $AssessmentPeriods->aliasField('id'),
            ])
            ->contain('StudentStatuses')
            ->where($where)
            ->group([
                $this->aliasField('student_id'),
                $AssessmentPeriods->aliasField('id'),
            ])
            ->innerJoin(
                [$AssessmentPeriods->alias() => $AssessmentPeriods->table()],
                [
                    $AssessmentPeriods->aliasField('assessment_id = ') . $assessment_id,
//                    $ItemResults->aliasField('assessment_id') => $assessment_id,
//                    $ItemResults->aliasField('academic_period_id') => $academic_period_id,
//                    $ItemResults->aliasField('education_subject_id') => $education_subject_id,
//                    $ItemResults->aliasField('education_grade_id') => $education_grade_id
                ]
            )
            ->formatResults(function (ResultSetInterface $results) use ($Results, $archive) {
                return $results->map(function ($row) use ($Results, $archive) {
                    $academic_period_id = $row->academic_period_id;
                    $education_subject_id = $row->education_subject_id;
                    $education_grade_id = $row->education_grade_id;
                    $student_id = $row->student_id;
                    $assessment_id = $row->assessment_id;
                    $assessment_period_id = $row->assessment_period_id;
                    $options = ["student_id" => $student_id,
                        "academic_period_id" => $academic_period_id,
                        "education_grade_id" => $education_grade_id,
                        "education_subject_id" => $education_subject_id,
                        "id" => -1,
                        "assessment_grading_option_id" => "-1",
                        "assessment_period_id" => $assessment_period_id,
                        'assessment_id' => $assessment_id];
                    $marks = $Results::getLastMark($options, $archive);
                    $mark = $marks[0];
                    $row['mark_id'] = self::getFromArray($mark, 'id');
                    $row['mark'] = self::getFromArray($mark, 'marks');
                    $row['academic_period_id'] = self::getFromArray($mark, 'academic_period_id');
                    $row['education_grade_id'] = self::getFromArray($mark, 'education_grade_id');
                    $row['education_subject_id'] = self::getFromArray($mark, 'education_subject_id');
                    $row['assessment_grading_option_id'] = self::getFromArray($mark, 'assessment_grading_option_id');
                    $row['assessment_period_id'] = $assessment_period_id;
                    $student = self::getRelatedRecord('User.Users', $row->student_id);
                    $student_name = $student['name'];
                    $student_code = $student['openemis_no'];
                    $row['the_student_name'] = $student_name;
                    $row['the_student_code'] = $student_code;
                    $row['student_status_name'] = __($row->student_status->name);
                    $row['student_status_code'] = $row->student_status->code;
//                    $row['student_status_id'] = $row->student_status->id;
//                    print_r($row);
                    return $row;
                });
            });

        //POCOR-6573 starts
//        return $query
//            ->select([
//                $ItemResults->aliasField('id'),
//                $ItemResults->aliasField('marks'),//POCOR-6573 starts
//                $ItemResults->aliasField('academic_period_id'),
//                $ItemResults->aliasField('education_grade_id'),
//                $ItemResults->aliasField('education_subject_id'),
//                $ItemResults->aliasField('assessment_grading_option_id'),
//                $ItemResults->aliasField('assessment_period_id'),//POCOR-6573 ends
//                $ItemResults->aliasField('assessment_grading_option_id'),
//                $ItemResults->aliasField('assessment_period_id'),
//                $this->aliasField('student_id'),
//                $this->aliasField('student_status_id'),
//                $this->aliasField('total_mark'),
////                $Users->aliasField('id'),
//                $Users->aliasField('openemis_no'),
//                $Users->aliasField('first_name'),
//                $Users->aliasField('middle_name'),
//                $Users->aliasField('third_name'),
//                $Users->aliasField('last_name'),
//                $Users->aliasField('preferred_name'),
//                $StudentStatuses->aliasField('code'),
//                $StudentStatuses->aliasField('name')
//            ])
//            ->matching('Users')
//            ->contain('StudentStatuses')
//            ->innerJoin(
//                [$InstitutionSubjects->alias() => $InstitutionSubjects->table()],
//                [
//                    $InstitutionSubjects->aliasField('id') => $education_subject_id,
//                    $InstitutionSubjects->aliasField('institution_id') => $institution_id,
//                    $InstitutionSubjects->aliasField('academic_period_id') => $academic_period_id,
//                ]
//            )
//            ->leftJoin(
//                [$ItemResults->alias() => $ItemResults->table()],
//                [
//                    $ItemResults->aliasField('student_id = ') . $this->aliasField('student_id'),
//                    $ItemResults->aliasField('assessment_id') => $assessment_id,
//                    $ItemResults->aliasField('academic_period_id') => $academic_period_id,
//                    $ItemResults->aliasField('education_subject_id') => $educationId->education_subject_id,
//                    $ItemResults->aliasField('education_grade_id') => $education_grade_id
//                ]
//            )
//            ->leftJoin(
//                [$StudentStatuses->alias() => $StudentStatuses->table()],
//                [
//                    $this->aliasField('student_status_id') => $StudentStatuses->aliasField('id')
//                ]
//            )//POCOR-6572 starts
//            ->innerJoin(
//                [$InstitutionClassStudents->alias() => $InstitutionClassStudents->table()],
//                [
//                    $InstitutionClassStudents->aliasField('student_id = ') . $this->aliasField('student_id')
//                ]
//            )//POCOR-6572 ends
//            ->where([
//                $this->aliasField('institution_subject_id') => $education_subject_id,
//                $this->aliasField('institution_class_id') => $institution_class_id,
//                $InstitutionSubjects->aliasField('institution_id') => $institution_id,
//                $InstitutionClassStudents->aliasField('institution_class_id') => $institution_class_id,//POCOR-6572
//                $InstitutionClassStudents->aliasField('institution_id') => $institution_id,//POCOR-6572
//                $StudentStatuses->aliasField('code NOT IN ') => ['TRANSFERRED','WITHDRAWN', 'REPEATED']//POCOR-6687 - uncommented status condition because it was showing "repeated status" student
//            ])
//            ->group([
//                $this->aliasField('student_id'),
//            ])
//            ->formatResults(function ($results) {
//                $arrResults = is_array($results) ? $results : $results->toArray();
//                foreach ($arrResults as &$result) {
//                    $result['student_status']['name'] = __($result['student_status']['name']);
//                }
//                return $arrResults;
//            })
//            //POCOR-6573 starts
//POCOR-6573 ends
    }

    /**
     * common proc to show related field with id in the index table
     * @param $tableName
     * @param $relatedField
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public static function getRelatedRecord($tableName, $relatedField)
    {
        if (!$relatedField) {
            return "";
        }
        $Table = TableRegistry::get($tableName);
        try {
            $related = $Table->get($relatedField);
            return $related->toArray();
        } catch (RecordNotFoundException $e) {
            return $relatedField;
        }
        return $relatedField;
    }

    /**
     * common proc to show related field with id in the index table
     * @param $tableName
     * @param $relatedField
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public static function getRelatedName($tableName, $relatedField)
    {
        if (!$relatedField) {
            return "";
        }
        $Table = TableRegistry::get($tableName);
        try {
            $related = $Table->get($relatedField);
            return $related->name;
        } catch (RecordNotFoundException $e) {
            return 'RecordNotFoundException';
        }
        return 'Not tried';
    }

    private static function getFromArray($array, $key)
    {
        return isset($array[$key]) ? $array[$key] : null;
    }

    public function findStudentResultsArchived(Query $query, array $options)
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
        // $ItemResults = TableRegistry::get('Assessment.AssessmentItemResults');
        $ItemResults = TableRegistry::get('Institution.AssessmentItemResultsArchived');
        $InstitutionClassStudents = TableRegistry::get('institution_class_students');//POCOR-6572
        $educationId = $InstitutionSubjects->find()->select('education_subject_id')->where(['id' => $subjectId])->first();


        return $query
            ->select([
                $ItemResults->aliasField('id'),
                //$ItemResults->aliasField('marks'),//POCOR-6573 starts
                $ItemResults->aliasField('academic_period_id'),
                $ItemResults->aliasField('education_grade_id'),
                $ItemResults->aliasField('education_subject_id'),
                $ItemResults->aliasField('assessment_grading_option_id'),
                $ItemResults->aliasField('assessment_period_id'),//POCOR-6573 ends
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
            )//POCOR-6572 starts
            ->innerJoin(
                [$InstitutionClassStudents->alias() => $InstitutionClassStudents->table()],
                [
                    $InstitutionClassStudents->aliasField('student_id = ') . $this->aliasField('student_id')
                ]
            )//POCOR-6572 ends
            ->where([
                $this->aliasField('institution_subject_id') => $subjectId,
                $this->aliasField('institution_class_id') => $classId,
                $InstitutionSubjects->aliasField('institution_id') => $institutionId,
                $InstitutionClassStudents->aliasField('institution_class_id') => $classId,//POCOR-6572
                $InstitutionClassStudents->aliasField('institution_id') => $institutionId,//POCOR-6572
                $StudentStatuses->aliasField('code NOT IN ') => ['TRANSFERRED', 'WITHDRAWN', 'REPEATED']//POCOR-6687 - uncommented status condition because it was showing "repeated status" student
            ])
            ->group([
                $this->aliasField('student_id'),
                //Added for POCOR-6558[START]
                $ItemResults->aliasField('assessment_period_id')
                //Added for POCOR-6558[END]
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
            })
            //POCOR-6573 starts    
            ->formatResults(function ($results1) {
                $arrResults1 = is_array($results1) ? $results1 : $results1->toArray();
                foreach ($arrResults1 as &$result) {
                    $assessmentItemResults = TableRegistry::get('assessment_item_results_archived');
                    $assessmentItemResultsData = $assessmentItemResults->find()
                        ->select([
                            $assessmentItemResults->aliasField('marks')
                        ])
                        ->order([
                            $assessmentItemResults->aliasField('created') => 'DESC',
                            $assessmentItemResults->aliasField('modified') => 'DESC'
                        ])
                        ->where([
                            $assessmentItemResults->aliasField('student_id') => $result['student_id'],
                            $assessmentItemResults->aliasField('academic_period_id') => $result['AssessmentItemResults']['academic_period_id'],
                            $assessmentItemResults->aliasField('education_grade_id') => $result['AssessmentItemResults']['education_grade_id'],
                            $assessmentItemResults->aliasField('assessment_period_id') => $result['AssessmentItemResults']['assessment_period_id'],
                            $assessmentItemResults->aliasField('education_subject_id') => $result['AssessmentItemResults']['education_subject_id'],
                        ])
                        ->first();
                    $result['AssessmentItemResults']['marks'] = $assessmentItemResultsData->marks;
                }
                return $arrResults1;
            }); //POCOR-6573 ends
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
            ->count();
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
            ->count();
        return $count;
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {

        if ($entity->isNew() || $entity->dirty('student_status_id')) {
            $id = $entity->institution_subject_id;
            $countMale = $this->getMaleCountBySubject($id);
            $countFemale = $this->getFemaleCountBySubject($id);

            //$this->InstitutionSubjects->updateAll(['total_male_students' => $countMale, 'total_female_students' => $countFemale], ['id' => $id]);
        }
    }

    public function afterDelete(Event $event, Entity $entity, ArrayObject $options)
    {

        $res = $this->InstitutionSubjects->find()->select(['InstitutionSubjects.id'])->join([
            'institution_subject_students' => [
                'table' => 'institution_subject_students',
                'type' => 'LEFT',
                'conditions' => 'institution_subject_students.institution_subject_id = InstitutionSubjects.id'
            ]])->where(['institution_subject_students.academic_period_id' => $entity->academic_period_id, 'institution_subject_students.education_grade_id' => $entity->education_grade_id, 'institution_subject_students.education_subject_id' => $entity->education_subject_id, 'institution_subject_students.institution_class_id' => $entity->institution_class_id])->group('institution_subject_students.institution_subject_id')->first();

        $oldCount = $this->InstitutionSubjects->find()->select(['total_male_students', 'total_female_students'])->where(['id' => $res['id']])->first();
        $id = $entity->institution_subject_id;
        $prevCount = $this->InstitutionSubjects->find()->select(['total_male_students', 'total_female_students'])->where(['id' => $id])->first();
        $id = $entity->institution_subject_id;
        $countMale = $this->getMaleCountBySubject($id);
        $countFemale = $this->getFemaleCountBySubject($id);
        $totalMale = $oldCount['total_male_students'] + $prevCount['total_male_students'] - $countMale;
        $totalFemale = $oldCount['total_female_students'] + $prevCount['total_female_students'] - $countFemale;

        $this->InstitutionSubjects->updateAll(['total_male_students' => $countMale, 'total_female_students' => $countFemale], ['id' => $id]);

        //$this->InstitutionSubjects->updateAll(['total_male_students' => $totalMale, 'total_female_students' => $totalFemale], ['id' => $res['id']]);//POCOR-6583 commented unnecessary line
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

    // POCOR-7362 starts
    public function getEnrolledStudent($period, $subject, $educationGradeId)
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
                $this->aliasField('education_subject_id') => $subject,
                $this->aliasField('education_grade_id') => $educationGradeId
            ])
            ->select([
                $this->aliasField('student_id'),
                $Users->aliasField('openemis_no'),
                $Users->aliasField('first_name'),
                $Users->aliasField('middle_name'),
                $Users->aliasField('third_name'),
                $Users->aliasField('last_name'),
            ])
            ->toArray();

        $studentList = [];
        foreach ($students as $key => $value) {
            $studentList[$value->student_id] = $value->_matchingData['Users']['name_with_id'];
        }

        return $studentList;
    }
    // POCOR-7362 ends

    public function getStudentClassGradeDetails($period, $institution, $student, $subject) //function return class and grade of student.
    {
        return $this
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
