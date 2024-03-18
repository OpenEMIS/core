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
use App\Models\Areas;
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
use App\Models\InstitutionTypes;
use App\Models\AreaLevels;
use App\Models\AreaAdministrativeLevels;
use App\Models\SecurityGroupUsers;
use App\Models\UserContacts;
use Illuminate\Support\Facades\DB;
use Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class RegistrationRepository extends Controller
{


    public function academicPeriodsList()
    {
        try {
            $academicPeriods = AcademicPeriod::select('id', 'name', 'start_year')->where('current', 1)->orderBy('id','DESC')->get()->toArray();
            $current_start_year = $academicPeriods[0]['start_year']??0;

            $restAcademicPeriods = [];
            if($current_start_year != 0){
                $restAcademicPeriods = AcademicPeriod::select('id', 'name', 'start_year')->where('start_year' ,'>', $current_start_year)->get()->toArray();
            }

            $academicPeriodArr['current_academic_year'] = $academicPeriods;
            $academicPeriodArr['rest_academic_year'] = $restAcademicPeriods;

            
            
            return $academicPeriodArr;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Academic Period List Not Found');
        }
    }

    public function educationGradesList($request)
    {
        try {
            $academic_period_id = $request['academic_period_id'];

            $lists = EducationGrades::select(
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
                    ->join('academic_periods', 'academic_periods.id', '=', 'education_systems.academic_period_id');

            if($academic_period_id){
                $lists = $lists->where('academic_periods.id', $academic_period_id);
            } else {
                $lists = $lists->where('academic_periods.current', 1);
            }

                    
            $educationGrades = $lists->get();
            
            return $educationGrades;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }


    public function institutionDropdown($request)
    {
        try {
            $institutions = Institutions::select('id', 'name', 'code');

            if($request['institution_type_id']){
                $institutions = $institutions->where('institution_type_id', $request['institution_type_id']);
            }


            if($request['area_id']){
                $institutions = $institutions->where('area_id', $request['area_id']);
            }


            $data = $institutions->get();
            
            return $data;
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

            $areaAdministratives = Areas::select('id', 'name', 'parent_id')->orderBy('name', 'ASC')->get()->toArray();
            
            return $areaAdministratives;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Areas List Not Found');
        }
    }



    public function generateOtp($request)
    {
        DB::beginTransaction();
        try {
            $email = $request['email'];
            $blankLastName = 0;

            $securityUser = SecurityUsers::where('email', $email)->first();
            if(!$securityUser){
                $emailArr = explode("@", $email);
                $user['password'] = "";
                $user['first_name'] = $emailArr[0];
                $user['last_name'] = $emailArr[0];
                $user['email'] = $email;
                $user['gender_id'] = 1;
                $user['date_of_birth'] = Carbon::now()->toDateTimeString();
                $user['status'] = 0;
                $user['is_student'] = 0;
                $user['is_staff'] = 0;
                $user['is_guardian'] = 0;
                $user['created_user_id'] = 2;
                $user['created'] = Carbon::now()->toDateTimeString();

                $newUserId = SecurityUsers::insertGetId($user);
                $securityUser = SecurityUsers::where('id', $newUserId)->first();
                $blankLastName = 1;
            }

            $userId = $securityUser->id;
            
            $otpData = $this->getUniqueOtp();
            $otp = $otpData['otp'];
            $encodedOtp = $otpData['encodedOtp'];
            
            $data['otp'] = $otp;
            $data['first_name'] = $securityUser->first_name;
            //$data['last_name'] = $securityUser->last_name;
            if($blankLastName == 1){
                $data['last_name'] = "";
            }

            $insertData['security_user_id'] = $securityUser->id;
            $insertData['verification_otp'] = $encodedOtp;
            //$insertData['is_expired'] = 0;
            $insertData['created'] = Carbon::now()->toDateTimeString();
            $store = SecurityUserCode::insert($insertData);

            Mail::send('generateOtp', $data, function($message) use($email) {
                $message->to($email, 'OpenEMIS User')
                    ->subject(config('constants.emailConfig.generateOtpEmail.subject'));
            });
            DB::commit();
            return 1;
            
        } catch (\Exception $e) {
            DB::rollback();
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
            $data = SecurityUsers::select(
                    'id as key', 
                    'openemis_no as value',
                    'first_name',
                    'middle_name',
                    'third_name',
                    'last_name',
                    'openemis_no',
                    )->where('openemis_no', 'LIKE', '%'.$id.'%')->get()->toArray();
            
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
            $nationalities = Nationalities::orderBy('order', 'ASC')->get();
            
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
            $userData = SecurityUserCode::select('security_users.id as user_id', 'security_users.email')->join('security_users', 'security_users.id', '=', 'security_user_codes.security_user_id')->where('verification_otp', $encodedOtp)->first();

            if(!$userData){
                return 7; //Invalid otp...
            }

            $validateAge = $this->validateAge($request['date_of_birth'], $request['education_grade_id'], $request['academic_period_id']);
            
            if(is_array($validateAge)){
                return $validateAge;
            }

            $validateCustomField = $this->validateCustomField($request);

            if(is_array($validateCustomField) && count($validateCustomField) > 0){
                return $validateCustomField;
            }
            
            if($validateCustomField == 0){
                return 8;
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
                            

                            //For POCOR-8178 Start
                            if(isset($request['email'])){
                                $userContactStore['contact_type_id'] = 8; //For email
                                $userContactStore['value'] = $request['email'];
                                $userContactStore['preferred'] = 1;
                                $userContactStore['security_user_id'] = $student->id;
                                $userContactStore['created_user_id'] = $student->id;
                                $userContactStore['created'] = Carbon::now()->toDateTimeString();

                                $userContactInsert = UserContacts::insert($userContactStore);
                            }
                            //For POCOR-8178 End


                            //Creating Institution_student_Admission...
                            $assigneeId = $this->getAssigneeId();
                            $storeAdmission['start_date'] = $academicPeriod['start_date'];
                            $storeAdmission['end_date'] = $academicPeriod['end_date'];
                            $storeAdmission['student_id'] = $student->id;
                            $storeAdmission['status_id'] = 81; //For Pending Approval..
                            $storeAdmission['institution_id'] = $request['institution_id'];
                            $storeAdmission['academic_period_id'] = $academicPeriod['id'];
                            $storeAdmission['education_grade_id'] = $request['education_grade_id'];
                            $storeAdmission['assignee_id'] = $assigneeId??$userData->user_id;
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

                            //For POCOR-8178 Start
                            if(isset($request['email'])){
                                $userContactStore['contact_type_id'] = 8; //For email
                                $userContactStore['value'] = $request['email'];
                                $userContactStore['preferred'] = 1;
                                $userContactStore['security_user_id'] = $student->id;
                                $userContactStore['created_user_id'] = $student->id;
                                $userContactStore['created'] = Carbon::now()->toDateTimeString();

                                $userContactInsert = UserContacts::insert($userContactStore);
                            }
                            //For POCOR-8178 End

                            //Creating Institution_student_Admission...
                            $assigneeId = $this->getAssigneeId();

                            $storeAdmission['start_date'] = $academicPeriod['start_date'];
                            $storeAdmission['end_date'] = $academicPeriod['end_date'];
                            $storeAdmission['student_id'] = $student->id;
                            $storeAdmission['status_id'] = 81; //For Pending Approval..
                            $storeAdmission['institution_id'] = $request['institution_id'];
                            $storeAdmission['academic_period_id'] = $academicPeriod['id'];
                            $storeAdmission['education_grade_id'] = $request['education_grade_id'];
                            $storeAdmission['assignee_id'] = $assigneeId??$userData->user_id;
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


                        //For POCOR-8184 Start
                        if(isset($request['email'])){
                            $userContactStore['contact_type_id'] = 8; //For email
                            $userContactStore['value'] = $request['email'];
                            $userContactStore['preferred'] = 1;
                            $userContactStore['security_user_id'] = $userId;
                            $userContactStore['created_user_id'] = $userId;
                            $userContactStore['created'] = Carbon::now()->toDateTimeString();

                            $userContactInsert = UserContacts::insert($userContactStore);
                        }
                        //For POCOR-8184 End


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
                        $assigneeId = $this->getAssigneeId();
                        $storeAdmission['start_date'] = $academicPeriod['start_date'];
                        $storeAdmission['end_date'] = $academicPeriod['end_date'];
                        $storeAdmission['student_id'] = $student->id;
                        $storeAdmission['status_id'] = 81; //For Pending Approval..
                        $storeAdmission['institution_id'] = $request['institution_id'];
                        $storeAdmission['academic_period_id'] = $academicPeriod['id'];
                        $storeAdmission['education_grade_id'] = $request['education_grade_id'];
                        $storeAdmission['assignee_id'] = $assigneeId??$userData->user_id;
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


    public function validateCustomField($request)
    {
        try {
            $param = $request->all();
            
            $customFields = $this->getStudentCustomFields();
            
            
            

            $requiredCfArray = [];
            $requiredCfIds = [];
            $allCfIds = [];
            foreach($customFields as $k => $cf){
                if(is_numeric($cf['is_mandatory']) && $cf['is_mandatory'] == 1){
                    
                    //array_push($requiredCfArray, $cf);
                    array_push($requiredCfIds, $cf['student_custom_field_id']);
                }
            }
            

            if(count($requiredCfIds) > 0){
                if(isset($param['custom_fields']) && count($param['custom_fields']) > 0){
                    $customField = $param['custom_fields'];
                    
                    foreach($customField as $cf){
                        array_push($allCfIds, $cf['custom_field_id']);
                    }
                    

                    foreach($requiredCfIds as $reqCfId){
                        if(in_array($reqCfId, $allCfIds)){
                            $key = array_search($reqCfId, array_column($customField, 'custom_field_id'));
                            
                            if($key !== false){
                                $array = $customField[$key];
                                if($array['text_value'] != null || $array['number_value'] != null || $array['decimal_value'] != null || $array['textarea_value'] != null || $array['time_value'] != null || $array['dropdown_value'] != null || $array['checkbox_value'] != null || $array['file'] != null){
                                    //
                                } else {
                                    return 0;
                                }
                            }
                        } else {
                            return 0;
                        }
                    }

                } else {
                    return 0;
                }
            }



            $validationRule = $this->checkValidationRule($customFields, $param);
            
            if(is_array($validationRule) && count($validationRule) > 0){
                return $validationRule;
            }

            return 1;
        } catch (\Exception $e) {
            return 0;
        }
    }


    public function checkValidationRule($customFields, $param)
    {
        try {
            //dd($customFields, $param);
            $error = [];
            if(isset($param['custom_fields'])  && count($param['custom_fields']) > 0){

                foreach($param['custom_fields'] as $k => $cF){
                    $key = array_search($cF['custom_field_id'], array_column($customFields, 'student_custom_field_id'));
                    
                    if($key !== false){
                        $customFieldData = $customFields[$key]['student_custom_field'];

                        if(count($customFieldData) > 0){
                            $fieldType = $customFieldData['field_type'];
                            $params = $customFieldData['params'];
                            if(isset($params)){
                                
                                $paramArr = json_decode($params);

                                //Validating Date field type...
                                if($fieldType == 'DATE'){
                                    $dateErr = $this->validateDate($paramArr, $cF, $customFieldData['name']);

                                    if(is_array($dateErr) && count($dateErr) >0){
                                        return $dateErr;
                                    }
                                }

                                //Validating Time field type...
                                if($fieldType == 'TIME'){
                                    $timeErr = $this->validateTime($paramArr, $cF, $customFieldData['name']);

                                    if(is_array($timeErr) && count($timeErr) >0){
                                        return $timeErr;
                                    }
                                }


                                //Validating Number field type...
                                if($fieldType == 'NUMBER'){
                                    $numErr = $this->validateNumber($paramArr, $cF, $customFieldData['name']);

                                    if(is_array($numErr) && count($numErr) >0){
                                        return $numErr;
                                    }
                                }


                                //Validating Decimal field type...
                                if($fieldType == 'DECIMAL'){
                                    $decErr = $this->validateDecimal($paramArr, $cF, $customFieldData['name']);

                                    if(is_array($decErr) && count($decErr) >0){
                                        return $decErr;
                                    }
                                }
                            }


                            //Validating File field type...
                            if($fieldType == 'FILE'){

                                $fileErr = $this->validateFile($cF, $customFieldData['name']);
                                
                                if(is_array($fileErr) && count($fileErr) >0){
                                    return $fileErr;
                                }
                            }
                        }
                    }

                }
                
            }
            
            return $error;
        } catch (\Exception $e) {
            return [];
        }
    }



    public function validateDate($paramArr, $cF, $cFName)
    {
        try {
            $resp = [];

            $date_val = $cF['date_value']??"";
            $start_date = $paramArr->start_date??"";
            $end_date = $paramArr->end_date??"";

            if(!strtotime($date_val)){
                $resp['msg'] = $cFName. ' should be a date value.';
                return $resp;
            }
            
            if(isset($date_val) && $date_val != ""){
                if($start_date != "" && $end_date == ""){
                    if($date_val < $start_date){
                        $resp['msg'] = $cFName. ' should be earlier than '.$start_date;
                    }
                }

                if($start_date == "" && $end_date != ""){
                    if($date_val > $end_date){
                        $resp['msg'] = $cFName. ' should be later than '.$end_date;
                    }
                }

                if($start_date != "" && $end_date != ""){
                    if($date_val < $start_date || $date_val > $end_date){
                        $resp['msg'] = $cFName. ' should be between '.$start_date.' and '.$end_date;
                    }
                }
                
            }

            return $resp;
        } catch (\Exception $e){
            return [];
        }
    }

    public function validateTime($paramArr, $cF, $cFName)
    {
        try {
            $resp = [];

            $time_val = $cF['time_value']??"";
            $start_time = $paramArr->start_time??"";
            $end_time = $paramArr->end_time??"";

            if(!strtotime($time_val)){
                $resp['msg'] = $cFName. ' should be a time value.';
                return $resp;
            }

            if(isset($time_val) && $time_val != ""){
                $time_val = strtotime($time_val);

                $start_time_val = explode(" ", $start_time)[0]; //Removing AM/PM
                $end_time_val = explode(" ", $end_time)[0]; //Removing AM/PM

                
                if($start_time_val != "" && $end_time_val == ""){
                    $start_time_str = strtotime($start_time_val);

                    if($time_val < $start_time_str){
                        $resp['msg'] = $cFName. ' should be later than '.$start_time;
                    }
                }

                if($start_time_val == "" && $end_time_val != ""){
                    $end_time_str = strtotime($end_time_val);

                    if($time_val > $end_time_str){
                        $resp['msg'] = $cFName. ' should be earlier than '.$end_time;
                    }
                }

                if($start_time_val != "" && $end_time_val != ""){
                    $start_time_str = strtotime($start_time_val);
                    $end_time_str = strtotime($end_time_val);

                    if($time_val < $start_time_str || $time_val > $end_time_str){
                        $resp['msg'] = $cFName. ' should be between '.$start_time.' and '.$end_time;
                    }
                }
            }
            return $resp;

        } catch (\Exception $e) {
            return [];
        }
    }

    public function validateNumber($paramArr, $cF, $cFName)
    {
        try {
            $resp = [];

            $num_val = $cF['number_value']??"";
            
            $range = $paramArr->range??"";
            $min_value = $paramArr->min_value??"";
            $max_value = $paramArr->max_value??"";
            

            if($num_val != ""){
                if(!is_numeric($num_val)){
                    $resp['msg'] = $cFName. ' should be a numeric value.';
                    return $resp;
                }
                
                
                if(isset($range) && $range != ""){
                    if(isset($num_val) && $num_val != ""){

                        $lower = $range->lower??"";
                        $upper = $range->upper??"";

                        if($num_val < $lower || $num_val > $upper){
                            $resp['msg'] = $cFName. ' should be between '.$lower.' and '.$upper;
                        }
                    }
                }

                if(isset($num_val) && $num_val != ""){

                    if($min_value != "" && $max_value == ""){
                        if($num_val < $min_value){
                            $resp['msg'] = $cFName. ' should be greater than '.$min_value;
                        }
                    }

                    if($min_value == "" && $max_value != ""){
                        if($num_val > $max_value){
                            $resp['msg'] = $cFName. ' should be less than '.$max_value;
                        }
                    }
                }
            }
            return $resp;
        } catch (\Exception $e){
            return [];
        }
    }


    public function validateDecimal($paramArr, $cF, $cFName)
    {
        try {
            $resp = [];

            $dec_val = $cF['decimal_value']??"";
            $length = $paramArr->length??"";
            $precision = $paramArr->precision??"";
            

            if(isset($dec_val) && $dec_val != ""){
                $dec_val_arr = explode(".", $dec_val);
                if(count($dec_val_arr) == 1){
                    $resp['msg'] = $cFName. ' should be a decimal value.';
                    return $resp;
                }

                $dec_val_place = strlen($dec_val_arr[1]);
                
                if($dec_val_place > $precision) {
                    $resp['msg'] = $cFName. ' should have '.$precision.' decimal places.';

                    return $resp;
                }

            }
            return $resp;
        } catch (\Exception $e){
            return [];
        }
    }


    public function validateFile($cF, $cFName)
    {
        try {
            $resp = [];

            $file_link = $cF['file']??"";
            
            if($file_link != ""){
                if (!filter_var($file_link, FILTER_VALIDATE_URL)) {
                    
                    $resp['msg'] = "Invalid file url for ".$cFName.".";
                }
            }
            
            return $resp;
        } catch (\Exception $e) {
            return [];
        }
    }


    public function storeCustomField($customFieldsArr, $student_id, $user_id)
    {
        DB::beginTransaction();
        try {
            $cfArray = [];
            $fileArr = [];
            foreach($customFieldsArr as $k => $cf){
                $cfArray[$k]['id'] = Str::uuid();
                $cfArray[$k]['student_custom_field_id'] = $cf['custom_field_id'];
                $cfArray[$k]['text_value'] = $cf['text_value']??Null;
                $cfArray[$k]['number_value'] = $cf['number_value']??Null;
                $cfArray[$k]['decimal_value'] = $cf['decimal_value']??Null;
                $cfArray[$k]['textarea_value'] = $cf['textarea_value']??Null;
                $cfArray[$k]['time_value'] = $cf['time_value']??Null;
                $cfArray[$k]['date_value'] = $cf['date_value']??Null;

                if(isset($cf['file']) && ($cf['file']) != ""){
                    $file_name = basename($cf['file']);
                    
                    $fileContent = Http::get($cf['file'])->body();
                    
                    //$cfArray[$k]['file'] = file_get_contents($cf['file']);
                    $cfArray[$k]['file'] = $fileContent;
                    $cfArray[$k]['text_value'] = $file_name;
                    //$cfArray[$k]['text_value'] = $cf['file']->getClientOriginalName();

                    $fileArr[] = $cf['file'];

                } else {
                    $cfArray[$k]['file'] = Null;
                }
                
                $cfArray[$k]['student_id'] = $student_id;
                $cfArray[$k]['created_user_id'] = $user_id;
                $cfArray[$k]['created'] = Carbon::now()->toDateTimeString();
            }
            
            $store = StudentCustomFieldValues::insert($cfArray);
            
            //Removing the custom field files...

            foreach($fileArr as $file){
                $fileName = basename($file);
                $path = 'public/customfieldfiles/'.$fileName;
                Storage::delete($path);
            }
            Log::info("## Stored in StudentCustomFieldValues ##", $cfArray);
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
                //$data['last_name'] = $otpData->last_name;

                $email = $otpData->email;
                

                Mail::send('registrationSuccess', $data, function($message) use ($email) {
                    $message->to($email, 'OpenEMIS User')
                        ->subject(config('constants.emailConfig.registrationSuccessEmail.subject'));
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
            $newOpenemisNo = getNewOpenemisNo();
            return $newOpenemisNo;
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
            $customFields = StudentCustomFormField::with([

                'studentCustomField',
                'studentCustomField.studentCustomFieldOption:id as option_id,name as option_name,is_default,visible,order as option_order,student_custom_field_id'
            ])
            ->whereHas('studentCustomField')
            ->where('student_custom_form_id', 1)
            ->orderBy('order', 'ASC')
            ->get()
            ->toArray();
            //dd($customFields);
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
            $identityTypes = IdentityTypes::select('id', 'name')->orderBy('order', 'ASC')->get();
            
            return $identityTypes;
        } catch (\Exception $e) {
            Log::error(
                'Failed to find identity type list.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to find identity type list.');
        }
    }



    public function getInstitutionGradesList($request, $gradeId)
    {
        try {
            $institutions = new Institutions();

            $institutions = $institutions->select('institutions.*')->where('institutions.institution_status_id', '!=', 2);
            /*$institutions = $institutions->whereHas('educationGrades',
                    function ($query) use ($gradeId) {
                        $query->where('education_grade_id', $gradeId);
                    })->select('id', 'name', 'code');*/

            $institutions = $institutions->join('institution_grades', 'institution_grades.institution_id', '=', 'institutions.id')->where('institution_grades.education_grade_id', $gradeId);


            if($request['institution_type_id']){
                $institutions = $institutions->where('institution_type_id', $request['institution_type_id']);
            }


            if($request['area_id']){
                $institutions = $institutions->where('area_id', $request['area_id']);
            }

            $lists = $institutions->orderBy('institutions.name', 'ASC')->get();
            
            return $lists;
        } catch (\Exception $e) {

            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institutions List Not Found');
        }
    }


    public function institutionTypesDropdown()
    {
        try {
            $institutions = InstitutionTypes::select('id', 'name')->get();
            
            return $institutions;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institutions List Not Found');
        }
    }


    public function areaLevelsDropdown()
    {
        try {
            $areaLevels = AreaLevels::select('id', 'name')->get();
            
            return $areaLevels;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Area Levels List Not Found');
        }
    }


    public function areasDropdown($request)
    {
        try {
            $areas = Areas::select('id', 'name');

            if($request['area_level_id']){
                $areas = $areas->where('area_level_id', $request['area_level_id']);
            }

            $data = $areas->orderBy('name', 'ASC')->get();
            
            return $data;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Area Levels List Not Found');
        }
    }


    public function areaAdministrativeLevelsDropdown()
    {
        try {
            $areaLevels = AreaAdministrativeLevels::select('id', 'name')->get();
            
            return $areaLevels;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Area Levels List Not Found');
        }
    }



    public function areasAdministrativeDropdown($request)
    {
        try {
            $areas = AreaAdministrativeLevels::select('id', 'name');

            if($request['area_level_id']){
                $areas = $areas->where('area_administrative_level_id', $request['area_administrative_level_id']);
            }

            $data = $areas->orderBy('name', 'ASC')->get();
            
            return $data;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Area Administrative Levels List Not Found');
        }
    }

    
    public function validateAge($dob, $educationGradeId, $academicPeriodId)
    {
        try {  
            $agePlusData = ConfigItem::where('code', 'admission_age_plus')->first();

            $ageMinusData = ConfigItem::where('code', 'admission_age_minus')->first();
            if($agePlusData && $ageMinusData){
                $agePlusVal = $agePlusData->value??0;
                $ageMinusVal = $ageMinusData->value??0;

                $academicPeriod = AcademicPeriod::where('id', $academicPeriodId)->first();
                if(!$academicPeriod){
                    return 0;
                }

                $dobArr = explode("-", $dob);
                $dobYear = $dobArr[0];
                $acadmicPeriodYear = $academicPeriod->end_year;

                $studentAge = $acadmicPeriodYear - $dobYear;

                $educationGrade = EducationGrades::where('id', $educationGradeId)->first();
                if(!$educationGrade){
                    return 0;
                }

                $admissionAge = $educationGrade->admission_age;

                $lowerLimit = $admissionAge - $ageMinusVal;

                //If lower limit is in -ve...
                if($lowerLimit < 0){
                    $lowerLimit = 0;
                }
                
                $upperLimit = $admissionAge + $agePlusVal;

                if(($studentAge < $lowerLimit) || ($studentAge > $upperLimit)){
                    //$arr['loweAgeLimit'] = $lowerLimit;
                    //$arr['upperAgeLimit'] = $upperLimit;

                    $arr['msg'] = "The student should be between ".$lowerLimit." to ".$upperLimit. " years old.";

                    return $arr;
                } else {
                    return 1;
                }

            } else {
                return 0;
            }
        } catch (\Exception $e) {
            Log::error(
                'Failed to validate age.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to validate age.');
        }
    }

    
    public function getAssigneeId()
    {
        try {
            $data = SecurityGroupUsers::join('workflow_steps_roles', 'workflow_steps_roles.security_role_id', '=', 'security_group_users.security_role_id')
                ->join('workflow_steps', 'workflow_steps.id', '=', 'workflow_steps_roles.workflow_step_id')
                ->join('workflows', 'workflows.id', '=', 'workflow_steps.workflow_id')
                ->where('workflows.code', 'STUDENT-ADMISSION-1001')
                ->where('workflow_steps.name', 'Pending Approval')
                ->select('security_group_users.security_role_id', 'security_group_users.security_user_id')
                ->first();

            return $data->security_user_id??Null;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch assignee id from DB.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch assignee id from DB.');
        }
    }



    public function storecustomfieldfile($request)
    {
        try {
            $params = $request->all();
            $resp = [];
            if(count($params) > 0){
                
                $storage_path = 'public/customfieldfiles';

                Storage::deleteDirectory($storage_path);
                if (!Storage::exists($storage_path)) {
                    Storage::makeDirectory($storage_path, 0755);
                }
                
                foreach($params['custom_field'] as $key => $param){
                    
                    $file = $param['file'];
                    $str = rand(0000,9999).substr(time(), 0, -4);

                    $file_name = $str.'_'.$file->getClientOriginalName();
                    $file_name = str_replace(" ", "_", $file_name);
                    
                    $file->storeAs($storage_path,$file_name);

                    $path = asset('public/storage/customfieldfiles/'.$file_name);
                    
                    $resp[$key]['custom_field_id'] = $param['custom_field_id'];
                    $resp[$key]['file_name'] = $file_name;
                    $resp[$key]['file_path'] = $path;
                    
                }
                return $resp;
            } else {
                return 0;
            }
        } catch (\Exception $e) {
            
            Log::error(
                'Failed to store file.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to store file.');
        }
    }

}

