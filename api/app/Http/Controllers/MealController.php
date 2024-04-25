<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\MealService;
use App\Http\Requests\MealStudentListRequest;

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
     *                  @OA\Property(property="total", type="integer", example="1")
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
     *      tags={"Meals"},
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
     *                  @OA\Property(property="total", type="integer", example="1")
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
     *      tags={"Meals"},
     *      @OA\Parameter(
     *         name="institutionId",
     *         in="path",
     *         required=true,
     *         description="Id of the institution",
     *         @OA\Schema(type="integer", example=6)
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
     *                          @OA\Property(property="code", type="string", example="Received"),
     *                          @OA\Property(property="name", type="string", example="Received")
     *                      )
     *                  ),
     *                  @OA\Property(property="total", type="integer", example="1")
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
     *      tags={"Meals"},
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
     *                  @OA\Property(property="total", type="integer", example="1")
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
     *      tags={"Meals"},
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
     *                  @OA\Property(property="total", type="integer", example="1")
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
     *      tags={"Meals"},
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
     *                  @OA\Property(property="total", type="integer", example="1")
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
     *      tags={"Meals"},
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
     *                  @OA\Property(property="total", type="integer", example="1")
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
     *                  @OA\Property(property="total", type="integer", example="4")
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
     *      tags={"Meals"},
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
     *                  @OA\Property(property="total", type="integer", example="4")
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
}