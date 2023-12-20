<?php

namespace App\Repositories;

use App\Http\Controllers\Controller;
use App\Models\InstitutionScheduleCurriculumLessons;
use App\Models\InstitutionScheduleLessonDetails;
use App\Models\InstitutionScheduleLessonRooms;
use App\Models\InstitutionScheduleLessons;
use App\Models\InstitutionScheduleNonCurriculumLessons;
use App\Models\InstitutionScheduleTimeslots;
use App\Models\InstitutionScheduleTimetables;
use App\Models\TextbookConditions;
use App\Models\TextbookDimensions;
use App\Models\Textbooks;
use App\Models\TextbookStatuses;
use App\Models\InstitutionTextbooks;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use JWTAuth;


class ScheduleRepository extends Controller
{

    const CURRICULUM_LESSON = 1;
    const NON_CURRICULUM_LESSON = 2;

    public function deleteTimeTableLessonById($id)
    {
        try {
            InstitutionScheduleCurriculumLessons::where('institution_schedule_lesson_detail_id', $id)->delete();
            InstitutionScheduleNonCurriculumLessons::where('institution_schedule_lesson_detail_id', $id)->delete();
            InstitutionScheduleLessonRooms::where()->delete('institution_schedule_lesson_detail_id', $id);
            InstitutionScheduleLessonDetails::where('id', $id)->delete();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getAllTimeTableLessons($id)
    {
        return InstitutionScheduleLessonDetails::get();
    }


    public function getTimeTableById($id)
    {
        return InstitutionScheduleTimetables::with('academicPeriod', 'scheduleTerm', 'scheduleInterval', 'institutionClass')->where('id', $id)->first();
    }

    public function getLessonsByTimeTableId($id)
    {
        return InstitutionScheduleLessons::with('schedule_lesson_details')->where('institution_schedule_timetable_id', $id)->get();
    }


    public function getLessonTypeOptions($select = false)
    {
        $lessonType = [
            [
                'id' => self::NON_CURRICULUM_LESSON,
                'name' => __('Non Curriculum Lesson'),
                'title' => __('Non Curriculum')
            ],
            [
                'id' => self::CURRICULUM_LESSON,
                'name' => __('Curriculum Lesson'),
                'title' => __('Curriculum')
            ]
        ];

        if ($select) {
            $selectOption = [
                [
                    'id' => 0,
                    'name' => __('-- Select --')
                ]
            ];
            $lessonType = array_merge($selectOption, $lessonType);
        }

        return $lessonType;
    }

    public function getTimeSlotsByIntervalId($intervalId)
    {
        return InstitutionScheduleTimeslots::with('interval.shift')->where('institution_schedule_interval_id', $intervalId)->get();
    }

}