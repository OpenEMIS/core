<?php

namespace App\Repositories;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\MealProgrammes;
use App\Models\MealBenefits;
use Carbon\Carbon;
use JWTAuth;


class MealRepository extends Controller
{

    public function getMealInstitutionProgrammes($params, $institutionId){
    
        try {
            $academic_period_id = $params['academic_period_id']??0;

            $list = MealProgrammes::select('meal_institution_programmes.id', 'meal_programmes.id as meal_programme_id', 'meal_programmes.name')
                ->join('meal_institution_programmes', 'meal_institution_programmes.meal_programme_id', '=', 'meal_programmes.id')
                ->where('meal_institution_programmes.institution_id', $institutionId)
                ->where('meal_programmes.academic_period_id', $academic_period_id)
                ->get()
                ->toArray();
            $total = count($list);

            $resp['data'] = $list;
            $resp['total'] = $total;

            return $resp;
        } catch (\Exception $e) {
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
            $params = $request->all();

            $mealBenefits = MealBenefits::get();
            
            $total = count($mealBenefits);
            
            $resp['data'] = $mealBenefits;
            $resp['total'] = $total;

            return $resp;
        } catch (\Exception $e) {
            Log::error(
                'Failed to get Meal Benefits List.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get Meal Benefits List.');
        }
    }



    public function getMealStudents($request)
    {
        try {
            
            $params = $request->all();
            dd($params);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Student Meals List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Student Meals List Not Found');
        }
    }

}