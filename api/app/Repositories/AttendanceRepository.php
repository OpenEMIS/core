<?php

namespace App\Repositories;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\ConfigItem;
use App\Models\CalendarEventDate;
use App\Models\InstitutionStaffAttendances;
use App\Models\InstitutionStaffLeave;
use App\Models\InstitutionStaffLeaveArchive;
use App\Models\InstitutionPositions;
use App\Models\InstitutionStaff;
use App\Models\InstitutionShifts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use JWTAuth;
use Illuminate\Support\Facades\DB;
use DateTime;
use DateInterval;
use DatePeriod;

class AttendanceRepository extends Controller
{

    public function getAcademicPeriods($request)
    {
        try {
            $params = $request->all();

            $limit = config('constantvalues.defaultPaginateLimit');
                
            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $list = $this->findSchoolAcademicPeriod($params, $limit);
            $resp['list'] = $list;

            return $resp;
            
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


    public function findSchoolAcademicPeriod($params, $limit)
    {
        try {
            $list = AcademicPeriod::where('editable', 1)
                        ->where('parent_id', '!=', 0)
                        ->where('visible', '=', 1)
                        ->orderBy('order', 'ASC');

            $list = $list->paginate($limit);

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


    public function findDaysForPeriodWeek($params, $limit)
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


    public function getStaffAttendances($request, $institutionId)
    {
        try {
            $params = $request->all();
            $resp = [];

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

            

            $attendanceByStaffIdRecords = $this->getAttendanceByStaffIdRecordsArray($institutionId, $academicPeriodId, $weekStartDate, $weekEndDate, $shiftId);
            

            $leaveByStaffIdRecords = $this->getLeaveByStaffIdRecordsArray($institutionId, $academicPeriodId, $weekStartDate, $weekEndDate);


            $conditionQueryArray = $this->setConditionQueryForDates($weekStartDate, $weekEndDate, $conditionQuery);

            $conditionQuery = $conditionQueryArray[0]??[];
            $conditionQueryOR = $conditionQueryArray[1]??[];

            

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
                if ($ownAttendanceView == 0 && $otherAttendanceView == 0) {
                    //
                }
                if ($ownAttendanceView == 1 && $otherAttendanceView == 0) {
                    $query = $query->where('institution_staff.staff_id', $user_id);
                } elseif ($ownAttendanceView == 0 && $otherAttendanceView == 1) {
                    $query = $query->where('institution_staff.staff_id', '!=', $user_id);
                }
            }

            if($weekStartDate == $weekEndDate){
                $query = $query->where('start_date', '<=', $weekStartDate);
            } else {
                $query = $query->where('start_date', '<=', $weekStartDate)
                        ->where('start_date', '<=', $weekEndDate);
            }
            

            $query = $query->where(function ($q) use($weekStartDate, $weekEndDate) {
                $q->where('end_date', Null)->orWhere('end_date', '>=', $weekEndDate);
            });


            $data = $query->orderBy('security_users.first_name')
                        ->groupBy('institution_staff.staff_id')
                        ->get()
                        ->toArray();

            $total = count($data);
            $resp = [];
            
            foreach ($data as $k => $d) {
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
                            //isNew determines if record is existing data
                            $attendanceData = [
                                'dateStr' => $dateStr,
                                'date' => date('F d, Y', strtotime($attendanceRecord['date'])),
                                'time_in' => date('H:i:s', strtotime($attendanceRecord['time_in'])),
                                'time_out' => date('H:i:s', strtotime($attendanceRecord['time_out'])),
                                'comment' => $attendanceRecord['comment'],
                                'absence_type_id' => $attendanceRecord['absence_type_id'],
                                'isNew' => false
                            ];
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
                            'isNew' => true
                        ];
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
                            $leaveRecord['startTime'] = isset($staffLeaveRecord['start_time']) ? date('H:i:s', strtotime($staffLeaveRecord['start_time'])) : "";
                            $leaveRecord['endTime'] = isset($staffLeaveRecord['end_time']) ? date('H:i:s', strtotime($staffLeaveRecord['end_time'])) : "";
                            $leaveRecord['staffLeaveTypeName'] = $staffLeaveRecord['leave_type_name']??"";
                            $leaveRecords[] = $leaveRecord;
                        }
                    }

                    
                    //dd($base_url);
                    /*$url = [
                        'plugin' => 'Institution',
                        'controller' => 'Institutions',
                        'action' => 'StaffLeave',
                        'index',
                        'user_id' => $staffId
                    ];*/
                    $url = "/".$base_url."/Institution/Institutions/StaffLeave/index?user_id=".$staffId;
                    $staffTimeRecords[$key]['leave'] = $leaveRecords;
                    $staffTimeRecords[$key]['url'] = $url;
                }

                $resp[$k]['attendance'] = $staffTimeRecords;
            }
            $resp['total'] = $total;

            return $resp;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Staff Attendances List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Staff Attendances List Not Found');
        }
    }


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

                $allStaffAttendancesQuery = InstitutionStaffAttendances::where('institution_id', $institutionId)
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
            //$StaffLeaveTable = TableRegistry::get('Institution.StaffLeave');
            $allStaffLeaves = new InstitutionStaffLeave();

            $allStaffLeaves = $allStaffLeaves->select('institution_staff_leave.*', 'staff_leave_types.name as leave_type_name');

            $allStaffLeaves = $allStaffLeaves->join('staff_leave_types', 'staff_leave_types.id', '=', 'institution_staff_leave.staff_leave_type_id')
                    ->where('institution_id', $institutionId)
                    ->where('academic_period_id', $academicPeriodId);

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
            $allStaffLeaves = new InstitutionStaffLeaveArchive();
            $allStaffLeaves = $allStaffLeaves->where('institution_id', $institutionId)
                    ->where('academic_period_id', $academicPeriodId);

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

            $list = $this->findDaysForPeriodWeek($params, $limit);
            
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

}


        
