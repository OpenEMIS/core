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
        echo "<pre>"; print_r($record); die;
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

        /*$record = [];
        foreach ($lessons as $key => $lesson) {
            $record[$key]['id'] = $lesson['id'] ?? null;
            $record[$key]['day'] = $lesson['day_of_week'] ?? null;
            $record[$key]['name'] = $lesson['timeslots']['interval'] ;
            
            // Check if schedule_term exists in the lesson data
            $record[$key]['schedule_term'] = $lesson['scheduleLessonDetails']['schedule_term']['name'] ?? 'Unknown Term';
        }*/

        $formattedSchedule = [];

$timeSlots = [];
foreach ($lessons as $item) {
    $day = '';
    $timeslot = '';
    
    // Determine the day of the week
    switch ($item['day_of_week']) {
        case 1: $day = 'mon'; break;
        case 2: $day = 'tue'; break;
        case 3: $day = 'wed'; break;
        case 4: $day = 'thu'; break;
        case 5: $day = 'fri'; break;
    }

    // Determine the time slot
    $startTime = date('h:i A', strtotime($item['timetables']['schedule_interval']['shift']['start_time']));
    $endTime = date('h:i A', strtotime($item['timetables']['schedule_interval']['shift']['end_time']));
    $timeslot = "$startTime - " . date('h:i A', strtotime("+{$item['timeslots']['interval']} minutes", strtotime($item['timetables']['schedule_interval']['shift']['start_time'])));

    // Add lesson details
    foreach ($item['schedule_lesson_details'] as $lessonDetail) {
        $subject = isset($lessonDetail['institution_schedule_curriculum_lessons']) ? 
            $lessonDetail['institution_schedule_curriculum_lessons']['institution_subject']['name'] : 
            $lessonDetail['institution_schedule_non_curriculum_lessons']['name'];
        
        $room = $lessonDetail['lesson_rooms']['institution_rooms']['name'];
        
        $formattedSchedule[$timeslot][] = [
            'subject' => $subject,
            'room' => $room,
            'day' => $day,
            'timeslot' => $timeslot,
        ];
    }
}

// Rearranging to the final required format
$finalArray = [];
foreach ($formattedSchedule as $time => $lessons) {
    $tempArray = [];
    foreach ($lessons as $lesson) {
        $tempArray[] = $lesson;
    }
    $finalArray[] = $tempArray;
}

echo "<pre>"; print_r($finalArray); 
}



}