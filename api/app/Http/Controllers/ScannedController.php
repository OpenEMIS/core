<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ScannedService;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\ScannedAttendanceRequest;
use App\Exports\InstitutionScannedExport;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * POCOR-8666
 * ScannedController handles the scanned data operations.
 * It interacts with the ScannedService to manage scanned records.
 */
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
    *     description="Endpoint for submitting scanned data with location, timestamp, and scanner details.",
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
    *             required={"openemis_no", "datetime"},
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
    *             ),
    *             @OA\Property(
    *                 property="location",
    *                 type="string",
    *                 description="Human-readable location description",
    *                 example="Main Building Entrance"
    *             ),
    *             @OA\Property(
    *                 property="access",
    *                 type="string",
    *                 description="Access level or type of scan",
    *                 example="employee"
    *             ),
    *          
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
            
            if (empty($request->all())) {
                return $this->sendErrorResponse("Missing data. Please provide scanned user data.");
            }
            
            $data = $this->scannedService->addScannedUser($request);

            if ($data == 1) {
                return $this->sendSuccessResponse("Scanned User Data added successfully.");
            } else {
                return $this->sendErrorResponse(
                    "OpenEMIS ID does not exist. Kindly check OpenEMIS ID"
                );
            }
        } catch (\Exception $e) {
            Log::error(
                'Failed to save Scanned User Data in DB',
                ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to save Scanned User Data in DB');
        }
    }

    /**
    * @OA\Get(
    *     path="/api/v4/scanned/{openemis_no}",
    *     summary="Retrieve scanned data by OpenEMIS number",
    *     description="Endpoint to fetch scanned data based on the provided OpenEMIS number.",
    *     tags={"Scanned"},
    *     @OA\Parameter(
    *         name="openemis_no",
    *         in="path",
    *         required=true,
    *         description="OpenEMIS identification number",
    *         @OA\Schema(type="string", example="1234567890")
    *     ),
    *     @OA\Parameter(
    *         name="token",
    *         in="header",
    *         required=true,
    *         description="Authentication token",
    *         @OA\Schema(type="string", example="your_auth_token_here")
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Scanned data retrieved successfully.",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="message", type="string", example="Scanned data retrieved successfully."),
    *             @OA\Property(property="data", type="object",
    *                 @OA\Property(property="openemis_no", type="string", example="1234567890"),
    *                 @OA\Property(property="datetime", type="string", format="date-time", example="2024-10-25T14:30:00Z"),
    *                 @OA\Property(property="latitude", type="number", format="float", example=25.276987),
    *                 @OA\Property(property="longitude", type="number", format="float", example=55.296249),
    *                 @OA\Property(property="location", type="string", example="Main Building Entrance"),
    *                 @OA\Property(property="access", type="string", example="employee"),
    *                 
    *             )
    *         )
    *     ),
    *     @OA\Response(
    *         response=400,
    *         description="Invalid request. OpenEMIS number is missing or invalid."
    *     ),
    *     @OA\Response(
    *         response=401,
    *         description="Unauthorized. Invalid or missing token."
    *     ),
    *     @OA\Response(
    *         response=404,
    *         description="Scanned data not found for the provided OpenEMIS number."
    *     )
    * )
    */
    public function scannedUserOpenemisNo($params, Request $request)
    {
        try {
            $scannedUserData = $this->scannedService->scannedOpenemisNo($params,$request);
            return $scannedUserData;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Scanned User Data',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch Scanned User Data');
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v4/scanned/data/export",
     *     summary="Export scanned data",
     *     description="Endpoint to export scanned data for a specific OpenEMIS number or all scan data as well.",
     *     tags={"Scanned"},
     *     @OA\Parameter(
     *         name="openemis_no",
     *         in="query",
     *         required=false,
     *         description="OpenEMIS identification number to filter scanned data.",
     *         @OA\Schema(type="string", example="1022290909")
     *     ),
     *     @OA\Parameter(
     *         name="token",
     *         in="header",
     *         required=true,
     *         description="Authentication token",
     *         @OA\Schema(type="string", example="your_auth_token_here")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Scanned data exported successfully.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Scanned data exported successfully."),
     *             @OA\Property(property="data", type="object", 
     *                 @OA\Property(property="file_url", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid request. Missing or invalid parameters."
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized. Invalid or missing token."
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error while processing the export."
     *     )
     * )
     */
    public function institutionScannedExport(Request $request)
    {
        try {
            $params = $request->all();
            $data = $this->scannedService->institutionScannedDataExport($params);
            $str = time();
            $fileName = 'InstitutionScanned'.$str.'.xlsx';
            return Excel::download(new InstitutionScannedExport($data), $fileName);

        } catch (\Exception $e) {
            Log::error(
                'Failed to export Scanned User Data from DB.',
                ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Failed to export Scanned User Data from DB.');
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v4/scanned",
     *     summary="Get scanned user listing",
     *     description="Endpoint to retrieve scanned user listing with optional filters.",
     *     tags={"Scanned"},
     *     @OA\Parameter(
     *         name="date_from",
     *         in="query",
     *         required=false,
     *         description="Filter records from this date (YYYY-MM-DD).",
     *         @OA\Schema(type="string", format="date", example="2024-12-24")
     *     ),
     *     @OA\Parameter(
     *         name="date_to",
     *         in="query",
     *         required=false,
     *         description="Filter records up to this date (YYYY-MM-DD).",
     *         @OA\Schema(type="string", format="date", example="2024-12-24")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Number of records to retrieve per page.",
     *         @OA\Schema(type="integer", example=30)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number for paginated results.",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="token",
     *         in="header",
     *         required=true,
     *         description="Authentication token",
     *         @OA\Schema(type="string", example="your_auth_token_here")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Scanned user listing retrieved successfully.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Scanned user listing retrieved successfully."),
     *             @OA\Property(property="data", type="array", 
     *                 @OA\Items(
     *                     @OA\Property(property="openemis_no", type="string", example="1022290909"),
     *                     @OA\Property(property="datetime", type="string", format="date-time", example="2024-12-24T12:34:56"),
     *                     @OA\Property(property="latitude", type="string", example="87.7675"),
     *                     @OA\Property(property="longitude", type="string", example="34.9878"),
     *                     @OA\Property(property="location", type="string", example="Sample Location"),
     *                     @OA\Property(property="access", type="string", example="Granted")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid request. Missing or invalid parameters.",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Missing required parameters.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized. Invalid or missing token.",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthorized.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error while processing the request.",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="An error occurred while retrieving the scanned user listing.")
     *         )
     *     )
     * )
     */
    public function scannedUserListing(Request $request)
    {

        try {
            $listing = $this->scannedService->scannedUserListing($request);
            return $listing;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Scanned User Data',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Failed to fetch Scanned User Data');
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v4/scanned/user/{scannedId}",
     *     summary="Get scanned user details",
     *     description="Retrieve details for a specific scanned user by their scanned ID.",
     *     tags={"Scanned"},
     *     @OA\Parameter(
     *         name="scannedId",
     *         in="path",
     *         required=true,
     *         description="The unique ID of the scanned user.",
     *         @OA\Schema(type="integer", example=16)
     *     ),
     *     @OA\Parameter(
     *         name="token",
     *         in="header",
     *         required=true,
     *         description="Authentication token.",
     *         @OA\Schema(type="string", example="your_auth_token_here")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Scanned user details retrieved successfully.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Scanned User Details retrieved successfully."),
     *             @OA\Property(property="data", type="object", 
     *                 @OA\Property(property="scanned_id", type="integer", example=16),
     *                 @OA\Property(property="openemis_no", type="string", example="1022290909"),
     *                 @OA\Property(property="datetime", type="string", format="date-time", example="2024-12-24T12:34:56"),
     *                 @OA\Property(property="latitude", type="string", example="87.7675"),
     *                 @OA\Property(property="longitude", type="string", example="34.9878"),
     *                 @OA\Property(property="location", type="string", example="Sample Location"),
     *                 @OA\Property(property="access", type="string", example="Granted")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid scanned ID provided.",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Invalid scanned ID provided.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No scanned user details found.",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="No scanned user details found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to retrieve scanned user details.",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Failed to retrieve Scanned User Details.")
     *         )
     *     )
     * )
     */
    public function scannedUserDetails($scannedId)
    {
        try {
            if (!is_numeric($scannedId)) {
                return $this->sendErrorResponse('Invalid scanned ID provided');
            }

            $details = $this->scannedService->scannedUserDetails($scannedId);
            
            if (empty($details)) {
                return $this->sendErrorResponse('No scanned user details found');
            }

            return $this->sendSuccessResponse('Scanned User Details retrieved successfully', $details);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to retrieve Scanned User Details',
                [
                    'scanned_id' => $scannedId,
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]
            );
            return $this->sendErrorResponse('Failed to retrieve Scanned User Details');
        }
    }
   
}