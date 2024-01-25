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
}
