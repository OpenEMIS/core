<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function sendErrorResponse($message, $data = [])
    {
        return response()->json(
            [
                'message' => $message,
                'data' => $data,
            ],
            config('constantvalues.statusCodes.resourceNotFound')
        );
    }

    public function sendFieldErrorResponse($message, $data = [])
    {
        return response()->json(
            [
                'message' => $message,
                'data' => $data,
            ],
            config('constantvalues.statusCodes.fieldNotFound')
        );
    }

    public function sendSuccessResponse($message, $data = [])
    {
        return response()->json(
            [
                'message' => $message,
                'data' => $data,
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
}
