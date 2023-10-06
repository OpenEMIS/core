<?php

namespace App\Http\Controllers\Authentication;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use Carbon\Carbon;
use Hash; 
use App\Models\SecurityUsers;
use App\Models\ApiCredentials;
use JWTAuth;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        try{
            
            $userCheck = SecurityUsers::where('username', $request->username)->first();
            
            if($userCheck->super_admin == config('constantvalues.canLogIn.superAdmin') || $userCheck->is_staff == config('constantvalues.canLogIn.isStaff') || $userCheck->is_student == 1){
                $input = $request->only('username', 'password');
                $token = null;
                $api_key = $request->api_key??"";

                $apiCredentials = ApiCredentials::where('api_key', $api_key)->first();
                if(!$apiCredentials){
                    return $this->sendErrorResponse("Invalid API key provided.");
                }


                if (!$token = JWTAuth::attempt($input)) {
                    return $this->sendErrorResponse('Invalid Email or Password');
                }
                

                return $this->sendSuccessResponse('Logged In successfully', ['token' => $token, 'client_id' => $apiCredentials->client_id??""]); 
            } else {
                return $this->sendErrorResponse("You Are Not Authorized To Access This Page");
            }
                
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse("You Are Not Authorized To Access This Page");
        }
        
    }
}
