<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ReportCardService;
use Illuminate\Support\Facades\Log;
use Exception;
use JWTAuth;
use App\Http\Requests\ReportCardGenerateRequest;

class ReportCardController extends Controller
{
    protected $reportCardService;

    public function __construct(
        ReportCardService $reportCardService
    ) {
        $this->reportCardService = $reportCardService;
    }

    //pocor-7856 starts

    /**
     * @OA\Get(
     *      path="/api/v4/institutions/classes/reportcards/subject/comments",
     *      summary="Get list of report card comments",
     *      description="Get list of report card comments",
     *      tags={"Report card"},
     *      @OA\Parameter(
     *         name="academic_period_id",
     *         in="query",
     *         required=false,
     *         description="Academic Period Id",
     *         @OA\Schema(type="integer", example="32")
     *      ),
     *      @OA\Parameter(
     *         name="institution_id",
     *         in="query",
     *         required=false,
     *         description="Institution id",
     *         @OA\Schema(type="integer", example="6")
     *      ),
     *      @OA\Parameter(
     *         name="institution_class_id",
     *         in="query",
     *         required=false,
     *         description="Institution Class Id",
     *         @OA\Schema(type="integer", example="589")
     *      ),
     *      @OA\Parameter(
     *         name="education_grade_id",
     *         in="query",
     *         required=false,
     *         description="Education Grade Id",
     *         @OA\Schema(type="integer", example="189")
     *      ),
     *      @OA\Parameter(
     *         name="report_card_id",
     *         in="query",
     *         required=false,
     *         description="Report card id",
     *         @OA\Schema(type="integer", example="7")
     *      ),
     *      @OA\Parameter(
     *         name="type",
     *         in="query",
     *         required=true,
     *         description="Type",
     *         @OA\Schema(type="integer", example="PRINCIPAL")
     *      ),
     *      @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="first_name")
     *      ),
     *      @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Limit",
     *         @OA\Schema(type="integer", example="10")
     *     ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful.",
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="student_id", type="integer", example=1),
     *                     @OA\Property(property="student_user_id", type="integer", example="1311"),
     *                     @OA\Property(property="student_openemis_no", type="integer", example=1522413076),
     *                     @OA\Property(property="student_gender", type="string", example=""),
     *                     @OA\Property(property="comments", type="string", example="comment"),
     *                     @OA\Property(property="comments_code", type="integer", example=1),
     *                     @OA\Property(property="student_status", type="object",
     *                         @OA\Property(property="id", type="integer", example=7),
     *                         @OA\Property(property="code", type="string", example="PROMOTED"),
     *                         @OA\Property(property="name", type="string", example="Promoted"),
     *                     ),
     *                     @OA\Property(property="student_status_name", type="string", example="Promoted"),
     *                     @OA\Property(property="InstitutionStudentsReportCards", type="object",
     *                         @OA\Property(property="report_card_id", type="integer", example=29),
     *                     ),
     *                     @OA\Property(property="Staff", type="object",
     *                         @OA\Property(property="first_name", type="string", example="System"),
     *                         @OA\Property(property="last_name", type="string", example="Admin"),
     *                     ),
     *                     @OA\Property(property="reportCardStartDate", type="string", example="2018-12-31"),
     *                     @OA\Property(property="reportCardEndDate", type="string", example="2018-12-31"),
     *                     @OA\Property(property="total_mark", type="string", example=175),
     *                         @OA\Property(property="_matchingData", type="object",
     *                             @OA\Property(property="Users", type="object",
     *                             @OA\Property(property="id", type="integer", example=8842),
     *                             @OA\Property(property="first_name", type="string", example="Bastien"),
     *                             @OA\Property(property="middle_name", type="string", example=""),
     *                             @OA\Property(property="third_name", type="string", example=""),
     *                             @OA\Property(property="last_name", type="string", example="Danby"),
     *                             @OA\Property(property="openemis_no", type="integer", example=1524270931),
     *                             @OA\Property(property="preferred_name", type="string", example=""),
     *                             @OA\Property(property="full_name", type="string", example="Bastien  Danby"),
     *                             @OA\Property(property="name_with_id", type="string", example="1524270931 - Bastien  Danby")
     *                         )
     *                     )
     *                 )
     *             )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Unsuccessful.",
     *      )
     * )
     */
    public function getReportCardStudents(Request $request)
    {
        try {
            $data = $this->reportCardService->getReportCardStudents($request);

            return $this->sendSuccessResponse("Report card student list found", $data);

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list.');
        }
    }


