<?php

namespace App\Http\Controllers;

use App\Http\Controllers\WebhookController;

use Illuminate\Http\Request;
use App\Services\StudentService;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\ClassAttendanceAdd;
use App\Http\Requests\StudentAbsenceAdd;
use App\Http\Requests\StaffAttendanceAdd;
use App\Http\Requests\UpdateStaffDetails;
use App\Http\Requests\StudentTransferAddRequest;
use Tymon\JWTAuth\Facades\JWTAuth;


class StudentController extends Controller
{
    protected $studentService;

    public function __construct(
        StudentService $studentService
    ) {
        $this->studentService = $studentService;
    }

    /**
     * @OA\Get(
     *      path="/api/v4/institutions/students",
     *      summary="Get a list of students",
     *      description="Get a list of students",
     *      tags={"Institutions"},
     *      @OA\Parameter(
     *         name="academic_period_id",
     *         in="query",
     *         required=false,
     *         description="Academic period id",
     *         @OA\Schema(type="integer", example="1")
     *      ),
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
     *             @OA\Property(property="data", type="object",
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                      @OA\Property(property="student_id", type="integer", example=196),
     *                      @OA\Property(property="first_name", type="string", example="Dixie"),
     *                      @OA\Property(property="middle_name", type="string", example=null),
     *                      @OA\Property(property="third_name", type="string", example=null),
     *                      @OA\Property(property="last_name", type="string", example="Campbell"),
     *                      @OA\Property(property="openemis_no", type="string", example="1522272158"),
     *                      @OA\Property(property="date_of_birth", type="string", example="2011-01-01"),
     *                      @OA\Property(property="date_of_death", type="string", example=null),
     *                      @OA\Property(property="identity_number", type="string", example=null),
     *                      @OA\Property(property="external_reference", type="string", example=null),
     *                      @OA\Property(property="gender_id", type="integer", example=2),
     *                      @OA\Property(property="gender_name", type="string", example="Female"),
     *                      @OA\Property(property="start_date", type="string", example="2019-01-01"),
     *                      @OA\Property(property="start_year", type="integer", example=2019),
     *                      @OA\Property(property="end_date", type="string", example="2019-12-31"),
     *                      @OA\Property(property="end_year", type="integer", example=2019),
     *                      @OA\Property(property="institution_id", type="integer", example=22),
     *                      @OA\Property(property="institution_code", type="string", example="P1009"),
     *                      @OA\Property(property="institution_name", type="string", example="Kinda Primary School"),
     *                      @OA\Property(property="student_status_id", type="integer", example=1),
     *                      @OA\Property(property="student_status_name", type="string", example="Enrolled"),
     *                      @OA\Property(property="student_status_code", type="string", example="CURRENT"),
     *                      @OA\Property(property="education_grade_id", type="integer", example=121),
     *                      @OA\Property(property="education_grade_name", type="string", example="Primary 1"),
     *                      @OA\Property(property="academic_period_id", type="integer", example=28),
     *                      @OA\Property(property="academic_period_name", type="string", example="2019"),
     *                      @OA\Property(property="previous_institution_student_id", type="string", example="6546009d-815f-42e6-8db5-76050a4f95d9"),
     *                      @OA\Property(
     *                          property="classes",
     *                          type="array",
     *                          @OA\Items(
     *                              type="object",
     *                              @OA\Property(property="id", type="integer", example=593),
     *                              @OA\Property(property="name", type="string", example="Primary 1-C"),
     *                              @OA\Property(
     *                                  property="subjects",
     *                                  type="array",
     *                                  @OA\Items(
     *                                      type="object",
     *                                      @OA\Property(property="id", type="integer", example=4732),
     *                                      @OA\Property(property="name", type="string", example="Expressive Arts")
     *                                  )
     *                              )
     *                          )
     *                       ),
     *                       @OA\Property(
     *                          property="custom_fields",
     *                          type="array",
     *                          @OA\Items(
     *                              type="object",
     *                              @OA\Property(property="id", type="string", example="0d66a4f8-a274-48cc-9520-967a07731ae8"),
     *                              @OA\Property(property="text_value", type="string", example=""),
     *                              @OA\Property(property="number_value", type="number", example=null),
     *                              @OA\Property(property="decimal_value", type="string", example=""),
     *                              @OA\Property(property="textarea_value", type="string", example=""),
     *                              @OA\Property(property="date_value", type="string", format="date", example=null),
     *                              @OA\Property(property="time_value", type="string", format="time", example=null),
     *                              @OA\Property(property="file", type="string", example=""),
     *                              @OA\Property(property="student_custom_field_id", type="integer", example=17),
     *                              @OA\Property(property="student_id", type="integer", example=14663),
     *                              @OA\Property(
     *                                  property="student_custom_field",
     *                                  type="object",
     *                                  @OA\Property(property="id", type="integer", example=17),
     *                                  @OA\Property(property="name", type="string", example="Father Living With Student")
     *                              )
     *                          )
     *                      ),
     *                      @OA\Property(property="modified_user_id", type="integer", example=null),
     *                      @OA\Property(property="modified", type="string", example=null),
     *                      @OA\Property(property="created_user_id", type="integer", example=2),
     *                      @OA\Property(property="created", type="string", example="2019-11-20 19:51:58")
     *                 )
     *             )
     *             )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Unsuccessful.",
     *      )
     * )
     */
    public function getStudents(Request $request)
    {
        try {
            $data = $this->studentService->getStudents($request);
            return $this->sendSuccessResponse("Institutions Students List Found", $data);

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution Students List Not Found');
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v4/institutions/{institutionId}/students",
     *      summary="Get a list of institutions students",
     *      description="Get a list of institutions students",
     *      tags={"Institutions"},
     *      @OA\Parameter(
     *         name="institutionId",
     *         in="path",
     *         required=true,
     *         description="Id of institution",
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="academic_period_id",
     *         in="query",
     *         required=false,
     *         description="Academic period id",
     *         @OA\Schema(type="integer", example="1")
     *      ),
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
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                      @OA\Property(property="student_id", type="integer", example=196),
     *                      @OA\Property(property="first_name", type="string", example="Dixie"),
     *                      @OA\Property(property="middle_name", type="string", example=null),
     *                      @OA\Property(property="third_name", type="string", example=null),
     *                      @OA\Property(property="last_name", type="string", example="Campbell"),
     *                      @OA\Property(property="openemis_no", type="string", example="1522272158"),
     *                      @OA\Property(property="date_of_birth", type="string", example="2011-01-01"),
     *                      @OA\Property(property="date_of_death", type="string", example=null),
     *                      @OA\Property(property="identity_number", type="string", example=null),
     *                      @OA\Property(property="external_reference", type="string", example=null),
     *                      @OA\Property(property="gender_id", type="integer", example=2),
     *                      @OA\Property(property="gender_name", type="string", example="Female"),
     *                      @OA\Property(property="start_date", type="string", example="2019-01-01"),
     *                      @OA\Property(property="start_year", type="integer", example=2019),
     *                      @OA\Property(property="end_date", type="string", example="2019-12-31"),
     *                      @OA\Property(property="end_year", type="integer", example=2019),
     *                      @OA\Property(property="institution_id", type="integer", example=22),
     *                      @OA\Property(property="institution_code", type="string", example="P1009"),
     *                      @OA\Property(property="institution_name", type="string", example="Kinda Primary School"),
     *                      @OA\Property(property="student_status_id", type="integer", example=1),
     *                      @OA\Property(property="student_status_name", type="string", example="Enrolled"),
     *                      @OA\Property(property="student_status_code", type="string", example="CURRENT"),
     *                      @OA\Property(property="education_grade_id", type="integer", example=121),
     *                      @OA\Property(property="education_grade_name", type="string", example="Primary 1"),
     *                      @OA\Property(property="academic_period_id", type="integer", example=28),
     *                      @OA\Property(property="academic_period_name", type="string", example="2019"),
     *                      @OA\Property(property="previous_institution_student_id", type="string", example="6546009d-815f-42e6-8db5-76050a4f95d9"),
     *                      @OA\Property(
     *                          property="classes",
     *                          type="array",
     *                          @OA\Items(
     *                              type="object",
     *                              @OA\Property(property="id", type="integer", example=593),
     *                              @OA\Property(property="name", type="string", example="Primary 1-C"),
     *                              @OA\Property(
     *                                  property="subjects",
     *                                  type="array",
     *                                  @OA\Items(
     *                                      type="object",
     *                                      @OA\Property(property="id", type="integer", example=4732),
     *                                      @OA\Property(property="name", type="string", example="Expressive Arts")
     *                                  )
     *                              )
     *                          )
     *                       ),
     *                       @OA\Property(
     *                          property="custom_fields",
     *                          type="array",
     *                          @OA\Items(
     *                              type="object",
     *                              @OA\Property(property="id", type="string", example="0d66a4f8-a274-48cc-9520-967a07731ae8"),
     *                              @OA\Property(property="text_value", type="string", example=""),
     *                              @OA\Property(property="number_value", type="number", example=null),
     *                              @OA\Property(property="decimal_value", type="string", example=""),
     *                              @OA\Property(property="textarea_value", type="string", example=""),
     *                              @OA\Property(property="date_value", type="string", format="date", example=null),
     *                              @OA\Property(property="time_value", type="string", format="time", example=null),
     *                              @OA\Property(property="file", type="string", example=""),
     *                              @OA\Property(property="student_custom_field_id", type="integer", example=17),
     *                              @OA\Property(property="student_id", type="integer", example=14663),
     *                              @OA\Property(
     *                                  property="student_custom_field",
     *                                  type="object",
     *                                  @OA\Property(property="id", type="integer", example=17),
     *                                  @OA\Property(property="name", type="string", example="Father Living With Student")
     *                              )
     *                          )
     *                      ),
     *                      @OA\Property(property="modified_user_id", type="integer", example=null),
     *                      @OA\Property(property="modified", type="string", example=null),
     *                      @OA\Property(property="created_user_id", type="integer", example=2),
     *                      @OA\Property(property="created", type="string", example="2019-11-20 19:51:58")
     *                 )
     *             )
     *             )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Unsuccessful.",
     *      )
     * )
     */
    public function getInstitutionStudents(Request $request, $institutionId)
    {
        try {
            $data = $this->studentService->getInstitutionStudents($request, $institutionId);
            return $this->sendSuccessResponse("Institutions Students List Found", $data);

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution Students List Not Found');
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v4/institutions/{institutionId}/students/{studentId}",
     *      summary="Get a institution student detail",
     *      description="Get a institution student detail",
     *      tags={"Institutions"},
     *      @OA\Parameter(
     *         name="institutionId",
     *         in="path",
     *         required=true,
     *         description="Id of institution",
     *         @OA\Schema(type="integer", example="1")
     *      ),
     *      @OA\Parameter(
     *         name="studentId",
     *         in="path",
     *         required=true,
     *         description="Id of student",
     *         @OA\Schema(type="integer", example="1")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful.",
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="student_id", type="integer", example=196),
     *                 @OA\Property(property="first_name", type="string", example="Dixie"),
     *                 @OA\Property(property="middle_name", type="string", example=null),
     *                 @OA\Property(property="third_name", type="string", example=null),
     *                 @OA\Property(property="last_name", type="string", example="Campbell"),
     *                 @OA\Property(property="openemis_no", type="string", example="1522272158"),
     *                 @OA\Property(property="date_of_birth", type="string", example="2011-01-01"),
     *                 @OA\Property(property="date_of_death", type="string", example=null),
     *                 @OA\Property(property="identity_number", type="string", example=null),
     *                 @OA\Property(property="external_reference", type="string", example=null),
     *                 @OA\Property(property="gender_id", type="integer", example=2),
     *                 @OA\Property(property="gender_name", type="string", example="Female"),
     *                 @OA\Property(property="start_date", type="string", example="2019-01-01"),
     *                 @OA\Property(property="start_year", type="integer", example=2019),
     *                 @OA\Property(property="end_date", type="string", example="2019-12-31"),
     *                 @OA\Property(property="end_year", type="integer", example=2019),
     *                 @OA\Property(property="institution_id", type="integer", example=22),
     *                 @OA\Property(property="institution_code", type="string", example="P1009"),
     *                 @OA\Property(property="institution_name", type="string", example="Kinda Primary School"),
     *                 @OA\Property(property="student_status_id", type="integer", example=1),
     *                 @OA\Property(property="student_status_name", type="string", example="Enrolled"),
     *                 @OA\Property(property="student_status_code", type="string", example="CURRENT"),
     *                 @OA\Property(property="education_grade_id", type="integer", example=121),
     *                 @OA\Property(property="education_grade_name", type="string", example="Primary 1"),
     *                 @OA\Property(property="academic_period_id", type="integer", example=28),
     *                 @OA\Property(property="academic_period_name", type="string", example="2019"),
     *                 @OA\Property(property="previous_institution_student_id", type="string", example="6546009d-815f-42e6-8db5-76050a4f95d9"),
     *                 @OA\Property(
     *                          property="classes",
     *                          type="array",
     *                          @OA\Items(
     *                              type="object",
     *                              @OA\Property(property="id", type="integer", example=593),
     *                              @OA\Property(property="name", type="string", example="Primary 1-C"),
     *                              @OA\Property(
     *                                  property="subjects",
     *                                  type="array",
     *                                  @OA\Items(
     *                                      type="object",
     *                                      @OA\Property(property="id", type="integer", example=4732),
     *                                      @OA\Property(property="name", type="string", example="Expressive Arts")
     *                                  )
     *                              )
     *                          )
     *                       ),
     *                       @OA\Property(
     *                          property="custom_fields",
     *                          type="array",
     *                          @OA\Items(
     *                              type="object",
     *                              @OA\Property(property="id", type="string", example="0d66a4f8-a274-48cc-9520-967a07731ae8"),
     *                              @OA\Property(property="text_value", type="string", example=""),
     *                              @OA\Property(property="number_value", type="number", example=null),
     *                              @OA\Property(property="decimal_value", type="string", example=""),
     *                              @OA\Property(property="textarea_value", type="string", example=""),
     *                              @OA\Property(property="date_value", type="string", format="date", example=null),
     *                              @OA\Property(property="time_value", type="string", format="time", example=null),
     *                              @OA\Property(property="file", type="string", example=""),
     *                              @OA\Property(property="student_custom_field_id", type="integer", example=17),
     *                              @OA\Property(property="student_id", type="integer", example=14663),
     *                              @OA\Property(
     *                                  property="student_custom_field",
     *                                  type="object",
     *                                  @OA\Property(property="id", type="integer", example=17),
     *                                  @OA\Property(property="name", type="string", example="Father Living With Student")
     *                              )
     *                          )
     *                      ),
     *                 @OA\Property(property="modified_user_id", type="integer", example=null),
     *                 @OA\Property(property="modified", type="string", example=null),
     *                 @OA\Property(property="created_user_id", type="integer", example=2),
     *                 @OA\Property(property="created", type="string", example="2019-11-20 19:51:58")
     *             )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Unsuccessful.",
     *      )
     * )
     */
    public function getInstitutionStudentData(Request $request, $institutionId, $studentId)
    {
        try {
            $data = $this->studentService->getInstitutionStudentData($request, $institutionId, $studentId);
            return $this->sendSuccessResponse("Institutions Student Data Found", $data);

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution Student Data Not Found');
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v4/institutions/students/absences",
     *      summary="Get a list of students absences",
     *      description="Get a list of students absences",
     *      tags={"Institutions"},
     *      @OA\Parameter(
     *         name="academic_period_id",
     *         in="query",
     *         required=false,
     *         description="Academic period id",
     *         @OA\Schema(type="integer", example="1")
     *      ),
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
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                      type="object",
     *                      @OA\Property(property="student_id", type="integer", example=10602),
     *                      @OA\Property(property="first_name", type="string", example="Francklin"),
     *                      @OA\Property(property="middle_name", type="string", example=null),
     *                      @OA\Property(property="third_name", type="string", example=null),
     *                      @OA\Property(property="last_name", type="string", example="Samter"),
     *                      @OA\Property(property="openemis_no", type="string", example="1548403445"),
     *                      @OA\Property(property="date_of_birth", type="string", example="2013-06-27"),
     *                      @OA\Property(property="date_of_death", type="string", example=null),
     *                      @OA\Property(property="identity_number", type="string", example=null),
     *                      @OA\Property(property="external_reference", type="string", example=null),
     *                      @OA\Property(property="gender_id", type="integer", example=1),
     *                      @OA\Property(property="gender_name", type="string", example="Male"),
     *                      @OA\Property(property="institution_id", type="integer", example=1),
     *                      @OA\Property(property="institution_code", type="string", example="K0001"),
     *                      @OA\Property(property="institution_name", type="string", example="Star Kindergarten"),
     *                      @OA\Property(property="education_grade_id", type="integer", example=119),
     *                      @OA\Property(property="education_grade_name", type="string", example="Kindergarten 1"),
     *                      @OA\Property(property="academic_period_id", type="integer", example=28),
     *                      @OA\Property(property="academic_period_name", type="string", example="2019"),
     *                      @OA\Property(property="institution_class_id", type="integer", example=488),
     *                      @OA\Property(property="institution_class_name", type="string", example="Kindergarten 1-A"),
     *                      @OA\Property(property="date", type="array",
     *                          @OA\Items(
     *                              type="object",
     *                              @OA\Property(property="period_id", type="null"),
     *                              @OA\Property(property="period_name", type="null"),
     *                              @OA\Property(property="subject_id", type="null"),
     *                              @OA\Property(property="subject_name", type="null"),
     *                              @OA\Property(property="absence_type_id", type="integer", example=2),
     *                              @OA\Property(property="absence_type_name", type="string", example="Absence - Unexcused"),
     *                              @OA\Property(property="student_absence_reason_id", type="null"),
     *                              @OA\Property(property="student_absence_reason_name", type="null"),
     *                              @OA\Property(property="comment", type="null"),
     *                              @OA\Property(property="date", type="string", example="2019-05-07")
     *                          )
     *                      ),
     *                      @OA\Property(property="modified_user_id", type="null"),
     *                      @OA\Property(property="modified", type="null"),
     *                      @OA\Property(property="created_user_id", type="integer", example=2),
     *                      @OA\Property(property="created", type="string", example="2019-11-25 18:23:42")
     *                  )
     *              )
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Unsuccessful.",
     *      )
     * )
     */
    public function getStudentAbsences(Request $request)
    {
        try {
            $data = $this->studentService->getStudentAbsences($request);
            return $this->sendSuccessResponse("Institutions Student Absences List Found", $data);

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution Student Absences List Not Found');
        }
    }


    /**
     * @OA\Get(
     *      path="/api/v4/institutions/{institutionId}/students/absences",
     *      summary="Get a list of institution students absences",
     *      description="Get a list of institution students absences",
     *      tags={"Institutions"},
     *      @OA\Parameter(
     *         name="institutionId",
     *         in="path",
     *         required=true,
     *         description="Instituton Id",
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="academic_period_id",
     *         in="query",
     *         required=false,
     *         description="Academic period id",
     *         @OA\Schema(type="integer", example="1")
     *      ),
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
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                      type="object",
     *                      @OA\Property(property="student_id", type="integer", example=10602),
     *                      @OA\Property(property="first_name", type="string", example="Francklin"),
     *                      @OA\Property(property="middle_name", type="string", example=null),
     *                      @OA\Property(property="third_name", type="string", example=null),
     *                      @OA\Property(property="last_name", type="string", example="Samter"),
     *                      @OA\Property(property="openemis_no", type="string", example="1548403445"),
     *                      @OA\Property(property="date_of_birth", type="string", example="2013-06-27"),
     *                      @OA\Property(property="date_of_death", type="string", example=null),
     *                      @OA\Property(property="identity_number", type="string", example=null),
     *                      @OA\Property(property="external_reference", type="string", example=null),
     *                      @OA\Property(property="gender_id", type="integer", example=1),
     *                      @OA\Property(property="gender_name", type="string", example="Male"),
     *                      @OA\Property(property="institution_id", type="integer", example=1),
     *                      @OA\Property(property="institution_code", type="string", example="K0001"),
     *                      @OA\Property(property="institution_name", type="string", example="Star Kindergarten"),
     *                      @OA\Property(property="education_grade_id", type="integer", example=119),
     *                      @OA\Property(property="education_grade_name", type="string", example="Kindergarten 1"),
     *                      @OA\Property(property="academic_period_id", type="integer", example=28),
     *                      @OA\Property(property="academic_period_name", type="string", example="2019"),
     *                      @OA\Property(property="institution_class_id", type="integer", example=488),
     *                      @OA\Property(property="institution_class_name", type="string", example="Kindergarten 1-A"),
     *                      @OA\Property(property="date", type="array",
     *                          @OA\Items(
     *                              type="object",
     *                              @OA\Property(property="period_id", type="null"),
     *                              @OA\Property(property="period_name", type="null"),
     *                              @OA\Property(property="subject_id", type="null"),
     *                              @OA\Property(property="subject_name", type="null"),
     *                              @OA\Property(property="absence_type_id", type="integer", example=2),
     *                              @OA\Property(property="absence_type_name", type="string", example="Absence - Unexcused"),
     *                              @OA\Property(property="student_absence_reason_id", type="null"),
     *                              @OA\Property(property="student_absence_reason_name", type="null"),
     *                              @OA\Property(property="comment", type="null"),
     *                              @OA\Property(property="date", type="string", example="2019-05-07")
     *                          )
     *                      ),
     *                      @OA\Property(property="modified_user_id", type="null"),
     *                      @OA\Property(property="modified", type="null"),
     *                      @OA\Property(property="created_user_id", type="integer", example=2),
     *                      @OA\Property(property="created", type="string", example="2019-11-25 18:23:42")
     *                  )
     *              )
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Unsuccessful.",
     *      )
     * )
     */
    public function getInstitutionStudentAbsences(Request $request, $institutionId)
    {
        try {
            $data = $this->studentService->getInstitutionStudentAbsences($request, $institutionId);
            return $this->sendSuccessResponse("Institutions Student Absences List Found", $data);

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution Student Absences List Not Found');
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v4/institutions/{institutionId}/students/{studentId}/absences",
     *      summary="Get details of institution student absences",
     *      description="Get details of institution student absences",
     *      tags={"Institutions"},
     *      @OA\Parameter(
     *         name="institutionId",
     *         in="path",
     *         required=true,
     *         description="Instituton Id",
     *         @OA\Schema(type="integer", example="6")
     *      ),
     *      @OA\Parameter(
     *         name="studentId",
     *         in="path",
     *         required=true,
     *         description="Student Id",
     *         @OA\Schema(type="integer", example="11763")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful.",
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="object",
     *                      @OA\Property(property="student_id", type="integer", example=10602),
     *                      @OA\Property(property="first_name", type="string", example="Francklin"),
     *                      @OA\Property(property="middle_name", type="string", example=null),
     *                      @OA\Property(property="third_name", type="string", example=null),
     *                      @OA\Property(property="last_name", type="string", example="Samter"),
     *                      @OA\Property(property="openemis_no", type="string", example="1548403445"),
     *                      @OA\Property(property="date_of_birth", type="string", example="2013-06-27"),
     *                      @OA\Property(property="date_of_death", type="string", example=null),
     *                      @OA\Property(property="identity_number", type="string", example=null),
     *                      @OA\Property(property="external_reference", type="string", example=null),
     *                      @OA\Property(property="gender_id", type="integer", example=1),
     *                      @OA\Property(property="gender_name", type="string", example="Male"),
     *                      @OA\Property(property="institution_id", type="integer", example=1),
     *                      @OA\Property(property="institution_code", type="string", example="K0001"),
     *                      @OA\Property(property="institution_name", type="string", example="Star Kindergarten"),
     *                      @OA\Property(property="education_grade_id", type="integer", example=119),
     *                      @OA\Property(property="education_grade_name", type="string", example="Kindergarten 1"),
     *                      @OA\Property(property="academic_period_id", type="integer", example=28),
     *                      @OA\Property(property="academic_period_name", type="string", example="2019"),
     *                      @OA\Property(property="institution_class_id", type="integer", example=488),
     *                      @OA\Property(property="institution_class_name", type="string", example="Kindergarten 1-A"),
     *                      @OA\Property(property="date", type="array",
     *                          @OA\Items(
     *                              type="object",
     *                              @OA\Property(property="period_id", type="null"),
     *                              @OA\Property(property="period_name", type="null"),
     *                              @OA\Property(property="subject_id", type="null"),
     *                              @OA\Property(property="subject_name", type="null"),
     *                              @OA\Property(property="absence_type_id", type="integer", example=2),
     *                              @OA\Property(property="absence_type_name", type="string", example="Absence - Unexcused"),
     *                              @OA\Property(property="student_absence_reason_id", type="null"),
     *                              @OA\Property(property="student_absence_reason_name", type="null"),
     *                              @OA\Property(property="comment", type="null"),
     *                              @OA\Property(property="date", type="string", example="2019-05-07")
     *                          )
     *                      ),
     *                      @OA\Property(property="modified_user_id", type="null"),
     *                      @OA\Property(property="modified", type="null"),
     *                      @OA\Property(property="created_user_id", type="integer", example=2),
     *                      @OA\Property(property="created", type="string", example="2019-11-25 18:23:42")
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Unsuccessful.",
     *      )
     * )
     */
    public function getInstitutionStudentAbsencesData(Request $request, $institutionId, $studentId)
    {
        try {
            $data = $this->studentService->getInstitutionStudentAbsencesData($request, $institutionId, $studentId);
            return $this->sendSuccessResponse("Institutions Student Absences Data Found", $data);

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution Student Absences Data Not Found');
        }
    }



    //POCOR-7547 Starts...
    /**
     * @OA\Get(
     *      path="/api/v4/attendance-mark-types/education-grades",
     *      summary="Get a list of education grades",
     *      description="Get a list of education grades",
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
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                      type="object",
     *                      @OA\Property(property="academic_period_id", type="integer", example=28),
     *                      @OA\Property(property="academic_period_name", type="string", example="2019"),
     *                      @OA\Property(property="education_grade_id", type="integer", example=119),
     *                      @OA\Property(property="education_grade_name", type="string", example="Kindergarten 1"),
     *                      @OA\Property(property="attendance_by", type="string", example="DAY"),
     *                      @OA\Property(property="period_name", type="string", example="Period 1"),
     *                      @OA\Property(property="attendance_per_day", type="interger", example=2),
     *                      @OA\Property(property="date_enabled", type="string", example="2023-12-31"),
     *                      @OA\Property(property="date_disabled", type="string", example="2023-12-31"),
     *                      @OA\Property(property="value", type="string", example="1"),
     *                      @OA\Property(property="day_configuration", type="string", example="Mark absent if one or more records absent"),
     *                  )
     *              )
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Unsuccessful.",
     *      )
     * )
     */
    public function getEducationGrades(Request $request)
    {
        try {
            $data = $this->studentService->getEducationGrades($request);
            return $this->sendSuccessResponse("Education Grade List Found", $data);

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Education Grade List Not Found');
        }
    }


    /**
     * @OA\Get(
     *      path="/api/v4/institutions/{institutionId}/classes/subjects",
     *      summary="Get a list of institution class subjects",
     *      description="Get a list of institution class subjects",
     *      tags={"Institutions"},
     *      @OA\Parameter(
     *         name="institutionId",
     *         in="path",
     *         required=true,
     *         description="Instituton Id",
     *         @OA\Schema(type="integer", example="6")
     *     ),
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
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                      type="object",
     *                      @OA\Property(property="academic_period_id", type="integer", example=28),
     *                      @OA\Property(property="institution_id", type="integer", example=6),
     *                      @OA\Property(property="institution_class_id", type="integer", example=119),
     *                      @OA\Property(property="institution_class_name", type="string", example="Kindergarten 1"),
     *                      @OA\Property(property="institution_subject_id", type="integer", example=40),
     *                      @OA\Property(property="institution_subject_name", type="string", example="Science"),
     *                      @OA\Property(property="education_subject_id", type="integer", example=2),
     *                      @OA\Property(property="education_grade_id", type="integer", example=13),
     *                      @OA\Property(property="total_male_students", type="integer", example=131),
     *                      @OA\Property(property="total_female_students", type="integer", example=13),
     *                      @OA\Property(property="modified_user_id", type="integer", example=null),
     *                      @OA\Property(property="modified", type="string", example=null),
     *                      @OA\Property(property="created_user_id", type="integer", example=2),
     *                      @OA\Property(property="created", type="string", example="2019-11-20 19:51:58")

     *                  )
     *              )
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Unsuccessful.",
     *      )
     * )
     */
    public function getClassesSubjects(Request $request, $institutionId)
    {
        try {
            $data = $this->studentService->getClassesSubjects($request, $institutionId);
            return $this->sendSuccessResponse("Class Subjects List Found", $data);

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Class Subjects List Not Found');
        }
    }

    /**
     * @OA\Post(
     *      path="/api/v4/institutions/classes/attendances",
     *      summary="Add class attendances",
     *      description="Add class attendances",
     *      tags={"Institutions"},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(property="academic_period_id", type="integer", example=33),
     *              @OA\Property(property="education_grade_id", type="integer", example=209),
     *              @OA\Property(property="institution_id", type="integer", example=6),
     *              @OA\Property(property="institution_class_id", type="integer", example=6),
     *              @OA\Property(property="date", type="string", example="2012-04-17"),
     *              @OA\Property(property="period", type="integer", example=6),
     *              @OA\Property(property="subject_id", type="integer", example=6),
     *          )
     *      ),
     *      @OA\Response(
     *           response=200,
     *          description="Successful.",
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="array",
     *                  @OA\Items()
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Unsuccessful.",
     *      )
     * )
     */
    public function addClassAttendances(ClassAttendanceAdd $request)
    {
        try {

            //For POCOR-7772 Start
            $checkPermission = checkPermission(['Institutions', 'StudentAttendances', 'edit'], ['institution_id' => $request['institution_id']]);

            if(!$checkPermission){
                return $this->sendAuthorizationErrorResponse();
            }
            //For POCOR-7772 End

            $data = $this->studentService->addClassAttendances($request);

            if($data == 1){
                return $this->sendSuccessResponse("Class attendances data added.", $data);
            } elseif($data == 2) {
                return $this->sendSuccessResponse("Class attendances data updated.", $data);
            } else {
                return $this->sendErrorResponse('Failed to add class attendance details.');
            }

        } catch (\Exception $e) {
            Log::error(
                'Failed to add data in DB.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Class Attendances Not Added');
        }
    }

    /**
     * @OA\Post(
     *      path="/api/v4/institutions/students/absences",
     *      summary="Add student absences",
     *      description="Add student absences",
     *      tags={"Institutions"},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(property="academic_period_id", type="integer", example=33),
     *              @OA\Property(property="education_grade_id", type="integer", example=209),
     *              @OA\Property(property="institution_id", type="integer", example=6),
     *              @OA\Property(property="institution_class_id", type="integer", example=6),
     *              @OA\Property(property="date", type="string", example="2012-04-17"),
     *              @OA\Property(property="period", type="integer", example=6),
     *              @OA\Property(property="subject_id", type="integer", example=6),
     *              @OA\Property(property="student_id", type="integer", example=611),
     *              @OA\Property(property="absence_type_id", type="integer", example=1),
     *              @OA\Property(property="student_absence_reason_id", type="integer", example=1),
     *              @OA\Property(property="comment", type="string", example="test"),
     *          )
     *      ),
     *      @OA\Response(
     *           response=200,
     *          description="Successful.",
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="array",
     *                  @OA\Items()
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Unsuccessful.",
     *      )
     * )
     */
    public function addStudentAbsences(StudentAbsenceAdd $request)
    {
        try {
            //For POCOR-7772 Start
            $checkPermission = checkPermission(['Institutions', 'StudentAttendances', 'edit'], ['institution_id' => $request['institution_id']]);

            if(!$checkPermission){
                return $this->sendAuthorizationErrorResponse();
            }
            //For POCOR-7772 End

            $data = $this->studentService->addStudentAbsences($request);
            if($data == 1){
                //POCOR-8631[START]
                $webhookController = app(WebhookController::class);
                $result = $webhookController->handleWebhookRequest($request);
                //POCOR-8631[END]
                return $this->sendSuccessResponse("Student absences data added.");
            } elseif($data == 2) {
                //POCOR-8631[START]
                $webhookController = app(WebhookController::class);
                $result = $webhookController->handleWebhookRequest($request);
                //POCOR-8631[END]
                return $this->sendSuccessResponse("Student absences data updated.");
            } elseif($data == 3) {
                return $this->sendErrorResponse("Student is not assigned to the class, grade and academic period for which attendance/absence is marked");
            } else {
                return $this->sendErrorResponse('Failed to add student absences details.');
            }

        } catch (\Exception $e) {
            Log::error(
                'Failed to add data in DB.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Student absences Not Added');
        }
    }

    /**
     * @OA\Post(
     *      path="/api/v4/institutions/staff/attendances",
     *      summary="Add staff attendances",
     *      description="Add staff attendances",
     *      tags={"Institutions"},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(property="academic_period_id", type="integer", example=33),
     *              @OA\Property(property="institution_id", type="integer", example=6),
     *              @OA\Property(property="date", type="string", example="2012-04-17"),
     *              @OA\Property(property="staff_id", type="integer", example=6),
     *          )
     *      ),
     *      @OA\Response(
     *           response=200,
     *          description="Successful.",
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="array",
     *                  @OA\Items()
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Unsuccessful.",
     *      )
     * )
     */
    public function addStaffAttendances(StaffAttendanceAdd $request)
    {
        // POCOR-8965 start
        try {
            $user = JWTAuth::user();
            $userId = (int) ($user->id ?? -1); // Ensure $userId is always an integer

// Check base permission for editing staff attendances
            $checkPermission = checkPermission(
                ['Institutions', 'InstitutionStaffAttendances', 'edit'],
                ['institution_id' => $request['institution_id']]
            );

            if ($checkPermission) {
                $staffId = (int) ($request->get('staff_id') ?? 0);

                // If the user is trying to edit their own attendance
                if ($staffId === $userId) {
                    $checkPermission = checkPermission(
                        ['Institutions', 'InstitutionStaffAttendances', 'ownedit'],
                        ['institution_id' => $request['institution_id']]
                    );
                }
                // If the user is trying to edit another staff member's attendance
                else {
                    $checkPermission = checkPermission(
                        ['Institutions', 'InstitutionStaffAttendances', 'otheredit'],
                        ['institution_id' => $request['institution_id']]
                    );
                }
            }

// Deny access if the final permission check fails
            // POCOR-8965 end
            if (!$checkPermission) {
                return $this->sendAuthorizationErrorResponse();
            }


            $data = $this->studentService->addStaffAttendances($request);

            if($data == 1){
                return $this->sendSuccessResponse("Staff attendances data added.");
            } elseif($data == 2) {
                return $this->sendSuccessResponse("Staff attendances data updated.");
            } else {
                return $this->sendErrorResponse('Failed to add staff attendances details.');
            }

        } catch (\Exception $e) {
            Log::error(
                'Failed to add data in DB.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Staff attendances Not Added');
        }
    }

    /**
     * @OA\Post(
     *      path="/api/v4/institutions/staff",
     *      summary="update staff",
     *      description="update staff",
     *      tags={"Institutions"},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(property="id", type="integer", example=33),
     *              @OA\Property(property="first_name", type="string", example="teststaffUser first name"),
     *              @OA\Property(property="last_name", type="string", example="teststaffUser last name"),
     *              @OA\Property(property="gender_id", type="integer", example=1),
     *              @OA\Property(property="date_of_birth", type="string", example="2000-01-01"),
     *          )
     *      ),
     *      @OA\Response(
     *           response=200,
     *          description="Successful.",
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="array",
     *                  @OA\Items()
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Unsuccessful.",
     *      )
     * )
     */
    public function updateStaffDetails(UpdateStaffDetails $request)
    {
        try {

            //For POCOR-7772 Start
            $checkPermission = checkPermission(['Institutions', 'Staff', 'add']);

            if(!$checkPermission){
                return $this->sendAuthorizationErrorResponse();
            }
            //For POCOR-7772 End

            $data = $this->studentService->updateStaffDetails($request);

            if($data == 1){
                return $this->sendSuccessResponse("Staff data updated.", $data);
            } elseif($data == 0){
                return $this->sendErrorResponse('Invalid user id.');
            } else {
                return $this->sendErrorResponse('Failed to update staff data details.');
            }

        } catch (\Exception $e) {
            Log::error(
                'Failed to update data in DB.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Staff data not updated');
        }
    }

    //POCOR-7547 Ends...


    //POCOR-8221 Starts...
    public function getStudentTransferData(Request $request, $institutionId, $studentId)
    {
        try {
            $params = $request->all();
            $data = $this->studentService->getStudentTransferData($params, $institutionId, $studentId);
            return $this->sendSuccessResponse("Student Transfer List Found", $data);

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Student Transfer List Not Found');
        }
    }


    public function addStudentTransferData(StudentTransferAddRequest $request, $institutionId)
    {
        try {
            $params = $request->all();

            $data = $this->studentService->addStudentTransferData($params, $institutionId);

            if($data == 0){
                return $this->sendErrorResponse('Invalid Institution Id.');
            } elseif($data == 1){
                return $this->sendSuccessResponse("Student Transfer In Added Successfully.");
            } elseif($data == 2){
                return $this->sendSuccessResponse("Student Transfer Out Added Successfully.");
            } else{
                return $this->sendErrorResponse('Failed to add student tranfer data.');
            }

        } catch (\Exception $e) {
            Log::error(
                'Failed to add student tranfer data.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to add student tranfer data.');
        }
    }
    //POCOR-8221 Ends...

    /**
     * @OA\Get(
     *     path="/api/v4/students/{openemis_no}/absences",
     *     summary="Get Student Absence Details",
     *     description="Retrieves student absence details for a given OpenEMIS number.",
     *     tags={"Institutions"},
     *     security={{"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="openemis_no",
     *         in="path",
     *         required=true,
     *         description="The OpenEMIS number of the student.",
     *         @OA\Schema(type="string", example="ST12345")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful retrieval of student absences.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="date", type="string", format="date", example="2024-03-01"),
     *                     @OA\Property(property="institution", type="string", example="Avory Primary School"),
     *                     @OA\Property(property="period", type="string", nullable=true, example=1),
     *                     @OA\Property(property="class", type="string", example="Primary 1-A"),
     *                     @OA\Property(property="subject", type="string", nullable=true, example="Creative Arts"),
     *                     @OA\Property(property="absence_type", type="string", example="Absence - Excused")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Student Absences Data Not Found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Student Absences Data Not Found")
     *         )
     *     )
     * )
    */
    public function getStudentAbsencesDetails(Request $request, $openemis_no)
    {
        try {
            $params = $request->all();
            $data = $this->studentService->getStudentAbsencesDetails($params, $openemis_no);
            if(count($data) > 0) {
                return $this->sendSuccessResponse("Student Absences Data Found", $data);
            } else {
                return $this->sendSuccessResponse("Student Absences Data Not Found", false);
            }
        } catch (\Exception $e) {
            Log::error(
                'Student Absences Data Not Found.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Student Absences Data Not Found');
        }
    }
}
