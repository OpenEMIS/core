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
