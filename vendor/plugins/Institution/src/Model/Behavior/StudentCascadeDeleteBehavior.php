<?php
namespace Institution\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Core\Configure;

class StudentCascadeDeleteBehavior extends Behavior
{
    private $classIds = [];
    private $subjectIds = [];

    public function initialize(array $config)
    {
        parent::initialize($config);
    }

    public function afterDelete(Event $event, Entity $entity, ArrayObject $options)
    {
        $this->classIds = $this->getClassIds($entity);
        $this->subjectIds = $this->getSubjectIds($entity);

        if ($this->noStudentRecords($entity, true)) { // delete only if student no longer in this grade/academic period
            $this->deleteClassStudents($entity);
            $this->deleteSubjectStudents($entity);
            $this->deleteStudentResults($entity);
            $this->deleteStudentFees($entity);
        }

        if ($this->noStudentRecords($entity)) { // delete all other records if student no longer in school
            $this->deleteStudentAbsences($entity);
            $this->deleteStudentBehaviours($entity);
            $this->deleteStudentWithdrawRecords($entity);
            $this->deleteStudentAdmissionRecords($entity);
            if (!Configure::read('schoolMode')) {
                $this->deleteStudentSurveys($entity);
            }
        }

        $listeners = [
            TableRegistry::get('Institution.StudentAdmission'),
            TableRegistry::get('Institution.StudentWithdraw'),
            TableRegistry::get('Institution.StudentStatusUpdates')
        ];
        $this->_table->dispatchEventToModels('Model.Students.afterDelete', [$entity], $this->_table, $listeners);
    }

    private function deleteClassStudents(Entity $entity)
    {
        if (!empty($this->classIds)) {
            $ClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
            $classStudentData = $ClassStudents->find()
                ->where([
                    $ClassStudents->aliasField('student_id') => $entity->student_id,
                    $ClassStudents->aliasField('education_grade_id') => $entity->education_grade_id,
                    $ClassStudents->aliasField('institution_class_id IN ') => $this->classIds
                ])
                ->toArray()
                ;
            foreach ($classStudentData as $key => $value) {
                $ClassStudents->delete($value);
            }
        }
    }

    private function deleteSubjectStudents(Entity $entity)
    {
        if (!empty($this->subjectIds) && !empty($this->classIds)) {
            $SubjectStudents = TableRegistry::get('Institution.InstitutionSubjectStudents');
            $subjectStudentData = $SubjectStudents->find()
                ->where([
                    $SubjectStudents->aliasField('student_id') => $entity->student_id,
                    $SubjectStudents->aliasField('institution_class_id IN ') => $this->classIds,
                    $SubjectStudents->aliasField('institution_subject_id IN ') => $this->subjectIds
                ])
                ->toArray()
                ;

            foreach ($subjectStudentData as $key => $value) {
                $SubjectStudents->delete($value);
            }
        }
    }

    private function deleteStudentResults(Entity $entity)
    {
        $Assessments = TableRegistry::get('Assessment.Assessments');
        $Results = TableRegistry::get('Assessment.AssessmentItemResults');

        $institutionId = $entity->institution_id;
        $periodId = $entity->academic_period_id;
        $gradeId = $entity->education_grade_id;
        $studentId = $entity->student_id;

        $assessmentIds = $Assessments
            ->find('list', ['keyField' => 'id', 'valueField' => 'id'])
            ->where([
                $Assessments->aliasField('academic_period_id') => $periodId,
                $Assessments->aliasField('education_grade_id') => $gradeId
            ])
            ->toArray();


        if (!empty($assessmentIds)) {
            $resultList = $Results->find()
                ->where([
                    $Results->aliasField('institution_id') => $institutionId,
                    $Results->aliasField('academic_period_id') => $periodId,
                    $Results->aliasField('student_id') => $studentId,
                    $Results->aliasField('assessment_id IN') => $assessmentIds
                ]);
            foreach ($resultList as $key => $value) {
                $Results->delete($value);
            }
        }
    }

    private function deleteStudentFees(Entity $entity)
    {
        $InstitutionFees = TableRegistry::get('Institution.InstitutionFees');
        $StudentFees = TableRegistry::get('Institution.StudentFeesAbstract');

        $institutionFeeResults = $InstitutionFees
            ->find('list', ['keyField' => 'id', 'valueField' => 'id'])
            ->where([
                $InstitutionFees->aliasField('institution_id') => $entity->institution_id,
                $InstitutionFees->aliasField('academic_period_id') => $entity->academic_period_id,
                $InstitutionFees->aliasField('education_grade_id') => $entity->education_grade_id
            ])
            ->all();

        if (!$institutionFeeResults->isEmpty()) {
            $institutionFeeIds = $institutionFeeResults->toArray();
            $feeList = $StudentFees->find()->where([
                $StudentFees->aliasField('institution_fee_id IN') => $institutionFeeIds,
                $StudentFees->aliasField('student_id') => $entity->student_id
            ]);
            foreach ($feeList as $key => $value) {
                $StudentFees->delete($value);
            }
        }
    }

