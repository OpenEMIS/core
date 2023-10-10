<?php

namespace App\Services;

use App\Http\Controllers\Controller;
use App\Repositories\WorkbenchRepository;
use JWTAuth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class WorkbenchService extends Controller
{

    protected $workbenchRepository;

    public function __construct(
    WorkbenchRepository $workbenchRepository) {
        $this->workbenchRepository = $workbenchRepository;
    }


    public function getNoticesList($request)
    {
        try {
            $data = $this->workbenchRepository->getNoticesList($request);
            
            return $data;
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
            $data = $this->workbenchRepository->getInstitutionStaffLeave($request);
            $resp = [];

            foreach($data['data'] as $k=> $d){
                $resp[$k]['id'] = $d['id'];
                $resp[$k]['institution_id'] = $d['institution_id'];
                $resp[$k]['institution'] = $d['institution']['name'];
                $resp[$k]['request_title'] = $d['staff_leave_type']['name']. ' of ' .$d['staff']['name_with_id'];
                $resp[$k]['received_date'] = Carbon::create($d['created'])->toFormattedDateString();
                $resp[$k]['requester'] = $d['security_user']['name_with_id'];
                $resp[$k]['staff_id'] = $d['staff_id'];
                $resp[$k]['status_id'] = $d['status_id'];
                $resp[$k]['status'] = $d['status']['name'];
                $resp[$k]['staff_leave_type'] = $d['staff_leave_type'];
                $resp[$k]['user'] = $d['staff'];
                $resp[$k]['created_user'] = $d['security_user'];
            }

            $data['data'] = $resp; 
            
            return $data;
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
