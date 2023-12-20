<?php

namespace App\Http\Controllers;

use App\Models\ConfigItem;
use App\Repositories\ScheduleRepository;
use App\Services\ScheduleService;
use Illuminate\Support\Facades\Log;

class ScheduleController extends Controller
{

    protected $scheduleService;

    public function __construct(ScheduleService $scheduleService) {
        $this->scheduleService = $scheduleService;
    }

    public function deleteTimeTableLessonById($id)
    {
        try {
            $data = $this->scheduleService->deleteTimeTableLessonId($id);
            return $this->sendSuccessResponse("Lesson Id deleted successfully", $data);

        } catch (\Exception $e) {
            Log::error(
                'Failed to delete Lesson',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Something went wrong.',[], 500);
        }
    }

    public function getTimeTableById($id)
    {
        try {
            $data = $this->scheduleService->getTimeTableById($id);
            return $this->sendSuccessResponse("Time table data", $data);

        } catch (\Exception $e) {
            Log::error(
                'Failed to get timetable data',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Something went wrong.',[], 500);
        }
    }

    public function getLessonsByTimeTableId($id)
    {
        try {
            $data = $this->scheduleService->getLessonsByTimeTableId($id);
            return $this->sendSuccessResponse("Time table lessons", $data);

        } catch (\Exception $e) {
            Log::error(
                'Failed to get timetable data',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Something went wrong.',[], 500);
        }
    }

    public function getLessonType($id)
    {
        $scheduleRepository = new ScheduleRepository();
        $data = $scheduleRepository->getLessonTypeOptions(true);

        return $this->sendSuccessResponse("Time table lessons", $data);
    }

    public function getTimeTableStatus()
    {
        $status = [
            [
                'id' => 1,
                'name' => 'Draft'
            ],
            [
                'id' => 2,
                'name' => 'Published'
            ]
        ];

        return $this->sendSuccessResponse("Time table lessons", $status);
    }

    public function workingDayOfWeek()
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

        $firstDayOfWeek = ConfigItem::where('code','first_day_of_week')->first();
        $daysPerWeek = ConfigItem::where('code','days_per_week')->first();

        if ($firstDayOfWeek) {
            $firstDayOfWeek = $firstDayOfWeek->value ?? $firstDayOfWeek->default_value;
        }

        if ($daysPerWeek) {
            $daysPerWeek = $daysPerWeek->value ?? $daysPerWeek->default_value;
        }


        $lastDayIndex = ($firstDayOfWeek + $daysPerWeek - 1) % 7;
        $week = [];
        for ($i = 0; $i < $daysPerWeek; $i++) {
            $week[] = $weekdays[$firstDayOfWeek++];
            $firstDayOfWeek = $firstDayOfWeek % 7;
        }

        return $this->sendSuccessResponse("Working day of weeks", $week);
    }

    public function getTimeSlotsByIntervalId($intervalId)
    {
        try {
            $timeSlots = $this->scheduleService->getTimeSlotsByIntervalId($intervalId);
            return $this->sendSuccessResponse("Time slots", $timeSlots);
        } catch (\Exception $e) {
            Log::error(
                'Failed to Time slots',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Something went wrong.',[], 500);
        }

    }

}