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



    /**
     * @OA\Get(
     *     path="/api/v4/exams/{examId}",
     *     summary="Get details of a specific exam",
     *     description="Returns details of the exam identified by the provided examId",
     *     tags={"Examinations"},
     *     @OA\Parameter(
     *         name="examId",
     *         in="path",
     *         required=true,
     *         description="ID of the exam",
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example="1"),
     *                 @OA\Property(property="code", type="string", example="PSLE"),
     *                 @OA\Property(property="name", type="string", example="Primary School Leaving Examination"),
     *                 @OA\Property(property="description", type="string", example=""),
     *                 @OA\Property(property="registration_start_date", type="string", format="date", example="2020-01-01"),
     *                 @OA\Property(property="registration_end_date", type="string", format="date", example="2020-07-31"),
     *                 @OA\Property(property="academic_period_id", type="integer", example="29"),
     *                 @OA\Property(property="education_grade_id", type="integer", example="64"),
     *                 @OA\Property(property="modified_user_id", type="integer", nullable=true, example=null),
     *                 @OA\Property(property="modified", type="string", format="date-time", nullable=true, example=null),
     *                 @OA\Property(property="created_user_id", type="integer", example="2"),
     *                 @OA\Property(property="created", type="string", format="date-time", example="2020-03-06 05:10:34"),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
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

    

    /**
     * @OA\Get(
     *     path="/api/v4/exams/{examId}/centres/{centreId}",
     *     summary="Get centre details for a specific exam",
     *     description="Returns details of the centre associated with the specified exam",
     *     tags={"Examinations"},
     *     @OA\Parameter(
     *         name="examId",
     *         in="path",
     *         required=true,
     *         description="ID of the exam",
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="centreId",
     *         in="path",
     *         required=true,
     *         description="ID of the centre",
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example="1"),
     *                 @OA\Property(property="name", type="string", example="Avory Primary School"),
     *                 @OA\Property(property="code", type="string", example="P1002"),
     *                 @OA\Property(property="address", type="string", example="270 Duke Lane"),
     *                 @OA\Property(property="postal_code", type="string", example=""),
     *                 @OA\Property(property="contact_person", type="string", example=""),
     *                 @OA\Property(property="telephone", type="string", example="83948723"),
     *                 @OA\Property(property="fax", type="string", example="83948723"),
     *                 @OA\Property(property="email", type="string", format="email", example="contact@avoryprimary.com"),
     *                 @OA\Property(property="website", type="string", example="avoryprimary.com"),
     *                 @OA\Property(property="institution_id", type="integer", example="6"),
     *                 @OA\Property(property="area_id", type="integer", example="11"),
     *                 @OA\Property(property="academic_period_id", type="integer", example="29"),
     *                 @OA\Property(property="examination_id", type="integer", example="1"),
     *                 @OA\Property(property="total_registered", type="integer", example="0"),
     *                 @OA\Property(property="modified_user_id", type="integer", example=null),
     *                 @OA\Property(property="modified", type="string", format="date-time", example=null),
     *                 @OA\Property(property="created_user_id", type="integer", example="2"),
     *                 @OA\Property(property="created", type="string", format="date-time", example="2020-03-06 05:13:01"),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
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

    
    /**
     * @OA\Get(
     *     path="/api/v4/exams/{examId}/centres/{centreId}/students/{studentId}",
     *     summary="Get details of a student for a specific exam and centre",
     *     description="Returns details of a student for the specified exam and centre",
     *     tags={"Examinations"},
     *     @OA\Parameter(
     *         name="examId",
     *         in="path",
     *         required=true,
     *         description="ID of the exam",
     *         @OA\Schema(type="integer", example="2")
     *     ),
     *     @OA\Parameter(
     *         name="centreId",
     *         in="path",
     *         required=true,
     *         description="ID of the centre",
     *         @OA\Schema(type="integer", example="12")
     *     ),
     *     @OA\Parameter(
     *         name="studentId",
     *         in="path",
     *         required=true,
     *         description="ID of the student",
     *         @OA\Schema(type="integer", example="1130")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example="1"),
     *                     @OA\Property(property="marks", type="integer", nullable=true, example=null),
     *                     @OA\Property(property="examination_subject_id", type="integer", example="3"),
     *                     @OA\Property(property="student_id", type="integer", example="1130"),
     *                     @OA\Property(property="academic_period_id", type="integer", example="31"),
     *                     @OA\Property(property="examination_id", type="integer", example="2"),
     *                     @OA\Property(property="examination_centre_id", type="integer", example="12"),
     *                     @OA\Property(property="education_subject_id", type="integer", example="37"),
     *                     @OA\Property(property="examination_grading_option_id", type="integer", example="1"),
     *                     @OA\Property(property="institution_id", type="integer", example="6"),
     *                     @OA\Property(property="modified_user_id", type="integer", nullable=true, example=null),
     *                     @OA\Property(property="modified", type="string", format="date-time", nullable=true, example=null),
     *                     @OA\Property(property="created_user_id", type="integer", example="2"),
     *                     @OA\Property(property="created", type="string", format="date-time", example="2022-04-28 12:46:54")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
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

    

    /**
     * @OA\Get(
     *     path="/api/v4/exams/{examId}/centres/{centreId}/subjects",
     *     summary="Get subjects for an exam at a specific centre",
     *     description="Returns subjects for a specified exam at a particular examination centre.",
     *     tags={"Examinations"},
     *     @OA\Parameter(
     *         name="examId",
     *         in="path",
     *         required=true,
     *         description="ID of the exam",
     *         @OA\Schema(type="integer", example=2)
     *     ),
     *     @OA\Parameter(
     *         name="centreId",
     *         in="path",
     *         required=true,
     *         description="ID of the examination centre",
     *         @OA\Schema(type="integer", example=12)
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         description="Order",
     *         @OA\Schema(type="integer", example="order")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of items per page",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string", example="422a4b66f50b45144bff948fb11505d585d2f7a80378fd4e016d7fd7897c2d97"),
     *                     @OA\Property(property="created", type="string", example="2022-04-28 04:42:39"),
     *                     @OA\Property(property="education_subject_id", type="integer", example=60),
     *                     @OA\Property(property="examination_centre_id", type="integer", example=12),
     *                     @OA\Property(property="examination_id", type="integer", example=2),
     *                     @OA\Property(property="examination_subject_id", type="integer", example=2),
     *                     @OA\Property(property="education_subject", type="object",
     *                         @OA\Property(property="id", type="integer", example=60),
     *                         @OA\Property(property="name", type="string", example="Social Studies"),
     *                         @OA\Property(property="code", type="string", example="SSMC"),
     *                         @OA\Property(property="order", type="integer", example=1),
     *                         @OA\Property(property="visible", type="integer", example=1),
     *                         @OA\Property(property="modified_user_id", type="integer", example=2),
     *                         @OA\Property(property="modified", type="string", example="2023-04-03 10:11:23"),
     *                         @OA\Property(property="created_user_id", type="integer", example=2),
     *                         @OA\Property(property="created", type="string", example="2023-04-03 10:11:23")
     *                     ),
     *                     @OA\Property(property="examination_subject", type="object",
     *                         @OA\Property(property="id", type="integer", example=6),
     *                         @OA\Property(property="name", type="string", example="Social Studies"),
     *                         @OA\Property(property="code", type="string", example="SST"),
     *                         @OA\Property(property="weight", type="string", example="0.25"),
     *                         @OA\Property(property="examination_date", type="string", example="2022-12-01"),
     *                         @OA\Property(property="start_time", type="string", example="13:00:00"),
     *                         @OA\Property(property="end_time", type="string", example="14:00:00"),
     *                         @OA\Property(property="examination_id", type="integer", example=2),
     *                         @OA\Property(property="education_subject_id", type="integer", example=60),
     *                         @OA\Property(property="examination_grading_type_id", type="integer", example=1),
     *                         @OA\Property(property="modified_user_id", type="integer", example=2),
     *                         @OA\Property(property="modified", type="string", example="2023-04-03 10:11:23"),
     *                         @OA\Property(property="created_user_id", type="integer", example=2),
     *                         @OA\Property(property="created", type="string", example="2023-04-03 10:11:23"),
     *                         @OA\Property(property="grading_type", type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="code", type="string", example="PSLE-GD"),
     *                             @OA\Property(property="name", type="string", example="PSLE Gradings"),
     *                             @OA\Property(property="pass_mark", type="string", example="50.00"),
     *                             @OA\Property(property="max", type="string", example="100.00"),
     *                             @OA\Property(property="result_type", type="string", example="GRADES"),
     *                             @OA\Property(property="visible", type="integer", example=1),
     *                             @OA\Property(property="grading_options", type="array",
     *                                 @OA\Items(
     *                                     type="object",
     *                                     @OA\Property(property="id", type="integer", example=1),
     *                                     @OA\Property(property="code", type="string", example="A"),
     *                                     @OA\Property(property="name", type="string", example="A"),
     *                                     @OA\Property(property="description", type="string", example=""),
     *                                     @OA\Property(property="min", type="string", example="80.00"),
     *                                     @OA\Property(property="max", type="string", example="100.00"),
     *                                     @OA\Property(property="order", type="integer", example=1),
     *                                     @OA\Property(property="visible", type="integer", example=1),
     *                                     @OA\Property(property="examination_grading_type_id", type="integer", example=1),
     *                                     @OA\Property(property="created_user_id", type="integer", example=2),
     *                                     @OA\Property(property="created", type="string", example="2023-04-03 10:11:23")
     *                                 )
     *                             )
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
    public function examinationCenterExaminationSubjects(Request $request, $examinationId, $centerId)
    {
        try {
            $params = $request->all();
            $validateExamination = ParameterValidator::validateExamination($examinationId);
            $validateExaminationCentre = ParameterValidator::validateExaminationCentre($centerId);

            if (!$validateExamination || !$validateExaminationCentre) {
                return $this->sendErrorResponse('Unsuccessful-Invalid parameters');
            }
            $examinationCenterExaminationSubjects =  $this->examinationService->examinationCenterExaminationSubjects($params, $examinationId, $centerId);

            if (!$examinationCenterExaminationSubjects) {
                return $this->sendErrorResponse('Unsuccessful');
            }
            return $this->sendSuccessResponse('Successful', $examinationCenterExaminationSubjects);
        } catch (Exception $e) {
            return $this->sendErrorResponse('Unsuccessful', $e->getMessage());
        }
    }

    
    /**
     * @OA\Get(
     *     path="/api/v4/exams/{examId}/centres/{centreId}/subjects/{subjectId}/students",
     *     summary="Get students for a specific exam, centre, and subject",
     *     description="Returns details of students enrolled in a specific exam, centre, and subject based on the provided parameters.",
     *     tags={"Examinations"},
     *     @OA\Parameter(
     *         name="examId",
     *         in="path",
     *         required=true,
     *         description="ID of the exam",
     *         @OA\Schema(type="integer", example=2)
     *     ),
     *     @OA\Parameter(
     *         name="centreId",
     *         in="path",
     *         required=true,
     *         description="ID of the centre",
     *         @OA\Schema(type="integer", example=12)
     *     ),
     *     @OA\Parameter(
     *         name="subjectId",
     *         in="path",
     *         required=true,
     *         description="ID of the subject",
     *         @OA\Schema(type="integer", example=6)
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="student_id")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Number of items per page",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="student_id", type="integer", example=1130),
     *                     @OA\Property(property="registration_number", type="string", nullable=true, example=null),
     *                     @OA\Property(property="institution_id", type="integer", example=6),
     *                     @OA\Property(property="academic_period_id", type="integer", example=31),
     *                     @OA\Property(property="total_mark", type="number", nullable=true, example=null),
     *                     @OA\Property(property="result_marks", type="number", nullable=true, example=null),
     *                     @OA\Property(property="result_id", type="integer", nullable=true, example=null),
     *                     @OA\Property(property="examination_grading_option_id", type="integer", nullable=true, example=null),
     *                     @OA\Property(property="first_name", type="string", example="John"),
     *                     @OA\Property(property="last_name", type="string", example="Klein"),
     *                     @OA\Property(property="third_name", type="string", nullable=true, example=null),
     *                     @OA\Property(property="middle_name", type="string", nullable=true, example=null),
     *                     @OA\Property(property="openemis_no", type="string", example=1522412895)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
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

    

    /**
     * @OA\Post(
     *     path="/api/v4/exams/student-subject-result",
     *     summary="Retrieve student subject result for exams",
     *     description="Retrieve the subject result for a specific student in an exam",
     *     tags={"Examinations"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Parameters for retrieving student subject result",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="examination_grading_option_id", type="integer", example=1),
     *             @OA\Property(property="marks", type="integer", description="Mark is required only if examination_grading_option_id is null.", example=null),
     *             @OA\Property(property="academic_period_id", type="integer", example=31),
     *             @OA\Property(property="examination_id", type="integer", example=2),
     *             @OA\Property(property="examination_subject_id", type="integer", example=37),
     *             @OA\Property(property="examination_centre_id", type="integer", example=12),
     *             @OA\Property(property="institution_id", type="integer", example=6),
     *             @OA\Property(property="student_id", type="integer", example=1130)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
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