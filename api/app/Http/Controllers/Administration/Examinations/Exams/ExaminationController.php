<?php

namespace App\Http\Controllers\Administration\Examinations\Exams;

use App\Http\Controllers\Controller;
use App\Models\Examination;
use App\Services\ExaminationService;
use Exception;
use Illuminate\Http\Request;

class ExaminationController extends Controller
{
    protected ExaminationService $examinationService;
    
    public function __construct(ExaminationService $examinationService) {
        $this->examinationService = $examinationService;
    }


    public function getExaminationDetails($id)
    {
        try {
            $examination = $this->examinationService->getExaminationDetails($id);

            if (!$examination) {
                return $this->sendErrorResponse('Examination not found.');
            }
            return $this->sendSuccessResponse('Examination found.', $examination);
        } catch (Exception $e) {
            return $this->sendErrorResponse('Examination not found.');
        }
    }

    public function getCenterExaminationDetails($examinationId, $centerId)
    {
        try {
            $examinationCenter =  $this->examinationService->getCenterExaminationDetails($examinationId, $centerId);

            if (!$examinationCenter) {
                return $this->sendErrorResponse('Center examination not found.');
            }
            return $this->sendSuccessResponse('Center examination found.', $examinationCenter);
        } catch (Exception $e) {
            return $this->sendErrorResponse('Center examination not found.');
        }
    }


    public function getCenterExaminationStudentDetails($examinationId, $centerId, $studentId)
    {
        try {
            $examinationCenterStudent =  $this->examinationService->getCenterExaminationStudentDetails($examinationId, $centerId, $studentId);

            if (!$examinationCenterStudent) {
                return $this->sendErrorResponse('Student not found.');
            }
            return $this->sendSuccessResponse('Student found.', $examinationCenterStudent);
        } catch (Exception $e) {

            return $this->sendErrorResponse('Student not found.');
        }
    }

    public function examinationCenterExaminationSubjects($examinationId, $centerId)
    {
        try {
            $examinationCenterExaminationSubjects =  $this->examinationService->examinationCenterExaminationSubjects($examinationId, $centerId);

            if (!$examinationCenterExaminationSubjects) {
                return $this->sendErrorResponse('Subjects list not found.');
            }
            return $this->sendSuccessResponse('Subjects list found.', $examinationCenterExaminationSubjects);
        } catch (Exception $e) {
            return $this->sendErrorResponse('Subjects list not found.');
        }
    }

    public function examinationCenterExaminationSubjectsStudents($examinationId, $centerId, $subjectId)
    {
        try {
            $examinationCenterExaminationSubjects =  $this->examinationService->examinationCenterExaminationSubjectsStudents($examinationId, $centerId, $subjectId);

            if (!$examinationCenterExaminationSubjects) {
                return $this->sendErrorResponse('Students not found.');
            }
            return $this->sendSuccessResponse('Students list found.', $examinationCenterExaminationSubjects);
        } catch (Exception $e) {
            return $this->sendErrorResponse('Student not found.');
        }
    }

    public function examStudentSubjectResult(Request $request)
    {
        try {
            $data = $request->all();
            $examinationCenterExaminationSubjects =  $this->examinationService->examStudentSubjectResult($data);

            return $this->sendSuccessResponse('Result saved.', $examinationCenterExaminationSubjects);
        } catch (Exception $e) {
            return $this->sendErrorResponse('Result not saved.');
        }
    }

}