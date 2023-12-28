<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AttendanceService;
use App\Http\Requests\AcademicPeriodListRequest;
use Illuminate\Support\Facades\Log;

class AttendanceController extends Controller
{
    protected $attendanceService;

    public function __construct(AttendanceService $attendanceService) 
    {
        $this->attendanceService = $attendanceService;
    }


    public function getAcademicPeriods(AcademicPeriodListRequest $request)
    {
        try {
            $data = $this->attendanceService->getAcademicPeriods($request);
            return $this->sendSuccessResponse("Academic Periods List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Academic Periods List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Academic Periods List Not Found');
        }
    }


    public function getStaffAttendances(Request $request)
    {
        try {
            $data = $this->attendanceService->getStaffAttendances($request);
            return $this->sendSuccessResponse("Staff Attendances List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Staff Attendances List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Staff Attendances List Not Found');
        }
    }


    
}
