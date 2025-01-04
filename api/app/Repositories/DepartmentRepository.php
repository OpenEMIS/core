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
use App\Http\Requests\InstitutionDepartmentRequest;

class DepartmentRepository extends Controller
{
    
    public function getDepartmentList($params, $institutionId)
    {
        try {
            $permissions = checkAccess();
            if(isset($permissions)){
                if($permissions['super_admin'] != 1){
                    if($permissions['allowAllInstitutions'] != 1){
                        $institution_Ids = $permissions['institutionIds'];
                    } 
                }
            }
            $list = InstitutionDepartments::with(['securityUser','departmentManager','institution'])->where('institution_id', $institutionId);
            if(isset($institution_Ids)){
                $list = $list->whereIn('institution_id', $institution_Ids);
            }
           
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
            return $this->sendErrorResponse('User Not Found');
        }
    }

    public function institutionDepartmentViewDetails($institutionId,$departmentId,$request)
    {
        try {
            $data = InstitutionDepartments::with(['securityUser','departmentManager','institution'])->where('institution_id', $institutionId)->where('id', $departmentId)->get();
            return $data;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Department Not Found');
        }
    }

    public function saveInstitutionDepartment($request)
    {
        DB::beginTransaction();
        try {
            $params = $request->all();
            $bulkInsertData = [];
            $currentTimestamp = Carbon::now()->toDateTimeString();
            $userId = JWTAuth::user()->id;

            if(!empty($params)){
                $data = [
                    'name' => $params['name'],
                    'code' => $params['code'],
                    'manager_id' => $params['manager_id'],
                    'staff_id' => $params['staff_id'],
                    'institution_id' => $params['institution_id'],
                    'created_user_id' => $userId,
                    'created' => $currentTimestamp,
                ];
                InstitutionDepartments::create($data);
            }
            DB::commit();
            return 1;

        } catch (\Exception $e) {
            DB::rollback();
            Log::channel('scan')->error('Failed to store department data', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return 2;
            
        }
    }

    public function institutionDepartmentUpdate($departmentId, $request)
    {
        DB::beginTransaction();
        try {
            $params = $request->all();
            $currentTimestamp = Carbon::now()->toDateTimeString();
            $userId = JWTAuth::user()->id;
            $department = InstitutionDepartments::find($departmentId);

            if (!$department) {
                return $this->sendErrorResponse("Department not found.");
            }
            $department->update([
                'name' => $params['name'],
                'code' => $params['code'],
                'manager_id' => $params['manager_id'],
                'staff_id' => $params['staff_id'],
                'institution_id' => $params['institution_id'],
                'modified_user_id' => $userId, 
                'modified' => $currentTimestamp, 
            ]);
            DB::commit();
            return 1;

        } catch (\Exception $e) {
            DB::rollback();
            Log::channel('scan')->error('Failed to update department data', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);
            return $this->sendErrorResponse("Failed to update Institution Department.");
        }
    }
    
}