<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WorkbenchService;
use Illuminate\Support\Facades\Log;

class WorkbenchController extends Controller
{
    protected $workbenchService;

    public function __construct(
        WorkbenchService $workbenchService
    ) {
        $this->workbenchService = $workbenchService;
    }


    public function getNoticesList(Request $request)
    {
        try {
            $data = $this->workbenchService->getNoticesList($request);
            
            return $this->sendSuccessResponse("Notice List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }

    public function getInstitutionStaffLeave(Request $request)
    {
        try {
            $data = $this->workbenchService->getInstitutionStaffLeave($request);
            
            return $this->sendSuccessResponse("Staff Leave List Found", $data);
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
            $data = $this->workbenchService->getInstitutionStaffSurveys($request);
            
            return $this->sendSuccessResponse("Staff Survey List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }
}
