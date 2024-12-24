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
            $bulkInsertData = [];
            $notFoundUsers = []; 
            $currentTimestamp = Carbon::now()->toDateTimeString();
            $userId = JWTAuth::user()->id;
            if(!empty($params)){
                foreach ($params as $param) {
                    $openemisNo = SecurityUsers::where('openemis_no', $param['openemis_no'])->first();
                    
                    if (!empty($openemisNo)) {
                        $bulkInsertData[] = [
                            'openemis_no' => $param['openemis_no'],
                            'datetime' => Carbon::parse($param['datetime'])->toDateTimeString(),
                            'latitude' => $param['latitude'],
                            'longitude' => $param['longitude'],
                            'location' => $param['location'],
                            'access' => $param['access'],
                            'created_user_id' => $userId,
                            'created' => $currentTimestamp,
                        ];
                    } else {
                        // Log users not found to scan.log
                        Log::channel('scan')->error('User not found in db', [
                            'openemis_no' => $param['openemis_no'],
                            'timestamp' => $currentTimestamp,
                            'details' => $param
                        ]);
                        $notFoundUsers[] = $param['openemis_no'];
                        return 2;
                    }
                }
                if (!empty($bulkInsertData)) {
                    ScannedAttendance::insert($bulkInsertData);
                }

                DB::commit();
                return 1;
            }

        } catch (\Exception $e) {
            DB::rollback();
            // Log to scan.log channel with detailed error information
            Log::channel('scan')->error('Failed to store Scanned User data', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return 2;
            
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

   
}