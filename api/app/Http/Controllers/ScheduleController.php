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

    /**
     * @OA\Delete(
     *      path="/api/v4/institutions/{institutionId}/schedules/timetables/lessons/{id}",
     *      summary="Delete lesson by id",
     *      description="Delete lesson by id",
     *      tags={"Institution time table"},
     *      @OA\Parameter(
     *         name="institutionId",
     *         in="path",
     *         required=true,
     *         description="Id of institution",
     *         @OA\Schema(type="integer", example=6)
     *      ),
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Id of lesson",
     *         @OA\Schema(type="integer", example=6)
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful.",
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items()
     *                  ),
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Unsuccessful.",
     *      )
     * )
     */
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

    /**
     * @OA\Get(
     *      path="/api/v4/schedules/timetables/{id}",
     *      summary="Get detail of time table by id",
     *      description="Get detail of time table by id",
     *      tags={"Institution time table"},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Timetable id",
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful.",
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="P1A"),
     *                     @OA\Property(property="status", type="integer", example=1),
     *                     @OA\Property(property="academic_period_id", type="integer", example=29),
     *                     @OA\Property(property="institution_class_id", type="integer", example=129),
     *                     @OA\Property(property="institution_id", type="integer", example=6),
     *                     @OA\Property(property="institution_schedule_interval_id", type="integer", example=1),
     *                     @OA\Property(property="institution_schedule_term_id", type="integer", example=1),
     *                     @OA\Property(property="modified_user_id", type="integer", example=1),
     *                     @OA\Property(property="modified", type="string", example="2020-02-11 07:36:56"),
     *                     @OA\Property(property="created_user_id", type="integer", example=2),
     *                     @OA\Property(property="created", type="string", example="2020-02-11 07:36:56"),
     *                     @OA\Property(property="academic_period", type="object",
     *                         @OA\Property(property="id", type="integer", example=29),
     *                         @OA\Property(property="code", type="string", example="YR2020"),
     *                         @OA\Property(property="name", type="string", example="2020"),
     *                         @OA\Property(property="start_date", type="string", example="2020-01-01"),
     *                         @OA\Property(property="start_year", type="integer", example=2020),
     *                         @OA\Property(property="end_date", type="string", example="2020-12-31"),
     *                         @OA\Property(property="end_year", type="integer", example=2020),
     *                         @OA\Property(property="school_days", type="integer", example=0),
     *                         @OA\Property(property="current", type="integer", example=0),
     *                         @OA\Property(property="editable", type="integer", example=1),
     *                         @OA\Property(property="parent_id", type="integer", example=9),
     *                         @OA\Property(property="lft", type="integer", example=26),
     *                         @OA\Property(property="rght", type="integer", example=27),
     *                         @OA\Property(property="academic_period_level_id", type="integer", example=1),
     *                         @OA\Property(property="order", type="integer", example=6),
     *                         @OA\Property(property="visible", type="integer", example=1),
     *                         @OA\Property(property="modified_user_id", type="integer", example="null"),
     *                         @OA\Property(property="modified", type="string", example="null"),
     *                         @OA\Property(property="created_user_id", type="integer", example=2),
     *                         @OA\Property(property="created", type="string", example="2020-01-08 01:49:42")
     *                     ),
     *                     @OA\Property(property="schedule_term", type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Kindergarten 1-A"),
     *                         @OA\Property(property="class_number", type="integer", example=1),
     *                         @OA\Property(property="capacity", type="integer", example=100),
     *                         @OA\Property(property="total_male_students", type="integer", example=10),
     *                         @OA\Property(property="total_female_students", type="integer", example=16),
     *                         @OA\Property(property="staff_id", type="integer", example=0),
     *                         @OA\Property(property="institution_shift_id", type="integer", example=1),
     *                         @OA\Property(property="institution_id", type="integer", example=1),
     *                         @OA\Property(property="institution_unit_id", type="integer", example="null"),
     *                         @OA\Property(property="institution_course_id", type="integer", example="null"),
     *                         @OA\Property(property="academic_period_id", type="integer", example=10),
     *                         @OA\Property(property="modified_user_id", type="integer", example="null"),
     *                         @OA\Property(property="modified", type="string", example="null"),
     *                         @OA\Property(property="created_user_id", type="integer", example=2),
     *                         @OA\Property(property="created", type="string", example="2018-03-28 16:35:19")
     *                     ),
     *                     @OA\Property(property="schedule_interval", type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="APS Morning Shift"),
     *                         @OA\Property(property="academic_period_id", type="integer", example=29),
     *                         @OA\Property(property="institution_id", type="integer", example=6),
     *                         @OA\Property(property="institution_shift_id", type="integer", example=172),
     *                         @OA\Property(property="modified_user_id", type="integer", example=2),
     *                         @OA\Property(property="modified", type="string", example="2020-02-11 07:34:13"),
     *                         @OA\Property(property="created_user_id", type="integer", example=2),
     *                         @OA\Property(property="created", type="string", example="2020-02-11 07:30:53")
     *                     ),
     *                     @OA\Property(property="institution_class", type="object",
     *                         @OA\Property(property="id", type="integer", example=496),
     *                         @OA\Property(property="name", type="string", example="Primary 1-A"),
     *                         @OA\Property(property="class_number", type="integer", example=1),
     *                         @OA\Property(property="capacity", type="integer", example=50),
     *                         @OA\Property(property="total_male_students", type="integer", example=12),
     *                         @OA\Property(property="total_female_students", type="integer", example=25),
     *                         @OA\Property(property="staff_id", type="integer", example=8815),
     *                         @OA\Property(property="institution_shift_id", type="integer", example=172),
     *                         @OA\Property(property="institution_id", type="integer", example=6),
     *                         @OA\Property(property="institution_unit_id", type="integer", example="null"),
     *                         @OA\Property(property="institution_course_id", type="integer", example="null"),
     *                         @OA\Property(property="academic_period_id", type="integer", example=29),
     *                         @OA\Property(property="modified_user_id", type="integer", example=1),
     *                         @OA\Property(property="modified", type="string", example="2021-08-12 04:48:11"),
     *                         @OA\Property(property="created_user_id", type="integer", example=8805),
     *                         @OA\Property(property="created", type="string", example="2020-01-08 02:28:30")
     *                     )
     *                 )
     *             )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Unsuccessful.",
     *      )
     * )
     */
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

    /**
     * @OA\Get(
     *      path="/api/v4/schedules/timetables/{id}/lessons",
     *      summary="Get list of lesson by timetable id",
     *      description="Get list of lesson by timetable id",
     *      tags={"Institution time table"},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Timetable id",
     *         @OA\Schema(type="integer", example="1")
     *      ),
     *      @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
     *     ),
     *      @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Limit",
     *         @OA\Schema(type="integer", example="10")
     *     ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful.",
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="day_of_week", type="integer", example="1"),
     *                     @OA\Property(property="institution_schedule_timeslot_id", type="integer", example=1),
     *                     @OA\Property(property="institution_schedule_timetable_id", type="integer", example=3),
     *                     @OA\Property(property="modified_user_id", type="integer", example="null"),
     *                     @OA\Property(property="modified", type="string", example="null"),
     *                     @OA\Property(property="created_user_id", type="integer", example=2),
     *                     @OA\Property(property="created", type="string", example="2020-01-08 01:49:42"),
     *                     @OA\Property(property="schedule_lesson_details", type="object",
     *                         @OA\Property(property="id", type="integer", example=29),
     *                         @OA\Property(property="lesson_type", type="integer", example=1),
     *                         @OA\Property(property="day_of_week", type="integer", example=1),
     *                         @OA\Property(property="institution_schedule_timeslot_id", type="integer", example=1),
     *                         @OA\Property(property="institution_schedule_timetable_id", type="integer", example=2),
     *                         @OA\Property(property="modified_user_id", type="integer", example="null"),
     *                         @OA\Property(property="modified", type="string", example="null"),
     *                         @OA\Property(property="created_user_id", type="integer", example=2),
     *                         @OA\Property(property="created", type="string", example="2020-01-08 01:49:42")
     *                     ),
     *                     @OA\Property(property="timeslots", type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="interval", type="integer", example=1),
     *                         @OA\Property(property="order", type="integer", example=1),
     *                         @OA\Property(property="institution_schedule_interval_id", type="integer", example=1),
     *                         @OA\Property(property="start_time", type="string", example="07:00:00"),
     *                         @OA\Property(property="end_time", type="string", example="07:30:00"),
     *                     ),
     *                 )
     *             )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Unsuccessful.",
     *      )
     * )
     */
    public function getLessonsByTimeTableId($id, Request $request)
    {
        try {
            $params = $request->all();
            $data = $this->scheduleService->getLessonsByTimeTableId($id, $params);
            return $this->sendSuccessResponse("Time table lessons list", $data);

        } catch (\Exception $e) {
            Log::error(
                'Failed to get timetable data',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Something went wrong.',[], 500);
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v4/schedules/lessons/types",
     *      summary="Get list of lesson types",
     *      description="Get list of lesson types",
     *      tags={"Institution time table"},
     *      @OA\Response(
     *          response=200,
     *          description="Successful.",
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Curriculum Lesson"),
     *                     @OA\Property(property="title", type="string", example="Curriculum"),
     *                 )
     *             )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Unsuccessful.",
     *      )
     * )
     */
    public function getLessonType()
    {
        $scheduleRepository = new ScheduleRepository();
        $data = $scheduleRepository->getLessonTypeOptions(true);

        return $this->sendSuccessResponse("Time table lessons type list", $data);
    }

    /**
     * @OA\Get(
     *      path="/api/v4/schedules/timetables/statuses",
     *      summary="Get list of statuses",
     *      description="Get list of statuses",
     *      tags={"Institution time table"},
     *      @OA\Response(
     *          response=200,
     *          description="Successful.",
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Draft"),
     *                 )
     *             )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Unsuccessful.",
     *      )
     * )
     */
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

    /**
     * @OA\Get(
     *      path="/api/v4/weekdays",
     *      summary="Get list of weekdays",
     *      description="Get list of weekdays",
     *      tags={"Institution time table"},
     *      @OA\Response(
     *          response=200,
     *          description="Successful.",
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="day_of_week", type="integer", example=1),
     *                     @OA\Property(property="day", type="string", example="monday"),
     *                 )
     *             )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Unsuccessful.",
     *      )
     * )
     */
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


    /**
     * @OA\Get(
     *      path="/api/v4/schedules/timeslots/{intervalId}",
     *      summary="Get list of time slots by interval id",
     *      description="Get list of time slots by interval id",
     *      tags={"Institution time table"},
     *      @OA\Parameter(
     *         name="intervalId",
     *         in="path",
     *         required=true,
     *         description="Interval Id",
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Limit",
     *         @OA\Schema(type="integer", example="10")
     *     ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful.",
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="institution_schedule_interval_id", type="integer", example=1),
     *                     @OA\Property(property="interval", type="integer", example=0),
     *                     @OA\Property(property="order", type="integer", example=1),
     *                     @OA\Property(property="start_time", type="string", example="07:00:00"),
     *                     @OA\Property(property="end_time", type="string", example="07:30:00"),
     *                 )
     *             )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Unsuccessful.",
     *      )
     * )
     */
    public function getTimeSlotsByIntervalId($intervalId, Request $request)
    {
        try {
            $params = $request->all();
            $timeSlots = $this->scheduleService->getTimeSlotsByIntervalId($intervalId, $params);
            return $this->sendSuccessResponse("Time slots list", $timeSlots);
        } catch (\Exception $e) {
            Log::error(
                'Failed to Time slots',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Something went wrong.',[], 500);
        }

    }

    /**
     * @OA\Post(
     *      path="/api/v4/schedules/timetables/lessons",
     *      summary="Add Lesson",
     *      description="Add Lesson",
     *      tags={"Institution time table"},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(property="day_of_week", type="integer", example=1),
     *              @OA\Property(property="institution_schedule_timeslot_id", type="integer", example=29),
     *              @OA\Property(property="institution_schedule_timetable_id", type="integer", example=1),
     *              @OA\Property(property="lesson_type", type="integer", example=2),
     *              @OA\Property(property="schedule_non_curriculum_lesson", type="object",
     *                  @OA\Property(property="name", type="string", example="dfg")
     *              ),
     *              @OA\Property(property="schedule_lesson_room", type="object",
     *                  @OA\Property(property="institution_schedule_lesson_detail_id", type="string", example="1"),
     *                  @OA\Property(property="institution_room_id", type="string", example="656")
     *              ),
     *              @OA\Property(property="institution_id", type="integer", example=6)
     *          )
     *      ),
     *      @OA\Response(
     *           response=200,
     *          description="Successful.",
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items()
     *                  ),
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Unsuccessful.",
     *      )
     * )
     */
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
     *     path="/api/v4/institutions/schedule-timetables",
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
     *     path="/api/v4/institutions/{institutionId}/schedule-timetables",
     *     summary="Get schedule timetables for institutions",
     *     tags={"Institution time table"},
     *     @OA\Parameter(
     *         name="institutionId",
     *         in="path",
     *         description="Institution Id",
     *         required=true,
     *         @OA\Schema(type="string", example="6")
     *     ),
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
     *     path="/api/v4/institutions/schedule-timetables/{scheduleTimeTableId}",
     *     summary="Get schedule timetables data for institutions",
     *     tags={"Institution time table"},
     *     @OA\Parameter(
     *         name="scheduleTimeTableId",
     *         in="path",
     *         description="Schedule Time Table Id",
     *         required=true,
     *         @OA\Schema(type="string", example="1")
     *     ),
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