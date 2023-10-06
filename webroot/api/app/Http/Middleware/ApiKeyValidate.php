<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Hash;

class ApiKeyValidate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        /*$username = env('API_USERNAME');
        $password = env('API_PASSWORD');
        
        $api_key = env('API_KEY');*/
        
        return $next($request);
    }
}
