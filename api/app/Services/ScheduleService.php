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
            $this->scheduleRepository->deleteTimeTableLessonById($id);
            $data = $this->scheduleRepository->getAllTimeTableLessons();
            return $data;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Lesson Id not found');
        }
    }

    public function getTimeTableById($id)
    {
        return $this->scheduleRepository->getTimeTableById($id);
    }

    public function getLessonsByTimeTableId($id)
    {
        return $this->scheduleRepository->getLessonsByTimeTableId($id);
    }

    public function getTimeSlotsByIntervalId($intervalId)
    {
        $timeSlots = $this->scheduleRepository->getTimeSlotsByIntervalId($intervalId)->map(function($item){

            $startTime = $item->interval->shift->start_time;

            $carbon = Carbon::createFromFormat('H:i:s', $startTime);

            $carbon->addMinutes($item->interval);

            $endTime = $carbon->format('H:i:s');

            return [
                'id' => $item->id,
                'institution_schedule_interval_id' => $item->institution_schedule_interval_id,
                'interval' => $item->interval,
                'order' => $item->order,
                'start_time' => $item->interval->shift->start_time,
                'end_time' => $endTime
            ];
        });

        return $timeSlots;
    }
}