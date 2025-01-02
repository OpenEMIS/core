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
use App\Models\InstitutionDepartments;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

class DepartmentRepository extends Controller
{
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
    
    public function getDepartmentList($params, $institutionId)
    {
        try {
            $list = InstitutionDepartments::with(['securityUser','departmentManager','institution'])->where('institution_id', $institutionId);
           
            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $list = $list->orderBy($col, $orderBy);
            }

            $resp = [];
            if(isset($params['limit'])){
                $limit = $params['limit'];
                $resp = $list->paginate($limit)->toArray();
            } else{
                $list = $list->get()->toArray();
                $resp['data'] = $list;
            }
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