    /**
     * @OA\Get(
     *      path="/api/v4/institutions/classes/reportcards/subjects",
     *      summary="Get list of report card subjects",
     *      description="Get list of report card subjects",
     *      tags={"Report card"},
     *      @OA\Parameter(
     *         name="report_card_id",
     *         in="query",
     *         required=false,
     *         description="Report card id",
     *         @OA\Schema(type="integer", example="9")
     *      ),
     *      @OA\Parameter(
     *         name="institution_class_id",
     *         in="query",
     *         required=false,
     *         description="Institution Class id",
     *         @OA\Schema(type="integer", example="591")
     *      ),
     *      @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="order")
     *      ),
     *      @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", example="1")
     *      ),
     *      @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Limit",
     *         @OA\Schema(type="integer", example="10")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful.",
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="education_subject_id", type="integer", example=1),
     *                     @OA\Property(property="education_subject_code", type="string", example="LAC"),
     *                     @OA\Property(property="institution_subjects_name", type="string", example="Language Arts Content Standards and Learning Outcomes"),
     *                     @OA\Property(property="institution_subjects_id", type="integer", example=1),
     *                     @OA\Property(property="order", type="integer", example=1),
     *                     @OA\Property(property="staff_id", type="integer", example=573),
     *                 )
     *             )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Unsuccessful.",
     *      )
     * )
     */
    public function getReportCardSubjects(Request $request)
    {
        try {
            $data = $this->reportCardService->getReportCardSubjects($request);

            return $this->sendSuccessResponse("Report card subject list found", $data);

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list.');
        }
    }

    //pocor-7856 ends


    //For pocor-8260 start...

