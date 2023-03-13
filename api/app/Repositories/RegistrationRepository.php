<?php

namespace App\Repositories;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use JWTAuth;
use App\Models\AcademicPeriod;
use App\Models\EducationGrades;
use App\Models\Institutions;
use App\Models\AreaAdministratives;
use App\Models\SecurityUsers;
use App\Models\SecurityUserCode;
use App\Models\Nationalities;
use App\Models\StudentStatuses;
use App\Models\RegistrationOtp;
use App\Models\InstitutionStudent;
use App\Models\ConfigItem;
use App\Models\OpenemisTemp;
use App\Models\InstitutionStudentAdmission;
use App\Models\StudentCustomFormField;
use App\Models\StudentCustomFieldValues;
use App\Models\IdentityTypes;
use Illuminate\Support\Facades\DB;
use Mail;
use Illuminate\Support\Str;

class RegistrationRepository extends Controller
{


    public function academicPeriodsList()
    {
        try {
            $academicPeriods = AcademicPeriod::select('id', 'name')->where('current', 1)->orderBy('id','DESC')->get();
            
            return $academicPeriods;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Academic Period List Not Found');
        }
    }

    public function educationGradesList()
    {
        try {
            //$educationGrades = EducationGrades::select('id', 'name')->get();

            $educationGrades = EducationGrades::select(
                        'academic_periods.id as academic_period_id',
                        'academic_periods.name as academic_period_name',
                        'academic_periods.code as academic_period_code',
                        'education_grades.id as educaiton_grade_id',
                        'education_grades.name as educaiton_grade_name'
                    )
                    ->join('education_programmes', 'education_programmes.id', '=', 'education_grades.education_programme_id')
                    ->join('education_cycles', 'education_cycles.id', '=', 'education_programmes.education_cycle_id')
                    ->join('education_levels', 'education_levels.id', '=', 'education_cycles.education_level_id')
                    ->join('education_systems', 'education_systems.id', '=', 'education_levels.education_system_id')
                    ->join('academic_periods', 'academic_periods.id', '=', 'education_systems.academic_period_id')
                    ->where('academic_periods.current', 1)
                    //->where('academic_periods.id', $academic_period_id)
                    ->get();
            
            return $educationGrades;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }


    public function institutionDropdown()
    {
        try {
            $institutions = Institutions::select('id', 'name', 'code')->get();
            
            return $institutions;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institutions List Not Found');
        }
    }


    public function administrativeAreasList()
    {
        try {
            /*$areaAdministratives = AreaAdministratives::select('id', 'name', 'parent_id')->with('areaAdministrativesChild:id,name,parent_id')->get();*/

            $areaAdministratives = AreaAdministratives::select('id', 'name', 'parent_id')->get()->toArray();
            
            return $areaAdministratives;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Area Administratives List Not Found');
        }
    }



    public function generateOtp($request)
    {
        try {
            $email = $request['email'];

            $otpData = $this->getUniqueOtp($email);
            $otp = $otpData['otp'];
            $encodedOtp = $otpData['encodedOtp'];

            $securityUser = SecurityUsers::where('email', $email)->first();
            if(!$securityUser){
                return 2;
            }
            
            $data['otp'] = $otp;
            $data['first_name'] = $securityUser->first_name;
            $data['last_name'] = $securityUser->last_name;

            $insertData['security_user_id'] = $securityUser->id;
            $insertData['verification_otp'] = $encodedOtp;
            //$insertData['is_expired'] = 0;
            $insertData['created'] = Carbon::now()->toDateTimeString();
            $store = SecurityUserCode::insert($insertData);

            Mail::send('generateOtp', $data, function($message) use($email) {
                $message->to($email, 'OpenEMIS User')
                    ->subject('OpenEMIS - One-time Password (OTP)');
            });
            return 1;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to sent otp on email.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to sent otp on email.');
        }
    }


    public function getUniqueOtp()
    {
        try {
            $otp = random_int(100000, 999999);
            $encodedOtp = base64_encode($otp);

            $securityUserCode = SecurityUserCode::where('verification_otp', $encodedOtp)->first();
            if($securityUserCode){
                return $this->getUniqueOtp();
            } else {
                $array = array('encodedOtp' => $encodedOtp, 'otp' => $otp);
                return $array;
            }
        } catch (\Exception $e) {
            Log::error(
                'Failed to generate otp.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to generate otp.');
        }
    }



    public function verifyOtp($request)
    {
        try {
            $params = $request->all();
            
            $otp = $params['otp'];
            $encodedOtp = base64_encode($otp);

            $checkOtp = SecurityUserCode::select('security_user_codes.security_user_id','security_user_codes.verification_otp', 'security_user_codes.created')
                ->join('security_users', 'security_users.id', '=', 'security_user_codes.security_user_id')
                ->where('verification_otp', $encodedOtp)
                ->where('security_users.email', $params['email'])
                ->first();

            
            if($checkOtp){
                $currentTime = date('Y-m-d h:i:s');
                $otpExpTime = date('Y-m-d h:i:s', strtotime($checkOtp->created.'+1 hour'));

                if($currentTime > $otpExpTime){
                    return 0;
                }
                return 1;
            } else {
                return 2;
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to verify otp.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to verify otp.');
        }
    }


    public function autocompleteOpenemisNo($id)
    {
        try {
            $data = SecurityUsers::select('id as key', 'openemis_no as value')->where('openemis_no', 'LIKE', '%'.$id.'%')->get()->toArray();
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to find candidate data.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to find candidate data.');
        }
    }


    public function autocompleteIdentityNo($identityTypeId, $identityNumber)
    {
        try {
            $data = SecurityUsers::select('id as key', 'identity_number as value')->where('identity_type_id', $identityTypeId)->where('identity_number', 'LIKE', '%'.$identityNumber.'%')->get()->toArray();
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to find candidate data.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to find candidate data.');
        }
    }


    public function detailsByEmis($id)
    {
        try {
            $data = SecurityUsers::with(
                    'gender',
                    'nationalities',
                    'institutionStudent',
                    'institutionStudent.institution'
                )
                ->where('openemis_no', $id)
                ->orWhere('identity_number', $id)
                ->get();
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to find candidate data.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to find candidate data.');
        }
    }


    public function nationalityList()
    {
        try {
            $nationalities = Nationalities::select('id', 'name')->get();
            
            return $nationalities;
        } catch (\Exception $e) {
            Log::error(
                'Failed to find nationality list.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to find nationality list.');
        }
    }


    public function institutionStudents($request)
    {
        DB::beginTransaction();
        try {
            //dd($request->all());

            $encodedOtp = base64_encode($request['otp']??"");
            //$otpData = RegistrationOtp::where('otp', $encodedOtp)->first();
            $userData = SecurityUserCode::select('security_users.id as user_id')->join('security_users', 'security_users.id', '=', 'security_user_codes.security_user_id')->where('verification_otp', $encodedOtp)->first();

            if(!$userData){
                return 7; //Invalid otp...
            }

            
            if($request['openemis_id'] != ""){
                Log::info('For User Registration using openemis id.');
                $student = SecurityUsers::with(
                        'gender',
                        'nationalities',
                        'institutionStudent',
                        'institutionStudent.institution',
                        'institutionStudent.studentStatus'
                    )
                    ->where('openemis_no', $request['openemis_id'])
                    ->first();
                
                if($student){
                    $dob = $student['date_of_birth']->format('Y-m-d');

                    //To match the date string given in request params...
                    $dobArr = explode("-", $dob);
                    if(count($dobArr) > 0){
                        $dobArr[1] = ltrim($dobArr[1], '0');
                        $dobArr[2] = ltrim($dobArr[2], '0');
                        $dob = $dobArr[0]."-".$dobArr[1]."-".$dobArr[2];
                    }

                    if($dob == $request['date_of_birth']){

                        if(isset($student['institutionStudent']['studentStatus']['name']) && $student['institutionStudent']['studentStatus']['name'] == 'Enrolled'){
                            
                            DB::commit();
                            return 4; //registration unsuccessful – student already enrolled
                        } else {
                            //$stuStatus = StudentStatuses::where('name', 'Pending Admission')->first();
                            $academicPeriod = AcademicPeriod::where('id', $request['academic_period_id'])->first();

                            
                            //Creating Institution_student...
                            /*$storeStu['id'] = Str::uuid();
                            $storeStu['student_status_id'] = 1;
                            $storeStu['student_id'] = $student->id;
                            $storeStu['education_grade_id'] = $request['education_grade_id'];
                            $storeStu['academic_period_id'] = $request['academic_period_id'];
                            $storeStu['start_date'] = $academicPeriod['start_date'];
                            $storeStu['start_year'] = $academicPeriod['start_year'];
                            $storeStu['end_date'] = $academicPeriod['end_date'];
                            $storeStu['end_year'] = $academicPeriod['end_year'];
                            $storeStu['institution_id'] = $request['institution_id'];
                            $storeStu['previous_institution_student_id'] = $student['institutionStudent']['id']??NULL;
                            $storeStu['created_user_id'] = $userData->user_id;
                            $storeStu['created'] = Carbon::now()->toDateTimeString();

                            $store = InstitutionStudent::insert($storeStu);
                            Log::info("## Stored in InstitutionStudent ##", $storeStu);*/
                            

                            //Creating Institution_student_Admission...
                            $storeAdmission['start_date'] = $academicPeriod['start_date'];
                            $storeAdmission['end_date'] = $academicPeriod['end_date'];
                            $storeAdmission['student_id'] = $student->id;
                            $storeAdmission['status_id'] = 81; //For Pending Approval..
                            $storeAdmission['institution_id'] = $request['institution_id'];
                            $storeAdmission['academic_period_id'] = $academicPeriod['id'];
                            $storeAdmission['education_grade_id'] = $request['education_grade_id'];
                            $storeAdmission['assignee_id'] = $userData->user_id;
                            $storeAdmission['created_user_id'] = $userData->user_id;
                            $storeAdmission['created'] = Carbon::now()->toDateTimeString();

                            $store = InstitutionStudentAdmission::insert($storeAdmission);
                            Log::info("## Stored in InstitutionStudentAdmission ##", $storeAdmission);

                            if((isset($request->custom_fields)) && (count($request->custom_fields) > 0)){
                                $storeCustomField = $this->storeCustomField($request->custom_fields, $student->id, $userData->user_id);
                            }
                            


                            if(isset($request['otp'])){
                                $sendMail = $this->sendSuccessMail($request);
                                Log::info("## Mail sent Successfully. ##");
                            }

                            DB::commit();
                            return 1;
                        }
                    } else {

                        DB::commit();
                        return 2; //registration unsuccessful – student details do not match
                    }
                } else {
                    DB::commit();
                    return 3; //registration unsuccessful – openemis_no not found
                }

            } elseif ($request['identity_number'] != "") {
                //dd("elseif");
                Log::info('For User Registration using identity number.');
                $student = SecurityUsers::with(
                        'gender',
                        'nationalities',
                        'institutionStudent',
                        'institutionStudent.institution',
                        'institutionStudent.studentStatus'
                    )
                    ->where('identity_number', $request['identity_number'])
                    ->first();
                //dd($student['institutionStudent']['studentStatus']['name']);
                if($student){
                    $dob = $student['date_of_birth']->format('Y-m-d');

                    //To match the date string given in request params...
                    $dobArr = explode("-", $dob);
                    if(count($dobArr) > 0){
                        $dobArr[1] = ltrim($dobArr[1], '0');
                        $dobArr[2] = ltrim($dobArr[2], '0');
                        $dob = $dobArr[0]."-".$dobArr[1]."-".$dobArr[2];
                    }

                    if($dob == $request['date_of_birth']){
                        if(isset($student['institutionStudent']['studentStatus']['name']) === 'Enrolled'){

                            DB::commit();
                            return 4; //registration unsuccessful – student already enrolled
                        } else {
                            //$stuStatus = StudentStatuses::where('name', 'Pending Admission')->first();
                            $academicPeriod = AcademicPeriod::where('id', $request['academic_period_id'])->first();

                            
                            //Creating Institution_student...
                            /*$storeStu['id'] = Str::uuid();
                            $storeStu['student_status_id'] = 1;
                            $storeStu['student_id'] = $student->id;
                            $storeStu['education_grade_id'] = $request['education_grade_id'];
                            $storeStu['academic_period_id'] = $request['academic_period_id'];
                            $storeStu['start_date'] = $academicPeriod['start_date'];
                            $storeStu['start_year'] = $academicPeriod['start_year'];
                            $storeStu['end_date'] = $academicPeriod['end_date'];
                            $storeStu['end_year'] = $academicPeriod['end_year'];
                            $storeStu['institution_id'] = $request['institution_id'];
                            $storeStu['previous_institution_student_id'] = $student['institutionStudent']['id']??NULL;
                            $storeStu['created_user_id'] = $userData->user_id;
                            $storeStu['created'] = Carbon::now()->toDateTimeString();

                            $store = InstitutionStudent::insert($storeStu);
                            Log::info("## Stored in InstitutionStudent ##", $storeStu);*/

                            

                            //Creating Institution_student_Admission...
                            $storeAdmission['start_date'] = $academicPeriod['start_date'];
                            $storeAdmission['end_date'] = $academicPeriod['end_date'];
                            $storeAdmission['student_id'] = $student->id;
                            $storeAdmission['status_id'] = 81; //For Pending Approval..
                            $storeAdmission['institution_id'] = $request['institution_id'];
                            $storeAdmission['academic_period_id'] = $academicPeriod['id'];
                            $storeAdmission['education_grade_id'] = $request['education_grade_id'];
                            $storeAdmission['assignee_id'] = $userData->user_id;
                            $storeAdmission['created_user_id'] = $userData->user_id;
                            $storeAdmission['created'] = Carbon::now()->toDateTimeString();

                            $store = InstitutionStudentAdmission::insert($storeAdmission);

                            Log::info("## Stored in InstitutionStudentAdmission ##", $storeAdmission);

                            if((isset($request->custom_fields)) && (count($request->custom_fields) > 0)){
                                $storeCustomField = $this->storeCustomField($request->custom_fields, $student->id, $userData->user_id);
                            }
                            


                            if(isset($request['otp'])){
                                $sendMail = $this->sendSuccessMail($request);
                                Log::info("## Mail sent successfully. ##");
                            }

                            DB::commit();
                            return 1;
                        }
                    } else {
                        DB::commit();
                        return 2; //registration unsuccessful – student details do not match
                    }
                } else {
                    DB::commit();
                    return 5; //registration unsuccessful – identity_number not found
                }
            } else {
                Log::info('For New User Registration.');
                $configItem = ConfigItem::where('code', 'NewStudent')->first();
                
                if(isset($configItem) && $configItem['value'] == 1){
                    $nationality = Nationalities::where('id', $request['nationality_id'])->first();

                    $openemisNumber = $this->getNewOpenemisNo();

                    //Creating Security_User...
                    $insertUser['username'] = $openemisNumber??Null;
                    $insertUser['password'] = "";
                    $insertUser['openemis_no'] = $openemisNumber??Null;
                    $insertUser['first_name'] = $request['first_name'];
                    $insertUser['middle_name'] = $request['middle_name']??"";
                    $insertUser['third_name'] = $request['third_name']??"";
                    $insertUser['last_name'] = $request['last_name'];
                    $insertUser['preferred_name'] = $request['preferred_name']??"";
                    $insertUser['email'] = $request['email']??"";
                    $insertUser['address'] = $request['address']??"";
                    $insertUser['postal_code'] = $request['postal_code']??"";
                    $insertUser['address_area_id'] = $request['address_area_id']??NULL;
                    $insertUser['birthplace_area_id'] = $request['birthplace_area_id']??NULL;
                    $insertUser['gender_id'] = $request['gender_id'];
                    $insertUser['date_of_birth'] = $request['date_of_birth'];
                    $insertUser['nationality_id'] = $request['nationality_id'];
                    $insertUser['identity_type_id'] = $nationality->identity_type_id??Null;
                    $insertUser['identity_number'] = $request['identity_number'];
                    $insertUser['created_user_id'] = $userData->user_id;
                    $insertUser['created'] = Carbon::now()->toDateTimeString();
                    //dd($insertUser);
                    $userId = SecurityUsers::insertGetId($insertUser);
                    Log::info("## Stored in SecurityUsers ##", $insertUser);

                    if($userId){
                        $student = SecurityUsers::with(
                            'gender',
                            'nationalities',
                            'institutionStudent',
                            'institutionStudent.institution',
                            'institutionStudent.studentStatus'
                        )
                        ->where('id', $userId)
                        ->first();


                        //$stuStatus = StudentStatuses::where('name', 'Pending Admission')->first();
                        $academicPeriod = AcademicPeriod::where('id', $request['academic_period_id'])->first();

                        //Creating Institution_student...
                        /*$storeStu['id'] = Str::uuid();
                        $storeStu['student_status_id'] = 1;
                        $storeStu['student_id'] = $student->id;
                        $storeStu['education_grade_id'] = $request['education_grade_id'];
                        $storeStu['academic_period_id'] = $request['academic_period_id'];
                        $storeStu['start_date'] = $academicPeriod['start_date'];
                        $storeStu['start_year'] = $academicPeriod['start_year'];
                        $storeStu['end_date'] = $academicPeriod['end_date'];
                        $storeStu['end_year'] = $academicPeriod['end_year'];
                        $storeStu['institution_id'] = $request['institution_id'];
                        $storeStu['previous_institution_student_id'] = $student['institutionStudent']['id']??NULL;
                        $storeStu['created_user_id'] = $userData->user_id;
                        $storeStu['created'] = Carbon::now()->toDateTimeString();

                        $store = InstitutionStudent::insert($storeStu);
                        Log::info("## Stored in InstitutionStudent ##", $storeStu);*/


                        //Creating Institution_student_Admission...
                        $storeAdmission['start_date'] = $academicPeriod['start_date'];
                        $storeAdmission['end_date'] = $academicPeriod['end_date'];
                        $storeAdmission['student_id'] = $student->id;
                        $storeAdmission['status_id'] = 81; //For Pending Approval..
                        $storeAdmission['institution_id'] = $request['institution_id'];
                        $storeAdmission['academic_period_id'] = $academicPeriod['id'];
                        $storeAdmission['education_grade_id'] = $request['education_grade_id'];
                        $storeAdmission['assignee_id'] = $userData->user_id;
                        $storeAdmission['created_user_id'] = $userData->user_id;
                        $storeAdmission['created'] = Carbon::now()->toDateTimeString();

                        $store = InstitutionStudentAdmission::insert($storeAdmission);

                        Log::info("## Stored in InstitutionStudentAdmission ##", $storeAdmission);

                        if((isset($request->custom_fields)) && (count($request->custom_fields) > 0)){
                            $storeCustomField = $this->storeCustomField($request->custom_fields, $student->id, $userData->user_id);
                        }
                        

                        if(isset($request['otp'])){
                            $sendMail = $this->sendSuccessMail($request);
                            Log::info("## Mail sent successfully. ##");
                        }

                        DB::commit();
                        return 1;
                    }

                } else {

                    DB::commit();
                    return 6; //registration unsuccessful – not able to create new student
                }
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to register student.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            DB::rollback();
            return $this->sendErrorResponse('Failed to register student.');
        }
    }


    public function storeCustomField($customFields, $student_id, $user_id)
    {
        DB::beginTransaction();
        try {
            $cfArray = [];
            foreach($customFields as $k => $cf){
                $cfArray[$k]['id'] = Str::uuid();
                $cfArray[$k]['student_custom_field_id'] = $cf['custom_field_id'];
                $cfArray[$k]['text_value'] = $cf['text_value'];
                $cfArray[$k]['number_value'] = $cf['number_value'];
                $cfArray[$k]['decimal_value'] = $cf['decimal_value'];
                $cfArray[$k]['textarea_value'] = $cf['textarea_value'];
                $cfArray[$k]['time_value'] = $cf['time_value'];
                $cfArray[$k]['date_value'] = $cf['date_value'];
                $cfArray[$k]['file'] = $cf['file'];
                $cfArray[$k]['student_id'] = $student_id;
                $cfArray[$k]['created_user_id'] = $user_id;
                $cfArray[$k]['created'] = Carbon::now()->toDateTimeString();
            }

            $store = StudentCustomFieldValues::insert($cfArray);
            Log::info("## Stored in InstitutionStudentAdmission ##", $cfArray);
            DB::commit();
            return true;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to store custom fields.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            DB::rollback();
            return $this->sendErrorResponse('Failed to custom fields.');
        }
    }


    public function sendSuccessMail($request)
    {
        try {
            $param = $request->all();
            
            $encodedOtp = base64_encode($request['otp']??"");
            //$otpData = RegistrationOtp::where('otp', $encodedOtp)->first();
            $otpData = SecurityUserCode::join('security_users', 'security_users.id', '=', 'security_user_codes.security_user_id')->where('verification_otp', $encodedOtp)->first();
            

            if($otpData){
                $data['first_name'] = $otpData->first_name;
                $data['last_name'] = $otpData->last_name;

                $email = $otpData->email;
                /*Mail::send(['text'=>'registrationSuccess'], $data, function($message) use ($email) {
                    $message->to($email, 'OpenEMIS User')
                        ->subject('OpenEMIS - Successful Registration');
                });*/

                Mail::send('registrationSuccess', $data, function($message) use ($email) {
                    $message->to($email, 'OpenEMIS User')
                        ->subject('OpenEMIS - Successful Registration');
                });
            }

            return true;
        } catch (\Exception $e) {
            Log::error(
                'Failed to send success mail.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to send success mail.');
        }
    }


    public function getNewOpenemisNo()
    {
        try {
            $configItem = ConfigItem::where('code', 'openemis_id_prefix')->first();
            if($configItem){
                $value = $configItem->value;
                $prefix = explode(",", $value);
                if($prefix[1] > 0){
                    $prefix = $prefix[1];
                } else {
                    $prefix = '';
                }

                $latest = SecurityUsers::orderBy('id', 'DESC')->first();
                $latestOpenemisNo = $latest->openemis_no;


                if (empty($prefix)) {
                    $latestDbStamp = $latestOpenemisNo;
                } else {
                    $latestDbStamp = substr($latestOpenemisNo, strlen($prefix));
                }

                $latestOpenemisNoLastValue = substr($latestOpenemisNo, -1);


                $currentStamp = time();
                if ($latestDbStamp <= $currentStamp && is_numeric($latestOpenemisNoLastValue)) {
                    $newStamp = $latestDbStamp + 1;
                } else {
                    $newStamp = $currentStamp;
                }
                $newOpenemisNo = $prefix.$newStamp;

                $resultOpenemisTemp = OpenemisTemp::orderBy('id', 'DESC')->first();

                if(strlen($resultOpenemisTemp->openemis_no) < 5){
                    $resultOpenemisTemp = SecurityUsers::orderBy('id', 'DESC')->first();
                }

                $resultOpenemisNoTemp = substr($resultOpenemisTemp->openemis_no, strlen($prefix));

                $newOpenemisNo = $resultOpenemisNoTemp+1;
                $newOpenemisNo=$prefix.$newOpenemisNo;

                $resultOpenemisTemps = OpenemisTemp::where('openemis_no', $newOpenemisNo)->first();
                
                if(empty($resultOpenemisTemps->openemis_no)){
                    $storeOpenemisTemp = OpenemisTemp::insert([
                        'openemis_no' => $newOpenemisNo,
                        'ip_address' => $_SERVER['REMOTE_ADDR'],
                        'created' => Carbon::now()->toDateTimeString()
                    ]);
                }

                return $newOpenemisNo;
            }
        } catch (\Exception $e) {
            Log::error(
                'Failed to get new openemis number.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get new openemis number.');
        }
    }



    public function getStudentCustomFields()
    {
        try {
            $customFields = StudentCustomFormField::with('studentCustomField')->whereHas('studentCustomField')->where('student_custom_form_id', 1)->get();

            return $customFields;

        } catch (\Exception $e) {
            Log::error(
                'Failed to find custom fields list.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to find custom fields list.');
        }
    }


    public function identityTypeList()
    {
        try {
            $identityTypes = IdentityTypes::select('id', 'name')->get();
            
            return $identityTypes;
        } catch (\Exception $e) {
            Log::error(
                'Failed to find identity type list.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to find identity type list.');
        }
    }

}

