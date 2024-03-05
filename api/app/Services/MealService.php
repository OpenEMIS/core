<?php

namespace App\Services;

use App\Http\Controllers\Controller;
use App\Repositories\MealRepository;
use JWTAuth;
use Illuminate\Support\Facades\Log;

class MealService extends Controller
{

    protected $mealRepository;

    public function __construct(MealRepository $mealRepository) {
        $this->mealRepository = $mealRepository;
    }

    public function getMealInstitutionProgrammes($params, $institutionId){
    
        try {
            $data = $this->mealRepository->getMealInstitutionProgrammes($params, $institutionId);
            return $data;
            
        } catch (\Exception $e) {
            dd($e);
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Meal Institution Programmes Found.');
        }
    }


    public function getMealBenefits($request)
    {
        try {
            
            $data = $this->mealRepository->getMealBenefits($request);
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Meal Benefits List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Meal Benefit Types List Not Found');
        }
    }



    public function getMealStudents($options, $institutionId)
    {
        try {
            
            $data = $this->mealRepository->getMealStudents($options, $institutionId);
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Student Meals List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Student Meals List Not Found');
        }
    }


    public function getMealDistributions($options, $institutionId)
    {
        try {
            $data = $this->mealRepository->getMealDistributions($options, $institutionId);
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Meals Distribution List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Meals Distribution List Not Found');
        }
    }


    //For POCOR-8078 Start...
    public function getMealProgrammeData($options, $programmeId)
    {
        try {
            $data = $this->mealRepository->getMealProgrammeData($options, $programmeId);

            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Meal Programme Data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Meal Programme Data Not Found');
        }
    }


    public function getMealTargets($options)
    {
        try {
            $data = $this->mealRepository->getMealTargets($options);

            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Meal Targets List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Meal Targets List Not Found');
        }
    }
    //For POCOR-8078 End...
}
