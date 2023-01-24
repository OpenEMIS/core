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
use Mail;
use Illuminate\Support\Str;

class RegistrationRepository extends Controller
{


    public function academicPeriodsList()
    {
        try {
            $academicPeriods = AcademicPeriod::select('id', 'name')->orderBy('id','DESC')->get();
            
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
            $educationGrades = EducationGrades::select('id', 'name')->get();
            
            return $educationGrades;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Education Grade List Not Found');
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

            $data['otp'] = $otp;

            Mail::send(['text'=>'generateOtp'], $data, function($message) use($email) {
                $message->to($email, 'OpenEMIS User')
                    ->subject('OpenEMIS Registration OTP Verification.');
            });

            $insertData['email'] = $email;
            $insertData['otp'] = $encodedOtp;
            $insertData['is_expired'] = 0;
            $insertData['created'] = Carbon::now()->toDateTimeString();
            $store = RegistrationOtp::insert($insertData);
            return 1;
            
        } catch (\Exception $e) {
            dd($e);
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
            $checkOtp = RegistrationOtp::where('otp', $encodedOtp)
                    ->where('email', $params['email'])
                    ->first();

            if($checkOtp){
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


    public function autocompleteIdentityNo($id)
    {
        try {
            $data = SecurityUsers::select('id as key', 'identity_number as value')->where('identity_number', 'LIKE', '%'.$id.'%')->get()->toArray();
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
        try {
            //dd($request->all());
            if(isset($request['openemis_id'])){
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
                    if($dob == $request['date_of_birth']){

                        if(isset($student['institutionStudent']['studentStatus']['name']) && $student['institutionStudent']['studentStatus']['name'] == 'Enrolled'){
                            
                            return 4; //registration unsuccessful – student already enrolled
                        } else {
                            $stuStatus = StudentStatuses::where('name', 'Pending')->first();
                            $academicPeriod = AcademicPeriod::where('id', $request['academic_period_id'])->first();

                            $storeStu['id'] = Str::uuid();
                            $storeStu['student_status_id'] = $stuStatus->id??0;
                            $storeStu['student_id'] = $student->id;
                            $storeStu['education_grade_id'] = $request['education_grade_id'];
                            $storeStu['academic_period_id'] = $request['academic_period_id'];
                            $storeStu['start_date'] = $academicPeriod['start_date'];
                            $storeStu['start_year'] = $academicPeriod['start_year'];
                            $storeStu['end_date'] = $academicPeriod['end_date'];
                            $storeStu['end_year'] = $academicPeriod['end_year'];
                            $storeStu['institution_id'] = $request['institution_id'];
                            $storeStu['previous_institution_student_id'] = $student['institutionStudent']['id']??NULL;
                            $storeStu['created_user_id'] = 2;
                            $storeStu['created'] = Carbon::now()->toDateTimeString();

                            $store = InstitutionStudent::insert($storeStu);
                            
                            if(isset($request['otp'])){
                                $sendMail = $this->sendSuccessMail($request);
                            }
                            return 1;
                        }
                    } else {
                        return 2; //registration unsuccessful – student details do not match
                    }
                } else {
                    return 3; //registration unsuccessful – openemis_no not found
                }

            } elseif (isset($request['identity_number'])) {
                $student = SecurityUsers::with(
                        'gender',
                        'nationalities',
                        'institutionStudent',
                        'institutionStudent.institution',
                        'institutionStudent.studentStatus'
                    )
                    ->where('identity_number', $request['identity_number'])
                    ->first();

                if($student){
                    $dob = $student['date_of_birth']->format('Y-m-d');
                    if($dob == $request['date_of_birth']){
                        if(isset($student['institutionStudent']['studentStatus']['name']) == 'Enrolled'){
                            return 4; //registration unsuccessful – student already enrolled
                        } else {
                            $stuStatus = StudentStatuses::where('name', 'Pending')->first();
                            $academicPeriod = AcademicPeriod::where('id', $request['academic_period_id'])->first();

                            $storeStu['id'] = Str::uuid();
                            $storeStu['student_status_id'] = $stuStatus->id??0;
                            $storeStu['student_id'] = $student->id;
                            $storeStu['education_grade_id'] = $request['education_grade_id'];
                            $storeStu['academic_period_id'] = $request['academic_period_id'];
                            $storeStu['start_date'] = $academicPeriod['start_date'];
                            $storeStu['start_year'] = $academicPeriod['start_year'];
                            $storeStu['end_date'] = $academicPeriod['end_date'];
                            $storeStu['end_year'] = $academicPeriod['end_year'];
                            $storeStu['institution_id'] = $request['institution_id'];
                            $storeStu['previous_institution_student_id'] = $student['institutionStudent']['id']??NULL;
                            $storeStu['created_user_id'] = 2;
                            $storeStu['created'] = Carbon::now()->toDateTimeString();

                            $store = InstitutionStudent::insert($storeStu);
                            if(isset($request['otp'])){
                                $sendMail = $this->sendSuccessMail($request);
                            }
                            return 1;
                        }
                    } else {
                        return 2; //registration unsuccessful – student details do not match
                    }
                } else {
                    return 5; //registration unsuccessful – identity_number not found
                }
            } else {
                $configItem = ConfigItem::where('code', 'NewStudent')->first();
                if(isset($configItem) && $configItem == 1){
                    dd("add new student");
                } else {
                    return 6; //registration unsuccessful – not able to create new student
                }
            }
            
        } catch (\Exception $e) {
            dd($e);
            Log::error(
                'Failed to register student.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to register student.');
        }
    }


    public function sendSuccessMail($request)
    {
        try {
            $param = $request->all();
            
            $encodedOtp = base64_encode($request['otp']??"");
            $otpData = RegistrationOtp::where('otp', $encodedOtp)->first();

            if($otpData){
                $data = [];
                $email = $otpData->email;
                Mail::send(['text'=>'registrationSuccess'], $data, function($message) use ($email) {
                    $message->to($email, 'OpenEMIS User')
                        ->subject('OpenEMIS Registration Success Email.');
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

}

