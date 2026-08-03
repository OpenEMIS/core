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


    /**
     * @OA\Post(
     *     path="/api/v4/login",
     *     summary="Login endpoint",
     *     tags={"Authentication"},
     *     description="Authenticate user and retrieve access token",
     *     @OA\Parameter(
     *         name="username",
     *         in="query",
     *         required=true,
     *         example="admin",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="password",
     *         in="query",
     *         required=true,
     *         example="demo",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="api_key",
     *         in="query",
     *         required=true,
     *         example="apikeytest",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC9sb2NhbGhvc3RcL3BvY29yLW9wZW5lbWlzLWNvcmVcL2FwaVwvdjRcL2xvZ2luIiwiaWF0IjoxNzEyODk5MTYyLCJleHAiOjE3MTI5MDI3NjIsIm5iZiI6MTcxMjg5OTE2MiwianRpIjoiVlo1YnFjeXFNUXVSMHZTaSIsInN1YiI6MiwicHJ2IjoiZTIxNDlmNmY1NGFiZWYxYzdkNjYzM2E1M2M5MjJjNTc4MTgwNWU4YyJ9.ozhynp6UBJA11ibptkc_hMGQSyrDiM0ZZMKEtZRAhog"),
     *                 @OA\Property(property="client_id", type="string", example="1678073692-e3b037ab67ee2b8a.app")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
    public function login(Request $request)
    {
        try {
            $userCheck = SecurityUsers::where('username', $request->username)->first();
            if (!$userCheck) {
                return $this->sendErrorResponse("Invalid Username or Password.");
            }
            //POCOR-9591: start - block locked and inactive accounts before attempting auth
            if ($userCheck->status === SecurityUsers::STATUS_LOCKED) {
                return $this->sendErrorResponse('Account is locked.', [], "", 403);
            }
            if ($userCheck->status === SecurityUsers::STATUS_INACTIVE) {
                return $this->sendErrorResponse('Account is inactive.', [], "", 403);
            }
            //POCOR-9591: end
            // Validate API key
            $apikey = $request->api_key ?? "";
            $apiCredentials = ApiCredentials::where('api_key', $apikey)->first();
            if (!$apiCredentials) {
                return $this->sendErrorResponse("Invalid API key provided.");
            }
            $password = $request->password;
            //POCOR-9745 Check encryption type
            if ($request->has('enc') && !empty($request->enc)) {
                $privateKey = config('services.rsa_private_key');
                $key = openssl_pkey_get_private($privateKey);
                if (!$key) {
                    return $this->sendErrorResponse('Invalid private key.');
                }
                switch ($request->enc) {
                    case 'RSA-OAEP-256':
                        $result = openssl_private_decrypt(
                            base64_decode($password),
                            $decryptedPassword,
                            $key,
                            OPENSSL_PKCS1_OAEP_PADDING
                        );
                        openssl_free_key($key);
                        if (!$result) {
                            return $this->sendErrorResponse('Failed to decrypt password.');
                        }
                        $password = $decryptedPassword;
                        break;
                    default:
                        openssl_free_key($key);
                        return $this->sendErrorResponse('Unsupported encryption type.');
                }
            }
            $input = [
                'username' => $request->username,
                'password' => $password
            ];

            $token = JWTAuth::attempt($input);

            if (!$token) {
                //POCOR-9591: start - increment failed_logins; lock when threshold reached
                $threshold = (int) DB::table('config_items')->where('code', 'login_attempts')->value('value') ?: 5;
                $newCount = $userCheck->failed_logins + 1;
                if ($newCount >= $threshold) {
                    SecurityUsers::where('id', $userCheck->id)->update([
                        'failed_logins' => $newCount,
                        'status'        => SecurityUsers::STATUS_LOCKED,
                    ]);
                    return $this->sendErrorResponse('Account is locked due to too many failed login attempts.', [], "", 403);
                }
                SecurityUsers::where('id', $userCheck->id)->update(['failed_logins' => $newCount]);
                //POCOR-9591: end
                return $this->sendErrorResponse('Invalid Username or Password.');
            }
            //POCOR-9591: reset failed login counter on successful authentication
            SecurityUsers::where('id', $userCheck->id)->update(['failed_logins' => 0]);

            return $this->sendSuccessResponse(
                'Logged In successfully',
                [
                    'token' => $token,
                    'client_id' => $apiCredentials->client_id ?? ''
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed to login.', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->sendErrorResponse("You Are Not Authorized To Access This Page");
        }
    }
}
