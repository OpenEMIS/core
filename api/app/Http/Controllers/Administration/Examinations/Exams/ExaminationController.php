<?php

namespace App\Http\Controllers\Administration\Examinations\Exams;

use App\Helpers\ParameterValidator;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExaminationStudentSubjectResultRequest;
use App\Models\Examination;
use App\Models\ExaminationCenterExaminationSubjectStudent;
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
            $validateExamination = ParameterValidator::validateExamination($examinationId);
            $validateExaminationCentre = ParameterValidator::validateExaminationCentre($centerId);

            if (!$validateExamination || !$validateExaminationCentre) {
                return $this->sendErrorResponse('Unsuccessful-Invalid parameters');
            }

            return $this->sendSuccessResponse('Successful', $validateExaminationCentre);
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
            $validateExamination = ParameterValidator::validateExamination($examinationId);
            $validateExaminationCentre = ParameterValidator::validateExaminationCentre($centerId);

            if (!$validateExamination || !$validateExaminationCentre) {
                return $this->sendErrorResponse('Unsuccessful-Invalid parameters');
            }
            $examinationCenterExaminationSubjects =  $this->examinationService->examinationCenterExaminationSubjects($examinationId, $centerId);

            if (!$examinationCenterExaminationSubjects) {
                return $this->sendErrorResponse('Unsuccessful');
            }
            return $this->sendSuccessResponse('Successful', $examinationCenterExaminationSubjects);
        } catch (Exception $e) {
            return $this->sendErrorResponse('Unsuccessful', $e->getMessage());
        }
    }

    public function examinationCenterExaminationSubjectsStudents($examinationId, $centerId, $subjectId)
    {
        try {
            $validateExamination = ParameterValidator::validateExamination($examinationId);
            $validateExaminationCentre = ParameterValidator::validateExaminationCentre($centerId);
            $validateSubject = ParameterValidator::validateEducationSubject($subjectId);

            if (!$validateExamination || !$validateExaminationCentre || !$validateSubject) {
                return $this->sendErrorResponse('Unsuccessful-Invalid parameters');
            }

            $examinationCenterExaminationSubjects =  $this->examinationService->examinationCenterExaminationSubjectsStudents($examinationId, $centerId, $subjectId);

            if (!$examinationCenterExaminationSubjects) {
                return $this->sendErrorResponse('Unsuccessful');
            }
            return $this->sendSuccessResponse('Successful', $examinationCenterExaminationSubjects);
        } catch (Exception $e) {
            return $this->sendErrorResponse('Unsuccessful', $e->getMessage());
        }
    }

    public function examStudentSubjectResult(ExaminationStudentSubjectResultRequest $request)
    {
        try {

            $data = $request->all();

            $examinationId = $data['examination_id'];
            $marks = $data['marks'];
            $academicPeriod = $data['academic_period_id'];
            $examinationSubjectId = $data['examination_subject_id'];
            $examinationCentreId = $data['examination_centre_id'];
            $institutionId = $data['institution_id'];
            $studentId = $data['student_id'];
            $examinationGradingOptionId = $data['examination_grading_option_id'];

            $validateExamination = ParameterValidator::validateExamination($examinationId);
            $validateExaminationCentre = ParameterValidator::validateExaminationCentre($examinationCentreId);
            $validateExaminationSubject = ParameterValidator::validateExaminationSubject($examinationSubjectId);
            $validateAcademicPeriod = ParameterValidator::validateAcademicPeriod($academicPeriod);
            $validateInstitution = ParameterValidator::validateInstitution($institutionId);
            $validateExaminationGradingOption = $marks ? true : ParameterValidator::validateExaminationGradingOption($examinationGradingOptionId);

            $validateStudent = ExaminationCenterExaminationSubjectStudent::where('examination_subject_id', $examinationSubjectId)->where('examination_centre_id', $examinationCentreId)->where('examination_id', $examinationId)->where('student_id', $studentId)->first();

            if (!$validateExamination || !$validateExaminationCentre || !$validateExaminationSubject || !$validateAcademicPeriod || !$validateInstitution || !$validateExaminationGradingOption || !$validateStudent) {
                return $this->sendErrorResponse('Unsuccessful-Invalid parameters');
            }

            $examinationCenterExaminationSubjects =  $this->examinationService->examStudentSubjectResult($data);

            return $this->sendSuccessResponse('Successful', $examinationCenterExaminationSubjects);
        } catch (Exception $e) {
            return $this->sendErrorResponse('Unsuccessful', $e->getMessage());
        }
    }

}