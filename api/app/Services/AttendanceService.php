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
            if($data['action_type'] == 'SchoolAcademicPeriod'){
                $resp = $data['list'];
            } elseif($data['action_type'] == 'WeeksForPeriod') {
                $resp = $data['list'];
            } elseif($data['action_type'] == 'DaysForPeriodWeek') {
                $resp = $data['list'];
            }
            return $resp;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Academic Periods List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Academic Periods List Not Found');
        }
    }



    public function getStaffAttendances($request)
    {
        try {
            $data = $this->attendanceRepository->getStaffAttendances($request);
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Staff Attendances List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Staff Attendances List Not Found');
        }
    }

}