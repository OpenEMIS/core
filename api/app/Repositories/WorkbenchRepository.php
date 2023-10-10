<?php

namespace App\Repositories;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use JWTAuth;
use App\Models\AcademicPeriod;
use App\Models\Notice;
use App\Models\Workflows;
use App\Models\InstitutionStaffLeave;
use Illuminate\Support\Facades\DB;
use Mail;
use Illuminate\Support\Str;

class WorkbenchRepository extends Controller
{
    

    public function getNoticesList($request)
    {
        try {
            $params = $request->all();

            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $list = Notice::paginate($limit)->toArray();
            return $list;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }

    public function getInstitutionStaffLeave($request)
    {
        try {
            $param = $request->all();
            $assigneeId = JWTAuth::user()->id;

            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $list = InstitutionStaffLeave::with(
                        'institution:id,name', 
                        'staff',
                        'assignee',
                        'securityUser',
                        'status:id,name',
                        'staffLeaveType:id,name'
                    )
                    ->whereHas(
                        'status', function ($q) {
                            $q->where('workflow_id', 20)
                                ->whereHas(
                                    'workflows', function($query){
                                        $query->where('category', '!=', 3);
                                    }
                                );
                        }        
                    )
                    ->where('assignee_id', $assigneeId)
                    ->paginate($limit)
                    ->toArray();

            return $list;

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }


    public function getInstitutionStaffSurveys(Request $request)
    {
        try {
            $data = $this->workbenchRepository->getInstitutionStaffSurveys($request);
            
            return $data;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }    

}

