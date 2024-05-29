<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;


    public function sendErrorResponse($desc, $data = [], $success="", $statusCode = null)
    {
        $message = "Unsuccessful.";
        if($success === false){
            return response()->json(
                [
                    'message' => $message,
                    'description' => $desc,
                    'data' => $data,
                    'success' => $success
                ],
                $statusCode ?? config('constantvalues.statusCodes.resourceNotFound')
            );
        } else {
            return response()->json(
                [
                    'message' => $message,
                    'description' => $desc,
                    'data' => $data,
                ],
                $statusCode ?? config('constantvalues.statusCodes.resourceNotFound')
            );
        }
    }

    public function sendFieldErrorResponse($message, $data = [], $success=false)
    {
        $message = "Unsuccessful.";
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
        $message = "Successful.";
        return response()->json(
            [
                'message' => $message,
                'data' => $data,
                //'success' => $success
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


    public function sendAuthorizationErrorResponse($message = '', $data = [], $success=false)
    {
        return response()->json(
            [
                'message' => "Unsuccessful.",
                'description' => "You are not authorized to access this API.",
                'data' => $data,
                'success' => $success
            ],
            403
        );
    }


    public function sendServerErrorResponse($desc, $data=[], $success="", $statusCode = null)
    {
        $message = "Unsuccessful.";
        if($success === false){
            return response()->json(
                [
                    'message' => $message,
                    'description' => $desc,
                    'success' => $success,
                    'data' => $data,
                ],
                $statusCode ?? config('constantvalues.statusCodes.internalError')
            );
        } else {
            return response()->json(
                [
                    'message' => $message,
                    'description' => $desc,
                    'data' => $data,
                ],
                $statusCode ?? config('constantvalues.statusCodes.internalError')
            );
        }
    }
}