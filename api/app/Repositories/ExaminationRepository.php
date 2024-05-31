<?php

namespace App\Repositories;

use App\Models\Examination;
use App\Models\ExaminationCenterExaminationSubjectStudent;
use App\Models\ExaminationCentreExamination;
use App\Models\ExaminationCentreExaminationStudent;
use App\Models\ExaminationCentreExaminationSubject;
use App\Models\ExaminationStudentSubjectResult;
use App\Models\ExaminationSubject;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
        return ExaminationCentreExaminationSubject::select('examination_centres_examinations_subjects.*')->with('examinationSubject.gradingType.gradingOptions','educationSubject')
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

    public function examStudentSubjectResult($data)
    {
        // used to update total mark whenever an examination mark is added or updated
        $studentId = $data['student_id'];
        $examinationCentreId = $data['examination_centre_id'];
        $examinationSubjectId = $data['examination_subject_id'];
        $examinationId = $data['examination_id'];

        $examinationResult = ExaminationStudentSubjectResult::where('examination_subject_id', $examinationSubjectId)->where('examination_centre_id', $examinationCentreId)->where('examination_id', $examinationId)->where('student_id', $studentId)->first();

        $examinationSubjects = ExaminationSubject::with('gradingType.gradingOptions')->where('id', $examinationSubjectId)->first();

        $result = [];
        if ($examinationSubjects->gradingType) {
            $resultType = $examinationSubjects->gradingType->result_type;
            if ($resultType == 'MARKS') {
                $gradingOptions = $examinationSubjects->gradingType->gradingOptions;
                if ($gradingOptions && !empty($gradingOptions)) {
                    foreach ($gradingOptions as $key => $obj) {
                        if ($data['marks'] >= $obj->min && $data['marks'] <= $obj->max) {
                            $result['examination_grading_option_id'] = $obj->id;
                            break;
                        }
                    }
                }
                $result['total_mark'] = round($data['marks'] * $examinationSubjects->weight, 2);
            } else if ($resultType == 'GRADES') {
                $result['total_mark'] = NULL;
            }

            ExaminationCenterExaminationSubjectStudent::where('examination_subject_id', $examinationSubjectId)->where('examination_centre_id', $examinationCentreId)->where('examination_id', $examinationId)->where('student_id', $studentId)->update(['total_mark' => $result['total_mark']]);
        }

        if ($examinationResult) {
            $examinationResult->examination_grading_option_id = $result['examination_grading_option_id'];
            $examinationResult->marks = $data['marks'];
            $examinationResult->modified_user_id = Auth::id();
            $examinationResult->modified = Carbon::now()->toDateTimeString();

            $examinationResult->save();
            return $examinationResult;
        } else {
            return ExaminationStudentSubjectResult::create([
                'id' => Str::uuid(),
                'examination_subject_id' => $examinationSubjectId,
                'marks' => $data['marks'],
                'student_id' => $studentId,
                'academic_period_id' => $data['academic_period_id'],
                'examination_id' => $examinationId,
                'examination_centre_id' => $examinationCentreId,
                'education_subject_id' => $examinationSubjects['education_subject_id'],
                'examination_grading_option_id' => $result['examination_grading_option_id'],
                'institution_id' => $data['institution_id'],
                'created_user_id' => Auth::id(),
                'created' => Carbon::now()->toDateTimeString()
            ]);
        }
    }
}