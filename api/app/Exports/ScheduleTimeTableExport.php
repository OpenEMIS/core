<?php

namespace App\Exports;

use App\Models\InstitutionScheduleTimetables;
use App\Models\InstitutionScheduleLessons;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ScheduleTimeTableExport
{
    private array $params;

    public function __construct($params)
    {
        $this->params = $params;
    }

    public function build(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $options     = $this->params;
        $timeTableId = $options['timetable_id'];

        $lessons = InstitutionScheduleLessons::with([
            'timetables.scheduleInterval.shift.shiftOption',
            'timetables.institutionClass.grades.educationGrades',
            'timeslots',
            'scheduleLessonDetails',
            'scheduleLessonDetails.schedule_curriculum_lesson',
            'scheduleLessonDetails.schedule_non_curriculum_lesson',
            'scheduleLessonDetails.schedule_curriculum_lesson.institution_subject.educationSubjects',
            'scheduleLessonDetails.schedule_lesson_room.institution_room',
        ])
            ->where('institution_schedule_lessons.institution_schedule_timetable_id', $timeTableId)
            ->get()
            ->toArray();

        $formattedSchedule        = [];
        $classInfo                = [];
        $previousInstitutionTimeslot = null;

        foreach ($lessons as $key => $item) {
            $className      = $item['timetables']['institution_class']['name'];
            $gradeName      = $item['timetables']['institution_class']['grades'][0]['education_grades']['name'];
            $timetablesName = $item['timetables']['name'];

            if (empty($classInfo)) {
                $classInfo = [
                    'grade'    => $gradeName,
                    'class'    => $className,
                    'schedule' => $timetablesName,
                ];
            }

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

            if ($key == 0) {
                $prevDay = $day;
            }

            if ($key == 0 || $prevDay != $day) {
                $shiftStartTime = new \DateTime($item['timetables']['schedule_interval']['shift']['start_time']);
            }
            $prevDay = $day;

            if (empty($item['schedule_lesson_details']) && $prevDay == $day) {
                $intervalMinutes     = $item['timeslots']['interval'];
                $institutionTimeslot = $item['institution_schedule_timeslot_id'];
                if ($institutionTimeslot !== $previousInstitutionTimeslot) {
                    $endTime            = clone $shiftStartTime;
                    $endTime->add(new \DateInterval('PT' . $intervalMinutes . 'M'));
                    $formattedStartTime = $shiftStartTime->format('h:i A');
                    $formattedEndTime   = $endTime->format('h:i A');
                    $timeslot           = "$formattedStartTime - $formattedEndTime";
                    $shiftStartTime     = $endTime;
                    $previousInstitutionTimeslot = $institutionTimeslot;
                }
            }

            foreach ($item['schedule_lesson_details'] as $lessonDetail) {
                $intervalMinutes     = $item['timeslots']['interval'];
                $institutionTimeslot = $item['institution_schedule_timeslot_id'];
                if ($institutionTimeslot !== $previousInstitutionTimeslot) {
                    $endTime            = clone $shiftStartTime;
                    $endTime->add(new \DateInterval('PT' . $intervalMinutes . 'M'));
                    $formattedStartTime = $shiftStartTime->format('h:i A');
                    $formattedEndTime   = $endTime->format('h:i A');
                    $timeslot           = "$formattedStartTime - $formattedEndTime";
                    $shiftStartTime     = $endTime;
                    $previousInstitutionTimeslot = $institutionTimeslot;
                }

                $subject = isset($lessonDetail['schedule_curriculum_lesson'])
                    ? $lessonDetail['schedule_curriculum_lesson']['institution_subject']['name']
                    : $lessonDetail['schedule_non_curriculum_lesson']['name'];

                $room = isset($lessonDetail['schedule_lesson_room']['institution_room']['name'])
                    ? $lessonDetail['schedule_lesson_room']['institution_room']['name']
                    : '';

                if (!isset($formattedSchedule[$timeslot][$day])) {
                    $formattedSchedule[$timeslot][$day] = [];
                }
                $formattedSchedule[$timeslot][$day][] = "$subject, Room: $room";
            }
        }

        // Build the data array
        $finalArray   = [];
        $finalArray[] = ['Grade: ' . ($classInfo['grade'] ?? '') . ' | Class: ' . ($classInfo['class'] ?? '') . ' | Schedule: ' . ($classInfo['schedule'] ?? '')];
        $finalArray[] = [];
        $finalArray[] = ['Time', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

        foreach ($formattedSchedule as $time => $days) {
            $row = [$time];
            foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $dayName) {
                $row[] = isset($days[$dayName]) ? implode('||', $days[$dayName]) : '';
            }
            $finalArray[] = $row;
            $finalArray[] = [];
        }

        $sheetRow = 1;
        foreach ($finalArray as $rowData) {
            $sheet->fromArray($rowData, null, 'A' . $sheetRow);
            $sheetRow++;
        }

        return $spreadsheet;
    }
}
