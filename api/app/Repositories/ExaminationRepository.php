<?php

namespace App\Repositories;

use App\Models\Examination;
use App\Models\ExaminationCentreExamination;
use App\Models\ExaminationCentreExaminationStudent;
use App\Models\ExaminationCentreExaminationSubject;
use App\Models\ExaminationStudentSubjectResult;
use Exception;
use Illuminate\Support\Facades\DB;

class ExaminationRepository
{

    public function getExaminationDetails($id)
    {
        return Examination::find($id);
    }

    public function getCenterExaminationDetails($examinationId, $centerId)
    {
        return ExaminationCentreExamination::with('examination', 'examinationCentre')
        ->where('examination_id', $examinationId)
        ->where('examination_centre_id', $centerId)
        ->first();
    }

    public function getCenterExaminationStudentDetails($examinationId, $centerId, $studentId)
    {
        $student = ExaminationStudentSubjectResult::where('student_id', $studentId)
        ->where('examination_centre_id', $centerId)
        ->where('examination_id', $examinationId)
        ->get();

        return $student;
    }
    public function examinationCenterExaminationSubjects($examinationId, $centerId)
    {
        return ExaminationCentreExaminationSubject::select('examination_centres_examinations_subjects.*')->with('examinationSubject.gradingType','educationSubject')
                ->join('education_subjects',  'education_subjects.id', '=', 'examination_centres_examinations_subjects.education_subject_id')
        ->where('examination_id', $examinationId)
        ->where('examination_centre_id', $centerId)
        ->orderBy('order')
        ->get();
    }

    public function examinationCenterExaminationSubjectsStudents($examinationId, $centerId, $subjectId)
    {

        $sql = "SELECT
            student.student_id AS 'student_id',
            student.registration_number AS 'registration_number',
            student.institution_id AS 'institution_id',
            student.academic_period_id AS 'academic_period_id',
            studentSubject.total_mark AS 'total_mark',
            studentResults.id AS 'result_id',
            studentResults.marks AS 'result_marks',
            studentResults.examination_grading_option_id AS 'examination_grading_option_id',
            security_users.first_name AS 'first_name',
            security_users.last_name AS 'last_name',
            security_users.middle_name AS 'middle_name',
            security_users.openemis_no AS 'openemis_no',
            security_users.third_name AS 'third_name'
            FROM
                `examination_centres_examinations_students` `student`
                INNER JOIN `examination_centres_examinations_subjects_students` `studentSubject` ON `student`.`student_id` = (
                `studentSubject`.`student_id`
                )

                AND student.examination_id = studentSubject.examination_id
                AND student.examination_centre_id = studentSubject.examination_centre_id
                LEFT JOIN `examination_student_subject_results` `studentResults` ON `studentSubject`.`student_id` = (
                `studentResults`.`student_id`
                )
                AND studentSubject.examination_id = studentResults.examination_id
                AND studentSubject.examination_centre_id = studentResults.examination_centre_id
                AND studentSubject.examination_subject_id = studentResults.examination_subject_id
            INNER JOIN `security_users` ON `security_users`.`id` = (
                `studentSubject`.`student_id`
            )
            WHERE
            (
            studentSubject.examination_id = $examinationId
            AND studentSubject.examination_centre_id = $centerId
            AND studentSubject.examination_subject_id = $subjectId
            )";

        return DB::select(DB::raw($sql));
    }
}