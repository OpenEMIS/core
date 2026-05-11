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
use App\Models\SecurityUsers;
use App\Models\InstitutionStudent;
use App\Models\InstitutionMealStudents;
use App\Models\InstitutionClasses;
use App\Models\AcademicPeriod;
use Carbon\Carbon;
//use JWTAuth;
//use File;
use App\Imports\StudentMealImport;
use App\Exports\StudentMealExport;
use \PhpOffice\PhpSpreadsheet\Shared\Date;

use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Tymon\JWTAuth\Facades\JWTAuth;

class MealRepository extends Controller
{

    public function getMealInstitutionProgrammes($params, $institutionId){

        try {
            $academic_period_id = $params['academic_period_id']??0;

            $list = MealProgrammes::select('meal_institution_programmes.id', 'meal_programmes.id as meal_programme_id', 'meal_programmes.name')
                ->join('meal_institution_programmes', 'meal_institution_programmes.meal_programme_id', '=', 'meal_programmes.id')
                ->where('meal_institution_programmes.institution_id', $institutionId)
                ->where('meal_programmes.academic_period_id', $academic_period_id);

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $list = $list->orderBy($col, $orderBy);
            }

            if (isset($params['limit'])) {
                $list = $list->paginate($params['limit']);
            } else {
                $list = $list->get();
            }

            $list = $list->toArray();

            if (isset($params['limit'])) {
                $resp = $list;
            } else {
                $resp['data'] = $list;
            }

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

            $list = new MealBenefits;

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $list = $list->orderBy($col, $orderBy);
            }

            if (isset($params['limit'])) {
                $mealBenefits = $list->paginate($params['limit']);
                $resp = $mealBenefits;
            } else {
                $mealBenefits = $list->get();
                $resp['data'] = $mealBenefits;
            }

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
                    $query = $query->where('institution_class_students.id', $ID);
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
                ->groupby('institution_class_students.student_id');


                if(isset($options['order'])){
                    $orderBy = $options['order_by']??"ASC";
                    $col = $options['order'];
                    $query = $query->orderBy($col, $orderBy);
                }

                if (isset($options['limit'])) {
                    $query = $query->paginate($options['limit']);
                } else {
                    $query = $query->get();
                }
                if(count($query) > 0){

                    if (isset($options['limit'])) {
                        $query->getCollection()->transform(function ($item, $key) use ($default_meal_receive_id) {
                           $item->default_meal_receive_id = $default_meal_receive_id;
                           return  $item;
                        });
                        $resp  = $query->toArray();
                    } else {
                        $query = $query->map(
                            function ($item, $key) use ($default_meal_receive_id) {
                                $item->default_meal_receive_id = $default_meal_receive_id;
                                return  $item;
                            }
                        );
                        $resp['data'] = $query;
                    }
                }

                //For POCOR-8210 Start...
                $helpUrl = "";
                $getHelpUrl = $this->getHelpUrl();
                if($getHelpUrl){
                    $helpUrl = $getHelpUrl;
                }

                //POCOR-9594: start - encode institution_class_id, academic_period_id, meal_programme_id so ImportStudentMealsTable::beforeAction can read them via getQueryString()
                $insId = '{"id":'.$institutionId.',"institution_id":'.$institutionId.',"institution_class_id":'.$institutionClassId.',"academic_period_id":'.$academicPeriodId.',"meal_programme_id":'.$mealProgramId.'}';
                //POCOR-9594: end
                $encodedInstitutionID = base64_encode($insId);
                $encodedInstitutionID = rtrim($encodedInstitutionID, "=");
                $urls = [
                    'import' => 'Institution/Institutions/'.$encodedInstitutionID.'.cake_session_id/ImportStudentMeals/add',

                    'export' => 'Institution/Institutions/'.$encodedInstitutionID.'.cake_session_id/StudentMeals/excel?institution_id='.$institutionId.'&institution_class_id='.$institutionClassId.'&education_grade_id=undefined&academic_period_id='.$academicPeriodId.'&day_id='.$day.'&attendance_period_id=undefined&week_start_day='.$weekStartDay.'&week_end_day='.$weekEndDay.'&subject_id=undefined&week_id='.$weekId,
                    'help' => $helpUrl
                ];
                $resp['url'] = $urls;
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

            $resp = [];

            $list = new MealReceived;

            if(isset($options['order'])){
                $orderBy = $options['order_by']??"ASC";
                $col = $options['order'];
                $list = $list->orderBy($col, $orderBy);
            }

