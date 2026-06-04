<?php

namespace App\Repositories;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\ConfigItem;
use App\Models\CalendarEventDate;
use App\Models\InstitutionStaffAttendances;
use App\Models\InstitutionStaffAttendancesArchived;//POCOR-8630
use App\Models\InstitutionStaffLeave;
use App\Models\InstitutionStaffLeaveArchive;
use App\Models\InstitutionPositions;
use App\Models\InstitutionStaff;
use App\Models\InstitutionShifts;
use App\Models\StudentAttendanceMarkType;
use App\Models\StudentAttendanceType;
use App\Models\InstitutionScheduleTimetables;
use App\Models\InstitutionClassSubjects;
use App\Models\SecurityRoleFunctions;
use App\Models\InstitutionClassGrades;
use App\Models\StudentAttendancePerDayPeriod;
use App\Models\InstitutionClassStudents;
use App\Models\StudentStatuses;
use App\Models\StudentAttendanceMarkedRecords;
use App\Models\InstitutionStudentAbsenceDetails;
use App\Models\StudentAbsenceReason;
use App\Models\AbsenceTypes;
use App\Models\InstitutionClassAttendanceRecord;
use App\Models\Institutions;
use App\Models\InstitutionClasses;
use App\Models\InstitutionClassStudent;
use App\Models\SecurityUsers;
use App\Models\InstitutionSubjects;
use App\Models\InstitutionClassAttendanceRecordsArchive;
use App\Models\InstitutionStudentAbsencesArchived;
use App\Models\InstitutionStudentAbsenceDetailsArchived;
use App\Models\StudentAttendanceMarkedRecordsArchived;
use App\Models\InstitutionStudentWithdraw;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use JWTAuth;
use Illuminate\Support\Facades\DB;
use DateTime;
use DateInterval;
use DatePeriod;
use App\Imports\StudentAttendanceImport;
use App\Imports\StaffAttendanceImport;//POCOR-8630
use File;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Illuminate\Support\Str;//POCOR-8630

class AttendanceRepository extends Controller
{

    const NOT_VALID = -1;
    const NOT_MARKED = 0;
    const MARKED = 1;
    const PARTIAL_MARKED = 2;
    const DAY_COLUMN_PREFIX = 'day_';


    public function getAcademicPeriods($request)
    {
        try {
            $params = $request->all();

            //For POCOR-8216/8215 start...
            //$limit = config('constantvalues.defaultPaginateLimit');   
            if(isset($params['limit'])){
                $limit = $params['limit'];
                $list = $this->findSchoolAcademicPeriod($params, $limit);
            } else {
                $list['data'] = $this->findSchoolAcademicPeriod($params);
            }
            //For POCOR-8216/8215 end...
            return $list;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Academic Periods List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Academic Periods List Not Found');
        }
    }


    public function getAttendanceWeeks($academic_period_id)
    {
        try {
            $period = AcademicPeriod::where('id', $academic_period_id)->first();
            $configItems1 = ConfigItem::where('code', 'first_day_of_week')->first();

            
            // If First of week is sunday change the value to 7, because sunday with the '0' value unable to be displayed
            $firstDayOfWeek = $configItems1->value??"";

            if ($firstDayOfWeek == 0) {
                $firstDayOfWeek = 7;
            }

            $configItems2 = ConfigItem::where('code', 'days_per_week')->first();
            $daysPerWeek = $configItems2->value??"";


            $lastDayIndex = ($firstDayOfWeek - 1);// last day index always 1 day before the starting date.
            if ($lastDayIndex == 0) {
                $lastDayIndex = 7;
            }

            $startDate = $period->start_date;
            $endDate = $period->end_date;
            $nextDate = date('Y-m-d', strtotime("+1 day", strtotime($startDate)));
            
            $weekIndex = 1;
            $weeks = [];
            
            $daysInWeek = $lastDayIndex;
            $startDate = Carbon::parse($startDate);
            

            do {

                $endDate = $startDate->copy()->next('Sunday');
                if ($endDate->gt($period->end_date)) {
                    $endDate = Carbon::parse($period->end_date);

                }

                $startDateNew = $startDate->format('Y-m-d');
                $endDateNew = $endDate->format('Y-m-d');
                
                $weeks[$weekIndex++] = [$startDateNew, $endDateNew];

                $startDate = $endDate->copy();
                $startDate->addDay();
                
            } while ($endDate->lt($period->end_date));
            
            return $weeks;

        } catch (\Exception $e){
            return false;
        }
    }


    public function findSchoolAcademicPeriod($params, $limit=0)
    {
        try {
            $list = AcademicPeriod::where('editable', 1)
                        ->where('parent_id', '!=', 0)
                        ->where('visible', '=', 1)
                        ->orderBy('order', 'ASC');

            //For POCOR-8216/8215 start...
            if($limit > 0){
                $list = $list->paginate($limit);
            } else {
                $list = $list->get();
            }
            //For POCOR-8216/8215 end...

            return $list;
        } catch (\Exception $e) {
            return [];
        }
    }


    public function findWeeksForPeriod($params, $limit)
    {
        try {
            $academic_period_id = $params['academic_period_id'];
            $list = AcademicPeriod::where('id', $academic_period_id)->first();

            
            if($list){
                $todayDate = date("Y-m-d");
                $weekOptions = [];
                $selectedIndex = 0;

                $weeks = $this->getAttendanceWeeks($academic_period_id);
                
                $weekStr = __('Week') . ' %d (%s - %s)';
                $currentWeek = null;

                foreach ($weeks as $index => $dates) {
                    
                    $startDay = $dates[0];
                    $endDay = $dates[1];
                    $weekAttr = [];
                    if ($todayDate >= $startDay && $todayDate <= $endDay) {
                        $weekStr = __('Current Week') . ' %d (%s - %s)';
                        // $weekAttr['selected'] = true;
                        $currentWeek = $index;
                    } else {
                        $weekStr = __('Week') . ' %d (%s - %s)';
                    }

                    $startDayNew = Carbon::create($startDay)->toFormattedDateString();
                    $endDayNew = Carbon::create($endDay)->toFormattedDateString();

                    $weekAttr['name'] = sprintf($weekStr, $index, $startDayNew, $endDayNew);
                    $weekAttr['start_day'] = $startDay;
                    $weekAttr['end_day'] = $endDay;
                    $weekAttr['id'] = $index;
                    $weekOptions[] = $weekAttr;

                    if ($todayDate >= $startDay && $todayDate <= $endDay) {
                        end($weekOptions);

                        $selectedIndex = key($weekOptions);
                    }
                }

                $weekOptions[$selectedIndex]['selected'] = true;
                
                $list->weeks = $weekOptions;

                return $list;
            } else {
                return [];
            }

            
        } catch (\Exception $e){
            return [];
        }
    }


    public function findDaysForPeriodWeek($params)
    {
        try {
            $academicPeriodId = $params['academic_period_id'];
            $current_week_number_selected = $params['current_week_number_selected']??Null;
            $weekId = $params['week_id'];
            $institutionId = $params['institution_id'];

            // pass true if you need school closed data
            if (array_key_exists('school_closed_required', $params)) {
                $schoolClosedRequired = $params['school_closed_required'];
            } else {
                $schoolClosedRequired = false;
            }

            $configItems = new ConfigItem();

            $configItems1 = $configItems->where('code', 'first_day_of_week')->first();
            $firstDayOfWeek = 0;
            if($configItems1){
                if($configItems1->value){
                    $firstDayOfWeek = $configItems1->value;
                } elseif($configItems1->default_value){
                    $firstDayOfWeek = $configItems1->default_value;
                } else {
                    $firstDayOfWeek = 0;
                }
            }
            
            
            $configItems2 = $configItems->where('code', 'days_per_week')->first();
            $daysPerWeek = 0;
            if($configItems2){
                if($configItems2->value){
                    $daysPerWeek = $configItems2->value;
                } elseif($configItems2->default_value){
                    $daysPerWeek = $configItems2->default_value;
                } else {
                    $daysPerWeek = 0;
                }
            }

            $weeks = $this->getAttendanceWeeks($academicPeriodId);
            
            $week = $weeks[$weekId];

            if (isset($params['exclude_all']) && $params['exclude_all']) {
                $dayOptions = [];
            } else {
                $dayOptions[] = [
                    'id' => -1,
                    'name' => __('All Days'),
                    'date' => -1
                ];
            }

            $schooldays = [];

            for ($i = 0; $i < $daysPerWeek; ++$i) {
                // sunday should be '7' in order to be displayed
                $schooldays[] = 1 + ($firstDayOfWeek + 6 + $i) % 7;
            }

            
            $firstDayOfWeek = $week[0];
            $today = null;


            $startDate = Carbon::createFromFormat('Y-m-d', $week[0]);
            $endDate = Carbon::createFromFormat('Y-m-d', $week[1]);

      
            $dateRange = CarbonPeriod::create($startDate, $endDate);

            $dateRange = $dateRange->toArray();


       
            foreach($dateRange as $key => $startdate){
                $startdateformat = Carbon::create($startdate)->toFormattedDateString();
                
                $date = $startdate->format('Y-m-d');
                
                $dayOfWeek = $key + 1;
                
                if(in_array($dayOfWeek, $schooldays)){
                    if ($schoolClosedRequired == false) {
                        $schoolClosed = false;
                    } else {
                        $schoolClosed = $this->isSchoolClosed($date, $institutionId);

                        if ($schoolClosed) {
                            $sql = "SELECT institution_shift_periods.period_id  FROM calendar_event_dates
                                INNER JOIN calendar_events ON calendar_events.id = calendar_event_dates.calendar_event_id 
                                INNER JOIN institution_shifts ON calendar_events.academic_period_id = institution_shifts.academic_period_id 
                                        AND calendar_events.institution_id = institution_shifts.institution_id 
                                        AND calendar_events.institution_shift_id = institution_shifts.shift_option_id 
                                INNER JOIN calendar_types ON calendar_types.id = calendar_events.calendar_type_id
                                INNER JOIN institution_shift_periods ON institution_shift_periods.institution_shift_period_id = institution_shifts.id 
                                WHERE calendar_event_dates.date = '" . $date . "' AND calendar_types.is_attendance_required = 0";

                            $result = DB::select($sql);
                            $closedPeriods = [];
                            foreach ($result as $data) {
                                $closedPeriods[] = $data['period_id'];
                            }
                        }
                    }

                    $suffix = $schoolClosed ? __('School Closed') : '';
                    $today = date('Y-m-d');
                    $day_number = false;

                    if($date == $today){
                        $day_number = true;
                    }

                    $data = [
                        'id' => $dayOfWeek,
                        'day' => __($startdate->format('l')),
                        'name' => __($startdate->format('l')) . ' (' . $startdateformat . ') ' . $suffix,
                        'date' => $date,
                        'current_week_number_selected' => $current_week_number_selected, 
                        'day_number' => $day_number
                    ];
                    
                    if ($schoolClosed) {
                        $data['closed'] = true;
                        $data['periods'] = $closedPeriods;
                    }

                    $dayOptions[] = $data;
                }
                
            }
            
            return $dayOptions;

        } catch (\Exception $e) {
            return [];
        }
    }


    public function isSchoolClosed($date, $institutionId)
    {
        try {
            $findInstitutions = [-1];
            if (!is_null($institutionId)) {
                $findInstitutions[] = $institutionId;
            }


            $dateEvents = CalendarEventDate::with(
                        'calendarEvent', 
                        'calendarEvent.calendarType'
                    )
                    ->whereHas('calendarEvent', function ($q) use($institutionId){
                        $q->whereIn('institution_id', $findInstitutions);
                    })
                    ->where('date', $date)
                    ->get()
                    ->toArray();

            if (!empty($dateEvents)) {
                $isAttendanceRequired = [];
                foreach ($dateEvents as $event) {
                    $isAttendanceRequired[] = $event['calendar_event']['calendar_type']['is_attendance_required'];
                }

                // if in $isAttendanceRequired got 1 means school is open
                if (in_array('1', $isAttendanceRequired)) {
                    return false;
                } else {
                    return true;
                }
            }

            // false = school is open, true = school is closed
            return false;
        } catch (\Exception $e){
            return false;
        }
    }

    //POCOR-8630 start
    /**
     * Validate staff attendance view/edit flags against role permissions and institution scope.
     *
     * @return array<string, int>|false Server-resolved flags, or false when access is denied.
     */
    public function validateStaffAttendancePermissions(int $institutionId, array $params): array|false
    {
        $user = JWTAuth::user();
        if (!$user) {
            return false;
        }

        if (($user->super_admin ?? 0) != 1) {
            $hasAccess = checkPermission(
                ['Institutions', 'InstitutionStaffAttendances', 'view'],
                ['institution_id' => $institutionId]
            );
            if (!$hasAccess) {
                $hasAccess = checkPermission(
                    ['Institutions', 'InstitutionStaffAttendances', 'index'],
                    ['institution_id' => $institutionId]
                );
            }
            if (!$hasAccess) {
                return false;
            }
        }

        if (($user->super_admin ?? 0) == 1) {
            $ownView = (int) ($params['own_attendance_view'] ?? 1);
            $otherView = (int) ($params['other_attendance_view'] ?? 1);
            $ownEdit = (int) ($params['own_attendance_edit'] ?? 1);
            $otherEdit = (int) ($params['other_attendance_edit'] ?? 1);
        } else {
            $ownView = checkPermission(
                ['Institutions', 'InstitutionStaffAttendances', 'ownview'],
                ['institution_id' => $institutionId]
            ) ? 1 : 0;
            $otherView = checkPermission(
                ['Institutions', 'InstitutionStaffAttendances', 'otherview'],
                ['institution_id' => $institutionId]
            ) ? 1 : 0;
            $ownEdit = checkPermission(
                ['Institutions', 'InstitutionStaffAttendances', 'ownedit'],
                ['institution_id' => $institutionId]
            ) ? 1 : 0;
            $otherEdit = checkPermission(
                ['Institutions', 'InstitutionStaffAttendances', 'otheredit'],
                ['institution_id' => $institutionId]
            ) ? 1 : 0;
        }

        if ($ownView === 0 && $otherView === 0) {
            return false;
        }

        if ((int) ($params['own_attendance_view'] ?? 0) === 1 && $ownView === 0) {
            return false;
        }
        if ((int) ($params['other_attendance_view'] ?? 0) === 1 && $otherView === 0) {
            return false;
        }
        if ((int) ($params['own_attendance_edit'] ?? 0) === 1 && $ownEdit === 0) {
            return false;
        }
        if ((int) ($params['other_attendance_edit'] ?? 0) === 1 && $otherEdit === 0) {
            return false;
        }

        return [
            'own_attendance_view' => $ownView,
            'other_attendance_view' => $otherView,
            'own_attendance_edit' => $ownEdit,
            'other_attendance_edit' => $otherEdit,
        ];
    }

    public function getStaffAttendancesArchive($request)
    {
        return $this->getStaffAttendances($request, (int) $request->input('institution_id'), true);
    }

