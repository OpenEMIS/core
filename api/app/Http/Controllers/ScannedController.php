<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ScannedService;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\ScannedAttendanceRequest;

class ScannedController extends Controller
{
    protected $scannedService;

    public function __construct(ScannedService $scannedService) 
    {
        $this->scannedService = $scannedService;
    }

    /**
    * @OA\Post(
    *     path="/api/v4/scanned",
    *     summary="Submit scanned data",
    *     description="Endpoint for submitting scanned data with location and timestamp.",
    *     tags={"Scanned"},
    *     @OA\Parameter(
    *         name="token",
    *         in="header",
    *         required=true,
    *         description="Authentication token",
    *         @OA\Schema(type="string", example="your_auth_token_here")
    *     ),
    *     @OA\RequestBody(
    *         required=true,
    *         description="Scanned data payload",
    *         @OA\JsonContent(
    *             required={"openemis_no", "datetime", "latitude", "longitude"},
    *             @OA\Property(
    *                 property="openemis_no",
    *                 type="string",
    *                 description="OpenEMIS identification number",
    *                 example="1234567890"
    *             ),
    *             @OA\Property(
    *                 property="datetime",
    *                 type="string",
    *                 format="date-time",
    *                 description="Timestamp of the scan in ISO 8601 format",
    *                 example="2024-10-25T14:30:00Z"
    *             ),
    *             @OA\Property(
    *                 property="latitude",
    *                 type="number",
    *                 format="float",
    *                 description="Latitude of the scanned location",
    *                 example=25.276987
    *             ),
    *             @OA\Property(
    *                 property="longitude",
    *                 type="number",
    *                 format="float",
    *                 description="Longitude of the scanned location",
    *                 example=55.296249
    *             )
    *         )
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Scanned data submitted successfully.",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="message", type="string", example="Scanned data submitted successfully."),
    *             @OA\Property(property="data", type="object",
    *                 @OA\Property(property="status", type="string", example="success")
    *             )
    *         )
    *     ),
    *     @OA\Response(
    *         response=400,
    *         description="Invalid request. Required fields are missing or invalid."
    *     ),
    *     @OA\Response(
    *         response=401,
    *         description="Unauthorized. Invalid or missing token."
    *     )
    * )
    */


    public function addScannedUserData(ScannedAttendanceRequest $request)
    {

        try {
            $data = $this->scannedService->addScannedUser($request);
            if($data == 1){
                return $this->sendSuccessResponse("Scanned User Data Added successfully.");
            } else {
                return $this->sendErrorResponse("Scanned User Data not Added successfully.");
            }
        } catch (\Exception $e) {
            dd($e);
            Log::error(
                'Failed to save Scanned User Data in DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to save Scanned User Data in DB');
        }
    }

    public function updateScannedUserData($scannedId, ScannedAttendanceRequest $request)
    {
        try {
            $data = $this->scannedService->updateScannedUser($scannedId, $request);
            if($data == 1){
                return $this->sendSuccessResponse("Scanned User Data Update successfully.");
            } else {
                return $this->sendErrorResponse("Scanned User Data not Update successfully.");
            }
        } catch (\Exception $e) {
            Log::error(
                'Failed to Update Scanned User Data in DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to Update Scanned User Data in DB');
        }
    }

    public function scannedUserListing(Request $request)
    {
        try {
            $listing = $this->scannedService->scannedUserListing($request);
            return $listing;
        } catch (\Exception $e) {
            Log::error(
                'Failed to Update Scanned User Data in DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to Update Scanned User Data in DB');
        }
    }


   
}