    private function deleteStudentAbsences(Entity $entity)
    {
        $startDate = $entity->start_date;
        $endDate = $entity->end_date;

        if ($startDate instanceof Time) {
            $startDate = $startDate->format('Y-m-d');
        } else {
            $startDate = date('Y-m-d', strtotime($startDate));
        }
        if ($endDate instanceof Time) {
            $endDate = $endDate->format('Y-m-d');
        } else {
            $endDate = date('Y-m-d', strtotime($endDate));
        }

        /* delete all attendance records (institution_site_student_absences) with dates that fall between the start and end date found in institution_students */
        $InstitutionStudentAbsences = TableRegistry::get('Institution.InstitutionStudentAbsences');
        $overlapDateCondition = [];
        $overlapDateCondition = [
            $InstitutionStudentAbsences->aliasField('date') . ' >= ' => $startDate,
            $InstitutionStudentAbsences->aliasField('date') . ' <= ' => $endDate
        ];

        $studentAbsenceData = $InstitutionStudentAbsences->find()
            ->where($overlapDateCondition)
            ->where([$InstitutionStudentAbsences->aliasField('student_id') => $entity->student_id])
            ->where([$InstitutionStudentAbsences->aliasField('institution_id') => $entity->institution_id])
            ;

        foreach ($studentAbsenceData as $key => $value) {
            $InstitutionStudentAbsences->delete($value);
        }

        $StudentAbsencesPeriodDetails = TableRegistry::get('Institution.StudentAbsencesPeriodDetails');
        $overlapDateCondition = [
            $StudentAbsencesPeriodDetails->aliasField('date') . ' >= ' => $startDate,
            $StudentAbsencesPeriodDetails->aliasField('date') . ' <= ' => $endDate
        ];

        $studentAbsenceDetailData = $StudentAbsencesPeriodDetails->find()
            ->where($overlapDateCondition)
            ->where([$StudentAbsencesPeriodDetails->aliasField('student_id') => $entity->student_id])
            ->where([$StudentAbsencesPeriodDetails->aliasField('institution_id') => $entity->institution_id])
            ;   

        foreach ($studentAbsenceDetailData as $key => $value) {
            $StudentAbsencesPeriodDetails->delete($value);
        }
    }

    private function deleteStudentBehaviours(Entity $entity)
    {
        $startDate = $entity->start_date;
        $endDate = $entity->end_date;

        if ($startDate instanceof Time) {
            $startDate = $startDate->format('Y-m-d');
        } else {
            $startDate = date('Y-m-d', strtotime($startDate));
        }
        if ($endDate instanceof Time) {
            $endDate = $endDate->format('Y-m-d');
        } else {
            $endDate = date('Y-m-d', strtotime($endDate));
        }
        /* delete all behaviour records (student_behaviours) with dates that fall between the start and end date found in institution_students */
        $StudentBehaviours = TableRegistry::get('Institution.StudentBehaviours');

        $studentBehaviourData = $StudentBehaviours->find()
            ->where([
                $StudentBehaviours->aliasField('date_of_behaviour').' >= ' => $startDate,
                $StudentBehaviours->aliasField('date_of_behaviour').' <= ' => $endDate
            ])
            ->where([$StudentBehaviours->aliasField('student_id') => $entity->student_id])
            ->where([$StudentBehaviours->aliasField('institution_id') => $entity->institution_id])
            ;

        // DELETION TO BE DONE HERE FOR $studentBehaviourData
        foreach ($studentBehaviourData as $key => $value) {
            $StudentBehaviours->delete($value);
        }
    }

    private function deleteStudentSurveys(Entity $entity)
    {
        $StudentSurveys = TableRegistry::get('Student.StudentSurveys');
        $StudentSurveyAnswers = TableRegistry::get('Student.StudentSurveyAnswers');
        $StudentSurveyTableCells = TableRegistry::get('Student.StudentSurveyTableCells');

        $institutionId = $entity->institution_id;
        $studentId = $entity->student_id;

        $studentSurveyResults = $StudentSurveys
            ->find('list', ['keyField' => 'id', 'valueField' => 'id'])
            ->where([
                $StudentSurveys->aliasField('institution_id') => $institutionId,
                $StudentSurveys->aliasField('student_id') => $studentId
            ])
            ->all();

        if (!$studentSurveyResults->isEmpty()) {
            $studentSurveyIds = $studentSurveyResults->toArray();
            $studentSurveyAnswersData = $StudentSurveyAnswers->find()
                ->where([
                    $StudentSurveyAnswers->aliasField('institution_student_survey_id IN ') => $studentSurveyIds
                ])
                ->toArray()
                ;
            foreach ($studentSurveyAnswersData as $key => $value) {
                $StudentSurveyAnswers->delete($value);
            }
            $studentSurveyTableCellsData = $StudentSurveyTableCells->find()
                ->where([
                    $StudentSurveyTableCells->aliasField('institution_student_survey_id IN ') => $studentSurveyIds
                ])
                ->toArray()
                ;
            foreach ($studentSurveyTableCellsData as $key => $value) {
                $StudentSurveyTableCells->delete($value);
            }
        }

        $studentSurveysData = $StudentSurveys->find()
            ->where([
                $StudentSurveys->aliasField('institution_id') => $institutionId,
                $StudentSurveys->aliasField('student_id') => $studentId
            ])
            ->toArray()
            ;
        foreach ($studentSurveysData as $key => $value) {
            $StudentSurveys->delete($value);
        }
    }

