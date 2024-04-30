<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssessmentItemResultRequest;
use Illuminate\Http\Request;
use App\Services\InstitutionService;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\ReportCardCommentAdd;
use App\Http\Requests\ReportCardCommentHomeroomAdd;
use App\Http\Requests\CompetencyResultsAddRequest;
use App\Http\Requests\CompetencyCommentAddRequest;
use App\Http\Requests\CompetencyPeriodCommentAddRequest;
use App\Http\Requests\DeleteClassAttendanceRequest;
use App\Http\Requests\StudentBehavioursRequest;
use App\Http\Requests\InstitutionStudentAddRequest;
use App\Http\Requests\StaffPayslipsRequest;
use App\Http\Requests\InstitutionMealStudentsRequest;
use App\Http\Requests\InstitutionMealDistributionRequest;
use App\Http\Requests\InstitutionsAddRequest;
use App\Models\InstitutionClassGrades;
use App\Models\InstitutionClassSubjects;
use App\Models\InstitutionRooms;
use Exception;
use JWTAuth;


class InstitutionController extends Controller
{
    protected $institutionService;

    public function __construct(
        InstitutionService $institutionService
    ) {
        $this->institutionService = $institutionService;
    }


    /**
     * @OA\Get(
     *     path="/api/v4/institutions",
     *     summary="Get a list of All Institutions",
     *     description="Returns a list of institutions based on the provided parameters",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Number of items to return per page",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=6),
     *                         @OA\Property(property="name", type="string", example="Avory Primary School"),
     *                         @OA\Property(property="alternative_name", type="string", nullable=true, example=null),
     *                         @OA\Property(property="code", type="string", example="P1002"),
     *                         @OA\Property(property="address", type="string", nullable=true, example="270 Duke Lane"),
     *                         @OA\Property(property="postal_code", type="string", nullable=true, example=null),
     *                         @OA\Property(property="contact_person", type="string", nullable=true, example=null),
     *                         @OA\Property(property="telephone", type="string", nullable=true, example="83948723"),
     *                         @OA\Property(property="fax", type="string", nullable=true, example="83948723"),
     *                         @OA\Property(property="email", type="string", nullable=true, example="contact@avoryprimary.com"),
     *                         @OA\Property(property="website", type="string", nullable=true, example="avoryprimary.com"),
     *                         @OA\Property(property="date_opened", type="string", format="date", nullable=true, example="2014-12-01"),
     *                         @OA\Property(property="year_opened", type="integer", nullable=true, example="2014"),
     *                         @OA\Property(property="date_closed", type="string", format="date", nullable=true, example=null),
     *                         @OA\Property(property="year_closed", type="integer", nullable=true, example=null),
     *                         @OA\Property(property="longitude", type="string", nullable=true, example="76.76315917262934"),
     *                         @OA\Property(property="latitude", type="string", nullable=true, example="-15.378664747523954"),
     *                         @OA\Property(property="logo_name", type="string", nullable=true, example=null),
     *                         @OA\Property(property="logo_content", type="string", nullable=true, example=null),
     *                         @OA\Property(property="shift_type", type="integer", nullable=true, example=3),
     *                         @OA\Property(property="classification", type="integer", nullable=true, example=1),
     *                         @OA\Property(property="area_id", type="integer", nullable=true, example=11),
     *                         @OA\Property(property="area_administrative_id", type="integer", nullable=true, example=23),
     *                         @OA\Property(property="institution_locality_id", type="integer", nullable=true, example=1),
     *                         @OA\Property(property="institution_locality_name", type="string", nullable=true, example="Urban"),
     *                         @OA\Property(property="institution_locality_international_code", type="string", nullable=true, example=null),
     *                         @OA\Property(property="institution_locality_national_code", type="string", nullable=true, example=null),
     *                         @OA\Property(property="institution_ownership_id", type="integer", nullable=true, example=4),
     *                         @OA\Property(property="institution_ownership_name", type="string", nullable=true, example="Freehold"),
     *                         @OA\Property(property="institution_ownership_international_code", type="string", nullable=true, example=null),
     *                         @OA\Property(property="institution_ownership_national_code", type="string", nullable=true, example=null),
     *                         @OA\Property(property="institution_provider_id", type="integer", nullable=true, example=2),
     *                         @OA\Property(property="institution_provider_name", type="string", nullable=true, example="Government"),
     *                         @OA\Property(property="institution_provider_international_code", type="string", nullable=true, example=null),
     *                         @OA\Property(property="institution_provider_national_code", type="string", nullable=true, example=null),
     *                         @OA\Property(property="institution_sector_id", type="integer", nullable=true, example=1),
     *                         @OA\Property(property="institution_sector_name", type="string", nullable=true, example="Public"),
     *                         @OA\Property(property="institution_sector_international_code", type="string", nullable=true, example=null),
     *                         @OA\Property(property="institution_sector_national_code", type="string", nullable=true, example=null),
     *                         @OA\Property(property="institution_type_id", type="integer", nullable=true, example=2),
     *                         @OA\Property(property="institution_type_name", type="string", nullable=true, example="Primary"),
     *                         @OA\Property(property="institution_type_international_code", type="string", nullable=true, example=null),
     *                         @OA\Property(property="institution_type_national_code", type="string", nullable=true, example=null),
     *                         @OA\Property(property="institution_gender_id", type="integer", nullable=true, example=1),
     *                         @OA\Property(property="institution_gender_name", type="string", nullable=true, example="Mixed"),
     *                         @OA\Property(property="institution_gender_code", type="string", nullable=true, example="X"),
     *                         @OA\Property(property="institution_status_id", type="integer", nullable=true, example=1),
     *                         @OA\Property(property="institution_status_name", type="string", nullable=true, example="Active"),
     *                         @OA\Property(property="modified_user_id", type="integer", nullable=true, example=2),
     *                         @OA\Property(property="modified", type="string", format="date-time", nullable=true, example="2024-04-29 21:06:35"),
     *                         @OA\Property(property="created_user_id", type="integer", nullable=true, example=2),
     *                         @OA\Property(property="created", type="string", format="date-time", nullable=true, example="2024-04-29 21:06:35")
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
    public function getInstitutionsList(Request $request)
    {
        try {

            $data = $this->institutionService->getInstitutions($request);
            return $this->sendSuccessResponse("Successful.", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution List Not Found');
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v4/institutions/{institutionId}",
     *     summary="Get a list of All Institutions",
     *     description="Returns a list of institutions based on the provided parameters",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="institutionId",
     *         in="path",
     *         required=true,
     *         description="Institution Id.",
     *         @OA\Schema(type="integer", example=6)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Number of items to return per page",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="object",
     *                         @OA\Property(property="id", type="integer", example=6),
     *                         @OA\Property(property="name", type="string", example="Avory Primary School"),
     *                         @OA\Property(property="alternative_name", type="string", nullable=true, example=null),
     *                         @OA\Property(property="code", type="string", example="P1002"),
     *                         @OA\Property(property="address", type="string", nullable=true, example="270 Duke Lane"),
     *                         @OA\Property(property="postal_code", type="string", nullable=true, example=null),
     *                         @OA\Property(property="contact_person", type="string", nullable=true, example=null),
     *                         @OA\Property(property="telephone", type="string", nullable=true, example="83948723"),
     *                         @OA\Property(property="fax", type="string", nullable=true, example="83948723"),
     *                         @OA\Property(property="email", type="string", nullable=true, example="contact@avoryprimary.com"),
     *                         @OA\Property(property="website", type="string", nullable=true, example="avoryprimary.com"),
     *                         @OA\Property(property="date_opened", type="string", format="date", nullable=true, example="2014-12-01"),
     *                         @OA\Property(property="year_opened", type="integer", nullable=true, example="2014"),
     *                         @OA\Property(property="date_closed", type="string", format="date", nullable=true, example=null),
     *                         @OA\Property(property="year_closed", type="integer", nullable=true, example=null),
     *                         @OA\Property(property="longitude", type="string", nullable=true, example="76.76315917262934"),
     *                         @OA\Property(property="latitude", type="string", nullable=true, example="-15.378664747523954"),
     *                         @OA\Property(property="logo_name", type="string", nullable=true, example=null),
     *                         @OA\Property(property="logo_content", type="string", nullable=true, example=null),
     *                         @OA\Property(property="shift_type", type="integer", nullable=true, example=3),
     *                         @OA\Property(property="classification", type="integer", nullable=true, example=1),
     *                         @OA\Property(property="area_id", type="integer", nullable=true, example=11),
     *                         @OA\Property(property="area_administrative_id", type="integer", nullable=true, example=23),
     *                         @OA\Property(property="institution_locality_id", type="integer", nullable=true, example=1),
     *                         @OA\Property(property="institution_locality_name", type="string", nullable=true, example="Urban"),
     *                         @OA\Property(property="institution_locality_international_code", type="string", nullable=true, example=null),
     *                         @OA\Property(property="institution_locality_national_code", type="string", nullable=true, example=null),
     *                         @OA\Property(property="institution_ownership_id", type="integer", nullable=true, example=4),
     *                         @OA\Property(property="institution_ownership_name", type="string", nullable=true, example="Freehold"),
     *                         @OA\Property(property="institution_ownership_international_code", type="string", nullable=true, example=null),
     *                         @OA\Property(property="institution_ownership_national_code", type="string", nullable=true, example=null),
     *                         @OA\Property(property="institution_provider_id", type="integer", nullable=true, example=2),
     *                         @OA\Property(property="institution_provider_name", type="string", nullable=true, example="Government"),
     *                         @OA\Property(property="institution_provider_international_code", type="string", nullable=true, example=null),
     *                         @OA\Property(property="institution_provider_national_code", type="string", nullable=true, example=null),
     *                         @OA\Property(property="institution_sector_id", type="integer", nullable=true, example=1),
     *                         @OA\Property(property="institution_sector_name", type="string", nullable=true, example="Public"),
     *                         @OA\Property(property="institution_sector_international_code", type="string", nullable=true, example=null),
     *                         @OA\Property(property="institution_sector_national_code", type="string", nullable=true, example=null),
     *                         @OA\Property(property="institution_type_id", type="integer", nullable=true, example=2),
     *                         @OA\Property(property="institution_type_name", type="string", nullable=true, example="Primary"),
     *                         @OA\Property(property="institution_type_international_code", type="string", nullable=true, example=null),
     *                         @OA\Property(property="institution_type_national_code", type="string", nullable=true, example=null),
     *                         @OA\Property(property="institution_gender_id", type="integer", nullable=true, example=1),
     *                         @OA\Property(property="institution_gender_name", type="string", nullable=true, example="Mixed"),
     *                         @OA\Property(property="institution_gender_code", type="string", nullable=true, example="X"),
     *                         @OA\Property(property="institution_status_id", type="integer", nullable=true, example=1),
     *                         @OA\Property(property="institution_status_name", type="string", nullable=true, example="Active"),
     *                         @OA\Property(property="modified_user_id", type="integer", nullable=true, example=2),
     *                         @OA\Property(property="modified", type="string", format="date-time", nullable=true, example="2024-04-29 21:06:35"),
     *                         @OA\Property(property="created_user_id", type="integer", nullable=true, example=2),
     *                         @OA\Property(property="created", type="string", format="date-time", nullable=true, example="2024-04-29 21:06:35")
     *                     
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
    public function getInstitutionData(int $id)
    {
        try {

            $data = $this->institutionService->getInstitutionData($id);
            return $this->sendSuccessResponse("Institutions Data Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution Data Not Found');
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v4/institutions/grades",
     *     summary="Get grades of institutions",
     *     description="Returns a list of grades available in institutions.",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Number of items to return per page",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=76),
     *                         @OA\Property(property="code", type="string", example="Kindergarten 1"),
     *                         @OA\Property(property="name", type="string", example="Kindergarten 1"),
     *                         @OA\Property(property="admission_age", type="integer", example=5),
     *                         @OA\Property(property="order", type="integer", example=1),
     *                         @OA\Property(property="visible", type="integer", example=1),
     *                         @OA\Property(property="education_stage_id", type="integer", example=14),
     *                         @OA\Property(property="education_programme_id", type="integer", example=8),
     *                         @OA\Property(property="modified_user_id", type="integer", example=2),
     *                         @OA\Property(property="modified", type="string", example="2018-03-28 15:22:40"),
     *                         @OA\Property(property="created_user_id", type="integer", example=2),
     *                         @OA\Property(property="created", type="string", example="2016-05-25 09:52:26")
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
    public function getGradesList(Request $request)
    {
        try {
            

            $data = $this->institutionService->getGradesList($request);
            return $this->sendSuccessResponse("Grades List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Grades List Not Found');
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v4/institutions/{institutionId}/grades",
     *     summary="Get grades for a specific institution",
     *     description="Returns grades associated with a specific institution based on the provided parameters",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="institutionId",
     *         in="path",
     *         required=true,
     *         description="ID of the institution",
     *         @OA\Schema(type="integer", example=6)
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
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=9),
     *                         @OA\Property(property="education_grade_id", type="integer", example=59),
     *                         @OA\Property(property="academic_period_id", type="integer", example=30),
     *                         @OA\Property(property="start_date", type="string", format="date", example="2021-01-01"),
     *                         @OA\Property(property="start_year", type="integer", example=2021),
     *                         @OA\Property(property="end_date", type="string", nullable=true, format="date", example=null),
     *                         @OA\Property(property="end_year", type="integer", nullable=true, example=null),
     *                         @OA\Property(property="institution_id", type="integer", example=6),
     *                         @OA\Property(property="modified_user_id", type="integer", example=2),
     *                         @OA\Property(property="modified", type="string", format="date-time", example="2020-01-08 01:55:30"),
     *                         @OA\Property(property="created_user_id", type="integer", example=2),
     *                         @OA\Property(property="created", type="string", format="date-time", example="2020-01-08 01:55:30"),
     *                         @OA\Property(property="education_grades", type="object",
     *                             @OA\Property(property="id", type="integer", example=59),
     *                             @OA\Property(property="code", type="string", example="Primary 1"),
     *                             @OA\Property(property="name", type="string", example="Primary 1"),
     *                             @OA\Property(property="admission_age", type="integer", example=7),
     *                             @OA\Property(property="order", type="integer", example=1),
     *                             @OA\Property(property="visible", type="integer", example=1),
     *                             @OA\Property(property="education_stage_id", type="integer", example=1),
     *                             @OA\Property(property="education_programme_id", type="integer", example=9),
     *                             @OA\Property(property="modified_user_id", type="integer", nullable=true, example=null),
     *                             @OA\Property(property="modified", type="string", nullable=true, format="date-time", example=null),
     *                             @OA\Property(property="created_user_id", type="integer", example=2),
     *                             @OA\Property(property="created", type="string", format="date-time", example="2014-09-20 22:36:24")
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
    public function getInstitutionGradeList(Request $request, int $institutionId)
    {
        try {
            $data = $this->institutionService->getInstitutionGradeList($request, $institutionId);
            return $this->sendSuccessResponse("Grades List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Grades List Not Found');
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v4/institutions/{institution_id}/grades/{grade_id}",
     *     summary="Get details of a specific grade in an institution",
     *     description="Returns details of a specific grade in an institution based on the provided parameters",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="institution_id",
     *         in="path",
     *         required=true,
     *         description="ID of the institution",
     *         @OA\Schema(type="integer", example=6)
     *     ),
     *     @OA\Parameter(
     *         name="grade_id",
     *         in="path",
     *         required=true,
     *         description="ID of the grade",
     *         @OA\Schema(type="integer", example=59)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=59),
     *                 @OA\Property(property="code", type="string", example="Primary 1"),
     *                 @OA\Property(property="name", type="string", example="Primary 1"),
     *                 @OA\Property(property="admission_age", type="integer", example=7),
     *                 @OA\Property(property="order", type="integer", example=1),
     *                 @OA\Property(property="visible", type="integer", example=1),
     *                 @OA\Property(property="education_stage_id", type="integer", example=1),
     *                 @OA\Property(property="education_programme_id", type="integer", example=9),
     *                 @OA\Property(property="modified_user_id", type="integer", nullable=true),
     *                 @OA\Property(property="modified", type="string", format="datetime", example="2015-09-30 10:51:18"),
     *                 @OA\Property(property="created_user_id", type="integer", example=1),
     *                 @OA\Property(property="created", type="string", format="datetime", example="2014-09-20 22:36:24")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
    public function getInstitutionGradeData(int $institutionId, int $gradeId)
    {
        try {
            $data = $this->institutionService->getInstitutionGradeData($institutionId, $gradeId);
            return $this->sendSuccessResponse("Grades Data Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Grades Data Not Found');
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v4/institutions/classes",
     *     summary="Get classes for an academic period",
     *     description="Returns a list of classes for a specific academic period based on the provided parameters",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="academic_period_id",
     *         in="query",
     *         required=true,
     *         description="ID of the academic period",
     *         @OA\Schema(type="integer", example=10)
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
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Kindergarten 1-A"),
     *                         @OA\Property(property="class_number", type="integer", example=1),
     *                         @OA\Property(property="capacity", type="integer", example=100),
     *                         @OA\Property(property="total_male_students", type="integer", example=10),
     *                         @OA\Property(property="total_female_students", type="integer", example=16),
     *                         @OA\Property(property="staff_id", type="integer", nullable=true, example=null),
     *                         @OA\Property(property="institution_shift_id", type="integer", example=1),
     *                         @OA\Property(property="institution_id", type="integer", example=1),
     *                         @OA\Property(property="institution_unit_id", type="integer", nullable=true, example=null),
     *                         @OA\Property(property="institution_course_id", type="integer", nullable=true, example=null),
     *                         @OA\Property(property="academic_period_id", type="integer", example=10),
     *                         @OA\Property(property="modified_user_id", type="integer", nullable=true, example=null),
     *                         @OA\Property(property="modified", type="string", format="date-time", nullable=true, example=null),
     *                         @OA\Property(property="created_user_id", type="integer", example=2),
     *                         @OA\Property(property="created", type="string", format="date-time", example="2018-03-28 16:35:19"),
     *                         @OA\Property(property="grades", type="array",
     *                             @OA\Items(
     *                                 type="object",
     *                                 @OA\Property(property="institution_class_id", type="integer", example=1),
     *                                 @OA\Property(property="grade_id", type="integer", example=76)
     *                             )
     *                         ),
     *                         @OA\Property(property="subjects", type="array",
     *                             @OA\Items(
     *                                 type="object",
     *                                 @OA\Property(property="institution_class_id", type="integer", example=1),
     *                                 @OA\Property(property="subject_id", type="integer", example=1)
     *                             )
     *                         ),
     *                         @OA\Property(property="students", type="array",
     *                             @OA\Items(
     *                                 type="object",
     *                                 @OA\Property(property="institution_class_id", type="integer", example=1),
     *                                 @OA\Property(property="student_id", type="integer", example=3)
     *                             )
     *                         ),
     *                         @OA\Property(property="secondary_teachers", type="array",
     *                             @OA\Items(
     *                                 type="object"
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
    public function getClassesList(Request $request)
    {
        try {
            $data = $this->institutionService->getClassesList($request);
            return $this->sendSuccessResponse("Classes List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Classes List Not Found');
        }
    }




    public function getInstitutionClassesList(Request $request, int $institutionId)
    {
        try {
            $data = $this->institutionService->getInstitutionClassesList($request, $institutionId);
            return $this->sendSuccessResponse("Classes List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Classes List Not Found');
        }
    }



    /**
     * @OA\Get(
     *     path="/pocor-openemis-core/api/v4/institutions/{institutionId}/classes/{classId}",
     *     summary="Get details of a specific class in an institution",
     *     description="Returns details of a specific class in an institution based on the provided class ID",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="institutionId",
     *         in="path",
     *         required=true,
     *         description="ID of the institution",
     *         @OA\Schema(type="integer", example=6)
     *     ),
     *     @OA\Parameter(
     *         name="classId",
     *         in="path",
     *         required=true,
     *         description="ID of the class",
     *         @OA\Schema(type="integer", example=9)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=9),
     *                 @OA\Property(property="name", type="string", example="Primary 1-A"),
     *                 @OA\Property(property="class_number", type="integer", example=1),
     *                 @OA\Property(property="capacity", type="integer", example=100),
     *                 @OA\Property(property="total_male_students", type="integer", example=31),
     *                 @OA\Property(property="total_female_students", type="integer", example=19),
     *                 @OA\Property(property="staff_id", type="integer", example=575),
     *                 @OA\Property(property="institution_shift_id", type="integer", example=5),
     *                 @OA\Property(property="institution_id", type="integer", example=6),
     *                 @OA\Property(property="institution_unit_id", type="integer", nullable=true, example=null),
     *                 @OA\Property(property="institution_course_id", type="integer", nullable=true, example=null),
     *                 @OA\Property(property="academic_period_id", type="integer", example=10),
     *                 @OA\Property(property="modified_user_id", type="integer", example=2),
     *                 @OA\Property(property="modified", type="string", example="2018-03-30 23:48:00"),
     *                 @OA\Property(property="created_user_id", type="integer", example=2),
     *                 @OA\Property(property="created", type="string", example="2018-03-30 23:48:00"),
     *                 @OA\Property(property="grades", type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="institution_class_id", type="integer", example=9),
     *                         @OA\Property(property="grade_id", type="integer", example=59)
     *                     )
     *                 ),
     *                 @OA\Property(property="subjects", type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="institution_class_id", type="integer", example=9),
     *                         @OA\Property(property="subject_id", type="integer", example=40)
     *                     )
     *                 ),
     *                 @OA\Property(property="students", type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="institution_class_id", type="integer", example=9),
     *                         @OA\Property(property="student_id", type="integer", example=805)
     *                     )
     *                 ),
     *                 @OA\Property(property="secondary_teachers", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
    public function getInstitutionClassData(int $institutionId, int $classId)
    {
        try {
            $data = $this->institutionService->getInstitutionClassData($institutionId, $classId);
            return $this->sendSuccessResponse("Class Data Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Class Data Not Found');
        }
    }


    public function getSubjectsList(Request $request)
    {
        try {
            $data = $this->institutionService->getSubjectsList($request);
            return $this->sendSuccessResponse("Subjects List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Subjects List Not Found');
        }
    }


    public function getInstitutionSubjectsList(Request $request, int $institutionId)
    {
        try {
            $data = $this->institutionService->getInstitutionSubjectsList($request, $institutionId);
            return $this->sendSuccessResponse("Subjects List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Subjects List Not Found');
        }
    }


    public function getInstitutionSubjectsData(int $institutionId, int $subjectId)
    {
        try {
            $data = $this->institutionService->getInstitutionSubjectsData($institutionId, $subjectId);
            return $this->sendSuccessResponse("Subjects Data Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Subjects Data Not Found');
        }
    }


    public function getInstitutionShifts(Request $request)
    {
        try {
            $data = $this->institutionService->getInstitutionShifts($request);
            return $this->sendSuccessResponse("Shifts List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Shifts List Not Found');
        }
    }


    public function getInstitutionShiftsList(Request $request, int $institutionId)
    {
        try {
            $data = $this->institutionService->getInstitutionShiftsList($request, $institutionId);
            return $this->sendSuccessResponse("Shifts List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Shifts List Not Found');
        }
    }


    public function getInstitutionShiftsData(int $institutionId, int $shiftId)
    {
        try {
            $data = $this->institutionService->getInstitutionShiftsData($institutionId, $shiftId);
            return $this->sendSuccessResponse("Shifts Data Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Shifts Data Not Found');
        }
    }


    public function getInstitutionAreas(Request $request)
    {
        try {
            $data = $this->institutionService->getInstitutionAreas($request);
            return $this->sendSuccessResponse("Areas List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Areas List Not Found');
        }
    }


    public function getInstitutionAreasList(Request $request, int $institutionId)
    {
        try {
            $data = $this->institutionService->getInstitutionAreasList($request, $institutionId);
            return $this->sendSuccessResponse("Areas List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Areas List Not Found');
        }
    }


    public function getInstitutionAreasData(int $institutionId, int $areaAdministrativeId)
    {
        try {
            $data = $this->institutionService->getInstitutionAreasData($institutionId, $areaAdministrativeId);
            return $this->sendSuccessResponse("Areas Data Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Areas Data Not Found');
        }
    }



    public function getSummariesList(Request $request)
    {
        try {
            $data = $this->institutionService->getSummariesList($request);
            return $this->sendSuccessResponse("Summaries List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Summaries List Not Found');
        }
    }


    public function getInstitutionSummariesList(Request $request, int $institutionId)
    {
        try {
            $data = $this->institutionService->getInstitutionSummariesList($request, $institutionId);
            return $this->sendSuccessResponse("Summaries List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Summaries List Not Found');
        }
    }


    public function getGradeSummariesList(Request $request)
    {
        try {
            $data = $this->institutionService->getGradeSummariesList($request);
            return $this->sendSuccessResponse("Grade Summaries List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Grade Summaries List Not Found');
        }
    }


    public function getInstitutionGradeSummariesList(Request $request, int $institutionId)
    {
        try {
            $data = $this->institutionService->getInstitutionGradeSummariesList($request, $institutionId);
            return $this->sendSuccessResponse("Grade Summaries List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Grade Summaries List Not Found');
        }
    }


    public function getInstitutionGradeSummariesData(int $institutionId, int $gradeId)
    {
        try {
            $data = $this->institutionService->getInstitutionGradeSummariesData($institutionId, $gradeId);
            return $this->sendSuccessResponse("Grade Summaries Data Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Grade Summaries Data Not Found');
        }
    }


    public function getStudentNationalitySummariesList(Request $request)
    {
        try {
            $data = $this->institutionService->getStudentNationalitySummariesList($request);
            return $this->sendSuccessResponse("Student Nationality Summaries Data Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Student Nationality Summaries Data Not Found');
        }
    }


    public function getInstitutionStudentNationalitySummariesList(Request $request, $institutionId)
    {
        try {
            $data = $this->institutionService->getInstitutionStudentNationalitySummariesList($request, $institutionId);
            return $this->sendSuccessResponse("Student Nationality Summaries Data Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Student Nationality Summaries Data Not Found');
        }
    }


    public function getGradesStudentNationalitySummariesList(Request $request)
    {
        try {
            $data = $this->institutionService->getGradesStudentNationalitySummariesList($request);
            return $this->sendSuccessResponse("Student Nationality Summaries Data Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Student Nationality Summaries Data Not Found');
        }
    }


    public function getInstitutionGradeStudentNationalitySummariesList(Request $request, int $institutionId)
    {
        try {
            $data = $this->institutionService->getInstitutionGradeStudentNationalitySummariesList($request, $institutionId);
            return $this->sendSuccessResponse("Student Nationality Summaries Data Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Student Nationality Summaries Data Not Found');
        }
    }


    public function getInstitutionGradeStudentNationalitySummaries(Request $request, int $institutionId, int $gradeId)
    {
        try {
            $data = $this->institutionService->getInstitutionGradeStudentNationalitySummaries($request, $institutionId, $gradeId);
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Student Nationality Summaries Data Not Found');
        }
    }


    public function getStaffList(Request $request)
    {
        try {
            $data = $this->institutionService->getStaffList($request);

            return $this->sendSuccessResponse("Institutions Staff List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution Staff List Not Found');
        }
    }


    public function getInstitutionStaffList(Request $request, int $institutionId)
    {
        try {
            $data = $this->institutionService->getInstitutionStaffList($request, $institutionId);
            
            return $this->sendSuccessResponse("Institutions Staff List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution Staff List Not Found');
        }
    }


    public function getInstitutionStaffData(int $institutionId, int $staffId)
    {
        try {
            $data = $this->institutionService->getInstitutionStaffData($institutionId, $staffId);
            
            return $this->sendSuccessResponse("Institutions Staff Data Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution Staff Data Not Found');
        }
    }



    public function getPositionsList(Request $request)
    {
        try {
            $data = $this->institutionService->getPositionsList($request);
            
            return $this->sendSuccessResponse("Institutions Positions List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institutions Positions List Not Found');
        }
    }



    public function getInstitutionPositionsList(Request $request, int $institutionId)
    {
        try {
            $data = $this->institutionService->getInstitutionPositionsList($request, $institutionId);
            
            return $this->sendSuccessResponse("Institutions Positions List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institutions Positions List Not Found');
        }
    }


    public function getInstitutionPositionsData(int $institutionId, int $positionId)
    {
        try {
            $data = $this->institutionService->getInstitutionPositionsData($institutionId, $positionId);
            
            return $this->sendSuccessResponse("Institutions Positions Data Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institutions Positions Data Not Found');
        }
    }


    public function localeContentsList(Request $request)
    {
        try {
            $data = $this->institutionService->localeContentsList($request);
            
            return $this->sendSuccessResponse("Locale Contents List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Locale Contents List Not Found');
        }
    }


    public function localeContentsData(int $localeId)
    {
        try {
            $data = $this->institutionService->localeContentsData($localeId);
            
            return $this->sendSuccessResponse("Locale Contents Data Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Locale Contents Data Not Found');
        }
    }


    public function roomTypeSummaries(Request $request)
    {
        try {
            $data = $this->institutionService->roomTypeSummaries($request);
            
            return $this->sendSuccessResponse("Room Type Summaries List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Room Type Summaries List Not Found');
        }
    }


    public function institutionRoomTypeSummaries(Request $request, int $institutionId)
    {
        try {
            $data = $this->institutionService->institutionRoomTypeSummaries($request, $institutionId);
            
            return $this->sendSuccessResponse("Room Type Summaries List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Room Type Summaries List Not Found');
        }
    }


    public function reportCardCommentAdd(ReportCardCommentAdd $request, int $institutionId, int $classId)
    {
        try {

            //For POCOR-7772 Start
            $checkPermission = checkPermission(['Institutions', 'ReportCardComments', 'add'], ['institution_id' => $institutionId]);
            
            if(!$checkPermission){
                return $this->sendAuthorizationErrorResponse();
            }
            //For POCOR-7772 End

            $data = $this->institutionService->reportCardCommentAdd($request, $institutionId, $classId);
            
            if($data == 0){
                return $this->sendErrorResponse("Student is not enrolled in the class.");
            }elseif ($data == 1) {
                return $this->sendSuccessResponse("Report card comment added successfully.");
            } else {
                return $this->sendErrorResponse('Something went wrong.');
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to add report card comment.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to add report card comment.');
        }
    }


    public function reportCardCommentHomeroomAdd(ReportCardCommentHomeroomAdd $request, int $institutionId, int $classId)
    {
        try {

            //For POCOR-7772 Start
            $checkPermission = checkPermission(['Institutions', 'ReportCardComments', 'add'], ['institution_id' => $institutionId]);
            
            if(!$checkPermission){
                return $this->sendAuthorizationErrorResponse();
            }
            //For POCOR-7772 End

            $data = $this->institutionService->reportCardCommentHomeroomAdd($request, $institutionId, $classId);
            
            if($data == 0){
                return $this->sendErrorResponse("Student is not enrolled in the class.");
            } else {
                return $this->sendSuccessResponse("Report card comment added successfully.");
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to add report card comment.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to add report card comment.');
        }
    }



    public function reportCardCommentPrincipalAdd(ReportCardCommentHomeroomAdd $request, int $institutionId, int $classId)
    {
        try {

            //For POCOR-7772 Start
            $checkPermission = checkPermission(['Institutions', 'ReportCardComments', 'add'], ['institution_id' => $institutionId]);
            
            if(!$checkPermission){
                return $this->sendAuthorizationErrorResponse();
            }
            //For POCOR-7772 End


            $data = $this->institutionService->reportCardCommentPrincipalAdd($request, $institutionId, $classId);
            
            if($data == 0){
                return $this->sendSuccessResponse("Unsuccessful - Invalid parameters.");
            } else {
                return $this->sendSuccessResponse("Successful");
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Unsuccessful',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendServerErrorResponse('Unsuccessful');
        }
    }



    public function getInstitutionGradeStudentdata(int $institutionId, int $gradeId, int $studentId)
    {
        try {
            $data = $this->institutionService->getInstitutionGradeStudentdata($institutionId, $gradeId, $studentId);
            
            return $this->sendSuccessResponse("Student Details Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to get student data.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get student data.');
        }
    }



    public function addCompetencyResults(CompetencyResultsAddRequest $request)
    {
        try {

            //For POCOR-7772 Start
            $checkPermission = checkPermission(['Institutions', 'StudentCompetencies', 'add'], ['institution_id' => $request['institution_id']??0]);
            
            if(!$checkPermission){
                return $this->sendAuthorizationErrorResponse();
            }
            //For POCOR-7772 End


            $data = $this->institutionService->addCompetencyResults($request);
            
            if($data == 1){
                return $this->sendSuccessResponse("Competency result stored successfully.");
            } elseif($data == 0){
                return $this->sendServerErrorResponse("Invalid parameters.");
            } else {
                return $this->sendSuccessResponse("Competeny result not stored.", $data);
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to add competency result.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to add competency result.');
        }
    }


    public function addCompetencyComments(CompetencyCommentAddRequest $request)
    {
        try {

            //For POCOR-7772 Start
            $checkPermission = checkPermission(['Institutions', 'StudentCompetencyComments', 'add'], ['institution_id' => $request['institution_id']??0]);
            
            if(!$checkPermission){
                return $this->sendAuthorizationErrorResponse();
            }
            //For POCOR-7772 End

            $data = $this->institutionService->addCompetencyComments($request);
            
            if($data == 1){
                return $this->sendSuccessResponse("Competency comments stored successfully.");
            } elseif($data == 0){
                return $this->sendServerErrorResponse("Invalid parameters.");
            } else {
                return $this->sendSuccessResponse("Competeny comments not stored.", $data);
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to add competency comments.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to add competency comments.');
        }
    }



    public function addCompetencyPeriodComments(CompetencyPeriodCommentAddRequest $request)
    {
        try {

            //For POCOR-7772 Start
            $checkPermission = checkPermission(['Institutions', 'StudentCompetencyComments', 'add'], ['institution_id' => $request['institution_id']??0]);
            
            if(!$checkPermission){
                return $this->sendAuthorizationErrorResponse();
            }
            //For POCOR-7772 End
            
            $data = $this->institutionService->addCompetencyPeriodComments($request);
            
            if($data == 1){
                return $this->sendSuccessResponse("Successful");
            } elseif($data == 0){
                return $this->sendSuccessResponse("Unsuccessful - Invalid parameters.");
            } else {
                return $this->sendErrorResponse("Unsuccessful");
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Unsuccessful - Failed to add competency comments.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendServerErrorResponse('Unsuccessful');
        }
    }



    public function getStudentAssessmentItemResult(Request $request, $institutionId, $studentId)
    {
        try {
            $data = $this->institutionService->getStudentAssessmentItemResult($request, $institutionId, $studentId);
            
            return $this->sendSuccessResponse("Student Assessment Details Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to get student assessment data.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get student assessment data.');
        }
    }
    
    public function displayAddressAreaLevel(Request $request)
    {
        try {
            $data = $this->institutionService->displayAddressAreaLevel($request);
            
            return $this->sendSuccessResponse("Address area level area found.", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to get address area level area.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get address area level area.');
        }
    }


    public function displayBirthplaceAreaLevel(Request $request)
    {
        try {
            $data = $this->institutionService->displayBirthplaceAreaLevel($request);
            
            return $this->sendSuccessResponse("Birthplace area level area found.", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to get birthplace area level area.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get birthplace area level area.');
        }
    }

    
    public function getSubjectsStaffList(Request $request)
    {
        try {
            if(!isset($request['staff_id']) || !isset($request['institution_id'])){
                return $this->sendErrorResponse('Staff id and institution id is required.');
            }
            $data = $this->institutionService->getSubjectsStaffList($request);
            return $this->sendSuccessResponse("Subjects Staff List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Subjects Staff List Not Found');
        }
    }

    // POCOR-7394-S starts

    public function getAbsenceReasons(Request $request)
    {
        try {
            
            $data = $this->institutionService->getAbsenceReasons($request);
            return $this->sendSuccessResponse("Absence Reasons List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Absence Reasons List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Absence Reasons List Not Found');
        }
    }

    public function getAbsenceTypes(Request $request)
    {
        try {
            
            $data = $this->institutionService->getAbsenceTypes($request);
            return $this->sendSuccessResponse("Absence Types List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Absence Types List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Absence Types List Not Found');
        }
    }


    public function getAreaAdministratives(Request $request)
    {
        try {
            
            $data = $this->institutionService->getAreaAdministratives($request);
            return $this->sendSuccessResponse("Area Administratives List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Area Administratives List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Area Administratives List Not Found');
        }
    }


    public function getAreaAdministrativesById(int $areaAdministrativeId)
    {
        try {
            
            $data = $this->institutionService->getAreaAdministrativesById($areaAdministrativeId);

            if($data){
            return $this->sendSuccessResponse("Area Administrative Found", $data);
            }
            else {
                return $this->sendErrorResponse('Area Administrative Not Found');
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Area Administrative from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Area Administrative Not Found');
        }
    }
    
    public function getInstitutionGenders()
    {
        try {
            
            $data = $this->institutionService->getInstitutionGenders();
            return $this->sendSuccessResponse("Institution Genders List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Institution Genders List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution Genders List Not Found');
        }
    }


    public function getInstitutionsLocalitiesById(int $localityId)
    {
        try {
            
            $data = $this->institutionService->getInstitutionsLocalitiesById($localityId);

            if($data){
            return $this->sendSuccessResponse("Institution Locality Found", $data);
            }
            else {
                return $this->sendErrorResponse('Institution Locality Not Found');
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Institution Locality from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution Locality Not Found');
        }
    }

    public function getInstitutionsOwnershipsById(int $ownershipId)
    {
        try {
            
            $data = $this->institutionService->getInstitutionsOwnershipsById($ownershipId);

            if($data){
            return $this->sendSuccessResponse("Institution Ownership Found", $data);
            }
            else {
                return $this->sendErrorResponse('Institution Ownership Not Found');
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Institution Ownership from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution Ownership Not Found');
        }
    }

    public function getInstitutionSectorsById(int $sectorId)
    {
        try {
            
            $data = $this->institutionService->getInstitutionSectorsById($sectorId);

            if($data){
            return $this->sendSuccessResponse("Institution Sector Found", $data);
            }
            else {
                return $this->sendErrorResponse('Institution Sector Not Found');
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Institution Sector from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution Sector Not Found');
        }
    }

    public function getInstitutionProvidersById(int $providerId)
    {
        try {
            
            $data = $this->institutionService->getInstitutionProvidersById($providerId);

            if($data){
            return $this->sendSuccessResponse("Institution Provider Found", $data);
            }
            else {
                return $this->sendErrorResponse('Institution Provider Not Found');
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Institution Provider from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution Provider Not Found');
        }
    }

    public function getInstitutionTypesById(int $typeId)
    {
        try {
            
            $data = $this->institutionService->getInstitutionTypesById($typeId);

            if($data){
            return $this->sendSuccessResponse("Institution Type Found", $data);
            }
            else {
                return $this->sendErrorResponse('Institution Type Not Found');
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Institution Type from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution Type Not Found');
        }
    }

    public function getInstitutionProviderBySectorId(int $sectorId)
    {
        try {
            
            $data = $this->institutionService->getInstitutionProviderBySectorId($sectorId);

            if($data){
            return $this->sendSuccessResponse("Institution Provider By Sector ID Found", $data);
            }
            else {
                return $this->sendErrorResponse('Institution Provider By Sector ID Not Found');
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Institution Provider By Sector ID from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution Provider By Sector ID Not Found');
        }
    }

    public function getMealBenefits(Request $request)
    {
        try {
            
            $data = $this->institutionService->getMealBenefits($request);
            return $this->sendSuccessResponse("Meal Benefits List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Meal Benefits List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Meal Benefits List Not Found');
        }
    }

    public function getMealProgrammes(Request $request)
    {
        try {
            
            $data = $this->institutionService->getMealProgrammes($request);
            return $this->sendSuccessResponse("Meal Programmes List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Meal Programmes List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Meal Programmes List Not Found');
        }
    }

    // POCOR-7394-S ends

    public function deleteClassAttendance(DeleteClassAttendanceRequest $request)
    {
        try {

            //For POCOR-7772 Start
            $checkPermission = checkPermission(['Institutions', 'StudentAttendances', 'delete'], ['institution_id' => $request['institution_id']]);

            if(!$checkPermission){
                return $this->sendAuthorizationErrorResponse();
            }
            
            //For POCOR-7772 End

            $data = $this->institutionService->deleteClassAttendance($request);
            if($data == 1){
                return $this->sendSuccessResponse("Student attendance deleted successfully.");
            } elseif($data == 2){
                return $this->sendSuccessResponse("Record not found for selected parameters.");
            } else {
                return $this->sendErrorResponse("Student attendance not deleted.", $data);
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to delete student attendance.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to delete student attendance.');
        }
    }


    public function deleteStudentAttendance(DeleteClassAttendanceRequest $request, $studentId)
    {
        try {

            //For POCOR-7772 Start
            $checkPermission = checkPermission(['Institutions', 'StudentAttendances', 'delete'], ['institution_id' => $request['institution_id']]);

            if(!$checkPermission){
                return $this->sendAuthorizationErrorResponse();
            }
            
            //For POCOR-7772 End

            $data = $this->institutionService->deleteStudentAttendance($request, $studentId);
            if($data == 1){
                return $this->sendSuccessResponse("Student attendance deleted successfully.");
            } elseif($data == 2){
                return $this->sendSuccessResponse("Record not found for selected parameters.");
            }else {
                return $this->sendErrorResponse("Student attendance not deleted.", $data);
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to delete student attendance.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to delete student attendance.');
        }
    }

    // POCOR-7546 starts

    public function getBehaviourCategories(Request $request)
    {
        try {
            
            $data = $this->institutionService->getBehaviourCategories($request);
            return $this->sendSuccessResponse("Behaviour Categories List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Behaviour Categories List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
        
            return $this->sendErrorResponse('Behaviour Categories List Not Found');
        }
    }

    public function getInstitutionStudentBehaviour(int $institutionId, $studentId)
    {
        try {
            
            $data = $this->institutionService->getInstitutionStudentBehaviour($institutionId, $studentId);

            if($data){
            return $this->sendSuccessResponse("Institution Student Behaviour Found", $data);
            }
            
            return $this->sendErrorResponse('Institution Student Behaviour Not Found');
            
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Institution Student Behaviour from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Institution Student Behaviour Not Found');
        }
    }

    public function addStudentAssessmentItemResult(AssessmentItemResultRequest $request)
    {
        try {
            //For POCOR-7772 Start
            $checkPermission = checkPermission(['Institutions', 'Assessments', 'add'], ['institution_id' => $request['institution_id']]);

            if(!$checkPermission){
                return $this->sendAuthorizationErrorResponse();
            }
            
            //For POCOR-7772 End

            $data = $this->institutionService->addStudentAssessmentItemResult($request);
            
            if($data == 1){
                return $this->sendSuccessResponse("Successful");
            } elseif($data == 2){
                return $this->sendSuccessResponse("Successful");
            } elseif($data == 0){
                return $this->sendSuccessResponse("Unsuccessful - Invalid parameters.");
            } else {
                return $this->sendErrorResponse("Unsuccessful");
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Unsuccessful',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendServerErrorResponse('Unsuccessful');
        }
    }

    public function addStudentBehaviour(StudentBehavioursRequest $request)
    {
        try {

            //For POCOR-7772 Start
            $checkPermission = checkPermission(['Institutions', 'StudentBehaviours', 'add'], ['institution_id' => $request['institution_id']]);

            if(!$checkPermission){
                return $this->sendAuthorizationErrorResponse();
            }
            
            //For POCOR-7772 End
            
            $data = $this->institutionService->addStudentBehaviour($request);
            
            if($data == 1){
                return $this->sendErrorResponse("Student Behaviour is added/updated successfully..");
            } elseif($data == 2) {
                return $this->sendErrorResponse("Invalid academic period.");
            } elseif($data == 3) {
                return $this->sendErrorResponse("Invalid institution.");
            } elseif($data == 4) {
                return $this->sendErrorResponse("Invalid student.");
            } elseif($data == 5) {
                return $this->sendErrorResponse("Invalid student behaviour category.");
            } else {
                return $this->sendSuccessResponse("The update of student behaviour could not be completed successfully.");
            }
            
        } catch (\Exception $e) {
            Log::error(
                'The update of student behaviour could not be completed successfully.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('The update of student behaviour could not be completed successfully.');
        }
    }


    public function getInstitutionClassEducationGradeStudents(int $institutionId, int $institutionClassId, int $educationGradeId)
    {
        try {
            $data = $this->institutionService->getInstitutionClassEducationGradeStudents($institutionId, $institutionClassId, $educationGradeId);
            
            return $this->sendSuccessResponse("Students List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to get Students List.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get Students List.');
        }
    }

    public function getInstitutionEducationSubjectStudents(int $institutionId, int $educationGradeId)
    {
        try {
            $data = $this->institutionService->getInstitutionEducationSubjectStudents($institutionId, $educationGradeId);
            
            return $this->sendSuccessResponse("Students List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to get Students List.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get Students List.');
        }
    }

    public function deleteStudentBehaviour(int $institutionId, int $studentId, int $behaviourId)
    {
        try {

            //For POCOR-7772 Start
            $checkPermission = checkPermission(['Institutions', 'StudentBehaviours', 'delete'], ['institution_id' => $institutionId]);

            if(!$checkPermission){
                return $this->sendAuthorizationErrorResponse();
            }
            
            //For POCOR-7772 End

            $data = $this->institutionService->deleteStudentBehaviour($institutionId, $studentId, $behaviourId);
            if($data == 1){
                return $this->sendSuccessResponse("Student Behaviour is deleted successfully.");
            } elseif($data == 2){
                return $this->sendSuccessResponse("Record not found for selected Id(s).");
            }else {
                return $this->sendErrorResponse("The deletion of student behaviour could not be completed successfully.", $data);
            }
            
        } catch (\Exception $e) {
            Log::error(
                'The deletion of student behaviour could not be completed successfully.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('The deletion of student behaviour could not be completed successfully.');
        }
    }

    // POCOR-7546 ends


    // pocor-7545 starts

    public function getSecurityRoleFunction(Request $request)
    {
        try {
            
            $data = $this->institutionService->getSecurityRoleFunction($request);
            return $this->sendSuccessResponse("Security Role Function List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Security Role Function List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Security Role Function List Not Found');
        }
    }

    public function getSecurityGroupUsers(Request $request)
    {
        try {
            
            $data = $this->institutionService->getSecurityGroupUsers($request);
            return $this->sendSuccessResponse("Security Group Users List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Security Group Users List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Security Group Users List Not Found');
        }
    }

    public function getInstitutionStudentsMeals(Request $request)
    {
        try {
            
            $data = $this->institutionService->getInstitutionStudentsMeals($request);
            return $this->sendSuccessResponse("Institution Students Meals List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Institution Students Meals List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution Students Meals List Not Found');
        }
    }

    public function getStudentsMealsByInstitutionId(int $institutionId)
    {
        try {
            
            $data = $this->institutionService->getStudentsMealsByInstitutionId($institutionId);

            if($data){
                return $this->sendSuccessResponse("Students Meals List By Institution Id Found", $data);
            }
            else {
                return $this->sendErrorResponse('Students Meals List By Institution Id Not Found');
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Students Meals List By Institution Id from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Students Meals List By Institution Id Not Found');
        }
    }

    public function getInstitutionStudentStatusByStudentId(int $studentId)
    {
        try {
            
            $data = $this->institutionService->getInstitutionStudentStatusByStudentId($studentId);

            if($data){
            return $this->sendSuccessResponse("Institution Students Status By Student Id Found", $data);
            }
            else {
                return $this->sendErrorResponse('Institution Students Status By Student Id Not Found');
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Institution Students Status from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution Students Status By Student Id Not Found');
        }
    }

    public function addInstitutionStudent(InstitutionStudentAddRequest $request)
    {
        try {
            $data = $this->institutionService->addInstitutionStudent($request);
            
            if($data == 1){
                return $this->sendSuccessResponse("Student is created/updated successfully.");
            } else {
                return $this->sendErrorResponse("Student is not created/updated successfully.");
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Student is not created/updated successfully.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Student is not created/updated successfully.');
        }
    }

    public function addInstitutionStaffPayslip(StaffPayslipsRequest $request)
    {
        try {
            //For POCOR-7772 Start
            $checkPermission = checkPermission(['Staff', 'Payslips', 'add']);
            
            if(!$checkPermission){
                return $this->sendAuthorizationErrorResponse();
            }
            //For POCOR-7772 End


            $data = $this->institutionService->addInstitutionStaffPayslip($request);
            
            if($data == 1){
                return $this->sendSuccessResponse("Payslips is created/updated successfully.");
            } elseif($data == 2){
                return $this->sendErrorResponse("Invalid staff id.");
            } else {
                return $this->sendErrorResponse("Payslips is not created/updated successfully.");
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Payslips is not created/updated successfully.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Payslips is not created/updated successfully.');
        }
    }

    public function addInstitutionStudentMealBenefits(InstitutionMealStudentsRequest $request)
    {
        try {
            //For POCOR-7772 Start
            $checkPermission = checkPermission(['Institutions', 'StudentMeals', 'edit'], ['institution_id' => $request['institution_id']??0]);
            
            if(!$checkPermission){
                return $this->sendAuthorizationErrorResponse();
            }
            //For POCOR-7772 End

            $data = $this->institutionService->addInstitutionStudentMealBenefits($request);
            
            if($data == 1){
                return $this->sendSuccessResponse("Meal Benefit is created/updated successfully.");
            } elseif ($data == 2) {
                return $this->sendErrorResponse("Invalid meal distribution id.");
            } else {
                return $this->sendErrorResponse("Meal Benefit is not created/updated successfully.");
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Meal Benefit is not created/updated successfully.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Meal Benefit is not created/updated successfully.');
        }
    }

    public function addInstitutionMealDistributions(InstitutionMealDistributionRequest $request)
    {
        try {
            $data = $this->institutionService->addInstitutionMealDistributions($request);
            
            if($data == 1){
                return $this->sendSuccessResponse("Meal Distribution is created/updated successfully.");
            } elseif ($data == 2) {
                return $this->sendErrorResponse("Invalid meal distribution id.");
            } else {
                return $this->sendErrorResponse("Meal Distribution is not created/updated successfully.");
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Meal Distribution is not created/updated successfully.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Meal Distribution is not created/updated successfully.');
        }
    }

    public function addInstitution(InstitutionsAddRequest $request)
    {
        try {

            //For POCOR-7772 Start
            
            $paramArray = [];
            if(isset($request['id']) && $request['id'] > 0){
                $paramArray['institution_id'] = $request['id'];  
            }
            
            $checkPermission = checkPermission(['Institutions', 'Institutions', 'edit'], $paramArray);
            
            if(!$checkPermission){
                return $this->sendAuthorizationErrorResponse();
            }
            //For POCOR-7772 End


            $data = $this->institutionService->addInstitution($request);
            
            if($data == 1){
                return $this->sendSuccessResponse("Institution is created/updated successfully.");
            } elseif ($data == 2) {
                return $this->sendErrorResponse("Invalid institution id.");
            } else {
                return $this->sendErrorResponse("Institution is not created/updated successfully.");
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Institution is not created/updated successfully.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution is not created/updated successfully.');
        }
    }

    //pocor-7545 ends

    public function updateInstitutionClass($institutionId, $classId, Request $request)
    {
        try {

            $checkPermission = checkPermission(['Institutions', 'AllClasses', 'edit'], ['institution_id' => $institutionId]);

            if(!$checkPermission) {
                return $this->sendAuthorizationErrorResponse();
            }
            $data = $request->all();

            $validate = $this->institutionService->validateInstitutionClassData($institutionId, $classId, $data);
            if ($validate) {
                return $this->sendErrorResponse('Class not updated.', $validate);
            }

            $this->institutionService->updateInstitutionClass($institutionId, $classId, $data);
            return $this->sendSuccessResponse('Class updated successfully.',[]);
        } catch (Exception $e) {
            return $this->sendErrorResponse('Class not updated.');
        }
    }

    public function updateInstitutionSubject($institutionId, $subjectId, Request $request)
    {
        try {

            $checkPermission = checkPermission(['Institutions', 'AllSubjects', 'edit'], ['institution_id' => $institutionId]);

            if(!$checkPermission) {
                return $this->sendAuthorizationErrorResponse();
            }

            $data = $request->all();

            $validate = $this->institutionService->validateInstitutionSubjectData($institutionId, $subjectId, $data);
            if ($validate) {
                return $this->sendErrorResponse('Subject not updated.', $validate);
            }

            $this->institutionService->updateInstitutionSubject($institutionId, $subjectId, $data);
            return  $this->sendSuccessResponse('Subject updated successfully.',[]);
        } catch (Exception $e) {
            return $this->sendErrorResponse('Subject not updated.');
        }
    }

    public function institutionClassGrade($id)
    {
        //For POCOR-7854 Starts...
        $instituionClassGrades = InstitutionClassGrades::select('institution_class_grades.*')
            ->join('education_grades', 'education_grades.id', '=', 'institution_class_grades.education_grade_id')
            ->with('educationGrades')
            ->orderBy('education_grades.name', 'ASC')
            ->where('institution_class_id', $id)
            ->get();
        //For POCOR-7854 Ends...

        //$instituionClassGrades = InstitutionClassGrades::with('educationGrades')->where('institution_class_id', $id)->get();

        return $this->sendSuccessResponse("Institution Class grades", $instituionClassGrades);
    }


    public function institutionRooms($institutionId, $academicYearId)
    {
        $rooms = InstitutionRooms::where('institution_id', $institutionId)->where('academic_period_id', $academicYearId)->get();

        return $this->sendSuccessResponse('Institution rooms.', $rooms);
    }

    public function institutionClassSubjects($institutionClassId)
    {
        $subjects = InstitutionClassSubjects::with('institutionSubject')->where('institution_class_id', $institutionClassId)->get();

        return $this->sendSuccessResponse('Institution Subjects.', $subjects);
    }


    //For POCOR-8197 Starts...
    public function getGradesViaInstitutionId(Request $request, $institutionId)
    {
        try {
            $params = $request->all();
            $list = $this->institutionService->getGradesViaInstitutionId($params, $institutionId);
            
            return  $this->sendSuccessResponse('Successful.',$list);
            
        } catch (Exception $e) {
           return $this->sendErrorResponse($e->getMessage());
        }
    }
    //For POCOR-8197 End...
}