    /**
     * @OA\Get(
     *     path="/api/v4/institutions/classes/reportcards/comment/codes",
     *     summary="Get a list of report card comment codes",
     *     description="Retrieve a list of report card comment codes.",
     *     tags={"Report card"},
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Field by which to order the results",
     *         @OA\Schema(type="string", example="order")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number for pagination",
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
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="A keen student who does well in the subject."),
     *                     @OA\Property(property="order", type="integer", example=1),
     *                     @OA\Property(property="visible", type="integer", example=1),
     *                     @OA\Property(property="editable", type="integer", example=1),
     *                     @OA\Property(property="default", type="integer", example=0),
     *                     @OA\Property(property="international_code", type="string", example=""),
     *                     @OA\Property(property="national_code", type="string", example=""),
     *                     @OA\Property(property="modified_user_id", type="integer", nullable=true, example=null),
     *                     @OA\Property(property="modified", type="string", format="date-time", nullable=true, example=null),
     *                     @OA\Property(property="created_user_id", type="integer", example=2),
     *                     @OA\Property(property="created", type="string", format="date-time", example="2018-04-18 08:47:42")
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
    public function getReportCardCommentCodes(Request $request)
    {
        try {
            $params = $request->all();
            $data = $this->reportCardService->getReportCardCommentCodes($params);

            return $this->sendSuccessResponse("Report card comment codes list found", $data);

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list.');
        }
    }
    //For pocor-8260 end...


    //For pocor-8270 start...

    /**
     * @OA\Get(
     *     path="/api/v4/security-roles/{roleId}",
     *     summary="Get security role details",
     *     description="Retrieve details of a specific security role by ID",
     *     tags={"Users"},
     *     @OA\Parameter(
     *         name="roleId",
     *         in="path",
     *         required=true,
     *         description="ID of the security role",
     *         @OA\Schema(type="integer", example=4)
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
     *                 @OA\Property(property="id", type="integer", example=4),
     *                 @OA\Property(property="name", type="string", example="Principal"),
     *                 @OA\Property(property="code", type="string", example="PRINCIPAL"),
     *                 @OA\Property(property="order", type="integer", example=5),
     *                 @OA\Property(property="visible", type="integer", example=1),
     *                 @OA\Property(property="security_group_id", type="integer", example=-1),
     *                 @OA\Property(property="modified_user_id", type="integer", example=2),
     *                 @OA\Property(property="modified", type="string", format="date-time", example="2022-06-14 17:34:20"),
     *                 @OA\Property(property="created_user_id", type="integer", example=1),
     *                 @OA\Property(property="created", type="string", format="date-time", example="1990-01-01 00:00:00")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
    public function getSecurityRoleData(Request $request, $roleId)
    {
        try {
            $params = $request->all();
            $data = $this->reportCardService->getSecurityRoleData($params, $roleId);

            return $this->sendSuccessResponse("Security role data found.", $data);

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch data.');
        }
    }


    /**
     * @OA\Get(
     *     path="/api/v4/reportcards/{reportCardId}",
     *     summary="Get report card details",
     *     description="Retrieve details of a specific report card by ID",
     *     tags={"Report card"},
     *     @OA\Parameter(
     *         name="reportCardId",
     *         in="path",
     *         required=true,
     *         description="ID of the report card.",
     *         @OA\Schema(type="integer", example=9)
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
     *                 @OA\Property(property="id", type="integer", example=9),
     *                 @OA\Property(property="code", type="string", example="P1 Test"),
     *                 @OA\Property(property="name", type="string", example="P1 Test"),
     *                 @OA\Property(property="description", type="string", example=""),
     *                 @OA\Property(property="start_date", type="string", format="date", example="2023-01-01"),
     *                 @OA\Property(property="end_date", type="string", format="date", example="2023-06-30"),
     *                 @OA\Property(property="generate_start_date", type="string", format="date-time", example="2023-01-01 00:00:00"),
     *                 @OA\Property(property="generate_end_date", type="string", format="date-time", example="2023-12-31 00:00:00"),
     *                 @OA\Property(property="principal_comments_required", type="integer", example=1),
     *                 @OA\Property(property="homeroom_teacher_comments_required", type="integer", example=1),
     *                 @OA\Property(property="teacher_comments_required", type="integer", example=1),
     *                 @OA\Property(property="excel_template_name", type="string", nullable=true, example=null),
     *                 @OA\Property(property="pdf_page_number", type="integer", nullable=true, example=null),
     *                 @OA\Property(property="academic_period_id", type="integer", example=33),
     *                 @OA\Property(property="education_grade_id", type="integer", example=206),
     *                 @OA\Property(property="modified_user_id", type="integer", nullable=true, example=null),
     *                 @OA\Property(property="modified", type="string", format="date-time", example="1970-01-01 00:00:00"),
     *                 @OA\Property(property="created_user_id", type="integer", example=2),
     *                 @OA\Property(property="created", type="string", format="date-time", example="2023-07-28 17:41:00")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
    public function getReportCardData(Request $request, $reportCardId)
    {
        try {
            $params = $request->all();
            $data = $this->reportCardService->getReportCardData($params, $reportCardId);

            return $this->sendSuccessResponse("Report card data found.", $data);

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Failed to fetch data.');
        }
    }
    //For pocor-8270 end...



    //For POCOR-8617 Start...
    /**
     * @OA\Post(
     *     path="/api/v4/institutions/{institutionId}/classes/{classId}/student-report-cards/{studentId}/pdf",
     *     summary="Download a student's report card in PDF format",
     *     description="Retrieve a student's report card for a specific academic period and report card ID in PDF format.",
     *     tags={"Report Card"},
     *     @OA\Parameter(
     *         name="institutionId",
     *         in="path",
     *         required=true,
     *         description="The ID of the institution",
     *         @OA\Schema(type="integer", example=6)
     *     ),
     *     @OA\Parameter(
     *         name="classId",
     *         in="path",
     *         required=true,
     *         description="The ID of the class",
     *         @OA\Schema(type="integer", example=609)
     *     ),
     *     @OA\Parameter(
     *         name="studentId",
     *         in="path",
     *         required=true,
     *         description="The ID of the student",
     *         @OA\Schema(type="integer", example=13685)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"academic_period_id", "institution_id", "education_grade_id", "institution_class_id", "student_id", "report_card_id"},
     *             @OA\Property(property="academic_period_id", type="integer", example=34, description="The ID of the academic period"),
     *             @OA\Property(property="institution_id", type="integer", example=6, description="The ID of the institution"),
     *             @OA\Property(property="education_grade_id", type="integer", example=223, description="The ID of the education grade"),
     *             @OA\Property(property="institution_class_id", type="integer", example=609, description="The ID of the institution class"),
     *             @OA\Property(property="student_id", type="integer", example=13685, description="The ID of the student"),
     *             @OA\Property(property="report_card_id", type="integer", example=12, description="The ID of the report card")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Report card PDF generated successfully.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="file_name", type="string", example="ReportCard_13685.pdf"),
     *                 @OA\Property(property="file_url", type="string", example="https://example.com/storage/reports/ReportCard_13685.pdf")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid request."
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Report card not found."
     *     )
     * )
     */


