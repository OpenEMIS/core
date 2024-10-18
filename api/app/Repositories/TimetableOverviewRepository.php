<?php

namespace App\Repositories;

use App\Http\Controllers\Controller;
use App\Models\InstitutionScheduleTimetables;
use App\Models\InstitutionScheduleLessons;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;
use JWTAuth;


//POCOR-8616
class TimetableOverviewRepository extends Controller
{

   public function getTimetableOverview($params)
   {
        try {
         $timeSystem =  InstitutionScheduleTimetables::with('academicPeriod',
                        'institution',
                        'scheduleTerm',
                        'scheduleInterval.shift.shiftOption',
                        'institutionClass.grades.educationGrades'
                     );

          if(isset($params['academic_period_id'])){
                $timeSystem = $timeSystem->where('academic_period_id', $params['academic_period_id'])->where('institution_id', $params['institution_id'])->where('institution_class_id', $params['institution_class_id'])->where('institution_schedule_term_id', $params['institution_schedule_term_id']);
            }
           
            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $timeSystem = $timeSystem->orderBy($col, $orderBy);
            }

            if(isset($params['limit'])){
                $limit = $params['limit'];
                $list = $timeSystem->paginate($limit)->toArray();
            } else {
                $list['data'] = $timeSystem->get()->toArray();

            }
            return $list;

        } catch (\Exception $e) {
            throw $e;
        }
   }

   public function getScheduleTimeTableExport($params)
    {
        $timeTableId = $params['timetable_id'];
        $record =  $this->getAllLesson($timeTableId);
        try {
            $list = InstitutionScheduleTimetables::join('academic_periods', 'academic_periods.id', '=', 'institution_schedule_timetables.academic_period_id')
                    ->join('institution_classes', 'institution_classes.id', '=', 'institution_schedule_timetables.institution_class_id')
                    ->join('institutions', 'institutions.id', '=', 'institution_schedule_timetables.institution_id')
                    ->join('institution_schedule_intervals', 'institution_schedule_intervals.id', '=', 'institution_schedule_timetables.institution_schedule_interval_id')
                    ->join('institution_schedule_terms', 'institution_schedule_terms.id', '=', 'institution_schedule_timetables.institution_schedule_term_id')
                    ->join('institution_class_grades', 'institution_class_grades.institution_class_id', '=', 'institution_schedule_timetables.institution_class_id')
                    ->join('education_grades', 'education_grades.id', '=', 'institution_class_grades.education_grade_id')
                    ->where('institution_schedule_timetables.id', $timeTableId)
                    ->select(
                        'academic_periods.name as academic_period_name',
                        'institutions.name as institution_name',
                        'institutions.code as institution_code',
                        'institution_classes.name as class_name',
                        'education_grades.name as educaiton_grade_name',
                        'institution_schedule_intervals.name as institution_schedule_interval_name',
                        'institution_schedule_terms.name as institution_schedule_term_name',
                        'institution_schedule_timetables.name',
                        'institution_schedule_timetables.status',
                        'institution_schedule_interval_id as institution_schedule_interval_id'
                    )
                    ->get()
                    ->toArray();
                    
            return $list;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to export Timetable from DB.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to export Timetable from DB.');
        }
    }

   public function getAllLesson($timeTableId)
    {
        // Retrieve lessons with related models
        $lessons = InstitutionScheduleLessons::with([
                'timetables.scheduleInterval.shift.shiftOption',
                'timetables.institutionClass.grades.educationGrades',
                'timeslots',
                'scheduleLessonDetails',
                'scheduleLessonDetails.institutionScheduleCurriculumLessons',
                'scheduleLessonDetails.institutionScheduleNonCurriculumLessons',
                'scheduleLessonDetails.institutionScheduleCurriculumLessons.institutionSubject.educationSubjects',
                'scheduleLessonDetails.lessonRooms.institutionRooms'
            ])
            ->where('institution_schedule_lessons.institution_schedule_timetable_id', $timeTableId)
            ->get()
            ->toArray();


$timeSlots = [];
$formattedSchedule = [];

// Extract class, grade, and schedule names only once
foreach ($lessons as $item) {
    $className = $item['timetables']['institution_class']['name'];
    $gradeName = $item['timetables']['institution_class']['grades'][0]['education_grades']['name'];
    $scheduleName = $item['timetables']['name'];

    // Store this in the $timeSlots once
    if (empty($timeSlots)) {
        $timeSchedule = [
            'class_name' => $className,
            'grade_name' => $gradeName,
            'schedule_name' => $scheduleName,
        ];
        $timeSlots[] = $timeSchedule;
    }

    $day = '';
    $timeslot = '';

    // Determine the day of the week
    switch ($item['day_of_week']) {
        case 1: $day = 'Monday'; break;
        case 2: $day = 'Tuesday'; break;
        case 3: $day = 'Wednesday'; break;
        case 4: $day = 'Thursday'; break;
        case 5: $day = 'Friday'; break;
        case 6: $day = 'Saturday'; break;
        case 7: $day = 'Sunday'; break;
    }

    // Initialize the start time using DateTime
    $currentStartTime = new \DateTime($item['timetables']['schedule_interval']['shift']['start_time']);

    // Adjust the timeslot dynamically by adding the interval each time
    foreach ($item['schedule_lesson_details'] as $lessonDetail) {
        $intervalMinutes = $item['timeslots']['interval']; // Get the interval in minutes
        $interval = new \DateInterval('PT' . $intervalMinutes . 'M'); // Create DateInterval object

        // Clone the current start time for the end time calculation
        $endTime = clone $currentStartTime;
        $endTime->add($interval); // Add interval to get the end time

        // Format the timeslot start and end times
        $formattedStartTime = $currentStartTime->format('h:i A');
        $formattedEndTime = $endTime->format('h:i A');
        $timeslot = "$formattedStartTime - $formattedEndTime";

        // Update the current start time to be the end time for the next iteration
        $currentStartTime = $endTime;

        // Determine subject and room
        $subject = isset($lessonDetail['institution_schedule_curriculum_lessons']) ? 
            $lessonDetail['institution_schedule_curriculum_lessons']['institution_subject']['name'] : 
            $lessonDetail['institution_schedule_non_curriculum_lessons']['name'];

        $room = $lessonDetail['lesson_rooms']['institution_rooms']['name'];

        // Group schedule by timeslot and day
        $formattedSchedule[$timeslot][$day][] = [
            'subject' => $subject,
            'room' => $room,
        ];
    

// Rearrange to final format for export
$finalArray = [];
foreach ($formattedSchedule as $time => $days) {
    $tempArray = ['timeslot' => $time, 'interval' => $intervalMinutes];
    foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day) {
        if (isset($days[$day])) {
            $daySchedule = [];
            foreach ($days[$day] as $lesson) {
                $daySchedule[] = "{$lesson['subject']}, Room: {$lesson['room']}";
            }
            $tempArray[$day] = implode(' | ', $daySchedule);
        } else {
            $tempArray[$day] = ''; // No lessons for this day
        }
    }
    $finalArray[] = $tempArray;
}
}
}

// Merge class info and schedule for final export structure
$record = array_merge($timeSlots, $finalArray);

return $record;



}



}