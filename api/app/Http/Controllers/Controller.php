<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;


/**
 * @OA\Info(
 *     title="OpenEMIS Core API V4",
 *     description="The [OpenEMIS](https://www.openemis.org/) initiative aims to deploy a high-quality Education Management Information System (EMIS) designed to collect and report data on schools, students, teachers and staff. The system was conceived by `UNESCO` to be a royalty-free system that can be easily customized to meet the specific needs of member countries.",
 *     termsOfService="https://www.openemis.org/terms-of-service/",
 *     version="4.0.0",
 *      @OA\License(
 *          name="GNU General Public License V3.0",
 *          url="https://www.gnu.org/licenses/gpl-3.0.en.html"
 *      ),
 *      @OA\Contact(
 *          email="support@openemis.org"
 *      ),
 * ),
 *  @OA\Server(
 *      url="https://demo.openemis.org/core"
 *  ),
 */
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
        $message = $message ?? "Successful."; // POCOR-8915
        return response()->json(
            [
                'message' => $message,
                'data' => $data,
                //'success' => $success
            ],
            config('constantvalues.statusCodes.success')
        );
    }

    // POCOR-8915 start
    public function sendCreateSuccessResponse($message, $data = [], $success=true)
    {
        $message = $message ?? "Successful.";
        return response()->json(
            [
                'message' => $message,
                'data' => $data,
                //'success' => $success
            ],
            config('constantvalues.statusCodes.createSuccess')
        );
    }
    // POCOR-8915 end


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
