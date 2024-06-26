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

    public function deleteTimeTableLessonById($institutionId, $id)
    {
        try {

            $checkPermission = checkPermission(['Institutions', 'ScheduleTimetableOverview', 'remove'], ['institution_id' => $institutionId]);

            if(!$checkPermission){
                return $this->sendAuthorizationErrorResponse();
            }

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
            return $this->sendSuccessResponse("Time table lessons list", $data);

        } catch (\Exception $e) {
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

        return $this->sendSuccessResponse("Time table lessons type list", $data);
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

        return $this->sendSuccessResponse("Time table status list", $status);
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
            return $this->sendSuccessResponse("Time slots list", $timeSlots);
        } catch (\Exception $e) {
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

            $checkPermission = checkPermission(['Institutions', 'ScheduleTimetableOverview', 'add'], ['institution_id' => $request->institution_id]);

            if(!$checkPermission){
                return $this->sendAuthorizationErrorResponse();
            }

            $data = $request->all();
            $result = $this->scheduleService->addLesson($data);
            if ($result['status']) {
                return $this->sendSuccessResponse($result['msg'], []);
            }
            return $this->sendErrorResponse($result['msg'], [], 403);
        } catch (\Exception $e) {
            Log::error(
                'Failed to add lesson.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Something went wrong.',[], 500);
        }

    }



    //POCOR-8295 start...

    /**
     * @OA\Get(
     *     path="/api/v1/institutions/schedule-timetables",
     *     summary="Get schedule timetables for institutions",
     *     tags={"Institution time table"},
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         description="Order of the results",
     *         required=false,
     *         @OA\Schema(type="string", example="id")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", example=2)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Successful.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="P1A"),
     *                         @OA\Property(property="status", type="integer", example=1),
     *                         @OA\Property(property="academic_period_id", type="integer", example=29),
     *                         @OA\Property(property="academic_period_name", type="string", example="2020"),
     *                         @OA\Property(property="institution_class_id", type="integer", example=496),
     *                         @OA\Property(property="institution_class_name", type="string", example="Primary 1-A"),
     *                         @OA\Property(property="institution_id", type="integer", example=6),
     *                         @OA\Property(property="institution_code", type="string", example="P1002"),
     *                         @OA\Property(property="institution_name", type="string", example="Avory Primary School"),
     *                         @OA\Property(property="institution_schedule_interval_id", type="integer", example=1),
     *                         @OA\Property(property="institution_schedule_interval_name", type="string", example="APS Morning Shift"),
     *                         @OA\Property(property="institution_schedule_term_id", type="integer", example=1),
     *                         @OA\Property(property="institution_schedule_term_name", type="string", example="Semester 1"),
     *                         @OA\Property(property="modified_user_id", type="integer", example=null),
     *                         @OA\Property(property="modified", type="string", example=null),
     *                         @OA\Property(property="created_user_id", type="integer", example=2),
     *                         @OA\Property(property="created", type="string", example="2020-02-11 07:36:56")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
    public function getScheduleTimetables(Request $request)
    {
        try {
            $params = $request->all();
            $data = $this->scheduleService->getScheduleTimetables($params);

            if (!empty($data)) {
                return $this->sendSuccessResponse("Schedule timetables found.", $data);
            } else {
                return $this->sendErrorResponse("Schedule timetables not found.");
            }
            
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch schedule timetables.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Failed to fetch schedule timetables.',[], 500);
        }

    }



    /**
     * @OA\Get(
     *     path="/api/v1/institutions/{institutionId}/schedule-timetables",
     *     summary="Get schedule timetables for institutions",
     *     tags={"Institution time table"},
     *     @OA\Parameter(
     *         name="institutionId",
     *         in="path",
     *         description="Institution Id",
     *         required=true,
     *         @OA\Schema(type="string", example="6")
     *     )
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         description="Order of the results",
     *         required=false,
     *         @OA\Schema(type="string", example="id")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", example=2)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Successful.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="P1A"),
     *                         @OA\Property(property="status", type="integer", example=1),
     *                         @OA\Property(property="academic_period_id", type="integer", example=29),
     *                         @OA\Property(property="academic_period_name", type="string", example="2020"),
     *                         @OA\Property(property="institution_class_id", type="integer", example=496),
     *                         @OA\Property(property="institution_class_name", type="string", example="Primary 1-A"),
     *                         @OA\Property(property="institution_id", type="integer", example=6),
     *                         @OA\Property(property="institution_code", type="string", example="P1002"),
     *                         @OA\Property(property="institution_name", type="string", example="Avory Primary School"),
     *                         @OA\Property(property="institution_schedule_interval_id", type="integer", example=1),
     *                         @OA\Property(property="institution_schedule_interval_name", type="string", example="APS Morning Shift"),
     *                         @OA\Property(property="institution_schedule_term_id", type="integer", example=1),
     *                         @OA\Property(property="institution_schedule_term_name", type="string", example="Semester 1"),
     *                         @OA\Property(property="modified_user_id", type="integer", example=null),
     *                         @OA\Property(property="modified", type="string", example=null),
     *                         @OA\Property(property="created_user_id", type="integer", example=2),
     *                         @OA\Property(property="created", type="string", example="2020-02-11 07:36:56")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
    public function getScheduleTimetablesViaInstitutionId(Request $request, $institutionId)
    {
        try {
            $params = $request->all();
            $data = $this->scheduleService->getScheduleTimetablesViaInstitutionId($params, $institutionId);

            if (!empty($data)) {
                return $this->sendSuccessResponse("Schedule timetables found.", $data);
            } else {
                return $this->sendErrorResponse("Schedule timetables not found.");
            }
            
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch schedule timetables.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Failed to fetch schedule timetables.',[], 500);
        }

    }



    /**
     * @OA\Get(
     *     path="/api/v1/institutions/schedule-timetables/{scheduleTimeTableId}",
     *     summary="Get schedule timetables data for institutions",
     *     tags={"Institution time table"},
     *     @OA\Parameter(
     *         name="scheduleTimeTableId",
     *         in="path",
     *         description="Schedule Time Table Id",
     *         required=true,
     *         @OA\Schema(type="string", example="1")
     *     )
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         description="Order of the results",
     *         required=false,
     *         @OA\Schema(type="string", example="id")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", example=2)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Successful.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Successful."),
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="P1A"),
     *                         @OA\Property(property="status", type="integer", example=1),
     *                         @OA\Property(property="academic_period_id", type="integer", example=29),
     *                         @OA\Property(property="academic_period_name", type="string", example="2020"),
     *                         @OA\Property(property="institution_class_id", type="integer", example=496),
     *                         @OA\Property(property="institution_class_name", type="string", example="Primary 1-A"),
     *                         @OA\Property(property="institution_id", type="integer", example=6),
     *                         @OA\Property(property="institution_code", type="string", example="P1002"),
     *                         @OA\Property(property="institution_name", type="string", example="Avory Primary School"),
     *                         @OA\Property(property="institution_schedule_interval_id", type="integer", example=1),
     *                         @OA\Property(property="institution_schedule_interval_name", type="string", example="APS Morning Shift"),
     *                         @OA\Property(property="institution_schedule_term_id", type="integer", example=1),
     *                         @OA\Property(property="institution_schedule_term_name", type="string", example="Semester 1"),
     *                         @OA\Property(property="modified_user_id", type="integer", example=null),
     *                         @OA\Property(property="modified", type="string", example=null),
     *                         @OA\Property(property="created_user_id", type="integer", example=2),
     *                         @OA\Property(property="created", type="string", example="2020-02-11 07:36:56")
     *                     )
     *                 )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
    public function getScheduleTimetableData($scheduleTimetableId)
    {
        try {
            $data = $this->scheduleService->getScheduleTimetableData($scheduleTimetableId);

            if (!empty($data)) {
                return $this->sendSuccessResponse("Schedule timetable data found.", $data);
            } else {
                return $this->sendErrorResponse("Schedule timetable data not found.");
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch schedule timetable data.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Failed to fetch schedule timetables data',[], 500);
        }

    }

    //POCOR-8295 end...

}