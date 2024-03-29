<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AttendanceService;
use App\Http\Requests\AcademicPeriodListRequest;
use App\Http\Requests\AttendanceShiftsRequest;
use App\Http\Requests\StaffAttendanceRequest;
use App\Http\Requests\StudentAttendanceListRequest;
use App\Http\Requests\StudentAttendanceMarkedRecordListRequest;
use App\Http\Requests\StudentAttendanceTypeListRequest;
use App\Http\Requests\SubjectsByClassPerAcademicPeriodRequest;
use App\Http\Requests\StudentAttendanceMarkTypeListRequest;
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
    
    //For POCOR-7854 Starts...
    public function getAttendanceTypes(StudentAttendanceTypeListRequest $request, $gradeId)
    {
        try {
            $params = $request->all();
            $data = $this->attendanceService->getAttendanceTypes($params, $gradeId);
            

            return $this->sendSuccessResponse("Attendance Types Found.", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Attendance Types from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Attendance Types Not Found');
        }
    }


    public function allSubjectsByClassPerAcademicPeriod(SubjectsByClassPerAcademicPeriodRequest $request, $institutionId, $gradeId, $classId)
    {
        try {
            $params = $request->all();
            $data = $this->attendanceService->allSubjectsByClassPerAcademicPeriod($params, $institutionId, $gradeId, $classId);
            

            return $this->sendSuccessResponse("Subjects List Found.", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Subjects List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Subjects List Not Found');
        }
    }


    public function getStudentAttendanceMarkType(StudentAttendanceMarkTypeListRequest $request, $institutionId, $gradeId, $classId)
    {
        try {
            $params = $request->all();
            $data = $this->attendanceService->getStudentAttendanceMarkType($params, $institutionId, $gradeId, $classId);
            
            return $this->sendSuccessResponse("Student Attendance Mark Type Found.", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Student Attendance Mark Type from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Student Attendance Mark Type Not Found');
        }
    }



    public function getStudentAttendanceList(StudentAttendanceListRequest $request, $institutionId, $gradeId, $classId)
    {
        try {
            $params = $request->all();
            $data = $this->attendanceService->getStudentAttendanceList($params, $institutionId, $gradeId, $classId);
            
            return $this->sendSuccessResponse("Student Attendance List Found.", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Student Attendance List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Student Attendance List Not Found');
        }
    }


    public function getStudentAttendanceMarkedRecordList(StudentAttendanceMarkedRecordListRequest $request, $institutionId, $gradeId, $classId)
    {
        try {
            $params = $request->all();
            $data = $this->attendanceService->getStudentAttendanceMarkedRecordList($params, $institutionId, $gradeId, $classId);
            
            return $this->sendSuccessResponse("Student Attendance Marked List Found.", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Student Attendance Marked List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Student Attendance Marked List Not Found');
        }
    }
    //For POCOR-7854 Ends...
}
