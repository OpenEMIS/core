<?php

namespace App\Exports;

use App\Models\InstitutionScheduleTimetables;
use App\Models\InstitutionScheduleLessons;
use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ScheduleTimeTableExport implements FromCollection, WithHeadings
{
    public function __construct($params)
    {
        $this->params = $params;
    }
    
    /**
    * @return \Illuminate\Support\Collection
    */
    public function array(): array
    {
        return $this->params;
    }


    public function headings(): array
    {
        return [
            'Timetable Name',
            
        ];
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    /*public function collectionbkp()
    {
        $options = $this->params;
        $timeTableId = $options['timetable_id'];
        
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

        // Initialize arrays
        $formattedSchedule = [];
        $classInfo = [];

        foreach ($lessons as $item) {
            $className = $item['timetables']['institution_class']['name'];
            $gradeName = $item['timetables']['institution_class']['grades'][0]['education_grades']['name'];
            $timetablesName = $item['timetables']['name'];
            
            if (empty($classInfo)) {
                $classInfo = [
                    'grade' => $gradeName,
                    'class' => $className,
                    'schedule' => $timetablesName,
                ];
            }

            // Determine the day of the week
            $day = '';
            switch ($item['day_of_week']) {
                case 1: $day = 'Mon'; break;
                case 2: $day = 'Tue'; break;
                case 3: $day = 'Wed'; break;
                case 4: $day = 'Thu'; break;
                case 5: $day = 'Fri'; break;
                case 6: $day = 'Sat'; break;
                case 7: $day = 'Sun'; break;
            }

            // Initialize the start time using DateTime
            $currentStartTime = new \DateTime($item['timetables']['schedule_interval']['shift']['start_time']);

            foreach ($item['schedule_lesson_details'] as $lessonDetail) {
                $intervalMinutes = $item['timeslots']['interval'];
                $interval = new \DateInterval('PT' . $intervalMinutes . 'M');

                // Calculate end time
                $endTime = clone $currentStartTime;
                $endTime->add($interval);
                $formattedStartTime = $currentStartTime->format('h:i A');
                $formattedEndTime = $endTime->format('h:i A');
                $timeslot = "$formattedStartTime - $formattedEndTime";

                $currentStartTime = $endTime; // Update current start time

                $subject = isset($lessonDetail['institution_schedule_curriculum_lessons']) ? 
                    $lessonDetail['institution_schedule_curriculum_lessons']['institution_subject']['name'] : 
                    $lessonDetail['institution_schedule_non_curriculum_lessons']['name'];

                $room = $lessonDetail['lesson_rooms']['institution_rooms']['name'];

                // Store the subject and room for the respective timeslot and day
                // Initialize if it doesn't exist
                if (!isset($formattedSchedule[$timeslot][$day])) {
                    $formattedSchedule[$timeslot][$day] = [];
                }
                // Append to the existing list
                $formattedSchedule[$timeslot][$day][] = "$subject, Room: $room";
            }
        }

        // Prepare final array for export
        $finalArray = [];

        // Add class and grade information
        $finalArray[] = ['Grade: ' . $classInfo['grade'] . ' | Class: ' . $classInfo['class']. ' | Schedule: ' . $classInfo['schedule']];
        $finalArray[] = []; // Add an empty row

        // Add header row
        $finalArray[] = ['Time', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        
        // Populate the schedule
        foreach ($formattedSchedule as $time => $days) {
            $row = [$time];
            foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $day) {
                // Join subjects with line breaks, if they exist
                $row[] = isset($days[$day]) ? implode('<br>', $days[$day]) : ''; 
            }
            $finalArray[] = $row;
            $finalArray[] = []; // Add an empty row for spacing
        }

        return collect($finalArray); // Return as a Collection
    }*/

    public function collection()
    {
        $options = $this->params;
        $timeTableId = $options['timetable_id'];

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

        // Initialize arrays
        $formattedSchedule = [];
        $classInfo = [];

        // Initialize variable to hold previous institution timeslot ID
        $previousInstitutionTimeslot = null;

        foreach ($lessons as $key => $item) {
            $className = $item['timetables']['institution_class']['name'];
            $gradeName = $item['timetables']['institution_class']['grades'][0]['education_grades']['name'];
            $timetablesName = $item['timetables']['name'];

            if (empty($classInfo)) {
                $classInfo = [
                    'grade' => $gradeName,
                    'class' => $className,
                    'schedule' => $timetablesName,
                ];
            }

            // Determine the day of the week
            $day = '';
            switch ($item['day_of_week']) {
                case 1: $day = 'Mon'; break;
                case 2: $day = 'Tue'; break;
                case 3: $day = 'Wed'; break;
                case 4: $day = 'Thu'; break;
                case 5: $day = 'Fri'; break;
                case 6: $day = 'Sat'; break;
                case 7: $day = 'Sun'; break;
            }

            // Initialize the start time using DateTime
            //if($key == 0){
                $shiftStartTime = new \DateTime($item['timetables']['schedule_interval']['shift']['start_time']);
            //}
           //echo "<pre>"; print_r($shiftStartTime); 
            // Loop through schedule lesson details

            foreach ($item['schedule_lesson_details'] as $lessonDetail) {

                $intervalMinutes = $item['timeslots']['interval'];
                $institutionTimeslot = $item['institution_schedule_timeslot_id'];

                // Compare with previous timeslot ID
                if ($institutionTimeslot !== $previousInstitutionTimeslot) {
                    // Only calculate start and end times if the timeslot has changed
                    $endTime = clone $shiftStartTime;
                    $endTime->add(new \DateInterval('PT' . $intervalMinutes . 'M'));
                    $formattedStartTime = $shiftStartTime->format('h:i A');
                    $formattedEndTime = $endTime->format('h:i A');
                    $timeslot = "$formattedStartTime - $formattedEndTime";

                    // Update the current start time for the next lesson
                    $shiftStartTime = $endTime; // Set current start time to end time for the next iteration

                    // Reset the previous institution timeslot to the current one
                    $previousInstitutionTimeslot = $institutionTimeslot;
                }
 
                // Prepare subject and room info
                $subject = isset($lessonDetail['institution_schedule_curriculum_lessons']) ? 
                    $lessonDetail['institution_schedule_curriculum_lessons']['institution_subject']['name'] : 
                    $lessonDetail['institution_schedule_non_curriculum_lessons']['name'];

                $room = $lessonDetail['lesson_rooms']['institution_rooms']['name'];

                // Initialize if it doesn't exist
                if (!isset($formattedSchedule[$timeslot][$day])) {
                    $formattedSchedule[$timeslot][$day] = [];
                }

                // Append the subject and room
                $formattedSchedule[$timeslot][$day][] = "$subject, Room: $room";
            }
            
        }

        // Prepare final array for export
        $finalArray = [];

        // Add class and grade information
        $finalArray[] = ['Grade: ' . $classInfo['grade'] . ' | Class: ' . $classInfo['class'] . ' | Schedule: ' . $classInfo['schedule']];
        $finalArray[] = []; // Add an empty row

        // Add header row
        $finalArray[] = ['Time', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

        foreach ($formattedSchedule as $time => $days) {
            $row = [$time];
            foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $day) {
                // Join subjects with line breaks, if they exist
                $row[] = isset($days[$day]) ? implode('||', $days[$day]) : '';
            }
            $finalArray[] = $row;
            $finalArray[] = []; // Add an empty row for spacing
        }

        return collect($finalArray); // Return as a Collection
    }


}
