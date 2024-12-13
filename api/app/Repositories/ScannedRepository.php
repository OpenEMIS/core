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

class ScannedRepository extends Controller
{

    public function saveScannedUserData(ScannedAttendanceRequest $request)
    {
        DB::beginTransaction();
        try {
            $param = $request->all();
            $storeArr = [
                'openemis_no' => $param['openemis_no'],
                'datetime' => Carbon::parse($param['datetime'])->toDateTimeString(),
                'latitude' => $param['latitude'],
                'longitude' => $param['longitude'],
                'scanner_code' => $param['scanner_code'],
                'location' => $param['location'],
                'access' => $param['access'],
                'created_user_id' => JWTAuth::user()->id,
                'created' => Carbon::now()->toDateTimeString()
            ];
            $scannedAttendance = new ScannedAttendance();
            $scannedAttendance->fill($storeArr);
            $scannedAttendance->save(); // Call save() on the instance
            DB::commit();
            return 1;
        } catch (\Exception $e) {
            DB::rollback();
            Log::error(
                'Failed to store Scanned User data.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to store Scanned User data.');
        }
    }

    public function updateScannedUserData($openemisNo, Request $request)
    {
        DB::beginTransaction();
        try {
            $param = $request->all();
            $storeArr = [
                'openemis_no' => $openemisNo,
                'datetime' => Carbon::parse($param['datetime']) ,
                'latitude' => $param['latitude'] ?? null,
                'longitude' => $param['longitude'] ?? null,
                'location' => $param['location'] ?? null,
                'access' => $param['access'] ?? null,
                'modified_user_id' => JWTAuth::user()->id,
                'modified' => Carbon::now()->toDateTimeString()
            ];
            $scannedAttendance = ScannedAttendance::where('openemis_no', $openemisNo)->first();
            if (!$scannedAttendance) {
                DB::rollback();
                return $this->sendErrorResponse('Scanned attendance record not found.');
            }
            $scannedAttendance->fill($storeArr);
            $scannedAttendance->save();
            DB::commit();
            return 1;

        } catch (\Exception $e) {
            DB::rollback();
            Log::error(
                'Failed to Update Scanned User data.',
                ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to Update Scanned User data.');
        }
    }


    public function scannedListing(Request $request)
    {
        try {
            $params = $request->all();
            $dateTo = $params['date_to'] ?? null;
            $dateFrom = $params['date_from'] ?? null;
            if ($dateFrom && $dateTo && $dateFrom > $dateTo) {
                [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
            }

            // Initialize the query
            $userListingRecord = ScannedAttendance::with('securityUser');

            // Date filtering
            if (!empty($dateFrom) && !empty($dateTo)) {
                $userListingRecord = $userListingRecord->whereBetween('datetime', [$dateFrom, $dateTo]);
            }

            // Ordering
            if (isset($params['order'])) {
                $orderBy = $params['order_by'] ?? "ASC";
                $col = $params['order'];
                $userListingRecord = $userListingRecord->orderBy($col, $orderBy);
            }

            // Pagination or get all records
            $resp = [];
            if (isset($params['limit'])) {
                $limit = $params['limit'];
                $resp = $userListingRecord->paginate($limit)->toArray();
            } else {
                $userListingRecord = $userListingRecord->get()->toArray();
                $resp['data'] = $userListingRecord;
            }

            return $resp;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Scanned List Not Found');
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