<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\UserService;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\SaveStudentDataRequest;
use App\Http\Requests\SaveStaffDataRequest;
use App\Http\Requests\SaveGuardianDataRequest;
use App\Http\Requests\UsersAddRequest;
use App\Http\Requests\ExternalDataSourceRequest;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * @OA\Get(
     *      path="/api/v4/users",
     *      summary="Get a list of users",
     *      description="Get a list of users",
     *      tags={"Users"},
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
     *                          @OA\Property(property="username", type="string", example="admin"),
     *                          @OA\Property(property="password", type="string", example=""),
     *                          @OA\Property(property="openemis_no", type="string", example="1522271965"),
     *                          @OA\Property(property="first_name", type="string", example="first name"),
     *                          @OA\Property(property="middle_name", type="string", example="last name"),
     *                          @OA\Property(property="third_name", type="string", example="third_name"),
     *                          @OA\Property(property="last_name", type="string", example="last_name"),
     *                          @OA\Property(property="preferred_name", type="string", example=""),
     *                          @OA\Property(property="email", type="string", example=""),
     *                          @OA\Property(property="address", type="string", example=""),
     *                          @OA\Property(property="postal_code", type="string", example=""),
     *                          @OA\Property(property="address_area_id", type="integer", example=1),
     *                          @OA\Property(property="birthplace_area_id", type="integer", example=1),
     *                          @OA\Property(property="gender_id", type="integer", example=1),
     *                          @OA\Property(property="date_of_birth", type="string", example="2022-08-10 12:00:00"),
     *                          @OA\Property(property="date_of_death", type="string", example=null),
     *                          @OA\Property(property="nationality_id", type="integer", example=3),
     *                          @OA\Property(property="identity_type_id", type="integer", example=1),
     *                          @OA\Property(property="identity_type_name", type="string", example=null),
     *                          @OA\Property(property="identity_number", type="string", example=null),
     *                          @OA\Property(property="external_reference", type="string", example=null),
     *                          @OA\Property(property="status", type="integer", example=1),
     *                          @OA\Property(property="last_login", type="string", example=null),
     *                          @OA\Property(property="photo_name", type="string", example=null),
     *                          @OA\Property(property="photo_content", type="string", example=null),
     *                          @OA\Property(property="preferred_language", type="string", example=null),
     *                          @OA\Property(property="is_student", type="integer", example=1),
     *                          @OA\Property(property="is_staff", type="integer", example=1),
     *                          @OA\Property(property="is_guardian", type="integer", example=1),
     *                          @OA\Property(property="modified_user_id", type="integer", example=1),
     *                          @OA\Property(property="modified", type="date", example="2022-01-01 10:32:20"),
     *                          @OA\Property(property="created_user_id", type="integer", example=1),
     *                          @OA\Property(property="created", type="date", example="2022-01-01 10:32:20"),
     *                          @OA\Property(property="nationalities", type="array",
     *                              @OA\Items(
     *                                  type="object",
     *                                  @OA\Property(property="preferred", type="integer", example=1),
     *                                  @OA\Property(property="nationality_id", type="integer", example=1),
     *                                  @OA\Property(property="nationality_name", type="string", example="Jordanian"),
     *                                  @OA\Property(property="security_user_id", type="integer", example=1),
     *                                  @OA\Property(property="modified_user_id", type="integer", example=1),
     *                                  @OA\Property(property="modified", type="date", example="2022-01-01 10:32:20"),
     *                                  @OA\Property(property="created_user_id", type="integer", example=1),
     *                                  @OA\Property(property="created", type="date", example="2022-01-01 10:32:20"),
     *                              )
     *                          ),
     *                          @OA\Property(property="identities", type="array",
     *                              @OA\Items(
     *                                  type="object",
     *                                  @OA\Property(property="identity_type_id", type="integer", example=1),
     *                                  @OA\Property(property="identity_type_name", type="string", example="National Number"),
     *                                  @OA\Property(property="number", type="integer", example=1),
     *                                  @OA\Property(property="issue_date", type="integer", example=1),
     *                                  @OA\Property(property="expiry_date", type="integer", example=1),
     *                                  @OA\Property(property="issue_location", type="string", example="Jordan"),
     *                                  @OA\Property(property="nationality_id", type="integer", example=1),
     *                                  @OA\Property(property="comments", type="string", example="No comment"),
     *                                  @OA\Property(property="security_user_id", type="date", example="2022-01-01 10:32:20"),
     *                                  @OA\Property(property="modified_user_id", type="integer", example=1),
     *                                  @OA\Property(property="modified", type="date", example="2022-01-01 10:32:20"),
     *                                  @OA\Property(property="created_user_id", type="integer", example=1),
     *                                  @OA\Property(property="created", type="date", example="2022-01-01 10:32:20"),
     *                              )
     *                          )
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
    public function getUsersList(Request $request)
    {
        try {
            $data = $this->userService->getUsersList($request);
            return $this->sendSuccessResponse("Users List Found", $data);

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Users List Not Found');
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v4/users/{userId}",
     *      summary="Get user details",
     *      description="Get user details",
     *      tags={"Users"},
     *      @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         required=true,
     *         description="User Id",
     *         @OA\Schema(type="integer", example="1")
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
     *                          @OA\Property(property="id", type="integer", example=1),
     *                          @OA\Property(property="username", type="string", example="admin"),
     *                          @OA\Property(property="password", type="string", example=""),
     *                          @OA\Property(property="openemis_no", type="string", example="1522271965"),
     *                          @OA\Property(property="first_name", type="string", example="first name"),
     *                          @OA\Property(property="middle_name", type="string", example="last name"),
     *                          @OA\Property(property="third_name", type="string", example="third_name"),
     *                          @OA\Property(property="last_name", type="string", example="last_name"),
     *                          @OA\Property(property="preferred_name", type="string", example=""),
     *                          @OA\Property(property="email", type="string", example=""),
     *                          @OA\Property(property="address", type="string", example=""),
     *                          @OA\Property(property="postal_code", type="string", example=""),
     *                          @OA\Property(property="address_area_id", type="integer", example=1),
     *                          @OA\Property(property="birthplace_area_id", type="integer", example=1),
     *                          @OA\Property(property="gender_id", type="integer", example=1),
     *                          @OA\Property(property="date_of_birth", type="string", example="2022-08-10 12:00:00"),
     *                          @OA\Property(property="date_of_death", type="string", example=null),
     *                          @OA\Property(property="nationality_id", type="integer", example=3),
     *                          @OA\Property(property="identity_type_id", type="integer", example=1),
     *                          @OA\Property(property="identity_type_name", type="string", example=null),
     *                          @OA\Property(property="identity_number", type="string", example=null),
     *                          @OA\Property(property="external_reference", type="string", example=null),
     *                          @OA\Property(property="status", type="integer", example=1),
     *                          @OA\Property(property="last_login", type="string", example=null),
     *                          @OA\Property(property="photo_name", type="string", example=null),
     *                          @OA\Property(property="photo_content", type="string", example=null),
     *                          @OA\Property(property="preferred_language", type="string", example=null),
     *                          @OA\Property(property="is_student", type="integer", example=1),
     *                          @OA\Property(property="is_staff", type="integer", example=1),
     *                          @OA\Property(property="is_guardian", type="integer", example=1),
     *                          @OA\Property(property="modified_user_id", type="integer", example=1),
     *                          @OA\Property(property="modified", type="date", example="2022-01-01 10:32:20"),
     *                          @OA\Property(property="created_user_id", type="integer", example=1),
     *                          @OA\Property(property="created", type="date", example="2022-01-01 10:32:20"),
     *                          @OA\Property(property="nationalities", type="array",
     *                              @OA\Items(
     *                                  type="object",
     *                                  @OA\Property(property="preferred", type="integer", example=1),
     *                                  @OA\Property(property="nationality_id", type="integer", example=1),
     *                                  @OA\Property(property="nationality_name", type="string", example="Jordanian"),
     *                                  @OA\Property(property="security_user_id", type="integer", example=1),
     *                                  @OA\Property(property="modified_user_id", type="integer", example=1),
     *                                  @OA\Property(property="modified", type="date", example="2022-01-01 10:32:20"),
     *                                  @OA\Property(property="created_user_id", type="integer", example=1),
     *                                  @OA\Property(property="created", type="date", example="2022-01-01 10:32:20"),
     *                              )
     *                          ),
     *                          @OA\Property(property="identities", type="array",
     *                              @OA\Items(
     *                                  type="object",
     *                                  @OA\Property(property="identity_type_id", type="integer", example=1),
     *                                  @OA\Property(property="identity_type_name", type="string", example="National Number"),
     *                                  @OA\Property(property="number", type="integer", example=1),
     *                                  @OA\Property(property="issue_date", type="integer", example=1),
     *                                  @OA\Property(property="expiry_date", type="integer", example=1),
     *                                  @OA\Property(property="issue_location", type="string", example="Jordan"),
     *                                  @OA\Property(property="nationality_id", type="integer", example=1),
     *                                  @OA\Property(property="comments", type="string", example="No comment"),
     *                                  @OA\Property(property="security_user_id", type="date", example="2022-01-01 10:32:20"),
     *                                  @OA\Property(property="modified_user_id", type="integer", example=1),
     *                                  @OA\Property(property="modified", type="date", example="2022-01-01 10:32:20"),
     *                                  @OA\Property(property="created_user_id", type="integer", example=1),
     *                                  @OA\Property(property="created", type="date", example="2022-01-01 10:32:20"),
     *                              )
     *                          ),
     *                          @OA\Property(property="genderData", type="object",
     *                              @OA\Property(property="key", type="integer", example=1),
     *                              @OA\Property(property="value", type="string", example="Male"),
     *                          ),
     *                          @OA\Property(property="institution", type="object",
     *                              @OA\Property(property="key", type="integer", example=1),
     *                              @OA\Property(property="value", type="string", example=""),
     *                          ),
     *                          @OA\Property(property="educationGrade", type="object",
     *                              @OA\Property(property="key", type="integer", example=1),
     *                              @OA\Property(property="value", type="string", example=""),
     *                          ),
     *                          @OA\Property(property="studentStatus", type="object",
     *                              @OA\Property(property="key", type="integer", example=1),
     *                              @OA\Property(property="value", type="string", example=""),
     *                          )
     *                  )
     *             )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Unsuccessful.",
     *      )
     * )
     */
    public function getUsersData(int $userId)
    {
        try {
            $data = $this->userService->getUsersData($userId);
            return $this->sendSuccessResponse("Users Data Found", $data);

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Users Data Not Found');
        }
    }



    /**
     * @OA\Post(
     *     path="/api/v4/institutions/save-student",
     *     summary="Save student data",
     *     description="Save student data to the system.",
     *     tags={"Users"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={
     *                 "first_name",
     *                 "last_name",
     *                 "gender_id",
     *                 "date_of_birth"
     *             },
     *             @OA\Property(property="openemis_no", type="string", example=1522545402),
     *             @OA\Property(property="first_name", type="string", example="Test"),
     *             @OA\Property(property="middle_name", type="string", example=""),
     *             @OA\Property(property="third_name", type="string", example=""),
     *             @OA\Property(property="last_name", type="string", example="User"),
     *             @OA\Property(property="preferred_name", type="string", example=""),
     *             @OA\Property(property="gender_id", type="string", example=1),
     *             @OA\Property(property="date_of_birth", type="string", format="date", example="2011-01-01"),
     *             @OA\Property(property="identity_number", type="string", example="54542"),
     *             @OA\Property(property="nationality_id", type="string", example="2"),
     *             @OA\Property(property="nationality_name", type="string", example="America"),
     *             @OA\Property(property="username", type="string", example="TestUser101"),
     *             @OA\Property(property="password", type="string", example="TestUser101"),
     *             @OA\Property(property="postal_code", type="string", example="12233"),
     *             @OA\Property(property="address", type="string", example=""),
     *             @OA\Property(property="birthplace_area_id", type="string", example="2"),
     *             @OA\Property(property="address_area_id", type="string", example="2"),
     *             @OA\Property(property="identity_type_id", type="string", example="160"),
     *             @OA\Property(property="identity_type_name", type="string", example="Passport"),
     *             @OA\Property(property="education_grade_id", type="string", example="59"),
     *             @OA\Property(property="academic_period_id", type="string", example="30"),
     *             @OA\Property(property="start_date", type="string", format="date", example="01-01-2021"),
     *             @OA\Property(property="end_date", type="string", format="date", example="31-21-2021"),
     *             @OA\Property(property="institution_class_id", type="string", example="524"),
     *             @OA\Property(property="student_status_id", type="integer", example="1"),
     *             @OA\Property(property="comment", type="string", example="Hi"),
     *             @OA\Property(property="custom", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="student_custom_field_id", type="integer", example="17"),
     *                     @OA\Property(property="text_value", type="string", example="Yes"),
     *                     @OA\Property(property="number_value", type="string", example=""),
     *                     @OA\Property(property="decimal_value", type="string", example=""),
     *                     @OA\Property(property="textarea_value", type="string", example=""),
     *                     @OA\Property(property="time_value", type="string", example=""),
     *                     @OA\Property(property="file", type="string", example="")
     *                 )
     *             )
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
    public function saveStudentData(SaveStudentDataRequest $request)
    {
        try {
            $data = $this->userService->saveStudentData($request);

            if($data == 1){
                return $this->sendSuccessResponse("Student data stored successfully.");
            } elseif($data == 2) {
                return $this->sendErrorResponse("Invalid academic period.");
            }else {
                return $this->sendErrorResponse("Student data not stored.", $data);
            }

        } catch (\Exception $e) {
            Log::error(
                'Failed to store student data.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to store student data.');
        }
    }



    /**
     * @OA\Post(
     *     path="/api/v4/institutions/save-staff",
     *     summary="Save staff data",
     *     description="Save staff data to the system.",
     *     tags={"Users"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={
     *                 "first_name",
     *                 "last_name",
     *                 "gender_id",
     *                 "date_of_birth"
     *             },
     *             @OA\Property(property="openemis_no", type="string", example=1522545402),
     *             @OA\Property(property="first_name", type="string", example="Test"),
     *             @OA\Property(property="middle_name", type="string", example=""),
     *             @OA\Property(property="third_name", type="string", example=""),
     *             @OA\Property(property="last_name", type="string", example="User"),
     *             @OA\Property(property="preferred_name", type="string", example=""),
     *             @OA\Property(property="gender_id", type="string", example=1),
     *             @OA\Property(property="date_of_birth", type="string", format="date", example="2011-01-01"),
     *             @OA\Property(property="identity_number", type="string", example="54542"),
     *             @OA\Property(property="nationality_id", type="string", example="2"),
     *             @OA\Property(property="nationality_name", type="string", example="America"),
     *             @OA\Property(property="username", type="string", example="TestUser101"),
     *             @OA\Property(property="password", type="string", example="TestUser101"),
     *             @OA\Property(property="postal_code", type="string", example="12233"),
     *             @OA\Property(property="address", type="string", example=""),
     *             @OA\Property(property="birthplace_area_id", type="string", example="2"),
     *             @OA\Property(property="address_area_id", type="string", example="2"),
     *             @OA\Property(property="identity_type_id", type="string", example="160"),
     *             @OA\Property(property="identity_type_name", type="string", example="Passport"),
     *             @OA\Property(property="education_grade_id", type="string", example="59"),
     *             @OA\Property(property="academic_period_id", type="string", example="30"),
     *             @OA\Property(property="start_date", type="string", format="date", example="01-01-2021"),
     *             @OA\Property(property="end_date", type="string", format="date", example="31-21-2021"),
     *             @OA\Property(property="fte", type="string", example="1"),
     *             @OA\Property(property="staff_id", type="string", example="506"),
     *             @OA\Property(property="staff_position_grade_id", type="string", example="1"),
     *             @OA\Property(property="custom", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="staff_custom_field_id", type="integer", example="17"),
     *                     @OA\Property(property="text_value", type="string", example="Yes"),
     *                     @OA\Property(property="number_value", type="string", example=""),
     *                     @OA\Property(property="decimal_value", type="string", example=""),
     *                     @OA\Property(property="textarea_value", type="string", example=""),
     *                     @OA\Property(property="time_value", type="string", example=""),
     *                     @OA\Property(property="file", type="string", example="")
     *                 )
     *             )
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
    public function saveStaffData(SaveStaffDataRequest $request)
    {
        try {
            $data = $this->userService->saveStaffData($request);

            if($data == 1){
                return $this->sendSuccessResponse("Staff data stored successfully.");
            } elseif($data == 2) {
                return $this->sendErrorResponse("Invalid academic period.");
            } elseif($data == 3) {
                return $this->sendErrorResponse("Invalid staff type.");
            } elseif($data == 4) {
                return $this->sendErrorResponse("Invalid staff position grade.");
            } elseif($data == 5) {
                return $this->sendErrorResponse("Invalid institution position.");
            } else {
                return $this->sendErrorResponse("Staff data not stored.", $data);
            }

        } catch (\Exception $e) {
            Log::error(
                'Failed to store student data.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to store student data.');
        }
    }

    //Day 2

    /**
     * @OA\Get(
     *      path="/api/v4/users/genders",
     *      summary="Get genders",
     *      description="Get genders",
     *      tags={"Users"},
     *      @OA\Parameter(
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
     *      @OA\Response(
     *          response=200,
     *          description="Successful.",
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                          @OA\Property(property="id", type="integer", example=1),
     *                          @OA\Property(property="name", type="string", example="Male"),
     *                          @OA\Property(property="code", type="string", example="M"),
     *                          @OA\Property(property="order", type="integer", example=1),
     *                          @OA\Property(property="created_user_id", type="integer", example=1),
     *                          @OA\Property(property="created", type="string", example="2015-04-09 02:46:40"),
     *                  )
     *             )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Unsuccessful.",
     *      )
     * )
     */
    public function getUsersGender(Request $request)
    {
        try {
            $data = $this->userService->getUsersGender($request);
            return $this->sendSuccessResponse("Users Gender List Found", $data);

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Users Gender List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Users Gender List Not Found');
        }
    }


    /**
     * @OA\Post(
     *     path="/api/v4/institutions/save-guardian",
     *     summary="Save guardian data",
     *     description="Save guardian data to the system.",
     *     tags={"Users"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={
     *                 "first_name",
     *                 "last_name",
     *                 "gender_id",
     *                 "date_of_birth"
     *             },
     *             @OA\Property(property="openemis_no", type="string", example=1522545402),
     *             @OA\Property(property="first_name", type="string", example="Test"),
     *             @OA\Property(property="middle_name", type="string", example=""),
     *             @OA\Property(property="third_name", type="string", example=""),
     *             @OA\Property(property="last_name", type="string", example="User"),
     *             @OA\Property(property="preferred_name", type="string", example=""),
     *             @OA\Property(property="gender_id", type="string", example=1),
     *             @OA\Property(property="date_of_birth", type="string", format="date", example="2011-01-01"),
     *             @OA\Property(property="identity_number", type="string", example="54542"),
     *             @OA\Property(property="nationality_id", type="string", example="2"),
     *             @OA\Property(property="nationality_name", type="string", example="America"),
     *             @OA\Property(property="username", type="string", example="TestUser101"),
     *             @OA\Property(property="password", type="string", example="TestUser101"),
     *             @OA\Property(property="postal_code", type="string", example="12233"),
     *             @OA\Property(property="address", type="string", example=""),
     *             @OA\Property(property="birthplace_area_id", type="string", example="2"),
     *             @OA\Property(property="address_area_id", type="string", example="2"),
     *             @OA\Property(property="identity_type_id", type="string", example="160"),
     *             @OA\Property(property="identity_type_name", type="string", example="Passport"),
     *             @OA\Property(property="guardian_relation_id", type="string", example="1"),
     *             @OA\Property(property="student_id", type="string", example="1161")
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
    public function saveGuardianData(SaveGuardianDataRequest $request)
    {
        try {
            $data = $this->userService->saveGuardianData($request);

            if($data == 1){
                return $this->sendSuccessResponse("Guardian data stored successfully.");
            } elseif($data == 2) {
                return $this->sendErrorResponse("Invalid academic period.");
            } elseif($data == 3) {
                return $this->sendErrorResponse("Guardian Relation Id is invalid.");
            } else {
                return $this->sendErrorResponse("Guardian data not stored.", $data);
            }

        } catch (\Exception $e) {
            Log::error(
                'Failed to store guardian data.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to store guardian data.');
        }
    }



    //pocor-7545 starts

    /**
     * @OA\Post(
     *     path="/api/v4/users",
     *     summary="Update user's data",
     *     description="Update user's data to the system.",
     *     tags={"Users"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={
     *                 "first_name",
     *                 "last_name",
     *                 "gender_id",
     *                 "date_of_birth"
     *             },
     *             @OA\Property(property="first_name", type="string", example="Test"),
     *             @OA\Property(property="middle_name", type="string", example=""),
     *             @OA\Property(property="third_name", type="string", example=""),
     *             @OA\Property(property="last_name", type="string", example="User"),
     *             @OA\Property(property="preferred_name", type="string", example=""),
     *             @OA\Property(property="gender_id", type="string", example=1),
     *             @OA\Property(property="date_of_birth", type="string", format="date", example="2011-01-01"),
     *             @OA\Property(property="identity_number", type="string", example="54542"),
     *             @OA\Property(property="nationality_id", type="string", example="2"),
     *             @OA\Property(property="nationality_name", type="string", example="America"),
     *             @OA\Property(property="username", type="string", example="TestUser101"),
     *             @OA\Property(property="password", type="string", example="TestUser101"),
     *             @OA\Property(property="postal_code", type="string", example="12233"),
     *             @OA\Property(property="address", type="string", example=""),
     *             @OA\Property(property="birthplace_area_id", type="string", example="2"),
     *             @OA\Property(property="address_area_id", type="string", example="2"),
     *             @OA\Property(property="identity_type_id", type="string", example="160"),
     *             @OA\Property(property="identity_type_name", type="string", example="Passport")
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
    public function addUsers(UsersAddRequest $request)
    {
        try {
            $data = $this->userService->addUsers($request);

            if($data == 1){
                return $this->sendSuccessResponse("User is created/updated successfully.");
            } elseif($data == 2){
                return $this->sendErrorResponse("Invalid user id.");
            } else {
                return $this->sendErrorResponse("User is not created/updated successfully.");
            }

        } catch (\Exception $e) {
            Log::error(
                'User is not created/updated successfully.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('User is not created/updated successfully.');
        }
    }
    //pocor-7545 ends
    //POCOR-7716 start


    /**
     * @OA\Get(
     *     path="/api/v4/institutions/getStudentAdmissionStatus",
     *     summary="Get student admission status",
     *     description="Returns a list of student admission statuses.",
     *     tags={"Users"},
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
     *                     @OA\Property(property="name", type="string", example="Enrolled")
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
    public function getStudentAdmissionStatus()
    {
        try {
            $data = $this->userService->getStudentAdmissionStatus();
            return $this->sendSuccessResponse("Default Student Admission Status Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to get default student admission status',
                ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Default Student Admission Status Not Found');
        }
    }
    //POCOR-7716 end


    //POCOR-8136 start


    /**
     * @OA\Get(
     *     path="/api/v4/permissions",
     *     summary="Get permissions for a user",
     *     description="Returns permissions for a user based on the provided user ID",
     *     tags={"Users"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="userId", type="integer", example=573),
     *                 @OA\Property(property="super_admin", type="integer", example=0),
     *                 @OA\Property(property="groupIds", type="array",
     *                     @OA\Items(type="integer", example=6)
     *                 ),
     *                 @OA\Property(property="roleIds", type="array",
     *                     @OA\Items(type="integer", example=4)
     *                 ),
     *                 @OA\Property(property="institutionIds", type="array",
     *                     @OA\Items(type="integer", example=6)
     *                 ),
     *                 @OA\Property(property="permissions", type="object",
     *                     @OA\Property(property="Institutions", type="object",
     *                         @OA\Property(property="Institutions", type="object",
     *                             @OA\Property(property="index", type="array",
     *                                 @OA\Items(type="integer", example=4)
     *                             ),
     *                             @OA\Property(property="view", type="array",
     *                                 @OA\Items(type="integer", example=4)
     *                             ),
     *                             @OA\Property(property="add", type="array",
     *                                 @OA\Items(type="integer", example=4)
     *                             ),
     *                             @OA\Property(property="edit", type="array",
     *                                 @OA\Items(type="integer", example=4)
     *                             ),
     *                             @OA\Property(property="remove", type="array",
     *                                 @OA\Items(type="integer", example=4)
     *                             ),
     *                             @OA\Property(property="excel", type="array",
     *                                 @OA\Items(type="integer", example=4)
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
    public function getUserPermissions()
    {
        try {
            $data = $this->userService->getUserPermissions();
            return $this->sendSuccessResponse("User Permissions List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to get User Permissions List.',
                ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('User Permissions List Not Found.');
        }
    }
    //POCOR-8136 end


    //POCOR-8139 Starts
    /**
     * @OA\Post(
     *     path="/api/v4/external-data-sources",
     *     summary="Add a external data souce",
     *     description="Add a external data souce",
     *     tags={"Users"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Payload for adding external data source",
     *         @OA\JsonContent(
     *             required={
     *                 "first_name",
     *                 "last_name",
     *                 "date_of_birth",
     *                 "access_token"
     *             },
     *             @OA\Property(property="first_name", type="string", example="first name"),
     *             @OA\Property(property="last_name", type="string", example="last name"),
     *             @OA\Property(property="date_of_birth", type="string", example="01-12-2020"),
     *             @OA\Property(property="access_token", type="string", example="eyJpc3MiOiIxNzExNDIzNjE3LWZkZGU2MjhlNjhhZThkZDIuYXBwIiwic2NvcGUiOiJTdHVkZW50IiwiYXVkIjoiaHR0cHM6XC9cL2lkZW50aXR5Lm9wZW5lbWlzLm9yZ1wvaWRlbnRpdHlcL2FwaVwvb2F1dGhcL3Rva2VuIiwiZXhwIjoxNzExNDI3NTczLCJpYXQiOiIxNzExNDIzOTczIn0"),
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
    public function externalDataSources(ExternalDataSourceRequest $request)
    {
        try {
            $data = $this->userService->externalDataSources($request);
            return $this->sendSuccessResponse("Successful Operation.", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to get data from external data sources.',
                ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get data from external data sources.');
        }
    }

    //POCOR-8139 Ends

    //POCOR-8912 start
    /**
     * @OA\Get(
     *      path="/api/v4/users/email/{email}",
     *      summary="Get user details by email",
     *      description="Get user details by email",
     *      tags={"Users", "Email"},
     *      @OA\Parameter(
     *         name="email",
     *         in="path",
     *         required=true,
     *         description="Main Email",
     *         @OA\Schema(type="string", example="username")
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
     *                          @OA\Property(property="id", type="integer", example=1),
     *                          @OA\Property(property="username", type="string", example="admin"),
     *                          @OA\Property(property="openemis_no", type="string", example="1522271965"),
     *                          @OA\Property(property="first_name", type="string", example="first name"),
     *                          @OA\Property(property="middle_name", type="string", example="last name"),
     *                          @OA\Property(property="third_name", type="string", example="third_name"),
     *                          @OA\Property(property="last_name", type="string", example="last_name"),
     *                          @OA\Property(property="preferred_name", type="string", example=""),
     *                          @OA\Property(property="email", type="string", example=""),
     *                          @OA\Property(property="address", type="string", example=""),
     *                          @OA\Property(property="postal_code", type="string", example=""),
     *                          @OA\Property(property="address_area_id", type="integer", example=1),
     *                          @OA\Property(property="birthplace_area_id", type="integer", example=1),
     *                          @OA\Property(property="gender_id", type="integer", example=1),
     *                          @OA\Property(property="date_of_birth", type="string", example="2022-08-10 12:00:00"),
     *                          @OA\Property(property="date_of_death", type="string", example=null),
     *                          @OA\Property(property="nationality_id", type="integer", example=3),
     *                          @OA\Property(property="identity_type_id", type="integer", example=1),
     *                          @OA\Property(property="identity_type_name", type="string", example=null),
     *                          @OA\Property(property="identity_number", type="string", example=null),
     *                          @OA\Property(property="external_reference", type="string", example=null),
     *                          @OA\Property(property="status", type="integer", example=1),
     *                          @OA\Property(property="last_login", type="string", example=null),
     *                          @OA\Property(property="photo_name", type="string", example=null),
     *                          @OA\Property(property="photo_content", type="string", example=null),
     *                          @OA\Property(property="preferred_language", type="string", example=null),
     *                          @OA\Property(property="is_student", type="integer", example=1),
     *                          @OA\Property(property="is_staff", type="integer", example=1),
     *                          @OA\Property(property="is_guardian", type="integer", example=1),
     *                          @OA\Property(property="modified_user_id", type="integer", example=1),
     *                          @OA\Property(property="modified", type="date", example="2022-01-01 10:32:20"),
     *                          @OA\Property(property="created_user_id", type="integer", example=1),
     *                          @OA\Property(property="created", type="date", example="2022-01-01 10:32:20"),
     *                          @OA\Property(property="nationalities", type="array",
     *                              @OA\Items(
     *                                  type="object",
     *                                  @OA\Property(property="preferred", type="integer", example=1),
     *                                  @OA\Property(property="nationality_id", type="integer", example=1),
     *                                  @OA\Property(property="nationality_name", type="string", example="Jordanian"),
     *                                  @OA\Property(property="security_user_id", type="integer", example=1),
     *                                  @OA\Property(property="modified_user_id", type="integer", example=1),
     *                                  @OA\Property(property="modified", type="date", example="2022-01-01 10:32:20"),
     *                                  @OA\Property(property="created_user_id", type="integer", example=1),
     *                                  @OA\Property(property="created", type="date", example="2022-01-01 10:32:20"),
     *                              )
     *                          ),
     *                          @OA\Property(property="identities", type="array",
     *                              @OA\Items(
     *                                  type="object",
     *                                  @OA\Property(property="identity_type_id", type="integer", example=1),
     *                                  @OA\Property(property="identity_type_name", type="string", example="National Number"),
     *                                  @OA\Property(property="number", type="integer", example=1),
     *                                  @OA\Property(property="issue_date", type="integer", example=1),
     *                                  @OA\Property(property="expiry_date", type="integer", example=1),
     *                                  @OA\Property(property="issue_location", type="string", example="Jordan"),
     *                                  @OA\Property(property="nationality_id", type="integer", example=1),
     *                                  @OA\Property(property="comments", type="string", example="No comment"),
     *                                  @OA\Property(property="security_user_id", type="date", example="2022-01-01 10:32:20"),
     *                                  @OA\Property(property="modified_user_id", type="integer", example=1),
     *                                  @OA\Property(property="modified", type="date", example="2022-01-01 10:32:20"),
     *                                  @OA\Property(property="created_user_id", type="integer", example=1),
     *                                  @OA\Property(property="created", type="date", example="2022-01-01 10:32:20"),
     *                              )
     *                          ),
     *                          @OA\Property(property="genderData", type="object",
     *                              @OA\Property(property="key", type="integer", example=1),
     *                              @OA\Property(property="value", type="string", example="Male"),
     *                          ),
     *                          @OA\Property(property="institution", type="object",
     *                              @OA\Property(property="key", type="integer", example=1),
     *                              @OA\Property(property="value", type="string", example=""),
     *                          ),
     *                          @OA\Property(property="educationGrade", type="object",
     *                              @OA\Property(property="key", type="integer", example=1),
     *                              @OA\Property(property="value", type="string", example=""),
     *                          ),
     *                          @OA\Property(property="studentStatus", type="object",
     *                              @OA\Property(property="key", type="integer", example=1),
     *                              @OA\Property(property="value", type="string", example=""),
     *                          )
     *                  )
     *             )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Unsuccessful.",
     *      )
     * )
     */
    public function getUserByEmail(string $email): \Illuminate\Http\JsonResponse
    {
        try {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return response()->json(['error' => 'Invalid email format'], 400);
            }
            $userId = $this->userService->getUserIdByEmail($email);

            if ($userId) {

                // Get the user's data
                $data = $this->userService->getUsersData($userId);

                // Remove password from the response
                if (isset($data['password'])) {
                    unset($data['password']);
                }

                return $this->sendSuccessResponse("User Found", $data);
            } else {
                return $this->sendErrorResponse("User Not Found");
            }
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('User Data Not Found');
        }
    }
    //POCOR-8912 end

}
