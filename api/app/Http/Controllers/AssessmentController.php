<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AssessmentService;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\AssessmentItemStudentExemptionRequest;

class AssessmentController extends Controller
{
    protected $assessmentService;

    public function __construct(AssessmentService $assessmentService) 
    {
        $this->assessmentService = $assessmentService;
    }

    public function getEducationGradeList(Request $request)
    {
        try {
            
            $data = $this->assessmentService->getEducationGradeList($request);
            return $this->sendSuccessResponse("Assessment Education Grade List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Assessment Education Grade List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Assessment Education Grade List Not Found');
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v4/assessments/items",
     *     summary="Get assessment items",
     *     description="Returns a list of assessment items",
     *     tags={"Assessment"},
     *     @OA\Parameter(
     *         name="academic_period_id",
     *         in="query",
     *         required=false,
     *         description="Academic Period Id",
     *         @OA\Schema(type="integer", example="id")
     *     ),
     *     @OA\Parameter(
     *         name="education_grade_id",
     *         in="query",
     *         required=false,
     *         description="Education Grade Id",
     *         @OA\Schema(type="integer", example="id")
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
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
     *                 @OA\Property(property="data", type="object",
     *                      @OA\Property(property="data", type="array",
     *                          @OA\Items(
     *                         type="object",
     *                              @OA\Property(property="id", type="string", example="02bd59fe-1e6b-40e2-bc8b-462e28ee2753"),
     *                              @OA\Property(property="weight", type="string", example="1.00"),
     *                              @OA\Property(property="classification", type="string", example=""),
     *                              @OA\Property(property="assessment_id", type="integer", example=24),
     *                              @OA\Property(property="education_subject_id", type="integer", example=17),
     *                              @OA\Property(property="modified_user_id", type="integer", nullable=true, example=null),
     *                              @OA\Property(property="modified", type="string", format="date-time", nullable=true, example=null),
     *                              @OA\Property(property="created_user_id", type="integer", example=2),
     *                              @OA\Property(property="created", type="string", format="date-time", example="2018-05-30 07:29:11"),
     *                              @OA\Property(property="education_subject_name", type="string", example="SUD-Spanish Upper Division")
     *                          )
     *                      )
     *                 )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
    public function getAssessmentItemList(Request $request)
    {
        try {
            $data = $this->assessmentService->getAssessmentItemList($request);
            return $this->sendSuccessResponse("Assessment Item List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Assessment Item List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            
            return $this->sendErrorResponse('Assessment Item List Not Found');
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v4/assessments/periods",
     *     summary="Get assessment periods",
     *     description="Returns a list of assessment periods",
     *     tags={"Assessment"},
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
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
     *                 @OA\Property(property="data", type="object",
     *                      @OA\Property(property="data", type="array",
     *                          @OA\Items(
     *                              type="object",
     *                              @OA\Property(property="id", type="integer", example=2),
     *                              @OA\Property(property="code", type="string", example="FYAK2"),
     *                              @OA\Property(property="name", type="string", example="Final Year Assessment K2"),
     *                              @OA\Property(property="start_date", type="string", format="date", example="2016-01-01"),
     *                              @OA\Property(property="end_date", type="string", format="date", example="2016-12-31"),
     *                              @OA\Property(property="date_enabled", type="string", format="date", example="2016-01-01"),
     *                              @OA\Property(property="date_disabled", type="string", format="date", example="2016-12-31"),
     *                              @OA\Property(property="weight", type="string", example="100.00"),
     *                              @OA\Property(property="academic_term", type="string", nullable=true, example=null),
     *                              @OA\Property(property="assessment_id", type="integer", example=16),
     *                              @OA\Property(property="education_grade_id", type="integer", example=171),
     *                              @OA\Property(property="education_grade_code", type="string", example="K2"),
     *                              @OA\Property(property="education_grade_name", type="string", example="K2"),
     *                              @OA\Property(property="modified_user_id", type="integer", nullable=true, example=null),
     *                              @OA\Property(property="modified", type="string", format="date-time", nullable=true, example=null),
     *                              @OA\Property(property="created_user_id", type="integer", example=2),
     *                              @OA\Property(property="created", type="string", format="date-time", example="2016-06-24 02:42:45")
     *                          )
     *                      )
     *                 )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
    public function getAssessmentPeriodList(Request $request)
    {
        try {
            
            $data = $this->assessmentService->getAssessmentPeriodList($request);
            return $this->sendSuccessResponse("Assessment Period List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Assessment Period List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
             
            return $this->sendErrorResponse('Assessment Period List Not Found');
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v4/assessments/items/grading-types",
     *     summary="Get grading types for assessment items",
     *     description="Returns a list of grading types associated with assessment items",
     *     tags={"Assessment"},
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
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
     *             @OA\Property(property="data", type="object",
     *                  @OA\Property(property="data", type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=2),
     *                         @OA\Property(property="code", type="string", example=001),
     *                         @OA\Property(property="name", type="string", example="Marks Grading Scale"),
     *                         @OA\Property(property="pass_mark", type="string", example="50.00"),
     *                         @OA\Property(property="max", type="string", example=100.00),
     *                         @OA\Property(property="result_type", type="string", example="MARKS"),
     *                         @OA\Property(property="assessment_grading_options", type="array",
     *                             @OA\Items(
     *                                 type="object",
     *                                 @OA\Property(property="id", type="integer", example="2"),
     *                                 @OA\Property(property="code", type="string", example="A"),
     *                                 @OA\Property(property="name", type="string", example="Excellent"),
     *                                 @OA\Property(property="description", type="string", example=""),
     *                                 @OA\Property(property="min", type="string", example="80.00"),
     *                                 @OA\Property(property="max", type="string", example="100.00"),
     *                                 @OA\Property(property="point", type="string", example="5.00"),
     *                                 @OA\Property(property="order", type="integer", example="1"),
     *                                 @OA\Property(property="visible", type="integer", example="1"),
     *                                 @OA\Property(property="assessment_grading_type_id", type="integer", example="2"),
     *                                 @OA\Property(property="modified_user_id", type="integer", example="2"),
     *                                 @OA\Property(property="modified", type="string", example="2023-07-28 17:40:09"),
     *                                 @OA\Property(property="created_user_id", type="integer", example="2"),
     *                                 @OA\Property(property="created", type="string", example="2023-07-28 17:40:09")
     *                             )
     *                         ),
     *                         @OA\Property(property="modified_user_id", type="integer", example="2"),
     *                         @OA\Property(property="modified", type="string", example="2023-07-28 17:40:09"),
     *                         @OA\Property(property="created_user_id", type="integer", example="2"),
     *                         @OA\Property(property="created", type="string", example="2023-07-28 17:40:09")
     *                     )
     *                  )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
    public function getAssessmentItemGradingTypeList(Request $request)
    {
        try {
            
            $data = $this->assessmentService->getAssessmentItemGradingTypeList($request);
            return $this->sendSuccessResponse("Assessment Item Grading Type List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Assessment Item Grading Type List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Assessment Item Grading Type List Not Found');
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v4/assessments/grading-options",
     *     summary="Get grading options for assessments",
     *     description="Returns a list of grading options for assessments",
     *     tags={"Assessment"},
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
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
     *             @OA\Property(property="data", type="object",
     *                  @OA\Property(property="data", type="array",
     *                      @OA\Items(
     *                      type="object",
     *                          @OA\Property(property="id", type="integer", example=2),
     *                          @OA\Property(property="code", type="string", example="A"),
     *                          @OA\Property(property="name", type="string", example="Excellent"),
     *                          @OA\Property(property="min", type="string", example="80.00"),
     *                          @OA\Property(property="max", type="string", example="100.00"),
     *                          @OA\Property(property="order", type="integer", example=1),
     *                          @OA\Property(property="visible", type="integer", example=1),
     *                          @OA\Property(property="modified_user_id", type="integer", example=2),
     *                          @OA\Property(property="modified", type="string", format="date-time", example="2023-07-28 17:40:09"),
     *                          @OA\Property(property="created_user_id", type="integer", example=1),
     *                          @OA\Property(property="created", type="string", format="date-time", example="2015-07-10 19:24:17")
     *                      )
     *                  )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
    public function getAssessmentGradingOptionList(Request $request)
    {
        try {
            
            $data = $this->assessmentService->getAssessmentGradingOptionList($request);
            return $this->sendSuccessResponse("Assessment Grading Option List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Assessment Grading Option List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
             
            return $this->sendErrorResponse('Assessment Grading Option List Not Found');
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v4/assessments/{assessment_id}/assessmentperiods",
     *     summary="Get assessment periods for a specific assessment",
     *     description="Returns a list of assessment periods associated with the specified assessment ID",
     *     tags={"Assessment"},
     *     @OA\Parameter(
     *         name="assessment_id",
     *         in="path",
     *         required=true,
     *         description="ID of the assessment",
     *         @OA\Schema(type="integer", example=31)
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
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
     *             @OA\Property(property="data", type="object",
     *                  @OA\Property(property="data", type="array",
     *                      @OA\Items(
     *                          type="object",
     *                          @OA\Property(property="id", type="string", example="First Half"),
     *                          @OA\Property(property="name", type="string", example="First Half")
     *                      )
     *                  )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
    public function getAssessmentUniquePeriodList(Request $request, $assessmentId)
    {
        try {
            
            $data = $this->assessmentService->getAssessmentUniquePeriodList($request, $assessmentId);
            return $this->sendSuccessResponse("Assessment Periods List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Assessment Periods List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
             
            return $this->sendErrorResponse('Assessment Periods List Not Found');
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v4/assessments/{assessment_id}",
     *     summary="Get assessment details by ID",
     *     description="Returns details of an assessment based on the provided assessment ID",
     *     tags={"Assessment"},
     *     @OA\Parameter(
     *         name="assessment_id",
     *         in="path",
     *         required=true,
     *         description="ID of the assessment",
     *         @OA\Schema(type="integer", example=31)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(type="object",
     *                      @OA\Property(property="id", type="integer", example=31),
     *                      @OA\Property(property="code", type="string", example="Assessments"),
     *                      @OA\Property(property="name", type="string", example="Primary 1"),
     *                      @OA\Property(property="code_name", type="string", example="Assessments - Primary 1"),
     *                      @OA\Property(property="description", type="string", example=""),
     *                      @OA\Property(property="excel_template_name", type="string", example="assessment_report_template (18).xlsx"),
     *                      @OA\Property(property="type", type="integer", example=2),
     *                      @OA\Property(property="academic_period_id", type="integer", example=31),
     *                      @OA\Property(property="education_grade_id", type="integer", example=104),
     *                      @OA\Property(property="assessment_grading_type_id", type="integer", nullable=true, example=null),
     *                      @OA\Property(property="modified_user_id", type="integer", example=2),
     *                      @OA\Property(property="modified", type="string", format="date-time", example="2022-06-13 16:05:41"),
     *                      @OA\Property(property="created_user_id", type="integer", example=2),
     *                      @OA\Property(property="created", type="string", format="date-time", example="2022-04-05 10:19:55")
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
    public function getAssessmentData(Request $request, $assessmentId)
    {
        try {
            
            $data = $this->assessmentService->getAssessmentData($request, $assessmentId);
            return $this->sendSuccessResponse("Assessment Data Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Assessment Deatils from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
             
            return $this->sendErrorResponse('Assessment Deatils Not Found');
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v4/assessments/{assessmentId}/assessmentitems",
     *     summary="Get assessment items",
     *     description="Returns details of assessment items based on the provided parameters",
     *     tags={"Assessment"},
     *     @OA\Parameter(
     *         name="assessmentId",
     *         in="path",
     *         required=true,
     *         description="ID of the assessment",
     *         @OA\Schema(type="integer", example=33)
     *     ),
     *     @OA\Parameter(
     *         name="institution_id",
     *         in="query",
     *         required=true,
     *         description="ID of the institution",
     *         @OA\Schema(type="integer", example=6)
     *     ),
     *     @OA\Parameter(
     *         name="academic_period_id",
     *         in="query",
     *         required=true,
     *         description="ID of the academic period",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Parameter(
     *         name="class_id",
     *         in="query",
     *         required=true,
     *         description="ID of the class",
     *         @OA\Schema(type="integer", example=11)
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
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
     *             @OA\Property(property="data", type="object",
     *                  @OA\Property(property="data", type="array",
     *                      @OA\Items(type="object",
     *                              @OA\Property(property="id", type="string", example="a12126ea-f0b8-4237-91ad-8719862ce1e2"),
     *                              @OA\Property(property="weight", type="string", example="0.00"),
     *                              @OA\Property(property="classification", type="string", example=""),
     *                              @OA\Property(property="InstitutionSubjects", type="object",
     *                                  @OA\Property(property="education_subject_id", type="integer", example=60),
     *                                  @OA\Property(property="id", type="integer", example=50),
     *                                  @OA\Property(property="name", type="string", example="Social Studies")
     *                              ),
     *                              @OA\Property(property="education_subject", type="object",
     *                                  @OA\Property(property="id", type="integer", example=60),
     *                                  @OA\Property(property="code_name", type="string", example="SSMC - Social Studies")
     *                              ),
     *                              @OA\Property(property="is_editable", type="integer", example=1)
     *                      )
     *                  )
     *              )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
    public function assessmentItemsList(Request $request, $assessmentId)
    {
        try {
            
            $data = $this->assessmentService->assessmentItemsList($request, $assessmentId);
            return $this->sendSuccessResponse("Assessment Item List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch assessment item list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
             
            return $this->sendErrorResponse('Assessment item list not found');
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v4/institutions/subject/student",
     *     summary="Get student details for a subject in an institution",
     *     description="Returns details of students enrolled in a specific subject in an institution based on the provided parameters",
     *     tags={"Assessment"},
     *     @OA\Parameter(
     *         name="institution_id",
     *         in="query",
     *         required=true,
     *         description="ID of the institution",
     *         @OA\Schema(type="integer", example=6)
     *     ),
     *     @OA\Parameter(
     *         name="institution_class_id",
     *         in="query",
     *         required=true,
     *         description="ID of the institution class",
     *         @OA\Schema(type="integer", example=568)
     *     ),
     *     @OA\Parameter(
     *         name="assessment_id",
     *         in="query",
     *         required=true,
     *         description="ID of the assessment",
     *         @OA\Schema(type="integer", example=32)
     *     ),
     *     @OA\Parameter(
     *         name="academic_period_id",
     *         in="query",
     *         required=true,
     *         description="ID of the academic period",
     *         @OA\Schema(type="integer", example=32)
     *     ),
     *     @OA\Parameter(
     *         name="institution_subject_id",
     *         in="query",
     *         required=true,
     *         description="ID of the institution subject",
     *         @OA\Schema(type="integer", example=4516)
     *     ),
     *     @OA\Parameter(
     *         name="education_grade_id",
     *         in="query",
     *         required=true,
     *         description="ID of the education grade",
     *         @OA\Schema(type="integer", example=189)
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="academic_period_id")
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
     *             @OA\Property(property="data", type="object",
     *                  @OA\Property(property="data", type="array",
     *                      @OA\Items(
     *                     type="object",
     *                          @OA\Property(property="total_mark", type="string", example="42.00"),
     *                          @OA\Property(property="academic_period_id", type="integer", example=32),
     *                          @OA\Property(property="education_grade_id", type="integer", example=189),
     *                          @OA\Property(property="education_subject_id", type="integer", example=69),
     *                          @OA\Property(property="student_status_id", type="integer", example=7),
     *                          @OA\Property(property="assessment_id", type="integer", example=32),
     *                          @OA\Property(property="assessment_period_id", type="integer", example=33),
     *                          @OA\Property(property="student_status_name", type="string", example="Promoted"),
     *                          @OA\Property(property="the_student_status", type="string", example="Promoted"),
     *                          @OA\Property(property="student_status_code", type="string", example="PROMOTED"),
     *                          @OA\Property(property="student_id", type="integer", example=27),
     *                          @OA\Property(property="first_name", type="string", example="Todd"),
     *                          @OA\Property(property="middle_name", type="string", nullable=true, example=null),
     *                          @OA\Property(property="third_name", type="string", nullable=true, example=null),
     *                          @OA\Property(property="last_name", type="string", example="Renner"),
     *                          @OA\Property(property="preferred_name", type="string", nullable=true, example=null),
     *                          @OA\Property(property="the_student_code", type="string", example=1522271989)
     *                      )
     *                  )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
    public function getInstitutionSubjectStudent(Request $request)
    {
        try {
            
            $data = $this->assessmentService->getInstitutionSubjectStudent($request);
            return $this->sendSuccessResponse("Institution Subject Student List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch institution subject student list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
             
            return $this->sendErrorResponse('Institution subject student list not found');
        }
    }


    //POCOR-8292 start...

    /**
     * @OA\Get(
     *     path="/api/v4/assessments/{assessment_id}/periods",
     *     summary="Get assessment periods",
     *     description="Retrieve the assessment periods for a specific assessment.",
     *     tags={"Assessment"},
     *     @OA\Parameter(
     *         name="assessment_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         example="34"
     *     ),
     *     @OA\Parameter(
     *         name="academic_term",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         example="Term 1"
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer"),
     *         example="5"
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer"),
     *         example="1"
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string"),
     *         example="id"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=37),
     *                         @OA\Property(property="code", type="string", example="Period 1"),
     *                         @OA\Property(property="name", type="string", example="Assessment Period 1"),
     *                         @OA\Property(property="start_date", type="string", format="date", example="2024-01-01"),
     *                         @OA\Property(property="end_date", type="string", format="date", example="2024-12-31"),
     *                         @OA\Property(property="date_enabled", type="string", format="date", example="2024-01-01"),
     *                         @OA\Property(property="date_disabled", type="string", format="date", example="2024-12-31"),
     *                         @OA\Property(property="weight", type="number", format="float", example="0.30"),
     *                         @OA\Property(property="academic_term", type="string", example="Term 1"),
     *                         @OA\Property(property="assessment_id", type="integer", example=34),
     *                         @OA\Property(property="editable_student_statuses", type="integer", example=0),
     *                         @OA\Property(property="modified_user_id", type="integer", example=2),
     *                         @OA\Property(property="modified", type="string", format="date-time", example="2024-01-04 12:36:14"),
     *                         @OA\Property(property="created_user_id", type="integer", example=2),
     *                         @OA\Property(property="created", type="string", format="date-time", example="2023-01-03 16:12:00")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Unsuccessful."
     *     )
     * )
     */
    public function getAssessmentViaAcademicTerm(Request $request, $assessmentId)
    {
        try {
            $params = $request->all();
            $data = $this->assessmentService->getAssessmentViaAcademicTerm($params, $assessmentId);
            return $this->sendSuccessResponse("Assessment periods list found.", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch assessment periods list from DB.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
             
            return $this->sendErrorResponse('Failed to fetch assessment periods list from DB.');
        }
    }
    //POCOR-8292 end...

    //POCOR-8292 start...

     /**
     * @OA\Post(
     *     path="/api/v4/institutions/students/assessment-item-exemption",
     *     summary="Save Assessment Item Exemption",
     *     description="Create an exemption record for a student based on the provided assessment and related details.",
     *     tags={"Assessment"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={
     *                 "assessment_id",
     *                 "education_subject_id",
     *                 "student_id",
     *                 "institution_class_id",
     *                 "education_grade_id",
     *                 "assessment_period_id"
     *             },
     *             @OA\Property(property="assessment_id", type="integer", example=38, description="ID of the assessment."),
     *             @OA\Property(property="education_subject_id", type="integer", example=37, description="ID of the education subject."),
     *             @OA\Property(property="student_id", type="integer", example=13766, description="ID of the student."),
     *             @OA\Property(property="institution_class_id", type="integer", example=611, description="ID of the institution class."),
     *             @OA\Property(property="education_grade_id", type="integer", example=224, description="ID of the education grade."),
     *             @OA\Property(property="assessment_period_id", type="integer", example=49, description="ID of the assessment period.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Exemption record created successfully.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Exemption record created successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="assessment_id", type="integer", example=38),
     *                 @OA\Property(property="education_subject_id", type="integer", example=37),
     *                 @OA\Property(property="student_id", type="integer", example=13766),
     *                 @OA\Property(property="institution_class_id", type="integer", example=611),
     *                 @OA\Property(property="education_grade_id", type="integer", example=224),
     *                 @OA\Property(property="assessment_period_id", type="integer", example=49),
     *                 @OA\Property(property="created", type="string", format="date-time", example="2024-01-01T10:00:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Validation failed."),
     *             @OA\Property(property="errors", type="object", additionalProperties=@OA\Property(type="string"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="An unexpected error occurred.")
     *         )
     *     )
     * )
     */

    // POCOR-8619 end
    public function saveAssessmentItemExemption(AssessmentItemStudentExemptionRequest $request)
    {
        try {
            $data = $this->assessmentService->assessmentItemExemption($request);
            if($data == 1){
                return $this->sendSuccessResponse("Save successfuly", $data);
            }else{
                return $this->sendErrorResponse("Student is already exempted");
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to save Exempted User Data in DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Failed to save Exempted User Data in D');
        }
    }
}
