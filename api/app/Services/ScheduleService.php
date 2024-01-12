<?php

namespace App\Services;

use App\Http\Controllers\Controller;
use App\Repositories\ScheduleRepository;
use Carbon\Carbon;
use JWTAuth;
use Illuminate\Support\Facades\Log;

class ScheduleService extends Controller
{

    protected $scheduleRepository;

    public function __construct(ScheduleRepository $scheduleRepository) {
        $this->scheduleRepository = $scheduleRepository;
    }

    public function deleteTimeTableLessonById($id)
    {
        try {
            return $this->scheduleRepository->deleteTimeTableLessonById($id);
        } catch (\Exception $e) {
           throw $e;
        }
    }

    public function getTimeTableById($id)
    {
        return $this->scheduleRepository->getTimeTableById($id);
    }

    public function getLessonsByTimeTableId($id)
    {
        $lessons = $this->scheduleRepository->getLessonsByTimeTableId($id)->map(function($item){

            $startTime = $item->timeslots->instituteInterval->shift->start_time;

            $carbon = Carbon::createFromFormat('H:i:s', $startTime);

            $carbon->addMinutes($item->timeslots->interval);

            $endTime = $carbon->format('H:i:s');

            $item->timeslots->start_time = $startTime;
            $item->timeslots->end_time = $endTime;

            unset($item['timeslots']['instituteInterval']);

            return $item;
        });

        return $lessons;
    }

    public function getTimeSlotsByIntervalId($intervalId)
    {
        $timeSlots = $this->scheduleRepository->getTimeSlotsByIntervalId($intervalId)->map(function($item){

            $startTime = $item->instituteInterval->shift->start_time;

            $carbon = Carbon::createFromFormat('H:i:s', $startTime);

            $carbon->addMinutes($item->interval);

            $endTime = $carbon->format('H:i:s');

            return [
                'id' => $item->id,
                'institution_schedule_interval_id' => $item->institution_schedule_interval_id,
                'interval' => $item->interval,
                'order' => $item->order,
                'start_time' => $item->instituteInterval->shift->start_time,
                'end_time' => $endTime
            ];
        });

        return $timeSlots;
    }

    public function addLesson($data)
    {
        try {
            $lesson = $this->scheduleRepository->addLesson($data);
            return $lesson;
        } catch (\Exception $e) {
           throw $e;
        }
    }
}