<?php

namespace App\Repositories;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\ConfigItem;
use App\Models\CalendarEventDate;
use App\Models\InstitutionStaffAttendances;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use JWTAuth;
use Illuminate\Support\Facades\DB;

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

            $action_type = $params['action_type'];

            $resp['action_type'] = $action_type;
            if($action_type == 'SchoolAcademicPeriod'){

                $list = $this->findSchoolAcademicPeriod($params, $limit);
                $resp['list'] = $list;

            } elseif($action_type == 'WeeksForPeriod'){

                $list = $this->findWeeksForPeriod($params, $limit);
                $resp['list'] = $list;

            } elseif($action_type == 'DaysForPeriodWeek'){

                $list = $this->findDaysForPeriodWeek($params, $limit);
                $resp['list'] = $list;

            } else {
                $resp['list'] = [];
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
            $counter = 0;
            $daysInWeek = $lastDayIndex;
            do {
                
                if($counter > 0){
                    $lastDayIndex = $daysInWeek - 1;  
                }
                
                $endDate = date('Y-m-d', strtotime("+".$lastDayIndex." day", strtotime($startDate)));
                
                if ($endDate > $period->end_date) {
                    $endDate = $period->end_date;
                }

                $weeks[$weekIndex++] = [$startDate, $endDate];

                $startDate = $endDate;
                $startDate = date('Y-m-d', strtotime("+1 day", strtotime($startDate)));
                
                $counter++;

            } while ($endDate < $period->end_date);
            
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
            }

            return $list;
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


    public function getStaffAttendances($request)
    {
        try {
            $params = $request->all();
            $resp = [];

            $institutionId = $params['institution_id'];
            $academicPeriodId = $params['academic_period_id'];
            $ownAttendanceView = $params['own_attendance_view'];
            $otherAttendanceView = $params['other_attendance_view'];
            $shiftId = $params['shift_id'];
            $weekStartDate = $params['week_start_day'];
            $weekEndDate = $params['week_end_day'];
            $dayId = $params['day_id'];
            //if -1 = that means all days of the week
            $dayDate = $params['day_date'];

            $user = JWTAuth::user();
            $superAdmin = $user->super_admin;
            $user_id = $user->id;

            $conditionQuery = [
                'institution_id' => $institutionId,
            ];

            if ($superAdmin == 0) {
                $conditionQuery = $this->setConditionQueryForUser($ownAttendanceView, $otherAttendanceView, $user_id, $conditionQuery);
                
            }


            //if $dayId != -1 then $weekStartDate = $weekEndDate
            list($weekStartDate, $weekEndDate) =
                $this->resetWeekStartEndForOneDaySearch($dayId, $dayDate, $weekStartDate, $weekEndDate);

            

            $attendanceByStaffIdRecords = $this->getAttendanceByStaffIdRecordsArray($institutionId, $academicPeriodId, $weekStartDate, $weekEndDate, $shiftId);

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
                $conditionQuery['staff_id'] = $user_id;
            } elseif ($ownAttendanceView == 0 && $otherAttendanceView == 1) {
                //$conditionQuery[$this->aliasField('staff_id != ')] = $user_id;
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
            

            /*$attendanceByStaffIdRecords = Hash::combine($allStaffAttendances, '{n}.id', '{n}', '{n}.staff_id');
            return $attendanceByStaffIdRecords;*/

            return $allStaffAttendances;
        } catch (\Exception $e) {
            Log::error(
                'Failed in getAttendanceByStaffIdRecordsArray.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return false;
        }
    }
}


        
