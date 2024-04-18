<?php

namespace App\Http\Controllers\Administration\Examinations\Exams;

use App\Http\Controllers\Controller;
use App\Models\Examination;
use App\Services\ExaminationService;
use Exception;

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
            $examinationCenter =  $this->examinationService->getCenterExaminationDetails($examinationId, $centerId);

            if (!$examinationCenter) {
                return $this->sendErrorResponse('Center examination not found.');
            }
            return $this->sendSuccessResponse('Center examination found.', $examinationCenter);
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
}