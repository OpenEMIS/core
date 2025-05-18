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
     *         name="institutionId",
     *         in="query",
     *         required=false,
     *         description="Institution Id.",
     *         @OA\Schema(type="integer", example=6)
     *     ),
     *     @OA\Parameter(
     *         name="typeId",
     *         in="query",
     *         required=false,
     *         description="Institution Type Id.",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="classificationId",
     *         in="query",
     *         required=false,
     *         description="Classification Id.",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="institutionCode",
     *         in="query",
     *         required=false,
     *         description="Institution Code.",
     *         @OA\Schema(type="integer", example="K0001")
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
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
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
     *         name="typeId",
     *         in="query",
     *         required=false,
     *         description="Institution Type Id.",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="classificationId",
     *         in="query",
     *         required=false,
     *         description="Classification Id.",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="institutionCode",
     *         in="query",
     *         required=false,
     *         description="Institution Code.",
     *         @OA\Schema(type="integer", example="K0001")
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
    public function getInstitutionData(Request $request, int $id)
    {
        try {
            $params = $request->all();
            $data = $this->institutionService->getInstitutionData($params, $id);
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
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
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
     *         required=false,
     *         description="ID of the academic period",
     *         @OA\Schema(type="integer", example=10)
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

    /**
     * @OA\Get(
     *     path="/api/v4/institutions/{institutionId}/classes",
     *     summary="Get classes of an institution",
     *     description="Returns a list of classes of institution based on the institution id",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="institutionId",
     *         in="path",
     *         required=true,
     *         description="ID of the Institution",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Parameter(
     *         name="academic_period_id",
     *         in="query",
     *         required=false,
     *         description="ID of the academic period",
     *         @OA\Schema(type="integer", example=10)
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
     *     path="/api/v4/institutions/{institutionId}/classes/{classId}",
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

            $validateInstitution = $this->institutionService->validateInstitution($institutionId);

            $validateClass = $this->institutionService->validateClass($classId);

            if (!$validateInstitution || !$validateClass) {
                return $this->sendErrorResponse('Unsuccessful-Invalid Parameters');
            }

            $data = $this->institutionService->getInstitutionClassData($institutionId, $classId);
            return $this->sendSuccessResponse("Successful", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Unsuccessful', $e->getMessage(), "", 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v4/institutions/subjects",
     *     summary="Get list of institution subjects",
     *     description="Returns list of institution subjects",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
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
     *                  @OA\Property(property="data", type="array",
     *                      @OA\Items(
     *                          type="object",
     *                          @OA\Property(property="id", type="integer", example=33),
     *                      @OA\Property(property="name", type="string", example="Social Studies"),
     *                      @OA\Property(property="no_of_seats", type="integer", nullable=true),
     *                      @OA\Property(property="total_male_students", type="integer", example=31),
     *                      @OA\Property(property="total_female_students", type="integer", example=19),
     *                      @OA\Property(property="institution_id", type="integer", example=6),
     *                      @OA\Property(property="education_grade_id", type="integer", example=59),
     *                      @OA\Property(property="education_subject_id", type="integer", example=60),
     *                      @OA\Property(property="academic_period_id", type="integer", example=10),
     *                      @OA\Property(property="modified_user_id", type="integer", nullable=true),
     *                      @OA\Property(property="modified", type="string", nullable=true),
     *                      @OA\Property(property="created_user_id", type="integer", example=2),
     *                      @OA\Property(property="created", type="string", example="2018-03-28 16:45:11"),
     *                      @OA\Property(property="education_grades", type="object",
     *                          @OA\Property(property="id", type="integer", example=59),
     *                          @OA\Property(property="name", type="string", example="Primary 1")
     *                      ),
     *                      @OA\Property(property="education_subjects", type="object",
     *                          @OA\Property(property="id", type="integer", example=60),
     *                          @OA\Property(property="name", type="string", example="Social Studies")
     *                      ),
     *                      @OA\Property(property="classes", type="array",
     *                          @OA\Items(
     *                              type="object",
     *                              @OA\Property(property="institution_subject_id", type="integer", example=33),
     *                              @OA\Property(property="class_id", type="integer", example=9)
     *                          )
     *                      ),
     *                      @OA\Property(property="rooms", type="array", @OA\Items()),
     *                      @OA\Property(property="staff", type="array", @OA\Items()),
     *                      @OA\Property(property="students", type="array",
     *                          @OA\Items(
     *                              type="object",
     *                              @OA\Property(property="institution_subject_id", type="integer", example=33),
     *                              @OA\Property(property="user_id", type="integer", example=805)
     *                          )
     *                      )
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

    /**
     * @OA\Get(
     *     path="/api/v4/institutions/{insitutionId}/subjects",
     *     summary="Get list of institution subjects by institution id",
     *     description="Returns list of institution subjects by institution id",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="insitutionId",
     *         in="path",
     *         required=true,
     *         description="Insitution Id",
     *         @OA\Schema(type="integer", example=6)
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
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
     *                  @OA\Property(property="data", type="array",
     *                      @OA\Items(
     *                          type="object",
     *                          @OA\Property(property="id", type="integer", example=33),
     *                      @OA\Property(property="name", type="string", example="Social Studies"),
     *                      @OA\Property(property="no_of_seats", type="integer", nullable=true),
     *                      @OA\Property(property="total_male_students", type="integer", example=31),
     *                      @OA\Property(property="total_female_students", type="integer", example=19),
     *                      @OA\Property(property="institution_id", type="integer", example=6),
     *                      @OA\Property(property="education_grade_id", type="integer", example=59),
     *                      @OA\Property(property="education_subject_id", type="integer", example=60),
     *                      @OA\Property(property="academic_period_id", type="integer", example=10),
     *                      @OA\Property(property="modified_user_id", type="integer", nullable=true),
     *                      @OA\Property(property="modified", type="string", nullable=true),
     *                      @OA\Property(property="created_user_id", type="integer", example=2),
     *                      @OA\Property(property="created", type="string", example="2018-03-28 16:45:11"),
     *                      @OA\Property(property="education_grades", type="object",
     *                          @OA\Property(property="id", type="integer", example=59),
     *                          @OA\Property(property="name", type="string", example="Primary 1")
     *                      ),
     *                      @OA\Property(property="education_subjects", type="object",
     *                          @OA\Property(property="id", type="integer", example=60),
     *                          @OA\Property(property="name", type="string", example="Social Studies")
     *                      ),
     *                      @OA\Property(property="classes", type="array",
     *                          @OA\Items(
     *                              type="object",
     *                              @OA\Property(property="institution_subject_id", type="integer", example=33),
     *                              @OA\Property(property="class_id", type="integer", example=9)
     *                          )
     *                      ),
     *                      @OA\Property(property="rooms", type="array", @OA\Items()),
     *                      @OA\Property(property="staff", type="array", @OA\Items()),
     *                      @OA\Property(property="students", type="array",
     *                          @OA\Items(
     *                              type="object",
     *                              @OA\Property(property="institution_subject_id", type="integer", example=33),
     *                              @OA\Property(property="user_id", type="integer", example=805)
     *                          )
     *                      )
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

    /**
     * @OA\Get(
     *     path="/api/v4/institutions/{insitutionId}/subjects/{subjectId}",
     *     summary="Get detail of institution subject by institution id and subject id",
     *     description="Returns detail of institution subject by institution id and subject id",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="insitutionId",
     *         in="path",
     *         required=true,
     *         description="Insitution Id",
     *         @OA\Schema(type="integer", example=6)
     *     ),
     *    @OA\Parameter(
     *         name="subjectId",
     *         in="path",
     *         required=true,
     *         description="Subject Id",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *                  @OA\Property(property="data", type="array",
     *                      @OA\Items(
     *                          type="object",
     *                          @OA\Property(property="id", type="integer", example=1),
     *                          @OA\Property(property="name", type="string", example="Language Arts Content Standards and Learning Outcomes"),
     *                          @OA\Property(property="no_of_seats", type="integer", nullable=true),
     *                          @OA\Property(property="total_male_students", type="integer", example=10),
     *                          @OA\Property(property="total_female_students", type="integer", example=16),
     *                          @OA\Property(property="institution_id", type="integer", example=1),
     *                          @OA\Property(property="education_grade_id", type="integer", example=76),
     *                          @OA\Property(property="education_subject_id", type="integer", example=6),
     *                          @OA\Property(property="academic_period_id", type="integer", example=10),
     *                          @OA\Property(property="modified_user_id", type="integer", nullable=true),
     *                          @OA\Property(property="modified", type="string", nullable=true),
     *                          @OA\Property(property="created_user_id", type="integer", example=2),
     *                          @OA\Property(property="created", type="string", example="2018-03-28 16:35:19"),
     *                          @OA\Property(property="education_grades", type="object",
     *                          @OA\Property(property="id", type="integer", example=76),
     *                          @OA\Property(property="name", type="string", example="Kindergarten 1")
     *                      ),
     *                      @OA\Property(property="education_subjects", type="object",
     *                      @OA\Property(property="id", type="integer", example=6),
     *                      @OA\Property(property="name", type="string", example="Language Arts Content Standards and   Learning Outcomes")
     *                      ),
     *                      @OA\Property(property="classes", type="array",
     *                          @OA\Items(
     *                              type="object",
     *                              @OA\Property(property="institution_subject_id", type="integer", example=1),
     *                              @OA\Property(property="class_id", type="integer", example=1)
     *                          )
     *                      ),
     *                      @OA\Property(property="rooms", type="array", @OA\Items()),
     *                      @OA\Property(property="staff", type="array", @OA\Items()),
     *                      @OA\Property(property="students", type="array",
     *                          @OA\Items(
     *                              type="object",
     *                              @OA\Property(property="institution_subject_id", type="integer", example=1),
     *                              @OA\Property(property="user_id", type="integer", example=3)
     *                          ),
     *                          @OA\Items(
     *                              type="object",
     *                              @OA\Property(property="institution_subject_id", type="integer", example=1),
     *                              @OA\Property(property="user_id", type="integer", example=4)
     *                          ),

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
    public function getInstitutionSubjectsData(int $institutionId, int $subjectId)
    {
        try {
            $validateInstitution = $this->institutionService->validateInstitution($institutionId);

            $validateClass = $this->institutionService->validateSubject($subjectId);

            if (!$validateInstitution || !$validateClass) {
                return $this->sendErrorResponse('Unsuccessful-Invalid Parameters');
            }
            $data = $this->institutionService->getInstitutionSubjectsData($institutionId, $subjectId);
            return $this->sendSuccessResponse("Successful", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Unsuccessful', $e->getMessage(), "", 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v4/institutions/shifts",
     *     summary="Get list of institution shifts",
     *     description="Returns list of institution shifts",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
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
     *                  @OA\Property(property="data", type="array",
     *                      @OA\Items(
     *                          type="object",
     *                          @OA\Property(property="id", type="integer", example=6),
     *                          @OA\Property(property="start_time", type="string", example="2018-03-30"),
     *                          @OA\Property(property="end_time", type="integer", example="2018-03-30"),
     *                          @OA\Property(property="academic_period_id", type="integer", example="1"),
     *                          @OA\Property(property="institution_id", type="integer", example=6),
     *                          @OA\Property(property="location_institution_id", type="integer", example=6),
     *                          @OA\Property(property="shift_option_id", type="integer", example=5),
     *                          @OA\Property(property="previous_shift_id", type="integer", example=4),
     *                          @OA\Property(property="modified_user_id", type="integer", example=null),
     *                          @OA\Property(property="modified", type="string", example=null),
     *                          @OA\Property(property="created_user_id", type="integer", example=2),
     *                          @OA\Property(property="created", type="string", example="2018-03-30 22:17:09"),
     *                          @OA\Property(property="shift_option", type="object",
     *                              @OA\Property(property="id", type="integer", example=11),
     *                              @OA\Property(property="name", type="string", example="District 8"),
     *                          )
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

    /**
     * @OA\Get(
     *     path="/api/v4/institutions/{institutionId}/shifts",
     *     summary="Get list of institution shifts by institution id",
     *     description="Returns list of institution shifts by institution id",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="institutionId",
     *         in="path",
     *         required=true,
     *         description="Institution Id",
     *         @OA\Schema(type="integer", example=6)
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
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
     *                  @OA\Property(property="data", type="array",
     *                      @OA\Items(
     *                          type="object",
     *                          @OA\Property(property="id", type="integer", example=6),
     *                          @OA\Property(property="start_time", type="string", example="2018-03-30"),
     *                          @OA\Property(property="end_time", type="integer", example="2018-03-30"),
     *                          @OA\Property(property="academic_period_id", type="integer", example="1"),
     *                          @OA\Property(property="institution_id", type="integer", example=6),
     *                          @OA\Property(property="location_institution_id", type="integer", example=6),
     *                          @OA\Property(property="shift_option_id", type="integer", example=5),
     *                          @OA\Property(property="previous_shift_id", type="integer", example=4),
     *                          @OA\Property(property="modified_user_id", type="integer", example=null),
     *                          @OA\Property(property="modified", type="string", example=null),
     *                          @OA\Property(property="created_user_id", type="integer", example=2),
     *                          @OA\Property(property="created", type="string", example="2018-03-30 22:17:09"),
     *                          @OA\Property(property="shift_option", type="object",
     *                              @OA\Property(property="id", type="integer", example=11),
     *                              @OA\Property(property="name", type="string", example="District 8"),
     *                          )
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

    /**
     * @OA\Get(
     *     path="/api/v4/institutions/{institutionId}/shifts/{shiftId}",
     *     summary="Get list of institution shift detail by shift id",
     *     description="Returns list of institution shift detail by shift id",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="institutionId",
     *         in="path",
     *         required=true,
     *         description="Institution Id",
     *         @OA\Schema(type="integer", example=6)
     *     ),
     *     @OA\Parameter(
     *         name="shiftId",
     *         in="path",
     *         required=true,
     *         description="shift Id",
     *         @OA\Schema(type="integer", example=61)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=6),
     *                 @OA\Property(property="start_time", type="string", example="2018-03-30"),
     *                 @OA\Property(property="end_time", type="integer", example="2018-03-30"),
     *                 @OA\Property(property="academic_period_id", type="integer", example="1"),
     *                 @OA\Property(property="institution_id", type="integer", example=6),
     *                 @OA\Property(property="location_institution_id", type="integer", example=6),
     *                 @OA\Property(property="shift_option_id", type="integer", example=5),
     *                 @OA\Property(property="previous_shift_id", type="integer", example=4),
     *                 @OA\Property(property="modified_user_id", type="integer", example=null),
     *                 @OA\Property(property="modified", type="string", example=null),
     *                 @OA\Property(property="created_user_id", type="integer", example=2),
     *                 @OA\Property(property="created", type="string", example="2018-03-30 22:17:09"),
     *                 @OA\Property(property="shift_option", type="object",
     *                     @OA\Property(property="id", type="integer", example=11),
     *                     @OA\Property(property="name", type="string", example="District 8"),
     *                 )
     *              )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/v4/institutions/areas",
     *     summary="Get list of all institution area",
     *     description="Returns list of all institution area",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
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
     *                  @OA\Property(property="data", type="array",
     *                      @OA\Items(
     *                          type="object",
     *                          @OA\Property(property="id", type="integer", example=6),
     *                          @OA\Property(property="area_administrative_id", type="integer", example=23),
     *                          @OA\Property(property="area_id", type="integer", example=11),
     *                          @OA\Property(property="code", type="string", example="P1002"),
     *                              @OA\Property(property="name", type="string", example="Avory Primary School"),
     *                              @OA\Property(property="code_name", type="string", example="P1002 - Avory Primary School"),
     *                          @OA\Property(property="area_administratives", type="object",
     *                              @OA\Property(property="id", type="integer", example=23),
     *                              @OA\Property(property="code", type="string", example="END002003"),
     *                              @OA\Property(property="name", type="string", example="District 8"),
     *                              @OA\Property(property="parent_id", type="integer", example=14),
     *                              @OA\Property(property="area_administratives_child", type="array", @OA\Items())
     *                          ),
     *                          @OA\Property(property="area_education", type="object",
     *                              @OA\Property(property="id", type="integer", example=11),
     *                              @OA\Property(property="code", type="string", example="END002003"),
     *                              @OA\Property(property="name", type="string", example="District 8"),
     *                              @OA\Property(property="parent_id", type="integer", example=3),
     *                              @OA\Property(property="area_education_child", type="array", @OA\Items())
     *                          )
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

    /**
     * @OA\Get(
     *     path="/api/v4/institutions/{institutionId}/areas",
     *     summary="Get list of institution area by institution Id",
     *     description="Returns list of institution area by institution Id",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="institutionId",
     *         in="path",
     *         required=true,
     *         description="Institution Id",
     *         @OA\Schema(type="integer", example=6)
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
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
     *                  @OA\Property(property="data", type="array",
     *                      @OA\Items(
     *                          type="object",
     *                          @OA\Property(property="id", type="integer", example=6),
     *                          @OA\Property(property="area_administrative_id", type="integer", example=23),
     *                          @OA\Property(property="area_id", type="integer", example=11),
     *                          @OA\Property(property="code", type="string", example="P1002"),
     *                              @OA\Property(property="name", type="string", example="Avory Primary School"),
     *                              @OA\Property(property="code_name", type="string", example="P1002 - Avory Primary School"),
     *                          @OA\Property(property="area_administratives", type="object",
     *                              @OA\Property(property="id", type="integer", example=23),
     *                              @OA\Property(property="code", type="string", example="END002003"),
     *                              @OA\Property(property="name", type="string", example="District 8"),
     *                              @OA\Property(property="parent_id", type="integer", example=14),
     *                              @OA\Property(property="area_administratives_child", type="array", @OA\Items())
     *                          ),
     *                          @OA\Property(property="area_education", type="object",
     *                              @OA\Property(property="id", type="integer", example=11),
     *                              @OA\Property(property="code", type="string", example="END002003"),
     *                              @OA\Property(property="name", type="string", example="District 8"),
     *                              @OA\Property(property="parent_id", type="integer", example=3),
     *                              @OA\Property(property="area_education_child", type="array", @OA\Items())
     *                          )
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

    /**
     * @OA\Get(
     *     path="/api/v4/institutions/{institutionId}/areas/{areaAdministrativeId}",
     *     summary="Get list of institution area by institution Id and area administrative Id",
     *     description="Returns list of institution area by institution Id and area administrative Id",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="institutionId",
     *         in="path",
     *         required=true,
     *         description="Institution Id",
     *         @OA\Schema(type="integer", example=6)
     *     ),
     *     @OA\Parameter(
     *         name="areaAdministrativeId",
     *         in="path",
     *         required=true,
     *         description="Area Administrative Id",
     *         @OA\Schema(type="integer", example=23)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="object",
     *                          @OA\Property(property="id", type="integer", example=6),
     *                          @OA\Property(property="area_administrative_id", type="integer", example=23),
     *                          @OA\Property(property="area_id", type="integer", example=11),
     *                          @OA\Property(property="code", type="string", example="P1002"),
     *                              @OA\Property(property="name", type="string", example="Avory Primary School"),
     *                              @OA\Property(property="code_name", type="string", example="P1002 - Avory Primary School"),
     *                          @OA\Property(property="area_administratives", type="object",
     *                              @OA\Property(property="id", type="integer", example=23),
     *                              @OA\Property(property="code", type="string", example="END002003"),
     *                              @OA\Property(property="name", type="string", example="District 8"),
     *                              @OA\Property(property="parent_id", type="integer", example=14),
     *                              @OA\Property(property="area_administratives_child", type="array", @OA\Items())
     *                          ),
     *                          @OA\Property(property="area_education", type="object",
     *                              @OA\Property(property="id", type="integer", example=11),
     *                              @OA\Property(property="code", type="string", example="END002003"),
     *                              @OA\Property(property="name", type="string", example="District 8"),
     *                              @OA\Property(property="parent_id", type="integer", example=3),
     *                              @OA\Property(property="area_education_child", type="array", @OA\Items())
     *                          )
     *              )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
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


    /**
     * @OA\Get(
     *     path="/api/v4/institutions/summaries",
     *     summary="Get list of all institution summary",
     *     description="Returns list of all institution summary",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="academicPeriodId",
     *         in="query",
     *         required=false,
     *         description="Academic period id",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Parameter(
     *         name="institutionId",
     *         in="query",
     *         required=false,
     *         description="Institution Id",
     *         @OA\Schema(type="integer", example=6)
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="institution_code")
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
     *                  @OA\Property(property="data", type="array",
     *                      @OA\Items(
     *                          type="object",
     *                          @OA\Property(property="academic_period_id", type="integer", example=32),
     *                          @OA\Property(property="academic_period_name", type="string", example="2022"),
     *                          @OA\Property(property="institution_id", type="integer", example=6),
     *                          @OA\Property(property="institution_code", type="string", example="P1002"),
     *                          @OA\Property(property="total_grades", type="integer", example=6),
     *                          @OA\Property(property="total_classes", type="integer", example=6),
     *                          @OA\Property(property="total_lands", type="integer", example=12),
     *                          @OA\Property(property="total_land_size", type="integer", example=2108),
     *                          @OA\Property(property="total_buildings", type="integer", example=1),
     *                          @OA\Property(property="total_building_sizes", type="integer", example=1185),
     *                          @OA\Property(property="total_floors", type="integer", example=8),
     *                          @OA\Property(property="total_floor_sizes", type="integer", example=3080),
     *                          @OA\Property(property="total_rooms", type="integer", example=13),
     *                          @OA\Property(property="total_room_sizes", type="integer", example=2),
     *                          @OA\Property(property="total_room_classrooms", type="integer", example=5),
     *                          @OA\Property(property="total_room_classroom_sizes", type="integer", example=0),
     *                          @OA\Property(property="total_students", type="integer", example=211),
     *                          @OA\Property(property="total_students_female", type="integer", example=150),
     *                          @OA\Property(property="total_students_male", type="integer", example=61),
     *                          @OA\Property(property="total_staff_teaching", type="integer", example=21),
     *                          @OA\Property(property="total_staff_teaching_female", type="integer", example=18),
     *                          @OA\Property(property="total_staff_teaching_male", type="integer", example=3),
     *                          @OA\Property(property="total_staff_non_teaching", type="integer", example=3),
     *                          @OA\Property(property="total_staff_non_teaching_female", type="integer", example=2),
     *                          @OA\Property(property="total_staff_non_teaching_male", type="integer", example=1),
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

    /**
     * @OA\Get(
     *     path="/api/v4/institutions/{institutionId}/summaries",
     *     summary="Get list of institution summary by institution id",
     *     description="Returns list of institution summary by institution id",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="institutionId",
     *         in="path",
     *         required=true,
     *         description="Institution Id",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Parameter(
     *         name="academicPeriodId",
     *         in="query",
     *         required=false,
     *         description="Academic period id",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="institution_code")
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
     *                  @OA\Property(property="data", type="array",
     *                      @OA\Items(
     *                          type="object",
     *                          @OA\Property(property="academic_period_id", type="integer", example=32),
     *                          @OA\Property(property="academic_period_name", type="string", example="2022"),
     *                          @OA\Property(property="institution_id", type="integer", example=6),
     *                          @OA\Property(property="institution_code", type="string", example="P1002"),
     *                          @OA\Property(property="total_grades", type="integer", example=6),
     *                          @OA\Property(property="total_classes", type="integer", example=6),
     *                          @OA\Property(property="total_lands", type="integer", example=12),
     *                          @OA\Property(property="total_land_size", type="integer", example=2108),
     *                          @OA\Property(property="total_buildings", type="integer", example=1),
     *                          @OA\Property(property="total_building_sizes", type="integer", example=1185),
     *                          @OA\Property(property="total_floors", type="integer", example=8),
     *                          @OA\Property(property="total_floor_sizes", type="integer", example=3080),
     *                          @OA\Property(property="total_rooms", type="integer", example=13),
     *                          @OA\Property(property="total_room_sizes", type="integer", example=2),
     *                          @OA\Property(property="total_room_classrooms", type="integer", example=5),
     *                          @OA\Property(property="total_room_classroom_sizes", type="integer", example=0),
     *                          @OA\Property(property="total_students", type="integer", example=211),
     *                          @OA\Property(property="total_students_female", type="integer", example=150),
     *                          @OA\Property(property="total_students_male", type="integer", example=61),
     *                          @OA\Property(property="total_staff_teaching", type="integer", example=21),
     *                          @OA\Property(property="total_staff_teaching_female", type="integer", example=18),
     *                          @OA\Property(property="total_staff_teaching_male", type="integer", example=3),
     *                          @OA\Property(property="total_staff_non_teaching", type="integer", example=3),
     *                          @OA\Property(property="total_staff_non_teaching_female", type="integer", example=2),
     *                          @OA\Property(property="total_staff_non_teaching_male", type="integer", example=1),
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

    /**
     * @OA\Get(
     *     path="/api/v4/institutions/grades/summaries",
     *     summary="Get list of all institution grade summary",
     *     description="Returns list of all institution grade summary",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="academic_period_id",
     *         in="query",
     *         required=false,
     *         description="Academic period id",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="institution_code")
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
     *                  @OA\Property(property="data", type="array",
     *                      @OA\Items(
     *                          type="object",
     *                          @OA\Property(property="academic_period_id", type="integer", example=32),
     *                          @OA\Property(property="academic_period_name", type="string", example="2022"),
     *                          @OA\Property(property="institution_id", type="integer", example=6),
     *                          @OA\Property(property="institution_code", type="string", example="P1002"),
     *                          @OA\Property(property="grade_id", type="integer", example=32),
     *                          @OA\Property(property="grade_name", type="string", example=null),
     *                          @OA\Property(property="total_classes", type="integer", example=34),
     *                          @OA\Property(property="total_classes_female", type="integer", example=12),
     *                          @OA\Property(property="total_classes_male", type="integer", example=21),
     *                          @OA\Property(property="total_classes_mixed", type="integer", example=1),
     *                          @OA\Property(property="total_students", type="integer", example=11),
     *                          @OA\Property(property="total_students_female", type="integer", example=8),
     *                          @OA\Property(property="total_students_male", type="integer", example=3),
     *                          @OA\Property(property="total_home_room_teachers", type="integer", example=3),
     *                          @OA\Property(property="total_secondary_teachers", type="integer", example=2),
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

    /**
     * @OA\Get(
     *     path="/api/v4/institutions/{institutionId}/grades/summaries",
     *     summary="Get list of institution grade summary",
     *     description="Returns list of institution grade summary",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="institutionId",
     *         in="path",
     *         required=true,
     *         description="Institution Id",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Parameter(
     *         name="academic_period_id",
     *         in="query",
     *         required=false,
     *         description="Academic period id",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="institution_code")
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
     *                  @OA\Property(property="data", type="array",
     *                      @OA\Items(
     *                          type="object",
     *                          @OA\Property(property="academic_period_id", type="integer", example=32),
     *                          @OA\Property(property="academic_period_name", type="string", example="2022"),
     *                          @OA\Property(property="institution_id", type="integer", example=6),
     *                          @OA\Property(property="institution_code", type="string", example="P1002"),
     *                          @OA\Property(property="grade_id", type="integer", example=32),
     *                          @OA\Property(property="grade_name", type="string", example=null),
     *                          @OA\Property(property="total_classes", type="integer", example=34),
     *                          @OA\Property(property="total_classes_female", type="integer", example=12),
     *                          @OA\Property(property="total_classes_male", type="integer", example=21),
     *                          @OA\Property(property="total_classes_mixed", type="integer", example=1),
     *                          @OA\Property(property="total_students", type="integer", example=11),
     *                          @OA\Property(property="total_students_female", type="integer", example=8),
     *                          @OA\Property(property="total_students_male", type="integer", example=3),
     *                          @OA\Property(property="total_home_room_teachers", type="integer", example=3),
     *                          @OA\Property(property="total_secondary_teachers", type="integer", example=2),
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

    /**
     * @OA\Get(
     *     path="/api/v4/institutions/{institutionId}/grade/{gradeId}/summaries",
     *     summary="Get list of institution student summary by institution id and grade id",
     *     description="Returns list of institution student summary by institution id and grade id",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="institutionId",
     *         in="path",
     *         required=true,
     *         description="Institution Id",
     *         @OA\Schema(type="integer", example=6)
     *     ),
     *     @OA\Parameter(
     *         name="gradeId",
     *         in="path",
     *         required=true,
     *         description="Grade Id",
     *         @OA\Schema(type="integer", example=61)
     *     ),
     *     @OA\Parameter(
     *         name="academic_period_id",
     *         in="query",
     *         required=false,
     *         description="Academic period id",
     *         @OA\Schema(type="integer", example=25)
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="institution_code")
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
     *             @OA\Property(property="data", type="array",
     *                  @OA\Items(
     *                 type="object",
     *                      @OA\Property(property="academic_period_id", type="integer", example=32),
     *                      @OA\Property(property="academic_period_name", type="string", example="2022"),
     *                      @OA\Property(property="institution_id", type="integer", example=6),
     *                      @OA\Property(property="institution_code", type="string", example="P1002"),
     *                      @OA\Property(property="grade_id", type="integer", example=32),
     *                      @OA\Property(property="grade_name", type="string", example=null),
     *                      @OA\Property(property="total_classes", type="integer", example=34),
     *                      @OA\Property(property="total_classes_female", type="integer", example=12),
     *                      @OA\Property(property="total_classes_male", type="integer", example=21),
     *                      @OA\Property(property="total_classes_mixed", type="integer", example=1),
     *                      @OA\Property(property="total_students", type="integer", example=11),
     *                      @OA\Property(property="total_students_female", type="integer", example=8),
     *                      @OA\Property(property="total_students_male", type="integer", example=3),
     *                      @OA\Property(property="total_home_room_teachers", type="integer", example=3),
     *                      @OA\Property(property="total_secondary_teachers", type="integer", example=2),
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
    public function getInstitutionGradeSummariesData(Request $request, int $institutionId, int $gradeId)
    {
        try {
            $params = $request->all();
            $data = $this->institutionService->getInstitutionGradeSummariesData($params, $institutionId, $gradeId);
            return $this->sendSuccessResponse("Grade Summaries Data Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Grade Summaries Data Not Found');
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v4/institutions/student-nationality-summaries",
     *     summary="Get list of all institution student nationality summary",
     *     description="Returns list of all institution student nationality summary",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="academic_period_id",
     *         in="query",
     *         required=false,
     *         description="Academic period id",
     *         @OA\Schema(type="integer", example=25)
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="total_students")
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
     *                  @OA\Property(property="data", type="array",
     *                      @OA\Items(
     *                          type="object",
     *                          @OA\Property(property="academic_period_id", type="integer", example=32),
     *                          @OA\Property(property="academic_period_name", type="string", example="2022"),
     *                          @OA\Property(property="institution_id", type="integer", example=6),
     *                          @OA\Property(property="institution_code", type="string", example="P1002"),
     *                          @OA\Property(property="nationality_id", type="integer", example=3),
     *                          @OA\Property(property="nationality_name", type="string", example=null),
     *                          @OA\Property(property="total_students", type="integer", example=11),
     *                          @OA\Property(property="total_students_female", type="integer", example=8),
     *                          @OA\Property(property="total_students_male", type="integer", example=3),
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

    /**
     * @OA\Get(
     *     path="/api/v4/institutions/{institutionId}/student-nationality-summaries",
     *     summary="Get list of institution student nationality summary",
     *     description="Returns list of institution student nationality summary",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="institutionId",
     *         in="path",
     *         required=true,
     *         description="Institution Id",
     *         @OA\Schema(type="integer", example=6)
     *     ),
     *     @OA\Parameter(
     *         name="academic_period_id",
     *         in="query",
     *         required=false,
     *         description="Academic period id",
     *         @OA\Schema(type="integer", example=25)
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="total_students")
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
     *                  @OA\Property(property="data", type="array",
     *                      @OA\Items(
     *                          type="object",
     *                          @OA\Property(property="academic_period_id", type="integer", example=32),
     *                          @OA\Property(property="academic_period_name", type="string", example="2022"),
     *                          @OA\Property(property="institution_id", type="integer", example=6),
     *                          @OA\Property(property="institution_code", type="string", example="P1002"),
     *                          @OA\Property(property="nationality_id", type="integer", example=3),
     *                          @OA\Property(property="nationality_name", type="string", example=null),
     *                          @OA\Property(property="total_students", type="integer", example=11),
     *                          @OA\Property(property="total_students_female", type="integer", example=8),
     *                          @OA\Property(property="total_students_male", type="integer", example=3),
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

    /**
     * @OA\Get(
     *     path="/api/v4/institutions/grades/student-nationality-summaries",
     *     summary="Get list of all institution grade student nationality summary",
     *     description="Returns list of all institution student nationality summary",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="academic_period_id",
     *         in="query",
     *         required=false,
     *         description="Academic period id",
     *         @OA\Schema(type="integer", example=25)
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="total_students")
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
     *                  @OA\Property(property="data", type="array",
     *                      @OA\Items(
     *                          type="object",
     *                          @OA\Property(property="academic_period_id", type="integer", example=32),
     *                          @OA\Property(property="academic_period_name", type="string", example="2022"),
     *                          @OA\Property(property="institution_id", type="integer", example=6),
     *                          @OA\Property(property="institution_code", type="string", example="P1002"),
     *                          @OA\Property(property="grade_id", type="integer", example=190),
     *                          @OA\Property(property="grade_name", type="string", example="Primary 2"),
     *                          @OA\Property(property="nationality_id", type="integer", example=3),
     *                          @OA\Property(property="nationality_name", type="string", example=null),
     *                          @OA\Property(property="total_students", type="integer", example=11),
     *                          @OA\Property(property="total_students_female", type="integer", example=8),
     *                          @OA\Property(property="total_students_male", type="integer", example=3),
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

    /**
     * @OA\Get(
     *     path="/api/v4/institutions/{institutionId}/grades/student-nationality-summaries",
     *     summary="Get list of institution grade student nationality summary",
     *     description="Returns list of institution grade student nationality summary",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="institutionId",
     *         in="path",
     *         required=true,
     *         description="Institution Id",
     *         @OA\Schema(type="integer", example=6)
     *     ),
     *     @OA\Parameter(
     *         name="academic_period_id",
     *         in="query",
     *         required=false,
     *         description="Academic period id",
     *         @OA\Schema(type="integer", example=25)
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="total_students")
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
     *                  @OA\Property(property="data", type="array",
     *                      @OA\Items(
     *                          type="object",
     *                          @OA\Property(property="academic_period_id", type="integer", example=32),
     *                          @OA\Property(property="academic_period_name", type="string", example="2022"),
     *                          @OA\Property(property="institution_id", type="integer", example=6),
     *                          @OA\Property(property="institution_code", type="string", example="P1002"),
     *                          @OA\Property(property="grade_id", type="integer", example=190),
     *                          @OA\Property(property="grade_name", type="string", example="Primary 2"),
     *                          @OA\Property(property="nationality_id", type="integer", example=3),
     *                          @OA\Property(property="nationality_name", type="string", example=null),
     *                          @OA\Property(property="total_students", type="integer", example=11),
     *                          @OA\Property(property="total_students_female", type="integer", example=8),
     *                          @OA\Property(property="total_students_male", type="integer", example=3),
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

    /**
     * @OA\Get(
     *     path="/api/v4/institutions/{institutionId}/grades/{gradeId}/student-nationality-summaries",
     *     summary="Get list of institution student grade nationality summary by grade Id",
     *     description="Returns list of institution grade student nationality summary by grade Id",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="institutionId",
     *         in="path",
     *         required=true,
     *         description="Institution Id",
     *         @OA\Schema(type="integer", example=6)
     *     ),
     *     @OA\Parameter(
     *         name="gradeId",
     *         in="path",
     *         required=true,
     *         description="Grade Id",
     *         @OA\Schema(type="integer", example=61)
     *     ),
     *     @OA\Parameter(
     *         name="academic_period_id",
     *         in="query",
     *         required=false,
     *         description="Academic period id",
     *         @OA\Schema(type="integer", example=25)
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="total_students")
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
     *                  @OA\Property(property="data", type="array",
     *                      @OA\Items(
     *                          type="object",
     *                          @OA\Property(property="academic_period_id", type="integer", example=32),
     *                          @OA\Property(property="academic_period_name", type="string", example="2022"),
     *                          @OA\Property(property="institution_id", type="integer", example=6),
     *                          @OA\Property(property="institution_code", type="string", example="P1002"),
     *                          @OA\Property(property="grade_id", type="integer", example=190),
     *                          @OA\Property(property="grade_name", type="string", example="Primary 2"),
     *                          @OA\Property(property="nationality_id", type="integer", example=3),
     *                          @OA\Property(property="nationality_name", type="string", example=null),
     *                          @OA\Property(property="total_students", type="integer", example=11),
     *                          @OA\Property(property="total_students_female", type="integer", example=8),
     *                          @OA\Property(property="total_students_male", type="integer", example=3),
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

    /**
     * @OA\Get(
     *     path="/api/v4/institutions/staff",
     *     summary="Get list of all institution staff",
     *     description="Returns list of all institution staff",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
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
     *                  @OA\Property(property="data", type="array",
     *                      @OA\Items(
     *                          type="object",
     *                          @OA\Property(property="id", type="integer", example=301),
     *                          @OA\Property(property="fte", type="string", example="1.00"),
     *                          @OA\Property(property="start_date", type="string", example="2015-01-01"),
     *                          @OA\Property(property="start_year", type="integer", example=2015),
     *                          @OA\Property(property="end_date", type="string", example=null),
     *                          @OA\Property(property="end_year", type="integer", example=null),
     *                          @OA\Property(property="staff_id", type="integer", example=573),
     *                          @OA\Property(property="staff_type_id", type="integer", example=3),
     *                          @OA\Property(property="staff_type_name", type="string", example="Permanent"),
     *                          @OA\Property(property="staff_status_id", type="integer", example=1),
     *                          @OA\Property(property="staff_status_name", type="string", example="Assigned"),
     *                          @OA\Property(property="institution_id", type="integer", example=6),
     *                          @OA\Property(property="institution_position_id", type="integer", example=42),
     *                          @OA\Property(property="classes", type="array",
     *                              @OA\Items(
     *                                 @OA\Property(property="id", type="integer", example=524),
     *                                 @OA\Property(property="name", type="string", example="Primary 1-A"),
     *                                 @OA\Property(property="subjects", type="array",
     *                                     @OA\Items(
     *                                         @OA\Property(property="id", type="integer", example=4101),
     *                                         @OA\Property(property="name", type="string", example="Social Studies")
     *                                     )
     *                                 )
     *                             )
     *                          ),
     *                          @OA\Property(
     *                              property="custom_fields",
     *                              type="array",
     *                              @OA\Items(
     *                                  type="object",
     *                                  @OA\Property(property="id", type="string", example="0d66a4f8-a274-48cc-9520-967a07731ae8"),
     *                                  @OA\Property(property="text_value", type="string", example=""),
     *                                  @OA\Property(property="number_value", type="number", example=null),
     *                                  @OA\Property(property="decimal_value", type="string", example=""),
     *                                  @OA\Property(property="textarea_value", type="string", example=""),
     *                                  @OA\Property(property="date_value", type="string", format="date", example=null),
     *                                  @OA\Property(property="time_value", type="string", format="time", example=null),
     *                                  @OA\Property(property="file", type="string", example=""),
     *                                  @OA\Property(property="staff_custom_field_id", type="integer", example=1),
     *                                  @OA\Property(property="staff_id", type="integer", example=573),
     *                                  @OA\Property(
     *                                      property="student_custom_field",
     *                                      type="object",
     *                                      @OA\Property(property="id", type="integer", example=1),
     *                                      @OA\Property(property="name", type="string", example="Father Living With Student")
     *                                  )
     *                              )
     *                          ),
     *                          @OA\Property(property="security_group_user_id", type="string", example="7a37caf1-b411-4db4-9402-1c3163e158c0"),
     *                          @OA\Property(property="modified_user_id", type="integer", example=null),
     *                          @OA\Property(property="modified", type="string", example=null),
     *                          @OA\Property(property="created_user_id", type="integer", example=2),
     *                          @OA\Property(property="created", type="string", example="2018-03-30 22:17:09"),
     *                          @OA\Property(property="institution_code", type="string", example="P1002"),
     *                          @OA\Property(property="institution_name", type="string", example="Avory Primary School"),
     *                          @OA\Property(property="institution_position_name", type="string", example="Principal")
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

    /**
     * @OA\Get(
     *     path="/api/v4/institutions/{institutionId}/staff",
     *     summary="Get list of institution staff by institution id",
     *     description="Returns list of institution staff by institution id",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="institutionId",
     *         in="path",
     *         required=true,
     *         description="Institution Id",
     *         @OA\Schema(type="integer", example=6)
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
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
     *                  @OA\Property(property="data", type="array",
     *                      @OA\Items(
     *                          type="object",
     *                          @OA\Property(property="id", type="integer", example=301),
     *                          @OA\Property(property="fte", type="string", example="1.00"),
     *                          @OA\Property(property="start_date", type="string", example="2015-01-01"),
     *                          @OA\Property(property="start_year", type="integer", example=2015),
     *                          @OA\Property(property="end_date", type="string", example=null),
     *                          @OA\Property(property="end_year", type="integer", example=null),
     *                          @OA\Property(property="staff_id", type="integer", example=573),
     *                          @OA\Property(property="staff_type_id", type="integer", example=3),
     *                          @OA\Property(property="staff_type_name", type="string", example="Permanent"),
     *                          @OA\Property(property="staff_status_id", type="integer", example=1),
     *                          @OA\Property(property="staff_status_name", type="string", example="Assigned"),
     *                          @OA\Property(property="institution_id", type="integer", example=6),
     *                          @OA\Property(property="institution_position_id", type="integer", example=42),
     *                          @OA\Property(property="classes", type="array",
     *                              @OA\Items(
     *                                 @OA\Property(property="id", type="integer", example=524),
     *                                 @OA\Property(property="name", type="string", example="Primary 1-A"),
     *                                 @OA\Property(property="subjects", type="array",
     *                                     @OA\Items(
     *                                         @OA\Property(property="id", type="integer", example=4101),
     *                                         @OA\Property(property="name", type="string", example="Social Studies")
     *                                     )
     *                                 )
     *                             )
     *                          ),
     *                          @OA\Property(
     *                              property="custom_fields",
     *                              type="array",
     *                              @OA\Items(
     *                                  type="object",
     *                                  @OA\Property(property="id", type="string", example="0d66a4f8-a274-48cc-9520-967a07731ae8"),
     *                                  @OA\Property(property="text_value", type="string", example=""),
     *                                  @OA\Property(property="number_value", type="number", example=null),
     *                                  @OA\Property(property="decimal_value", type="string", example=""),
     *                                  @OA\Property(property="textarea_value", type="string", example=""),
     *                                  @OA\Property(property="date_value", type="string", format="date", example=null),
     *                                  @OA\Property(property="time_value", type="string", format="time", example=null),
     *                                  @OA\Property(property="file", type="string", example=""),
     *                                  @OA\Property(property="staff_custom_field_id", type="integer", example=1),
     *                                  @OA\Property(property="staff_id", type="integer", example=573),
     *                                  @OA\Property(
     *                                      property="student_custom_field",
     *                                      type="object",
     *                                      @OA\Property(property="id", type="integer", example=1),
     *                                      @OA\Property(property="name", type="string", example="Father Living With Student")
     *                                  )
     *                              )
     *                          ),
     *                          @OA\Property(property="security_group_user_id", type="string", example="7a37caf1-b411-4db4-9402-1c3163e158c0"),
     *                          @OA\Property(property="modified_user_id", type="integer", example=null),
     *                          @OA\Property(property="modified", type="string", example=null),
     *                          @OA\Property(property="created_user_id", type="integer", example=2),
     *                          @OA\Property(property="created", type="string", example="2018-03-30 22:17:09"),
     *                          @OA\Property(property="institution_code", type="string", example="P1002"),
     *                          @OA\Property(property="institution_name", type="string", example="Avory Primary School"),
     *                          @OA\Property(property="institution_position_name", type="string", example="Principal")
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

    /**
     * @OA\Get(
     *     path="/api/v4/institutions/{institutionId}/staff/{staffId}",
     *     summary="Get list of institution staff by institution id",
     *     description="Returns list of institution staff by institution id",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="institutionId",
     *         in="path",
     *         required=true,
     *         description="Institution Id",
     *         @OA\Schema(type="integer", example=6)
     *     ),
     *     @OA\Parameter(
     *         name="staffId",
     *         in="path",
     *         required=true,
     *         description="Staff Id",
     *         @OA\Schema(type="integer", example=6)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="object",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=301),
     *                 @OA\Property(property="fte", type="string", example="1.00"),
     *                 @OA\Property(property="start_date", type="string", example="2015-01-01"),
     *                 @OA\Property(property="start_year", type="integer", example=2015),
     *                 @OA\Property(property="end_date", type="string", example=null),
     *                 @OA\Property(property="end_year", type="integer", example=null),
     *                 @OA\Property(property="staff_id", type="integer", example=573),
     *                 @OA\Property(property="staff_type_id", type="integer", example=3),
     *                 @OA\Property(property="staff_type_name", type="string", example="Permanent"),
     *                 @OA\Property(property="staff_status_id", type="integer", example=1),
     *                 @OA\Property(property="staff_status_name", type="string", example="Assigned"),
     *                 @OA\Property(property="institution_id", type="integer", example=6),
     *                 @OA\Property(property="institution_position_id", type="integer", example=42),
     *                 @OA\Property(property="classes", type="array",
     *                              @OA\Items(
     *                                 @OA\Property(property="id", type="integer", example=524),
     *                                 @OA\Property(property="name", type="string", example="Primary 1-A"),
     *                                 @OA\Property(property="subjects", type="array",
     *                                     @OA\Items(
     *                                         @OA\Property(property="id", type="integer", example=4101),
     *                                         @OA\Property(property="name", type="string", example="Social Studies")
     *                                     )
     *                                 )
     *                             )
     *                          ),
     *                 @OA\Property(
     *                              property="custom_fields",
     *                              type="array",
     *                              @OA\Items(
     *                                  type="object",
     *                                  @OA\Property(property="id", type="string", example="0d66a4f8-a274-48cc-9520-967a07731ae8"),
     *                                  @OA\Property(property="text_value", type="string", example=""),
     *                                  @OA\Property(property="number_value", type="number", example=null),
     *                                  @OA\Property(property="decimal_value", type="string", example=""),
     *                                  @OA\Property(property="textarea_value", type="string", example=""),
     *                                  @OA\Property(property="date_value", type="string", format="date", example=null),
     *                                  @OA\Property(property="time_value", type="string", format="time", example=null),
     *                                  @OA\Property(property="file", type="string", example=""),
     *                                  @OA\Property(property="staff_custom_field_id", type="integer", example=1),
     *                                  @OA\Property(property="staff_id", type="integer", example=573),
     *                                  @OA\Property(
     *                                      property="student_custom_field",
     *                                      type="object",
     *                                      @OA\Property(property="id", type="integer", example=1),
     *                                      @OA\Property(property="name", type="string", example="Father Living With Student")
     *                                  )
     *                              )
     *                          ),
     *                 @OA\Property(property="security_group_user_id", type="string", example="7a37caf1-b411-4db4-9402-1c3163e158c0"),
     *                 @OA\Property(property="modified_user_id", type="integer", example=null),
     *                 @OA\Property(property="modified", type="string", example=null),
     *                 @OA\Property(property="created_user_id", type="integer", example=2),
     *                 @OA\Property(property="created", type="string", example="2018-03-30 22:17:09"),
     *                 @OA\Property(property="institution_code", type="string", example="P1002"),
     *                 @OA\Property(property="institution_name", type="string", example="Avory Primary School"),
     *                 @OA\Property(property="institution_position_name", type="string", example="Principal")
     *              )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
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



    /**
     * @OA\Get(
     *     path="/api/v4/institutions/positions/list",
     *     summary="Get a list of positions",
     *     description="Retrieve a paginated list of positions for an institution with sorting options",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Field by which to order the results",
     *         @OA\Schema(type="string", example="id")
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
     *                 type="object",
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="position_id", type="integer", example=1),
     *                         @OA\Property(property="status_id", type="integer", example=29),
     *                         @OA\Property(property="status_name", type="string", example="Active"),
     *                         @OA\Property(property="position_no", type="string", example="K0001-1522277303"),
     *                         @OA\Property(property="staff_position_title_id", type="integer", example=240),
     *                         @OA\Property(property="staff_position_title_name", type="string", example="Principal"),
     *                         @OA\Property(property="institution_id", type="integer", example=1),
     *                         @OA\Property(property="assignee_id", type="integer", example=8805),
     *                         @OA\Property(property="modified_user_id", type="integer", example=2),
     *                         @OA\Property(property="modified", type="string", format="date-time", example="2018-03-30 17:30:29"),
     *                         @OA\Property(property="created_user_id", type="integer", example=2),
     *                         @OA\Property(property="created", type="string", format="date-time", example="2018-03-28 22:48:26")
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


    /**
     * @OA\Get(
     *     path="/api/v4/institutions/{institutionId}/positions",
     *     summary="Get list of positions",
     *     description="Returns list of positions",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="institutionId",
     *         in="path",
     *         required=true,
     *         description="Institution Id",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Field by which to order the results",
     *         @OA\Schema(type="string", example="id")
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
     *                  @OA\Property(property="data", type="array",
     *                      @OA\Items(
     *                          type="object",
     *                          @OA\Property(property="position_id", type="integer", example=76),
     *                          @OA\Property(property="status_id", type="integer", example=29),
     *                          @OA\Property(property="status_name", type="string", example="Active"),
     *                          @OA\Property(property="position_no", type="string", example="P1002-1522433392"),
     *                          @OA\Property(property="staff_position_title_id", type="integer", example=1),
     *                          @OA\Property(property="staff_position_title_name", type="string", example="Teacher"),
     *                          @OA\Property(property="institution_id", type="integer", example=6),
     *                          @OA\Property(property="assignee_id", type="integer", example=1),
     *                          @OA\Property(property="modified_user_id", type="integer", example=1),
     *                          @OA\Property(property="modified", type="date", example="2022-01-01 10:32:20"),
     *                          @OA\Property(property="created_user_id", type="integer", example=1),
     *                          @OA\Property(property="created", type="date", example="2022-01-01 10:32:20"),
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

    /**
     * @OA\Get(
     *     path="/api/v4/institutions/{institutionId}/positions/{positionId}",
     *     summary="Get positions detail by id",
     *     description="Returns positions detail by id",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="institutionId",
     *         in="path",
     *         required=true,
     *         description="Institution Id",
     *         @OA\Schema(type="integer", example=6)
     *     ),
     *     @OA\Parameter(
     *         name="positionId",
     *         in="path",
     *         required=true,
     *         description="Position Id",
     *         @OA\Schema(type="integer", example=76)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="object",
     *                 type="object",
     *                 @OA\Property(property="position_id", type="integer", example=76),
     *                 @OA\Property(property="status_id", type="integer", example=29),
     *                 @OA\Property(property="status_name", type="string", example="Active"),
     *                 @OA\Property(property="position_no", type="string", example="P1002-1522433392"),
     *                 @OA\Property(property="staff_position_title_id", type="integer", example=1),
     *                 @OA\Property(property="staff_position_title_name", type="string", example="Teacher"),
     *                 @OA\Property(property="institution_id", type="integer", example=6),
     *                 @OA\Property(property="assignee_id", type="integer", example=1),
     *                 @OA\Property(property="modified_user_id", type="integer", example=1),
     *                 @OA\Property(property="modified", type="date", example="2022-01-01 10:32:20"),
     *                 @OA\Property(property="created_user_id", type="integer", example=1),
     *                 @OA\Property(property="created", type="date", example="2022-01-01 10:32:20"),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/v4/locale-contents",
     *     summary="Get list of locale contents",
     *     description="Returns list of locale contents",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="locale_name",
     *         in="query",
     *         required=false,
     *         description="Locale name",
     *         @OA\Schema(type="string", example="test")
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Field by which to order the results",
     *         @OA\Schema(type="string", example="id")
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
     *                  @OA\Property(property="data", type="array",
     *                      @OA\Items(
     *                          type="object",
     *                          @OA\Property(property="id", type="integer", example=76),
     *                          @OA\Property(property="translation", type="string", example="Accueil"),
     *                          @OA\Property(property="locale_content_id", type="integer", example=6),
     *                          @OA\Property(property="locale_content_name", type="string", example=""),
     *                          @OA\Property(property="locale_id", type="integer", example=4),
     *                          @OA\Property(property="locale_name", type="string", example="Francais"),
     *                          @OA\Property(property="modified_user_id", type="integer", example=1),
     *                          @OA\Property(property="modified", type="date", example="2022-01-01 10:32:20"),
     *                          @OA\Property(property="created_user_id", type="integer", example=1),
     *                          @OA\Property(property="created", type="date", example="2022-01-01 10:32:20"),
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

    /**
     * @OA\Get(
     *     path="/api/v4/locale-contents/{id}",
     *     summary="Get locale contents by id",
     *     description="Returns a locale contents by id",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Locale content translation Id",
     *         @OA\Schema(type="integer", example=76)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=76),
     *                 @OA\Property(property="translation", type="string", example="Accueil"),
     *                 @OA\Property(property="locale_content_id", type="integer", example=6),
     *                 @OA\Property(property="locale_content_name", type="string", example=""),
     *                 @OA\Property(property="locale_id", type="integer", example=4),
     *                 @OA\Property(property="locale_name", type="string", example="Francais"),
     *                 @OA\Property(property="modified_user_id", type="integer", example=1),
     *                 @OA\Property(property="modified", type="date", example="2022-01-01 10:32:20"),
     *                 @OA\Property(property="created_user_id", type="integer", example=1),
     *                 @OA\Property(property="created", type="date", example="2022-01-01 10:32:20"),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/v4/institutions/room-type-summaries",
     *     summary="Get list of room type summaries",
     *     description="Returns a list of room type summaries",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="academic_period_id",
     *         in="query",
     *         required=false,
     *         description="Academic period Id",
     *         @OA\Schema(type="integer", example=32)
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
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
     *                  @OA\Property(property="data", type="array",
     *                      @OA\Items(
     *                          type="object",
     *                          @OA\Property(property="academic_period_id", type="integer", example=76),
     *                          @OA\Property(property="academic_period_name", type="string", example="2024"),
     *                          @OA\Property(property="institution_id", type="integer", example="6"),
     *                          @OA\Property(property="institution_code", type="integer", example=206),
     *                          @OA\Property(property="room_type", type="string", example="Classroom"),
     *                          @OA\Property(property="total_rooms", type="integer", example=10)
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

    /**
     * @OA\Get(
     *     path="/api/v4/institutions/{institutionId}/room-type-summaries",
     *     summary="Get list of institutions room type summaries",
     *     description="Returns a list of institutions room  type summaries",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="institutionId",
     *         in="path",
     *         required=true,
     *         description="Institution Id",
     *         @OA\Schema(type="integer", example=6)
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
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
     *                  @OA\Property(property="data", type="array",
     *                      @OA\Items(
     *                          type="object",
     *                          @OA\Property(property="academic_period_id", type="integer", example=76),
     *                          @OA\Property(property="academic_period_name", type="string", example="2024"),
     *                          @OA\Property(property="institution_id", type="integer", example="6"),
     *                          @OA\Property(property="institution_code", type="integer", example=206),
     *                          @OA\Property(property="room_type", type="string", example="Classroom"),
     *                          @OA\Property(property="total_rooms", type="integer", example=10)
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



    /**
     * @OA\Post(
     *     path="/api/v4/institutions/{institutionId}/classes/{classId}/reportcardcomment",
     *     summary="Add a report card comment for a student",
     *     description="Creates a new report card comment for a specific student in a class.",
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
     *         @OA\Schema(type="integer", example=240)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Payload for adding report card comment",
     *         @OA\JsonContent(
     *             required={
     *                 "academic_period_id",
     *                 "education_grade_id",
     *                 "student_id",
     *                 "education_subject_id",
     *                 "staff_id",
     *                 "comment",
     *                 "report_card_id"
     *             },
     *             @OA\Property(property="academic_period_id", type="integer", example=27),
     *             @OA\Property(property="education_grade_id", type="integer", example=136),
     *             @OA\Property(property="student_id", type="integer", example=8831),
     *             @OA\Property(property="education_subject_id", type="integer", example=75),
     *             @OA\Property(property="staff_id", type="integer", example=2),
     *             @OA\Property(property="comment", type="string", example="This is a dummy comment"),
     *             @OA\Property(property="report_card_id", type="integer", example=1),
     *             @OA\Property(property="report_card_comment_code_id", type="string", example="")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="array",
     *                  @OA\Items()
     *              )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
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



    /**
     * @OA\Post(
     *     path="/api/v4/institutions/{institutionId}/classes/{classId}/reportcardcomment/homeroom",
     *     summary="Add a report card comment for a homeroom class",
     *     description="Adds a report card comment for a homeroom class based on the provided parameters",
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
     *         @OA\Schema(type="integer", example=568)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Json Payload",
     *         @OA\JsonContent(
     *              required={
     *                 "academic_period_id",
     *                 "education_grade_id",
     *                 "student_id",
     *                 "comment",
     *                 "report_card_id"
     *              },
     *              @OA\Property(property="academic_period_id", type="integer", example=32),
     *              @OA\Property(property="education_grade_id", type="integer", example=189),
     *              @OA\Property(property="student_id", type="integer", example=3540),
     *              @OA\Property(property="comment", type="string", example="The student shows respect for teachers and peers."),
     *              @OA\Property(property="report_card_id", type="integer", example=6)
     *          )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="array",
     *                  @OA\Items()
     *              )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
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



    /**
     * @OA\Post(
     *     path="/api/v4/institutions/{institutionId}/classes/{classId}/reportcardcomment/principal",
     *     summary="Add a report card comment by the principal",
     *     description="Adds a report card comment by the principal for a specific student based on the provided parameters",
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
     *         @OA\Schema(type="integer", example=240)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Json Payload",
     *         @OA\JsonContent(
     *              required={
     *                 "academic_period_id",
     *                 "education_grade_id",
     *                 "student_id",
     *                 "comment",
     *                 "report_card_id"
     *              },
     *              @OA\Property(property="academic_period_id", type="integer", example=32),
     *              @OA\Property(property="education_grade_id", type="integer", example=189),
     *              @OA\Property(property="student_id", type="integer", example=3540),
     *              @OA\Property(property="comment", type="string", example="The student shows respect for teachers and peers."),
     *              @OA\Property(property="report_card_id", type="integer", example=6)
     *          )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="array",
     *                  @OA\Items()
     *              )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/v4/institutions/{institutionId}/grades/{gradeId}/students/{studentId}",
     *     summary="Get institution grade student detail",
     *     description="Returns institution grade student detail",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="institutionId",
     *         in="path",
     *         required=true,
     *         description="Institution Id",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="gradeId",
     *         in="path",
     *         required=true,
     *         description="Grade Id",
     *         @OA\Schema(type="integer", example=59)
     *     ),
     *     @OA\Parameter(
     *         name="studentId",
     *         in="path",
     *         required=true,
     *         description="Student Id",
     *         @OA\Schema(type="integer", example=111)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *       @OA\JsonContent(
     *          type="object",
     *          @OA\Property(property="message", type="string", example="Successful."),
     *          @OA\Property(property="data", type="object",
     *              @OA\Property(property="academic_period_id", type="integer", example=32),
     *              @OA\Property(property="institution_id", type="integer", example=6),
     *              @OA\Property(property="education_grade_id", type="integer", example=190),
     *              @OA\Property(property="student_status_id", type="integer", example=1),
     *              @OA\Property(property="student_id", type="integer", example=12047),
     *              @OA\Property(property="username", type="string", example="1611035684"),
     *              @OA\Property(property="openemis_no", type="string", example="1611035684"),
     *              @OA\Property(property="first_name", type="string", example="Rheba"),
     *              @OA\Property(property="last_name", type="string", example="MacWhirter"),
     *              @OA\Property(property="gender_id", type="integer", example=2),
     *              @OA\Property(property="date_of_birth", type="string", format="date-time", example="2014-02-14T00:00:00.000000Z"),
     *              @OA\Property(property="start_year", type="integer", example=2023),
     *              @OA\Property(property="start_date", type="string", format="date", example="2023-01-01"),
     *              @OA\Property(property="end_year", type="integer", example=2023),
     *              @OA\Property(property="end_date", type="string", format="date", example="2023-12-31")
     *          )
     *      )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
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




    /**
     * @OA\Post(
     *     path="/api/v4/institutions/students/competencies/results",
     *     summary="Add competency results for a student",
     *     description="Adds competency results for a student based on the provided parameters",
     *     tags={"Institutions"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="JSON Payload",
     *         @OA\JsonContent(
     *             required={
     *                 "academic_period_id",
     *                 "student_id",
     *                 "competency_template_id",
     *                 "competency_period_id",
     *                 "competency_item_id",
     *                 "competency_criteria_id",
     *                 "institution_id",
     *                 "competency_grading_option_id",
     *              },
     *             @OA\Property(property="academic_period_id", type="integer", example=32),
     *             @OA\Property(property="competency_grading_option_id", type="integer", example=1),
     *             @OA\Property(property="comments", type="string", example="test comment"),
     *             @OA\Property(property="student_id", type="integer", example=1311),
     *             @OA\Property(property="competency_template_id", type="integer", example=39),
     *             @OA\Property(property="competency_item_id", type="integer", example=148),
     *             @OA\Property(property="competency_criteria_id", type="integer", example=279),
     *             @OA\Property(property="competency_period_id", type="integer", example=20),
     *             @OA\Property(property="institution_id", type="integer", example=6)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="array",
     *                  @OA\Items()
     *              )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
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


    /**
     * @OA\Post(
     *     path="/api/v4/institutions/students/competencies/item/comments",
     *     summary="Add competency item comments for a student",
     *     description="Adds competency item comments for a student based on the provided parameters",
     *     tags={"Institutions"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="JSON Payload",
     *         @OA\JsonContent(
     *             required={
     *                 "academic_period_id",
     *                 "student_id",
     *                 "competency_template_id",
     *                 "competency_period_id",
     *                 "competency_item_id",
     *                 "institution_id",
     *              },
     *             @OA\Property(property="academic_period_id", type="integer", example=32),
     *             @OA\Property(property="education_grade_id", type="integer", example=189),  
     *             @OA\Property(property="comments", type="string", example="test comment"),
     *             @OA\Property(property="student_id", type="integer", example=1311),
     *             @OA\Property(property="competency_template_id", type="integer", example=1),
     *             @OA\Property(property="competency_item_id", type="integer", example=3),
     *             @OA\Property(property="competency_period_id", type="integer", example=3),
     *             @OA\Property(property="institution_id", type="integer", example=6),
     *             @OA\Property(property="institution_class_id", type="integer", example=568)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="array",
     *                  @OA\Items()
     *              )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
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



    /**
     * @OA\Post(
     *     path="/api/v4/institutions/students/competencies/periods/comments",
     *     summary="Add competency periods comments for a student",
     *     description="Adds competency periods comments for a student based on the provided parameters",
     *     tags={"Institutions"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="JSON Payload",
     *         @OA\JsonContent(
     *             required={
     *                 "academic_period_id",
     *                 "student_id",
     *                 "competency_template_id",
     *                 "competency_period_id",
     *                 "institution_id",
     *                 "comments"
     *              },
     *             @OA\Property(property="academic_period_id", type="integer", example=32), 
     *             @OA\Property(property="comments", type="string", example="test comment"),
     *             @OA\Property(property="student_id", type="integer", example=1311),
     *             @OA\Property(property="competency_template_id", type="integer", example=39),
     *             @OA\Property(property="competency_period_id", type="integer", example=26),
     *             @OA\Property(property="institution_id", type="integer", example=6)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="array",
     *                  @OA\Items()
     *              )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
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


    /**
     * @OA\Get(
     *     path="/api/v4/institutions/{institutionId}/students/{studentId}/assessment-item-results",
     *     summary="Get list of student assesment result",
     *     description="Returns a list of student assesment result",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="institutionId",
     *         in="path",
     *         required=true,
     *         description="Institution Id",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Parameter(
     *         name="studentId",
     *         in="path",
     *         required=true,
     *         description="Student Id",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order.",
     *         @OA\Schema(type="integer", example="id")
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
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                         @OA\Property(property="id", type="string", example="39ca854c-c5e3-44b2-baa2-dda96c24e0ca"),
     *                         @OA\Property(property="academic_period_id", type="integer", example=27),
     *                         @OA\Property(property="assessment_grading_option_id", type="integer", example=2),
     *                         @OA\Property(property="assessment_grading_option_name", type="string", example="Excellent"),
     *                         @OA\Property(property="assessment_id", type="integer", example=17),
     *                         @OA\Property(property="assessment_period_id", type="integer", example=3),
     *                         @OA\Property(property="education_grade_id", type="integer", example=136),
     *                         @OA\Property(property="education_subject_id", type="integer", example=6),
     *                         @OA\Property(property="institution_id", type="integer", example=2),
     *                         @OA\Property(property="marks", type="string", example="91.00"),
     *                         @OA\Property(property="student_id", type="integer", example=8831),
     *                         @OA\Property(property="modified_user_id", type="integer", example=null),
     *                         @OA\Property(property="modified", type="string", example=null),
     *                         @OA\Property(property="created_user_id", type="integer", example=2),
     *                         @OA\Property(property="created", type="string", example="2018-04-26 07:08:02")
     *                  )
     *             ),
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
    public function getStudentAssessmentItemResult(Request $request, $institutionId, $studentId)
    {
        try {
            $params = $request->all();
            $data = $this->institutionService->getStudentAssessmentItemResult($params, $institutionId, $studentId);
            
            return $this->sendSuccessResponse("Student Assessment Details Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to get student assessment data.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get student assessment data.');
        }
    }
    
    /**
     * @OA\Get(
     *     path="/api/v4/area-administrative/display-address-area-level",
     *     summary="Get list of address area level",
     *     description="Returns a list of address area level",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
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
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                          @OA\Property(property="id", type="integer", example=1),
     *                          @OA\Property(property="name", type="string", example="Endor"),
     *                  )
     *             ),
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
    public function displayAddressAreaLevel(Request $request)
    {
        try {
            $params = $request->all();
            $data = $this->institutionService->displayAddressAreaLevel($params);
            
            return $this->sendSuccessResponse("Address area level area found.", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to get address area level area.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get address area level area.');
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v4/area-administrative/display-birthplace-area-level",
     *     summary="Get list of birthplace area level",
     *     description="Returns a list of birthplace area level",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
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
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                          @OA\Property(property="id", type="integer", example=1),
     *                          @OA\Property(property="name", type="string", example="Endor"),
     *                  )
     *             ),
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
    public function displayBirthplaceAreaLevel(Request $request)
    {
        try {
            $params = $request->all();
            $data = $this->institutionService->displayBirthplaceAreaLevel($params);
            
            return $this->sendSuccessResponse("Birthplace area level area found.", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to get birthplace area level area.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get birthplace area level area.');
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v4/institutions/subjects/staff",
     *     summary="Get list of subject staff",
     *     description="Returns a list of  subject staff",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="institution_id",
     *         in="query",
     *         required=true,
     *         description="Institution Id",
     *         @OA\Schema(type="integer", example=6)
     *     ),
     *     @OA\Parameter(
     *         name="staff_id",
     *         in="path",
     *         required=true,
     *         description="Staff Id",
     *         @OA\Schema(type="integer", example=66)
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="path",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
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
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                      @OA\Property(property="education_systems_name", type="string", example="National Education System 2023"),
     *                          @OA\Property(property="education_levels_name", type="string", example="Primary Education"),
     *                          @OA\Property(property="education_cycles_name", type="string", example="Primary - General"),
     *                          @OA\Property(property="education_programmes_code", type="string", example="Primary"),
     *                          @OA\Property(property="education_programmes_name", type="string", example="Primary"),
     *                          @OA\Property(property="education_grades_code", type="string", example="Primary 1"),
     *                          @OA\Property(property="education_grades_name", type="string", example="Primary 1"),
     *                          @OA\Property(property="education_subjects_code", type="string", example="SSMC"),
     *                          @OA\Property(property="education_subjects_name", type="string", example="Social Studies"),
     *                          @OA\Property(property="institutions_id", type="integer", example=6),
     *                          @OA\Property(property="institutions_code", type="string", example="P1002"),
     *                          @OA\Property(property="institutions_name", type="string", example="Avory Primary School"),
     *                          @OA\Property(property="institution_classes_name", type="string", example="Primary 1-A"),
     *                          @OA\Property(property="academic_periods_code", type="string", example="YR2023"),
     *                          @OA\Property(property="academic_periods_name", type="string", example="2023"),
     *                          @OA\Property(property="institution_subjects_id", type="integer", example=4516),
     *                          @OA\Property(property="institution_subjects_name", type="string", example="Social Studies"),
     *                          @OA\Property(property="security_users_openemis_no_subject_teachers", type="string", example="1522952429"),
     *                     @OA\Property(property="security_users_openemis_no_students", type="array",
     *                         @OA\Items(type="integer", example=2382817279)
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
    public function getSubjectsStaffList(Request $request)
    {
        try {
            $params = $request->all();
            if(!isset($request['staff_id']) || !isset($request['institution_id'])){
                return $this->sendErrorResponse('Staff id and institution id is required.');
            }
            $data = $this->institutionService->getSubjectsStaffList($params);
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
    /**
     * @OA\Get(
     *     path="/api/v4/absence-reasons",
     *     summary="Get list of absence reasons",
     *     description="Returns a list of absence reasons",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
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
     *                  @OA\Property(property="data", type="array",
     *                      @OA\Items(
     *                          type="object",
     *                                  @OA\Property(property="id", type="integer", example=1),
     *                                  @OA\Property(property="name", type="string", example="Illness"),
     *                          )
     *                  ),
     *             ),
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
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


    /**
     * @OA\Get(
     *     path="/api/v4/absence-types",
     *     summary="Get list of absence types",
     *     description="Returns a list of absence types",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
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
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                          @OA\Property(property="id", type="integer", example=1),
     *                          @OA\Property(property="code", type="string", example="Present"),
     *                          @OA\Property(property="name", type="string", example="Present"),
     *                  )
     *             ),
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/v4/area-administratives",
     *     summary="Get list of area administratives",
     *     description="Returns a list of area-administratives",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
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
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                          @OA\Property(property="id", type="integer", example=1),
     *                          @OA\Property(property="code", type="string", example="World"),
     *                          @OA\Property(property="name", type="string", example="World"),
     *                          @OA\Property(property="is_main_country", type="integer", example=1),
     *                          @OA\Property(property="parent_id", type="integer", example=null),
     *                          @OA\Property(property="lft", type="integer", example=1),
     *                          @OA\Property(property="rght", type="integer", example=450),
     *                          @OA\Property(property="area_administrative_level_id", type="integer", example=1),
     *                          @OA\Property(property="order", type="integer", example=1),
     *                          @OA\Property(property="visible", type="integer", example=1),
     *                          @OA\Property(property="modified_user_id", type="integer", example=1),
     *                          @OA\Property(property="modified", type="date", example="2022-01-01 10:32:20"),
     *                          @OA\Property(property="created_user_id", type="integer", example=1),
     *                          @OA\Property(property="created", type="date", example="2022-01-01 10:32:20"),
     *                          @OA\Property(property="area_administrative_levels",
     *                          type="object",
     *                              @OA\Property(property="id", type="integer", example=4554),
     *                              @OA\Property(property="name", type="string", example="World"),
     *                              @OA\Property(property="level", type="integer", example=-1),
     *                              @OA\Property(property="area_administrative_id", type="integer", example=1),
     *                              @OA\Property(property="modified_user_id", type="integer", example=1),
     *                              @OA\Property(property="modified", type="date", example="2022-01-01 10:32:20"),
     *                              @OA\Property(property="created_user_id", type="integer", example=1),
     *                              @OA\Property(property="created", type="date", example="2022-01-01 10:32:20"),
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

    /**
     * @OA\Get(
     *     path="/api/v4/area-administratives/{areaAdministrativeId}",
     *     summary="Get area administratives by id",
     *     description="Returns area administratives by area administrative id",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="areaAdministrativeId",
     *         in="path",
     *         required=true,
     *         description="Area Administrative Id",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="code", type="string", example="World"),
     *                 @OA\Property(property="name", type="string", example="World"),
     *                 @OA\Property(property="is_main_country", type="integer", example=1),
     *                 @OA\Property(property="parent_id", type="integer", example=null),
     *                 @OA\Property(property="lft", type="integer", example=1),
     *                 @OA\Property(property="rght", type="integer", example=450),
     *                 @OA\Property(property="area_administrative_level_id", type="integer", example=1),
     *                 @OA\Property(property="order", type="integer", example=1),
     *                 @OA\Property(property="visible", type="integer", example=1),
     *                 @OA\Property(property="modified_user_id", type="integer", example=1),
     *                 @OA\Property(property="modified", type="date", example="2022-01-01 10:32:20"),
     *                 @OA\Property(property="created_user_id", type="integer", example=1),
     *                 @OA\Property(property="created", type="date", example="2022-01-01 10:32:20"),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
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
    
    /**
     * @OA\Get(
     *     path="/api/v4/institutions/genders",
     *     summary="Get list of institution genders ",
     *     description="Returns a list of genders",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
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
     *             @OA\Property(property="data", type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="integer", example=1),
     *                      @OA\Property(property="name", type="string", example="Male"),
     *                      @OA\Property(property="code", type="string", example="M"),
     *                      @OA\Property(property="order", type="integer", example=2),
     *                      @OA\Property(property="created_user_id", type="integer", example=1),
     *                      @OA\Property(property="created", type="date", example="2022-01-01 10:32:20"),
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
    public function getInstitutionGenders(Request $request)
    {
        try {
            $params = $request->all();
            $data = $this->institutionService->getInstitutionGenders($params);
            return $this->sendSuccessResponse("Institution Genders List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Institution Genders List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution Genders List Not Found');
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v4/institutions/localities/{localitiesId}",
     *     summary="Get institution locality by locality id",
     *     description="Returns institution locality by locality id",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="localitiesId",
     *         in="path",
     *         required=true,
     *         description="Locality Id",
     *         @OA\Schema(type="integer", example=6)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Urban"),
     *                 @OA\Property(property="order", type="integer", example=1),
     *                 @OA\Property(property="visible", type="integer", example=1),
     *                 @OA\Property(property="editable", type="integer", example=1),
     *                 @OA\Property(property="default", type="integer", example=0),
     *                 @OA\Property(property="international_code", type="integer", example=""),
     *                 @OA\Property(property="national_code", type="integer", example=""),
     *                 @OA\Property(property="modified_user_id", type="integer", example=1),
     *                 @OA\Property(property="modified", type="date", example="2022-01-01 10:32:20"),
     *                 @OA\Property(property="created_user_id", type="integer", example=1),
     *                 @OA\Property(property="created", type="date", example="2022-01-01 10:32:20"),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/v4/institutions/ownerships/{ownershipId}",
     *     summary="Get institution ownership by ownership id",
     *     description="Returns institution ownership by ownership id",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="ownershipId",
     *         in="path",
     *         required=true,
     *         description="Ownership Id",
     *         @OA\Schema(type="integer", example=6)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Customary"),
     *                 @OA\Property(property="order", type="integer", example=1),
     *                 @OA\Property(property="visible", type="integer", example=1),
     *                 @OA\Property(property="editable", type="integer", example=1),
     *                 @OA\Property(property="default", type="integer", example=0),
     *                 @OA\Property(property="international_code", type="integer", example=""),
     *                 @OA\Property(property="national_code", type="integer", example=""),
     *                 @OA\Property(property="modified_user_id", type="integer", example=1),
     *                 @OA\Property(property="modified", type="date", example="2022-01-01 10:32:20"),
     *                 @OA\Property(property="created_user_id", type="integer", example=1),
     *                 @OA\Property(property="created", type="date", example="2022-01-01 10:32:20"),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/v4/institutions/sectors/{sectorId}",
     *     summary="Get institution sector by sector id",
     *     description="Returns institution sector by sector id",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="sectorId",
     *         in="path",
     *         required=true,
     *         description="Sector Id",
     *         @OA\Schema(type="integer", example=6)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Public"),
     *                 @OA\Property(property="order", type="integer", example=1),
     *                 @OA\Property(property="visible", type="integer", example=1),
     *                 @OA\Property(property="editable", type="integer", example=1),
     *                 @OA\Property(property="default", type="integer", example=0),
     *                 @OA\Property(property="international_code", type="integer", example=""),
     *                 @OA\Property(property="national_code", type="integer", example=""),
     *                 @OA\Property(property="modified_user_id", type="integer", example=1),
     *                 @OA\Property(property="modified", type="date", example="2022-01-01 10:32:20"),
     *                 @OA\Property(property="created_user_id", type="integer", example=1),
     *                 @OA\Property(property="created", type="date", example="2022-01-01 10:32:20"),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/v4/institutions/providers/{providerId}",
     *     summary="Get institution provider by provider id  ",
     *     description="Returns institution provider by provider id",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="providerId",
     *         in="path",
     *         required=true,
     *         description="Provider Id",
     *         @OA\Schema(type="integer", example=6)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Religious"),
     *                 @OA\Property(property="order", type="integer", example=1),
     *                 @OA\Property(property="visible", type="integer", example=1),
     *                 @OA\Property(property="editable", type="integer", example=1),
     *                 @OA\Property(property="default", type="integer", example=0),
     *                 @OA\Property(property="international_code", type="integer", example=""),
     *                 @OA\Property(property="national_code", type="integer", example=""),
     *                 @OA\Property(property="modified_user_id", type="integer", example=1),
     *                 @OA\Property(property="modified", type="date", example="2022-01-01 10:32:20"),
     *                 @OA\Property(property="created_user_id", type="integer", example=1),
     *                 @OA\Property(property="created", type="date", example="2022-01-01 10:32:20"),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/v4/institutions/types/{typesId}",
     *     summary="Get institution type",
     *     description="Returns a institution type by type id",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="typesId",
     *         in="path",
     *         required=true,
     *         description="Institution Type Id",
     *         @OA\Schema(type="integer", example=6)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Pre-primary"),
     *                 @OA\Property(property="order", type="integer", example=1),
     *                 @OA\Property(property="visible", type="integer", example=1),
     *                 @OA\Property(property="editable", type="integer", example=1),
     *                 @OA\Property(property="default", type="integer", example=0),
     *                 @OA\Property(property="international_code", type="integer", example=""),
     *                 @OA\Property(property="national_code", type="integer", example=""),
     *                 @OA\Property(property="modified_user_id", type="integer", example=1),
     *                 @OA\Property(property="modified", type="date", example="2022-01-01 10:32:20"),
     *                 @OA\Property(property="created_user_id", type="integer", example=1),
     *                 @OA\Property(property="created", type="date", example="2022-01-01 10:32:20"),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/v4/institutions/provider/{sectorId}",
     *     summary="Get list of institution providers ",
     *     description="Returns a list of  institution providers by sector id",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="sectorId",
     *         in="path",
     *         required=true,
     *         description="Sector Id",
     *         @OA\Schema(type="integer", example=6)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", example=2)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Limit ",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="integer", example=1),
     *                      @OA\Property(property="name", type="string", example="Government"),
     *                      @OA\Property(property="order", type="integer", example=1),
     *                      @OA\Property(property="visible", type="integer", example=1),
     *                      @OA\Property(property="editable", type="integer", example=1),
     *                      @OA\Property(property="default", type="integer", example=0),
     *                      @OA\Property(property="international_code", type="integer", example=""),
     *                      @OA\Property(property="national_code", type="integer", example=""),
     *                      @OA\Property(property="modified_user_id", type="integer", example=1),
     *                      @OA\Property(property="modified", type="date", example="2022-01-01 10:32:20"),
     *                      @OA\Property(property="created_user_id", type="integer", example=1),
     *                      @OA\Property(property="created", type="date", example="2022-01-01 10:32:20"),
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
    /**
     * @OA\Get(
     *     path="/api/v4/meal-programmes",
     *     summary="Get list of meal programmes",
     *     description="Returns a list of meal programmes",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
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
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="academic_period_id", type="integer", example=32),
     *                     @OA\Property(property="name", type="string", example="National Meal Programme"),
     *                     @OA\Property(property="code", type="string", example="NMP"),
     *                     @OA\Property(property="type", type="integer", example=1),
     *                     @OA\Property(property="targeting", type="integer", example=1),
     *                     @OA\Property(property="start_date", type="string", example="2021-12-31"),
     *                     @OA\Property(property="end_date", type="string", example="2021-12-31"),
     *                     @OA\Property(property="amount", type="string", example="10.00"),
     *                     @OA\Property(property="implementer", type="integer", example=1),
     *                     @OA\Property(property="modified_user_id", type="integer", example=1),
     *                     @OA\Property(property="modified", type="date", example="2022-01-01 10:32:20"),
     *                     @OA\Property(property="created_user_id", type="integer", example=1),
     *                     @OA\Property(property="created", type="date", example="2022-01-01 10:32:20"),
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

    /**
     * @OA\Delete(
     *     path="/api/v4/institutions/institution-classes/education-grades/class-attendance",
     *     summary="Delete class attendance",
     *     description="Delete class attendance",
     *     tags={"Institutions"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="JSON Payload",
     *         @OA\JsonContent(
     *             required={
     *                 "academic_period_id",
     *                 "institution_id",
     *                 "institution_class_id",
     *                 "education_grade_id",
     *                 "date"
     *              },
     *             @OA\Property(property="academic_period_id", type="integer", example=32), 
     *             @OA\Property(property="institution_id", type="integer", example=6),
     *             @OA\Property(property="institution_class_id", type="integer", example=568),
     *             @OA\Property(property="education_grade_id", type="string", example=189),
     *             @OA\Property(property="date", type="integer", example="2023-07-17"),
     *             @OA\Property(property="period", type="integer", example=1),
     *             @OA\Property(property="subject_id", type="integer", example=0)
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



    /**
     * @OA\Delete(
     *     path="/api/v4/institutions/student/{studentId}/absence",
     *     summary="Delete student attendance",
     *     description="Delete student attendance",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="studentId",
     *         in="path",
     *         required=true,
     *         description="Student id",
     *         @OA\Schema(type="integer", example=8815)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="JSON Payload",
     *         @OA\JsonContent(
     *             required={
     *                 "academic_period_id",
     *                 "institution_id",
     *                 "institution_class_id",
     *                 "education_grade_id",
     *                 "date"
     *              },
     *             @OA\Property(property="academic_period_id", type="integer", example=32), 
     *             @OA\Property(property="institution_id", type="integer", example=6),
     *             @OA\Property(property="institution_class_id", type="integer", example=568),
     *             @OA\Property(property="education_grade_id", type="string", example=189),
     *             @OA\Property(property="date", type="integer", example="2023-07-17"),
     *             @OA\Property(property="period", type="integer", example=1),
     *             @OA\Property(property="subject_id", type="integer", example=0)
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
    /**
     * @OA\Get(
     *     path="/api/v4/behaviours/categories",
     *     summary="Get list of behaviour categories",
     *     description="Returns a list of behaviour categories",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
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
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Repeated Tardiness"),
     *                     @OA\Property(property="order", type="integer", example=1),
     *                     @OA\Property(property="visible", type="integer", example=1),
     *                     @OA\Property(property="editable", type="integer", example=1),
     *                     @OA\Property(property="default", type="integer", example=0),
     *                     @OA\Property(property="international_code", type="integer", example=""),
     *                     @OA\Property(property="national_code", type="integer", example=""),
     *                     @OA\Property(property="modified_user_id", type="integer", example=1),
     *                     @OA\Property(property="modified", type="date", example="2022-01-01 10:32:20"),
     *                     @OA\Property(property="created_user_id", type="integer", example=1),
     *                     @OA\Property(property="created", type="date", example="2022-01-01 10:32:20"),
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

    // POCOR-8711 starts
    /**
     * @OA\Get(
     *     path="/api/v4/behaviours/categories/students",
     *     summary="Get list of Student behaviour categories",
     *     description="Returns a list of Student behaviour categories",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
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
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Classroom Conduct"),
     *                     @OA\Property(property="order", type="integer", example=1),
     *                     @OA\Property(property="visible", type="integer", example=1),
     *                     @OA\Property(property="editable", type="integer", example=1),
     *                     @OA\Property(property="default", type="integer", example=0),
     *                     @OA\Property(property="international_code", type="integer", example=""),
     *                     @OA\Property(property="national_code", type="integer", example=""),
     *                     @OA\Property(property="behaviour_classification_id", type="integer", example=""),
     *                     @OA\Property(property="modified_user_id", type="integer", example=1),
     *                     @OA\Property(property="modified", type="date", example="2022-01-01 10:32:20"),
     *                     @OA\Property(property="created_user_id", type="integer", example=1),
     *                     @OA\Property(property="created", type="date", example="2022-01-01 10:32:20"),
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
    public function getStudentBehaviourCategories(Request $request)
    {
        try {
            
            $data = $this->institutionService->getStudentBehaviourCategories($request);
            return $this->sendSuccessResponse("Student Behaviour Categories List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Student Behaviour Categories List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
        
            return $this->sendErrorResponse('Student Behaviour Categories List Not Found');
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v4/behaviours/categories/staff",
     *     summary="Get list of Staff behaviour categories",
     *     description="Returns a list of Staff behaviour categories",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
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
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Repeated Tardiness"),
     *                     @OA\Property(property="order", type="integer", example=1),
     *                     @OA\Property(property="visible", type="integer", example=1),
     *                     @OA\Property(property="editable", type="integer", example=1),
     *                     @OA\Property(property="default", type="integer", example=0),
     *                     @OA\Property(property="international_code", type="integer", example=""),
     *                     @OA\Property(property="national_code", type="integer", example=""),
     *                     @OA\Property(property="modified_user_id", type="integer", example=1),
     *                     @OA\Property(property="modified", type="date", example="2022-01-01 10:32:20"),
     *                     @OA\Property(property="created_user_id", type="integer", example=1),
     *                     @OA\Property(property="created", type="date", example="2022-01-01 10:32:20"),
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
    public function getStaffBehaviourCategories(Request $request)
    {
        try {
            
            $data = $this->institutionService->getStaffBehaviourCategories($request);
            return $this->sendSuccessResponse("Staff Behaviour Categories List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Staff Behaviour Categories List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
        
            return $this->sendErrorResponse('Staff Behaviour Categories List Not Found');
        }
    }//POCOR-8711

    /**
     * @OA\Get(
     *     path="/api/v4/institutions/{institutionId}/students/{studentId}/behaviours",
     *     summary="Get list of student behaviour",
     *     description="Returns a list of student behaviour by institution id",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="institutionId",
     *         in="path",
     *         required=true,
     *         description="Institution Id",
     *         @OA\Schema(type="integer", example=6)
     *     ),
     *     @OA\Parameter(
     *         name="studentId",
     *         in="path",
     *         required=true,
     *         description="Student Id",
     *         @OA\Schema(type="integer", example=6)
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
     *         @OA\Schema(type="integer", example=2)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Limit ",
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
     *                 @OA\Property(property="id", type="integer", example=525),
     *                 @OA\Property(property="description", type="integer", example="Copying of fellow learner's homework . Student has copied a fellow learner's homework"),
     *                 @OA\Property(property="action", type="string", example="Disciplinary actions have been taken"),
     *                 @OA\Property(property="date_of_behaviour", type="string", example="2018-06-01"),
     *                 @OA\Property(property="time_of_behaviour", type="string", example="07:52:00"),
     *                 @OA\Property(property="academic_period_id", type="integer", example=32),
     *                 @OA\Property(property="student_id", type="integer", example=611),
     *                 @OA\Property(property="institution_id", type="integer", example=6),
     *                 @OA\Property(property="status_id", type="integer", example=1),
     *                 @OA\Property(property="student_behaviour_category_id", type="integer", example=1),
     *                 @OA\Property(property="assignee_id", type="integer", example=6),
     *                 @OA\Property(property="modified_user_id", type="integer", example=1),
     *                 @OA\Property(property="modified", type="date", example="2022-01-01 10:32:20"),
     *                 @OA\Property(property="created_user_id", type="integer", example=1),
     *                 @OA\Property(property="created", type="date", example="2022-01-01 10:32:20"),
     *                 @OA\Property(property="student_behaviour_classification_id", type="integer", example=null),
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
    public function getInstitutionStudentBehaviour(Request $request, int $institutionId, $studentId)
    {
        try {
            $params = $request->all();
            $data = $this->institutionService->getInstitutionStudentBehaviour($params, $institutionId, $studentId);

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



    /**
     * @OA\Post(
     *     path="/api/v4/institutions/students/assessment-item-results",
     *     summary="Add a assessment item result for a particular student.",
     *     description="Add a assessment item result for a particular student.",
     *     tags={"Institutions"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="JSON Payload",
     *         @OA\JsonContent(
     *             required={
     *                 "academic_period_id",
     *                 "institution_id",
     *                 "institution_classes_id",
     *                 "education_grade_id",
     *                 "student_id",
     *                 "assessment_id",
     *                 "education_subject_id",
     *                 "assessment_period_id",
     *                 "assessment_grading_option_id"
     *              },
     *             @OA\Property(property="academic_period_id", type="integer", example=32), 
     *             @OA\Property(property="institution_id", type="integer", example=6),
     *             @OA\Property(property="institution_classes_id", type="integer", example=568),
     *             @OA\Property(property="education_grade_id", type="string", example=189),
     *             @OA\Property(property="assessment_id", type="integer", example=205),
     *             @OA\Property(property="assessment_period_id", type="integer", example=1226),
     *             @OA\Property(property="education_subject_id", type="integer", example=163),
     *             @OA\Property(property="marks", type="integer", example=25),
     *             @OA\Property(property="student_id", type="integer", example=1131),
     *             @OA\Property(property="assessment_grading_option_id", type="integer", example=20)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="array",
     *                  @OA\Items()
     *              )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
    public function addStudentAssessmentItemResult(AssessmentItemResultRequest $request)
    {
        try {
            //For POCOR-7772 Start
            $checkPermission = checkPermission(['Institutions', 'Results', 'edit'], ['institution_id' => $request['institution_id']]);

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



    /**
     * @OA\Post(
     *     path="/api/v4/institutions/students/behaviours",
     *     summary="Add student behaviours.",
     *     description="Add student behaviours.",
     *     tags={"Institutions"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="JSON Payload",
     *         @OA\JsonContent(
     *             required={
     *                 "description",
     *                 "action",
     *                 "date_of_behaviour",
     *                 "student_id",
     *                 "institution_id",
     *                 "student_behaviour_category_id"
     *              },
     *             @OA\Property(property="academic_period_id", type="integer", example=32), 
     *             @OA\Property(property="institution_id", type="integer", example=6),
     *             @OA\Property(property="student_id", type="integer", example=1131),
     *             @OA\Property(property="description", type="integer", example="Test"),
     *             @OA\Property(property="action", type="string", example="Test action"),
     *             @OA\Property(property="date_of_behaviour", type="integer", example="2023-08-02"),
     *             @OA\Property(property="student_behaviour_category_id", type="integer", example=237),
     *             @OA\Property(property="status_id", type="integer", example=128),
     *             @OA\Property(property="assignee_id", type="integer", example=25)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="array",
     *                  @OA\Items()
     *              )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
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
                return $this->sendSuccessResponse("Student Behaviour is added/updated successfully..");
            } elseif($data == 2) {
                return $this->sendErrorResponse("Invalid academic period.");
            } elseif($data == 3) {
                return $this->sendErrorResponse("Invalid institution.");
            } elseif($data == 4) {
                return $this->sendErrorResponse("Invalid student.");
            } elseif($data == 5) {
                return $this->sendErrorResponse("Invalid student behaviour category.");
            } else {
                return $this->sendErrorResponse("The update of student behaviour could not be completed successfully.");
            }
            
        } catch (\Exception $e) {
            Log::error(
                'The update of student behaviour could not be completed successfully.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('The update of student behaviour could not be completed successfully.');
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v4/institutions/{institutionId}/institution-classes/{institutionClassId}/education-grades/{educationGradeId}/students",
     *     summary="Get list of institution class students",
     *     description="Returns a list of institution class students by institution id and grade id and class id",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="institutionId",
     *         in="path",
     *         required=true,
     *         description="Institution Id",
     *         @OA\Schema(type="integer", example=6)
     *     ),
     *     @OA\Parameter(
     *         name="institutionClassId",
     *         in="path",
     *         required=true,
     *         description="Education Grade Id",
     *         @OA\Schema(type="integer", example=525)
     *     ),
     *     @OA\Parameter(
     *         name="educationGradeId",
     *         in="path",
     *         required=true,
     *         description="Education Grade Id",
     *         @OA\Schema(type="integer", example=60)
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
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
     *                 @OA\Property(property="institution_class_id", type="integer", example=525),
     *                 @OA\Property(property="institution_class_name", type="integer", example="Primary"),
     *                 @OA\Property(property="institution_id", type="integer", example=6),
     *                 @OA\Property(property="student_id", type="array",
     *                     @OA\Items(type="integer", example=855)
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
    public function getInstitutionClassEducationGradeStudents(Request $request, int $institutionId, int $institutionClassId, int $educationGradeId)
    {
        try {
            $params = $request->all();
            $data = $this->institutionService->getInstitutionClassEducationGradeStudents($params, $institutionId, $institutionClassId, $educationGradeId);
            
            return $this->sendSuccessResponse("Students List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to get Students List.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get Students List.');
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v4/institutions/{institutionId}/education-grades/{educationGradeId}/institution-subjects/students",
     *     summary="Get list of institution education grade students",
     *     description="Returns a list of institution education grade students by institution id and grade id",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="institutionId",
     *         in="path",
     *         required=true,
     *         description="Institution Id",
     *         @OA\Schema(type="integer", example=6)
     *     ),
     *     @OA\Parameter(
     *         name="educationGradeId",
     *         in="path",
     *         required=true,
     *         description="Education Grade Id",
     *         @OA\Schema(type="integer", example=6)
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
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
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="institution_subject_id", type="integer", example=1),
     *                     @OA\Property(property="institution_subject_name", type="string", example="English"),
     *                     @OA\Property(property="education_subject_code", type="string", example="ENG"),
     *                     @OA\Property(property="education_subject_name", type="date", example="ENG"),
     *                     @OA\Property(property="institution_id", type="integer", example=6),
     *                     @OA\Property(property="student_id", type="array",
     *                         @OA\Items(type="integer", example=855)
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
    public function getInstitutionEducationSubjectStudents(Request $request, int $institutionId, int $educationGradeId)
    {
        try {
            $params = $request->all();
            $data = $this->institutionService->getInstitutionEducationSubjectStudents($params, $institutionId, $educationGradeId);
            
            return $this->sendSuccessResponse("Students List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to get Students List.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get Students List.');
        }
    }



    /**
     * @OA\Delete(
     *     path="/api/v4/institutions/{institutionId}/students/{studentId}/behaviours/{behaviourId}",
     *     summary="Delete student behaviours for a particular student.",
     *     description="Delete student behaviours for a particular student.",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="institutionId",
     *         in="path",
     *         required=true,
     *         description="Institution Id",
     *         @OA\Schema(type="integer", example="6")
     *     ),
     *     @OA\Parameter(
     *         name="studentId",
     *         in="path",
     *         required=true,
     *         description="Student Id",
     *         @OA\Schema(type="integer", example="8815")
     *     ),
     *     @OA\Parameter(
     *         name="behaviourId",
     *         in="path",
     *         required=true,
     *         description="Behaviour Id",
     *         @OA\Schema(type="integer", example="1")
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
    /**
     * @OA\Get(
     *      path="/api/v4/security-role-functions",
     *      summary="Get a list of security role functions",
     *      description="Returns a list of security role functions",
     *      tags={"Users"},
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
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data", type="array",
     *                      @OA\Items(
     *                          type="object",
     *                          @OA\Property(property="_view", type="integer", example=0),
     *                          @OA\Property(property="_edit", type="integer", example=0),
     *                          @OA\Property(property="_add", type="integer", example=0),
     *                          @OA\Property(property="_delete", type="integer", example=0),
     *                          @OA\Property(property="_execute", type="integer", example=0),
     *                          @OA\Property(property="security_role_id", type="integer", example=2),
     *                          @OA\Property(property="security_function_id", type="integer", example=255),
     *                          @OA\Property(property="modified_user_id", type="integer", example=1),
     *                          @OA\Property(property="modified", type="date", example="2022-01-01 10:32:20"),
     *                          @OA\Property(property="created_user_id", type="integer", example=1),
     *                          @OA\Property(property="created", type="date", example="2022-01-01 10:32:20"),
     *                      )
     *                  )
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Unsuccessful.",
     *      )
     * )
     */
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

    /**
     * @OA\Get(
     *      path="/api/v4/security-group-users",
     *      summary="Get a list of security group users",
     *      description="Returns a list of security group users",
     *      tags={"Users"},
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
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data", type="array",
     *                      @OA\Items(
     *                          type="object",
     *                          @OA\Property(property="id", type="integer", example=13),
     *                          @OA\Property(property="security_group_id", type="integer", example=11),
     *                          @OA\Property(property="security_user_id", type="integer", example=669),
     *                          @OA\Property(property="security_role_id", type="integer", example=2),
     *                          @OA\Property(property="created_user_id", type="integer", example=1),
     *                          @OA\Property(property="created", type="date", example="2022-01-01 10:32:20"),
     *                      )
     *                  )
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Unsuccessful.",
     *      )
     * )
     */
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

    /**
     * @OA\Get(
     *      path="/api/v4/institutions/students/meals",
     *      summary="Get a list of student meals",
     *      description="Returns a list of student meals",
     *      tags={"Institutions"},
     *      @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
     *     ),
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
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data", type="array",
     *                      @OA\Items(
     *                          type="object",
     *                          @OA\Property(property="institution_id", type="string", example="6"),
     *                          @OA\Property(property="institution_class_id", type="integer", example=739),
     *                          @OA\Property(property="academic_period_id", type="integer", example=1),
     *                          @OA\Property(property="date", type="date", example="2022-01-01"),
     *                          @OA\Property(property="student_id", type="integer", example=14665),
     *                          @OA\Property(property="meal_programmes_id", type="integer", example=1),
     *                          @OA\Property(property="meal_received_id", type="integer", example=1),
     *                          @OA\Property(property="meal_benefit_id", type="integer", example=2),
     *                      )
     *                  )
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Unsuccessful.",
     *      )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/v4/institutions/{institutionId}/students/meals",
     *     summary="Get list of institution student meals",
     *     description="Returns a list of institution student meals by institution id",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="institutionId",
     *         in="path",
     *         required=true,
     *         description="Institution Id",
     *         @OA\Schema(type="integer", example=6)
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
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
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="institution_id", type="string", example="6"),
     *                     @OA\Property(property="institution_class_id", type="integer", example=739),
     *                     @OA\Property(property="academic_period_id", type="integer", example=1),
     *                     @OA\Property(property="date", type="date", example="2022-01-01"),
     *                     @OA\Property(property="student_id", type="integer", example=14665),
     *                     @OA\Property(property="meal_programmes_id", type="integer", example=1),
     *                     @OA\Property(property="meal_received_id", type="integer", example=1),
     *                     @OA\Property(property="meal_benefit_id", type="integer", example=2),
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
    public function getStudentsMealsByInstitutionId(Request $request, int $institutionId)
    {
        try {
            $params = $request->all();
            $data = $this->institutionService->getStudentsMealsByInstitutionId($params, $institutionId);

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

    /**
     * @OA\Get(
     *     path="/api/v4/institutions/students/{studentID}/statuses",
     *     summary="Get list of institution student statuses by student id",
     *     description="Returns a list of student statuses",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="studentID",
     *         in="path",
     *         required=true,
     *         description="Student Id",
     *         @OA\Schema(type="integer", example=5721)
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
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string", example="6c6c880e-3e2f-4989-a807-61cf8a4ef82d"),
     *                     @OA\Property(property="security_user_id", type="integer", example=7039),
     *                     @OA\Property(property="student_status_id", type="integer", example=1),
     *                     @OA\Property(property="created_user_id", type="integer", example=1),
     *                     @OA\Property(property="created", type="date", example="2022-01-01 10:32:20")
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
    public function getInstitutionStudentStatusByStudentId(int $studentId, Request $request)
    {
        try {
            $params = $request->all();
            $data = $this->institutionService->getInstitutionStudentStatusByStudentId($studentId, $params);

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

    
    /**
     * @OA\Post(
     *     path="/api/v4/institutions/students",
     *     summary="Add student data record in institution.",
     *     description="Add student data record in institution.",
     *     tags={"Institutions"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Json Payload",
     *         @OA\JsonContent(
     *              required={
     *                 "academic_period_id",
     *                 "education_grade_id",
     *                 "student_id",
     *                 "student_status_id",
     *                 "start_date",
     *                 "start_year",
     *                 "end_date",
     *                 "end_year",
     *                 "institution_id",
     *              },
     *              @OA\Property(property="academic_period_id", type="integer", example=32),
     *              @OA\Property(property="education_grade_id", type="integer", example=189),
     *              @OA\Property(property="student_id", type="integer", example=8815),
     *              @OA\Property(property="student_status_id", type="string", example="1"),
     *              @OA\Property(property="start_date", type="integer", example="2024-05-03"),
     *              @OA\Property(property="start_year", type="integer", example="2024"),
     *              @OA\Property(property="end_date", type="integer", example="2024-12-31"),
     *              @OA\Property(property="end_year", type="integer", example="2024"),
     *              @OA\Property(property="institution_id", type="integer", example="6"),
     *              @OA\Property(property="previous_institution_student_id", type="integer", example="8814"),
     *          )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="array",
     *                  @OA\Items()
     *              )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/v4/institutions/staff/payslips",
     *     summary="Add institution staff payslips",
     *     description="Add institution staff payslips.",
     *     tags={"Institutions"},
     *      @OA\RequestBody(
     *          request="FilePayload",
     *          required=true,
     *          description="File payload",
     *          @OA\MediaType(
     *              mediaType="multipart/form-data",
     *              @OA\Schema(
     *                  @OA\Property(
     *                      property="name",
     *                      description="Name of the payload",
     *                      type="string"
     *                  ),
     *                  @OA\Property(
     *                      property="description",
     *                      description="Description of the payload",
     *                      type="string"
     *                  ),
     *                  @OA\Property(
     *                      property="staff_id",
     *                      description="ID of the staff",
     *                      type="integer"
     *                  ),
     *                  @OA\Property(
     *                      property="file_content",
     *                      description="File to upload",
     *                      type="string",
     *                      format="binary"
     *                  )
     *              )
     *          )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="array",
     *                  @OA\Items()
     *              )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/v4/institutions/students/meal-benefits",
     *     summary="Add student meal benefits",
     *     description="Add student meal benefits",
     *     tags={"Institutions"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Json Payload",
     *         @OA\JsonContent(
     *              required={
     *                 "student_id",
     *                 "academic_period_id",
     *                 "institution_id",
     *                 "institution_class_id"
     *              },
     *              @OA\Property(property="student_id", type="integer", example="1235"),
     *              @OA\Property(property="academic_period_id", type="integer", example="32"),
     *              @OA\Property(property="institution_class_id", type="integer", example="571"),
     *              @OA\Property(property="institution_id", type="integer", example=6),
     *              @OA\Property(property="meal_programmes_id", type="integer", example=3),
     *              @OA\Property(property="date", type="string", example="2022-03-01"),
     *              @OA\Property(property="meal_benefit_id", type="integer", example=1),
     *              @OA\Property(property="meal_received_id", type="integer", example=1),
     *              @OA\Property(property="paid", type="integer", example=1),
     *              @OA\Property(property="comment", type="string", example="comment"),
     *          )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *              @OA\Property(property="data", type="array",
     *                  @OA\Items()
     *              )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/v4/institutions/meals/distributions",
     *     summary="Add institution meal distribution",
     *     description="Add institution meal distribution",
     *     tags={"Institutions"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Json Payload",
     *         @OA\JsonContent(
     *              required={
     *                 "meal_programmes_id",
     *                 "academic_period_id",
     *                 "date_received",
     *                 "quantity_received",
     *                 "delivery_status_id",
     *              },
     *              @OA\Property(property="academic_period_id", type="integer", example="32"),
     *              @OA\Property(property="institution_id", type="integer", example=6),
     *              @OA\Property(property="meal_programmes_id", type="integer", example=3),
     *              @OA\Property(property="date_received", type="string", example="2022-03-01"),
     *              @OA\Property(property="quantity_received", type="integer", example=1),
     *              @OA\Property(property="delivery_status_id", type="integer", example=1),
     *              @OA\Property(property="meal_rating_id", type="integer", example=1),
     *              @OA\Property(property="comment", type="string", example="comment"),
     *          )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *              @OA\Property(property="data", type="array",
     *                  @OA\Items()
     *              )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/v4/institutions",
     *     summary="Add institution",
     *     description="Add institution",
     *     tags={"Institutions"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Json Payload",
     *         @OA\JsonContent(
     *              required={
     *                 "name",
     *                 "code",
     *                 "address",
     *                 "date_opened",
     *                 "year_opened",
     *                 "shift_type",
     *                 "area_id",
     *                 "area_administrative_id",
     *                 "institution_locality_id",
     *                 "institution_type_id",
     *                 "institution_ownership_id",
     *                 "institution_status_id",
     *                 "institution_sector_id",
     *                 "institution_provider_id",
     *                 "institution_gender_id",
     *                 "logo_content"
     *              },
     *              @OA\Property(property="name", type="string", example="test institution"),
     *              @OA\Property(property="alternative_name", type="string", example="alternate name"),
     *              @OA\Property(property="code", type="string", example="avl"),
     *              @OA\Property(property="address", type="string", example=""),
     *              @OA\Property(property="postal_code", type="integer", example=2661),
     *              @OA\Property(property="date_opened", type="string", example="2022-03-01"),
     *              @OA\Property(property="year_opened", type="string", example="2022-03-01"),
     *              @OA\Property(property="shift_type", type="integer", example=1),
     *              @OA\Property(property="classification", type="integer", example=1),
     *              @OA\Property(property="area_id", type="integer", example=1),
     *              @OA\Property(property="area_administrative_id", type="integer", example="comment"),
     *              @OA\Property(property="institution_locality_id", type="integer", example="comment"),
     *              @OA\Property(property="institution_type_id", type="integer", example=2),
     *              @OA\Property(property="institution_ownership_id", type="integer", example=2),
     *              @OA\Property(property="institution_status_id", type="integer", example=1),
     *              @OA\Property(property="institution_sector_id", type="integer", example=2),
     *              @OA\Property(property="institution_provider_id", type="integer", example=3),
     *              @OA\Property(property="institution_gender_id", type="integer", example=1),
     *              @OA\Property(property="logo_content", type="string", example="comment"),
     *              @OA\Property(property="contact_person", type="string", example="contact person"),
     *              @OA\Property(property="telephone", type="string", example="telephone"),
     *              @OA\Property(property="fax", type="string", example="fax"),
     *              @OA\Property(property="email", type="string", example="test@test.com"),
     *              @OA\Property(property="website", type="string", example="www.example.com"),
     *              @OA\Property(property="longitude", type="string", example="78.12"),
     *              @OA\Property(property="latitude", type="string", example="22.12"),
     *          )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *              @OA\Property(property="data", type="array",
     *                  @OA\Items()
     *              )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/v4/institutions/{institutionId}/classes/{classId}",
     *     summary="Update institution classes by class id",
     *     description="Update institution classes by class id",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="institutionId",
     *         in="path",
     *         required=true,
     *         description="Institution Id",
     *         @OA\Schema(type="integer", example=6)
     *     ),
     *     @OA\Parameter(
     *         name="classId",
     *         in="path",
     *         required=true,
     *         description="Class Id",
     *         @OA\Schema(type="integer", example=571)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Json Payload",
     *         @OA\JsonContent(
     *              required={
     *                  "name",
     *                  "institution_shift_id",
     *                  "academic_period_id",
     *                  "class_students",
     *                  "capacity",
     *                  "classes_secondary_staff"
     *              },
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  example="Primary 1-D"
     *              ),
     *              @OA\Property(
     *                  property="staff_id",
     *                  type="integer",
     *                  nullable=true,
     *                  example=null
     *              ),
     *              @OA\Property(
     *                  property="institution_shift_id",
     *                  type="integer",
     *                  example=279
     *              ),
     *              @OA\Property(
     *                  property="institution_unit_id",
     *                  type="integer",
     *                  nullable=true,
     *                  example=null
     *              ),
     *              @OA\Property(
     *                  property="institution_course_id",
     *                  type="integer",
     *                  nullable=true,
     *                  example=null
     *              ),
     *              @OA\Property(
     *                  property="academic_period_id",
     *                  type="integer",
     *                  example=33
     *              ),
     *              @OA\Property(
     *                  property="class_students",
     *                  type="array",
     *                  @OA\Items(
     *                      @OA\Property(
     *                          property="student_id",
     *                          type="integer",
     *                          example=1311
     *                      ),
     *                      @OA\Property(
     *                          property="education_grade_id",
     *                          type="integer",
     *                          example=207
     *                      )
     *                  )
     *              ),
     *              @OA\Property(
     *                  property="capacity",
     *                  type="integer",
     *                  example=201
     *              ),
     *              @OA\Property(
     *                  property="classes_secondary_staff",
     *                  type="array",
     *                      @OA\Items(
     *                          type="integer",
     *                          example=578
     *                      )
     *              )
     *          )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *              @OA\Property(property="data", type="array",
     *                  @OA\Items()
     *              )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/v4/institutions/{institutionId}/subject/{subjectId}",
     *     summary="Update institution classes by class id",
     *     description="Update institution classes by class id",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="institutionId",
     *         in="path",
     *         required=true,
     *         description="Institution Id",
     *         @OA\Schema(type="integer", example=8)
     *     ),
     *     @OA\Parameter(
     *         name="subjectId",
     *         in="path",
     *         required=true,
     *         description="Subject Id",
     *         @OA\Schema(type="integer", example=5856)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Json Payload",
     *         @OA\JsonContent(
     *              required={
     *                  "name",
     *                  "subject_staff",
     *                  "academic_period_id"
     *         },
     *          @OA\Property(
     *              property="name",
     *              type="string",
     *              example="Social"
     *          ),
     *          @OA\Property(
     *              property="academic_period_id",
     *              type="integer",
     *              example=33
     *          ),
     *          @OA\Property(
     *              property="subject_students",
     *              type="array",
     *              @OA\Items(
     *                  @OA\Property(
     *                      property="student_id",
     *                      type="integer",
     *                      example=1311
     *                  ),
     *                  @OA\Property(
     *                      property="institution_class_id",
     *                      type="integer",
     *                      example=207
     *                  )
     *              )
     *          ),
     *          @OA\Property(
     *              property="subject_staff",
     *              type="array",
     *              @OA\Items(
     *                  type="integer",
     *                  example=578
     *              )
     *          ),
     *          @OA\Property(
     *              property="classes",
     *              type="array",
     *              @OA\Items(
     *                  type="integer",
     *                  example=596
     *              )
     *          ),
     *          @OA\Property(
     *              property="rooms",
     *              type="array",
     *              @OA\Items(
     *                  type="integer",
     *                  example=12
     *              )
     *          )
     *        )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *              @OA\Property(property="data", type="array",
     *                  @OA\Items()
     *              )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/v4/institutions/classes/{classId}/grades",
     *     summary="Get list of institution class grades",
     *     description="Returns a list of grades available in institution class by class id",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="classId",
     *         in="path",
     *         required=true,
     *         description="Class Id",
     *         @OA\Schema(type="integer", example=572)
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
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string", example="9d00e5f3-7a6f-44d6-ae8c-be0338243006"),
     *                     @OA\Property(property="institution_class_id", type="integer", example=312),
     *                     @OA\Property(property="education_grade_id", type="integer", example=572),
     *                     @OA\Property(property="modified_user_id", type="integer", example=1),
     *                     @OA\Property(property="modified", type="date", example="2022-01-01 10:32:20"),
     *                     @OA\Property(property="created_user_id", type="integer", example=1),
     *                     @OA\Property(property="created", type="date", example="2022-01-01 10:32:20"),
     *                     @OA\Property(property="education_grades",
     *                          type="object",
     *                              @OA\Property(property="id", type="integer", example=4554),
     *                              @OA\Property(property="code", type="string", example="Primary 2"),
     *                              @OA\Property(property="name", type="string", example="Primary 2"),
     *                              @OA\Property(property="admission_age", type="integer", example=8),
     *                              @OA\Property(property="order", type="integer", example=1),
     *                              @OA\Property(property="visible", type="integer", example=1),
     *                              @OA\Property(property="education_stage_id", type="integer", example=2),
     *                              @OA\Property(property="education_programme_id", type="integer", example=57),
     *                              @OA\Property(property="modified_user_id", type="integer", example=1),
     *                              @OA\Property(property="modified", type="date", example="2022-01-01 10:32:20"),
     *                              @OA\Property(property="created_user_id", type="integer", example=1),
     *                              @OA\Property(property="created", type="date", example="2022-01-01 10:32:20"),
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
    public function institutionClassGrade($id, Request $request)
    {
        //For POCOR-7854 Starts...
        $instituionClassGrades = InstitutionClassGrades::select('institution_class_grades.*')
            ->join('education_grades', 'education_grades.id', '=', 'institution_class_grades.education_grade_id')
            ->with('educationGrades')
            ->orderBy('education_grades.name', 'ASC')
            ->where('institution_class_id', $id);
        //For POCOR-7854 Ends...

        if (isset($request['limit'])) {
            $instituionClassGrades = $instituionClassGrades->paginate($request['limit']);
        }else {
            $instituionClassGrades = $instituionClassGrades->get();
        }

        return $this->sendSuccessResponse("Institution Class grades", $instituionClassGrades);
    }

    /**
     * @OA\Get(
     *     path="/api/v4/institutions/{institutionId}/academicperiods/{academicYearId}/rooms",
     *     summary="Get list of institution rooms",
     *     description="Returns a list of rooms available in institution by institution id",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="institutionId",
     *         in="path",
     *         required=true,
     *         description="Institution Id",
     *         @OA\Schema(type="integer", example=6)
     *     ),
     *     @OA\Parameter(
     *         name="academicYearId",
     *         in="path",
     *         required=true,
     *         description="Academic Period Id",
     *         @OA\Schema(type="integer", example=33)
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
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=76),
     *                     @OA\Property(property="code", type="string", example="P1002-01010101"),
     *                     @OA\Property(property="name", type="string", example="Room A1-01"),
     *                     @OA\Property(property="start_date", type="date", example="2022-01-01"),
     *                     @OA\Property(property="start_year", type="date", example="2022-01-01"),
     *                     @OA\Property(property="end_date", type="date", example="2022-01-01"),
     *                     @OA\Property(property="end_year", type="date", example="2022-01-01"),
     *                     @OA\Property(property="accessibility", type="integer", example=1),
     *                     @OA\Property(property="comment", type="string", example=""),
     *                     @OA\Property(property="room_type_id", type="integer", example=1),
     *                     @OA\Property(property="room_status_id", type="integer", example=1),
     *                     @OA\Property(property="institution_floor_id", type="integer", example=243),
     *                     @OA\Property(property="institution_id", type="integer", example=6),
     *                     @OA\Property(property="academic_period_id", type="integer", example=32),
     *                     @OA\Property(property="infrastructure_condition_id", type="integer", example=1),
     *                     @OA\Property(property="area", type="integer", example=null),
     *                     @OA\Property(property="previous_institution_room_id", type="integer", example=516),
     *                     @OA\Property(property="modified_user_id", type="integer", example=1),
     *                     @OA\Property(property="modified", type="date", example="2022-01-01 10:32:20"),
     *                     @OA\Property(property="created_user_id", type="integer", example=1),
     *                     @OA\Property(property="created", type="date", example="2022-01-01 10:32:20"),
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
    public function institutionRooms($institutionId, $academicYearId, Request $request)
    {


        $validateInstitution = $this->institutionService->validateInstitution($institutionId);

        $validateAcademicPeriod = $this->institutionService->validateAcademicPeriod($academicYearId);

        if (!$validateInstitution || !$validateAcademicPeriod) {
            return $this->sendErrorResponse('Unsuccessful-Invalid Parameters');
        }

        $rooms = InstitutionRooms::where('institution_id', $institutionId)->where('academic_period_id', $academicYearId);

        if (isset($request['limit'])) {
            $rooms = $rooms->paginate($request['limit']);
        }else {
            $rooms = $rooms->get();
        }

        return $this->sendSuccessResponse('Successful', $rooms);
    }

    /**
     * @OA\Get(
     *     path="/api/v4/institutions/classes/{classId}/subjects",
     *     summary="Get list of institution class subjects",
     *     description="Returns a list of subjects available in institution class by class id",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="classId",
     *         in="path",
     *         required=true,
     *         description="Class Id",
     *         @OA\Schema(type="integer", example=572)
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
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=76),
     *                     @OA\Property(property="status", type="integer", example=32),
     *                     @OA\Property(property="institution_class_id", type="integer", example=572),
     *                     @OA\Property(property="institution_subject_id", type="integer", example=206),
     *                     @OA\Property(property="modified_user_id", type="integer", example=1),
     *                     @OA\Property(property="modified", type="date", example="2022-01-01 10:32:20"),
     *                     @OA\Property(property="created_user_id", type="integer", example=1),
     *                     @OA\Property(property="created", type="date", example="2022-01-01 10:32:20"),
     *                     @OA\Property(property="institution_subject",
     *                          type="object",
     *                              @OA\Property(property="id", type="integer", example=4554),
     *                              @OA\Property(property="name", type="string", example="Artistic Design"),
     *                              @OA\Property(property="no_of_seats", type="string", example=""),
     *                              @OA\Property(property="total_male_students", type="integer", example=206),
     *                              @OA\Property(property="total_female_students", type="string", example="Primary 1"),
     *                              @OA\Property(property="institution_id", type="integer", example=6),
     *                              @OA\Property(property="education_grade_id", type="integer", example=190),
     *                              @OA\Property(property="education_subject_id", type="integer", example=94),
     *                              @OA\Property(property="academic_period_id", type="integer", example=32),
     *                              @OA\Property(property="modified_user_id", type="integer", example=1),
     *                              @OA\Property(property="modified", type="date", example="2022-01-01 10:32:20"),
     *                              @OA\Property(property="created_user_id", type="integer", example=1),
     *                              @OA\Property(property="created", type="date", example="2022-01-01 10:32:20"),
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
    public function institutionClassSubjects($institutionClassId, Request $request)
    {

        $validateClass = $this->institutionService->validateClass($institutionClassId);

        if (!$validateClass) {
            return $this->sendErrorResponse('Unsuccessful-Invalid Parameters');
        }

        $subjects = InstitutionClassSubjects::with('institutionSubject')->where('institution_class_id', $institutionClassId);

        if (isset($request['limit'])) {
            $subjects = $subjects->paginate($request['limit']);
        }else {
            $subjects = $subjects->get();
        }

        return $this->sendSuccessResponse('Successful', $subjects);
    }

    public function shifts($institutionId, $academicPeriodId)
    {
        try {

            $validateInstitution = $this->institutionService->validateInstitution($institutionId);

            $validateAcademicPeriod = $this->institutionService->validateAcademicPeriod($academicPeriodId);

            if (!$validateInstitution || !$validateAcademicPeriod) {
                return $this->sendErrorResponse('Unsuccessful-Invalid Parameters');
            }

            $data = $this->institutionService->shifts($institutionId, $academicPeriodId);

            return $this->sendSuccessResponse("Successful", $data);

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Unsuccessful', $e->getMessage());
        }
    }

    public function staffs($institutionId)
    {
        try {

            $validateInstitution = $this->institutionService->validateInstitution($institutionId);

            if (!$validateInstitution) {
                return $this->sendErrorResponse('Unsuccessful-Invalid Parameters');
            }
            $data = $this->institutionService->staffs($institutionId);

            return $this->sendSuccessResponse("Successful", $data);

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Unsuccessful', $e->getMessage());
        }
    }

    public function units(Request $request)
    {
        try {
            $params = $request->all();
            $data = $this->institutionService->units($params);

            return $this->sendSuccessResponse("Successful", $data);

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Unsuccessful', $e->getMessage());
        }
    }

    public function courses(Request $request)
    {
        try {
            $params = $request->all();
            $data = $this->institutionService->courses($params);

            return $this->sendSuccessResponse("Successful", $data);

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Unsuccessful', $e->getMessage());
        }
    }

    public function rooms($institutionId, Request $request)
    {
        try {

            $params = $request->all();
            $validateInstitution = $this->institutionService->validateInstitution($institutionId);

            $academicPeriodId = $params['academic_period_id'];
            $validateAcademicPeriod = $this->institutionService->validateAcademicPeriod($academicPeriodId);

            if (!$validateInstitution || !$validateAcademicPeriod) {
                return $this->sendErrorResponse('Unsuccessful-Invalid Parameters');
            }

            $data = $this->institutionService->rooms($institutionId, $params);

            return $this->sendSuccessResponse("Successful", $data);

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Unsuccessful', $e->getMessage());
        }
    }

    public function subjectClasses($institutionId, $educationGradeId, $institutionSubjectId)
    {
        try {

            $validateInstitution = $this->institutionService->validateInstitution($institutionId);

            $validateEducationGrade = $this->institutionService->validateEducationGrade($educationGradeId);

            if (!$validateInstitution || !$validateEducationGrade) {
                return $this->sendErrorResponse('Unsuccessful-Invalid Parameters');
            }

            $data = $this->institutionService->subjectClasses($institutionId, $educationGradeId, $institutionSubjectId);

            return $this->sendSuccessResponse("Successful", $data);

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Unsuccessful', $e->getMessage());
        }
    }

    public function unassignedStudentsInClass($institutionId, $classId)
    {
        try {

            $validateInstitution = $this->institutionService->validateInstitution($institutionId);

            $validateClass = $this->institutionService->validateClass($classId);

            if (!$validateInstitution || !$validateClass) {
                return $this->sendErrorResponse('Unsuccessful-Invalid Parameters');
            }

            $data = $this->institutionService->unassignedStudentsInClass($institutionId, $classId);

            return $this->sendSuccessResponse("Successful", $data);

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Unsuccessful', $e->getMessage());
        }
    }

    public function unassignedStudentsInSubject($institutionId, $subjectId)
    {
        try {

            $validateInstitution = $this->institutionService->validateInstitution($institutionId);

            $validateClass = $this->institutionService->validateSubject($subjectId);

            if (!$validateInstitution || !$validateClass) {
                return $this->sendErrorResponse('Unsuccessful-Invalid Parameters');
            }

            $data = $this->institutionService->unassignedStudentsInSubject($institutionId, $subjectId);

            return $this->sendSuccessResponse("Successful", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Unsuccessful', $e->getMessage());
        }
    }


    //For POCOR-8197 Starts...

    /**
     * @OA\Get(
     *     path="/api/v4/institutions/{institutionId}/grade-list",
     *     summary="Get grades list of institutions",
     *     description="Returns a list of grades by institution id",
     *     tags={"Institutions"},
     *     @OA\Parameter(
     *         name="institutionId",
     *         in="path",
     *         required=true,
     *         description="Institution Id",
     *         @OA\Schema(type="integer", example=6)
     *     ),
     *     @OA\Parameter(
     *         name="academic_period_id",
     *         in="query",
     *         required=false,
     *         description="Academic Period Id",
     *         @OA\Schema(type="integer", example=32)
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
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="academic_period_id", type="integer", example=76),
     *                     @OA\Property(property="academic_period_name", type="string", example="2024"),
     *                     @OA\Property(property="academic_period_code", type="string", example="YR2024"),
     *                     @OA\Property(property="educaiton_grade_id", type="integer", example=206),
     *                     @OA\Property(property="educaiton_grade_name", type="string", example="Primary 1"),
     *                     @OA\Property(property="institutions_id", type="integer", example=6)
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