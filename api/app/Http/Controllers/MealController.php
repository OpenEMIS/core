<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\MealService;
use App\Http\Requests\MealStudentListRequest;
use App\Http\Requests\StudentMealImportRequest;
use App\Http\Requests\StudentMealExportRequest;
use App\Http\Requests\StudentMealImportTemplateRequest;
use App\Exports\StudentMealExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\StudentMealImport;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class MealController extends Controller
{
    protected $mealService;

    public function __construct(MealService $mealService) {
        $this->mealService = $mealService;
    }


    /**
     * @OA\Get(
     *      path="/api/v4/institutions/{institutionId}/meal-programmes",
     *      summary="Get a list of meal programmes by institution",
     *      description="Get a list of meal programmes by institution",
     *      tags={"Meals"},
     *      @OA\Parameter(
     *         name="institutionId",
     *         in="path",
     *         required=true,
     *         description="Id of the institution",
     *         @OA\Schema(type="integer", example=6)
     *      ),
     *      @OA\Parameter(
     *         name="academic_period_id",
     *         in="query",
     *         description="Id of the academic year",
     *         @OA\Schema(type="integer", example=30)
     *      ),
     *      @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="student_id")
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
     *                          @OA\Property(property="meal_programme_id", type="integer", example="2"),
     *                          @OA\Property(property="name", type="string", example="WFP")
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
    public function getMealInstitutionProgrammes(Request $request, $institutionId){
    
        try {
            $params = $request->all();
            $data = $this->mealService->getMealInstitutionProgrammes($params, $institutionId);
            return $this->sendSuccessResponse("Meal Institution Programmes Found.", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Meal Institution Programmes Found.');
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v4/meal-benefit-types",
     *      summary="Get a list of meal benefits type",
     *      description="Get a list of meal benefits type",
     *      tags={"Meals"},
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
     *                          @OA\Property(property="name", type="string", example="100%"),
     *                          @OA\Property(property="order", type="integer", example=1),
     *                          @OA\Property(property="visible", type="integer", example=1),
     *                          @OA\Property(property="default", type="integer", example=1),
     *                          @OA\Property(property="international_code", type="string", example=Null),
     *                          @OA\Property(property="national_code", type="string", example=Null),
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
    public function getMealBenefits(Request $request)
    {
        try {
            
            $data = $this->mealService->getMealBenefits($request);
            return $this->sendSuccessResponse("Meal Benefit Types List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Meal Benefits List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Meal Benefit Types List Not Found');
        }
    }


    /**
     * @OA\Get(
     *      path="/api/v4/institutions/{institutionId}/meal-students",
     *      summary="Get a list of meal students by institution",
     *      description="Get a list of meal students by institution",
     *      tags={"Meals"},
     *      @OA\Parameter(
     *         name="institutionId",
     *         in="path",
     *         required=true,
     *         description="Id of the institution",
     *         @OA\Schema(type="integer", example=6)
     *      ),
     *      @OA\Parameter(
     *         name="academic_period_id",
     *         in="query",
     *         required=true,
     *         description="Id of the academic year",
     *         @OA\Schema(type="integer", example=30)
     *      ),
     *      @OA\Parameter(
     *         name="institution_class_id",
     *         in="query",
     *         required=true,
     *         description="Id of the institution class",
     *         @OA\Schema(type="integer", example=52)
     *      ),
     *      @OA\Parameter(
     *         name="meal_program_id",
     *         in="query",
     *         required=true,
     *         description="Id of meal program",
     *         @OA\Schema(type="integer", example=3)
     *      ),
     *      @OA\Parameter(
     *         name="week_id",
     *         in="query",
     *         description="Id of week",
     *         @OA\Schema(type="integer", example=1)
     *      ),
     *      @OA\Parameter(
     *         name="week_start_day",
     *         in="query",
     *         description="Start day of week",
     *         @OA\Schema(type="integer", example=1)
     *      ),
     *      @OA\Parameter(
     *         name="week_end_day",
     *         in="query",
     *         description="End day of week",
     *         @OA\Schema(type="integer", example=4)
     *      ),
     *      @OA\Parameter(
     *         name="day_id",
     *         in="query",
     *         required=true,
     *         description="day Id",
     *         @OA\Schema(type="integer", example=1)
     *      ),
     *      @OA\Parameter(
     *         name="student_id",
     *         in="query",
     *         description="Student id",
     *         @OA\Schema(type="string", example="10ce5e7-e869-4323-8340")
     *      ),
     *      @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="Institution class student id",
     *         @OA\Schema(type="integer", example="410ce5e7-e869-4323-8340-165db3a2abc9")
     *      ),
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
     *                  @OA\Property(property="data", type="array",
     *                      @OA\Items(
     *                          type="object",
     *                          @OA\Property(property="academic_period_id", type="integer", example=30),
     *                          @OA\Property(property="institution_id", type="integer", example=6),
     *                          @OA\Property(property="institution_class_id", type="integer", example=441),
     *                          @OA\Property(property="marked_meal_id", type="integer", example=1),
     *                          @OA\Property(property="marked_meal_program_id", type="integer", example=1),
     *                          @OA\Property(property="marked_meal_benefit_id", type="integer", example=1),
     *                          @OA\Property(property="marked_meal_date", type="string", example="2022-01-01 10:32:20"),
     *                          @OA\Property(property="institution_meal_student_id", type="integer", example=1),
     *                          @OA\Property(property="meal_program_id", type="integer", example="1"),
     *                          @OA\Property(property="meal_benefit_id", type="integer", example=1),
     *                          @OA\Property(property="meal_received_id", type="string", example=1),
     *                          @OA\Property(property="meal_paid", type="date", example=1),
     *                          @OA\Property(property="meal_date", type="date", example="2022-01-01 10:32:20"),
     *                          @OA\Property(property="meal_program_name", type="string", example=""),
     *                          @OA\Property(property="meal_benefit_name", type="string", example=""),
     *                          @OA\Property(property="meal_received_name", type="string", example=""),
     *                          @OA\Property(property="student_id", type="integer", example=1),
     *                          @OA\Property(property="user", type="object",
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="username", type="string", example="admin"),
     *                              @OA\Property(property="password", type="string", example=""),
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
     *                              @OA\Property(property="super_admin", type="integer", example=1),
     *                              @OA\Property(property="status", type="integer", example=1),
     *                              @OA\Property(property="last_login", type="string", example=null),
     *                              @OA\Property(property="failed_logins", type="integer", example=0),
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
     *                      @OA\Property(property="default_meal_receive_id", type="integer", example=1),
     *                      )
     *                 ),
     *                 @OA\Property(property="url", type="object",
     *                          type="object",
     *                          @OA\Property(property="import", type="string", example="Institution/Institutions/eyJpZCI6Nn0.cake_session_id/ImportStudentMeals/add"),
     *                          @OA\Property(property="export", type="string", example="Institution/Institutions/eyJpZCI6Nn0.cake_session_id/StudentMeals/excel?institution_id=6&institution_class_id=572&education_grade_id=undefined&academic_period_id=32&day_id=1&attendance_period_id=undefined&week_start_day=&week_end_day=&subject_id=undefined&week_id=0"),
     *                          @OA\Property(property="help", type="string", example="")
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
    public function getMealStudents(MealStudentListRequest $request, $institutionId)
    {
        try {
            $options = $request->all();
            $data = $this->mealService->getMealStudents($options, $institutionId);
            return $this->sendSuccessResponse("Student Meals List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Student Meals List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Student Meals List Not Found');
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v4/institutions/{institutionId}/meal-distributions",
     *      summary="Get a list of meal distribution",
     *      description="Get a list of meal distribution",
     *      tags={"Meals"},
     *      @OA\Parameter(
     *         name="institutionId",
     *         in="path",
     *         required=true,
     *         description="Id of the institution",
     *         @OA\Schema(type="integer", example=6)
     *      ),
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
     *                          @OA\Property(property="code", type="string", example="Received"),
     *                          @OA\Property(property="name", type="string", example="Received")
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
    public function getMealDistributions(Request $request, $institutionId)
    {
        try {
            $options = $request->all();
            $data = $this->mealService->getMealDistributions($options, $institutionId);
            return $this->sendSuccessResponse("Meal Distribution List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Meals Distribution List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Meals Distribution List Not Found');
        }
    }


    //For POCOR-8078 Start...

    /**
     * @OA\Get(
     *      path="/api/v4/meal-programmes/{mealProgrammeId}",
     *      summary="Get meal program by id",
     *      description="Get meal program by id",
     *      tags={"Meals"},
     *      @OA\Parameter(
     *         name="mealProgrammeId",
     *         in="path",
     *         required=true,
     *         description="Id of the meal programme",
     *         @OA\Schema(type="integer", example=1)
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful.",
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="academic_period_id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="National Meal Programme"),
     *                 @OA\Property(property="code", type="integer", example="NMP"),
     *                 @OA\Property(property="type", type="integer", example=1),
     *                 @OA\Property(property="targeting", type="integer", example=1),
     *                 @OA\Property(property="start_date", type="date", example="2022-01-01"),
     *                 @OA\Property(property="end_date", type="date", example="2022-01-01"),
     *                 @OA\Property(property="amount", type="float", example="10.04"),
     *                 @OA\Property(property="implementer", type="integer", example=1),
     *                 @OA\Property(property="modified_user_id", type="integer", example=1),
     *                 @OA\Property(property="modified", type="date", example="2022-01-01 10:32:20"),
     *                 @OA\Property(property="created_user_id", type="integer", example=1),
     *                 @OA\Property(property="created", type="date", example="2022-01-01 10:32:20"),
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Unsuccessful.",
     *      )
     * )
     */
    public function getMealProgrammeData(Request $request, $programmeId)
    {
        try {
            $options = $request->all();
            $data = $this->mealService->getMealProgrammeData($options, $programmeId);

            return $this->sendSuccessResponse("Meal Programme Data Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Meal Programme Data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Meal Programme Data Not Found');
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v4/meal-targets",
     *      summary="Get a list of meal targets",
     *      description="Get a list of meal targets",
     *      tags={"Meals"},
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
     *                          @OA\Property(property="name", type="string", example="Individual"),
     *                          @OA\Property(property="order", type="integer", example=1),
     *                          @OA\Property(property="visible", type="integer", example=1),
     *                          @OA\Property(property="default", type="integer", example=1),
     *                          @OA\Property(property="international_code", type="string", example=Null),
     *                          @OA\Property(property="national_code", type="string", example=Null),
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
    public function getMealTargets(Request $request)
    {
        try {
            $options = $request->all();
            $data = $this->mealService->getMealTargets($options);

            return $this->sendSuccessResponse("Meal Targets List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Meal Targets List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Meal Targets List Not Found');
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v4/meal-implementers",
     *      summary="Get a list of meal implementers",
     *      description="Get a list of meal implementers",
     *      tags={"Meals"},
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
     *                          @OA\Property(property="name", type="string", example="Government"),
     *                          @OA\Property(property="order", type="integer", example=1),
     *                          @OA\Property(property="visible", type="integer", example=1),
     *                          @OA\Property(property="editable", type="integer", example=1),
     *                          @OA\Property(property="default", type="integer", example=1),
     *                          @OA\Property(property="international_code", type="string", example=Null),
     *                          @OA\Property(property="national_code", type="string", example=Null),
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
    public function getMealImplementers(Request $request)
    {
        try {
            $options = $request->all();
            $data = $this->mealService->getMealImplementers($options);

            return $this->sendSuccessResponse("Meal Implementers List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Meal Implementers List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Meal Implementers List Not Found');
        }
    }


    /**
     * @OA\Get(
     *      path="/api/v4/meal-nutritions",
     *      summary="Get a list of meal nutritions",
     *      description="Get a list of meal nutritions",
     *      tags={"Meals"},
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
     *                          @OA\Property(property="name", type="string", example="Energy"),
     *                          @OA\Property(property="order", type="integer", example=1),
     *                          @OA\Property(property="visible", type="integer", example=1),
     *                          @OA\Property(property="default", type="integer", example=1),
     *                          @OA\Property(property="international_code", type="string", example=Null),
     *                          @OA\Property(property="national_code", type="string", example=Null),
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
    public function getMealNutritions(Request $request)
    {
        try {
            $options = $request->all();
            $data = $this->mealService->getMealNutritions($options);

            return $this->sendSuccessResponse("Meal Nutritions List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Meal Nutritions List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Meal Nutritions List Not Found');
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v4/meal-ratings",
     *      summary="Get a list of meal ratings",
     *      description="Get a list of meal ratings",
     *      tags={"Meals"},
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
     *                          @OA\Property(property="name", type="string", example="1"),
     *                          @OA\Property(property="order", type="integer", example=1),
     *                          @OA\Property(property="visible", type="integer", example=1),
     *                          @OA\Property(property="editable", type="integer", example=1),
     *                          @OA\Property(property="default", type="integer", example=1),
     *                          @OA\Property(property="international_code", type="string", example=Null),
     *                          @OA\Property(property="national_code", type="string", example=Null),
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
    public function getMealRatings(Request $request)
    {
        try {
            $options = $request->all();
            $data = $this->mealService->getMealRatings($options);

            return $this->sendSuccessResponse("Meal Ratings List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Meal Ratings List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Meal Ratings List Not Found');
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v4/meal-statuses",
     *      summary="Get a list of meal statuses",
     *      description="Get a list of meal statuses",
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
     *      tags={"Meals"},
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
     *                          @OA\Property(property="name", type="string", example="Early"),
     *                          @OA\Property(property="order", type="integer", example=1),
     *                          @OA\Property(property="visible", type="integer", example=1),
     *                          @OA\Property(property="international_code", type="string", example=Null),
     *                          @OA\Property(property="national_code", type="string", example=Null),
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
    public function getMealStatusTypes(Request $request)
    {
        try {
            $options = $request->all();
            $data = $this->mealService->getMealStatusTypes($options);

            return $this->sendSuccessResponse("Meal Status Types List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Meal Status Types List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Meal Status Types List Not Found');
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v4/meal-food-types",
     *      summary="Get a list of meal food types",
     *      description="Get a list of meal food types",
     *      tags={"Meals"},
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
     *                          @OA\Property(property="name", type="string", example="Vegetable"),
     *                          @OA\Property(property="order", type="integer", example=1),
     *                          @OA\Property(property="visible", type="integer", example=1),
     *                          @OA\Property(property="default", type="integer", example=1),
     *                          @OA\Property(property="international_code", type="string", example=Null),
     *                          @OA\Property(property="national_code", type="string", example=Null),
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
    public function getMealFoodTypes(Request $request)
    {
        try {
            $options = $request->all();
            $data = $this->mealService->getMealFoodTypes($options);

            return $this->sendSuccessResponse("Meal Food Types List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Meal Food Types List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Meal Food Types List Not Found');
        }
    }
    //For POCOR-8078 End...

    //For POCOR-8348 Start...

    /**
     * @OA\Post(
     *     path="/api/v4/institutions/students/meals/import",
     *     summary="Import student meal data",
     *     description="Import meal data for students from an Excel file for a specific institution and class.",
     *     tags={"Meals"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"file", "institution_class_id", "institution_id"},
     *                 @OA\Property(
     *                     property="file",
     *                     type="string",
     *                     format="binary",
     *                     description="The Excel file containing meal data",
     *                     example="(binary)"
     *                 ),
     *                 @OA\Property(
     *                     property="institution_class_id",
     *                     type="integer",
     *                     description="The ID of the institution class",
     *                     example="591"
     *                 ),
     *                 @OA\Property(
     *                     property="institution_id",
     *                     type="integer",
     *                     description="The ID of the institution",
     *                     example="6"
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
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="total_count", type="integer", example=2),
     *                 @OA\Property(
     *                     property="records_added",
     *                     type="object",
     *                     @OA\Property(property="count", type="integer", example=2),
     *                     @OA\Property(
     *                         property="rows",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="row_number", type="integer", example=2),
     *                             @OA\Property(
     *                                 property="data",
     *                                 type="object",
     *                                 @OA\Property(property="Date ( DD/MM/YYYY )", type="string", example="25/06/2024"),
     *                                 @OA\Property(property="OpenEMIS ID", type="integer", example=2382817279),
     *                                 @OA\Property(property="Meal Programme Code", type="string", example="Meal Programme"),
     *                                 @OA\Property(property="Meal Received Code", type="string", example="None"),
     *                                 @OA\Property(property="Meal Benefit Name", type="string", example=null),
     *                                 @OA\Property(property="Comment", type="string", example="test")
     *                             ),
     *                             @OA\Property(property="errors", type="array", @OA\Items(type="string"))
     *                         )
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="records_updated",
     *                     type="object",
     *                     @OA\Property(property="count", type="integer", example=0),
     *                     @OA\Property(property="rows", type="array", @OA\Items(type="object"))
     *                 ),
     *                 @OA\Property(
     *                     property="records_failed",
     *                     type="object",
     *                     @OA\Property(property="count", type="integer", example=0),
     *                     @OA\Property(property="rows", type="array", @OA\Items(type="object"))
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
    public function getStudentMealImport(StudentMealImportRequest $request)
    {
        try {
            $params = $request->all();
            $data = $this->mealService->getStudentMealImport($params);
            if(!is_array($data)){
                if(isset($data) && $data == 1){
                    return $this->sendErrorResponse('Invalid file extension.');
                } elseif(isset($data) && $data == 2){
                    return $this->sendErrorResponse('Header is not present.');
                } elseif(isset($data) && $data == 3){
                    return $this->sendErrorResponse('Imported file is empty.');
                } elseif(isset($data) && $data == 4){
                    return $this->sendErrorResponse('Not a valid heading.');
                } elseif(isset($data) && $data == 5){
                    return $this->sendErrorResponse('Institution is not linked with Institution Class.');
                } elseif(isset($data) && $data == 6){
                    return $this->sendErrorResponse('No current Academic Period is set in DB.');
                } elseif(isset($data) && $data == 7){
                    return $this->sendErrorResponse('Uploaded file exceeds maximum no of records limit ('.config("constantvalues.importExcelRules.maxRows").').');
                } else {
                    return $this->sendErrorResponse('Student meals not imported.');
                }
            } else {
                return $this->sendSuccessResponse("Student meals imported.", $data);
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to import students meals in DB.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to import students meals in DB.');
        }
    }


    /**
     * @OA\Get(
     *     path="/api/v4/institutions/students/meals/export",
     *     summary="Export student meal data",
     *     description="Export student meal data for a specified academic period, day, week, class, and meal program.",
     *     tags={"Meals"},
     *     @OA\Parameter(
     *         name="academic_period_id",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="integer", example="33"),
     *         description="The ID of the academic period"
     *     ),
     *     @OA\Parameter(
     *         name="day_id",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string", format="date", example="2024-06-18"),
     *         description="The specific day for which meal data is being exported"
     *     ),
     *     @OA\Parameter(
     *         name="week_id",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="integer", example="25"),
     *         description="The ID of the week"
     *     ),
     *     @OA\Parameter(
     *         name="week_start_day",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string", format="date", example="2024-06-17"),
     *         description="The start day of the week"
     *     ),
     *     @OA\Parameter(
     *         name="week_end_day",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string", format="date", example="2024-06-23"),
     *         description="The end day of the week"
     *     ),
     *     @OA\Parameter(
     *         name="institution_class_id",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="integer", example="591"),
     *         description="The ID of the institution class"
     *     ),
     *     @OA\Parameter(
     *         name="meal_program_id",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="integer", example="3"),
     *         description="The ID of the meal program"
     *     ),
     *     @OA\Parameter(
     *         name="institution_id",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="integer", example="6"),
     *         description="The ID of the institution"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="string", format="binary")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Unsuccessful."
     *     )
     * )
     */
    public function getStudentMealExport(StudentMealExportRequest $request)
    {
        try {
            $params = $request->all();
            $str = time();
            $fileName = 'StudentMeals_'.$str.'.xlsx';
            return Excel::download(new StudentMealExport($params), $fileName);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to exported students meals from DB.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to exported students meals from DB.');
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v4/institutions/students/meals/import/template",
     *     summary="Get meal import template",
     *     description="Retrieve the meal import template for a specific institution and class.",
     *     tags={"Meals"},
     *     @OA\Parameter(
     *         name="institution_id",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="integer", example="6"),
     *         description="The ID of the institution"
     *     ),
     *     @OA\Parameter(
     *         name="institution_class_id",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="integer", example="591"),
     *         description="The ID of the institution class"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="string", format="binary")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Unsuccessful."
     *     )
     * )
     */
    public function getStudentMealImportTemplate(StudentMealImportTemplateRequest $request)
    {
        try {
            $params = $request->all();

            $fileName='OpenEMIS_Core_Import_Institution_Meal_Students_Template.xlsx';

            $filePath = public_path('templates').'/'.$fileName;

            if(file_exists($filePath)){
                $templatePath = public_path('storage/templates');
                $templateFile = $templatePath.'/'.$fileName;
                

                if(!File::exists($templatePath)){
                    File::makeDirectory($templatePath, 0775, true, true);
                }

                if (!File::exists($templateFile)) {
                    File::copy($filePath, $templateFile);
                }

            } else {
                return $this->sendErrorResponse('Import template not found.');
            }

            $spreadsheet = IOFactory::load($templateFile);
            
            // Select the 'References' sheet (assuming it's the second sheet)
            $sheet = $spreadsheet->getSheetByName('References');
            
            if (!$sheet) {
                return $this->sendErrorResponse('Reference sheet not found in import template.');
            }  

            $getDataForSheet = $this->mealService->getDataForSheet($params);

            // Write data to the sheet
            $row = 4; // Assuming the first row contains headings
            foreach ($getDataForSheet as $rowData) {
                $column = 'A';
                foreach ($rowData as $cellData) {
                    $sheet->setCellValue($column . $row, $cellData);
                    $column++;
                }
                $row++;
            }

            // Save the modified Excel file
            $writer = new Xlsx($spreadsheet);
            $writer->save($templateFile);

            return response()->download($templateFile);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch student meals import template data from DB.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            
            return $this->sendErrorResponse('Failed to fetch student meals import template data from DB.');
        }
    }

    //For POCOR-8348 End...
}
