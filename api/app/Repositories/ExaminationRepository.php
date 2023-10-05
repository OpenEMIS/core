<?php

namespace App\Repositories;

use App\Models\Examination;
use App\Models\ExaminationCentreExamination;
use App\Models\ExaminationStudentSubjectResult;
use Exception;

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
}