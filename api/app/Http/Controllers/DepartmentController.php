<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DepartmentService;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\InstitutionDepartmentRequest;

/**
 * POCOR-8030
 * DepartmentController handles the data operations.
 * It interacts with the DepartmentService to manage records.
 */
class DepartmentController extends Controller
{
    protected $departmentService;

    public function __construct(DepartmentService $departmentService) 
    {
        $this->departmentService = $departmentService;
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
    *             required={"openemis_no", "datetime", "scanner_code"},
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

    public function getDepartmentList(Request $request, $institutionId,)
    {
        try {
            $params = $request->all();
            $data = $this->departmentService->getDepartmentListing($params, $institutionId);
            if(!empty($data)){
                return $this->sendSuccessResponse("Institutions Department List Found", $data);
            } else {
                return $this->sendErrorResponse("Institutions Department List not Found" , $data);
            }
        } catch (\Exception $e) {
            Log::error(
                'Failed to save Scanned User Data in DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
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
    public function institutionDepartmentDetails($institutionId,$departmentId, Request $request)
    {
        try {
            $institutionDetails = $this->departmentService->institutionDepartmentDetails($institutionId,$departmentId,$request);
            if(!empty($institutionDetails)){
                return $this->sendSuccessResponse("Institutions Department details", $institutionDetails);
            } else {
                return $this->sendErrorResponse("Institutions Department view data not Found" , $data);
            }
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Scanned User Data',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch Scanned User Data');
        }
    }

    public function institutionDepartmentSave(InstitutionDepartmentRequest $request)
    {
        try {
            if(empty($request->all())){
                return $this->sendErrorResponse("Institutions Department not added. Data is empty");
            }
            $institutionDetails = $this->departmentService->addInstitutionDepartment($request);
            if($institutionDetails == 1){
                return $this->sendSuccessResponse("Institutions Department save Successfully");
            } else {
                return $this->sendErrorResponse("Institutions Department not added");
            }
        } catch (\Exception $e) {
            Log::error(
                'Failed to save Department  Data',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to save Department  Data');
        }
    }

    public function updateInstitutionDepartment($departmentId, InstitutionDepartmentRequest $request)
    {
        try {
            if(empty($request->all())){
                return $this->sendErrorResponse("Institutions Department not added. Data is empty");
            }
            $institutionDetails = $this->departmentService->updateInstitutionDepartment($departmentId,$request);
            if($institutionDetails == 1){
                return $this->sendSuccessResponse("Institutions Department save Successfully");
            } else {
                return $this->sendErrorResponse("Institutions Department not added");
            }
        } catch (\Exception $e) {
            Log::error(
                'Failed to save Department  Data',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to save Department  Data');
        }
    }

    public function institutionDepartmentEditMessage()
    {
        try {
            $data = $this->departmentService->departmentEditMessage();
            if(!empty($data)){
                return $this->sendSuccessResponse("Institutions Department configuration", $data);
            }else {
                return $this->sendErrorResponse("Institutions Department configuration not Available");
            }
        } catch (\Exception $e) {
            Log::error(
                'Failed to save Department  Data',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to save Department  Data');
        }
    }

   
}