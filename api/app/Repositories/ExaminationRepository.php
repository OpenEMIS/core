<?php

namespace App\Repositories;

use App\Models\Examination;
use App\Models\ExaminationCentreExamination;
use App\Models\ExaminationCentreExaminationSubject;
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
    public function examinationCenterExaminationSubjects($examinationId, $centerId)
    {
        return ExaminationCentreExaminationSubject::select('examination_centres_examinations_subjects.*')->with('examinationSubject.gradingType','educationSubject')
                ->join('education_subjects',  'education_subjects.id', '=', 'examination_centres_examinations_subjects.education_subject_id')
        ->where('examination_id', $examinationId)
        ->where('examination_centre_id', $centerId)
        ->orderBy('order')
        ->get();
    }
}