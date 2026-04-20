<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Illuminate\Support\Facades\Log;
use App\Models\Api5\SecurityUsers; //POCOR-9591: status constants for account lock check

// POCOR-9092 added some login
class AuthenticateJwt
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                Log::warning('AuthenticateJwt: Token parsed but no user found.', [
                    'token' => $request->bearerToken(),
                    'time' => now()->toDateTimeString(),
                ]);
                return response()->json(['error' => 'Unauthorized (no user)'], 401);
            }

            //POCOR-9591: start - reject locked or inactive accounts even with a valid token
            if ($user->status === SecurityUsers::STATUS_LOCKED) {
                return response()->json(['error' => 'Account is locked'], 401);
            }
            if ($user->status === SecurityUsers::STATUS_INACTIVE) {
                return response()->json(['error' => 'Account is inactive'], 401);
            }
            //POCOR-9591: end

        } catch (TokenExpiredException $e) {
            Log::warning('AuthenticateJwt: Token expired.', [
                'message' => $e->getMessage(),
                'token' => $request->bearerToken(),
            ]);
            return response()->json(['error' => 'Token Expired'], 401);

        } catch (TokenInvalidException $e) {
            Log::warning('AuthenticateJwt: Token invalid.', [
                'message' => $e->getMessage(),
                'token' => $request->bearerToken(),
            ]);
            return response()->json(['error' => 'Token Invalid'], 401);

        } catch (JWTException $e) {
            Log::warning('AuthenticateJwt: JWT Exception.', [
                'message' => $e->getMessage(),
                'token' => $request->bearerToken(),
            ]);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