    public function studentReportCardPdfDownload(ReportCardGenerateRequest $request, $institutionId, $classId, $studentId)
    {
        try {
            $params = $request->all();
            //POCOR-8728 starts
            if (empty($params['academic_period_id'])) {
                return $this->sendErrorResponse('Please send academic period in request.', 400);
            }
    
            if (empty($params['report_card_id'])) {
                return $this->sendErrorResponse('Please send Report Card Id in request.', 400);
            }//POCOR-8728 ends
            
            $data = $this->reportCardService->studentReportCardPdfDownload($params, $institutionId, $classId, $studentId);
            
            if(!empty($data)){
                return $this->sendSuccessResponse("Report card pdf file found.", $data);
            } else {
                return $this->sendErrorResponse('Report card pdf file not found.');
            }


        } catch (\Exception $e) {
            Log::error(
                'Failed to generate student report card in PDF.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Failed to generate student report card in PDF.');
        }
    }


    /**
     * @OA\Post(
     *     path="/api/v4/institutions/{institutionId}/classes/{classId}/student-report-cards/{studentId}/xls",
     *     summary="Download a student's report card in Excel format",
     *     description="Retrieve a student's report card for a specific academic period and report card ID in Excel format.",
     *     tags={"Report card"},
     *     @OA\Parameter(
     *         name="institutionId",
     *         in="path",
     *         required=true,
     *         description="The ID of the institution",
     *         @OA\Schema(type="integer", example=6)
     *     ),
     *     @OA\Parameter(
     *         name="classId",
     *         in="path",
     *         required=true,
     *         description="The ID of the class",
     *         @OA\Schema(type="integer", example=609)
     *     ),
     *     @OA\Parameter(
     *         name="studentId",
     *         in="path",
     *         required=true,
     *         description="The ID of the student",
     *         @OA\Schema(type="integer", example=13685)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"academic_period_id", "institution_id", "education_grade_id", "institution_class_id", "student_id", "report_card_id"},
     *             @OA\Property(property="academic_period_id", type="integer", example=34, description="The ID of the academic period"),
     *             @OA\Property(property="institution_id", type="integer", example=6, description="The ID of the institution"),
     *             @OA\Property(property="education_grade_id", type="integer", example=223, description="The ID of the education grade"),
     *             @OA\Property(property="institution_class_id", type="integer", example=609, description="The ID of the institution class"),
     *             @OA\Property(property="student_id", type="integer", example=13685, description="The ID of the student"),
     *             @OA\Property(property="report_card_id", type="integer", example=12, description="The ID of the report card")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Report card Excel file generated successfully.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="file_name", type="string", example="ReportCard_13685.xlsx"),
     *                 @OA\Property(property="file_url", type="string", example="https://example.com/storage/reports/ReportCard_13685.xlsx")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid request."
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Report card not found."
     *     )
     * )
     */

    public function studentReportCardExcelDownload(ReportCardGenerateRequest $request, $institutionId, $classId, $studentId)
    {
        try {
            $params = $request->all();
            //POCOR-8728 starts
            if (empty($params['academic_period_id'])) {
                return $this->sendErrorResponse('Please send academic period in request.', 400);
            }
    
            if (empty($params['report_card_id'])) {
                return $this->sendErrorResponse('Please send Report Card Id in request.', 400);
            }//POCOR-8728 ends
            $data = $this->reportCardService->studentReportCardExcelDownload($params, $institutionId, $classId, $studentId);

            if(!empty($data)){
                return $this->sendSuccessResponse("Report card excel file found.", $data);
            } else {
                return $this->sendErrorResponse('Report card excel file not found.');
            }


        } catch (\Exception $e) {
            Log::error(
                'Failed to generate student report card in excel.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Failed to generate student report card in excel.');
        }
    }
    //For POCOR-8617 End...
}