    private function deleteStudentWithdrawRecords(Entity $entity)
    {
        $StudentWithdraw = TableRegistry::get('Institution.StudentWithdraw');
        $studentWithdrawData = $StudentWithdraw->find()
            ->where([
                $StudentWithdraw->aliasField('institution_id') => $entity->institution_id,
                $StudentWithdraw->aliasField('academic_period_id') => $entity->academic_period_id,
                $StudentWithdraw->aliasField('education_grade_id') => $entity->education_grade_id,
                $StudentWithdraw->aliasField('student_id') => $entity->student_id
            ])
            ->toArray()
            ;
        foreach ($studentWithdrawData as $key => $value) {
            $StudentWithdraw->delete($value);
        }
    }

    private function deleteStudentAdmissionRecords(Entity $entity)
    {
        $StudentAdmission = TableRegistry::get('Institution.StudentAdmission');
        $doneStatus = $StudentAdmission::DONE;

        // only pending admission records will be deleted
        $studentAdmissionData = $StudentAdmission->find()
            ->matching('Statuses', function ($q) use ($doneStatus) {
                return $q->where(['category <> ' => $doneStatus]);
            })
            ->where([
                $StudentAdmission->aliasField('institution_id') => $entity->institution_id,
                $StudentAdmission->aliasField('academic_period_id') => $entity->academic_period_id,
                $StudentAdmission->aliasField('education_grade_id') => $entity->education_grade_id,
                $StudentAdmission->aliasField('student_id') => $entity->student_id
            ])
            ->toArray();

        foreach ($studentAdmissionData as $key => $value) {
            $StudentAdmission->delete($value);
        }
    }

    private function deleteStudentTransferRecords(Entity $entity)
    {
        $StudentTransfers = TableRegistry::get('Institution.InstitutionStudentTransfers');
        $studentTransferData = $StudentTransfers->find()
            ->where([
                $StudentTransfers->aliasField('previous_institution_id') => $entity->institution_id,
                $StudentTransfers->aliasField('previous_academic_period_id') => $entity->academic_period_id,
                $StudentTransfers->aliasField('previous_education_grade_id') => $entity->education_grade_id,
                $StudentTransfers->aliasField('student_id') => $entity->student_id
            ])
            ->toArray();

        foreach ($studentTransferData as $key => $value) {
            $StudentTransfers->delete($value);
        }
    }

    private function noStudentRecords(Entity $entity, $includeGrade = false)
    {
        $Students = $this->_table;
        $conditions = [
            $Students->aliasField('institution_id') => $entity->institution_id,
            $Students->aliasField('student_id') => $entity->student_id
        ];

        if ($includeGrade) {
            $conditions[$Students->aliasField('academic_period_id')] = $entity->academic_period_id;
            $conditions[$Students->aliasField('education_grade_id')] = $entity->education_grade_id;
        }

        $results = $Students
            ->find()
            ->where($conditions)
            ->all();

        return $results->isEmpty();
    }

    private function getClassIds(Entity $entity)
    {
        $Classes = TableRegistry::get('Institution.InstitutionClasses');
        $ClassGrades = TableRegistry::get('Institution.InstitutionClassGrades');

        return $Classes
            ->find('list', ['keyField' => 'id', 'valueField' => 'id'])
            ->innerJoin(
                [$ClassGrades->alias() => $ClassGrades->table()],
                [
                    $ClassGrades->aliasField('institution_class_id = ') . $Classes->aliasField('id'),
                    $ClassGrades->aliasField('education_grade_id') => $entity->education_grade_id
                ]
            )
            ->where([
                $Classes->aliasField('institution_id') => $entity->institution_id,
                $Classes->aliasField('academic_period_id') => $entity->academic_period_id
            ])
            ->toArray();
    }

    private function getSubjectIds(Entity $entity)
    {
        $subjectIds = [];

        if (!empty($this->classIds)) {
            $Subjects = TableRegistry::get('Institution.InstitutionSubjects');
            $ClassesSubjects = TableRegistry::get('Institution.InstitutionClassSubjects');

            $subjectIds = $Subjects
                ->find('list', ['keyField' => 'id', 'valueField' => 'id'])
                ->innerJoin(
                    [$ClassesSubjects->alias() => $ClassesSubjects->table()],
                    [
                        $ClassesSubjects->aliasField('institution_subject_id = ') . $Subjects->aliasField('id'),
                        $ClassesSubjects->aliasField('institution_class_id IN ') => $this->classIds
                    ]
                )
                ->where([
                    $Subjects->aliasField('institution_id') => $entity->institution_id,
                    $Subjects->aliasField('academic_period_id') => $entity->academic_period_id
                ])
                ->toArray();
        }

        return $subjectIds;
    }
}