    public function getStaffAttendances($request, $institutionId, $archive = false)
    {
        try {
            $params = $request->all();
            $resp = [];
            $data = [];

            $institutionId = $params['institution_id'];
            $academicPeriodId = $params['academic_period_id'];
            $ownAttendanceView = $params['own_attendance_view']??0;
            $otherAttendanceView = $params['other_attendance_view']??0;
            $otherAttendanceEdit = $params['other_attendance_edit']??0;
            $shiftId = $params['shift_id'];
            $weekStartDate = $params['week_start_day'];
            $weekEndDate = $params['week_end_day'];
            $dayId = $params['day_id'];
            //if -1 = that means all days of the week
            $dayDate = $params['day_date'];

            $user = JWTAuth::user();
            $superAdmin = $user->super_admin;
            $user_id = $user->id;

            //Getting the baseUrl
            $baseUrl = url('/');                
            $base_url = preg_replace("(^https?://)", "", $baseUrl );

            $base_url = str_replace("/api", "", $base_url );

            $baseUrlArr = explode("/", $base_url);
            if(count($baseUrlArr)>1){
                $base_url = $baseUrlArr[1];
            } else{
                $base_url = $baseUrlArr[0];
            }


            $conditionQuery[] = "'institution_id', '=', ".  $institutionId;
            
            if ($superAdmin == 0) {
                $conditionQuery = $this->setConditionQueryForUser($ownAttendanceView, $otherAttendanceView, $user_id, $conditionQuery);
                
            }


            //if $dayId != -1 then $weekStartDate = $weekEndDate
            list($weekStartDate, $weekEndDate) =
                $this->resetWeekStartEndForOneDaySearch($dayId, $dayDate, $weekStartDate, $weekEndDate);

            if ($archive && ($dayId === null || $dayId === '')) {
                $weekStartDate = $params['week_start_day'];
                $weekEndDate = $params['week_end_day'];
            }

            $attendanceByStaffIdRecords = $this->getAttendanceByStaffIdRecordsArray(
                $institutionId,
                $academicPeriodId,
                $weekStartDate,
                $weekEndDate,
                $shiftId,
                $archive
            );

            $leaveByStaffIdRecords = $this->getLeaveByStaffIdRecordsArray(
                $institutionId,
                $academicPeriodId,
                $weekStartDate,
                $weekEndDate,
                $archive
            );

            if ($archive) {
                $staffIdsWithArchivedAttendance = array_values(array_unique(array_map(
                    'intval',
                    array_keys($attendanceByStaffIdRecords)
                )));
                if ($staffIdsWithArchivedAttendance === []) {
                    return ['data' => [], 'total' => 0];
                }
            }

            if (!$archive) {
                $conditionQueryArray = $this->setConditionQueryForDates($weekStartDate, $weekEndDate, $conditionQuery);
                $conditionQuery = $conditionQueryArray[0] ?? [];
                $conditionQueryOR = $conditionQueryArray[1] ?? [];
            }

            $absenceTypes = [];
            if ($archive) {
                $absenceTypes = AbsenceTypes::query()->pluck('name', 'id')->toArray();
            }

            //Gets all the days in the selected week based on its start date end date
            $workingDaysArr = $this->getWorkingDays($weekStartDate, $weekEndDate);

            
            $query = InstitutionStaff::select('institution_staff.*');

            if ($shiftId != -1) {
                $query = $query
                    ->leftJoin('institution_positions', 'institution_positions.id', '=', 'institution_staff.institution_position_id')
                    ->where('institution_positions.shift_id', $shiftId);
            }


            $query = $query->with('user')->join('security_users', 'security_users.id', '=', 'institution_staff.staff_id')->where('institution_staff.institution_id', $institutionId);

            if ($superAdmin == 0) {
                if ($ownAttendanceView == 1 && $otherAttendanceView == 0) {
                    $query = $query->where('institution_staff.staff_id', $user_id);
                } elseif ($ownAttendanceView == 0 && $otherAttendanceView == 1) {
                    $query = $query->where('institution_staff.staff_id', '!=', $user_id);
                } elseif ($ownAttendanceView == 0 && $otherAttendanceView == 0) {
                    $query = $query->whereRaw('1 = 0');
                }
            }

            if ($archive) {
                $query = $query->whereIn('institution_staff.staff_id', $staffIdsWithArchivedAttendance);
            } else {
                if ($weekStartDate == $weekEndDate) {
                    $query = $query->where('start_date', '<=', $weekStartDate);
                } else {
                    $query = $query->where('start_date', '<=', $weekStartDate)
                        ->where('start_date', '<=', $weekEndDate);
                }

                $query = $query->where(function ($q) use ($weekStartDate, $weekEndDate) {
                    $q->where('end_date', null)->orWhere('end_date', '>=', $weekEndDate);
                });
            }


            /*$data = $query->orderBy('security_users.first_name')
                        ->groupBy('institution_staff.staff_id')
                        ->get()
                        ->toArray();*/

            $query = $query->orderBy('security_users.first_name')
                        ->groupBy('institution_staff.staff_id');
            

            //For POCOR-8215/8216 start...
            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = 'institution_staff.'.$params['order'];
                $query = $query->orderBy($col, $orderBy);
            }

            if(isset($params['limit'])){
                $limit = $params['limit'];
                $data = $query->paginate($limit)->toArray();
            } else {
                $data['data'] = $query->get()->toArray();

            }
            //For POCOR-8215/8216 end...


            $total = count($data);
            $resp = [];
            
            foreach ($data['data'] as $k => $d) {
                $resp[$k]['id'] = $d['id'];
                $resp[$k]['FTE'] = $d['FTE'];
                $resp[$k]['start_date'] = $d['start_date'];
                $resp[$k]['start_year'] = $d['start_year'];
                $resp[$k]['end_date'] = $d['end_date'];
                $resp[$k]['end_year'] = $d['end_year'];
                $resp[$k]['staff_id'] = $d['staff_id'];
                $resp[$k]['staff_type_id'] = $d['staff_type_id'];
                $resp[$k]['staff_status_id'] = $d['staff_status_id'];
                $resp[$k]['institution_id'] = $d['institution_id'];
                $resp[$k]['is_homeroom'] = $d['is_homeroom'];
                $resp[$k]['institution_position_id'] = $d['institution_position_id'];
                $resp[$k]['security_group_user_id'] = $d['security_group_user_id'];
                $resp[$k]['staff_position_grade_id'] = $d['staff_position_grade_id'];
                $resp[$k]['modified_user_id'] = $d['modified_user_id'];
                $resp[$k]['modified'] = $d['modified'];
                $resp[$k]['created_user_id'] = $d['created_user_id'];
                $resp[$k]['created'] = $d['created'];
                $resp[$k]['_matchingData']['User'] = $d['user'];


                $staffId = $d['staff_id'];
                $staffRecords = [];
                $staffLeaveRecords = [];

                if (array_key_exists($staffId, $attendanceByStaffIdRecords)) {
                    $staffRecords = $attendanceByStaffIdRecords[$staffId];
                }
               
                if (array_key_exists($staffId, $leaveByStaffIdRecords)) {
                    $staffLeaveRecords = $leaveByStaffIdRecords[$staffId];
                    $staffLeaveRecords = array_slice($staffLeaveRecords, 0, 2);
                }

                $staffTimeRecords = [];
                
                foreach ($workingDaysArr as $dateObj) {
                    $dateStr = $dateObj->format('Y-m-d');
                    $formattedDate = $dateObj->format('F d, Y');
                    
                    $found = false;
                    foreach ($staffRecords as $attendanceRecord) {
                        
                        $staffAttendanceDate = date('Y-m-d', strtotime($attendanceRecord['date']));

                        if ($dateStr == $staffAttendanceDate) {
                            $found = true;
                            $absenceTypeId = $attendanceRecord['absence_type_id'] ?? null;
                            //isNew determines if record is existing data
                            $attendanceData = [
                                'dateStr' => $dateStr,
                                'date' => date('F d, Y', strtotime($attendanceRecord['date'])),
                                'time_in' => $attendanceRecord['time_in']
                                    ? date('H:i:s', strtotime($attendanceRecord['time_in']))
                                    : null,
                                'time_out' => $attendanceRecord['time_out']
                                    ? date('H:i:s', strtotime($attendanceRecord['time_out']))
                                    : null,
                                'comment' => $attendanceRecord['comment'],
                                'absence_type_id' => $absenceTypeId,
                                'isNew' => false,
                            ];
                            if ($archive) {
                                $attendanceData['absence_type'] = ($absenceTypeId !== null && isset($absenceTypes[$absenceTypeId]))
                                    ? $absenceTypes[$absenceTypeId]
                                    : '';
                            }
                            break;
                        }
                    }

                    if (!$found) {
                        $attendanceData = [
                            'dateStr' => $dateStr,
                            'date' => $formattedDate,
                            'time_in' => null,
                            'time_out' => null,
                            'comment' => null,
                            'absence_type_id' => null,
                            'isNew' => true,
                        ];
                        if ($archive) {
                            $attendanceData['absence_type'] = '';
                        }
                    }

                    $staffTimeRecords[$dateStr] = $attendanceData;
                    if ($dayId != -1) {
                        $resp[$k]['date'] = $dateStr;
                    }
                    /*$historyUrl = [
                        'plugin' => 'Staff',
                        'controller' => 'Staff',
                        'action' => 'InstitutionStaffAttendanceActivities',
                        'index',
                        'user_id' => $staffId
                    ];*/
                    $historyUrl = "/".$base_url."/Staff/InstitutionStaffAttendanceActivities/index?user_id=".$staffId;
                    $resp[$k]['historyUrl'] = $historyUrl;
                }


                foreach ($staffTimeRecords as $key => $staffTimeRecord) {

                    $leaveRecords = [];
                    foreach ($staffLeaveRecords as $staffLeaveRecord) {

                        $dateFrom = date('Y-m-d', strtotime($staffLeaveRecord['date_from']));
                        $dateTo = date('Y-m-d', strtotime($staffLeaveRecord['date_to']));
                        
                        if ($dateFrom <= $key && $dateTo >= $key) {
                            $leaveRecord['isFullDay'] = $staffLeaveRecord['full_day'];
                            $leaveRecord['comment'] = $staffLeaveRecord['comments'] ?? null;
                            $leaveRecord['startTime'] = isset($staffLeaveRecord['start_time'])
                                ? date('H:i:s', strtotime($staffLeaveRecord['start_time']))
                                : '';
                            $leaveRecord['endTime'] = isset($staffLeaveRecord['end_time'])
                                ? date('H:i:s', strtotime($staffLeaveRecord['end_time']))
                                : '';
                            $leaveRecord['staffLeaveTypeName'] = $staffLeaveRecord['leave_type_name'] ?? '';
                            $leaveRecords[] = $leaveRecord;

                            if ($archive && !empty($staffLeaveRecord['comments'])) {
                                $existingComment = trim((string) ($staffTimeRecords[$key]['comment'] ?? ''));
                                $staffTimeRecords[$key]['comment'] = trim(
                                    $existingComment . ' ' . $staffLeaveRecord['comments']
                                );
                            }
                        }
                    }

                    if ($archive) {
                        $url = '/' . $base_url . '/Institution/Institutions/ArchivedStaffLeave/index?user_id=' . $staffId;
                    } else {
                        $url = '/' . $base_url . '/Institution/Institutions/StaffLeave/index?user_id=' . $staffId;
                    }
                    $staffTimeRecords[$key]['leave'] = $leaveRecords;
                    $staffTimeRecords[$key]['url'] = $url;
                }

                $resp[$k]['attendance'] = $staffTimeRecords;

                if ($archive && isset($d['user'])) {
                    $userData = $d['user'];
                    $resp[$k]['name'] = $userData['full_name'] ?? trim(
                        ($userData['first_name'] ?? '') . ' ' . ($userData['last_name'] ?? '')
                    );
                    $resp[$k]['staff_name'] = $userData['name_with_id'] ?? '';
                    $resp[$k]['photo_content'] = '';
                }
            }

            $data['data'] = $resp;

            if ($archive) {
                $data['total'] = count($resp);
                return $data;
            }

            //For POCOR-8291 start...
            $insId = '{"id":'.$institutionId.'}';
            $encodedInstitutionID = base64_encode($insId);
            $encodedInstitutionID = rtrim($encodedInstitutionID, "=");

            $url = [
                'import' => '/Institution/Institutions/'.$encodedInstitutionID.'.cake_session_id/ImportStaffAttendances/add',
                'archive' => '/Institution/Institutions/'.$encodedInstitutionID.'.cake_session_id/StaffAttendancesArchived/index'
            ];
            $data['url'] = $url;
            //For POCOR-8291 end...

            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Staff Attendances List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Staff Attendances List Not Found');
        }
    }
    //POCOR-8630 end

    public function setConditionQueryForUser($ownAttendanceView, $otherAttendanceView, $user_id, array $conditionQuery)
    {
        try{
            if ($ownAttendanceView == 0 && $otherAttendanceView == 0) {
                $conditionQuery = null;
            }
            if ($ownAttendanceView == 1 && $otherAttendanceView == 0) {
                $conditionQuery[] = "'staff_id', '=', " .$user_id;
            } elseif ($ownAttendanceView == 0 && $otherAttendanceView == 1) {
                $conditionQuery[] = "'staff_id', '!=', " .$user_id;;
            }   

            
            return $conditionQuery;

        } catch (\Exception $e) {
            Log::error(
                'Failed in setConditionQueryForUser.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return false;
        }
    }



    public function resetWeekStartEndForOneDaySearch($dayId, $dayDate, $weekStartDate, $weekEndDate)
    {
        try {
            if ($dayId != -1) {
                $weekStartDate = $dayDate;
                $weekEndDate = $dayDate;
            }
            return array($weekStartDate, $weekEndDate);
        } catch (\Exception $e){
            Log::error(
                'Failed in resetWeekStartEndForOneDaySearch.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return [];
        }
    }


    public function getAttendanceByStaffIdRecordsArray($institutionId, $academicPeriodId, $weekStartDate, $weekEndDate, $shiftId, $archive = false)
    {
        try {
            $arr = [];
            if (!$archive) {

                $allStaffAttendancesQuery = InstitutionStaffAttendances::where('institution_staff_attendances.institution_id', $institutionId)
                            ->where('academic_period_id', $academicPeriodId)
                            ->where('date', '>=', $weekStartDate)
                            ->where('date', '<=', $weekEndDate);

                if ($shiftId != -1) {

                    $allStaffAttendancesQuery = $allStaffAttendancesQuery->leftJoin('institution_staff', 'institution_staff.staff_id', '=', 'institution_staff_attendances.staff_id')
                            ->leftJoin('institution_positions', 'institution_positions.id', '=', 'institution_staff.institution_position_id')
                            ->where('institution_positions.shift_id', $shiftId);
                }
            }

            if ($archive) {

                $allStaffAttendancesQuery = InstitutionStaffAttendances::where('institution_id', $institutionId)
                        ->where('academic_period_id', $academicPeriodId)
                        ->where('date', '>=', $weekStartDate)
                        ->where('date', '<=', $weekEndDate);

            }

            $allStaffAttendances = $allStaffAttendancesQuery->get()->toArray();
            //$allStaffAttendances = $allStaffAttendancesQuery->get();
            

            //$attendanceByStaffIdRecords = \Hash::combine($allStaffAttendances, '{n}.id', '{n}', '{n}.staff_id');

            if(count($allStaffAttendances) > 0){
                foreach($allStaffAttendances as $sA){
                    $arr[$sA['staff_id']][$sA['id']] = $sA;
                }
            }

            //return $allStaffAttendances;
            return $arr;
        } catch (\Exception $e) {
            Log::error(
                'Failed in getAttendanceByStaffIdRecordsArray.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return false;
        }
    }


    public function getLeaveByStaffIdRecordsArray($institutionId, $academicPeriodId, $weekStartDate, $weekEndDate, $archive = false)
    {   
        $dataArr = [];
        
        if (!$archive) {
            //$StaffLeaveTable = TableRegistry::getTableLocator()->get('Institution.StaffLeave');
            $allStaffLeaves = new InstitutionStaffLeave();

            $allStaffLeaves = $allStaffLeaves->select('institution_staff_leave.*', 'staff_leave_types.name as leave_type_name');

            $allStaffLeaves = $allStaffLeaves->join('staff_leave_types', 'staff_leave_types.id', '=', 'institution_staff_leave.staff_leave_type_id')
                    ->where('institution_staff_leave.institution_id', $institutionId)
                    ->where('institution_staff_leave.academic_period_id', $academicPeriodId);

            if ($weekEndDate == $weekStartDate) {
            
                $allStaffLeaves = $allStaffLeaves->where('date_to', '>=', $weekEndDate)->where('date_from', '<=', $weekStartDate);
            } else {
                $allStaffLeaves = $allStaffLeaves->where(function($q) use($weekStartDate, $weekEndDate){
                        $q->where('date_to', '<=', $weekEndDate)
                        ->where('date_from', '>=', $weekStartDate);
                })->orWhere(function($q) use($weekStartDate, $weekEndDate) {
                    $q->where('date_to', '<=', $weekEndDate)
                        ->where('date_to', '>=', $weekStartDate);
                })->orWhere(function($q) use($weekStartDate, $weekEndDate) {
                    $q->where('date_from', '<=', $weekEndDate)
                        ->where('date_from', '>=', $weekStartDate);
                })->orWhere(function($q) use($weekStartDate, $weekEndDate) {
                    $q->where('date_from', '<=', $weekStartDate)
                        ->where('date_to', '>=', $weekEndDate);
                });

            }

            $allStaffLeaves = $allStaffLeaves->get()->toArray();
        }
        if ($archive) {
            //POCOR-8630 start
            $allStaffLeaves = InstitutionStaffLeaveArchive::query()
                ->select(
                    'institution_staff_leave_archived.*',
                    'staff_leave_types.name as leave_type_name'
                )
                ->join(
                    'staff_leave_types',
                    'staff_leave_types.id',
                    '=',
                    'institution_staff_leave_archived.staff_leave_type_id'
                )
                ->where('institution_staff_leave_archived.institution_id', $institutionId)
                ->where('institution_staff_leave_archived.academic_period_id', $academicPeriodId);
            //POCOR-8630 end
            if ($weekEndDate == $weekStartDate) {
            
                $allStaffLeaves = $allStaffLeaves->where('date_to', '>=', $weekEndDate)->where('date_from', '<=', $weekStartDate);
            } else {
                $allStaffLeaves = $allStaffLeaves->where(function($q) use($weekStartDate, $weekEndDate){
                        $q->where('date_to', '<=', $weekEndDate)
                        ->where('date_from', '>=', $weekStartDate);
                })->orWhere(function($q) use($weekStartDate, $weekEndDate) {
                    $q->where('date_to', '<=', $weekEndDate)
                        ->where('date_to', '>=', $weekStartDate);
                })->orWhere(function($q) use($weekStartDate, $weekEndDate) {
                    $q->where('date_from', '<=', $weekEndDate)
                        ->where('date_from', '>=', $weekStartDate);
                })->orWhere(function($q) use($weekStartDate, $weekEndDate) {
                    $q->where('date_from', '<=', $weekStartDate)
                        ->where('date_to', '>=', $weekEndDate);
                });

            }

            $allStaffLeaves = $allStaffLeaves->get()->toArray();
        }


        if(count($allStaffLeaves) > 0){
            foreach($allStaffLeaves as $sL){
                $dataArr[$sL['staff_id']][$sL['id']] = $sL;
            }
        }
        
        return $dataArr;
    }


    public function setConditionQueryForDates($weekStartDate, $weekEndDate, $conditionQuery)
    {
        $conditionQuery[] = "'start_date', '<=', '$weekStartDate'";
        $conditionQuery[] = "'start_date', '<=', '$weekEndDate'";
        
        $conditionQueryOr[] = "'end_date', '=', NULL";
        $conditionQueryOr[] = "'end_date', '>=', '$weekEndDate'";

        return [$conditionQuery,$conditionQueryOr];
    }


    public function getWorkingDays($weekStartDate, $weekEndDate)
    {
        $AcademicPeriodTable = new AcademicPeriod();
        $startDate = new DateTime($weekStartDate);
        $endDate = new DateTime($weekEndDate);
        $interval = new DateInterval('P1D');
        $daterange = new DatePeriod($startDate, $interval, $endDate->modify('+1 day'));
        // To get all the dates of the working days only
        $workingDaysArr = [];
        $workingDays = $this->getWorkingDaysOfWeek();

        foreach ($daterange as $date) {
            $dayText = $date->format('l');
            if (in_array($dayText, $workingDays)) {
                $workingDaysArr[] = $date;
            }
        }
        
        return $workingDaysArr;
    }



    public function getWorkingDaysOfWeek()
    {
        $weekdays = [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];
        $ConfigItems = new ConfigItem();
        
        $ConfigItems1 = $ConfigItems->where('code', 'first_day_of_week')->first();
        $firstDayOfWeek = $ConfigItems1->value??0;
        if($firstDayOfWeek == ""){
            $firstDayOfWeek = $ConfigItems1->default_value??0;
        }

        if($firstDayOfWeek == ""){
            $firstDayOfWeek = 1;
        }
        
        $ConfigItems2 = $ConfigItems->where('code', 'days_per_week')->first();
        $daysPerWeek = $ConfigItems2->value??0;

        if($daysPerWeek == ""){
            $daysPerWeek = $ConfigItems2->default_value??0;
        }

        if($daysPerWeek == ""){
            $daysPerWeek = 1;
        }


        $lastDayIndex = ($firstDayOfWeek + $daysPerWeek - 1) % 7;
        $week = [];
        for ($i = 0; $i < $daysPerWeek; $i++) {
            $week[] = $weekdays[$firstDayOfWeek++];
            $firstDayOfWeek = $firstDayOfWeek % 7;
        }
        return $week;
    }



    public function getQueryWithShiftId(Query $query, $shiftId)
    {
        $positions = new InstitutionPositions();
        if ($shiftId != -1) {
            $query = $query
                ->leftJoin([$positions->alias() => $positions->table()],
                    [$positions->aliasField('id = ') . $this->aliasField('institution_position_id')])
                ->where(
                    [
                        $positions->aliasField('shift_id') => $shiftId,
                    ]
                );
        }
        return $query;
    }


    public function getInstitutionShiftOption($request, $institutionId = 0)
    {
        try {
            $params = $request->all();
            $academicPeriodId = $params['academic_period_id'];
            

            $limit = config('constantvalues.defaultPaginateLimit');
                
            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $lists = InstitutionShifts::join('shift_options', 'shift_options.id', '=', 'institution_shifts.shift_option_id')
                        ->join('institutions', 'institutions.id', '=', 'institution_shifts.institution_id')
                        ->select(
                            'institution_shifts.id as institutionShiftId',
                            'institution_shifts.start_time as institutionShiftStartTime',
                            'institution_shifts.end_time as institutionShiftEndTime',
                            'institution_shifts.shift_option_id as shiftOptionId',
                            'institutions.id as institutionId',
                            'institutions.code as institutionCode',
                            'institutions.name as institutionName',
                            'shift_options.name as shiftOptionName',
                        )
                        ->where('location_institution_id', $institutionId)
                        ->where('academic_period_id', $academicPeriodId)
                        ->get()
                        ->toArray();

            $returnArr = [];
            foreach($lists as $k => $list){
                
                if($list['institutionId'] == $institutionId){
                    $shiftName = $list['shiftOptionName'];
                } else {
                    $shiftName = $list['institutionCode'] . " - " . $list['institutionName'] . " - " . $list['shiftOptionName'];
                }

                $returnArr[] = [
                    'id' => $list['shiftOptionId'],
                    'name' => $shiftName . ': ' . $list['institutionShiftStartTime'] . ' - ' . $list['institutionShiftEndTime'],
                    'start_time' => $list['institutionShiftStartTime'],
                    'end_time' => $list['institutionShiftEndTime']
                ];
            }

            if(count($returnArr) > 0){
                $defaultSelect = ['id' => '-1', 'name' => '-- All --'];
                $defaultSelect['selected'] = true;
                array_unshift($returnArr, $defaultSelect);
            }
            

            return $returnArr;
            
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
            $params = $request->all();
            $params['academic_period_id'] = $academicPeriodId;
            $limit = config('constantvalues.defaultPaginateLimit');
                
            $resp = [];

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $list = $this->findWeeksForPeriod($params, $limit);
            
            $total = 0;
            if(!empty($list)){
                $resp['list'] = $list;
                $resp['total'] = 1;
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



    public function getAcademicPeriodsWeekDays($request, $academicPeriodId, $weekId)
    {
        try {
            $params = $request->all();
            $params['academic_period_id'] = $academicPeriodId;
            $params['week_id'] = $weekId;

            $limit = config('constantvalues.defaultPaginateLimit');
                
            $resp = [];

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $list = $this->findDaysForPeriodWeek($params);
            
            $total = 0;
            if(!empty($list)){
                $resp['list'] = $list;
                $resp['total'] = 1;
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


    public function getAcademicPeriodData($academicPeriodId)
    {
        try {
            $data = AcademicPeriod::where('id', $academicPeriodId)->first();

            return $data;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Academic Periods Data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Academic Periods Data Not Found');
        }
    }



    //For POCOR-7854 Start...
    public function getAttendanceTypes($options, $gradeId)
    {
        try {
            $grade_id = $gradeId;
            $academic_period_id = $options['academic_period_id']??0;
            $institution_class_id = $options['institution_class_id']??0;
            
            $day_id = strval($options['day_id']??NULL);
            $date = new DateTime($day_id);
            $day_id = $date->format('Y-m-d'); // Format the date as desired
            $resp = [];

            $studentAttendanceMarkTypesData = StudentAttendanceMarkType::leftjoin('student_mark_type_statuses', 'student_mark_type_statuses.student_attendance_mark_type_id', '=', 'student_attendance_mark_types.id')
                ->leftjoin('student_mark_type_status_grades', 'student_mark_type_status_grades.student_mark_type_status_id', '=', 'student_mark_type_statuses.id')
                ->leftjoin('institution_class_grades', 'institution_class_grades.education_grade_id', '=', 'student_mark_type_status_grades.education_grade_id')
                ->where([
                    'institution_class_grades.institution_class_id' => $institution_class_id,
                    'student_mark_type_statuses.academic_period_id' => $academic_period_id
                ])
                ->where('student_mark_type_statuses.date_enabled', '<=', $day_id)
                ->where('student_mark_type_statuses.date_disabled', '>=', $day_id)
                ->get()
                ->toArray();

            if (count($studentAttendanceMarkTypesData) > 0) {
                $list = StudentAttendanceType::leftjoin('student_attendance_mark_types', 'student_attendance_mark_types.student_attendance_type_id', '=', 'student_attendance_types.id')
                    ->leftjoin('student_mark_type_statuses', 'student_mark_type_statuses.student_attendance_mark_type_id', '=', 'student_attendance_mark_types.id')
                    ->leftjoin('student_mark_type_status_grades', 'student_mark_type_status_grades.student_mark_type_status_id', '=', 'student_mark_type_statuses.id')
                    ->leftjoin('institution_class_grades', 'institution_class_grades.education_grade_id', '=', 'student_mark_type_status_grades.education_grade_id')
                    ->where([
                        'institution_class_grades.institution_class_id' => $institution_class_id,
                        'student_mark_type_statuses.academic_period_id' => $academic_period_id
                    ])
                    ->where('student_mark_type_statuses.date_enabled', '<=', $day_id)
                    ->where('student_mark_type_statuses.date_disabled', '>=', $day_id)
                    ->groupby('institution_class_grades.institution_class_id')
                    ->select('student_attendance_types.id', 'student_attendance_types.code');

                //For POCOR-8215/8216 start...
                if(isset($options['order'])){
                    $orderBy = $options['order_by']??"ASC";
                    $col = 'student_attendance_types.'.$options['order'];
                    $list = $list->orderBy($col, $orderBy);
                }
                            
                if(isset($options['limit'])){
                    $limit = $options['limit'];
                    $resp = $list->paginate($limit)->toArray();
                    
                } else {
                    $resp['data'] = $list->get()->toArray();
                }
                //For POCOR-8215/8216 end...

            } else {
                $list = StudentAttendanceType::select('id', 'code')
                        ->where('code', 'DAY');

                //For POCOR-8215/8216 start...
                if(isset($options['order'])){
                    $orderBy = $options['order_by']??"ASC";
                    $col = 'student_attendance_types.'.$options['order'];
                    $list = $list->orderBy($col, $orderBy);
                }
                            
                if(isset($options['limit'])){
                    $limit = $options['limit'];
                    $resp = $list->paginate($limit)->toArray();
                    
                } else {
                    $resp['data'] = $list->get()->toArray();
                }
                //For POCOR-8215/8216 end...

            }
            return $resp;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Attendance Types from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Attendance Types Not Found');
        }
    }


    public function allSubjectsByClassPerAcademicPeriod($params, $institutionId, $gradeId, $classId)
    {
        try {
            $institutionId = $institutionId;       
            $institutionClassId = $classId;
            $educationGradeId = $gradeId;
            $academicPeriodId = $params['academic_period_id'];
            $staff = JWTAuth::user();
            

            $scheduleTimetablesData = InstitutionScheduleTimetables::where('institution_class_id', $institutionClassId)->where('academic_period_id', $academicPeriodId)->get()->toArray();

            $list = InstitutionClassSubjects::join('institution_subjects', 'institution_subjects.id', '=', 'institution_class_subjects.institution_subject_id')
                    ->where('institution_class_id', $institutionClassId)
                    ->where('institution_subjects.education_grade_id', $educationGradeId)
                    ->orderBy('institution_subjects.name', 'DESC');

            $staffId = $staff->id;
            $isStaff = $staff->is_staff??0;
            $superAdmin = $staff->super_admin??0;

            if ($superAdmin == 0) {
                $allSubjectsPermission = $this->getRolePermissionAccessForAllSubjects($staffId, $institutionId);

                if (!$allSubjectsPermission) {
                    $list = $list->join('institution_subject_staff', function($q) use($staffId) {
                        $q->on('institution_subject_staff.staff_id', '=', 'institution_subjects.id')
                        ->where('institution_subject_staff.institution_subject_id', $staffId);
                    });
                }
            }

            $list = $list->select('institution_subjects.id', 'institution_subjects.name');


            //For POCOR-8215/8216 start...

            if(isset($params['limit'])){
                $limit = $params['limit'];
                $data = $list->paginate($limit)->toArray();
            } else {
                $data['data'] = $list->get()->toArray();
            }
            //For POCOR-8215/8216 end...

            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Subjects List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            
            return $this->sendErrorResponse('Subjects List Not Found');
        }
    }



    public function getRolePermissionAccessForAllSubjects($staffId, $institutionId)
    {
        try {
            $checkAccess = checkAccess();
            $roles = $checkAccess['roleIds'];
            
            $QueryResult = SecurityRoleFunctions::leftjoin('security_functions', 'security_functions.id', '=', 'security_role_functions.security_function_id')
                    ->where('security_functions.controller', 'Institutions')
                    ->whereIn('security_role_id', $roles)
                    ->where(function ($q) {
                        $q->where('security_functions._view', 'LIKE', '%AllSubjects.index%')
                        ->orWhere('security_functions._view', 'LIKE', '%AllSubjects.view%');
                    })
                    ->where('security_role_functions._view', 1)
                    ->where('security_role_functions._edit', 1)
                    ->get()
                    ->toArray();

            if(!empty($QueryResult)){
                return true;
            }
              
            return false;

        } catch (\Exception $e) {
            Log::error("failed in getRolePermissionAccessForAllSubjects", ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }
    }



    public function getStudentAttendanceMarkType($options, $institutionId, $gradeId, $classId)
    {
        try {
            $institionClassId = $classId;
            $institionId = $institutionId;
            $educationGradeId = $gradeId;

            $academicPeriodId = $options['academic_period_id'];
            $dayId = $options['day_id'];
            $weekStartDay = $options['week_start_day'];
            $weekEndDay = $options['week_end_day'];


            $attendanceOptions = $this->getAttendancePerDayOptionsByClass($institionClassId, $academicPeriodId, $dayId, $educationGradeId, $weekStartDay, $weekEndDay);
            $total = count($attendanceOptions);
            $list = $attendanceOptions;

            $resp['data'] = $list;
            $resp['total'] = $total;

            return $resp;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Student Attendance Mark Type from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Student Attendance Mark Type Not Found');
        }
    }



    public function getAttendancePerDayOptionsByClass($classId, $academicPeriodId, $dayId, $educationGradeId, $weekStartDay = '', $weekEndDay = '')
    {
        try {
            $prefix = 'Period ';

            /*$gradesResultSet = InstitutionClassGrades::where('institution_class_id', $classId)->where('education_grade_id', $educationGradeId)->pluck('education_grade_id')->toArray();*/


            $gradesResultSetQuery = DB::table('institution_class_grades')
                ->where('institution_class_id', $classId)
                ->where('education_grade_id', $educationGradeId)
                ->get()
                ->toArray();

            foreach($gradesResultSetQuery as $k => $query){
                $gradesResultSet[] = $query->education_grade_id;
            }

            if (!empty($gradesResultSet)) {
                $gradeList = $gradesResultSet;
                $attendencePerDay = 1;

                $markResultSet = StudentAttendanceMarkType::leftjoin('student_attendance_types', 'student_attendance_types.id', '=', 'student_attendance_mark_types.student_attendance_type_id')
                        ->leftjoin('student_mark_type_statuses', 'student_mark_type_statuses.student_attendance_mark_type_id', '=', 'student_attendance_mark_types.id')
                        ->leftjoin('student_mark_type_status_grades', 'student_mark_type_status_grades.student_mark_type_status_id', '=', 'student_mark_type_statuses.id')
                        ->whereIn('student_mark_type_status_grades.education_grade_id', $gradeList)
                        ->where('student_mark_type_statuses.academic_period_id', $academicPeriodId);


                if ($dayId != -1) {
                    $dayId = date('Y-m-d',strtotime($dayId));
                    $markResultSet = $markResultSet->where('student_mark_type_statuses.date_enabled', '<=', $dayId)
                        ->where('student_mark_type_statuses.date_disabled', '>=', $dayId);
                }

                $markResultSet = $markResultSet->select('student_attendance_mark_types.attendance_per_day', 'student_attendance_types.code')
                    ->first();

                $attendanceType = $markResultSet->code??"";
                if($attendanceType != "SUBJECT"){
                    if (!empty($markResultSet->attendance_per_day)) {
                        $attendencePerDay = $markResultSet->attendance_per_day??0;
                    }
                }


                $periodsData = StudentAttendancePerDayPeriod::select('student_attendance_per_day_periods.*')
                    ->leftjoin('student_mark_type_statuses', 'student_mark_type_statuses.student_attendance_mark_type_id', '=', 'student_attendance_per_day_periods.student_attendance_mark_type_id')
                    ->leftjoin('student_mark_type_status_grades', 'student_mark_type_status_grades.student_mark_type_status_id', '=', 'student_mark_type_statuses.id');

                if($dayId == -1){
                    $periodsData = $periodsData->whereIn('student_mark_type_status_grades.education_grade_id', $gradeList)
                            ->where('student_mark_type_statuses.academic_period_id', $academicPeriodId)
                            ->where('student_mark_type_statuses.date_enabled', '<=', $weekStartDay)
                            ->where('student_mark_type_statuses.date_disabled', '>=', $weekEndDay);
                } else {
                    $dayId = date('Y-m-d',strtotime($dayId));
                    $periodsData = $periodsData->whereIn('student_mark_type_status_grades.education_grade_id', $gradeList)
                            ->where('student_mark_type_statuses.academic_period_id', $academicPeriodId)
                            ->where('student_mark_type_statuses.date_enabled', '<=', $dayId)
                            ->where('student_mark_type_statuses.date_disabled', '>=', $dayId);
                }


                $periodsData = $periodsData->orderBy('student_attendance_per_day_periods.order', 'ASC')->get()->toArray();
                

                $options = [];
                $periodsDataId = [];
                $j = 0;  
                for ($k = 0; $k <= $attendencePerDay; ++$k) {
                    if(count($periodsData) > 0 && isset($periodsData[$k])){
                        $periodsDataId[] =  $periodsData[$k]['id'];
                    }
                }

                if(count($periodsDataId) > 0){
                    $periodsDataId = array_filter($periodsDataId);
                    asort($periodsDataId);
                    $periodsDataId = array_combine(range(1, count($periodsDataId)), array_values($periodsDataId));
                    $periodsDataId = array_flip($periodsDataId);
                }
                
                for ($i = 1; $i <= $attendencePerDay; ++$i) {
                    $id = $i;
                    $name = "Period ".$i;

                    if(count($periodsDataId) > 0 && count($periodsData) > 0){
                        if(isset($periodsDataId[$periodsData[$j]['id']])){
                            
                            $id = $periodsDataId[$periodsData[$j]['id']];
                            //$id = $i;
                            
                        }
                        if(isset($periodsData[$j]) && isset($periodsData[$j]['name'])){
                            $name = $periodsData[$j]['name'];
                        } 
                    }

                    $options[] = [
                        'id' => $id,
                        'name' => $name,
                    ];
                    $j++;
                }
                
                return $options;
            }
            return [];
        } catch (\Exception $e) {
            Log::error(
                'Failed in getAttendancePerDayOptionsByClass.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return false;
        }
    }



    public function getStudentAttendanceList($options, $institutionId, $gradeId, $classId)
    {
        try {
            //dd($options, $institutionId, $gradeId, $classId);

            $institutionId = $institutionId;
            $institutionClassId = $classId;
            $educationGradeId = $gradeId;
            $academicPeriodId = $options['academic_period_id'];
            $attendancePeriodId = $options['attendance_period_id'];
            $weekId = $options['week_id'];
            $weekStartDay = $options['week_start_day'];
            $weekEndDay = $options['week_end_day'];
            $day = $options['day_id'];
            $subjectId = $options['subject_id'];
            $list = [];
            $data = [];

            $studentStatus = StudentStatuses::pluck('id', 'code')->toArray();
            $studentStatusArray = [
                $studentStatus['REPEATED'],
                $studentStatus['CURRENT'],
                $studentStatus['TRANSFERRED'],
                $studentStatus['WITHDRAWN'],
                $studentStatus['GRADUATED'],
                $studentStatus['PROMOTED'],
            ];


            if ($day == -1) {
                $findDay[] = $weekStartDay;
                $findDay[] = $weekEndDay;
            } else {
                $findDay = $day;
            }


            if ($subjectId != 0) {
                $query = InstitutionClassStudents::select(
                            'institution_class_students.academic_period_id',
                            'institution_class_students.institution_class_id',
                            'institution_class_students.institution_id',
                            'institution_class_students.student_id',
                            'institution_class_students.student_status_id',
                            'institution_classes.name as class_name',
                            'institution_classes.modified_user_id',
                            'institution_classes.modified as modified_date',
                            'institution_classes.created_user_id',
                            'institution_classes.created as created_date',
                            'security_users.id',
                            'security_users.openemis_no',
                            'security_users.first_name',
                            'security_users.middle_name',
                            'security_users.third_name',
                            'security_users.last_name',
                            'security_users.preferred_name'
                        )
                        ->with('user', 'institutionClass', 'studentStatus', 'createdUser:id,first_name,middle_name,third_name,last_name,openemis_no', 'modifiedUser:id,first_name,middle_name,third_name,last_name,openemis_no')
                        ->leftjoin('institution_students', 'institution_students.student_id', '=', 'institution_class_students.student_id')
                        ->join('security_users', 'security_users.id', '=', 'institution_class_students.student_id')
                        ->join('institution_classes', 'institution_classes.id', '=', 'institution_class_students.institution_class_id')
                        ->leftjoin('institution_subject_students', function($q) {
                            $q->on('institution_subject_students.institution_class_id', '=', 'institution_class_students.institution_class_id')
                                ->on('institution_subject_students.student_id', '=', 'institution_class_students.student_id');
                        })
                        ->where('institution_class_students.academic_period_id', $academicPeriodId)
                        ->where('institution_class_students.institution_class_id', $institutionClassId)
                        ->where('institution_class_students.education_grade_id', $educationGradeId)
                        ->where('institution_subject_students.institution_subject_id', $subjectId)
                        ->where('institution_students.institution_id', $institutionId)
                        ->where('institution_students.academic_period_id', $academicPeriodId)
                        ->where('institution_students.education_grade_id', $educationGradeId)
                        ->whereIn('institution_students.student_status_id', $studentStatusArray);


                $query = $query->where(function ($q) use ($weekStartDay, $weekEndDay) {
                    $q->where('institution_students.start_date', '>=', $weekStartDay)->orWhere('institution_students.start_date', '<=', $weekEndDay);
                });

                $query = $query->where(function ($q) use ($weekStartDay, $weekEndDay) {
                    $q->where('institution_students.end_date', '>=', $weekStartDay)->orWhere('institution_students.end_date', '<=', $weekEndDay);
                });


                $query = $query->where(function ($q) use ($weekStartDay, $weekEndDay) {
                    $q->where('institution_students.start_date', '<=', $weekStartDay)->orWhere('institution_students.end_date', '>=', $weekEndDay);
                });


                if ($day != -1) {
                    $query = $query->where('institution_students.start_date', '<=', $day);

                    $query = $query->where(function ($q) use ($day) {
                        $q->where('end_date', '=', Null)->orWhere('end_date', '>=', $day);
                    });
                };

                $query = $query->groupby('institution_subject_students.student_id')->orderBy('security_users.id');

            } else {
                $query = InstitutionClassStudents::select(
                            'institution_class_students.academic_period_id',
                            'institution_class_students.institution_class_id',
                            'institution_class_students.institution_id',
                            'institution_class_students.student_id',
                            'institution_class_students.student_status_id',
                            'institution_classes.name as class_name',
                            'institution_classes.modified_user_id',
                            'institution_classes.modified as modified_date',
                            'institution_classes.created_user_id',
                            'institution_classes.created as created_date',
                            'security_users.id',
                            'security_users.openemis_no',
                            'security_users.first_name',
                            'security_users.middle_name',
                            'security_users.third_name',
                            'security_users.last_name',
                            'security_users.preferred_name'
                        )
                        ->with('user', 'institutionClass', 'studentStatus', 'createdUser:id,first_name,middle_name,third_name,last_name,openemis_no', 'modifiedUser:id,first_name,middle_name,third_name,last_name,openemis_no')
                        ->leftjoin('institution_students', 'institution_students.student_id', '=', 'institution_class_students.student_id')
                        ->join('security_users', 'security_users.id', '=', 'institution_class_students.student_id')
                        ->join('institution_classes', 'institution_classes.id', '=', 'institution_class_students.institution_class_id')
                        ->where('institution_class_students.academic_period_id', $academicPeriodId)
                        ->where('institution_class_students.institution_class_id', $institutionClassId)
                        ->where('institution_class_students.education_grade_id', $educationGradeId)
                        ->where('institution_students.institution_id', $institutionId)
                        ->where('institution_students.academic_period_id', $academicPeriodId)
                        ->where('institution_students.education_grade_id', $educationGradeId)
                        ->whereIn('institution_students.student_status_id', $studentStatusArray);

                $query = $query->where(function ($q) use ($weekStartDay, $weekEndDay) {
                    $q->where('institution_students.start_date', '>=', $weekStartDay)->orWhere('institution_students.start_date', '<=', $weekEndDay);
                });

                $query = $query->where(function ($q) use ($weekStartDay, $weekEndDay) {
                    $q->where('institution_students.end_date', '>=', $weekStartDay)->orWhere('institution_students.end_date', '<=', $weekEndDay);
                });


                $query = $query->where(function ($q) use ($weekStartDay, $weekEndDay) {
                    $q->where('institution_students.start_date', '<=', $weekStartDay)->orWhere('institution_students.end_date', '>=', $weekEndDay);
                });


                if ($day != -1) {
                    $query = $query->where('institution_students.start_date', '<=', $day);

                    $query = $query->where(function ($q) use ($day) {
                        $q->where('end_date', '=', Null)->orWhere('end_date', '>=', $day);
                    });
                }


                $query = $query->groupby('institution_students.student_id')->orderBy('security_users.first_name');
                
            }


            //For POCOR-8215/8216 start...
            if(isset($options['order'])){
                $orderBy = $options['order_by']??"ASC";
                $col = 'institution_class_students.'.$options['order'];
                $query = $query->orderBy($col, $orderBy);
            }

            if(isset($options['limit'])){
                $limit = $options['limit'];
                $resp = $query->paginate($limit)->toArray();
            } else {
                $resp['data'] = $query->get()->toArray();
            }
            //For POCOR-8215/8216 end...


            foreach($resp['data'] as $k => $q){
                $list[$k]['academic_period_id'] = $q['academic_period_id'];
                $list[$k]['institution_class_id'] = $q['institution_class_id'];
                $list[$k]['institution_class_name'] = $q['class_name'];
                $list[$k]['institution_id'] = $q['institution_id'];
                $list[$k]['student_id'] = $q['student_id'];
                $list[$k]['academic_period_id'] = $q['academic_period_id'];
                $list[$k]['student_id'] = $q['student_id'];
                $list[$k]['created_date'] = $q['created_date'];
                $list[$k]['modified_date'] = $q['modified_date'];
                $list[$k]['user'] = $q['user'];
                $list[$k]['created_user'] = $q['created_user'];
                $list[$k]['modified_user'] = $q['modified_user'];


                if ($day != -1) {
                    $academicPeriodId = $q['academic_period_id'];
                    $institutionClassId = $q['institution_class_id'];
                    $studentId = $q['student_id'];
                    $institutionId = $q['institution_id'];
                    $PRESENT = 0;
                    $conditions = [];

                    $absenceReason = array();
                    $absenceType = array();
                    $result = InstitutionStudentAbsenceDetails::with('absenceType')
                        ->select(
                            'date',
                            'period',
                            'comment',
                            'absence_type_id',
                            'student_absence_reason_id',
                            'student_absence_reasons.name as student_absence_reason_name',
                            'absence_types.code',
                            'absence_types.name as absence_type_name',
                        )
                        ->join('absence_types', 'absence_types.id', '=', 'institution_student_absence_details.absence_type_id')
                        ->leftjoin('student_absence_reasons', 'student_absence_reasons.id', '=', 'institution_student_absence_details.student_absence_reason_id')
                        ->where('academic_period_id', $academicPeriodId)
                        ->where('institution_class_id', $institutionClassId)
                        ->where('education_grade_id', $educationGradeId)
                        ->where('student_id', $studentId)
                        ->where('institution_id', $institutionId)
                        ->where('period', $attendancePeriodId)
                        ->where('date', $findDay);

                    if(isset($subjectId) && $subjectId > 0){
                        $result = $result->where('institution_student_absence_details.subject_id', $subjectId);
                    }
                        
                    $result = $result->first();


                    if(!empty($result)){
                        $data = [
                            'date' => $result->date,
                            'period' => $result->period,
                            'comment' => $result->comment,
                            'absence_type_id' => $result->absence_type_id,
                            'student_absence_reason_id' => $result->student_absence_reason_id,
                            'student_absence_reason_name' => $result->student_absence_reason_name,
                            'absence_type_code' => $result->absenceType->code,
                            'absence_type_name' => $result->absenceType->name
                        ];

                        if(isset($options['excel'])){
                            $studentAbsenceReason = StudentAbsenceReason::where('id', $result->student_absence_reason_id)->select('name')->first();

                            if (!empty($studentAbsenceReason)) {
                                $absenceReason['name'] = $studentAbsenceReason->name;
                            }

                            $absenceType = AbsenceTypes::where('id', $result->absence_type_id)->select('name', 'code')->first();

                            if (!empty($absenceType)) {
                                $absenceType['name'] = $absenceType->name;
                                $absenceType['code'] = $absenceType->code;
                            }
                        }

                    } else {
                        $isMarkedRecords = StudentAttendanceMarkedRecords::select('date', 'period')
                            ->leftjoin('institution_students', 'institution_students.institution_id', '=', 'student_attendance_marked_records.institution_id')
                            ->where('student_attendance_marked_records.academic_period_id', $academicPeriodId)
                            ->where('student_attendance_marked_records.institution_class_id', $institutionClassId)
                            ->where('student_attendance_marked_records.education_grade_id', $educationGradeId)
                            ->where('student_attendance_marked_records.institution_id', $institutionId)
                            ->where('student_attendance_marked_records.date', $findDay)
                            ->where('student_attendance_marked_records.subject_id', $subjectId)
                            //->where('institution_students.start_date', $findDay)
                            ->get()
                            ->toArray();


                        if (!empty($isMarkedRecords)) {
                            $data = [
                                'date' => $findDay,
                                'period' => $attendancePeriodId,
                                'comment' => null,
                                'absence_type_id' => $PRESENT,
                                'student_absence_reason_id' => null,
                                'student_absence_reason_name' => null,
                                'absence_type_code' => null,
                                'absence_type_name' => null
                            ];
                        } else {
                            $data = [
                                'date' => $findDay,
                                'period' => $attendancePeriodId,
                                'comment' => null,
                                'absence_type_id' => null,
                                'student_absence_reason_id' => null,
                                'student_absence_reason_name' => null,
                                'absence_type_code' => null,
                                'absence_type_name' => null
                            ];
                        }
                    }

                    $list[$k]['institution_student_absences'] = $data;


                    $getRecord = StudentAttendanceMarkedRecords::where('institution_class_id', $institutionClassId)
                            ->where('education_grade_id', $educationGradeId)
                            ->where('institution_id', $institutionId)
                            ->where('academic_period_id', $academicPeriodId)
                            ->where('date', $findDay)
                            ->where('no_scheduled_class', 1)
                            ->first();

                    if (!empty($getRecord)) {
                        $list[$k]['is_NoClassScheduled'] = 1;
                    } else {
                        $list[$k]['is_NoClassScheduled'] = 0;
                    }


                    if(isset($options['excel'])){
                        $list[$k]['attendance'] = '';

                        if($list[$k]['is_NoClassScheduled'] == 1){
                            $list[$k]['attendance'] = 'No scheduled class';
                        } else if (isset($data['absence_type_id']) && ($data['absence_type_id'] == $PRESENT)) {
                            $list[$k]['attendance'] = 'Present';
                        } else if (isset($data['absence_type_code']) && ($data['absence_type_code'] == 'EXCUSED' || $data['absence_type_code'] == 'UNEXCUSED')) {
                            $list[$k]['attendance'] = 'Absent - ' . (isset($absenceType['name'])) ? $absenceType['name'] : '';
                        } else if (isset($data['absence_type_code']) && $data['absence_type_code'] == 'LATE') {
                            $list[$k]['attendance'] = 'Late';
                        } else {
                            $list[$k]['attendance'] = 'NOTMARKED';
                        }

                        $list[$k]['comment'] = $data['comment'];
                        $list[$k]['student_absence_reasons'] = (isset($absenceReason['name'])) ? $absenceReason['name'] : NULL;
                        $list[$k]['name'] = $q['user']['first_name'] . ' ' . $list[$k]['user']['last_name'];
                        $list[$k]['class'] = $q['institution_class']['name'];
                        $list[$k]['date'] = date("d/m/Y", strtotime($findDay));
                        $list[$k]['StudentStatuses'] = $q['student_status']['name'];
                        $list[$k]['studentId'] = $q['student_id'];
                        $list[$k]['test'] = 1;

                    }

                } else {
                    $periodList = $this->getAttendancePerDayOptionsByClass($institutionClassId, $academicPeriodId, $day, $educationGradeId, $weekStartDay, $weekEndDay);

                    $array['institution_id'] = $institutionId;
                    $array['academic_period_id'] = $academicPeriodId;
                    $array['week_id'] = $weekId;
                    $array['exclude_all'] = true;
                    $array['school_closed_required'] = true;

                    $dayList = $this->findDaysForPeriodWeek($array);
                    

                    $studentListResult = InstitutionClassStudents::join('student_statuses', 'student_statuses.id', '=', 'institution_class_students.student_status_id')
                        ->where('academic_period_id', $academicPeriodId)
                        ->where('institution_class_id', $institutionClassId)
                        ->where('student_statuses.code', 'CURRENT')
                        ->pluck('institution_class_students.student_id')
                        ->toArray();

                    if (!empty($studentListResult)) {
                        $studentList = $studentListResult;

                        if (empty($studentList)) {
                            $studentList = [0];
                        }

                        $result = InstitutionStudentAbsenceDetails::with('absenceType')
                            ->select(
                                'date',
                                'period',
                                'student_id',
                                'absence_type_id',
                                'absence_types.code',
                            )
                            ->join('absence_types', 'absence_types.id', '=', 'institution_student_absence_details.absence_type_id')
                            ->where('academic_period_id', $academicPeriodId)
                            ->where('institution_class_id', $institutionClassId)
                            ->where('education_grade_id', $educationGradeId)
                            ->whereIn('student_id', $studentList)
                            ->where('institution_id', $institutionId)
                            ->where('period', $attendancePeriodId)
                            ->where('date', '>=', $weekStartDay)
                            ->where('date', '<=', $weekEndDay);

                        if(isset($subjectId) && $subjectId > 0){
                            $result = $result->where('institution_student_absence_details.subject_id', $subjectId);
                        }
                            
                        $result = $result->get()->toArray();

                        $isMarkedRecords = StudentAttendanceMarkedRecords::select('date', 'period', 'no_scheduled_class')
                            /*->leftjoin('institution_students', 'institution_students.institution_id', '=', 'student_attendance_marked_records.institution_id')*/
                            ->where('student_attendance_marked_records.academic_period_id', $academicPeriodId)
                            ->where('student_attendance_marked_records.institution_class_id', $institutionClassId)
                            ->where('student_attendance_marked_records.education_grade_id', $educationGradeId)
                            ->where('student_attendance_marked_records.institution_id', $institutionId)
                            ->where('student_attendance_marked_records.subject_id', $subjectId)
                            ->where('student_attendance_marked_records.date', '>=', $weekStartDay)
                            ->where('student_attendance_marked_records.date', '<=', $weekEndDay)
                            ->get()
                            ->toArray();

                        //dd("isMarkedRecords: ", $isMarkedRecords);

                        $studentAttenanceData = [];
                        foreach ($studentList as $value) {
                            $studentId = $value;
                            if (!isset($studentAttenanceData[$studentId])) {
                                $studentAttenanceData[$studentId] = [];
                            }

                            foreach ($dayList as $dayData) {
                                $dayId = $dayData['day'];
                                $date = $dayData['date'];
                                if (!isset($studentAttenanceData[$studentId][$dayId])) {
                                    $studentAttenanceData[$studentId][$dayId] = [];
                                }

                                foreach ($periodList as $period) {
                                    $periodId = $period['id'];
                                    if (!isset($studentAttenanceData[$studentId][$dayId][$periodId])) {
                                        $studentAttenanceData[$studentId][$dayId][$periodId] = 'NOTMARKED';
                                        if (!empty($isMarkedRecords)) {
                                            foreach ($isMarkedRecords as $entity) {
                                                $entityDate = $entity['date']->format('Y-m-d');
                                                $entityPeriod = $entity['period'];

                                                if ($entityDate == $date && $entity['no_scheduled_class'] == 1) {
                                                    $studentAttenanceData[$studentId][$dayId][$periodId] = 'NoScheduledClicked';
                                                    break;
                                                } else if ($entityDate == $date && $entityPeriod == $periodId) {
                                                    $studentAttenanceData[$studentId][$dayId][$periodId] = 'PRESENT';
                                                    break;
                                                }
                                            }
                                        }
                                    }

                                    if (!empty($result)) {
                                        foreach ($result as $entity) {
                                            $entityDateFormat = $entity['date']->format('Y-m-d');

                                            $entityStudentId = $entity['student_id'];
                                            $entityPeriod = $entity['period'];

                                            if ($studentId == $entityStudentId && $entityDateFormat == $date && $entityPeriod == $periodId) {
                                                if(isset($options['excel'])){
                                                    if ($entity['code'] == 'EXCUSED' || $entity['code'] == 'UNEXCUSED') {
                                                        $studentAttenanceData[$studentId][$dayId][$periodId] = 'ABSENT';
                                                        break;
                                                    } else {
                                                        $studentAttenanceData[$studentId][$dayId][$periodId] = $entity['code'];
                                                        break;
                                                    }
                                                } else {
                                                    $studentAttenanceData[$studentId][$dayId][$periodId] = $entity['code'];
                                                    break;
                                                }
                                            }
                                        }
                                    }
                                }

                            }
                        }
                        $list[$k]['current'] = date("d/m/Y", strtotime($weekStartDay)) . ' - ' . date("d/m/Y", strtotime($weekEndDay));

                        $s_id = $q['student_id'];

                        if(isset($studentAttenanceData[$s_id])){
                            $list[$k]['week_attendance'] = $studentAttenanceData[$s_id];
                        } else {
                            $list[$k]['week_attendance'] = "";
                        }

                        if(isset($options['excel'])){
                            $list[$k]['name'] = $q['user']['openemis_no'] . ' - ' . $q['user']['first_name'] . ' ' . $q['user']['last_name'];
                            foreach ($studentAttenanceData[$studentId] as $key => $value) {
                                foreach ($periodList as $Key => $PeriodData) {
                                    $id = (int)$PeriodData['id'];
                                    if ($value[$id] == "NoScheduledClicked") {
                                        $value[$id] = "No Scheduled Classes";
                                    }

                                    $var = 'week_attendance_status_'. $key . '-' . $PeriodData['name'];
                                    $list[$k][$var ] = $value[$id];
                                }
                            }
                        }
                    }

                }
            }

            $resp['data'] = $list;

            //For POCOR-8290 start...
            $array = '{"id":'.$institutionId.'}';
            $encodedArray = base64_encode($array);
            $encodedArray = rtrim($encodedArray, "=");
            $urlData = [
                'export' => 'Institution/Institutions/'.$encodedArray.'.cake_session_id/StudentAttendances/excel?institution_id='.$institutionId.'&institution_class_id='.$institutionClassId.'&education_grade_id='.$educationGradeId.'&academic_period_id='.$academicPeriodId.'&day_id='.$day.'&attendance_period_id='.$attendancePeriodId.'&week_start_day='.$weekStartDay.'&week_end_day='.$weekEndDay.'&subject_id='.$subjectId.'&week_id='.$weekId,
                'importAbsences' => 'Institution/Institutions/'.$encodedArray.'.cake_session_id/ImportStudentAttendances/add',
                'archive' => 'Institution/Institutions/'.$encodedArray.'.cake_session_id/InstitutionStudentAbsencesArchived/index'
            ];

            //For POCOR-8290 end...
            
            $total = count($list);
            $resp['url'] = $urlData;
            

            return $resp;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Student Attendance List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Student Attendance List Not Found');
        }
    }



    public function getStudentAttendanceMarkedRecordList($options, $institutionId, $gradeId, $classId)
    {
        try {
            $institutionId = $institutionId;
            $institutionClassId = $classId;
            $educationGradeId = $gradeId;     

            $academicPeriodId = $options['academic_period_id'];
            $day = $options['day_id'];
            $period = $options['attendance_period_id'];
            $subjectId = $options['subject_id'];
            
            $array['institution_class_id'] = $institutionClassId;
            $array['education_grade_id'] = $educationGradeId;
            $array['institution_id'] = $institutionId;
            $array['academic_period_id'] = $academicPeriodId;
            $array['day_id'] = $day;

            $data = $this->markedRecordAfterSave($array);

            $attendanceMarked = StudentAttendanceMarkedRecords::where('institution_id', $institutionId)
                    ->where('academic_period_id', $academicPeriodId)
                    ->where('institution_class_id', $institutionClassId)
                    ->where('education_grade_id', $educationGradeId)
                    ->where('date', $day)
                    ->where('period', $period)
                    ->where('subject_id', $subjectId);


            //For POCOR-8215/8216 start...
            if(isset($options['order'])){
                $orderBy = $options['order_by']??"ASC";
                $col = $options['order'];
                $attendanceMarked = $attendanceMarked->orderBy($col, $orderBy);
            }

            if(isset($options['limit'])){
                $limit = $options['limit'];
                $list = $attendanceMarked->paginate($limit)->toArray();
            } else {
                $list['data'] = $attendanceMarked->get()->toArray();
            }
            //For POCOR-8215/8216 end...

            return $list;

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Student Attendance List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Student Attendance List Not Found');
        }
    }



    public function markedRecordAfterSave($options)
    {
        try {
            $institutionClassId = $options['institution_class_id'];
            $educationGradeId = $options['education_grade_id'];
            $institutionId = $options['institution_id'];
            $academicPeriodId = $options['academic_period_id'];
            $date = $options['day_id'];
            $explodedData = explode("-", $date);

            $numberOfperiodByClass = $this->numberOfperiodByClass($options);

            $year = (int) $explodedData[0];
            $month = (int) $explodedData[1];
            $day = (int) $explodedData[2];

            $totalMarkedCount = StudentAttendanceMarkedRecords::where('institution_id', $institutionId)
                    ->where('academic_period_id', $academicPeriodId)
                    ->where('institution_class_id', $institutionClassId)
                    ->where('education_grade_id', $educationGradeId)
                    ->where('date', $date)
                    ->count();

            

            $attendancePerDay = $this->getAttendancePerDayByClass($institutionClassId, $academicPeriodId);
            
            $ClassAttendanceRecordsData = InstitutionClassAttendanceRecord::where('institution_class_id', $institutionClassId)
                    ->where('academic_period_id', $academicPeriodId)
                    ->where('year', $year)
                    ->where('month', $month)
                    ->first();

            
            if(empty($ClassAttendanceRecordsData)){
                $markedType = self::NOT_MARKED;
            }
            else if ($totalMarkedCount > count($attendancePerDay)) {
                $markedType = self::MARKED;
            } else {
                $markedType = self::PARTIAL_MARKED;
            }
            if(count($numberOfperiodByClass) == $totalMarkedCount){
                $markedType = self::MARKED;
            }


            $entityData = [
                'institution_class_id' => $institutionClassId,
                'academic_period_id' => $academicPeriodId,
                'year' => $year,
                'month' => $month,
                self::DAY_COLUMN_PREFIX . $day => $markedType
            ];

            if($ClassAttendanceRecordsData){
                $update = InstitutionClassAttendanceRecord::where('institution_class_id', $institutionClassId)
                    ->where('academic_period_id', $academicPeriodId)
                    ->where('year', $year)
                    ->where('month', $month)
                    ->update($entityData);
            } else {
                $insert = InstitutionClassAttendanceRecord::insert($entityData);
            }
            return true;

        } catch (\Exception $e) {
            Log::error(
                'Failed in markedRecordAfterSave',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            
            return false;
        }
    }


    public function numberOfperiodByClass($options)
    {
        $institionClassId = $options['institution_class_id'];
        $academicPeriodId = $options['academic_period_id'];
        $dayId = $options['day_id'];
        $educationGradeId = $options['education_grade_id'];
        
        $attendanceOptions = $this->getAttendancePerDayOptionsByClass($institionClassId, $academicPeriodId, $dayId, $educationGradeId);
        
        return $attendanceOptions;
    }


    public function getAttendancePerDayByClass($institionClassId, $academicPeriodId)
    {
        try {
            $gradeData = InstitutionClassGrades::where('institution_class_id', $institionClassId)->first();
            
            if (!is_null($gradeData)) {

                $attendancePerDay = StudentAttendanceMarkType::select('student_attendance_mark_types.id')
                        ->leftjoin('student_mark_type_statuses', 'student_mark_type_statuses.student_attendance_mark_type_id', '=', 'student_attendance_mark_types.id')
                        ->leftjoin('student_mark_type_status_grades', 'student_mark_type_status_grades.student_mark_type_status_id', '=', 'student_mark_type_statuses.id')
                        ->where('student_mark_type_status_grades.education_grade_id', $gradeData->education_grade_id)
                        ->where('student_mark_type_statuses.academic_period_id', $academicPeriodId)
                        ->first();

                if (!is_null($attendancePerDay)) {
                    $attendancePerDayId = $attendancePerDay->id;
                    
                    $modelData = StudentAttendancePerDayPeriod::select('period as id', 'name')->where('student_attendance_mark_type_id', $attendancePerDayId)->get()->toArray();

                    if (empty($modelData)) {
                        $data[] = [
                        'id' => 1,
                        'name' => 'Period 1'
                        ];
                 
                        return $data;
                    }

                    return $modelData;
                } else {
                    $data[] = [
                        'id' => 1,
                        'name' => 'Period 1'
                    ];
                 
                    return $data;
                }

            } else {
                return [];
            }

        } catch (\Exception $e) {
            Log::error(
                'Failed in getAttendancePerDayByClass',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return false;
        }
    }
    //For POCOR-7854 End...


    //For POCOR-8363 Starts...
    public function getStudentAttendancesExport($params)
    {
        try {
            $institutionId = $params['institution_id'];
            $gradeId = $params['education_grade_id'];
            $classId = $params['institution_class_id'];

            $data = $this->getStudentAttendanceList($params, $institutionId, $gradeId, $classId);

            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to export students attendances from DB.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Failed to export students attendances from DB.');
        }
    }


    public function getStudentAttendancesImportTemplate($params)
    {
        try {
            $institution_class_id = $params['institution_class_id'];
            $institution_id = $params['institution_id'];

            $currentYearData = AcademicPeriod::where("current", 1)->first();
            $institutionData = Institutions::where('id', $institution_id)->first();

            $outputData['Data']['header'] = [
                "Date ( DD/MM/YYYY )",
                "Student Attendance Type Code",
                "Period",
                "Institution Subject Name",
                " OpenEMIS ID",
                "Absence Type Code",
                "Student Absence Reason Code",
                "Comment"
            ];

            $outputData['References'] = [];

            $outputData['References']['Student Attendance Types']['header'] = ['Name', 'Code'];
            $getStudentAttendanceType = getStudentAttendanceType();
            $outputData['References']['Student Attendance Types']['data'] = $getStudentAttendanceType;


            $outputData['References']['Period']['header'] = ['Number Of Periods', 'Id'];
            $getNumberOfPeriods = getNumberOfPeriods();
            $outputData['References']['Period']['data'] = $getNumberOfPeriods;


            $outputData['References']['Subject']['header'] = ['Subject', 'Id'];
            $getInstutionClassSubject = getInstutionClassSubject($institution_id, $institution_class_id);
            $outputData['References']['Subject']['data'] = $getInstutionClassSubject;


            $institutionHeader = "Institution: ".$institutionData->name??"";
            $academicHeader = "Academic Period: ".$currentYearData->name??"";
            $outputData['References']['Student']['header'] = [$institutionHeader, $academicHeader, 'Education Grade', 'Name', 'OpenEMIS ID'];
            $getInstutionClassStudent = getInstutionClassStudent($institution_id, $institution_class_id);
            $outputData['References']['Student']['data'] = $getInstutionClassStudent;


            $outputData['References']['Absence Type']['header'] = ['Name', 'Code'];
            $getAbsenceTypes = getAbsenceTypes();
            $outputData['References']['Absence Type']['data'] = $getAbsenceTypes;


            $outputData['References']['Student Absence Reason']['header'] = ['Name', 'National Code'];
            $getStudentAbsenceReason = getStudentAbsenceReason();
            $outputData['References']['Student Absence Reason']['data'] = $getStudentAbsenceReason;
            return $outputData;

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch students attendances import template data from DB.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Failed to fetch students attendances import template data from DB.');
        }
    }


    public function studentAttendancesImport($params)
    {
        try {
            $validExtension = ['xlsx', 'xls', 'csv'];
            $extension = File::extension($params['file']->getClientOriginalName());

            if (!in_array($extension, $validExtension)) {
                return 1; //Invalid file extension...
            }

            $headers = ['Date ( DD/MM/YYYY )', 'Student Attendance Type Code', 'Period', 'Institution Subject Name', ' OpenEMIS ID', 'Absence Type Code', 'Student Absence Reason Code', 'Comment'];
            $results = StudentAttendanceImport::toArray($params['file']);
            
            if (empty($results[0][1])) {
                return 2; //Header is not present...
            }

            if (empty($results[0][2])) {
                return 3; //Imported file is empty...
            }


            foreach($headers as $k => $header){
                $trimmedArray = array_map('trim', $results[0][1]); //Removing whitespace...
                $header = trim($header);

                if(!in_array($header, $trimmedArray)){
                    return 4; //Not a valid header...
                }
            }

            $institutionClass = InstitutionClasses::where('institution_id', $params['institution_id'])->where('id', $params['institution_class_id'])->first();

            if(!$institutionClass){
                return 5; //Institution is not linked with Institution Class...
            }

            $currentAcademicPeriod = AcademicPeriod::where('current', 1)->first();
            if(!$currentAcademicPeriod){
                return 6; //No current Academic Period is set in DB...
            }

            $rowsCount = count($results[0]) - 2;
            
            if ($rowsCount > config('constantvalues.importExcelRules.maxRows')) {
                return 7; //File can not have more than 2000 records.
            }

            $import = $this->importStudentAttendances($results,  $params, $currentAcademicPeriod);
            return $import;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to import students attendances in DB.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Failed to import students attendances in DB.');
        }
    }


    public function importStudentAttendances($results,  $params, $currentAcademicPeriod)
    {   
        DB::beginTransaction();
        try {
            
            $i = -1;
            $validation = [];
            $updated_data = [];
            $add_data = [];
            $importResponse = [];
            
            foreach ($results[0] as $key => $row) {
                $errors = [];
                $i++;

                if ($i < 2) {
                    continue;
                }

                //For POCOR-8628 Start...
                if (!array_filter($row)) {
                    // Skip empty rows
                    continue;
                }
                //For POCOR-8628 End...
                
                if(is_numeric($row[0])){
                    $row[0] = Date::excelToDateTimeObject($row[0])->format('d/m/Y');
                }
                
                if (!$row[0]) { //Date
                    $label = $results[0][1][0];
                    $errors[$label] = 'Date is required.';
                } else {

                    //For POCOR-8534 start...
                    //Coverting into m/d/y because excel reads the date in m/d/y format...
                    if(is_numeric($row[0])){
                        $row[0] = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[0])->format('m/d/Y');
                    }
                    //For POCOR-8534 end...


                    if(!preg_match('/^(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/\d{4}$/', $row[0])){
                        $label = $results[0][1][0];
                        $errors[$label] = 'Invalid date format.';
                    } else {
                        $date = str_replace('/', '-', $row[0]);
                        $date = date('Y-m-d', strtotime($date));
                        
                        if($date < $currentAcademicPeriod->start_date || $date > $currentAcademicPeriod->end_date){
                            $label = $results[0][1][0];
                            $errors[$label] = 'Invalid date value. Date should be between '.$currentAcademicPeriod->start_date.' and '.$currentAcademicPeriod->end_date.' for current academic period.';
                        }
                    }
                }
                //dd($row);
                if (!$row[1]) { //Student attendance type code
                    $label = $results[0][1][1];
                    $errors[$label] = 'Student attendance type code is required.';
                }

                if (!$row[2]) { //Period
                    $label = $results[0][1][2];
                    $errors[$label] = 'Period is required.';
                }

                if (!$row[3] && ($row[1] == 'SUBJECT')) { //Institution subject name
                    $label = $results[0][1][3];
                    $errors[$label] = 'Institution subject name is required.';
                }

                if (!$row[4]) { //OpenEMIS ID
                    $label = $results[0][1][4];
                    $errors[$label] = 'OpenEMIS ID is required.';
                }

                if (!$row[5]) { //Absence type code
                    $label = $results[0][1][5];
                    $errors[$label] = 'Absence type code is required.';
                }

                if(isset($row[5]) && $row[5] == "EXCUSED"){
                    if (!$row[6]) { //Student Absence Reason Code
                        $label = $results[0][1][6];
                        $errors[$label] = 'Student absence reason code is required.';
                    }
                }
                
                $allRows = [
                    $results[0][1][0] => $row[0],
                    $results[0][1][1] => $row[1],
                    $results[0][1][2] => $row[2],
                    $results[0][1][3] => $row[3],
                    $results[0][1][4] => $row[4],
                    $results[0][1][5] => $row[5],
                    $results[0][1][6] => $row[6]
                ];


                if (count($errors) > 0) {
                    $validation[] = [
                        'row_number' => $i,
                        'data' => $allRows,
                        'errors' => $errors
                    ];
                } else {
                    $academicPeriodId = $currentAcademicPeriod->id;
                    $user = SecurityUsers::where('openemis_no', $row[4])->where('is_student', 1)->first();
                    
                    $institutionClassStudent = InstitutionClassStudents::where('student_id', $user->id??0)
                            ->where('institution_id', $params['institution_id'])
                            ->where('institution_class_id', $params['institution_class_id'])
                            ->first();

                    $attendanceType = StudentAttendanceType::where('code', $row[1])->first();

                    $institutionSubject = InstitutionSubjects::where('id', $row[3])->where('institution_id', $params['institution_id'])->first();

                    $absenceType = AbsenceTypes::where('code', $row[5])->first();

                    $absenceReason = StudentAbsenceReason::where('id', $row[6])->first();

                    $institutionClassGrade = InstitutionClassGrades::where('institution_class_id', $params['institution_class_id'])
                            ->first();

                    if(!$user){
                        $label = $results[0][1][4];
                        $errors[$label] = 'OpenEMIS ID does not exist.';
                        $validation[] = [
                            'row_number' => $i,
                            'data' => $allRows,
                            'errors' => $errors
                        ];
                    }

                    if(!$institutionClassStudent){
                        $label = $results[0][1][4];
                        $errors[$label] = 'Student does not associated with institution or institution classes.';
                        $validation[] = [
                            'row_number' => $i,
                            'data' => $allRows,
                            'errors' => $errors
                        ];
                    }

                    if(!$attendanceType){
                        $label = $results[0][1][1];
                            $errors[$label] = 'Student attendance type code does not exist.';
                            $validation[] = [
                                'row_number' => $i,
                                'data' => $allRows,
                                'errors' => $errors
                            ];
                    }

                    if(!$institutionSubject && ($attendanceType->code == 'SUBJECT')){
                        $label = $results[0][1][3];
                            $errors[$label] = 'Institution subject does not exist.';
                            $validation[] = [
                                'row_number' => $i,
                                'data' => $allRows,
                                'errors' => $errors
                            ];
                    }

                    if(!$absenceType){
                        $label = $results[0][1][5];
                            $errors[$label] = 'Absence type code does not exist.';
                            $validation[] = [
                                'row_number' => $i,
                                'data' => $allRows,
                                'errors' => $errors
                            ];
                    }

                    /*if(!$absenceReason){
                        $label = $results[0][1][6];
                            $errors[$label] = 'Student absence reason code does not exist.';
                            $validation[] = [
                                'row_number' => $i,
                                'data' => $allRows,
                                'errors' => $errors
                            ];
                    }*/
                    
                    if($user && $institutionClassStudent && $absenceType){
                        $date = str_replace('/', '-', $row[0]);
                        $date = date('Y-m-d', strtotime($date));

                        $check = InstitutionStudentAbsenceDetails::where([
                            'student_id' => $user->id,
                            'institution_id' => $params['institution_id'],
                            'academic_period_id' => $academicPeriodId,
                            'institution_class_id' => $params['institution_class_id'],
                            'date' => $date,
                            'period' => $row[2],
                            'subject_id' => $row[3]
                        ])->first();
                    
                        $insert = [];
                        $updateArr = [];
                        $storeArr = [];

                        $student_absence_reason_id = Null;
                        if($row[5] == "EXCUSED"){
                            $student_absence_reason_id = $row[6];
                        }

                        if(!$check){
                           
                            $addArr['education_grade_id'] = (int)$institutionClassGrade->education_grade_id??0;
                            $addArr['academic_period_id'] = (int)$academicPeriodId;
                            $addArr['institution_id'] = (int)$params['institution_id'];
                            $addArr['institution_class_id'] = (int)$params['institution_class_id'];
                            $addArr['date'] = $date;
                            $addArr['period'] = $row[2];
                            $addArr['subject_id'] = ($attendanceType->code == 'DAY') ? 0 : (int) ($row[3] ?? 0);
                            $addArr['student_id'] = $user->id;
                            $addArr['absence_type_id'] = $absenceType->id;
                            $addArr['student_absence_reason_id'] = $student_absence_reason_id;
                            $addArr['comment'] = $row[7];
                            $addArr['created_user_id'] = JWTAuth::user()->id;
                            $addArr['created'] = Carbon::now()->toDateTimeString();

                            // Force check for null values
                            if (is_null($addArr['subject_id']) || $addArr['subject_id'] === '') {
                                $addArr['subject_id'] = 0;
                            }

                            try {
                                $store = InstitutionStudentAbsenceDetails::insert($addArr);
                            } catch (\Exception $e) {
                                // Log::error('Failed to insert attendance record.', [
                                //     'error' => $e->getMessage(),
                                //     'data' => $addArr
                                // ]);
                                dd($e); // Re-throw for further debugging if necessary
                            }
                            
                            $add_data[] = [
                                'row_number' => $i,
                                'data' => $allRows,
                                'errors' => $errors
                            ];
                        } else {
                            $updateArr['academic_period_id'] = (int)$academicPeriodId;
                            $updateArr['education_grade_id'] = (int)$institutionClassGrade->education_grade_id??0;
                            $updateArr['institution_id'] = (int)$params['institution_id'];
                            $updateArr['institution_class_id'] = (int)$params['institution_class_id'];
                            $updateArr['date'] = $date;
                            $updateArr['period'] = $row[2];
                            $updateArr['subject_id'] = ($attendanceType->code == 'DAY') ? 0 : (int) ($row[3] ?? 0);
                            $updateArr['student_id'] = $user->id;
                            $updateArr['absence_type_id'] = $absenceType->id;
                            $updateArr['student_absence_reason_id'] = $student_absence_reason_id;
                            $updateArr['comment'] = $row[7];
                            $updateArr['modified_user_id'] = JWTAuth::user()->id;
                            $updateArr['modified'] = Carbon::now()->toDateTimeString();

                            $update = InstitutionStudentAbsenceDetails::where([
                                'student_id' => $user->id,
                                'institution_id' => (int)$params['institution_id'],
                                'academic_period_id' => (int)$academicPeriodId,
                                'institution_class_id' => (int)$params['institution_class_id'],
                                'date' => $date,
                                'period' => $row[2],
                                'subject_id' => ($attendanceType->code == 'DAY') ? 0 : (int) ($row[3] ?? 0)
                            ])->update($updateArr);

                            $updated_data[] = [
                                'row_number' => $i,
                                'data' => $allRows,
                                'errors' => $errors
                            ];
                        }

                        // For StudentAttendanceMarkedRecords Table...
                        $checkMarkedRecord = StudentAttendanceMarkedRecords::where([
                            'institution_id' => (int)$params['institution_id'],
                            'academic_period_id' => (int)$academicPeriodId,
                            'institution_class_id' => (int)$params['institution_class_id'],
                            'education_grade_id' => (int)$institutionClassGrade->education_grade_id??0,
                            'date' => $date,
                            'period' => $row[2],
                            'subject_id' => ($attendanceType->code == 'DAY') ? 0 : (int) ($row[3] ?? 0)
                        ])
                        ->first();

                        if(!$checkMarkedRecord){
                            $storeArr['institution_id'] = (int)$params['institution_id'];
                            $storeArr['academic_period_id'] = (int)$academicPeriodId;
                            $storeArr['institution_class_id'] = (int)$params['institution_class_id'];
                            $storeArr['education_grade_id'] = (int)$institutionClassGrade->education_grade_id??0;
                            $storeArr['date'] = $date;
                            $storeArr['period'] = $row[2];
                            $storeArr['subject_id'] = ($attendanceType->code == 'DAY') ? 0 : (int) ($row[3] ?? 0);

                            $insert = StudentAttendanceMarkedRecords::insert($storeArr);
                        }
                    }
                }
            }

            $importResponse = [
                'total_count' => count($results[0]) - 2,
                'records_added' => [
                    'count' => count($add_data),
                    'rows' => $add_data,
                ],
                'records_updated' => [
                    'count' => count($updated_data),
                    'rows' => $updated_data,
                ],
                'records_failed' => [
                    'count' => count($validation),
                    'rows' => $validation,
                ],
            ];
  
            DB::commit();
            return $importResponse;

        } catch (\Exception $e){
            DB::rollBack();
            Log::error(
                'Failed in importStudentAttendances method.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            
            return false;
        }
    }


    public function studentAttendancesNoScheduledClass($params)
    {
        try {
            $institutionId = $params['institution_id'];
            $academicPeriodId = $params['academic_period_id'];
            $institutionClassId = $params['institution_class_id'];
            $educationGradeId = $params['education_grade_id'];        
            $day = $params['day_id'];

            $studentAttendanceMarkedRecords = StudentAttendanceMarkedRecords::where([
                    'institution_class_id' => $institutionClassId,
                    'education_grade_id' => $educationGradeId,
                    'institution_id' => $institutionId,
                    'academic_period_id' => $academicPeriodId,
                    'date' => $day
                ])
                ->get()
                ->toArray();

            if(!empty($studentAttendanceMarkedRecords)){
                $updateArr['period'] = 0;
                $updateArr['subject_id'] = 0;
                $updateArr['no_scheduled_class'] = 1;

                $update = StudentAttendanceMarkedRecords::where([
                    'institution_class_id' => $institutionClassId,
                    'education_grade_id' => $educationGradeId,
                    'institution_id' => $institutionId,
                    'academic_period_id' => $academicPeriodId,
                    'date' => $day
                ])
                ->update($updateArr);
            } else {
                $insertArr = [
                    'institution_class_id' => $institutionClassId,
                    'education_grade_id' => $educationGradeId,
                    'institution_id' => $institutionId,
                    'academic_period_id' => $academicPeriodId,
                    'date' => $day,
                    'period' => 0,
                    'subject_id' => 0,
                    'no_scheduled_class' => 1
                ];

                $insert = StudentAttendanceMarkedRecords::insert($insertArr);
            }


            $totalMarkedCount = StudentAttendanceMarkedRecords::where([
                    'institution_class_id' => $institutionClassId,
                    'education_grade_id' => $educationGradeId,
                    'institution_id' => $institutionId,
                    'academic_period_id' => $academicPeriodId,
                    'date' => $day
                ])
                ->first();

            if(!empty($totalMarkedCount)){
                $explodedData = explode("-", $day);
                $year = (int) $explodedData[0];
                $month = (int) $explodedData[1];
                $daydata = (int) $explodedData[2];
                $classAttendanceMarked = InstitutionClassAttendanceRecord::where([
                    'academic_period_id' => $academicPeriodId,
                    'institution_class_id' => $institutionClassId,
                    'year' => $year,
                    'month' => $month
                ])
                ->first();

                if($classAttendanceMarked){
                    $updateClassAttendanceMarked = InstitutionClassAttendanceRecord::where([
                        'academic_period_id' => $academicPeriodId,
                        'institution_class_id' => $institutionClassId,
                        'year' => $year,
                        'month' => $month
                    ])
                    ->update([
                        self::DAY_COLUMN_PREFIX.$daydata => self::PARTIAL_MARKED
                    ]);
                } else {
                    $insertClassAttendanceMarked = InstitutionClassAttendanceRecord::insert([
                        'academic_period_id' => $academicPeriodId,
                        'institution_class_id' => $institutionClassId,
                        'year' => $year,
                        'month' => $month,
                        self::DAY_COLUMN_PREFIX.$daydata => self::PARTIAL_MARKED
                    ]);
                }
            }

            return true;

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
    public function getArchiveAcademicPeriods($params)
    {
        try {
            $institution_id = $params['institution_id']??0;
            $institutionClassIds = InstitutionClasses::where('institution_id', $institution_id)->pluck('id')->toArray();
            
            $academicPeriodArrayOne = InstitutionClassAttendanceRecordsArchive::whereIn('institution_class_id', $institutionClassIds)->pluck('academic_period_id')->toArray();

            $academicPeriodArrayTwo = InstitutionStudentAbsencesArchived::where('institution_id', $institution_id)->pluck('academic_period_id')->toArray();


            $academicPeriodArrayThree = InstitutionStudentAbsenceDetailsArchived::where('institution_id', $institution_id)->pluck('academic_period_id')->toArray();

            $academicPeriodArrayFour = StudentAttendanceMarkedRecordsArchived::where('institution_id', $institution_id)->pluck('academic_period_id')->toArray();


            $academicPeriodWithArchiveArrayId = [0];
            $academicPeriodWithArchiveArray = array_unique(
                array_merge($academicPeriodArrayOne,
                    $academicPeriodArrayTwo,
                    $academicPeriodArrayThree,
                    $academicPeriodArrayFour)
            );

            if (sizeof($academicPeriodWithArchiveArray) > 0) {
                $academicPeriodWithArchiveArrayId = $academicPeriodWithArchiveArray;
            }

            $academicPeriods = AcademicPeriod::where('current', '!=', 1)->whereIn('id', $academicPeriodWithArchiveArrayId);

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $academicPeriods = $academicPeriods->orderBy($col, $orderBy);
            }

            if(isset($params['limit'])){
                $limit = $params['limit'];
                $list = $academicPeriods->paginate($limit)->toArray();
            } else {
                $list['data'] = $academicPeriods->get()->toArray();
            }

            return $list;
        } catch (\Exception $e) {
            Log::error(
                'Failed to get archive academic periods.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            dd($e);
            return $this->sendErrorResponse('Failed to get archive academic periods.');
        }
    }


    public function getStudentAttendanceMarkedRecordArchiveList($params, $institutionId, $gradeId, $classId)
    {
        try {
            $institutionId = $institutionId;
            $academicPeriodId = $params['academic_period_id'];
            $day = $params['day_id'];
            $period = $params['attendance_period_id'];
            $subjectId = $params['subject_id'];

            $archives = StudentAttendanceMarkedRecordsArchived::where([
                'institution_id' => $institutionId,
                'academic_period_id' => $academicPeriodId,
                'institution_class_id' => $classId,
                //'education_grade_id' => $gradeId,
                'date' => $day,
                'period' => $period,
                'subject_id' => $subjectId,
            ]);


            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $archives = $archives->orderBy($col, $orderBy);
            }

            if(isset($params['limit'])){
                $limit = $params['limit'];
                $list = $archives->paginate($limit)->toArray();
            } else {
                $list['data'] = $archives->get()->toArray();
            }

            return $list;
        } catch (\Exception $e) {
            Log::error(
                'Failed to get student attendance marked archive.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Failed to get student attendance marked archive.');
        }
    }


    public function getStudentAttendanceArchiveList($params, $institutionId, $educationGradeId, $institutionClassId)
    {
        try {
            $academicPeriodId = $params['academic_period_id'];
            $attendancePeriodId = $params['attendance_period_id'];
            $weekId = $params['week_id'];
            $weekStartDay = $params['week_start_day'];
            $weekEndDay = $params['week_end_day'];
            $day = $params['day_id'];
            $subjectId = $params['subject_id'];

            $archive = true;
            $weekly = false;
            $dayly = false;

            if ($day == -1) {
                $weekly = true;
                $dayly = false;
            }

            if ($day != -1) {
                $weekly = false;
                $dayly = true;
            }
            $data = [];

            $query = InstitutionClassStudents::select(
                    'institution_class_students.academic_period_id',
                    'institution_class_students.institution_class_id',
                    'institution_class_students.institution_id',
                    'institution_class_students.student_id',
                    'institution_class_students.student_status_id',
                    'institution_classes.name as class_name',
                    'institution_classes.modified_user_id',
                    'institution_classes.modified as modified_date',
                    'institution_classes.created_user_id',
                    'institution_classes.created as created_date',
                    'security_users.id',
                    'security_users.openemis_no',
                    'security_users.first_name',
                    'security_users.middle_name',
                    'security_users.third_name',
                    'security_users.last_name',
                    'security_users.preferred_name'
                )
                ->with('user')
                ->join('security_users', 'security_users.id', '=', 'institution_class_students.student_id')
                ->join('institution_classes', 'institution_classes.id', '=', 'institution_class_students.institution_class_id')
                ->leftjoin('institution_students', function($q) {
                    $q->on('institution_students.institution_id', '=', 'institution_class_students.institution_id')
                        ->on('institution_students.student_id', '=', 'institution_class_students.student_id');
                })
                ->leftjoin('student_statuses', 'student_statuses.id', '=', 'institution_class_students.student_status_id')
                ->where(
                    [
                        'institution_class_students.academic_period_id' => $academicPeriodId,
                        'institution_class_students.institution_class_id' => $institutionClassId,
                        'institution_class_students.education_grade_id' => $educationGradeId
                    ]
                )
                ->where(
                    [
                        'institution_students.institution_id' => $institutionId,
                        'institution_students.academic_period_id' => $academicPeriodId,
                        'institution_students.education_grade_id' => $educationGradeId,
                        'institution_students.student_status_id' => 1
                    ]
                )
                ->groupBy('security_users.id')
                ->orderBy('security_users.first_name', 'ASC')
                ->orderBy('security_users.last_name', 'ASC');

            if ($subjectId != 0) {
                $query = $this->getAttendanceQueryWithSubjectId($query, $subjectId);
            } else {
                $subjectId = null;
            }

            $query = $this->getAttendanceQueryWithoutWithdrawn($query, $dayly, $day, $institutionId, $academicPeriodId, $educationGradeId, $weekStartDay, $weekEndDay, $archive);


            if ($dayly) {
                $query = $this->getAttendanceDailyQueryWithDayCondition($query, $day);

                $query = $this->getAttendanceDailyQueryWithDetails($query, $attendancePeriodId, $day, $subjectId, $archive);

                $query = $this->getAttendanceDailyQueryWithAbsenceTypes($query, $archive);

                $query = $this->getAttendanceDailyQueryWithMarkedRecords($query, $day, $archive);


                $query = $this->getAttendanceDailyQueryWithAbsenceReasons($query, $archive);

                $query = $this->getAttendanceDailySelectFields($query, $day, $archive);


            }
            
            if ($weekly) {
                $query = $this->getOverlapWeekCondition($query, $weekStartDay, $weekEndDay);
                return [];
            }


            if(isset($params['limit'])){
                $limit = $params['limit'];
                $data = $query->paginate($limit)->toArray();
            } else {
                $data['data'] = $query->get()->toArray();
            }

            return $data;

        } catch (\Exception $e) {
            Log::error(
                'Failed to get student attendance archive list.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Failed to get student attendance archive list.');
        }
    }


    public function getAttendanceQueryWithSubjectId($query, $subjectId)
    {
        try {
            $query = $query->join('institution_subject_students', function($q){
                    $q->on('institution_subject_students.institution_class_id', '=', 'institution_class_students.institution_class_id')
                        ->on('institution_subject_students.student_id', '=', 'institution_class_students.student_id');

                })
                ->where('institution_subject_students.institution_subject_id', $subjectId);

            return $query;
        } catch(\Exception $e) {
            Log::error(
                'Failed in getAttendanceQueryWithSubjectId method.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return false;
        }
    }


    public function getAttendanceQueryWithoutWithdrawn($query, $dayly, $day, $institutionId, $academicPeriodId, $educationGradeId, $weekStartDay, $weekEndDay, $archive)
    {
        try {
            $studentWithdraw = InstitutionStudentWithdraw::leftJoin('institution_students', function ($q) {
                    $q->on('institution_students.student_id', '=', 'institution_student_withdraw.student_id')
                    ->on('institution_students.education_grade_id', '=', 'institution_student_withdraw.education_grade_id')
                    ->on('institution_students.academic_period_id', '=', 'institution_student_withdraw.academic_period_id')
                    ->on('institution_students.institution_id', '=', 'institution_student_withdraw.institution_id');
                })
                ->where([
                    'institution_student_withdraw.institution_id' => $institutionId,
                    'institution_student_withdraw.academic_period_id' => $academicPeriodId,
                    'institution_student_withdraw.education_grade_id' => $educationGradeId
                ])
                ->where('institution_students.student_status_id', '!=', 1);

            if($dayly){
                $studentWithdraw = $studentWithdraw->where('effective_date', '<=', $day);
            } else {
                $studentWithdraw = $studentWithdraw->where('effective_date', '>=', $weekStartDay)->where('effective_date', '<=', $weekEndDay);
            }
            $studentWithdraw = $studentWithdraw->pluck('institution_student_withdraw.student_id')->toArray();
            
            if ($studentWithdraw) {
                foreach ($studentWithdraw as $withdrawStudent) {
                    $withdrawStudentIds[] = $withdrawStudent['student_id'];
                }

                if (!empty($withdrawStudentIds)) {
                    $query->whereNotIn('institution_class_students.student_id',$withdrawStudentIds);
                }
            }

            return $query;
        } catch (\Exception $e) {
            Log::error(
                'Failed in getAttendanceQueryWithoutWithdrawn method.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return false;
        }
    }



    public function getAttendanceDailyQueryWithDayCondition($query, $day)
    {
        try {
            $query = $query->where('institution_students.start_date', '<=', $day)->where(function($q) use($day){
                    $q->where('end_date', Null)->orWhere('end_date', '>=', $day);
            });

            return $query;
        } catch(\Exception $e) {
            Log::error(
                'Failed in getAttendanceDailyQueryWithDayCondition method.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return false;
        }
    }


    public function getAttendanceDailyQueryWithDetails($query, $attendancePeriodId, $day, $subjectId, $archive)
    {
        try {
            $query = $query->leftJoin('institution_student_absence_details_archived', function ($q) use($attendancePeriodId, $day, $subjectId){
                $q->on('institution_student_absence_details_archived.academic_period_id', '=', 'institution_class_students.academic_period_id')
                ->on('institution_student_absence_details_archived.institution_class_id', '=', 'institution_class_students.institution_class_id')
                ->on('institution_student_absence_details_archived.student_id', '=', 'institution_class_students.student_id')
                ->on('institution_student_absence_details_archived.institution_id', '=', 'institution_class_students.institution_id')
                ->where('institution_student_absence_details_archived.period', '=', $attendancePeriodId)
                ->where('institution_student_absence_details_archived.date', '=', $day);
                if($subjectId){
                    $q = $q->where('institution_student_absence_details_archived.subject_id', $subjectId);
                }
            });

            return $query;
        } catch (\Exception $e) {
            Log::error(
                'Failed in getAttendanceDailyQueryWithDetails method.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return false;
        }
    }


    public function getAttendanceDailyQueryWithAbsenceTypes($query, $archive)
    {
        try {
            $query = $query->leftJoin('absence_types', 'absence_types.id', '=', 'institution_student_absence_details_archived.absence_type_id');

            return $query;
        } catch (\Exception $e) {
            Log::error(
                'Failed in getAttendanceDailyQueryWithAbsenceTypes method.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return false;
        }
    }


    public function getAttendanceDailyQueryWithMarkedRecords($query, $day, $archive)
    {
        try {
            $query = $query->leftjoin('student_attendance_marked_records_archived', function ($q) use($day) {
                $q->on('student_attendance_marked_records_archived.institution_class_id', '=', 'institution_class_students.institution_class_id')
                    ->on('student_attendance_marked_records_archived.institution_id', 'institution_class_students.institution_id')
                    ->on('student_attendance_marked_records_archived.academic_period_id', 'institution_class_students.academic_period_id')
                    ->where('student_attendance_marked_records_archived.no_scheduled_class', 1)
                    ->where('student_attendance_marked_records_archived.date', $day);
            });

            return $query;
        } catch (\Exception $e) {
            Log::error(
                'Failed in getAttendanceDailyQueryWithMarkedRecords method.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            
            return false;
        }
    }


    public function getAttendanceDailyQueryWithAbsenceReasons($query, $archive)
    {
        try {
            $query = $query->leftjoin('student_absence_reasons', 'student_absence_reasons.id', '=', 'institution_student_absence_details_archived.student_absence_reason_id');

            return $query;
        } catch (\Exception $e) {
            Log::error(
                'Failed in getAttendanceDailyQueryWithAbsenceReasons method.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            
            return false;
        }
    }

    public function getAttendanceDailySelectFields($query, $day, $archive)
    {
        try {
            $query = $query->addSelect([
                'institution_student_absence_details_archived.date',
                'institution_student_absence_details_archived.date as day',
                'institution_student_absence_details_archived.period',
                'institution_student_absence_details_archived.subject_id',
                'institution_student_absence_details_archived.comment',
                'institution_student_absence_details_archived.student_absence_reason_id',
                'student_attendance_marked_records_archived.date as marked_date',
                'student_attendance_marked_records_archived.period as marked_period',
                'student_attendance_marked_records_archived.subject_id as marked_subject_id',
                'student_attendance_marked_records_archived.no_scheduled_class',
                'student_absence_reasons.name as student_absence_reason',
                'student_statuses.name as student_status',
                'absence_types.id as absence_type_id',
                'absence_types.code as absence_type_code',
                'absence_types.name as absence_type_name'
            ]);

            return $query;
        } catch (\Exception $e) {
            Log::error(
                'Failed in getAttendanceDailySelectFields method.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            
            return false;
        }
    }


    public function getOverlapWeekCondition($query, $weekStartDay, $weekEndDay)
    {
        try {
            $query = $query->where(function($q) use($weekStartDay, $weekEndDay){
                $q->where('institution_students.start_date', '>=', $weekStartDay)
                ->orWhere('institution_students.start_date', '<=', $weekEndDay);
            })
            ->where(function ($q) use($weekStartDay, $weekEndDay){
                $q->where('institution_students.end_date', '<=', $weekStartDay)
                ->where('institution_students.end_date', '>=', $weekEndDay);
            })
            ->where(function ($q) use($weekStartDay, $weekEndDay) {
                $q->where('start_date', '<=', $weekStartDay)
                ->where('end_date', '>=', $weekEndDay);
            });

            return $query;
        } catch (\Exception $e) {
            Log::error(
                'Failed in getOverlapWeekCondition method.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return false;
        }
    }


    public function getStudentAttendanceArchiveExport($params)
    {
        try {
            $list = InstitutionStudentAbsenceDetailsArchived::join('security_users', 'security_users.id', '=', 'institution_student_absence_details_archived.student_id')
                    ->join('academic_periods', 'academic_periods.id', '=', 'institution_student_absence_details_archived.academic_period_id')
                    ->join('institution_classes', 'institution_classes.id', '=', 'institution_student_absence_details_archived.institution_class_id')
                    ->join('education_grades', 'education_grades.id', '=', 'institution_student_absence_details_archived.education_grade_id')
                    ->leftjoin('absence_types', 'absence_types.id', '=', 'institution_student_absence_details_archived.absence_type_id')
                    ->leftjoin('student_absence_reasons', 'student_absence_reasons.id', '=', 'institution_student_absence_details_archived.student_absence_reason_id')
                    ->leftjoin('institution_subjects', 'institution_subjects.id', '=', 'institution_student_absence_details_archived.subject_id')
                    ->select(
                        'security_users.id as student_id',
                        'security_users.first_name',
                        'security_users.middle_name',
                        'security_users.third_name',
                        'security_users.last_name',
                        'academic_periods.name as academic_period_name',
                        'institution_classes.name as class_name',
                        'education_grades.name as education_grade_name',
                        'institution_student_absence_details_archived.date',
                        'institution_student_absence_details_archived.period',
                        'institution_student_absence_details_archived.comment',
                        'absence_types.name as absence_type_name',
                        'student_absence_reasons.name as student_absence_reason_name',
                        'institution_subjects.name as institution_subject_name',
                    )
                    ->orderBy('institution_student_absence_details_archived.date', 'ASC')
                    ->get()
                    ->toArray();
            return $list;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to export students attendances archive from DB.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to export students attendances archive from DB.');
        }
    }
    //For POCOR-8397 Ends...


    //For POCOR-8396 Start...
    public function getDataForSheet($params)
    {
        try {
            $institution_class_id = $params['institution_class_id'];
            $institution_id = $params['institution_id'];

            $currentYearData = AcademicPeriod::where("current", 1)->first();
            $institutionData = Institutions::where('id', $institution_id)->first();

            $getStudentAttendanceType = getStudentAttendanceType();

            $getNumberOfPeriods = getNumberOfPeriods();

            $getInstutionClassSubject = getInstutionClassSubject($institution_id, $institution_class_id);

            $getInstutionClassStudent = getInstutionClassStudent($institution_id, $institution_class_id);



            $getAbsenceTypes = getAbsenceTypes();

            $getStudentAbsenceReason = getStudentAbsenceReason();

            
            
            $getNewArray = $this->getNewArray($getStudentAttendanceType, $getNumberOfPeriods, $getInstutionClassSubject, $getInstutionClassStudent, $getAbsenceTypes, $getStudentAbsenceReason);
            return $getNewArray;

        } catch (\Exception $e) {
            Log::error(
                'Failed in getDataForSheet in AttendanceRepository.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Failed in getDataForSheet in AttendanceRepository.');
        }
    }


    public function getNewArray($array1, $array2, $array3, $array4, $array5, $array6)
    {
        try {
            $newArray = [];

            for ($i = 0; $i < count($array4); $i++) {
                $newRow = [];
                // Student Attendance Type data
                if (isset($array1[$i])) {
                    $newRow[] = $array1[$i]['Name'];
                    $newRow[] = $array1[$i]['Code'];
                } else {
                    $newRow[] = null;
                    $newRow[] = null;
                }
                

                // Number of periods data
                if (isset($array2[$i])) {
                    $newRow[] = $array2[$i]['Number Of Periods'];
                    $newRow[] = $array2[$i]['Id'];
                } else {
                    $newRow[] = null;
                    $newRow[] = null;
                }

                // Instution Class Subject data
                if (isset($array3[$i])) {
                    $newRow[] = $array3[$i]['Subject'];
                    $newRow[] = $array3[$i]['Id'];
                } else {
                    $newRow[] = null;
                    $newRow[] = null;
                }

                // Institution Class Student data
                if (isset($array4[$i])) {
                    $newRow[] = $array4[$i]['Institution'];
                    $newRow[] = $array4[$i]['Academic Period'];
                    $newRow[] = $array4[$i]['Education Grade'];
                    $newRow[] = $array4[$i]['Name'];
                    $newRow[] = $array4[$i]['OpenEMIS ID'];
                } else {
                    $newRow[] = null;
                    $newRow[] = null;
                    $newRow[] = null;
                    $newRow[] = null;
                    $newRow[] = null;
                }


                // Absence Types data
                if (isset($array5[$i])) {
                    $newRow[] = $array5[$i]['Name'];
                    $newRow[] = $array5[$i]['Code'];
                } else {
                    $newRow[] = null;
                    $newRow[] = null;
                }


                // Student Absence Reason data
                if (isset($array6[$i])) {
                    $newRow[] = $array6[$i]['Name'];
                    $newRow[] = $array6[$i]['National Code'];
                } else {
                    $newRow[] = null;
                    $newRow[] = null;
                }

                $newArray[] = $newRow;
            }
            return $newArray;
        } catch (\Exception $e) {
            Log::error(
                'Failed in getNewArray.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return [];
        }
    }
    //For POCOR-8396 End...

    public function getStaffAttendancesDetails($request, $institutionId, $staffId)
    {
        try {
            $staffAttendancesDetails = InstitutionStaffAttendances::select(
                    'institution_staff_attendances.date',
                    'institutions.name as institution',
                    'institution_staff_attendances.time_in as date_time_in',
                    'institution_staff_attendances.time_out as date_time_out',
                )->where('institution_id', $institutionId)
                ->where('staff_id', $staffId)
                ->leftJoin('institutions', 'institutions.id', '=', 'institution_staff_attendances.institution_id')
                ->get();
            return $staffAttendancesDetails;
                            
        } catch (\Throwable $th) {
            Log::error(
                'Failed to fetch Staff Attendances Details from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Staff Attendances Details Not Found');
        }
    }

    //POCOR-8630 STARTS
    /**
     * Build reference-sheet rows and metadata for the staff attendance import template.
     *
     * @param int $institutionId
     * @return array{institution_name:string,reference_rows:array<int,array<int,string|null>>}
     */
    public function getStaffAttendancesImportTemplateData(int $institutionId): array
    {
        $institution = Institutions::where('id', $institutionId)->first();
        if (!$institution) {
            throw new \RuntimeException('Institution not found.');
        }

        $staffMembers = InstitutionStaff::query()
            ->where('institution_staff.institution_id', $institutionId)
            ->join('security_users', 'security_users.id', '=', 'institution_staff.staff_id')
            ->select([
                'security_users.id',
                'security_users.openemis_no',
                'security_users.first_name',
                'security_users.middle_name',
                'security_users.third_name',
                'security_users.last_name',
            ])
            ->groupBy(
                'security_users.id',
                'security_users.openemis_no',
                'security_users.first_name',
                'security_users.middle_name',
                'security_users.third_name',
                'security_users.last_name',
            )
            ->orderBy('security_users.openemis_no')
            ->get();

        $staffRows = [];
        foreach ($staffMembers as $staff) {
            $nameParts = array_filter([
                $staff->first_name,
                $staff->middle_name,
                $staff->third_name,
                $staff->last_name,
            ]);
            $staffRows[] = [
                'institution' => $institution->name,
                'name' => trim(implode(' ', $nameParts)),
                'openemis_no' => $staff->openemis_no,
            ];
        }

        $academicPeriods = AcademicPeriod::query()
            ->where('academic_period_level_id', 1)
            ->orderByDesc('start_date')
            ->get(['name', 'start_date', 'end_date', 'code']);

        $periodRows = [];
        foreach ($academicPeriods as $period) {
            $periodRows[] = [
                'name' => $period->name,
                'start_date' => $this->formatImportTemplateDate($period->start_date),
                'end_date' => $this->formatImportTemplateDate($period->end_date),
                'code' => $period->code,
            ];
        }

        return [
            'institution_name' => $institution->name,
            'reference_rows' => $this->buildStaffAttendancesReferenceRows(
                $staffRows,
                $periodRows,
                $institution->name
            ),
        ];
    }


    /**
     * Merge staff and academic-period reference lists into flat spreadsheet rows.
     *
     * @param array<int,array{institution:string,name:string,openemis_no:string|null}> $staffRows
     * @param array<int,array{name:string,start_date:string,end_date:string,code:string|null}> $periodRows
     * @param string $institutionName
     * @return array<int,array<int,string|null>>
     */
    private function buildStaffAttendancesReferenceRows(
        array $staffRows,
        array $periodRows,
        string $institutionName
    ): array {
        $maxRows = max(count($staffRows), count($periodRows));
        $rows = [];

        for ($i = 0; $i < $maxRows; $i++) {
            $staff = $staffRows[$i] ?? null;
            $period = $periodRows[$i] ?? null;

            $rows[] = [
                $staff['institution'] ?? $institutionName,
                $staff['name'] ?? null,
                $staff['openemis_no'] ?? null,
                $period['name'] ?? null,
                $period['start_date'] ?? null,
                $period['end_date'] ?? null,
                $period['code'] ?? null,
            ];
        }

        return $rows;
    }


    /**
     * Format a date value for the import template reference sheet (DD/MM/YYYY).
     *
     * @param mixed $value
     * @return string|null
     */
    private function formatImportTemplateDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('d/m/Y');
        } catch (\Exception $e) {
            return null;
        }
    }


    /**
     * Import staff attendances from an uploaded Excel template.
     *
     * @param array $params
     * @return array|int
     */
    public function staffAttendancesImport(array $params)
    {
        try {
            $validExtension = ['xlsx', 'xls', 'csv'];
            $extension = strtolower(File::extension($params['file']->getClientOriginalName()));

            if (!in_array($extension, $validExtension, true)) {
                return 1;
            }

            $results = StaffAttendanceImport::toArray($params['file']);

            if (empty($results[0][1])) {
                return 2;
            }

            if (empty($results[0][2])) {
                return 3;
            }

            $columnMap = $this->mapStaffAttendanceHeaderColumns($results[0][1]);
            if ($columnMap === null) {
                return 4;
            }

            $institution = Institutions::where('id', $params['institution_id'])->first();
            if (!$institution) {
                return 5;
            }

            $rowsCount = count($results[0]) - 2;
            if ($rowsCount > config('constantvalues.importExcelRules.maxRows')) {
                return 7;
            }

            return $this->importStaffAttendances($results[0], $params, $columnMap);
        } catch (\Exception $e) {
            Log::error(
                'Failed to import staff attendances in DB.',
                ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to import staff attendances in DB.');
        }
    }


    /**
     * @param array<int,array<int,mixed>> $sheetRows
     * @param array $params
     * @param array<string,int> $columnMap
     * @return array|false
     */
    private function importStaffAttendances(array $sheetRows, array $params, array $columnMap)
    {
        DB::beginTransaction();

        try {
            $headerRow = $sheetRows[1];
            $labels = $this->buildStaffAttendanceRowLabels($headerRow, $columnMap);
            $institutionId = (int) $params['institution_id'];

            $validation = [];
            $addData = [];
            $updatedData = [];
            $processedRows = 0;

            foreach ($sheetRows as $rowIndex => $row) {
                if ($rowIndex < 2) {
                    continue;
                }

                if (!is_array($row) || !array_filter($row, static function ($value) {
                    return $value !== null && $value !== '';
                })) {
                    continue;
                }

                $processedRows++;
                $rowNumber = $rowIndex + 1;
                $rowData = $this->extractStaffAttendanceRowData($row, $columnMap, $labels);
                $errors = $this->validateStaffAttendanceImportRow($rowData, $labels, $institutionId);

                if (!empty($errors)) {
                    $validation[] = [
                        'row_number' => $rowNumber,
                        'data' => $rowData,
                        'errors' => $errors,
                    ];
                    continue;
                }

                $saveResult = $this->saveStaffAttendanceImportRow(
                    $rowData,
                    $institutionId,
                    (int) $rowData['_staff_id'],
                    (int) $rowData['_academic_period_id']
                );

                unset($rowData['_staff_id'], $rowData['_academic_period_id'], $rowData['_parsed_date'], $rowData['_parsed_time_in'], $rowData['_parsed_time_out']);

                if ($saveResult === 'added') {
                    $addData[] = [
                        'row_number' => $rowNumber,
                        'data' => $rowData,
                        'errors' => [],
                    ];
                } else {
                    $updatedData[] = [
                        'row_number' => $rowNumber,
                        'data' => $rowData,
                        'errors' => [],
                    ];
                }
            }

            $importResponse = [
                'total_count' => $processedRows,
                'records_added' => [
                    'count' => count($addData),
                    'rows' => $addData,
                ],
                'records_updated' => [
                    'count' => count($updatedData),
                    'rows' => $updatedData,
                ],
                'records_failed' => [
                    'count' => count($validation),
                    'rows' => $validation,
                ],
            ];

            DB::commit();

            return $importResponse;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error(
                'Failed in importStaffAttendances method.',
                ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return false;
        }
    }


    /**
     * @param array<int,mixed> $headerRow
     * @return array<string,int>|null
     */
    private function mapStaffAttendanceHeaderColumns(array $headerRow): ?array
    {
        $map = [];

        foreach ($headerRow as $index => $header) {
            $normalized = strtolower(preg_replace('/\s+/', ' ', trim((string) $header)));

            if ($normalized === '') {
                continue;
            }

            if (str_contains($normalized, 'openemis id')) {
                $map['openemis_id'] = $index;
            } elseif (str_contains($normalized, 'academic period code')) {
                $map['academic_period_code'] = $index;
            } elseif (str_contains($normalized, 'date')) {
                $map['date'] = $index;
            } elseif (str_contains($normalized, 'time in')) {
                $map['time_in'] = $index;
            } elseif (str_contains($normalized, 'time out')) {
                $map['time_out'] = $index;
            } elseif ($normalized === 'comment') {
                $map['comment'] = $index;
            }
        }

        $required = ['openemis_id', 'academic_period_code', 'date', 'time_in'];
        foreach ($required as $key) {
            if (!isset($map[$key])) {
                return null;
            }
        }

        return $map;
    }


    /**
     * @param array<int,mixed> $headerRow
     * @param array<string,int> $columnMap
     * @return array<string,string>
     */
    private function buildStaffAttendanceRowLabels(array $headerRow, array $columnMap): array
    {
        $labels = [];
        foreach ($columnMap as $key => $index) {
            $labels[$key] = trim((string) ($headerRow[$index] ?? $key));
        }

        return $labels;
    }


    /**
     * @param array<int,mixed> $row
     * @param array<string,int> $columnMap
     * @param array<string,string> $labels
     * @return array<string,mixed>
     */
    private function extractStaffAttendanceRowData(array $row, array $columnMap, array $labels): array
    {
        $data = [];
        foreach ($columnMap as $key => $index) {
            $data[$labels[$key]] = $this->normalizeStaffAttendanceImportCellValue($row[$index] ?? null);
        }

        return $data;
    }


    /**
     * @param mixed $value
     * @return string|null
     */
    private function normalizeStaffAttendanceImportCellValue($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value) && !is_string($value)) {
            if (is_float($value) && floor($value) == $value) {
                return (string) (int) $value;
            }

            return trim((string) $value);
        }

        return trim((string) $value);
    }


    /**
     * @param array<string,mixed> $rowData
     * @param array<string,string> $labels
     * @param int $institutionId
     * @return array<string,string>
     */
    private function validateStaffAttendanceImportRow(array &$rowData, array $labels, int $institutionId): array
    {
        $errors = [];
        $openemisLabel = $labels['openemis_id'] ?? 'Openemis ID';
        $periodLabel = $labels['academic_period_code'] ?? 'Academic Period Code';
        $dateLabel = $labels['date'] ?? 'Date ( DD/MM/YYYY )';
        $timeInLabel = $labels['time_in'] ?? 'Time In (HH:MM AM/PM)';
        $timeOutLabel = $labels['time_out'] ?? 'Time Out (HH:MM AM/PM)';

        $openemisNo = $rowData[$openemisLabel] ?? null;
        $periodCode = $rowData[$periodLabel] ?? null;
        $rawDate = $rowData[$dateLabel] ?? null;
        $rawTimeIn = $rowData[$timeInLabel] ?? null;
        $rawTimeOut = $rowData[$timeOutLabel] ?? null;

        if ($openemisNo === null || $openemisNo === '') {
            $errors[$openemisLabel] = 'Openemis ID is required.';
        }

        if ($periodCode === null || $periodCode === '') {
            $errors[$periodLabel] = 'Academic Period Code is required.';
        }

        if ($rawDate === null || $rawDate === '') {
            $errors[$dateLabel] = 'Date is required.';
        }

        if ($rawTimeIn === null || $rawTimeIn === '') {
            $errors[$timeInLabel] = 'Time In is required.';
        }

        $parsedDate = null;
        if (!isset($errors[$dateLabel]) && $rawDate !== null && $rawDate !== '') {
            $parsedDate = $this->parseStaffAttendanceImportDate($rawDate);
            if ($parsedDate === false) {
                $errors[$dateLabel] = 'Invalid date format. Expected DD/MM/YYYY or DD-MM-YYYY.';
            } else {
                $rowData[$dateLabel] = Carbon::parse($parsedDate)->format('d/m/Y');
            }
        }

        $parsedTimeIn = null;
        if (!isset($errors[$timeInLabel]) && $rawTimeIn !== null && $rawTimeIn !== '') {
            $parsedTimeIn = $this->parseStaffAttendanceImportTime($rawTimeIn);
            if ($parsedTimeIn === false) {
                $errors[$timeInLabel] = 'Invalid time format. Expected HH:MM AM/PM.';
            }
        }

        $parsedTimeOut = null;
        if ($rawTimeOut !== null && $rawTimeOut !== '') {
            $parsedTimeOut = $this->parseStaffAttendanceImportTime($rawTimeOut);
            if ($parsedTimeOut === false) {
                $errors[$timeOutLabel] = 'Invalid time format. Expected HH:MM AM/PM.';
            }
        }

        if ($parsedTimeIn && $parsedTimeOut && strtotime($parsedTimeOut) < strtotime($parsedTimeIn)) {
            $errors[$timeOutLabel] = 'Time Out is earlier than Time In.';
        }

        if (!empty($errors)) {
            return $errors;
        }

        $user = SecurityUsers::where('openemis_no', $openemisNo)->where('is_staff', 1)->first();
        if (!$user) {
            $errors[$openemisLabel] = 'Openemis ID does not exist.';
            return $errors;
        }

        $institutionStaff = InstitutionStaff::where('staff_id', $user->id)
            ->where('institution_id', $institutionId)
            ->first();

        if (!$institutionStaff) {
            $errors[$openemisLabel] = 'Staff does not belong to the selected institution.';
            return $errors;
        }

        $academicPeriod = AcademicPeriod::where('code', $periodCode)->first();
        if (!$academicPeriod) {
            $errors[$periodLabel] = 'Academic Period Code does not exist.';
            return $errors;
        }

        if ($parsedDate && ($parsedDate < $academicPeriod->start_date || $parsedDate > $academicPeriod->end_date)) {
            $errors[$dateLabel] = 'Date should be between '
                . $academicPeriod->start_date . ' and ' . $academicPeriod->end_date
                . ' for the selected academic period.';
            return $errors;
        }

        $rowData['_staff_id'] = $user->id;
        $rowData['_academic_period_id'] = $academicPeriod->id;
        $rowData['_parsed_date'] = $parsedDate;
        $rowData['_parsed_time_in'] = $parsedTimeIn;
        $rowData['_parsed_time_out'] = $parsedTimeOut;

        return $errors;
    }


    /**
     * @param array<string,mixed> $rowData
     * @param int $institutionId
     * @param int $staffId
     * @param int $academicPeriodId
     * @return string added|updated
     */
    private function saveStaffAttendanceImportRow(
        array $rowData,
        int $institutionId,
        int $staffId,
        int $academicPeriodId
    ): string {
        $commentLabel = null;

        foreach ($rowData as $label => $value) {
            if (strtolower(trim((string) $label)) === 'comment') {
                $commentLabel = $label;
                break;
            }
        }

        $payload = [
            'staff_id' => $staffId,
            'institution_id' => $institutionId,
            'academic_period_id' => $academicPeriodId,
            'date' => $rowData['_parsed_date'],
            'time_in' => $rowData['_parsed_time_in'],
            'time_out' => $rowData['_parsed_time_out'] ?? null,
            'comment' => $commentLabel ? ($rowData[$commentLabel] ?? null) : null,
        ];

        $existing = InstitutionStaffAttendances::where([
            'staff_id' => $staffId,
            'institution_id' => $institutionId,
            'academic_period_id' => $academicPeriodId,
            'date' => $payload['date'],
        ])->first();

        if ($existing) {
            $payload['modified_user_id'] = JWTAuth::user()->id;
            $payload['modified'] = Carbon::now()->toDateTimeString();
            $values = removeNonColumnFields($payload, 'institution_staff_attendances');

            InstitutionStaffAttendances::where([
                'staff_id' => $staffId,
                'institution_id' => $institutionId,
                'academic_period_id' => $academicPeriodId,
                'date' => $payload['date'],
            ])->update($values);

            return 'updated';
        }

        $payload['id'] = (string) Str::uuid();
        $payload['created_user_id'] = JWTAuth::user()->id;
        $payload['created'] = Carbon::now()->toDateTimeString();
        $values = removeNonColumnFields($payload, 'institution_staff_attendances');
        InstitutionStaffAttendances::insert($values);

        return 'added';
    }


    /**
     * @param mixed $value
     * @return string|false
     */
    private function parseStaffAttendanceImportDate($value)
    {
        if ($value === null || $value === '') {
            return false;
        }

        try {
            // Excel numeric serial date
            if (is_numeric($value)) {
                return Date::excelToDateTimeObject($value)
                    ->format('Y-m-d');
            }

            $value = trim((string)$value);

            /*
            |--------------------------------------------------------------------------
            | Handle Excel converted US format like 5/26/2026
            |--------------------------------------------------------------------------
            */
            if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $value)) {
                $parts = explode('/', $value);
                /*
                |--------------------------------------------------------------------------
                | If middle number > 12 => definitely DD/MM/YYYY
                |--------------------------------------------------------------------------
                */
                if ((int)$parts[1] > 12) {
                    $date = DateTime::createFromFormat('m/d/Y', $value);
                } else {
                    /*
                    |--------------------------------------------------------------------------
                    | Default assume DD/MM/YYYY
                    |--------------------------------------------------------------------------
                    */
                    $date = DateTime::createFromFormat('d/m/Y', $value);
                }

                if ($date instanceof DateTime) {
                    return $date->format('Y-m-d');
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Handle DD-MM-YYYY
            |--------------------------------------------------------------------------
            */
            $date = DateTime::createFromFormat('d-m-Y', $value);
            if ($date instanceof DateTime) {
                return $date->format('Y-m-d');
            }
        } catch (\Exception $e) {
            return false;
        }
        return false;
    }


    /**
     * @param mixed $value
     * @return string|false
     */
    private function parseStaffAttendanceImportTime($value)
    {
        if ($value === null || $value === '') {
            return false;
        }

        if (is_numeric($value)) {
            try {
                return Date::excelToDateTimeObject((float) $value)->format('H:i:s');
            } catch (\Exception $e) {
                return false;
            }
        }

        $value = trim((string) $value);
        $formats = ['h:i A', 'g:i A', 'H:i:s', 'H:i'];

        foreach ($formats as $format) {
            $time = DateTime::createFromFormat($format, strtoupper($value));
            if ($time) {
                return $time->format('H:i:s');
            }
        }

        $timestamp = strtotime($value);
        if ($timestamp !== false) {
            return date('H:i:s', $timestamp);
        }

        return false;
    }
    /**
     * Build staff attendance export data (one sheet per month in the academic period).
     *
     * @param array $params
     * @return array{sheets:array<int,array<string,mixed>>}
     */
    public function getStaffAttendancesExport(array $params): array
    {
        $institutionId = (int) $params['institution_id'];
        $academicPeriodId = (int) $params['academic_period_id'];

        $academicPeriod = AcademicPeriod::where('id', $academicPeriodId)->first();
        if (!$academicPeriod) {
            throw new \RuntimeException('Academic period not found.');
        }

        $startDate = Carbon::parse($academicPeriod->start_date)->format('Y-m-d');
        $endDate = Carbon::parse($academicPeriod->end_date)->format('Y-m-d');
        $workingDays = $this->getWorkingDaysOfWeek();
        $months = $this->generateMonthsByDates($startDate, $endDate);

        $sheets = [];
        foreach ($months as $month) {
            $year = $month['year'];
            $monthNumber = $month['month']['inNumber'];
            $sheetName = $month['month']['inString'] . ' ' . $year;

            $days = $this->generateDaysOfMonthForExport($year, $monthNumber, $startDate, $endDate);
            $workingDayColumns = array_values(array_filter(
                $days,
                static function (array $day) use ($workingDays) {
                    return in_array($day['weekDay'], $workingDays, true);
                }
            ));

            if (empty($workingDayColumns)) {
                continue;
            }

            $monthStart = $workingDayColumns[0]['date'];
            $monthEnd = $workingDayColumns[count($workingDayColumns) - 1]['date'];

            $staffMembers = $this->getInstitutionStaffForExport($institutionId, $monthStart, $monthEnd);
            $attendanceMap = $this->getStaffAttendanceExportMap(
                $institutionId,
                $academicPeriodId,
                $monthStart,
                $monthEnd
            );
            $leaveMap = $this->getStaffLeaveExportMap(
                $institutionId,
                $monthStart,
                $monthEnd,
                $workingDays
            );

            $headers = array_merge(
                ['OpenEMIS ID', 'Staff'],
                array_column($workingDayColumns, 'label')
            );

            $rows = [];
            foreach ($staffMembers as $staffMember) {
                $row = [
                    $staffMember['openemis_no'],
                    $staffMember['name'],
                ];

                foreach ($workingDayColumns as $day) {
                    $staffId = $staffMember['staff_id'];
                    $date = $day['date'];
                    $attendance = $attendanceMap[$staffId][$date] ?? null;
                    $leave = $leaveMap[$staffId][$date] ?? null;
                    $row[] = $this->formatStaffAttendanceExportCell($attendance, $leave);
                }

                $rows[] = $row;
            }

            $sheets[] = [
                'name' => $sheetName,
                'headers' => $headers,
                'rows' => $rows,
            ];
        }

        return ['sheets' => $sheets];
    }


    /**
     * @param string $startDate
     * @param string $endDate
     * @return array<int,array{month:array{inNumber:string,inString:string},year:string}>
     */
    private function generateMonthsByDates(string $startDate, string $endDate): array
    {
        $result = [];
        $stampStartDay = strtotime($startDate);
        $stampEndDay = strtotime($endDate);
        $stampFirstDayOfMonth = strtotime('01-' . date('m', $stampStartDay) . '-' . date('Y', $stampStartDay));

        while ($stampFirstDayOfMonth <= $stampEndDay) {
            $result[] = [
                'month' => [
                    'inNumber' => date('m', $stampFirstDayOfMonth),
                    'inString' => date('F', $stampFirstDayOfMonth),
                ],
                'year' => date('Y', $stampFirstDayOfMonth),
            ];
            $stampFirstDayOfMonth = strtotime('+1 month', $stampFirstDayOfMonth);
        }

        return $result;
    }


    /**
     * @param string|int $year
     * @param string|int $month
     * @param string $startDate
     * @param string $endDate
     * @return array<int,array{weekDay:string,date:string,day:string,label:string}>
     */
    private function generateDaysOfMonthForExport($year, $month, string $startDate, string $endDate): array
    {
        $days = [];
        $stampStartDay = strtotime($startDate);
        $stampEndDay = strtotime($endDate);
        $stampFirstDayOfMonth = strtotime($year . '-' . $month . '-01');
        $stampFirstDayNextMonth = strtotime('+1 month', $stampFirstDayOfMonth);
        $tempStamp = ($stampFirstDayOfMonth <= $stampStartDay) ? $stampStartDay : $stampFirstDayOfMonth;

        while ($tempStamp <= $stampEndDay && $tempStamp < $stampFirstDayNextMonth) {
            $weekDay = date('l', $tempStamp);
            $date = date('Y-m-d', $tempStamp);
            $day = date('d', $tempStamp);

            $days[] = [
                'weekDay' => $weekDay,
                'date' => $date,
                'day' => $day,
                'label' => sprintf('%s (%s)', $day, $weekDay),
            ];

            $tempStamp = strtotime('+1 day', $tempStamp);
        }

        return $days;
    }


    /**
     * @param int $institutionId
     * @param string $startDate
     * @param string $endDate
     * @return array<int,array{staff_id:int,openemis_no:string|null,name:string}>
     */
    private function getInstitutionStaffForExport(int $institutionId, string $startDate, string $endDate): array
    {
        $staff = InstitutionStaff::query()
            ->where('institution_staff.institution_id', $institutionId)
            ->where('institution_staff.start_date', '<=', $endDate)
            ->where(function ($query) use ($startDate) {
                $query->whereNull('institution_staff.end_date')
                    ->orWhere('institution_staff.end_date', '>=', $startDate);
            })
            ->join('security_users', 'security_users.id', '=', 'institution_staff.staff_id')
            ->orderBy('security_users.first_name')
            ->orderBy('security_users.last_name')
            ->get([
                'institution_staff.staff_id',
                'security_users.openemis_no',
                'security_users.first_name',
                'security_users.middle_name',
                'security_users.third_name',
                'security_users.last_name',
            ]);

        $result = [];
        $seenStaffIds = [];

        foreach ($staff as $member) {
            if (isset($seenStaffIds[$member->staff_id])) {
                continue;
            }
            $seenStaffIds[$member->staff_id] = true;

            $nameParts = array_filter([
                $member->first_name,
                $member->middle_name,
                $member->third_name,
                $member->last_name,
            ]);

            $result[] = [
                'staff_id' => (int) $member->staff_id,
                'openemis_no' => $member->openemis_no,
                'name' => trim(implode(' ', $nameParts)),
            ];
        }

        return $result;
    }


    /**
     * @return array<int,array<string,array{time_in:?string,time_out:?string}>>
     */
    private function getStaffAttendanceExportMap(
        int $institutionId,
        int $academicPeriodId,
        string $startDate,
        string $endDate
    ): array {
        $records = InstitutionStaffAttendances::query()
            ->where('institution_id', $institutionId)
            ->where('academic_period_id', $academicPeriodId)
            ->where('date', '>=', $startDate)
            ->where('date', '<=', $endDate)
            ->get(['staff_id', 'date', 'time_in', 'time_out']);

        $map = [];
        foreach ($records as $record) {
            $date = Carbon::parse($record->date)->format('Y-m-d');
            $map[(int) $record->staff_id][$date] = [
                'time_in' => $record->time_in,
                'time_out' => $record->time_out,
            ];
        }

        return $map;
    }


    /**
     * @param array<int,string> $workingDays
     * @return array<int,array<string,array{full_day:int|bool}>>
     */
    private function getStaffLeaveExportMap(
        int $institutionId,
        string $startDate,
        string $endDate,
        array $workingDays
    ): array {
        $leaveRecords = InstitutionStaffLeave::query()
            ->where('institution_id', $institutionId)
            ->where(function ($query) use ($startDate, $endDate) {
                $query->where(function ($q) use ($startDate, $endDate) {
                    $q->where('date_from', '<=', $endDate)
                        ->where('date_to', '>=', $startDate);
                });
            })
            ->get(['staff_id', 'date_from', 'date_to', 'full_day']);

        $map = [];
        foreach ($leaveRecords as $leave) {
            $periodStart = Carbon::parse($leave->date_from)->startOfDay();
            $periodEnd = Carbon::parse($leave->date_to)->startOfDay();

            while ($periodStart->lte($periodEnd)) {
                $dateStr = $periodStart->format('Y-m-d');
                $dayText = $periodStart->format('l');

                if (
                    in_array($dayText, $workingDays, true)
                    && $dateStr >= $startDate
                    && $dateStr <= $endDate
                ) {
                    $map[(int) $leave->staff_id][$dateStr] = [
                        'full_day' => (int) $leave->full_day,
                    ];
                }

                $periodStart->addDay();
            }
        }

        return $map;
    }


    /**
     * @param array{time_in:?string,time_out:?string}|null $attendance
     * @param array{full_day:int|bool}|null $leave
     */
    private function formatStaffAttendanceExportCell(?array $attendance, ?array $leave): string
    {
        if ($leave !== null) {
            if (!empty($leave['full_day'])) {
                return 'Absent Full Day';
            }

            if ($attendance !== null && !empty($attendance['time_in'])) {
                return 'Absent Half Day' . "\n" . $this->formatStaffAttendanceExportTimeRange($attendance);
            }

            return 'Absent Half Day';
        }

        if ($attendance !== null && !empty($attendance['time_in'])) {
            return $this->formatStaffAttendanceExportTimeRange($attendance);
        }

        return 'Attendance Not Marked';
    }


    /**
     * @param array{time_in:?string,time_out:?string} $attendance
     */
    private function formatStaffAttendanceExportTimeRange(array $attendance): string
    {
        $timeIn = $this->formatStaffAttendanceExportTime($attendance['time_in']);
        if (empty($attendance['time_out'])) {
            return $timeIn;
        }

        return $timeIn . ' - ' . $this->formatStaffAttendanceExportTime($attendance['time_out']);
    }


    /**
     * @param mixed $time
     */
    private function formatStaffAttendanceExportTime($time): string
    {
        if (empty($time)) {
            return '';
        }

        return Carbon::parse($time)->format('g:i A');
    }

    //POCOR-8630 ENDS
}


        
