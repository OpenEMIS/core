<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ReportCardService;
use Illuminate\Support\Facades\Log;
use Exception;
use JWTAuth;

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
     *      tags={"Report card"},
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
     *      tags={"Report card"},
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
}