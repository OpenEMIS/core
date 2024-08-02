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

            if (isset($userCheck)) {
                $input = $request->only('username', 'password');
                $token = null;
                $api_key = $request->api_key ?? "";

                $apiCredentials = ApiCredentials::where('api_key', $api_key)->first();
                if (!$apiCredentials) {
                    return $this->sendErrorResponse("Invalid API key provided.");
                }


                if (!$token = JWTAuth::attempt($input)) {
                    return $this->sendErrorResponse('Invalid Username or Password.');
                }


                return $this->sendSuccessResponse('Logged In successfully', ['token' => $token, 'client_id' => $apiCredentials->client_id ?? ""]);
            } else {
                return $this->sendErrorResponse("Invalid Username or Password.");
            }
        } catch (\Exception $e) {
            Log::error(
                'Failed to login.',
                ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse("You Are Not Authorized To Access This Page");
        }
    }
}
