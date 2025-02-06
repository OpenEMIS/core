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
    /*public function saveScannedUserData(ScannedAttendanceRequest $request)
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
    }*/

    public function saveScannedUserData(ScannedAttendanceRequest $request)
    {

        DB::beginTransaction();
        try {
            $params = $request->all();
            $bulkInsertData = [];
            $notFoundUsers = []; 
            $currentTimestamp = Carbon::now()->toDateTimeString();
            $userId = JWTAuth::user()->id;
            if (!empty($params) && is_array($params)) {
                foreach ($params as $param) {
                    if (!is_array($param) || !isset($param['openemis_no'])) {
                        Log::error('Invalid parameter structure', ['param' => $param]);
                        return 2;
                    }
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
            $query = ScannedAttendance::with(['createdUser', 'modifiedUser']);
            if (!empty($params['openemis_no'])) {
                $query->where('openemis_no', $params['openemis_no']);
            }
            
            $scanUser = $query->get();
            
            if ($scanUser->isEmpty()) {
                return $this->sendErrorResponse('No scanned data found.');
            }
            
            return $scanUser;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to export Scanned User Data',
                [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'parameters' => $params
                ]
            );
            return $this->sendErrorResponse('Failed to export Scanned User Data.');
        }
    }

    public function scannedListing(Request $request)
    {
        try {
            $params = $request->all();
            $dateTo = !empty($params['date_to']) ? Carbon::parse($params['date_to'])->endOfDay() : null;
            $dateFrom = !empty($params['date_from']) ? Carbon::parse($params['date_from'])->startOfDay() : null;

            if ($dateFrom && $dateTo && $dateFrom->gt($dateTo)) {
                [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
            }
            $userListingRecord = ScannedAttendance::with('securityUser');
            if ($dateFrom && $dateTo) {
                $userListingRecord = $userListingRecord->whereBetween('datetime', [
                    $dateFrom->format('Y-m-d H:i:s'),
                    $dateTo->format('Y-m-d H:i:s')
                ]);
            } elseif ($dateFrom) {
                $userListingRecord = $userListingRecord->whereDate('datetime', '>=', $dateFrom->format('Y-m-d'));
            } elseif ($dateTo) {
                $userListingRecord = $userListingRecord->whereDate('datetime', '<=', $dateTo->format('Y-m-d'));
            }

            if (isset($params['order'])) {
                $orderBy = $params['order_by'] ?? 'ASC';
                $orderBy = strtoupper($orderBy) === 'DESC' ? 'DESC' : 'ASC';
                $col = $params['order'];
                $userListingRecord = $userListingRecord->orderBy($col, $orderBy);
            }

            if (isset($params['limit'])) {
                $limit = max(1, intval($params['limit']));
                $resp = $userListingRecord->paginate($limit)->toArray();
            } else {
                $resp = [
                    'data' => $userListingRecord->get()->toArray()
                ];
            }

            return $resp;

        } catch (\Exception $e) {
            Log::error('Failed to fetch list from DB', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'parameters' => $params ?? [] // Log the input parameters for debugging
            ]);
            return $this->sendErrorResponse('Failed to retrieve scanned list');
        }
    }

    public function scanUserDetails($scannedId)
    {
        try {
            $userDetails = ScannedAttendance::with('securityUser')->where('id', $scannedId)->first();
            return $userDetails;
        } catch (\Exception $e) {
            Log::error('Failed to fetch Scanned User Data from db', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'parameters' => $params ?? [] 
            ]);
            return $this->sendErrorResponse('Failed to fetch Scanned User Data from db');
        }
    }
   
   
}