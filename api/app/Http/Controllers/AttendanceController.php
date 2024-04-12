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


    /**
     * @OA\Get(
     *     path="/api/v4/academic-periods",
     *     summary="Get all academic periods list",
     *     description="Returns all academic periods list",
     *     tags={"Attendance"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=33),
     *                 @OA\Property(property="code", type="string", example="YR2024"),
     *                 @OA\Property(property="name", type="string", example="2024"),
     *                 @OA\Property(property="start_date", type="string", format="date", example="2024-01-01"),
     *                 @OA\Property(property="start_year", type="integer", example=2024),
     *                 @OA\Property(property="end_date", type="string", format="date", example="2024-12-31"),
     *                 @OA\Property(property="end_year", type="integer", example=2024),
     *                 @OA\Property(property="school_days", type="integer", example=0),
     *                 @OA\Property(property="current", type="integer", example=1),
     *                 @OA\Property(property="editable", type="integer", example=1),
     *                 @OA\Property(property="parent_id", type="integer", example=9),
     *                 @OA\Property(property="lft", type="integer", example=34),
     *                 @OA\Property(property="rght", type="integer", example=35),
     *                 @OA\Property(property="academic_period_level_id", type="integer", example=1),
     *                 @OA\Property(property="order", type="integer", example=2),
     *                 @OA\Property(property="visible", type="integer", example=1),
     *                 @OA\Property(property="modified_user_id", type="integer", example=2),
     *                 @OA\Property(property="modified", type="string", format="date-time", example="2024-01-03 14:50:48"),
     *                 @OA\Property(property="created_user_id", type="integer", example=2),
     *                 @OA\Property(property="created", type="string", format="date-time", example="2023-12-05 11:02:59")
     *             )
     *         )
     *     )
     * )
     */
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


    /**
     * @OA\Get(
     *     path="/api/v4/academic-periods/{academicPeriodId}/weeks",
     *     summary="Get weeks for a specific academic period",
     *     description="Returns weeks belonging to the specified academic period",
     *     tags={"Attendance"},
     *     @OA\Parameter(
     *         name="academicPeriodId",
     *         in="path",
     *         required=true,
     *         description="ID of the academic period",
     *         example="33",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=33),
     *                 @OA\Property(property="code", type="string", example="YR2024"),
     *                 @OA\Property(property="name", type="string", example="2024"),
     *                 @OA\Property(property="start_date", type="string", format="date", example="2024-01-01"),
     *                 @OA\Property(property="start_year", type="integer", example=2024),
     *                 @OA\Property(property="end_date", type="string", format="date", example="2024-12-31"),
     *                 @OA\Property(property="end_year", type="integer", example=2024),
     *                 @OA\Property(property="school_days", type="integer", example=0),
     *                 @OA\Property(property="current", type="integer", example=1),
     *                 @OA\Property(property="editable", type="integer", example=1),
     *                 @OA\Property(property="parent_id", type="integer", example=9),
     *                 @OA\Property(property="lft", type="integer", example=34),
     *                 @OA\Property(property="rght", type="integer", example=35),
     *                 @OA\Property(property="academic_period_level_id", type="integer", example=1),
     *                 @OA\Property(property="order", type="integer", example=2),
     *                 @OA\Property(property="visible", type="integer", example=1),
     *                 @OA\Property(property="modified_user_id", type="integer", example=2),
     *                 @OA\Property(property="modified", type="string", format="date-time", example="2024-01-03 14:50:48"),
     *                 @OA\Property(property="created_user_id", type="integer", example=2),
     *                 @OA\Property(property="created", type="string", format="date-time", example="2023-12-05 11:02:59"),
     *                 @OA\Property(property="weeks", type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="name", type="string", example="Week 1 (Jan 1, 2024 - Jan 7, 2024)"),
     *                             @OA\Property(property="start_day", type="string", example="2024-01-01"),
     *                             @OA\Property(property="end_day", type="string", example="2024-01-07"),
     *                             @OA\Property(property="id", type="integer",  example="1"),
     *                         )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Academic period not found"
     *     )
     * )
     */
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


    /**
     * @OA\Get(
     *     path="/api/v4/academic-periods/{academicPeriodId}",
     *     summary="Get academic period by ID",
     *     description="Returns details of an academic period by its ID",
     *     tags={"Attendance"},
     *     @OA\Parameter(
     *         name="academicPeriodId",
     *         in="path",
     *         required=true,
     *         description="ID of the academic period",
     *         example="33",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=33),
     *                 @OA\Property(property="code", type="string", example="YR2024"),
     *                 @OA\Property(property="name", type="string", example="2024"),
     *                 @OA\Property(property="start_date", type="string", format="date", example="2024-01-01"),
     *                 @OA\Property(property="start_year", type="integer", example=2024),
     *                 @OA\Property(property="end_date", type="string", format="date", example="2024-12-31"),
     *                 @OA\Property(property="end_year", type="integer", example=2024),
     *                 @OA\Property(property="school_days", type="integer", example=0),
     *                 @OA\Property(property="current", type="integer", example=1),
     *                 @OA\Property(property="editable", type="integer", example=1),
     *                 @OA\Property(property="parent_id", type="integer", example=9),
     *                 @OA\Property(property="lft", type="integer", example=34),
     *                 @OA\Property(property="rght", type="integer", example=35),
     *                 @OA\Property(property="academic_period_level_id", type="integer", example=1),
     *                 @OA\Property(property="order", type="integer", example=2),
     *                 @OA\Property(property="visible", type="integer", example=1),
     *                 @OA\Property(property="modified_user_id", type="integer", example=2),
     *                 @OA\Property(property="modified", type="string", format="date-time", example="2024-01-03 14:50:48"),
     *                 @OA\Property(property="created_user_id", type="integer", example=2),
     *                 @OA\Property(property="created", type="string", format="date-time", example="2023-12-05 11:02:59")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Academic period not found"
     *     )
     * )
     */
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
