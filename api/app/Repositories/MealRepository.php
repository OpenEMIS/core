<?php

namespace App\Repositories;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\MealProgrammes;
use App\Models\MealBenefits;
use App\Models\InstitutionClassStudents;
use App\Models\ConfigItem;
use App\Models\MealReceived;
use App\Models\MealTargetType;
use App\Models\MealImplementer;
use App\Models\MealNutrition;
use App\Models\MealRating;
use App\Models\MealStatusType;
use App\Models\FoodType;
use App\Models\Manual;
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



    public function getMealStudents($options, $institutionId)
    {
        try {
            //$options = $request->all();
            $institutionId = $institutionId;
            $mealProgramId = $options['meal_program_id'];
            $institutionClassId = $options['institution_class_id'];
            $academicPeriodId = $options['academic_period_id'];
            $weekId = $options['week_id']??0;
            $weekStartDay = $options['week_start_day']??NULL;
            $weekEndDay = $options['week_end_day']??NULL;
            $day = $options['day_id'];
            $ID = $options['id']??Null;
            $studentID = $options['student_id']??Null;

            $resp = [];
            $query = InstitutionClassStudents::join('student_statuses', 'student_statuses.id', '=', 'institution_class_students.student_status_id')
                    ->join('security_users', 'security_users.id', '=', 'institution_class_students.student_id')
                    ->with('user')
                    ->where('student_statuses.code', 'CURRENT')
                    ->where([
                        'institution_class_students.academic_period_id' => $academicPeriodId,
                        'institution_class_students.institution_class_id' => $institutionClassId,
                        'institution_class_students.institution_id' => $institutionId,
                    ]);

            if (!empty($studentID)) {
                if (intval($studentID) > 0) {
                    $query = $query->where('student_id', $studentID);
                }
            }

            if (!empty($ID)) {
                if (intval($ID) > 0) {
                    $query = $query->where('id', $ID);
                }
            }

            $query = $query->orderBy('security_users.first_name')->orderBy('security_users.last_name');

            $default_meal_receive_id = $this->getDefaultMealReceiveID();

            if ($day != -1) {
                $query = $query->leftJoin('student_meal_marked_records', function ($q) use ($mealProgramId, $day) {
                    $q->on('institution_class_students.institution_class_id', '=', 'student_meal_marked_records.institution_class_id')
                        ->on('institution_class_students.institution_id', '=', 'student_meal_marked_records.institution_id')
                        ->where('student_meal_marked_records.meal_programmes_id', $mealProgramId)
                        ->where('student_meal_marked_records.date', '=', $day);
                })
                ->leftJoin('institution_meal_students', function ($q) use($mealProgramId, $day) {
                    $q->on('institution_meal_students.institution_class_id', '=', 'institution_class_students.institution_class_id')
                    ->on('institution_meal_students.student_id', '=', 'institution_class_students.student_id')
                    ->on('institution_meal_students.institution_id', '=', 'institution_class_students.institution_id')
                    ->where('institution_meal_students.meal_programmes_id', $mealProgramId)
                    ->where('institution_meal_students.date', '=', $day);
                })
                ->leftJoin('meal_programmes', 'meal_programmes.id', '=', 'institution_meal_students.meal_programmes_id')
                ->leftJoin('meal_received', 'meal_received.id', '=', 'institution_meal_students.meal_received_id')
                ->leftJoin('meal_benefits', 'meal_benefits.id', '=', 'institution_meal_students.meal_benefit_id')
                ->select(
                    'institution_class_students.academic_period_id',
                    'institution_class_students.institution_id',
                    'institution_class_students.institution_class_id',
                    'student_meal_marked_records.id as marked_meal_id',
                    'student_meal_marked_records.meal_programmes_id as marked_meal_program_id',
                    'student_meal_marked_records.meal_benefit_id as marked_meal_benefit_id',
                    'student_meal_marked_records.date as marked_meal_date',
                    'institution_meal_students.id as institution_meal_student_id',
                    'institution_meal_students.meal_programmes_id as meal_program_id',
                    'institution_meal_students.meal_benefit_id as meal_benefit_id',
                    'institution_meal_students.meal_received_id as meal_received_id',
                    'institution_meal_students.paid as meal_paid',
                    'institution_meal_students.date as meal_date',
                    'meal_programmes.name as meal_program_name',
                    'meal_benefits.name as meal_benefit_name',
                    'meal_received.name as meal_received_name',
                    'institution_class_students.student_id as student_id',
                )
                //->select(DB::raw("$default_meal_receive_id as default_meal_receive_id"))
                ->groupby('institution_class_students.student_id')
                ->get()
                ->toArray();

                $total = 0;
                
                if(count($query) > 0){
                    $total = count($query);
                    foreach($query as $key => $q){
                        $resp['data'][$key] = $q;
                        $resp['data'][$key]['default_meal_receive_id'] = $default_meal_receive_id??0;
                        
                    }
                }
                $resp['total'] = $total;


                //For POCOR-8210 Start...
                $helpUrl = "";
                $getHelpUrl = $this->getHelpUrl();
                if($getHelpUrl){
                    $helpUrl = $getHelpUrl;
                }

                $insId = '{"id":'.$institutionId.'}';
                $encodedInstitutionID = base64_encode($insId);
                $encodedInstitutionID = rtrim($encodedInstitutionID, "=");
                
                $resp['url'] = [
                    'import' => 'Institution/Institutions/'.$encodedInstitutionID.'.cake_session_id/ImportStudentMeals/add',

                    'export' => 'Institution/Institutions/'.$encodedInstitutionID.'.cake_session_id/StudentMeals/excel?institution_id='.$institutionId.'&institution_class_id='.$institutionClassId.'&education_grade_id=undefined&academic_period_id='.$academicPeriodId.'&day_id='.$day.'&attendance_period_id=undefined&week_start_day='.$weekStartDay.'&week_end_day='.$weekEndDay.'&subject_id=undefined&week_id='.$weekId,
                    'help' => $helpUrl
                ];
                //For POCOR-8210 End...

                return $resp;
            } else {
                return $resp;
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Student Meals List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            
            return $this->sendErrorResponse('Student Meals List Not Found');
        }
    }


    public function getDefaultMealReceiveID()
    {
        try {
            $configItem = ConfigItem::where('code', 'DefaultDeliveryStatus')->first();
            $DefaultDeliveryStatus = $configItem->value??"";
            $mealReceived = MealReceived::where('name', $DefaultDeliveryStatus)->first();

            $default_meal_receive_id = $mealReceived->id??0;
            return $default_meal_receive_id;

        } catch (\Exception $e) {
            Log::error(
                'Failed in getDefaultMealReceiveID.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return false;
        }
    }


    public function getMealDistributions($options, $institutionId)
    {
        try {
            $list = MealReceived::get()->toArray();
            $total = 0;
            $resp = [];

            if(count($list) > 0){
                $total = count($list);
                $resp['data'] = $list;
                $resp['total'] = $total;
            }
            return $resp;
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
            $data = MealProgrammes::where('id', $programmeId)->first();

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
            $list = MealTargetType::get()->toArray();
            $total = 0;
            $resp = [];

            if(count($list) > 0){
                $total = count($list);
                $resp['data'] = $list;
                $resp['total'] = $total;
            }
            return $resp;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Meal Targets List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Meal Targets List Not Found');
        }
    }


    public function getMealImplementers($options)
    {
        try {
            $list = MealImplementer::get()->toArray();
            $total = 0;
            $resp = [];

            if(count($list) > 0){
                $total = count($list);
                $resp['data'] = $list;
                $resp['total'] = $total;
            }
            return $resp;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Meal Implementers List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Meal Implementers List Not Found');
        }
    }



    public function getMealNutritions($options)
    {
        try {
            $list = MealNutrition::get()->toArray();
            $total = 0;
            $resp = [];

            if(count($list) > 0){
                $total = count($list);
                $resp['data'] = $list;
                $resp['total'] = $total;
            }
            return $resp;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Meal Nutritions List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Meal Nutritions List Not Found');
        }
    }


    public function getMealRatings($options)
    {
        try {
            $list = MealRating::get()->toArray();
            $total = 0;
            $resp = [];

            if(count($list) > 0){
                $total = count($list);
                $resp['data'] = $list;
                $resp['total'] = $total;
            }
            return $resp;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Meal Ratings List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Meal Ratings List Not Found');
        }
    }


    public function getMealStatusTypes($options)
    {
        try {
            $list = MealStatusType::get()->toArray();
            $total = 0;
            $resp = [];

            if(count($list) > 0){
                $total = count($list);
                $resp['data'] = $list;
                $resp['total'] = $total;
            }
            return $resp;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Meal Status Types List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Meal Status Types List Not Found');
        }
    }


    public function getMealFoodTypes($options)
    {
        try {
            $list = FoodType::get()->toArray();
            $total = 0;
            $resp = [];

            if(count($list) > 0){
                $total = count($list);
                $resp['data'] = $list;
                $resp['total'] = $total;
            }
            return $resp;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Meal Food Types List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Meal Food Types List Not Found');
        }
    }

    //For POCOR-8078 End...


    //For POCOR-8210 Start...       
    public function getHelpUrl()
    {
        try {
            $manual = Manual::where('category', 'Students - Meal')->first();
            if($manual){
                $url = $manual->url;

                if(isset($url) && $url != ""){
                    return $url;
                }
            }
            return false;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Help Button Url.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return false;
        }
    }
    //For POCOR-8210 End...

}