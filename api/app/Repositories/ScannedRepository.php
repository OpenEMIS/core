<?php

namespace App\Repositories;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use JWTAuth;
use Tymon\JWTAuth\Facades\JWTFactory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\SecurityUsers;
use App\Models\ScannedAttendance;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use App\Http\Requests\ScannedAttendanceRequest;

/**
 * POCOR-8666
 * ScannedRepository is responsible for interacting with the data storage (e.g., database)
 * to perform CRUD operations related to scanned user data.
 * It contains the logic to save and retrieve scanned attendance records.
 */
class ScannedRepository extends Controller
{
     /**
     * Save scanned user data to the database.
     * POCOR-8666
     * This method takes the scanned data from the request, processes it, and stores it
     * @param ScannedAttendanceRequest $request The request containing the scanned data to be saved.
     * @return mixed The result of the save operation, typically the saved record or a success message.
     */
    public function saveScannedUserData(ScannedAttendanceRequest $request)
    {
        DB::beginTransaction();
        try {
            $params = $request->all();
            $currentTimestamp = Carbon::now()->toDateTimeString();
            $userId = JWTAuth::user()->id;

            // Validate required fields
            if (empty($params['openemis_no']) || empty($params['datetime'])) {
                return response()->json(['error' => 'Missing required fields'], 400);
            }
            $openemisNo = SecurityUsers::where('openemis_no', $params['openemis_no'])->first();
            if ($openemisNo) {
                $data = [
                    'openemis_no' => $params['openemis_no'],
                    'datetime' => Carbon::parse($params['datetime'])->toDateTimeString(),
                    'latitude' => $params['latitude'] ?? NULL,
                    'longitude' => $params['longitude'] ?? NULL,
                    'location' => $params['location'] ?? NULL,
                    'access' => $params['access'] ?? NULL,
                    'created_user_id' => $userId,
                    'created' => $currentTimestamp,
                ];

                // Insert the data
                ScannedAttendance::create($data);

                DB::commit();
                return 1;
            } else {
                // Log not found user
                Log::channel('scan')->error('User not found in db', [
                    'openemis_no' => $params['openemis_no'],
                    'timestamp' => $currentTimestamp,
                    'details' => $params
                ]);

                return 2;
            }
        } catch (\Exception $e) {
            DB::rollback();

            // Log the exception
            Log::channel('scan')->error('Failed to store Scanned User data', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json(['error' => 'An error occurred while saving data'], 500);
        }
    }

    /**
     * POCOR-8666
     * Fetch scanned attendance records based on OpenEMIS number and optional date range.
     *
     * @param mixed $params The OpenEMIS number used to filter attendance records.
     * @param \Illuminate\Http\Request $request The incoming HTTP request containing additional parameters.
     * @return array An array containing the attendance records or an error response.
     */
    public function scannedOpenemisNo($params, Request $request)
    {
        try {
            $paramRequest = $request->all();
            $openemisNo = $params;
            $dateFrom = $paramRequest['datetime_start'] ?? null;
            $dateTo = $paramRequest['datetime_end'] ?? null;
            if ($dateFrom && $dateTo && $dateFrom > $dateTo) {
                [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
            }
            $query = ScannedAttendance::where('openemis_no', $openemisNo);
            if (!empty($dateFrom) && !empty($dateTo)) {
                $query = $query->whereBetween('datetime', [$dateFrom, $dateTo]);
            }
            $userListingRecord = $query->get()->toArray();

            $resp['data'] = $userListingRecord;
            return $resp;

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch user from DB',
                ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Scanned user Not Found');
        }
    }

    /**
     * POCOR-8793
     * export institution scan data xlsx
     * * This method fetches the scanned attendance data for a specific OpenEMIS number, including related user information (created by and modified by users).
     * The data is retrieved from the `ScannedAttendance` model with relationships to the 'createdUser' and 'modifiedUser'.
     * 
     * @param array $params An associative array containing the `openemis_no` (OpenEMIS number) to filter the scanned data.
     * 
     */
    public function institutionScannedDataExport($params)
    {
        try {
            $openemisNo = $params['openemis_no'];
            $scanUser =  ScannedAttendance::with(['createdUser', 'modifiedUser'])->where('openemis_no', $openemisNo)->get();
            return $scanUser; 
        } catch (\Exception $e) {
            Log::error(
                'Failed to export Scanned User Data',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to export Scanned User Data.');
        }
    }
   
}