<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WorkbenchService;
use Illuminate\Support\Facades\Log;

class WorkbenchController extends Controller
{
    protected $workbenchService;

    public function __construct(
        WorkbenchService $workbenchService
    ) {
        $this->workbenchService = $workbenchService;
    }


    /**
     * @OA\Get(
     *      path="/api/v4/notices",
     *      summary="Get a list of notices",
     *      description="Returns a list of notices",
     *      tags={"Workbench"},
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
     *                          @OA\Property(property="id", type="integer", example=1),
     *                          @OA\Property(property="message", type="string", example="notice"),
     *                          @OA\Property(property="modified_user_id", type="integer", example=1),
     *                          @OA\Property(property="modified", type="date", example="2022-01-01 10:32:20"),
     *                          @OA\Property(property="created_user_id", type="integer", example=1),
     *                          @OA\Property(property="created", type="date", example="2022-01-01 10:32:20"),
     *                      )
     *                  ),
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Unsuccessful.",
     *      )
     * )
     */
    public function getNoticesList(Request $request)
    {
        try {
            $data = $this->workbenchService->getNoticesList($request);
            
            return $this->sendSuccessResponse("Notice List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }
    /**
     * @OA\Get(
     *      path="/api/v4/staff/career/leave",
     *      summary="Get a list of staff leaves",
     *      description="Returns a list of staff leaves",
     *      tags={"Workbench"},
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
     *                          @OA\Property(property="id", type="integer", example=1),
     *                          @OA\Property(property="institution_id", type="integer", example=21),
     *                          @OA\Property(property="institution", type="string", example="test School"),
     *                          @OA\Property(property="request_title", type="string", example="Certified Sick Leave of 1528334018 - Ernesto  Flores"),
     *                          @OA\Property(property="received_date", type="string", example="May 11, 2020"),
     *                          @OA\Property(property="requester", type="string", example="admin - System  Admin"),
     *                          @OA\Property(property="staff_id", type="integer", example=8966),
     *                          @OA\Property(property="status_id", type="integer", example=126),
     *                          @OA\Property(property="status", type="string", example="Leave Cancellation Approved"),
     *                          @OA\Property(property="staff_leave_type", type="object",
     *                              @OA\Property(property="id", type="integer", example=20  ),
     *                              @OA\Property(property="name", type="string", example="Certified Sick Leave"),
     *                          ),
     *                          @OA\Property(property="user", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="username", type="string", example="admin"),
     *                              @OA\Property(property="openemis_no", type="string", example="1522271965"),
     *                              @OA\Property(property="first_name", type="string", example="firstname"),
     *                              @OA\Property(property="middle_name", type="string", example="lastname"),
     *                              @OA\Property(property="third_name", type="string", example="third_name"),
     *                              @OA\Property(property="last_name", type="string", example="last_name"),
     *                              @OA\Property(property="preferred_name", type="string", example=""),
     *                              @OA\Property(property="email", type="string", example=""),
     *                              @OA\Property(property="address", type="string", example=""),
     *                              @OA\Property(property="postal_code", type="string", example=""),
     *                              @OA\Property(property="address_area_id", type="integer", example=1),
     *                              @OA\Property(property="birthplace_area_id", type="integer", example=1),
     *                              @OA\Property(property="gender_id", type="integer", example=1),
     *                              @OA\Property(property="date_of_birth", type="string", example="2022-08-10 12:00:00"),
     *                              @OA\Property(property="date_of_death", type="string", example=null),
     *                              @OA\Property(property="nationality_id", type="integer", example=3),
     *                              @OA\Property(property="identity_type_id", type="integer", example=1),
     *                              @OA\Property(property="identity_type_name", type="string", example=null),
     *                              @OA\Property(property="identity_number", type="string", example=null),
     *                              @OA\Property(property="external_reference", type="string", example=null),
     *                              @OA\Property(property="status", type="integer", example=1),
     *                              @OA\Property(property="last_login", type="string", example=null),
     *                              @OA\Property(property="photo_name", type="string", example=null),
     *                              @OA\Property(property="photo_content", type="string", example=null),
     *                              @OA\Property(property="preferred_language", type="string", example=null),
     *                              @OA\Property(property="is_student", type="integer", example=1),
     *                              @OA\Property(property="is_staff", type="integer", example=1),
     *                              @OA\Property(property="is_guardian", type="integer", example=1),
     *                              @OA\Property(property="modified_user_id", type="integer", example=1),
     *                              @OA\Property(property="modified", type="date", example="2022-01-01 10:32:20"),
     *                              @OA\Property(property="created_user_id", type="integer", example=1),
     *                              @OA\Property(property="created", type="date", example="2022-01-01 10:32:20"),
     *                              @OA\Property(property="full_name", type="string", example="firstname lastname"),
     *                              @OA\Property(property="name_with_id", type="string", example="1522271965 - firstname lastname"),
     *                          ),
     *                          @OA\Property(property="url", type="object",
     *                              @OA\Property(property="plugin", type="string", example="Institution"),
     *                              @OA\Property(property="controller", type="string", example="Institutions"),
     *                              @OA\Property(property="action", type="string", example="StaffLeave"),
     *                              @OA\Property(property="0", type="string", example="view"),
     *                              @OA\Property(property="1", type="integer", example=1),
     *                              @OA\Property(property="user_id", type="string", example="1"),
     *                              @OA\Property(property="institution_id", type="integer", example=1),
     *                          ),
     *                          @OA\Property(property="created_user", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="username", type="string", example="admin"),
     *                              @OA\Property(property="openemis_no", type="string", example="1522271965"),
     *                              @OA\Property(property="first_name", type="string", example="firstname"),
     *                              @OA\Property(property="middle_name", type="string", example="lastname"),
     *                              @OA\Property(property="third_name", type="string", example="third_name"),
     *                              @OA\Property(property="last_name", type="string", example="last_name"),
     *                              @OA\Property(property="preferred_name", type="string", example=""),
     *                              @OA\Property(property="email", type="string", example=""),
     *                              @OA\Property(property="address", type="string", example=""),
     *                              @OA\Property(property="postal_code", type="string", example=""),
     *                              @OA\Property(property="address_area_id", type="integer", example=1),
     *                              @OA\Property(property="birthplace_area_id", type="integer", example=1),
     *                              @OA\Property(property="gender_id", type="integer", example=1),
     *                              @OA\Property(property="date_of_birth", type="string", example="2022-08-10 12:00:00"),
     *                              @OA\Property(property="date_of_death", type="string", example=null),
     *                              @OA\Property(property="nationality_id", type="integer", example=3),
     *                              @OA\Property(property="identity_type_id", type="integer", example=1),
     *                              @OA\Property(property="identity_type_name", type="string", example=null),
     *                              @OA\Property(property="identity_number", type="string", example=null),
     *                              @OA\Property(property="external_reference", type="string", example=null),
     *                              @OA\Property(property="status", type="integer", example=1),
     *                              @OA\Property(property="last_login", type="string", example=null),
     *                              @OA\Property(property="photo_name", type="string", example=null),
     *                              @OA\Property(property="photo_content", type="string", example=null),
     *                              @OA\Property(property="preferred_language", type="string", example=null),
     *                              @OA\Property(property="is_student", type="integer", example=1),
     *                              @OA\Property(property="is_staff", type="integer", example=1),
     *                              @OA\Property(property="is_guardian", type="integer", example=1),
     *                              @OA\Property(property="modified_user_id", type="integer", example=1),
     *                              @OA\Property(property="modified", type="date", example="2022-01-01 10:32:20"),
     *                              @OA\Property(property="created_user_id", type="integer", example=1),
     *                              @OA\Property(property="created", type="date", example="2022-01-01 10:32:20"),
     *                              @OA\Property(property="full_name", type="string", example="firstname lastname"),
     *                              @OA\Property(property="name_with_id", type="string", example="1522271965 - firstname lastname")
     *                          )
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
    public function getInstitutionStaffLeave(Request $request)
    {
        try {
            $data = $this->workbenchService->getInstitutionStaffLeave($request);
            
            return $this->sendSuccessResponse("Staff Leave List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v4/institutions/survey/forms",
     *      summary="Get a list of survey forms",
     *      description="Get a list of survey forms",
     *      tags={"Workbench"},
     *      @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
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
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data", type="array",
     *                      @OA\Items(
     *                          type="object",
     *                          @OA\Property(property="id", type="integer", example=1),
     *                          @OA\Property(property="institution_id", type="integer", example=21),
     *                          @OA\Property(property="institution", type="string", example="test School"),
     *                          @OA\Property(property="request_title", type="string", example="Certified Sick Leave of 1528334018 - Ernesto  Flores"),
     *                          @OA\Property(property="received_date", type="string", example="May 11, 2020"),
     *                          @OA\Property(property="requester", type="string", example="admin - System  Admin"),
     *                          @OA\Property(property="status_id", type="integer", example=126),
     *                          @OA\Property(property="status", type="string", example="Leave Cancellation Approved"),
     *                          @OA\Property(property="survey_form", type="object",
     *                              @OA\Property(property="id", type="integer", example=20),
     *                              @OA\Property(property="name", type="string", example="Preschool Form"),
     *                          ),
     *                          @OA\Property(property="academic_period", type="object",
     *                              @OA\Property(property="id", type="integer", example=20),
     *                              @OA\Property(property="name", type="string", example="2018"),
     *                          ),
     *                          @OA\Property(property="url", type="object",
     *                              @OA\Property(property="plugin", type="string", example="Institution"),
     *                              @OA\Property(property="controller", type="string", example="Institutions"),
     *                              @OA\Property(property="action", type="string", example="Surveys"),
     *                              @OA\Property(property="0", type="string", example="view"),
     *                              @OA\Property(property="1", type="integer", example=1),
     *                              @OA\Property(property="institution_id", type="integer", example=1),
     *                          ),
     *                          @OA\Property(property="created_user", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="username", type="string", example="admin"),
     *                              @OA\Property(property="openemis_no", type="string", example="1522271965"),
     *                              @OA\Property(property="first_name", type="string", example="firstname"),
     *                              @OA\Property(property="middle_name", type="string", example="lastname"),
     *                              @OA\Property(property="third_name", type="string", example="third_name"),
     *                              @OA\Property(property="last_name", type="string", example="last_name"),
     *                              @OA\Property(property="preferred_name", type="string", example=""),
     *                              @OA\Property(property="full_name", type="string", example="firstname lastname"),
     *                              @OA\Property(property="name_with_id", type="string", example="1522271965 - firstname lastname")
     *                          )
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
    public function getInstitutionStaffSurveys(Request $request)
    {
        try {
            $data = $this->workbenchService->getInstitutionStaffSurveys($request);
            
            return $this->sendSuccessResponse("Staff Survey List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v4/institutions/students/withdraw",
     *      summary="Get a list of withdrawn students requests",
     *      description="Get a list of withdrawn students requests",
     *      tags={"Workbench"},
     *      @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
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
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data", type="array",
     *                      @OA\Items(
     *                          type="object",
     *                          @OA\Property(property="id", type="integer", example=1),
     *                          @OA\Property(property="institution_id", type="integer", example=21),
     *                          @OA\Property(property="institution", type="string", example="test School"),
     *                          @OA\Property(property="request_title", type="string", example="Withdraw of student 1548403344 "),
     *                          @OA\Property(property="received_date", type="string", example="May 11, 2020"),
     *                          @OA\Property(property="requester", type="string", example="admin - System  Admin"),
     *                          @OA\Property(property="status_id", type="integer", example=126),
     *                          @OA\Property(property="status", type="string", example="Approved"),
     *                          @OA\Property(property="user", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="username", type="string", example="admin"),
     *                              @OA\Property(property="openemis_no", type="string", example="1522271965"),
     *                              @OA\Property(property="first_name", type="string", example="firstname"),
     *                              @OA\Property(property="middle_name", type="string", example="lastname"),
     *                              @OA\Property(property="third_name", type="string", example="third_name"),
     *                              @OA\Property(property="last_name", type="string", example="last_name"),
     *                              @OA\Property(property="preferred_name", type="string", example=""),
     *                              @OA\Property(property="full_name", type="string", example="firstname lastname"),
     *                              @OA\Property(property="name_with_id", type="string", example="1522271965 - firstname lastname")
     *                          ),
     *                          @OA\Property(property="url", type="object",
     *                              @OA\Property(property="plugin", type="string", example="Institution"),
     *                              @OA\Property(property="controller", type="string", example="Institutions"),
     *                              @OA\Property(property="action", type="string", example="StudentWithdraw"),
     *                              @OA\Property(property="0", type="string", example="view"),
     *                              @OA\Property(property="1", type="integer", example=1),
     *                              @OA\Property(property="institution_id", type="integer", example=1),
     *                          ),
     *                          @OA\Property(property="created_user", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="username", type="string", example="admin"),
     *                              @OA\Property(property="openemis_no", type="string", example="1522271965"),
     *                              @OA\Property(property="first_name", type="string", example="firstname"),
     *                              @OA\Property(property="middle_name", type="string", example="lastname"),
     *                              @OA\Property(property="third_name", type="string", example="third_name"),
     *                              @OA\Property(property="last_name", type="string", example="last_name"),
     *                              @OA\Property(property="preferred_name", type="string", example=""),
     *                              @OA\Property(property="full_name", type="string", example="firstname lastname"),
     *                              @OA\Property(property="name_with_id", type="string", example="1522271965 - firstname lastname")
     *                          )
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
    public function getInstitutionStudentWithdraw(Request $request)
    {
        try {
            $data = $this->workbenchService->getInstitutionStudentWithdraw($request);
            
            return $this->sendSuccessResponse("Student Withdraw List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v4/institutions/students/admission",
     *      summary="Get a list of students admission requests",
     *      description="Get a list of students admission requests",
     *      tags={"Workbench"},
     *      @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
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
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data", type="array",
     *                      @OA\Items(
     *                          type="object",
     *                          @OA\Property(property="id", type="integer", example=1),
     *                          @OA\Property(property="institution_id", type="integer", example=21),
     *                          @OA\Property(property="institution", type="string", example="test School"),
     *                          @OA\Property(property="request_title", type="string", example="Admission of student 1548403344 "),
     *                          @OA\Property(property="received_date", type="string", example="May 11, 2020"),
     *                          @OA\Property(property="requester", type="string", example="admin - System  Admin"),
     *                          @OA\Property(property="status_id", type="integer", example=126),
     *                          @OA\Property(property="status", type="string", example="Approved"),
     *                          @OA\Property(property="user", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="username", type="string", example="admin"),
     *                              @OA\Property(property="openemis_no", type="string", example="1522271965"),
     *                              @OA\Property(property="first_name", type="string", example="firstname"),
     *                              @OA\Property(property="middle_name", type="string", example="lastname"),
     *                              @OA\Property(property="third_name", type="string", example="third_name"),
     *                              @OA\Property(property="last_name", type="string", example="last_name"),
     *                              @OA\Property(property="preferred_name", type="string", example=""),
     *                              @OA\Property(property="full_name", type="string", example="firstname lastname"),
     *                              @OA\Property(property="name_with_id", type="string", example="1522271965 - firstname lastname")
     *                          ),
     *                          @OA\Property(property="url", type="object",
     *                              @OA\Property(property="plugin", type="string", example="Institution"),
     *                              @OA\Property(property="controller", type="string", example="Institutions"),
     *                              @OA\Property(property="action", type="string", example="StudentAdmission"),
     *                              @OA\Property(property="0", type="string", example="view"),
     *                              @OA\Property(property="1", type="integer", example=1),
     *                              @OA\Property(property="institution_id", type="integer", example=1),
     *                          ),
     *                          @OA\Property(property="created_user", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="username", type="string", example="admin"),
     *                              @OA\Property(property="openemis_no", type="string", example="1522271965"),
     *                              @OA\Property(property="first_name", type="string", example="firstname"),
     *                              @OA\Property(property="middle_name", type="string", example="lastname"),
     *                              @OA\Property(property="third_name", type="string", example="third_name"),
     *                              @OA\Property(property="last_name", type="string", example="last_name"),
     *                              @OA\Property(property="preferred_name", type="string", example=""),
     *                              @OA\Property(property="full_name", type="string", example="firstname lastname"),
     *                              @OA\Property(property="name_with_id", type="string", example="1522271965 - firstname lastname")
     *                          )
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
    public function getInstitutionStudentAdmission(Request $request)
    {
        try {
            $data = $this->workbenchService->getInstitutionStudentAdmission($request);
            
            return $this->sendSuccessResponse("Student Admission List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }


    /**
     * @OA\Get(
     *      path="/api/v4/institutions/students/transferout",
     *      summary="Get a list of transfer out students request",
     *      description="Get a list of transfer out students request",
     *      tags={"Workbench"},
     *      @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
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
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data", type="array",
     *                      @OA\Items(
     *                          type="object",
     *                          @OA\Property(property="id", type="integer", example=1),
     *                          @OA\Property(property="institution_id", type="integer", example=21),
     *                          @OA\Property(property="institution", type="string", example="test School"),
     *                          @OA\Property(property="previous_institution", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="name", type="string", example="Avory Primary School"),
     *                              @OA\Property(property="code", type="string", example="P1002"),
     *                              @OA\Property(property="code_name", type="string", example="P1002 - Avory Primary School"),
     *                          ),
     *                          @OA\Property(property="previous_institution_id", type="integer", example=6),
     *                          @OA\Property(property="request_title", type="string", example="Transfer of student 1522412648 - Annie  Croskey to S2007 - Jonmere Lower Secondary School "),
     *                          @OA\Property(property="received_date", type="string", example="May 11, 2020"),
     *                          @OA\Property(property="requester", type="string", example="admin - System  Admin"),
     *                          @OA\Property(property="status_id", type="integer", example=126),
     *                          @OA\Property(property="status", type="string", example="Rejected"),
     *                          @OA\Property(property="user", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="username", type="string", example="admin"),
     *                              @OA\Property(property="openemis_no", type="string", example="1522271965"),
     *                              @OA\Property(property="first_name", type="string", example="firstname"),
     *                              @OA\Property(property="middle_name", type="string", example="lastname"),
     *                              @OA\Property(property="third_name", type="string", example="third_name"),
     *                              @OA\Property(property="last_name", type="string", example="last_name"),
     *                              @OA\Property(property="preferred_name", type="string", example=""),
     *                              @OA\Property(property="full_name", type="string", example="firstname lastname"),
     *                              @OA\Property(property="name_with_id", type="string", example="1522271965 - firstname lastname")
     *                          ),
     *                          @OA\Property(property="url", type="object",
     *                              @OA\Property(property="plugin", type="string", example="Institution"),
     *                              @OA\Property(property="controller", type="string", example="Institutions"),
     *                              @OA\Property(property="action", type="string", example="StudentTransferOut"),
     *                              @OA\Property(property="0", type="string", example="view"),
     *                              @OA\Property(property="1", type="integer", example=1),
     *                              @OA\Property(property="user_id", type="string", example="1"),
     *                              @OA\Property(property="institution_id", type="integer", example=1),
     *                          ),
     *                          @OA\Property(property="created_user", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="username", type="string", example="admin"),
     *                              @OA\Property(property="openemis_no", type="string", example="1522271965"),
     *                              @OA\Property(property="first_name", type="string", example="firstname"),
     *                              @OA\Property(property="middle_name", type="string", example="lastname"),
     *                              @OA\Property(property="third_name", type="string", example="third_name"),
     *                              @OA\Property(property="last_name", type="string", example="last_name"),
     *                              @OA\Property(property="preferred_name", type="string", example=""),
     *                              @OA\Property(property="full_name", type="string", example="firstname lastname"),
     *                              @OA\Property(property="name_with_id", type="string", example="1522271965 - firstname lastname")
     *                          )
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
    public function getInstitutionStudentTransferOut(Request $request)
    {
        try {
            $data = $this->workbenchService->getInstitutionStudentTransferOut($request);
            
            return $this->sendSuccessResponse("Student Transfer Out List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v4/institutions/students/transferin",
     *      summary="Get a list of transfer in students request",
     *      description="Get a list of transfer in students request",
     *      tags={"Workbench"},
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
     *                          @OA\Property(property="id", type="integer", example=1),
     *                          @OA\Property(property="institution_id", type="integer", example=21),
     *                          @OA\Property(property="institution", type="string", example="test School"),
     *                          @OA\Property(property="previous_institution", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="name", type="string", example="Avory Primary School"),
     *                              @OA\Property(property="code", type="string", example="P1002"),
     *                              @OA\Property(property="code_name", type="string", example="P1002 - Avory Primary School"),
     *                          ),
     *                          @OA\Property(property="previous_institution_id", type="integer", example=6),
     *                          @OA\Property(property="request_title", type="string", example="Transfer of student 1522412648 - Annie  Croskey to S2007 - Jonmere Lower Secondary School "),
     *                          @OA\Property(property="received_date", type="string", example="May 11, 2020"),
     *                          @OA\Property(property="requester", type="string", example="admin - System  Admin"),
     *                          @OA\Property(property="status_id", type="integer", example=126),
     *                          @OA\Property(property="status", type="string", example="Rejected"),
     *                          @OA\Property(property="user", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="username", type="string", example="admin"),
     *                              @OA\Property(property="openemis_no", type="string", example="1522271965"),
     *                              @OA\Property(property="first_name", type="string", example="firstname"),
     *                              @OA\Property(property="middle_name", type="string", example="lastname"),
     *                              @OA\Property(property="third_name", type="string", example="third_name"),
     *                              @OA\Property(property="last_name", type="string", example="last_name"),
     *                              @OA\Property(property="preferred_name", type="string", example=""),
     *                              @OA\Property(property="full_name", type="string", example="firstname lastname"),
     *                              @OA\Property(property="name_with_id", type="string", example="1522271965 - firstname lastname")
     *                          ),
     *                          @OA\Property(property="url", type="object",
     *                              @OA\Property(property="plugin", type="string", example="Institution"),
     *                              @OA\Property(property="controller", type="string", example="Institutions"),
     *                              @OA\Property(property="action", type="string", example="StudentTransferIn"),
     *                              @OA\Property(property="0", type="string", example="view"),
     *                              @OA\Property(property="1", type="integer", example=1),
     *                              @OA\Property(property="institution_id", type="integer", example=1),
     *                          ),
     *                          @OA\Property(property="created_user", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="username", type="string", example="admin"),
     *                              @OA\Property(property="openemis_no", type="string", example="1522271965"),
     *                              @OA\Property(property="first_name", type="string", example="firstname"),
     *                              @OA\Property(property="middle_name", type="string", example="lastname"),
     *                              @OA\Property(property="third_name", type="string", example="third_name"),
     *                              @OA\Property(property="last_name", type="string", example="last_name"),
     *                              @OA\Property(property="preferred_name", type="string", example=""),
     *                              @OA\Property(property="full_name", type="string", example="firstname lastname"),
     *                              @OA\Property(property="name_with_id", type="string", example="1522271965 - firstname lastname")
     *                          )
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
    public function getInstitutionStudentTransferIn(Request $request)
    {
        try {
            $data = $this->workbenchService->getInstitutionStudentTransferIn($request);
            
            return $this->sendSuccessResponse("Student Transfer In List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v4/institutions/behaviour/students",
     *      summary="Get a list of student behaviour requests",
     *      description="Get a list of student behaviour requests",
     *      tags={"Workbench"},
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
     *                          @OA\Property(property="id", type="integer", example=1),
     *                          @OA\Property(property="institution_id", type="integer", example=21),
     *                          @OA\Property(property="institution", type="string", example="test School"),
     *                          @OA\Property(property="request_title", type="string", example="Behavour request of 1524270931 - Bastien  Danby"),
     *                          @OA\Property(property="received_date", type="string", example="May 11, 2020"),
     *                          @OA\Property(property="requester", type="string", example="admin - System  Admin"),
     *                          @OA\Property(property="status_id", type="integer", example=126),
     *                          @OA\Property(property="status", type="string", example="Open"),
     *                          @OA\Property(property="student", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="username", type="string", example="admin"),
     *                              @OA\Property(property="openemis_no", type="string", example="1522271965"),
     *                              @OA\Property(property="first_name", type="string", example="firstname"),
     *                              @OA\Property(property="middle_name", type="string", example="lastname"),
     *                              @OA\Property(property="third_name", type="string", example="third_name"),
     *                              @OA\Property(property="last_name", type="string", example="last_name"),
     *                              @OA\Property(property="preferred_name", type="string", example=""),
     *                              @OA\Property(property="full_name", type="string", example="firstname lastname"),
     *                              @OA\Property(property="name_with_id", type="string", example="1522271965 - firstname lastname")
     *                          ),
     *                          @OA\Property(property="url", type="object",
     *                              @OA\Property(property="plugin", type="string", example="Institution"),
     *                              @OA\Property(property="controller", type="string", example="Institutions"),
     *                              @OA\Property(property="action", type="string", example="StudentBehaviours"),
     *                              @OA\Property(property="0", type="string", example="view"),
     *                              @OA\Property(property="1", type="integer", example=1),
     *                              @OA\Property(property="institution_id", type="integer", example=1),
     *                          ),
     *                          @OA\Property(property="created_user", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="username", type="string", example="admin"),
     *                              @OA\Property(property="openemis_no", type="string", example="1522271965"),
     *                              @OA\Property(property="first_name", type="string", example="firstname"),
     *                              @OA\Property(property="middle_name", type="string", example="lastname"),
     *                              @OA\Property(property="third_name", type="string", example="third_name"),
     *                              @OA\Property(property="last_name", type="string", example="last_name"),
     *                              @OA\Property(property="preferred_name", type="string", example=""),
     *                              @OA\Property(property="full_name", type="string", example="firstname lastname"),
     *                              @OA\Property(property="name_with_id", type="string", example="1522271965 - firstname lastname")
     *                          )
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
    public function getInstitutionStudentBehaviour(Request $request)
    {
        try {
            $data = $this->workbenchService->getInstitutionStudentBehaviour($request);
            
            return $this->sendSuccessResponse("Student Behaviour List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }


    /**
     * @OA\Get(
     *      path="/api/v4/institutions/behaviour/staff",
     *      summary="Get a list of staff behaviour requests",
     *      description="Get a list of staff behaviour requests",
     *      tags={"Workbench"},
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
     *                          @OA\Property(property="id", type="integer", example=1),
     *                          @OA\Property(property="institution_id", type="integer", example=21),
     *                          @OA\Property(property="institution", type="string", example="test School"),
     *                          @OA\Property(property="request_title", type="string", example="Behavour request of 1524270931 - Bastien  Danby"),
     *                          @OA\Property(property="received_date", type="string", example="May 11, 2020"),
     *                          @OA\Property(property="requester", type="string", example="admin - System  Admin"),
     *                          @OA\Property(property="status_id", type="integer", example=126),
     *                          @OA\Property(property="status", type="string", example="Open"),
     *                          @OA\Property(property="staff", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="username", type="string", example="admin"),
     *                              @OA\Property(property="openemis_no", type="string", example="1522271965"),
     *                              @OA\Property(property="first_name", type="string", example="firstname"),
     *                              @OA\Property(property="middle_name", type="string", example="lastname"),
     *                              @OA\Property(property="third_name", type="string", example="third_name"),
     *                              @OA\Property(property="last_name", type="string", example="last_name"),
     *                              @OA\Property(property="preferred_name", type="string", example=""),
     *                              @OA\Property(property="full_name", type="string", example="firstname lastname"),
     *                              @OA\Property(property="name_with_id", type="string", example="1522271965 - firstname lastname")
     *                          ),
     *                          @OA\Property(property="url", type="object",
     *                              @OA\Property(property="plugin", type="string", example="Institution"),
     *                              @OA\Property(property="controller", type="string", example="Institutions"),
     *                              @OA\Property(property="action", type="string", example="StaffBehaviours"),
     *                              @OA\Property(property="0", type="string", example="view"),
     *                              @OA\Property(property="1", type="integer", example=1),
     *                              @OA\Property(property="user_id", type="string", example="1"),
     *                              @OA\Property(property="institution_id", type="integer", example=1),
     *                          ),
     *                          @OA\Property(property="created_user", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="username", type="string", example="admin"),
     *                              @OA\Property(property="openemis_no", type="string", example="1522271965"),
     *                              @OA\Property(property="first_name", type="string", example="firstname"),
     *                              @OA\Property(property="middle_name", type="string", example="lastname"),
     *                              @OA\Property(property="third_name", type="string", example="third_name"),
     *                              @OA\Property(property="last_name", type="string", example="last_name"),
     *                              @OA\Property(property="preferred_name", type="string", example=""),
     *                              @OA\Property(property="full_name", type="string", example="firstname lastname"),
     *                              @OA\Property(property="name_with_id", type="string", example="1522271965 - firstname lastname")
     *                          )
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
    public function getInstitutionStaffBehaviour(Request $request)
    {
        try {
            $data = $this->workbenchService->getInstitutionStaffBehaviour($request);
            
            return $this->sendSuccessResponse("Staff Behaviour List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v4/staff/career/appraisals",
     *      summary="Get a list of staff appraisals",
     *      description="Get a list of staff appraisals",
     *      tags={"Workbench"},
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
     *                          @OA\Property(property="id", type="integer", example=1),
     *                          @OA\Property(property="institution_id", type="integer", example=21),
     *                          @OA\Property(property="institution", type="string", example="test School"),
     *                          @OA\Property(property="request_title", type="string", example="Staff Appraisal(Peer) for 1522271965"),
     *                          @OA\Property(property="received_date", type="string", example="May 11, 2020"),
     *                          @OA\Property(property="requester", type="string", example="admin - System  Admin"),
     *                          @OA\Property(property="status_id", type="integer", example=126),
     *                          @OA\Property(property="status", type="string", example="Open"),
     *                          @OA\Property(property="user", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="username", type="string", example="admin"),
     *                              @OA\Property(property="openemis_no", type="string", example="1522271965"),
     *                              @OA\Property(property="first_name", type="string", example="firstname"),
     *                              @OA\Property(property="middle_name", type="string", example="lastname"),
     *                              @OA\Property(property="third_name", type="string", example="third_name"),
     *                              @OA\Property(property="last_name", type="string", example="last_name"),
     *                              @OA\Property(property="preferred_name", type="string", example=""),
     *                              @OA\Property(property="full_name", type="string", example="firstname lastname"),
     *                              @OA\Property(property="name_with_id", type="string", example="1522271965 - firstname lastname")
     *                          ),
     *                          @OA\Property(property="url", type="object",
     *                              @OA\Property(property="plugin", type="string", example="Institution"),
     *                              @OA\Property(property="controller", type="string", example="Institutions"),
     *                              @OA\Property(property="action", type="string", example="StaffAppraisals"),
     *                              @OA\Property(property="0", type="string", example="view"),
     *                              @OA\Property(property="1", type="integer", example=2),
     *                              @OA\Property(property="user_id", type="string", example="1"),
     *                              @OA\Property(property="institution_id", type="integer", example=1),
     *                          ),
     *                          @OA\Property(property="created_user", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="username", type="string", example="admin"),
     *                              @OA\Property(property="openemis_no", type="string", example="1522271965"),
     *                              @OA\Property(property="first_name", type="string", example="firstname"),
     *                              @OA\Property(property="middle_name", type="string", example="lastname"),
     *                              @OA\Property(property="third_name", type="string", example="third_name"),
     *                              @OA\Property(property="last_name", type="string", example="last_name"),
     *                              @OA\Property(property="preferred_name", type="string", example=""),
     *                              @OA\Property(property="full_name", type="string", example="firstname lastname"),
     *                              @OA\Property(property="name_with_id", type="string", example="1522271965 - firstname lastname")
     *                          ),
     *                          @OA\Property(property="appraisal_form", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="name", type="string", example="Staff Appraisal"),
     *                              @OA\Property(property="code", type="string", example="OESA-1"),
     *                              @OA\Property(property="code_name", type="string", example="OESA-1 - Staff Appraisal"),
     *                          ),
     *                          @OA\Property(property="appraisal_type", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="name", type="string", example="peer")
     *                          ),
     *                          @OA\Property(property="appraisal_period", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="name", type="string", example="2020 Annual Appraisal")
     *                          ),
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
    public function getStaffAppraisals(Request $request)
    {
        try {
            $data = $this->workbenchService->getStaffAppraisals($request);
            
            return $this->sendSuccessResponse("Staff Appraisals List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v4/institutions/staff/release",
     *      summary="Get a list of staff release",
     *      description="Get a list of staff release",
     *      tags={"Workbench"},
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
     *                          @OA\Property(property="id", type="integer", example=1),
     *                          @OA\Property(property="institution_id", type="integer", example=21),
     *                          @OA\Property(property="institution", type="string", example="test School"),
     *                          @OA\Property(property="new_institution", type="object",
     *                              @OA\Property(property="id", type="integer", example=12),
     *                              @OA\Property(property="name", type="string", example="Boster Lower Secondary School"),
     *                              @OA\Property(property="code", type="string", example="S2002"),
     *                              @OA\Property(property="code_name", type="string", example="S2002 - Boster Lower Secondary School"),
     *                          ),
     *                          @OA\Property(property="new_institution_id", type="integer", example=12),
     *                          @OA\Property(property="previous_institution", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="name", type="string", example="Avory Primary School"),
     *                              @OA\Property(property="code", type="string", example="P1002"),
     *                              @OA\Property(property="code_name", type="string", example="P1002 - Avory Primary School"),
     *                          ),
     *                          @OA\Property(property="previous_institution_id", type="integer", example=6),
     *                          @OA\Property(property="request_title", type="string", example=" release of S2002"),
     *                          @OA\Property(property="received_date", type="string", example="May 11, 2020"),
     *                          @OA\Property(property="requester", type="string", example="admin - System  Admin"),
     *                          @OA\Property(property="status_id", type="integer", example=126),
     *                          @OA\Property(property="status", type="string", example="Rejected"),
     *                          @OA\Property(property="user", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="username", type="string", example="admin"),
     *                              @OA\Property(property="openemis_no", type="string", example="1522271965"),
     *                              @OA\Property(property="first_name", type="string", example="firstname"),
     *                              @OA\Property(property="middle_name", type="string", example="lastname"),
     *                              @OA\Property(property="third_name", type="string", example="third_name"),
     *                              @OA\Property(property="last_name", type="string", example="last_name"),
     *                              @OA\Property(property="preferred_name", type="string", example=""),
     *                              @OA\Property(property="full_name", type="string", example="firstname lastname"),
     *                              @OA\Property(property="name_with_id", type="string", example="1522271965 - firstname lastname")
     *                          ),
     *                          @OA\Property(property="created_user", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="username", type="string", example="admin"),
     *                              @OA\Property(property="openemis_no", type="string", example="1522271965"),
     *                              @OA\Property(property="first_name", type="string", example="firstname"),
     *                              @OA\Property(property="middle_name", type="string", example="lastname"),
     *                              @OA\Property(property="third_name", type="string", example="third_name"),
     *                              @OA\Property(property="last_name", type="string", example="last_name"),
     *                              @OA\Property(property="preferred_name", type="string", example=""),
     *                              @OA\Property(property="full_name", type="string", example="firstname lastname"),
     *                              @OA\Property(property="name_with_id", type="string", example="1522271965 - firstname lastname")
     *                          )
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
    public function getStaffRelease(Request $request)
    {
        try {
            $data = $this->workbenchService->getStaffRelease($request);
            
            return $this->sendSuccessResponse("Staff Release List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v4/institutions/staff/transferout",
     *      summary="Get a list of transfer out staff request",
     *      description="Get a list of transfer out staff request",
     *      tags={"Workbench"},
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
     *                          @OA\Property(property="id", type="integer", example=1),
     *                          @OA\Property(property="institution_id", type="integer", example=21),
     *                          @OA\Property(property="institution", type="string", example="test School"),
     *                          @OA\Property(property="new_institution", type="object",
     *                              @OA\Property(property="id", type="integer", example=12),
     *                              @OA\Property(property="name", type="string", example="Boster Lower Secondary School"),
     *                              @OA\Property(property="code", type="string", example="S2002"),
     *                              @OA\Property(property="code_name", type="string", example="S2002 - Boster Lower Secondary School"),
     *                          ),
     *                          @OA\Property(property="new_institution_id", type="integer", example=12),
     *                          @OA\Property(property="previous_institution", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="name", type="string", example="Avory Primary School"),
     *                              @OA\Property(property="code", type="string", example="P1002"),
     *                              @OA\Property(property="code_name", type="string", example="P1002 - Avory Primary School"),
     *                          ),
     *                          @OA\Property(property="previous_institution_id", type="integer", example=6),
     *                          @OA\Property(property="request_title", type="string", example=" testtransfer to S2002 - Boster Lower Secondary School"),
     *                          @OA\Property(property="received_date", type="string", example="May 11, 2020"),
     *                          @OA\Property(property="requester", type="string", example="admin - System  Admin"),
     *                          @OA\Property(property="status_id", type="integer", example=126),
     *                          @OA\Property(property="status", type="string", example="Rejected"),
     *                          @OA\Property(property="user", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="username", type="string", example="admin"),
     *                              @OA\Property(property="openemis_no", type="string", example="1522271965"),
     *                              @OA\Property(property="first_name", type="string", example="firstname"),
     *                              @OA\Property(property="middle_name", type="string", example="lastname"),
     *                              @OA\Property(property="third_name", type="string", example="third_name"),
     *                              @OA\Property(property="last_name", type="string", example="last_name"),
     *                              @OA\Property(property="preferred_name", type="string", example=""),
     *                              @OA\Property(property="full_name", type="string", example="firstname lastname"),
     *                              @OA\Property(property="name_with_id", type="string", example="1522271965 - firstname lastname")
     *                          ),
     *                          @OA\Property(property="url", type="object",
     *                              @OA\Property(property="plugin", type="string", example="Institution"),
     *                              @OA\Property(property="controller", type="string", example="Institutions"),
     *                              @OA\Property(property="action", type="string", example="StaffTransferOut"),
     *                              @OA\Property(property="0", type="string", example="view"),
     *                              @OA\Property(property="1", type="integer", example=2),
     *                              @OA\Property(property="institution_id", type="integer", example=1),
     *                          ),
     *                          @OA\Property(property="created_user", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="username", type="string", example="admin"),
     *                              @OA\Property(property="openemis_no", type="string", example="1522271965"),
     *                              @OA\Property(property="first_name", type="string", example="firstname"),
     *                              @OA\Property(property="middle_name", type="string", example="lastname"),
     *                              @OA\Property(property="third_name", type="string", example="third_name"),
     *                              @OA\Property(property="last_name", type="string", example="last_name"),
     *                              @OA\Property(property="preferred_name", type="string", example=""),
     *                              @OA\Property(property="full_name", type="string", example="firstname lastname"),
     *                              @OA\Property(property="name_with_id", type="string", example="1522271965 - firstname lastname")
     *                          )
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
    public function getStaffTransferOut(Request $request)
    {
        try {
            $data = $this->workbenchService->getStaffTransferOut($request);
            
            return $this->sendSuccessResponse("Staff Transfer Out List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v4/institutions/staff/transferin",
     *      summary="Get a list of transfer in staff request",
     *      description="Get a list of transfer in staff request",
     *      tags={"Workbench"},
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
     *                          @OA\Property(property="id", type="integer", example=1),
     *                          @OA\Property(property="institution_id", type="integer", example=21),
     *                          @OA\Property(property="institution", type="string", example="test School"),
     *                          @OA\Property(property="new_institution", type="object",
     *                              @OA\Property(property="id", type="integer", example=12),
     *                              @OA\Property(property="name", type="string", example="Boster Lower Secondary School"),
     *                              @OA\Property(property="code", type="string", example="S2002"),
     *                              @OA\Property(property="code_name", type="string", example="S2002 - Boster Lower Secondary School"),
     *                          ),
     *                          @OA\Property(property="new_institution_id", type="integer", example=12),
     *                          @OA\Property(property="previous_institution", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="name", type="string", example="Avory Primary School"),
     *                              @OA\Property(property="code", type="string", example="P1002"),
     *                              @OA\Property(property="code_name", type="string", example="P1002 - Avory Primary School"),
     *                          ),
     *                          @OA\Property(property="previous_institution_id", type="integer", example=6),
     *                          @OA\Property(property="request_title", type="string", example=" testtransfer to S2002 - Boster Lower Secondary School"),
     *                          @OA\Property(property="received_date", type="string", example="May 11, 2020"),
     *                          @OA\Property(property="requester", type="string", example="admin - System  Admin"),
     *                          @OA\Property(property="status_id", type="integer", example=126),
     *                          @OA\Property(property="status", type="string", example="Rejected"),
     *                          @OA\Property(property="user", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="username", type="string", example="admin"),
     *                              @OA\Property(property="openemis_no", type="string", example="1522271965"),
     *                              @OA\Property(property="first_name", type="string", example="firstname"),
     *                              @OA\Property(property="middle_name", type="string", example="lastname"),
     *                              @OA\Property(property="third_name", type="string", example="third_name"),
     *                              @OA\Property(property="last_name", type="string", example="last_name"),
     *                              @OA\Property(property="preferred_name", type="string", example=""),
     *                              @OA\Property(property="full_name", type="string", example="firstname lastname"),
     *                              @OA\Property(property="name_with_id", type="string", example="1522271965 - firstname lastname")
     *                          ),
     *                          @OA\Property(property="url", type="object",
     *                              @OA\Property(property="plugin", type="string", example="Institution"),
     *                              @OA\Property(property="controller", type="string", example="Institutions"),
     *                              @OA\Property(property="action", type="string", example="StaffTransferIn"),
     *                              @OA\Property(property="0", type="string", example="view"),
     *                              @OA\Property(property="1", type="integer", example=2),
     *                              @OA\Property(property="institution_id", type="integer", example=1),
     *                          ),
     *                          @OA\Property(property="created_user", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="username", type="string", example="admin"),
     *                              @OA\Property(property="openemis_no", type="string", example="1522271965"),
     *                              @OA\Property(property="first_name", type="string", example="firstname"),
     *                              @OA\Property(property="middle_name", type="string", example="lastname"),
     *                              @OA\Property(property="third_name", type="string", example="third_name"),
     *                              @OA\Property(property="last_name", type="string", example="last_name"),
     *                              @OA\Property(property="preferred_name", type="string", example=""),
     *                              @OA\Property(property="full_name", type="string", example="firstname lastname"),
     *                              @OA\Property(property="name_with_id", type="string", example="1522271965 - firstname lastname")
     *                          )
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
    public function getStaffTransferIn(Request $request)
    {
        try {
            $data = $this->workbenchService->getStaffTransferIn($request);
            
            return $this->sendSuccessResponse("Staff Transfer In List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v4/institutions/staff/changeinassignment",
     *      summary="Get a list of change in assignment staff request",
     *      description="Get a list of change in assignment staff request",
     *      tags={"Workbench"},
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
     *                          @OA\Property(property="id", type="integer", example=1),
     *                          @OA\Property(property="institution_id", type="integer", example=21),
     *                          @OA\Property(property="institution", type="string", example="test School"),
     *                          @OA\Property(property="request_title", type="string", example=" testtransfer to S2002 - Boster Lower Secondary School"),
     *                          @OA\Property(property="received_date", type="string", example="May 11, 2020"),
     *                          @OA\Property(property="requester", type="string", example="admin - System  Admin"),
     *                          @OA\Property(property="status_id", type="integer", example=126),
     *                          @OA\Property(property="status", type="string", example="Rejected"),
     *                          @OA\Property(property="user", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="username", type="string", example="admin"),
     *                              @OA\Property(property="openemis_no", type="string", example="1522271965"),
     *                              @OA\Property(property="first_name", type="string", example="firstname"),
     *                              @OA\Property(property="middle_name", type="string", example="lastname"),
     *                              @OA\Property(property="third_name", type="string", example="third_name"),
     *                              @OA\Property(property="last_name", type="string", example="last_name"),
     *                              @OA\Property(property="preferred_name", type="string", example=""),
     *                              @OA\Property(property="full_name", type="string", example="firstname lastname"),
     *                              @OA\Property(property="name_with_id", type="string", example="1522271965 - firstname lastname")
     *                          ),
     *                          @OA\Property(property="url", type="object",
     *                              @OA\Property(property="plugin", type="string", example="Institution"),
     *                              @OA\Property(property="controller", type="string", example="Institutions"),
     *                              @OA\Property(property="action", type="string", example="StaffPositionProfiles"),
     *                              @OA\Property(property="0", type="string", example="view"),
     *                              @OA\Property(property="1", type="integer", example=1),
     *                              @OA\Property(property="institution_id", type="integer", example=1),
     *                          ),
     *                          @OA\Property(property="created_user", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="username", type="string", example="admin"),
     *                              @OA\Property(property="openemis_no", type="string", example="1522271965"),
     *                              @OA\Property(property="first_name", type="string", example="firstname"),
     *                              @OA\Property(property="middle_name", type="string", example="lastname"),
     *                              @OA\Property(property="third_name", type="string", example="third_name"),
     *                              @OA\Property(property="last_name", type="string", example="last_name"),
     *                              @OA\Property(property="preferred_name", type="string", example=""),
     *                              @OA\Property(property="full_name", type="string", example="firstname lastname"),
     *                              @OA\Property(property="name_with_id", type="string", example="1522271965 - firstname lastname")
     *                          )
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
    public function getChangeInAssignment(Request $request)
    {
        try {
            $data = $this->workbenchService->getChangeInAssignment($request);
            
            return $this->sendSuccessResponse("Staff Change In Assignmen List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v4/staff/training/needs",
     *      summary="Get a list of training needs",
     *      description="Get a list of training needs",
     *      tags={"Workbench"},
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
     *                          @OA\Property(property="id", type="integer", example=1),
     *                          @OA\Property(property="institution_id", type="integer", example=21),
     *                          @OA\Property(property="institution", type="string", example="test School"),
     *                          @OA\Property(property="request_title", type="string", example=" testtransfer to S2002 - Boster Lower Secondary School"),
     *                          @OA\Property(property="received_date", type="string", example="May 11, 2020"),
     *                          @OA\Property(property="requester", type="string", example="admin - System  Admin"),
     *                          @OA\Property(property="training_course", type="object",
     *                              @OA\Property(property="id", type="string", example=2),
     *                              @OA\Property(property="name", type="string", example="Special Educational Needs"),
     *                              @OA\Property(property="code", type="string", example="SEN001"),
     *                              @OA\Property(property="code_name", type="string", example="SEN001 - Special Educational Needs"),
     *                          ),
     *                          @OA\Property(property="training_course_id", type="integer", example=1),
     *                          @OA\Property(property="training_need_category", type="object",
     *                              @OA\Property(property="id", type="string", example=12),
     *                              @OA\Property(property="name", type="string", example="Personal"),
     *                          ),
     *                          @OA\Property(property="training_need_category_id", type="integer", example=14),
     *                          @OA\Property(property="type", type="string", example="dfg"),
     *                          @OA\Property(property="status_id", type="integer", example=126),
     *                          @OA\Property(property="status", type="string", example="Rejected"),
     *                          @OA\Property(property="staff", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="username", type="string", example="admin"),
     *                              @OA\Property(property="openemis_no", type="string", example="1522271965"),
     *                              @OA\Property(property="first_name", type="string", example="firstname"),
     *                              @OA\Property(property="middle_name", type="string", example="lastname"),
     *                              @OA\Property(property="third_name", type="string", example="third_name"),
     *                              @OA\Property(property="last_name", type="string", example="last_name"),
     *                              @OA\Property(property="preferred_name", type="string", example=""),
     *                              @OA\Property(property="full_name", type="string", example="firstname lastname"),
     *                              @OA\Property(property="name_with_id", type="string", example="1522271965 - firstname lastname")
     *                          ),
     *                          @OA\Property(property="url", type="object",
     *                              @OA\Property(property="plugin", type="string", example="Directories"),
     *                              @OA\Property(property="controller", type="string", example="Directories"),
     *                              @OA\Property(property="action", type="string", example="TrainingNeeds"),
     *                              @OA\Property(property="0", type="string", example="view"),
     *                              @OA\Property(property="1", type="integer", example=2),
     *                              @OA\Property(property="user_id", type="integer", example=11),
     *                          ),
     *                          @OA\Property(property="created_user", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="username", type="string", example="admin"),
     *                              @OA\Property(property="openemis_no", type="string", example="1522271965"),
     *                              @OA\Property(property="first_name", type="string", example="firstname"),
     *                              @OA\Property(property="middle_name", type="string", example="lastname"),
     *                              @OA\Property(property="third_name", type="string", example="third_name"),
     *                              @OA\Property(property="last_name", type="string", example="last_name"),
     *                              @OA\Property(property="preferred_name", type="string", example=""),
     *                              @OA\Property(property="full_name", type="string", example="firstname lastname"),
     *                              @OA\Property(property="name_with_id", type="string", example="1522271965 - firstname lastname")
     *                          )
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
    public function getStaffTrainingNeeds(Request $request)
    {
        try {
            $data = $this->workbenchService->getStaffTrainingNeeds($request);
            
            return $this->sendSuccessResponse("Staff Training Needs List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v4/staff/professionaldevelopment/licenses",
     *      summary="Get a list of licenses",
     *      description="Get a list of licenses",
     *      tags={"Workbench"},
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
     *                          @OA\Property(property="id", type="integer", example=1),
     *                          @OA\Property(property="license_type", type="object",
     *                              @OA\Property(property="id", type="string", example=12),
     *                              @OA\Property(property="name", type="string", example="Teaching License - Provisional"),
     *                          ),
     *                          @OA\Property(property="license_type_id", type="integer", example=12),
     *                          @OA\Property(property="request_title", type="string", example=" testtransfer to S2002 - Boster Lower Secondary School"),
     *                          @OA\Property(property="received_date", type="string", example="May 11, 2020"),
     *                          @OA\Property(property="requester", type="string", example="admin - System  Admin"),
     *                          @OA\Property(property="security_user_id", type="integer", example=141),
     *                          @OA\Property(property="status_id", type="integer", example=126),
     *                          @OA\Property(property="status", type="string", example="License Awarded"),
     *                          @OA\Property(property="user", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="username", type="string", example="admin"),
     *                              @OA\Property(property="openemis_no", type="string", example="1522271965"),
     *                              @OA\Property(property="first_name", type="string", example="firstname"),
     *                              @OA\Property(property="middle_name", type="string", example="lastname"),
     *                              @OA\Property(property="third_name", type="string", example="third_name"),
     *                              @OA\Property(property="last_name", type="string", example="last_name"),
     *                              @OA\Property(property="preferred_name", type="string", example=""),
     *                              @OA\Property(property="full_name", type="string", example="firstname lastname"),
     *                              @OA\Property(property="name_with_id", type="string", example="1522271965 - firstname lastname")
     *                          ),
     *                          @OA\Property(property="url", type="object",
     *                              @OA\Property(property="plugin", type="string", example="Directories"),
     *                              @OA\Property(property="controller", type="string", example="Directories"),
     *                              @OA\Property(property="action", type="string", example="StaffLicenses"),
     *                              @OA\Property(property="0", type="string", example="view"),
     *                              @OA\Property(property="1", type="integer", example=1),
     *                              @OA\Property(property="user_id", type="integer", example=11)
     *                          ),
     *                          @OA\Property(property="created_user", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="username", type="string", example="admin"),
     *                              @OA\Property(property="openemis_no", type="string", example="1522271965"),
     *                              @OA\Property(property="first_name", type="string", example="firstname"),
     *                              @OA\Property(property="middle_name", type="string", example="lastname"),
     *                              @OA\Property(property="third_name", type="string", example="third_name"),
     *                              @OA\Property(property="last_name", type="string", example="last_name"),
     *                              @OA\Property(property="preferred_name", type="string", example=""),
     *                              @OA\Property(property="full_name", type="string", example="firstname lastname"),
     *                              @OA\Property(property="name_with_id", type="string", example="1522271965 - firstname lastname")
     *                          )
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
    public function getStaffLicenses(Request $request)
    {
        try {
            $data = $this->workbenchService->getStaffLicenses($request);
            
            return $this->sendSuccessResponse("Staff Licenses List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v4/administration/training/courses",
     *      summary="Get a list of training courses",
     *      description="Get a list of training courses",
     *      tags={"Workbench"},
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
     *                          @OA\Property(property="id", type="integer", example=1),
     *                          @OA\Property(property="name", type="string", example="National Standard Training for Teachers"),
     *                          @OA\Property(property="code", type="string", example="NST"),
     *                          @OA\Property(property="code_name", type="string", example="NST - National Standard Training for Teachers"),
     *                          @OA\Property(property="request_title", type="string", example=" testtransfer to S2002 - Boster Lower Secondary School"),
     *                          @OA\Property(property="received_date", type="string", example="May 11, 2020"),
     *                          @OA\Property(property="requester", type="string", example="admin - System  Admin"),
     *                          @OA\Property(property="security_user_id", type="integer", example=141),
     *                          @OA\Property(property="status_id", type="integer", example=126),
     *                          @OA\Property(property="status", type="string", example="Accredited"),
     *                          @OA\Property(property="url", type="object",
     *                              @OA\Property(property="plugin", type="string", example="Training"),
     *                              @OA\Property(property="controller", type="string", example="Trainings"),
     *                              @OA\Property(property="action", type="string", example="Courses"),
     *                              @OA\Property(property="0", type="string", example="view"),
     *                              @OA\Property(property="1", type="integer", example=1)
     *                          ),
     *                          @OA\Property(property="created_user", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="username", type="string", example="admin"),
     *                              @OA\Property(property="openemis_no", type="string", example="1522271965"),
     *                              @OA\Property(property="first_name", type="string", example="firstname"),
     *                              @OA\Property(property="middle_name", type="string", example="lastname"),
     *                              @OA\Property(property="third_name", type="string", example="third_name"),
     *                              @OA\Property(property="last_name", type="string", example="last_name"),
     *                              @OA\Property(property="preferred_name", type="string", example=""),
     *                              @OA\Property(property="full_name", type="string", example="firstname lastname"),
     *                              @OA\Property(property="name_with_id", type="string", example="1522271965 - firstname lastname")
     *                          )
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
    public function getTrainingCourses(Request $request)
    {
        try {
            $data = $this->workbenchService->getTrainingCourses($request);
            
            return $this->sendSuccessResponse("Training Courses List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v4/administration/training/sessions",
     *      summary="Get a list of training sessions",
     *      description="Get a list of training sessions",
     *      tags={"Workbench"},
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
     *                          @OA\Property(property="id", type="integer", example=1),
     *                          @OA\Property(property="name", type="string", example="National Standard Training for Teachers"),
     *                          @OA\Property(property="code", type="string", example="NST"),
     *                          @OA\Property(property="code_name", type="string", example="NST - National Standard Training for Teachers"),
     *                          @OA\Property(property="request_title", type="string", example=" testtransfer to S2002 - Boster Lower Secondary School"),
     *                          @OA\Property(property="received_date", type="string", example="May 11, 2020"),
     *                          @OA\Property(property="requester", type="string", example="admin - System  Admin"),
     *                          @OA\Property(property="security_user_id", type="integer", example=141),
     *                          @OA\Property(property="status_id", type="integer", example=126),
     *                          @OA\Property(property="status", type="string", example="Accredited"),
     *                          @OA\Property(property="url", type="object",
     *                              @OA\Property(property="plugin", type="string", example="Training"),
     *                              @OA\Property(property="controller", type="string", example="Trainings"),
     *                              @OA\Property(property="action", type="string", example="Sessions"),
     *                              @OA\Property(property="0", type="string", example="view"),
     *                              @OA\Property(property="1", type="integer", example=1)
     *                          ),
     *                          @OA\Property(property="created_user", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="username", type="string", example="admin"),
     *                              @OA\Property(property="openemis_no", type="string", example="1522271965"),
     *                              @OA\Property(property="first_name", type="string", example="firstname"),
     *                              @OA\Property(property="middle_name", type="string", example="lastname"),
     *                              @OA\Property(property="third_name", type="string", example="third_name"),
     *                              @OA\Property(property="last_name", type="string", example="last_name"),
     *                              @OA\Property(property="preferred_name", type="string", example=""),
     *                              @OA\Property(property="full_name", type="string", example="firstname lastname"),
     *                              @OA\Property(property="name_with_id", type="string", example="1522271965 - firstname lastname")
     *                          )
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
    public function getTrainingSessions(Request $request)
    {
        try {
            $data = $this->workbenchService->getTrainingSessions($request);
            
            return $this->sendSuccessResponse("Training Sessions List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v4/administration/training/results",
     *      summary="Get a list of training results",
     *      description="Get a list of training results",
     *      tags={"Workbench"},
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
     *                          @OA\Property(property="id", type="integer", example=1),
     *                          @OA\Property(property="training_session_id", type="integer", example=1),
     *                          @OA\Property(property="request_title", type="string", example=" testtransfer to S2002 - Boster Lower Secondary School"),
     *                          @OA\Property(property="received_date", type="string", example="May 11, 2020"),
     *                          @OA\Property(property="session", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="code", type="string", example="NST2018"),
     *                              @OA\Property(property="name", type="string", example="National Standard Training for Teachers 2018"),
     *                              @OA\Property(property="code_name", type="string", example="NST2018 - National Standard Training for Teachers 2018"),
     *                          ),
     *                          @OA\Property(property="requester", type="string", example="admin - System  Admin"),
     *                          @OA\Property(property="security_user_id", type="integer", example=141),
     *                          @OA\Property(property="status_id", type="integer", example=126),
     *                          @OA\Property(property="status", type="string", example="Posted"),
     *                          @OA\Property(property="url", type="object",
     *                              @OA\Property(property="plugin", type="string", example="Training"),
     *                              @OA\Property(property="controller", type="string", example="Trainings"),
     *                              @OA\Property(property="action", type="string", example="Results"),
     *                              @OA\Property(property="0", type="string", example="view"),
     *                              @OA\Property(property="1", type="integer", example=1)
     *                          ),
     *                          @OA\Property(property="created_user", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="username", type="string", example="admin"),
     *                              @OA\Property(property="openemis_no", type="string", example="1522271965"),
     *                              @OA\Property(property="first_name", type="string", example="firstname"),
     *                              @OA\Property(property="middle_name", type="string", example="lastname"),
     *                              @OA\Property(property="third_name", type="string", example="third_name"),
     *                              @OA\Property(property="last_name", type="string", example="last_name"),
     *                              @OA\Property(property="preferred_name", type="string", example=""),
     *                              @OA\Property(property="full_name", type="string", example="firstname lastname"),
     *                              @OA\Property(property="name_with_id", type="string", example="1522271965 - firstname lastname")
     *                          )
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
    public function getTrainingResults(Request $request)
    {
        try {
            $data = $this->workbenchService->getTrainingResults($request);
            
            return $this->sendSuccessResponse("Training Results List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v4/institutions/visits/requests",
     *      summary="Get a list of institutions visits",
     *      description="Get a list of institutions visits",
     *      tags={"Workbench"},
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
     *                          @OA\Property(property="id", type="integer", example=1),
     *                          @OA\Property(property="institution", type="integer", example=1),
     *                          @OA\Property(property="institution_id", type="string", example=" testtransfer to S2002 - Boster Lower Secondary School"),
     *                          @OA\Property(property="request_title", type="string", example="Site Inspection in 2021 on Apr 5, 2024"),
     *                          @OA\Property(property="date_of_visit", type="string", example="Apr 5, 2024"),
     *                          @OA\Property(property="quality_visit_type", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="name", type="string", example="Site Inspection")
     *                          ),
     *                          @OA\Property(property="academic_period", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="name", type="string", example="2021")
     *                          ),
     *                          @OA\Property(property="academic_period_id", type="integer", example=1),
     *                          @OA\Property(property="requester", type="string", example="sysadmin1 - System  Administrator"),
     *                          @OA\Property(property="status_id", type="integer", example=1),
     *                          @OA\Property(property="status", type="string", example="Active"),
     *                          @OA\Property(property="url", type="object",
     *                              @OA\Property(property="plugin", type="string", example="Institution"),
     *                              @OA\Property(property="controller", type="string", example="Institutions"),
     *                              @OA\Property(property="action", type="string", example="VisitRequests"),
     *                              @OA\Property(property="0", type="string", example="view"),
     *                              @OA\Property(property="1", type="integer", example=1),
     *                              @OA\Property(property="institution_id", type="integer", example=12),
     *                          ),
     *                          @OA\Property(property="created_user", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="openemis_no", type="string", example="1522271965"),
     *                              @OA\Property(property="first_name", type="string", example="firstname"),
     *                              @OA\Property(property="middle_name", type="string", example="lastname"),
     *                              @OA\Property(property="third_name", type="string", example="third_name"),
     *                              @OA\Property(property="last_name", type="string", example="last_name"),
     *                              @OA\Property(property="preferred_name", type="string", example=""),
     *                              @OA\Property(property="full_name", type="string", example="firstname lastname"),
     *                              @OA\Property(property="name_with_id", type="string", example="1522271965 - firstname lastname")
     *                          )
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
    public function getVisitRequests(Request $request)
    {
        try {
            $data = $this->workbenchService->getVisitRequests($request);
            
            return $this->sendSuccessResponse("Visit Request List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v4/administration/training/applications",
     *      summary="Get a list of training applications",
     *      description="Get a list of training applications",
     *      tags={"Workbench"},
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
     *                          @OA\Property(property="id", type="integer", example=1),
     *                          @OA\Property(property="institution", type="integer", example=1),
     *                          @OA\Property(property="institution_id", type="string", example=" testtransfer to S2002 - Boster Lower Secondary School"),
     *                          @OA\Property(property="request_title", type="string", example="Site Inspection in 2021 on Apr 5, 2024"),
     *                          @OA\Property(property="received_date", type="string", example="Apr 5, 2024"),
     *                          @OA\Property(property="requester", type="string", example="sysadmin1 - System  Administrator"),
     *                          @OA\Property(property="session", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="code", type="string", example="SENO001"),
     *                              @OA\Property(property="name", type="string", example="An overview of Special Educational Needs (SEN)"),
     *                              @OA\Property(property="training_course_id", type="integer", example=12),
     *                              @OA\Property(property="code_name", type="string", example="SENO001 - An overview of Special Educational Needs (SEN)"),
     *                              @OA\Property(property="course", type="object",
     *                                  @OA\Property(property="id", type="integer", example=1),
     *                                  @OA\Property(property="code", type="string", example="SEN001"),
     *                                  @OA\Property(property="name", type="string", example="Special Educational Needs"),
     *                                  @OA\Property(property="code_name", type="string", example="SEN001 - Special Educational Needs"),
     *                              ),
     *                          ),
     *                          @OA\Property(property="status_id", type="integer", example=1),
     *                          @OA\Property(property="status", type="string", example="Active"),
     *                          @OA\Property(property="url", type="object",
     *                              @OA\Property(property="plugin", type="string", example="Institution"),
     *                              @OA\Property(property="controller", type="string", example="Institutions"),
     *                              @OA\Property(property="action", type="string", example="StaffTrainingApplications"),
     *                              @OA\Property(property="0", type="string", example="view"),
     *                              @OA\Property(property="1", type="integer", example=1),
     *                              @OA\Property(property="institution_id", type="integer", example=12),
     *                          ),
     *                          @OA\Property(property="created_user", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="openemis_no", type="string", example="1522271965"),
     *                              @OA\Property(property="first_name", type="string", example="firstname"),
     *                              @OA\Property(property="middle_name", type="string", example="lastname"),
     *                              @OA\Property(property="third_name", type="string", example="third_name"),
     *                              @OA\Property(property="last_name", type="string", example="last_name"),
     *                              @OA\Property(property="preferred_name", type="string", example=""),
     *                              @OA\Property(property="full_name", type="string", example="firstname lastname"),
     *                              @OA\Property(property="name_with_id", type="string", example="1522271965 - firstname lastname")
     *                          ),
     *                          @OA\Property(property="staff", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="openemis_no", type="string", example="1522271965"),
     *                              @OA\Property(property="first_name", type="string", example="firstname"),
     *                              @OA\Property(property="middle_name", type="string", example="lastname"),
     *                              @OA\Property(property="third_name", type="string", example="third_name"),
     *                              @OA\Property(property="last_name", type="string", example="last_name"),
     *                              @OA\Property(property="preferred_name", type="string", example=""),
     *                              @OA\Property(property="full_name", type="string", example="firstname lastname"),
     *                              @OA\Property(property="name_with_id", type="string", example="1522271965 - firstname lastname")
     *                          )
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
    public function getTrainingApplications(Request $request)
    {
        try {
            $data = $this->workbenchService->getTrainingApplications($request);
            
            return $this->sendSuccessResponse("Training Applications List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }


    /**
     * @OA\Get(
     *      path="/api/v4/administration/scholarships/applications",
     *      summary="Get a list of scholarships applications",
     *      description="Get a list of scholarships applications",
     *      tags={"Workbench"},
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
     *                          @OA\Property(property="id", type="integer", example=1),
     *                          @OA\Property(property="applicant", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="openemis_no", type="string", example="1522271965"),
     *                              @OA\Property(property="first_name", type="string", example="firstname"),
     *                              @OA\Property(property="middle_name", type="string", example="lastname"),
     *                              @OA\Property(property="third_name", type="string", example="third_name"),
     *                              @OA\Property(property="last_name", type="string", example="last_name"),
     *                              @OA\Property(property="preferred_name", type="string", example=""),
     *                              @OA\Property(property="full_name", type="string", example="firstname lastname"),
     *                              @OA\Property(property="name_with_id", type="string", example="1522271965 - firstname lastname")
     *                          ),
     *                          @OA\Property(property="applicant_id", type="integer", example=1),
     *                          @OA\Property(property="request_title", type="string", example="Site Inspection in 2021 on Apr 5, 2024"),
     *                          @OA\Property(property="received_date", type="string", example="Apr 5, 2024"),
     *                          @OA\Property(property="requester", type="string", example="sysadmin1 - System  Administrator"),
     *                          @OA\Property(property="scholarship", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="name", type="string", example="Eagles Award"),
     *                              @OA\Property(property="code", type="string", example="SCH-EA-01"),
     *                              @OA\Property(property="code_name", type="string", example="SCH-EA-01 - Eagles Award")
     *                          ),
     *                          @OA\Property(property="scholarship_id", type="integer", example=1),
     *                          @OA\Property(property="status_id", type="integer", example=1),
     *                          @OA\Property(property="status", type="string", example="Active"),
     *                          @OA\Property(property="url", type="object",
     *                              @OA\Property(property="plugin", type="string", example="Scholarship"),
     *                              @OA\Property(property="controller", type="string", example="Scholarships"),
     *                              @OA\Property(property="action", type="string", example="Applications"),
     *                              @OA\Property(property="0", type="string", example="view"),
     *                              @OA\Property(property="applicant_id", type="integer", example=1),
     *                              @OA\Property(property="scholarship_id", type="integer", example=1),
     *                              @OA\Property(property="queryString", type="string", example=""),
     *                          ),
     *                          @OA\Property(property="created_user", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="openemis_no", type="string", example="1522271965"),
     *                              @OA\Property(property="first_name", type="string", example="firstname"),
     *                              @OA\Property(property="middle_name", type="string", example="lastname"),
     *                              @OA\Property(property="third_name", type="string", example="third_name"),
     *                              @OA\Property(property="last_name", type="string", example="last_name"),
     *                              @OA\Property(property="preferred_name", type="string", example=""),
     *                              @OA\Property(property="full_name", type="string", example="firstname lastname"),
     *                              @OA\Property(property="name_with_id", type="string", example="1522271965 - firstname lastname")
     *                          ),
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
    public function getScholarshipApplications(Request $request)
    {
        try {
            $data = $this->workbenchService->getScholarshipApplications($request);
            
            return $this->sendSuccessResponse("Scholarship Applications List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v4/institutions/cases",
     *      summary="Get a list of institution cases",
     *      description="Get a list of institution cases",
     *      tags={"Workbench"},
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
     *                          @OA\Property(property="id", type="integer", example=1),
     *                          @OA\Property(property="institution", type="string", example="Avory Primary School"),
     *                          @OA\Property(property="institution_id", type="integer", example=1),
     *                          @OA\Property(property="request_title", type="string", example="loe fuinn from P1002 - Avory Primary School with Absence - Unexcused"),
     *                          @OA\Property(property="title", type="string", example="loe fuinn from P1002 - Avory Primary School with Absence - Unexcused"),
     *                          @OA\Property(property="received_date", type="string", example="Apr 5, 2024"),
     *                          @OA\Property(property="requester", type="string", example="sysadmin1 - System  Administrator"),
     *                          @OA\Property(property="status_id", type="integer", example=1),
     *                          @OA\Property(property="status", type="string", example="Active"),
     *                          @OA\Property(property="url", type="object",
     *                              @OA\Property(property="plugin", type="string", example="Institution"),
     *                              @OA\Property(property="controller", type="string", example="Institutions"),
     *                              @OA\Property(property="action", type="string", example="Cases"),
     *                              @OA\Property(property="0", type="string", example="view"),
     *                              @OA\Property(property="1", type="integer", example=1),
     *                              @OA\Property(property="institution_id", type="integer", example=1),
     *                          ),
     *                          @OA\Property(property="created_user", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="openemis_no", type="string", example="1522271965"),
     *                              @OA\Property(property="first_name", type="string", example="firstname"),
     *                              @OA\Property(property="middle_name", type="string", example="lastname"),
     *                              @OA\Property(property="third_name", type="string", example="third_name"),
     *                              @OA\Property(property="last_name", type="string", example="last_name"),
     *                              @OA\Property(property="preferred_name", type="string", example=""),
     *                              @OA\Property(property="full_name", type="string", example="firstname lastname"),
     *                              @OA\Property(property="name_with_id", type="string", example="1522271965 - firstname lastname")
     *                          ),
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
    public function getInstitutionCases(Request $request)
    {
        try {
            $data = $this->workbenchService->getInstitutionCases($request);
            
            return $this->sendSuccessResponse("Institution Cases List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v4/institutions/positions",
     *      summary="Get a list of institution positions",
     *      description="Get a list of institution positions",
     *      tags={"Workbench"},
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
     *                          @OA\Property(property="id", type="integer", example=1),
     *                          @OA\Property(property="institution", type="string", example="Avory Primary School"),
     *                          @OA\Property(property="institution_id", type="integer", example=1),
     *                          @OA\Property(property="name", type="string", example="K0001-1522277303 - Principal"),
     *                          @OA\Property(property="request_title", type="string", example="K0001-1522277303 - Principal"),
     *                          @OA\Property(property="title", type="string", example="loe fuinn from P1002 - Avory Primary School with Absence - Unexcused"),
     *                          @OA\Property(property="received_date", type="string", example="Apr 5, 2024"),
     *                          @OA\Property(property="position_no", type="string", example="K0001-1522277303"),
     *                          @OA\Property(property="requester", type="string", example="sysadmin1 - System  Administrator"),
     *                          @OA\Property(property="staff_position_title", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="name", type="string", example="Principal"),
     *                          ),
     *                          @OA\Property(property="status_id", type="integer", example=1),
     *                          @OA\Property(property="status", type="string", example="Active"),
     *                          @OA\Property(property="url", type="object",
     *                              @OA\Property(property="plugin", type="string", example="Institution"),
     *                              @OA\Property(property="controller", type="string", example="Institutions"),
     *                              @OA\Property(property="action", type="string", example="Positions"),
     *                              @OA\Property(property="0", type="string", example="view"),
     *                              @OA\Property(property="1", type="integer", example=1),
     *                              @OA\Property(property="institution_id", type="integer", example=1),
     *                          ),
     *                          @OA\Property(property="created_user", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="openemis_no", type="string", example="1522271965"),
     *                              @OA\Property(property="first_name", type="string", example="firstname"),
     *                              @OA\Property(property="middle_name", type="string", example="lastname"),
     *                              @OA\Property(property="third_name", type="string", example="third_name"),
     *                              @OA\Property(property="last_name", type="string", example="last_name"),
     *                              @OA\Property(property="preferred_name", type="string", example=""),
     *                              @OA\Property(property="full_name", type="string", example="firstname lastname"),
     *                              @OA\Property(property="name_with_id", type="string", example="1522271965 - firstname lastname")
     *                          ),
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
    public function getInstitutionPositions(Request $request)
    {
        try {
            $data = $this->workbenchService->getInstitutionPositions($request);
            
            return $this->sendSuccessResponse("Institution Positions List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }


    public function getMinidashboardData(Request $request)
    {
        try {
            $data = $this->workbenchService->getMinidashboardData($request);
            
            return $this->sendSuccessResponse("Dashboard Data Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }


    //For POCOR-8519 Start...

    /**
     * @OA\Get(
     *     path="/api/v4/workbenches",
     *     summary="Get workbench data",
     *     description="Retrieve workbench data for various workbench types.",
     *     tags={"Workbench"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="institutionSurvey",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=75),
     *                         @OA\Property(property="institution_id", type="integer", example=6),
     *                         @OA\Property(property="institution", type="string", example="Avory Primary School"),
     *                         @OA\Property(property="request_title", type="string", example="Repeater List of 2024"),
     *                         @OA\Property(property="received_date", type="string", example="Aug 19, 2024"),
     *                         @OA\Property(property="requester", type="string", example="admin - System  Admin"),
     *                         @OA\Property(property="status_id", type="integer", example=1),
     *                         @OA\Property(property="status", type="string", example="Open"),
     *                         @OA\Property(property="workflow_name", type="string", example="Institutions - Survey - General"),
     *                         @OA\Property(
     *                             property="survey_form",
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=18),
     *                             @OA\Property(property="name", type="string", example="Repeater List")
     *                         ),
     *                         @OA\Property(
     *                             property="academic_period",
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=33),
     *                             @OA\Property(property="name", type="string", example="2024")
     *                         ),
     *                         @OA\Property(
     *                             property="url",
     *                             type="object",
     *                             @OA\Property(property="plugin", type="string", example="Institution"),
     *                             @OA\Property(property="controller", type="string", example="Institutions"),
     *                             @OA\Property(property="action", type="string", example="Surveys"),
     *                             @OA\Property(property="0", type="string", example="view"),
     *                             @OA\Property(property="1", type="integer", example=75),
     *                             @OA\Property(property="institution_id", type="integer", example=6)
     *                         ),
     *                         @OA\Property(
     *                             property="created_user",
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=2),
     *                             @OA\Property(property="openemis_no", type="string", example="admin"),
     *                             @OA\Property(property="first_name", type="string", example="System"),
     *                             @OA\Property(property="middle_name", type="string", example=""),
     *                             @OA\Property(property="third_name", type="string", example=""),
     *                             @OA\Property(property="last_name", type="string", example="Admin"),
     *                             @OA\Property(property="preferred_name", type="string", example=""),
     *                             @OA\Property(property="full_name", type="string", example="System  Admin"),
     *                             @OA\Property(property="name_with_id", type="string", example="admin - System  Admin")
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
    public function getAllWorkbenches(Request $request)
    {
        try {
            $params = $request->all();
            $resp = [];
            $insSurvey = $this->workbenchService->getInstitutionStaffSurveys($request);
            $insPositions = $this->workbenchService->getInstitutionPositions($request);
            $studentTransferIn = $this->workbenchService->getInstitutionStudentTransferIn($request);
            $studentTransferOut = $this->workbenchService->getInstitutionStudentTransferOut($request);
            $insStaffLeave = $this->workbenchService->getInstitutionStaffLeave($request);
            $insStudentWithdraw = $this->workbenchService->getInstitutionStudentWithdraw($request);
            $insStudentAdmission = $this->workbenchService->getInstitutionStudentAdmission($request);
            $insStudentBehaviour = $this->workbenchService->getInstitutionStudentBehaviour($request);
            $insStaffBehaviour = $this->workbenchService->getInstitutionStaffBehaviour($request);
            $staffAppraisals = $this->workbenchService->getStaffAppraisals($request);
            $staffRelease = $this->workbenchService->getStaffRelease($request);
            $staffTransferIn = $this->workbenchService->getStaffTransferIn($request);
            $staffTransferOut = $this->workbenchService->getStaffTransferOut($request);
            $changeInAssignment = $this->workbenchService->getChangeInAssignment($request);
            $staffTrainingNeeds = $this->workbenchService->getStaffTrainingNeeds($request);
            $staffLicenses = $this->workbenchService->getStaffLicenses($request);
            $trainingCourses = $this->workbenchService->getTrainingCourses($request);
            $trainingSessions = $this->workbenchService->getTrainingSessions($request);
            $trainingResults = $this->workbenchService->getTrainingResults($request);
            $visitRequests = $this->workbenchService->getVisitRequests($request);
            $trainingApplications = $this->workbenchService->getTrainingApplications($request);
            $scholarshipApplications = $this->workbenchService->getScholarshipApplications($request);
            $institutionCases = $this->workbenchService->getInstitutionCases($request);

            $resp['institutionSurvey'] = $insSurvey['data'];
            $resp['institutionPositions'] = $insPositions['data'];
            $resp['studentTransferIn'] = $studentTransferIn['data'];
            $resp['studentTransferOut'] = $studentTransferOut['data'];
            $resp['institutionStaffLeave'] = $insStaffLeave['data'];
            $resp['institutionStudentWithdraw'] = $insStudentWithdraw['data'];
            $resp['institutionStudentAdmission'] = $insStudentAdmission['data'];
            $resp['institutionStudentBehaviour'] = $insStudentBehaviour['data'];
            $resp['institutionStaffBehaviour'] = $insStaffBehaviour['data'];
            $resp['staffAppraisals'] = $staffAppraisals['data'];
            $resp['staffRelease'] = $staffRelease['data'];
            $resp['staffTransferIn'] = $staffTransferIn['data'];
            $resp['staffTransferOut'] = $staffTransferOut['data'];
            $resp['changeInAssignment'] = $changeInAssignment['data'];
            $resp['staffTrainingNeeds'] = $staffTrainingNeeds['data'];
            $resp['staffLicenses'] = $staffLicenses['data'];
            $resp['trainingCourses'] = $trainingCourses['data'];
            $resp['trainingSessions'] = $trainingSessions['data'];
            $resp['trainingResults'] = $trainingResults['data'];
            $resp['visitRequests'] = $visitRequests['data'];
            $resp['trainingApplications'] = $trainingApplications['data'];
            $resp['scholarshipApplications'] = $scholarshipApplications['data'];
            $resp['institutionCases'] = $institutionCases['data'];
            
            
            return $this->sendSuccessResponse("Workbenches list found.", $resp);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }
    //For POCOR-8519 End...
}