            if (isset($options['limit'])) {
                $list = $list->paginate($options['limit'])->toArray();
                $resp = $list;
            } else {
                $list = $list->get()->toArray();
                $resp['data'] = $list;
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
            $resp = [];

            $list = new MealTargetType;

            if(isset($options['order'])){
                $orderBy = $options['order_by']??"ASC";
                $col = $options['order'];
                $list = $list->orderBy($col, $orderBy);
            }

            if (isset($options['limit'])) {
                $list = $list->paginate($options['limit'])->toArray();
                $resp = $list;
            } else {
                $list = $list->get()->toArray();
                $resp['data'] = $list;
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
            $resp = [];

            $list = new MealImplementer;

            if(isset($options['order'])){
                $orderBy = $options['order_by']??"ASC";
                $col = $options['order'];
                $list = $list->orderBy($col, $orderBy);
            }

            if (isset($options['limit'])) {
                $list = $list->paginate($options['limit'])->toArray();
                $resp = $list;
            } else {
                $list = $list->get()->toArray();
                $resp['data'] = $list;
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
            $resp = [];

            $list = new MealNutrition;

            if(isset($options['order'])){
                $orderBy = $options['order_by']??"ASC";
                $col = $options['order'];
                $list = $list->orderBy($col, $orderBy);
            }

            if (isset($options['limit'])) {
                $list = $list->paginate($options['limit'])->toArray();
                $resp = $list;
            } else {
                $list = $list->get()->toArray();
                $resp['data'] = $list;
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
            $resp = [];

            $list = new MealRating;

            if(isset($options['order'])){
                $orderBy = $options['order_by']??"ASC";
                $col = $options['order'];
                $list = $list->orderBy($col, $orderBy);
            }

            if (isset($options['limit'])) {
                $list = $list->paginate($options['limit'])->toArray();
                $resp = $list;
            } else {
                $list = $list->get()->toArray();
                $resp['data'] = $list;
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
            $resp = [];

            $list = new MealStatusType;

            if(isset($options['order'])){
                $orderBy = $options['order_by']??"ASC";
                $col = $options['order'];
                $list = $list->orderBy($col, $orderBy);
            }

            if (isset($options['limit'])) {
                $list = $list->paginate($options['limit'])->toArray();
                $resp = $list;
            } else {
                $list = $list->get()->toArray();
                $resp['data'] = $list;
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
            $resp = [];

            $list = new FoodType;

            if(isset($options['order'])){
                $orderBy = $options['order_by']??"ASC";
                $col = $options['order'];
                $list = $list->orderBy($col, $orderBy);
            }

            if (isset($options['limit'])) {
                $list = $list->paginate($options['limit'])->toArray();
                $resp = $list;
            } else {
                $list = $list->get()->toArray();
                $resp['data'] = $list;
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



    //For POCOR-8348 Start...
//    public function getStudentMealImport($params)
//    {
//        try {
//            $validExtension = ['xlsx', 'xls', 'csv'];
//            $extension = File::extension($params['file']->getClientOriginalName());
//
//            if (!in_array($extension, $validExtension)) {
//                return 1; //Invalid file extension...
//            }
//
//            $headers = ['Date ( DD/MM/YYYY )', 'OpenEMIS ID', 'Meal Programme Code', 'Meal Received Code', 'Meal Benefit Name', 'Comment'];
//            $results = StudentMealImport::toArray($params['file']);
//
//            if (empty($results[0][1])) {
//                return 2; //Header is not present...
//            }
//
//            if (empty($results[0][2])) {
//                return 3; //Imported file is empty...
//            }
//
//
//            foreach($headers as $k => $header){
//                $trimmedArray = array_map('trim', $results[0][1]); //Removing whitespace...
//
//                if(!in_array($header, $trimmedArray)){
//                    return 4; //Not a valid header...
//                }
//            }
//
//            $institutionClass = InstitutionClasses::where('institution_id', $params['institution_id'])->where('id', $params['institution_class_id'])->first();
//
//            if(!$institutionClass){
//                return 5; //Institution is not linked with Institution Class...
//            }
//
//            $currentAcademicPeriod = AcademicPeriod::where('current', 1)->first();
//            if(!$currentAcademicPeriod){
//                return 6; //No current Academic Period is set in DB...
//            }
//
//            $rowsCount = count($results[0]) - 2;
//
//            if ($rowsCount > config('constantvalues.importExcelRules.maxRows')) {
//                return 7; //File can not have more than 2000 records.
//            }
//
//            $import = $this->importStudentMeals($results,  $params, $currentAcademicPeriod);
//            return $import;
//
//        } catch (\Exception $e) {
//            Log::error(
//                'Failed to import students meals in DB.',
//                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
//            );
//
//            return $this->sendErrorResponse('Failed to import students meals in DB.');
//        }
//    }
//
//
//    public function importStudentMeals($results,  $params, $currentAcademicPeriod)
//    {
//        DB::beginTransaction();
//        try {
//            $i = -1;
//            $validation = [];
//            $updated_data = [];
//            $add_data = [];
//            $importResponse = [];
//
//            foreach ($results[0] as $key => $row) {
//                $errors = [];
//                $i++;
//
//                if ($i < 2) {
//                    continue;
//                }
//
//                if (!$row[0]) { //Date
//                    $label = $results[0][1][0];
//                    $errors[$label] = 'Date is required.';
//                } else {
//                    //For POCOR-8534 start...
//                    //Coverting into m/d/y because excel reads the date in m/d/y format...
//                    if(is_numeric($row[0])){
//                        $row[0] = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[0])->format('m/d/Y');
//                    }
//                    //For POCOR-8534 end...
//
//                    //POCOR-9594: start - normalize European dot/dash date separators and add leading zeros
//                    if (!is_numeric($row[0])) {
//                        $normalized = preg_replace('/[.\-]/', '/', trim($row[0]));
//                        // add leading zero to day if single digit: "2/02/2025" → "02/02/2025"
//                        $normalized = preg_replace('/^(\d)\//', '0$1/', $normalized);
//                        // add leading zero to month if single digit: "02/2/2025" → "02/02/2025"
//                        $normalized = preg_replace_callback('/^(\d{2})\/(\d)\//', fn($m) => $m[1] . '/0' . $m[2] . '/', $normalized);
//                        $row[0] = $normalized;
//                    }
//                    //POCOR-9594: end
//
//
//                    if(!preg_match('/^(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/\d{4}$/', $row[0])){
//                        $label = $results[0][1][0];
//                        $errors[$label] = 'Invalid date format.';
//                    } else {
//                        $date = str_replace('/', '-', $row[0]);
//                        $date = date('Y-m-d', strtotime($date));
//
//                        if($date < $currentAcademicPeriod->start_date || $date > $currentAcademicPeriod->end_date){
//                            $label = $results[0][1][0];
//                            $errors[$label] = 'Invalid date value. Date should be between '.$currentAcademicPeriod->start_date.' and '.$currentAcademicPeriod->end_date.' for current academic period.';
//                        }
//                    }
//                }
//
//                if (!$row[1]) { //OpenEMIS Id
//                    $label = $results[0][1][1];
//                    $errors[$label] = 'OpenEMIS Id is required.';
//                }
//
//                if (!$row[2]) { //Meal programme code
//                    $label = $results[0][1][2];
//                    $errors[$label] = 'Meal programme code is required.';
//                }
//
//                if (!$row[3]) { //Meal received code
//                    $label = $results[0][1][3];
//                    $errors[$label] = 'Meal received code is required.';
//                }
//
//                if(isset($row[3]) && $row[3] == "Received"){
//                    if (!$row[4]) { //Meal benefit name
//                        $label = $results[0][1][4];
//                        $errors[$label] = 'Meal benefit name is required.';
//                    }
//                }
//
//
//                $allRows = [
//                    $results[0][1][0] => $row[0],
//                    $results[0][1][1] => $row[1],
//                    $results[0][1][2] => $row[2],
//                    $results[0][1][3] => $row[3],
//                    $results[0][1][4] => $row[4],
//                    $results[0][1][5] => $row[5]
//                ];
//
//
//                if (count($errors) > 0) {
//                    $validation[] = [
//                        'row_number' => $i,
//                        'data' => $allRows,
//                        'errors' => $errors
//                    ];
//                } else {
//                    $user = SecurityUsers::where('openemis_no', $row[1])->where('is_student', 1)->first();
//                    $institutionStudent = InstitutionStudent::where('student_id', $user->id??0)->where('institution_id', $params['institution_id'])->first();
//                    $mealProgramme = MealProgrammes::where('code', $row[2])->first();
//                    $mealReceived = MealReceived::where('code', $row[3])->first();
//
//                    if(isset($mealReceived) && $mealReceived->code == "Received"){
//                        $mealBenefit = MealBenefits::where('id', $row[4])->first();
//                        if(!$mealBenefit){
//                            $label = $results[0][1][4];
//                            $errors[$label] = 'Meal benefit code does not exist.';
//                            $validation[] = [
//                                'row_number' => $i,
//                                'data' => $allRows,
//                                'errors' => $errors
//                            ];
//                        }
//                    }
//
//
//                    if(!$user){
//                        $label = $results[0][1][1];
//                        $errors[$label] = 'OpenEMIS ID does not exist.';
//                        $validation[] = [
//                            'row_number' => $i,
//                            'data' => $allRows,
//                            'errors' => $errors
//                        ];
//                    }
//
//                    if(!$institutionStudent){
//                        $label = $results[0][1][1];
//                        $errors[$label] = 'Student does not associated with institution.';
//                        $validation[] = [
//                            'row_number' => $i,
//                            'data' => $allRows,
//                            'errors' => $errors
//                        ];
//                    }
//
//                    if(!$mealProgramme){
//                        $label = $results[0][1][2];
//                            $errors[$label] = 'Meal programmes code does not exist.';
//                            $validation[] = [
//                                'row_number' => $i,
//                                'data' => $allRows,
//                                'errors' => $errors
//                            ];
//                    }
//
//                    if(!$mealReceived){
//                        $label = $results[0][1][3];
//                            $errors[$label] = 'Meal received code does not exist.';
//                            $validation[] = [
//                                'row_number' => $i,
//                                'data' => $allRows,
//                                'errors' => $errors
//                            ];
//                    }
//
//
//
//                    if($user && $institutionStudent && $mealProgramme && $mealReceived){
//
//                        $date = str_replace('/', '-', $row[0]);
//                        $date = date('Y-m-d', strtotime($date));
//
//                        $check = InstitutionMealStudents::where([
//                            'student_id' => $user->id,
//                            'academic_period_id' => $currentAcademicPeriod->id,
//                            'institution_class_id' => $params['institution_class_id'],
//                            'institution_id' => $params['institution_id'],
//                            'meal_programmes_id' => $mealProgramme->id,
//                            'date' => $date
//                        ])->first();
//
//
//                        if(!$check){
//                            $insert['student_id'] = $user->id;
//                            $insert['academic_period_id'] = $currentAcademicPeriod->id;
//                            $insert['institution_class_id'] = $params['institution_class_id'];
//                            $insert['institution_id'] = $params['institution_id'];
//                            $insert['meal_programmes_id'] = $mealProgramme->id;
//                            $insert['date'] = $date;
//                            $insert['meal_benefit_id'] = $row[4];
//                            $insert['meal_received_id'] = $mealReceived->id;
//                            $insert['paid'] = Null;
//                            $insert['comment'] = $row[5];
//                            $insert['created_user_id'] = JWTAuth::user()->id;
//                            $insert['created'] = Carbon::now()->toDateTimeString();
//
//                            $store = InstitutionMealStudents::insert($insert);
//
//                            $add_data[] = [
//                                'row_number' => $i,
//                                'data' => $allRows,
//                                'errors' => $errors
//                            ];
//                        } else {
//                            $update['student_id'] = $user->id;
//                            $update['academic_period_id'] = $currentAcademicPeriod->id;
//                            $update['institution_class_id'] = $params['institution_class_id'];
//                            $update['institution_id'] = $params['institution_id'];
//                            $update['meal_programmes_id'] = $mealProgramme->id;
//                            $update['date'] = $date;
//                            $update['meal_benefit_id'] = $row[4];
//                            $update['meal_received_id'] = $mealReceived->id;
//                            $update['paid'] = Null;
//                            $update['comment'] = $row[5];
//                            $update['modified_user_id'] = JWTAuth::user()->id;
//                            $update['modified'] = Carbon::now()->toDateTimeString();
//
//                            $updateData = InstitutionMealStudents::where([
//                                    'student_id' => $user->id,
//                                    'academic_period_id' => $currentAcademicPeriod->id,
//                                    'institution_class_id' => $params['institution_class_id'],
//                                    'institution_id' => $params['institution_id'],
//                                    'meal_programmes_id' => $mealProgramme->id,
//                                    'date' => $date
//                                ])->update($update);
//
//                            $updated_data[] = [
//                                'row_number' => $i,
//                                'data' => $allRows,
//                                'errors' => $errors
//                            ];
//                        }
//
//                    }
//
//                }
//
//            }
//
//            $importResponse = [
//                'total_count' => count($results[0]) - 2,
//                'records_added' => [
//                    'count' => count($add_data),
//                    'rows' => $add_data,
//                ],
//                'records_updated' => [
//                    'count' => count($updated_data),
//                    'rows' => $updated_data,
//                ],
//                'records_failed' => [
//                    'count' => count($validation),
//                    'rows' => $validation,
//                ],
//            ];
//
//            DB::commit();
//            return $importResponse;
//
//        } catch (\Exception $e){
//            DB::rollBack();
//
//            Log::error(
//                'Failed in importStudentMeals method.',
//                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
//            );
//            dd($e);
//            return false;
//        }
//    }
//




public function getStudentMealImport($params)
{
    try {
        $file = $params['file'];
        $extension = strtolower(File::extension($file->getClientOriginalName()));
        $validExtensions = ['xlsx', 'xls', 'csv'];

        if (!in_array($extension, $validExtensions, true)) {
            return 1; // Invalid file extension
        }

        $headers = [
            'Date ( DD/MM/YYYY )',
            'OpenEMIS ID',
            'Meal Programme Code',
            'Meal Received Code',
            'Meal Benefit Name',
            'Comment',
        ];

        $rows = $this->readStudentMealImportRows($file);

        if (empty($rows[2])) { //POCOR-9594: row 1 = title, row 2 = headers
            return 2; // Header is not present
        }

        if (empty($rows[3])) { //POCOR-9594: data starts at row 3
            return 3; // Imported file is empty
        }

        $trimmedHeaderRow = array_map(
            static fn($value) => trim((string)$value),
            $rows[2]['values'] //POCOR-9594: headers are on row 2
        );

        foreach ($headers as $expectedHeader) {
            if (!in_array($expectedHeader, $trimmedHeaderRow, true)) {
                return 4; // Not a valid header
            }
        }

        $institutionClass = InstitutionClasses::where('institution_id', $params['institution_id'])
            ->where('id', $params['institution_class_id'])
            ->first();

        if (!$institutionClass) {
            return 5; // Institution is not linked with Institution Class
        }

        //POCOR-9594: academic_period_level_id=-1 is the "All Data" catch-all; only Year-type periods (level_id>0) are valid
        $currentAcademicPeriod = AcademicPeriod::where('current', 1)
            ->where('academic_period_level_id', '>', 0)
            ->orderBy('start_date', 'desc')
            ->first();

        if (!$currentAcademicPeriod) {
            return 6; // No current Academic Period is set in DB
        }

        $dataRowCount = count($rows) - 2; //POCOR-9594: exclude title row + header row

        if ($dataRowCount > config('constantvalues.importExcelRules.maxRows')) {
            return 7; // File cannot have more than configured records
        }

        $result = $this->importStudentMeals($rows, $params, $currentAcademicPeriod);
        return $result;
    } catch (\Throwable $e) {
        Log::error('Failed to import students meals in DB.', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return $this->sendErrorResponse('Failed to import students meals in DB.');
    }
}

public function importStudentMeals($rows, $params, $currentAcademicPeriod)
{
    DB::beginTransaction();

    try {
        $headerRowNumber = 2; //POCOR-9594: row 1=title, row 2=headers, row 3+=data
        $firstDataRowNumber = 3; //POCOR-9594
        $headerRow = $rows[$headerRowNumber]['values'];

        $validation = [];
        $updatedRows = [];
        $addedRows = [];

        foreach ($rows as $rowNumber => $rowData) {
            if ($rowNumber < $firstDataRowNumber) {
                continue;
            }

            $row = $rowData['values'];
            $cells = $rowData['cells'];

            // Normalize row length to avoid undefined offsets
            for ($i = 0; $i <= 5; $i++) {
                $row[$i] = $row[$i] ?? null;
            }

            $allRows = [
                    $headerRow[0] ?? 'Date' => $row[0],
                    $headerRow[1] ?? 'OpenEMIS ID' => $row[1],
                    $headerRow[2] ?? 'Meal Programme Code' => $row[2],
                    $headerRow[3] ?? 'Meal Received Code' => $row[3],
                    $headerRow[4] ?? 'Meal Benefit Name' => $row[4],
                    $headerRow[5] ?? 'Comment' => $row[5],
            ];

            $errors = [];

            // Date
            $normalizedDate = $this->normalizeImportedDate(
                $cells[0] ?? null,
                $row[0],
                $currentAcademicPeriod,
                $errors,
                $headerRow[0] ?? 'Date'
            );

            // Required fields
            if ($this->isEmptyCellValue($row[1])) {
                $errors[$headerRow[1] ?? 'OpenEMIS ID'] = 'OpenEMIS ID is required.';
            }

            if ($this->isEmptyCellValue($row[2])) {
                $errors[$headerRow[2] ?? 'Meal Programme Code'] = 'Meal programme code is required.';
            }

            if ($this->isEmptyCellValue($row[3])) {
                $errors[$headerRow[3] ?? 'Meal Received Code'] = 'Meal received code is required.';
            }

            if (($row[3] ?? null) === 'Received' && $this->isEmptyCellValue($row[4])) {
                $errors[$headerRow[4] ?? 'Meal Benefit Name'] = 'Meal benefit name is required.';
            }

            if (!empty($errors)) {
                $validation[] = [
                    'row_number' => $rowNumber,
                    'data' => $allRows,
                    'errors' => $errors,
                ];
                continue;
            }

            $user = SecurityUsers::where('openemis_no', $row[1])
                ->where('is_student', 1)
                ->first();

            $institutionStudent = InstitutionStudent::where('student_id', $user->id ?? 0)
                ->where('institution_id', $params['institution_id'])
                ->first();

            $mealProgramme = MealProgrammes::where('code', $row[2])->first();
            $mealReceived = MealReceived::where('code', $row[3])->first();

            if (!$user) {
                $errors[$headerRow[1] ?? 'OpenEMIS ID'] = 'OpenEMIS ID does not exist.';
            }

            if (!$institutionStudent) {
                $errors[$headerRow[1] ?? 'OpenEMIS ID'] = 'Student is not associated with institution.';
            }

            if (!$mealProgramme) {
                $errors[$headerRow[2] ?? 'Meal Programme Code'] = 'Meal programme code does not exist.';
            }

            if (!$mealReceived) {
                $errors[$headerRow[3] ?? 'Meal Received Code'] = 'Meal received code does not exist.';
            }

            $mealBenefitId = null;

            if ($mealReceived && $mealReceived->code === 'Received') {
                //POCOR-9594: template References sheet shows Id column — user fills in the numeric id
                $mealBenefit = MealBenefits::where('id', $row[4])->first();

                if (!$mealBenefit) {
                    $errors[$headerRow[4] ?? 'Meal Benefit Name'] = 'Meal benefit id does not exist.';
                } else {
                    $mealBenefitId = $mealBenefit->id;
                }
            }

            if (!empty($errors)) {
                $validation[] = [
                    'row_number' => $rowNumber,
                    'data' => $allRows,
                    'errors' => $errors,
                ];
                continue;
            }

            $lookup = [
                'student_id' => $user->id,
                'academic_period_id' => $currentAcademicPeriod->id,
                'institution_class_id' => $params['institution_class_id'],
                'institution_id' => $params['institution_id'],
                'meal_programmes_id' => $mealProgramme->id,
                'date' => $normalizedDate,
            ];

            $payload = [
                'student_id' => $user->id,
                'academic_period_id' => $currentAcademicPeriod->id,
                'institution_class_id' => $params['institution_class_id'],
                'institution_id' => $params['institution_id'],
                'meal_programmes_id' => $mealProgramme->id,
                'date' => $normalizedDate,
                'meal_benefit_id' => $mealBenefitId,
                'meal_received_id' => $mealReceived->id,
                'paid' => null,
                'comment' => $row[5],
            ];

            $existingRecord = InstitutionMealStudents::where($lookup)->first();

            if ($existingRecord) {
                $payload['modified_user_id'] = JWTAuth::user()->id;
                $payload['modified'] = Carbon::now()->toDateTimeString();

                InstitutionMealStudents::where($lookup)->update($payload);

                $updatedRows[] = [
                    'row_number' => $rowNumber,
                    'data' => $allRows,
                    'errors' => [],
                ];
            } else {
                $payload['created_user_id'] = JWTAuth::user()->id;
                $payload['created'] = Carbon::now()->toDateTimeString();

                InstitutionMealStudents::insert($payload);

                $addedRows[] = [
                    'row_number' => $rowNumber,
                    'data' => $allRows,
                    'errors' => [],
                ];
            }
        }

        DB::commit();

        return [
            'total_count' => count($rows) - 2, //POCOR-9594: exclude title + header rows
            'records_added' => [
                'count' => count($addedRows),
                'rows' => $addedRows,
            ],
            'records_updated' => [
                'count' => count($updatedRows),
                'rows' => $updatedRows,
            ],
            'records_failed' => [
                'count' => count($validation),
                'rows' => $validation,
            ],
        ];
    } catch (\Throwable $e) {
        DB::rollBack();

        Log::error('Failed in importStudentMeals method.', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return false;
    }
}

/**
 * Read spreadsheet rows while preserving Cell objects,
 * so date detection can use workbook/cell formatting.
 */
private function readStudentMealImportRows($uploadedFile): array
{

    $path = $uploadedFile->getRealPath();

    $reader = IOFactory::createReaderForFile($path);

    // Important: do NOT use readDataOnly(true),
    // otherwise date format masks are lost for xls/xlsx.
    $reader->setReadDataOnly(false);

    $spreadsheet = $reader->load($path);
    $sheet = $spreadsheet->getActiveSheet();

    $highestRow = $sheet->getHighestDataRow();
    $highestColumnIndex = 6; // A..F only

    $rows = [];

    for ($rowNumber = 1; $rowNumber <= $highestRow; $rowNumber++) {
        $values = [];
        $cells = [];

        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $cell = $sheet->getCell([$col, $rowNumber]);
            $cells[] = $cell;
            $values[] = $cell->getValue();
        }

        $rows[$rowNumber] = [
            'values' => $values,
            'cells' => $cells,
        ];
    }

    return $rows;
}

/**
 * Convert imported date to Y-m-d as safely as possible.
 * First trust Excel-native date cells; then fall back to string parsing.
 */
private function normalizeImportedDate($cell, $rawValue, $currentAcademicPeriod, array &$errors, string $label): ?string
{
    if ($this->isEmptyCellValue($rawValue)) {
        $errors[$label] = 'Date is required.';
        return null;
    }

    $normalizedDate = null;

    // If cell holds an OLE numeric date serial (covers isDateTime() AND custom dot-format codes
    // that PhpSpreadsheet does not recognise as date formats) — convert directly. //POCOR-9594
    if (is_numeric($rawValue)) {
        try {
            $normalizedDate = ExcelDate::excelToDateTimeObject((float)$rawValue)->format('Y-m-d');
        } catch (\Throwable $e) {
            // not a valid date serial — fall through to string parsing
        }
    }

    // Fallback for CSV / manually typed text cells (e.g. "02/02/2025", "2.02.2025") //POCOR-9594
    if ($normalizedDate === null) {
        $normalizedDate = $this->parseLooseDateString($rawValue);
    }

    if ($normalizedDate === null) {
        $errors[$label] = 'Invalid date format.';
        return null;
    }

    if (
        $normalizedDate < $currentAcademicPeriod->start_date ||
        $normalizedDate > $currentAcademicPeriod->end_date
    ) {
        $errors[$label] = sprintf(
            'Invalid date value. Date should be between %s and %s for current academic period.',
            $currentAcademicPeriod->start_date,
            $currentAcademicPeriod->end_date
        );
        return null;
    }

    return $normalizedDate;
}

/**
 * Parse common text date formats when the source is not a true Excel date cell
 * (for example CSV or manually typed text in Excel).
 */
private function parseLooseDateString($value): ?string
{
    if ($value instanceof \DateTimeInterface) {
        return $value->format('Y-m-d');
    }

    $value = trim((string)$value);

    if ($value === '') {
        return null;
    }

    // Normalize separators — replace dots/dashes with slashes, then collapse duplicates //POCOR-9594
    $normalized = preg_replace('/[.\-]/', '/', $value);
    $normalized = preg_replace('/\/+/', '/', $normalized); //POCOR-9594: "01/27//2025" → "01/27/2025"
    $normalized = preg_replace('/\s+/', ' ', $normalized);

    $formats = [
        'd/m/Y',
        'j/n/Y',
        'd/n/Y',
        'j/m/Y',
        'Y/m/d',
        'Y/n/j',
        'm/d/Y',
        'n/j/Y',
        'd/m/y',
        'j/n/y',
        'm/d/y',
        'n/j/y',
    ];

    foreach ($formats as $format) {
        $date = \DateTime::createFromFormat('!' . $format, $normalized);
        $lastErrors = \DateTime::getLastErrors();

        if (
            $date !== false &&
            $lastErrors['warning_count'] === 0 &&
            $lastErrors['error_count'] === 0
        ) {
            return $date->format('Y-m-d');
        }
    }

    $timestamp = strtotime($normalized);

    if ($timestamp !== false) {
        return date('Y-m-d', $timestamp);
    }

    return null;
}

private function isEmptyCellValue($value): bool
{
    return $value === null || trim((string)$value) === '';
}
    /*public function getStudentMealImportTemplate($params)
    {
        try {
            $institution_class_id = $params['institution_class_id'];
            $institution_id = $params['institution_id'];




            $outputData['Data']['header'] = [
                "Date ( DD/MM/YYYY )",
                "OpenEMIS ID",
                "Meal Programme Code",
                "Meal Received Code",
                "Meal Benefit Name",
                "Comment",
            ];

            $outputData['References'] = [];

            $outputData['References']['OpenEMIS ID']['header'] = ['Name', 'OpenEMIS ID'];
            $getClassStudents = getClassStudents($institution_id, $institution_class_id);
            $outputData['References']['OpenEMIS ID']['data'] = $getClassStudents;


            $outputData['References']['Meal Programmes']['header'] = ['Name', 'Code'];
            $getMealProgrammes = getMealProgrammes();
            $outputData['References']['Meal Programmes']['data'] = $getMealProgrammes;


            $outputData['References']['Meal Received']['header'] = ['Name', 'Code'];
            $getMealReceived = getMealReceived();
            $outputData['References']['Meal Received']['data'] = $getMealReceived;


            $outputData['References']['Meal Benefit']['header'] = ['Name', 'Id'];
            $getMealBenefits = getMealBenefits();
            $outputData['References']['Meal Benefit']['data'] = $getMealBenefits;

            return $outputData;

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch student meals import template data from DB.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Failed to fetch student meals import template data from DB.');
        }
    }*/


    public function getDataForSheet($params)
    {
        try {
            $institution_class_id = $params['institution_class_id'];
            $institution_id = $params['institution_id'];
            $meal_programme_id = $params['meal_programme_id'] ?? null; //POCOR-9594: filter template to selected programme only
            // Log::debug('@MealRepository::getDataForSheet institution_id=' . json_encode($institution_id) . ' institution_class_id=' . json_encode($institution_class_id) . ' meal_programme_id=' . json_encode($meal_programme_id)); //[TEMP-LOG]

            $getClassStudents = getClassStudents($institution_id, $institution_class_id);
            // Log::debug('@MealRepository::getDataForSheet classStudents count=' . count($getClassStudents)); //[TEMP-LOG]
            $getMealProgrammes = getMealProgrammes($meal_programme_id); //POCOR-9594: pass filter
            // Log::debug('@MealRepository::getDataForSheet mealProgrammes count=' . count($getMealProgrammes) . ' data=' . json_encode($getMealProgrammes)); //[TEMP-LOG]

            $getMealReceived = getMealReceived();

            $getMealBenefits = getMealBenefits();

            $getNewArray = $this->getNewArray($getClassStudents, $getMealProgrammes, $getMealReceived, $getMealBenefits);
            return $getNewArray;
        } catch (\Exception $e) {
            Log::error(
                'Failed in getDataForSheet.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return [];
        }
    }


    public function getNewArray($array1, $array2, $array3, $array4)
    {
        try {
            $newArray = [];

            for ($i = 0; $i < count($array1); $i++) {
                $newRow = [];
                $newRow[] = $array1[$i]['Name'];
                $newRow[] = $array1[$i]['OpenEMIS ID'];

                // Meal Programme data
                if (isset($array2[$i])) {
                    $newRow[] = $array2[$i]['Name'];
                    $newRow[] = $array2[$i]['Code'];
                } else {
                    $newRow[] = null;
                    $newRow[] = null;
                }

                // Meal Received data
                if (isset($array3[$i])) {
                    $newRow[] = $array3[$i]['Name'];
                    $newRow[] = $array3[$i]['Code'];
                } else {
                    $newRow[] = null;
                    $newRow[] = null;
                }

                // Meal Benefit data
                if (isset($array4[$i])) {
                    $newRow[] = $array4[$i]['Name'];
                    $newRow[] = $array4[$i]['Id'];
                } else {
                    $newRow[] = null;
                    $newRow[] = null;
                }

                $newArray[] = $newRow;
            }
            return $newArray;
        } catch (\Exception $e) {
            Log::error(
                'Failed in getNewArray.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return [];
        }
    }
    //For POCOR-8348 End...

}
