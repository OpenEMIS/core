<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AttendanceService;
use App\Http\Requests\AcademicPeriodListRequest;
use App\Http\Requests\AttendanceShiftsRequest;
use App\Http\Requests\StaffAttendanceRequest;
use Illuminate\Support\Facades\Log;

class AttendanceController extends Controller
{
    protected $attendanceService;

    public function __construct(AttendanceService $attendanceService) 
    {
        $this->attendanceService = $attendanceService;
    }


    public function getAcademicPeriods(Request $request)
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


    public function getStaffAttendances(StaffAttendanceRequest $request, $institutionId)
    {
        try {
            $data = $this->attendanceService->getStaffAttendances($request, $institutionId);
            return $this->sendSuccessResponse("Staff Attendances List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Staff Attendances List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Staff Attendances List Not Found');
        }
    }

    public function getInstitutionShiftOption(AttendanceShiftsRequest $request, $institutionId)
    {
        try {
            $data = $this->attendanceService->getInstitutionShiftOption($request, $institutionId);

            if(!empty($data)){
                return $this->sendSuccessResponse("Institution Shift Options Found", $data);
            } else {
                return $this->sendErrorResponse("Institution Shift Option Not Found");
            }
            
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Institution Shift Options from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Institution Shift Options Not Found.');
        }
    }


    public function getAcademicPeriodsWeeks(Request $request, $academicPeriodId=0)
    {
        try {
            $data = $this->attendanceService->getAcademicPeriodsWeeks($request, $academicPeriodId);

            if(!empty($data)){
                return $this->sendSuccessResponse("Academic Periods List Found", $data);
            } else {
                return $this->sendErrorResponse("Academic Periods List Not Found");
            }
            
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Academic Periods List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Academic Periods List Not Found');
        }
    }


    public function getAcademicPeriodsWeekDays(AcademicPeriodListRequest $request, $academicPeriodId=0, $weekId=0)
    {
        try {
            $data = $this->attendanceService->getAcademicPeriodsWeekDays($request, $academicPeriodId, $weekId);
            if(!empty($data)){
                return $this->sendSuccessResponse("Academic Periods List Found", $data);
            } else {
                return $this->sendErrorResponse("Academic Periods List Not Found");
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Academic Periods List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Academic Periods List Not Found');
        }
    }


    public function getAcademicPeriodData($academicPeriodId)
    {
        try {
            $data = $this->attendanceService->getAcademicPeriodData($academicPeriodId);
            if(!empty($data)){
                return $this->sendSuccessResponse("Academic Periods Data Found", $data);
            } else {
                return $this->sendErrorResponse("Academic Periods Data Not Found");
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Academic Periods Data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Academic Periods Data Not Found');
        }
    }
    
}
