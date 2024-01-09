<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;


    public function sendErrorResponse($message, $data = [], $success=false, $statusCode = null)
    {
        return response()->json(
            [
                'message' => $message,
                'data' => $data,
                'success' => $success
            ],
            $statusCode ?? config('constantvalues.statusCodes.resourceNotFound')
        );
    }

    public function sendFieldErrorResponse($message, $data = [], $success=false)
    {
        return response()->json(
            [
                'message' => $message,
                'data' => $data,
                'success' => $success
            ],
            config('constantvalues.statusCodes.fieldNotFound')
        );
    }

    public function sendSuccessResponse($message, $data = [], $success=true)
    {
        return response()->json(
            [
                'message' => $message,
                'data' => $data,
                'success' => $success
            ],
            config('constantvalues.statusCodes.success')
        );
    }


    public function changeDateFormat($date)
    {
        return (Carbon::createFromFormat('Y-m-d', $date)->toDateString());
    }

    public function sendDeleteErrorResponse($message)
    {
        return response()->json(
            [
                'message' => $message,
            ],
            config('constantvalues.statusCodes.deleteError')
        );
    }


    public function sendAuthorizationErrorResponse($message = '', $data = [])
    {
        return response()->json(
            [
                'message' => "You are not authorized to access this API.",
                'data' => $data,
            ],
            403
        );
    }
}