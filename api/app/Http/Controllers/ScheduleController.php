<?php

namespace App\Http\Controllers;

use App\Models\ConfigItem;
use App\Repositories\ScheduleRepository;
use App\Services\ScheduleService;
use Illuminate\Http\Request;
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
            $data = $this->scheduleService->deleteTimeTableLessonById($id);
            return $this->sendSuccessResponse("Lesson Id deleted successfully", []);

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
            dd($e);
            Log::error(
                'Failed to get timetable data',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Something went wrong.',[], 500);
        }
    }

    public function getLessonType()
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

        return $this->sendSuccessResponse("Time table status", $status);
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
            $daysPerWeek = !empty($daysPerWeek->value) ? $daysPerWeek->value :  $daysPerWeek->default_value;
        } else {
            $daysPerWeek = 0;
        }

        

        $lastDayIndex = ($firstDayOfWeek + $daysPerWeek - 1) % 7;
        
        $dayOfWeek = [];
        for ($i = 0; $i < $daysPerWeek; $i++) {
            $dayOfWeek[] = [
                'day_of_week' => $i + 1,
                'day' => $weekdays[$firstDayOfWeek++]
            ];
            $firstDayOfWeek = $firstDayOfWeek % 7;
        }

        return $this->sendSuccessResponse("Working day of weeks", $dayOfWeek);
    }

    public function getTimeSlotsByIntervalId($intervalId)
    {
        try {
            $timeSlots = $this->scheduleService->getTimeSlotsByIntervalId($intervalId);
            return $this->sendSuccessResponse("Time slots", $timeSlots);
        } catch (\Exception $e) {
            dd($e);
            Log::error(
                'Failed to Time slots',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Something went wrong.',[], 500);
        }

    }

    public function addLesson(Request $request)
    {
        try {
            $data = $request->all();
            $result = $this->scheduleService->addLesson($data);
            if ($result['status']) {
                return $this->sendSuccessResponse($result['msg'], []);
            }
            return $this->sendErrorResponse($result['msg'], [], 403);
        } catch (\Exception $e) {
            dd($e);
            Log::error(
                'Failed to add lesson.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Something went wrong.',[], 500);
        }

    }

}