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
use App\Http\Requests\StudentAttendancesExportRequest;
use App\Http\Requests\StudentAttendancesImportTemplateRequest;
use App\Http\Requests\StudentAttendanceImportRequest;
use App\Http\Requests\StudentAttendancesNoScheduledClassRequest;
use Illuminate\Support\Facades\Log;
use App\Exports\StudentAttendancesExport;
use Maatwebsite\Excel\Facades\Excel;

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
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Limit",
     *         @OA\Schema(type="integer", example="10")
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="object",
     *                  @OA\Property(property="data", type="array",
     *                  @OA\Items(type="object",
     *                      @OA\Property(property="id", type="integer", example=33),
     *                      @OA\Property(property="code", type="string", example="YR2024"),
     *                      @OA\Property(property="name", type="string", example="2024"),
     *                      @OA\Property(property="start_date", type="string", format="date", example="2024-01-01"),
     *                      @OA\Property(property="start_year", type="integer", example=2024),
     *                      @OA\Property(property="end_date", type="string", format="date", example="2024-12-31"),
     *                      @OA\Property(property="end_year", type="integer", example=2024),
     *                      @OA\Property(property="school_days", type="integer", example=0),
     *                      @OA\Property(property="current", type="integer", example=1),
     *                      @OA\Property(property="editable", type="integer", example=1),
     *                      @OA\Property(property="parent_id", type="integer", example=9),
     *                      @OA\Property(property="lft", type="integer", example=34),
     *                      @OA\Property(property="rght", type="integer", example=35),
     *                      @OA\Property(property="academic_period_level_id", type="integer", example=1),
     *                      @OA\Property(property="order", type="integer", example=2),
     *                      @OA\Property(property="visible", type="integer", example=1),
     *                      @OA\Property(property="modified_user_id", type="integer", example=2),
     *                      @OA\Property(property="modified", type="string", format="date-time", example="2024-01-03 14:50:48"),
     *                      @OA\Property(property="created_user_id", type="integer", example=2),
     *                      @OA\Property(property="created", type="string", format="date-time", example="2023-12-05 11:02:59")
     *                  )
     *                  )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
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



    /**
     * @OA\Get(
     *     path="/api/v4/institutions/{institution_id}/staff/attendances",
     *     summary="Get staff attendances for a specific institution",
     *     description="Returns staff attendances for the specified institution and parameters",
     *     tags={"Attendance"},
     *     @OA\Parameter(
     *         name="institution_id",
     *         in="path",
     *         required=true,
     *         description="ID of the institution",
     *         @OA\Schema(type="integer", example="6")
     *     ),
     *     @OA\Parameter(
     *         name="academic_period_id",
     *         in="query",
     *         required=true,
     *         description="ID of the academic period",
     *         @OA\Schema(type="integer", example="33")
     *     ),
     *     @OA\Parameter(
     *         name="week_id",
     *         in="query",
     *         required=true,
     *         description="ID of the week",
     *         @OA\Schema(type="integer", example="2")
     *     ),
     *     @OA\Parameter(
     *         name="week_start_day",
     *         in="query",
     *         required=true,
     *         description="Start day of the week (YYYY-MM-DD)",
     *         @OA\Schema(type="string", format="date", example="2024-041-08")
     *     ),
     *     @OA\Parameter(
     *         name="week_end_day",
     *         in="query",
     *         required=true,
     *         description="End day of the week (YYYY-MM-DD)",
     *         @OA\Schema(type="string", format="date", example="2024-01-14")
     *     ),
     *     @OA\Parameter(
     *         name="day_id",
     *         in="query",
     *         required=true,
     *         description="ID of the day",
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="shift_id",
     *         in="query",
     *         required=true,
     *         description="ID of the shift. shif_id = -1 (For all shifts.)",
     *         @OA\Schema(type="integer", example="-1")
     *     ),
     *     @OA\Parameter(
     *         name="day_date",
     *         in="query",
     *         required=true,
     *         description="Date of the day (YYYY-MM-DD)",
     *         @OA\Schema(type="string", format="date", example="2024-01-08")
     *     ),
     *     @OA\Parameter(
     *         name="own_attendance_view",
     *         in="query",
     *         required=false,
     *         description="Own attendance view",
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="own_attendance_edit",
     *         in="query",
     *         required=false,
     *         description="Own attendance edit",
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="other_attendance_view",
     *         in="query",
     *         required=false,
     *         description="Other attendance view",
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="other_attendance_edit",
     *         in="query",
     *         required=false,
     *         description="Other attendance edit",
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Limit",
     *         @OA\Schema(type="integer", example="10")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example="395"),
     *                         @OA\Property(property="FTE", type="string", example="1.00"),
     *                         @OA\Property(property="start_date", type="string", format="date", example="2017-02-02"),
     *                         @OA\Property(property="start_year", type="integer", example="2017"),
     *                         @OA\Property(property="end_date", type="string", format="date", example=Null),
     *                         @OA\Property(property="end_year", type="integer", example=Null),
     *                         @OA\Property(property="staff_id", type="integer", example="8815"),
     *                         @OA\Property(property="staff_type_id", type="integer", example="3"),
     *                         @OA\Property(property="staff_status_id", type="integer", example="1"),
     *                         @OA\Property(property="institution_id", type="integer", example="6"),
     *                         @OA\Property(property="is_homeroom", type="integer", example="1"),
     *                         @OA\Property(property="institution_position_id", type="integer", example="220"),
     *                         @OA\Property(property="security_group_user_id", type="string", example="1ec69383-7703-43fb-a568-d87bb1da2949"),
     *                         @OA\Property(property="staff_position_grade_id", type="integer", example="1"),
     *                         @OA\Property(property="modified_user_id", type="integer", example=Null),
     *                         @OA\Property(property="modified", type="string", format="date-time", example=Null),
     *                         @OA\Property(property="created_user_id", type="integer", example="2"),
     *                         @OA\Property(property="created", type="string", format="date-time", example="2018-04-05 18:57:20"),
     *                         @OA\Property(property="date", type="string", format="date", example="2024-01-08"),
     *                         @OA\Property(property="historyUrl", type="string", example="/pocor-openemis-core/Staff/InstitutionStaffAttendanceActivities/index?user_id=8815"),
     *                         @OA\Property(property="_matchingData", type="object",
     *                              @OA\Property(property="User", type="object",
     *                                  @OA\Property(property="id", type="string", example=8815),
     *                                  @OA\Property(property="username", type="string", example="teacher"),
     *                                  @OA\Property(property="openemis_no", type="string", example="1522952436"),
     *                                  @OA\Property(property="first_name", type="string", example="Amanda"),
     *                                  @OA\Property(property="middle_name", type="string", example=Null),
     *                                  @OA\Property(property="third_name", type="string", example=Null),
     *                                  @OA\Property(property="last_name", type="string", example="Wells"),
     *                                  @OA\Property(property="preferred_name", type="string", example=Null),
     *                                  @OA\Property(property="email", type="string", example=Null),
     *                                  @OA\Property(property="address", type="string", example=Null),
     *                                  @OA\Property(property="postal_code", type="string", example=Null),
     *                                  @OA\Property(property="address_area_id", type="string", example=Null),
     *                                  @OA\Property(property="birthplace_area_id", type="string", example=Null),
     *                                  @OA\Property(property="gender_id", type="string", example=2),
     *                                  @OA\Property(property="date_of_birth", type="string", example="1981-01-01"),
     *                                  @OA\Property(property="nationality_id", type="string", example=1),
     *                                  @OA\Property(property="identity_type_id", type="string", example=161),
     *                                  @OA\Property(property="identity_number", type="string", example=1302042293),
     *                                  @OA\Property(property="is_student", type="string", example=0),
     *                                  @OA\Property(property="is_staff", type="string", example=1),
     *                                  @OA\Property(property="is_guardian", type="string", example=0),
     *                                  @OA\Property(property="modified_user_id", type="string", example=2),
     *                                  @OA\Property(property="modified", type="string", example="2018-04-05 18:20:27"),
     *                                  @OA\Property(property="created_user_id", type="string", example=2),
     *                                  @OA\Property(property="created", type="string", example="2018-04-05 18:20:27"),
     *                              )
     *                         ),
     *                         @OA\Property(property="attendance", type="object",
     *                             @OA\Property(property="2024-01-08", type="object",
     *                                 @OA\Property(property="dateStr", type="string", example="2024-01-08"),
     *                                 @OA\Property(property="date", type="string", example="January 08, 2024"),
     *                                 @OA\Property(property="time_in", type="string", example=Null),
     *                                 @OA\Property(property="time_out", type="string", example=Null),
     *                                 @OA\Property(property="comment", type="string", example=Null),
     *                                 @OA\Property(property="absence_type_id", type="integer", example=Null),
     *                                 @OA\Property(property="isNew", type="boolean", example=True),
     *                                 @OA\Property(property="leave", type="object"),
     *                                 @OA\Property(property="url", type="string", example="/pocor-openemis-core/Institution/Institutions/StaffLeave/index?user_id=8815")
     *                             )
     *                         )
     *                     )
     *                 ),
     *                 @OA\Property(property="total", type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
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

    
    /**
     * @OA\Get(
     *     path="/api/v4/institutions/{institution_id}/shift-options",
     *     summary="Get shift options for a specific institution",
     *     description="Returns shift options available for the specified institution",
     *     tags={"Attendance"},
     *     @OA\Parameter(
     *         name="institution_id",
     *         in="path",
     *         required=true,
     *         description="ID of the institution",
     *         @OA\Schema(type="integer", example=6)
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Limit",
     *         @OA\Schema(type="integer", example="10")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string|integer", example="1"),
     *                     @OA\Property(property="name", type="string", example="First Shift: 07:00:00 - 11:00:00"),
     *                     @OA\Property(property="selected", type="boolean", example=true),
     *                     @OA\Property(property="start_time", type="string", format="time", nullable=true, example="07:00:00"),
     *                     @OA\Property(property="end_time", type="string", format="time", nullable=true, example="11:00:00")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
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
     *         @OA\Schema(type="integer", example="33")
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="list", type="object",
     *                      @OA\Property(property="id", type="integer", example=33),
     *                      @OA\Property(property="code", type="string", example="YR2024"),
     *                      @OA\Property(property="name", type="string", example="2024"),
     *                      @OA\Property(property="start_date", type="string", format="date", example="2024-01-01"),
     *                      @OA\Property(property="start_year", type="integer", example=2024),
     *                      @OA\Property(property="end_date", type="string", format="date", example="2024-12-31"),
     *                      @OA\Property(property="end_year", type="integer", example=2024),
     *                      @OA\Property(property="school_days", type="integer", example=0),
     *                      @OA\Property(property="current", type="integer", example=1),
     *                      @OA\Property(property="editable", type="integer", example=1),
     *                      @OA\Property(property="parent_id", type="integer", example=9),
     *                      @OA\Property(property="lft", type="integer", example=34),
     *                      @OA\Property(property="rght", type="integer", example=35),
     *                      @OA\Property(property="academic_period_level_id", type="integer", example=1),
     *                      @OA\Property(property="order", type="integer", example=2),
     *                      @OA\Property(property="visible", type="integer", example=1),
     *                      @OA\Property(property="modified_user_id", type="integer", example=2),
     *                      @OA\Property(property="modified", type="string", format="date-time", example="2024-01-03 14:50:48"),
     *                      @OA\Property(property="created_user_id", type="integer", example=2),
     *                      @OA\Property(property="created", type="string", format="date-time", example="2023-12-05 11:02:59"),
     *                      @OA\Property(property="weeks", type="array",
     *                              @OA\Items(
     *                             type="object",
     *                                  @OA\Property(property="name", type="string", example="Week 1 (Jan 1, 2024 - Jan 7, 2024)"),
     *                                  @OA\Property(property="start_day", type="string", example="2024-01-01"),
     *                                  @OA\Property(property="end_day", type="string", example="2024-01-07"),
     *                                  @OA\Property(property="id", type="integer",  example="1"),
     *                              )
     *                      )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
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


    /**
     * @OA\Get(
     *     path="/api/v4/academic-periods/{academicPeriodId}/weeks/{weekId}/days",
     *     summary="Get days for a specific week of an academic period",
     *     tags={"Attendance"},
     *     description="Returns days belonging to the specified week of the academic period",
     *     @OA\Parameter(
     *         name="academicPeriodId",
     *         in="path",
     *         required=true,
     *         description="ID of the academic period",
     *         @OA\Schema(type="integer", example=33)
     *     ),
     *     @OA\Parameter(
     *         name="weekId",
     *         in="path",
     *         required=true,
     *         description="ID of the week",
     *         @OA\Schema(type="integer", example=2)
     *     ),
     *     @OA\Parameter(
     *         name="institution_id",
     *         in="query",
     *         required=true,
     *         description="ID of the institution",
     *         example="6",
     *         @OA\Schema(type="integer", example=6)
     *     ),
     *     @OA\Parameter(
     *         name="school_closed_required",
     *         in="query",
     *         required=true,
     *         description="Indicates if school closed days are required",
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="list", type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example="1"),
     *                         @OA\Property(property="day", type="string", example="Monday"),
     *                         @OA\Property(property="name", type="string", example="Monday (Jan 8, 2024) "),
     *                         @OA\Property(property="date", type="string", example="2024-01-08"),
     *                         @OA\Property(property="current_week_number_selected", type="integer", example=Null),
     *                         @OA\Property(property="day_number", type="boolean", example=Null),
     *                     )
     *                 ),
     *                 @OA\Property(property="total", type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
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
     *         @OA\Schema(type="integer", example="33")
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
     *         description="Unsuccessful."
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


    /**
     * @OA\Get(
     *     path="/api/v4/grades/{gradeId}/attendance-types",
     *     summary="Get attendance types for a specific grade",
     *     description="Returns attendance types for the specified grade based on academic period, institution class, and day",
     *     tags={"Attendance"},
     *     @OA\Parameter(
     *         name="gradeId",
     *         in="path",
     *         required=true,
     *         description="ID of the grade",
     *         @OA\Schema(type="integer", example=206)
     *     ),
     *     @OA\Parameter(
     *         name="academic_period_id",
     *         in="query",
     *         required=true,
     *         description="ID of the academic period",
     *         @OA\Schema(type="integer", example=33)
     *     ),
     *     @OA\Parameter(
     *         name="institution_class_id",
     *         in="query",
     *         required=true,
     *         description="ID of the institution class",
     *         @OA\Schema(type="integer", example=591)
     *     ),
     *     @OA\Parameter(
     *         name="day_id",
     *         in="query",
     *         required=true,
     *         description="Date for which attendance types are requested (format: yyyy-mm-dd)",
     *         @OA\Schema(type="string", format="date", example="2024-02-08")
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Limit",
     *         @OA\Schema(type="integer", example="10")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data", type="object",
     *                      @OA\Property(property="id", type="integer", example=1),
     *                      @OA\Property(property="code", type="string", example="DAY")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
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



    /**
     * @OA\Get(
     *     path="/api/v4/institutions/{institutionId}/grades/{gradeId}/classes/{classId}/subjects",
     *     summary="Get subjects for a specific class",
     *     description="Returns subjects belonging to the specified class in the given institution and grade",
     *     tags={"Attendance"},
     *     @OA\Parameter(
     *         name="institutionId",
     *         in="path",
     *         required=true,
     *         description="ID of the institution",
     *         @OA\Schema(type="integer", example=6)
     *     ),
     *     @OA\Parameter(
     *         name="gradeId",
     *         in="path",
     *         required=true,
     *         description="ID of the grade",
     *         @OA\Schema(type="integer", example=206)
     *     ),
     *     @OA\Parameter(
     *         name="classId",
     *         in="path",
     *         required=true,
     *         description="ID of the class",
     *         @OA\Schema(type="integer", example=591)
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="name")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Limit",
     *         @OA\Schema(type="integer", example="10")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=4270),
     *                         @OA\Property(property="name", type="string", example="Spanish")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
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


    
    /**
     * @OA\Get(
     *     path="/api/v4/institutions/{institutionId}/grades/{gradeId}/classes/{classId}/student-attendance-types",
     *     summary="Get student attendance types for a specific class",
     *     description="Returns student attendance types belonging to the specified class.",
     *     tags={"Attendance"},
     *     @OA\Parameter(
     *         name="institutionId",
     *         in="path",
     *         required=true,
     *         description="ID of the institution",
     *         @OA\Schema(type="integer", example=6)
     *     ),
     *     @OA\Parameter(
     *         name="gradeId",
     *         in="path",
     *         required=true,
     *         description="ID of the grade",
     *         @OA\Schema(type="integer", example=206)
     *     ),
     *     @OA\Parameter(
     *         name="classId",
     *         in="path",
     *         required=true,
     *         description="ID of the class",
     *         @OA\Schema(type="integer", example=591)
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Limit",
     *         @OA\Schema(type="integer", example="10")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Period")
     *                     )
     *                 ),
     *                 @OA\Property(property="total", type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful"
     *     )
     * )
     */
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


    /**
 * @OA\Get(
 *     path="/api/v4/institutions/{institutionId}/grades/{gradeId}/classes/{classId}/student-attendances",
 *     summary="Get student attendances for a specific class",
 *     description="Returns student attendances for the specified class",
 *     tags={"Attendance"},
 *     @OA\Parameter(
 *         name="institutionId",
 *         in="path",
 *         required=true,
 *         description="ID of the institution",
 *         @OA\Schema(type="integer", example=6)
 *     ),
 *     @OA\Parameter(
 *         name="gradeId",
 *         in="path",
 *         required=true,
 *         description="ID of the grade",
 *         @OA\Schema(type="integer", example=206)
 *     ),
 *     @OA\Parameter(
 *         name="classId",
 *         in="path",
 *         required=true,
 *         description="ID of the class",
 *         @OA\Schema(type="integer", example=591)
 *     ),
 *     @OA\Parameter(
 *         name="order",
 *         in="query",
 *         required=false,
 *         description="Order",
 *         @OA\Schema(type="integer", example="student_id")
 *     ),
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         required=false,
 *         description="Page number",
 *         @OA\Schema(type="integer", example="1")
 *     ),
 *     @OA\Parameter(
 *         name="limit",
 *         in="query",
 *         required=false,
 *         description="Limit",
 *         @OA\Schema(type="integer", example="10")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful.",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Successful."),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="data", type="array",
 *                     @OA\Items(
 *                         type="object",
 *                         @OA\Property(property="academic_period_id", type="integer", example="33"),
 *                         @OA\Property(property="institution_class_id", type="integer", example="591"),
 *                         @OA\Property(property="institution_id", type="integer", example="6"),
 *                         @OA\Property(property="student_id", type="integer", example="13685"),
 *                         @OA\Property(property="user", type="object",
 *                                  @OA\Property(property="id", type="string", example=8815),
 *                                  @OA\Property(property="username", type="string", example="teacher"),
 *                                  @OA\Property(property="openemis_no", type="string", example="1522952436"),
 *                                  @OA\Property(property="first_name", type="string", example="Amanda"),
 *                                  @OA\Property(property="middle_name", type="string", example=Null),
 *                                  @OA\Property(property="third_name", type="string", example=Null),
 *                                  @OA\Property(property="last_name", type="string", example="Wells"),
 *                                  @OA\Property(property="preferred_name", type="string", example=Null),
 *                                  @OA\Property(property="email", type="string", example=Null),
 *                                  @OA\Property(property="address", type="string", example=Null),
 *                                  @OA\Property(property="postal_code", type="string", example=Null),
 *                                  @OA\Property(property="address_area_id", type="string", example=Null),
 *                                  @OA\Property(property="birthplace_area_id", type="string", example=Null),
 *                                  @OA\Property(property="gender_id", type="string", example=2),
 *                                  @OA\Property(property="date_of_birth", type="string", example="1981-01-01"),
 *                                  @OA\Property(property="nationality_id", type="string", example=1),
 *                                  @OA\Property(property="identity_type_id", type="string", example=161),
 *                                  @OA\Property(property="identity_number", type="string", example=1302042293),
 *                                  @OA\Property(property="is_student", type="string", example=0),
 *                                  @OA\Property(property="is_staff", type="string", example=1),
 *                                  @OA\Property(property="is_guardian", type="string", example=0),
 *                                  @OA\Property(property="modified_user_id", type="string", example=2),
 *                                  @OA\Property(property="modified", type="string", example="2018-04-05 18:20:27"),
 *                                  @OA\Property(property="created_user_id", type="string", example=2),
 *                                  @OA\Property(property="created", type="string", example="2018-04-05 18:20:27"),
 *                         ),
 *                         @OA\Property(property="institution_student_absences", type="object",
 *                             @OA\Property(property="date", type="string", format="date", example="2024-02-07"),
 *                             @OA\Property(property="period", type="string", example="1"),
 *                             @OA\Property(property="comment", type="string", example=null),
 *                             @OA\Property(property="absence_type_id", type="integer", example=null),
 *                             @OA\Property(property="student_absence_reason_id", type="integer", example=null),
 *                             @OA\Property(property="absence_type_code", type="string", example=null)
 *                         ),
 *                         @OA\Property(property="is_NoClassScheduled", type="integer", example=0)
 *                     )
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Unsuccessful."
 *     )
 * )
 */
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


    /**
     * @OA\Get(
     *     path="/api/v4/institutions/{institutionId}/grades/{gradeId}/classes/{classId}/student-attendance-marked",
     *     summary="Get student attendance marked for a specific class",
     *     description="Returns student attendance marked for the specified class",
     *     tags={"Attendance"},
     *     @OA\Parameter(
     *         name="institutionId",
     *         in="path",
     *         required=true,
     *         description="ID of the institution",
     *         @OA\Schema(type="integer", example="6")
     *     ),
     *     @OA\Parameter(
     *         name="gradeId",
     *         in="path",
     *         required=true,
     *         description="ID of the education grade",
     *         @OA\Schema(type="integer", example="206")
     *     ),
     *     @OA\Parameter(
     *         name="classId",
     *         in="path",
     *         required=true,
     *         description="ID of the class",
     *         @OA\Schema(type="integer", example="591")
     *     ),
     *     @OA\Parameter(
     *         name="academic_period_id",
     *         in="query",
     *         required=true,
     *         description="ID of the academic period",
     *         @OA\Schema(type="integer", example="33")
     *     ),
     *     @OA\Parameter(
     *         name="attendance_period_id",
     *         in="query",
     *         required=true,
     *         description="ID of the attendance period",
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="day_id",
     *         in="query",
     *         required=true,
     *         description="Day Id",
     *         @OA\Schema(type="integer", example="2024-04-16")
     *     ),
     *     @OA\Parameter(
     *         name="subject_id",
     *         in="query",
     *         required=true,
     *         description="Subject Id",
     *         @OA\Schema(type="integer", example="0")
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Limit",
     *         @OA\Schema(type="integer", example="10")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="institution_id", type="integer", example="6"),
     *                         @OA\Property(property="academic_period_id", type="integer", example="33"),
     *                         @OA\Property(property="institution_class_id", type="integer", example="591"),
     *                         @OA\Property(property="education_grade_id", type="integer", example="206"),
     *                         @OA\Property(property="date", type="string", format="date", example="2024-04-16"),
     *                         @OA\Property(property="period", type="integer", example="1"),
     *                         @OA\Property(property="subject_id", type="integer", example="0"),
     *                         @OA\Property(property="no_scheduled_class", type="integer", example="0"),
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
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


    //For POCOR-8363 Starts...
    public function getStudentAttendancesExport(StudentAttendancesExportRequest $request)
    {
        try {
            $params = $request->all();

            $data = $this->attendanceService->getStudentAttendancesExport($params);
            
            $str = time();
            $fileName = 'StudentAttendances_'.$str.'.xlsx';
            return Excel::download(new StudentAttendancesExport($data), $fileName);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to export students attendances from DB.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Failed to export students attendances from DB.');
        }
    }


    public function getStudentAttendancesImportTemplate(StudentAttendancesImportTemplateRequest $request)
    {
        try {
            $params = $request->all();

            $data = $this->attendanceService->getStudentAttendancesImportTemplate($params);
            
            if(!empty($data)){
                return $this->sendSuccessResponse("Student attendance import template data found.", $data);
            } else {
                return $this->sendErrorResponse("Student attendance import not template data found.", $data);
            }

            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch students attendances import template data from DB.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Failed to fetch students attendances import template data from DB.');
        }
    }



    public function studentAttendancesImport(StudentAttendanceImportRequest $request)
    {
        try {
            $params = $request->all();
            //dd($params);
            $data = $this->attendanceService->studentAttendancesImport($params);
            
            if(!is_array($data)){
                if(isset($data) && $data == 1){
                    return $this->sendErrorResponse('Invalid file extension.');
                } elseif(isset($data) && $data == 2){
                    return $this->sendErrorResponse('Header is not present.');
                } elseif(isset($data) && $data == 3){
                    return $this->sendErrorResponse('Imported file is empty.');
                } elseif(isset($data) && $data == 4){
                    return $this->sendErrorResponse('Not a valid heading.');
                } elseif(isset($data) && $data == 5){
                    return $this->sendErrorResponse('Institution is not linked with Institution Class.');
                } elseif(isset($data) && $data == 6){
                    return $this->sendErrorResponse('No current Academic Period is set in DB.');
                } elseif(isset($data) && $data == 7){
                    return $this->sendErrorResponse('Uploaded file exceeds maximum no of records limit ('.config("constantvalues.importExcelRules.maxRows").').');
                } else {
                    return $this->sendErrorResponse('Student attendance not imported.');
                }
            } else {
                return $this->sendSuccessResponse("Student attendance imported.", $data);
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to import students attendance in DB.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to import students attendance in DB.');
        }
    }


    public function studentAttendancesNoScheduledClass(StudentAttendancesNoScheduledClassRequest $request)
    {
        try {
            $params = $request->all();

            $data = $this->attendanceService->studentAttendancesNoScheduledClass($params);
            
            if(!empty($data)){
                return $this->sendSuccessResponse("Student attendance set for no-schedules class.", $data);
            } else {
                return $this->sendErrorResponse("Failed to set Student attendance for no-schedules class.");
            }

            
        } catch (\Exception $e) {
            Log::error(
                'Failed to set Student attendance for no-schedules class.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Failed to set Student attendance for no-schedules class.');
        }
    }
    //For POCOR-8363 Ends...



    //For POCOR-8397 Starts...
    public function getArchiveAcademicPeriods(Request $request)
    {
        try {
            $params = $request->all();

            $data = $this->attendanceService->getArchiveAcademicPeriods($params);
            
            if(!empty($data)){
                return $this->sendSuccessResponse("Archive academic periods found.", $data);
            } else {
                return $this->sendErrorResponse("Archive academic periods not found.");
            }
        } catch (\Exception $e) {
            Log::error(
                'Failed to get archive academic periods.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Failed to get archive academic periods.');
        }
    }
    //For POCOR-8397 Ends...
}
