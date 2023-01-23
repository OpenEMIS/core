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
use Mail;

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

            $isExists = SecurityUsers::where('email', $email)->first();
            //$isExists = 1;
            if($isExists){
                $otpData = $this->getUniqueOtp($email);
                $otp = $otpData['otp'];
                $encodedOtp = $otpData['encodedOtp'];

                $data['otp'] = $otp;

                Mail::send(['text'=>'generateOtp'], $data, function($message) {
                    $message->to('ravi.verma111@mailinator.com', 'OpenEMIS User')
                        ->subject('OpenEMIS Registration OTP Verification.');
                });

                $insertData['security_user_id'] = $isExists->id??2;
                $insertData['verification_otp'] = $encodedOtp;
                $insertData['created'] = Carbon::now()->toDateTimeString();
                $store = SecurityUserCode::insert($insertData);
                return 1;
            } else {
                return 0;
            }
            
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
            $securityUser = SecurityUsers::where('email', $params['email'])->first();
            if($securityUser){
                $otp = $params['otp'];
                $encodedOtp = base64_encode($otp);
                $checkOtp = SecurityUserCode::where('verification_otp', $encodedOtp)
                        ->where('security_user_id', $securityUser->id)
                        ->first();
                if($checkOtp){
                    return 1;
                } else {
                    return 2;
                }
            } else {
                return 0;
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
                    if($student == $request['date_of_birth']){
                        if(isset($student['institutionStudent']['studentStatus']['name']) == 'Enrolled'){
                            return 4; //registration unsuccessful – student already enrolled
                        } else {
                            //
                        }
                    } else {
                        dd("qqq");
                        return 2; //registration unsuccessful – student details do not match
                    }
                } else {
                    dd("www");
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
                    if($student == $request['date_of_birth']){
                        if(isset($student['institutionStudent']['studentStatus']['name']) == 'Enrolled'){
                            return 4; //registration unsuccessful – student already enrolled
                        } else {
                            //
                        }
                    } else {
                        dd("eee");
                        return 2; //registration unsuccessful – student details do not match
                    }
                } else {
                    dd("rrr");
                    return 55; //registration unsuccessful – identity_number not found
                }
            } else {
                dd("else");
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
}

