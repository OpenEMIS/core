<?php

namespace App\Services;

use App\Http\Controllers\Controller;
use App\Repositories\AttendanceRepository;
use JWTAuth;
use Illuminate\Support\Facades\Log;

class AttendanceService extends Controller
{

    protected $attendanceRepository;

    public function __construct(AttendanceRepository $attendanceRepository) 
    {
        $this->attendanceRepository = $attendanceRepository;
    }

    
    public function getAcademicPeriods($request)
    {
        try {
            $data = $this->attendanceRepository->getAcademicPeriods($request);
            $resp = [];
            $resp = $data['list'];
            
            return $resp;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Academic Periods List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Academic Periods List Not Found');
        }
    }



    public function getStaffAttendances($request, $institutionId)
    {
        try {
            $data = $this->attendanceRepository->getStaffAttendances($request, $institutionId);
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Staff Attendances List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Staff Attendances List Not Found');
        }
    }


    public function getInstitutionShiftOption($request, $institutionId)
    {
        try {
            $data = $this->attendanceRepository->getInstitutionShiftOption($request, $institutionId);
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Institution Shift Options from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Institution Shift Options Not Found.');
        }
    }


    public function getAcademicPeriodsWeeks($request, $academicPeriodId)
    {
        try {
            $data = $this->attendanceRepository->getAcademicPeriodsWeeks($request, $academicPeriodId);
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Academic Periods List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Academic Periods List Not Found');
        }
    }



    public function getAcademicPeriodsWeekDays($request, $academicPeriodId, $weekId)
    {
        try {
            $data = $this->attendanceRepository->getAcademicPeriodsWeekDays($request, $academicPeriodId, $weekId);
            return $data;
            
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
            $data = $this->attendanceRepository->getAcademicPeriodData($academicPeriodId);
            
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Academic Periods Data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Academic Periods Data Not Found');
        }
    }

}