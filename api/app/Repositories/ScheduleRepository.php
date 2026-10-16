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
use Exception;
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
            InstitutionScheduleLessonRooms::where('institution_schedule_lesson_detail_id', $id)->delete();
            InstitutionScheduleLessonDetails::where('id', $id)->delete();

            return true;
        } catch (\Exception $e) {
           throw $e;
        }
    }

    public function getAllTimeTableLessons()
    {
        return InstitutionScheduleLessonDetails::get();
    }


    public function getTimeTableById($id)
    {
        return InstitutionScheduleTimetables::with('academicPeriod', 'scheduleTerm', 'scheduleInterval', 'institutionClass')->where('id', $id)->first();
    }

    public function getLessonsByTimeTableId($id, $params)
    {
        $lessons =  InstitutionScheduleLessons::with('scheduleLessonDetails.schedule_curriculum_lesson.institution_subject','scheduleLessonDetails.schedule_non_curriculum_lesson','scheduleLessonDetails.schedule_lesson_room.institution_room','timeslots.instituteInterval.shift')->where('institution_schedule_timetable_id', $id);

        if(isset($params['order'])){
            $orderBy = $params['order_by']??"ASC";
            $col = $params['order'];
            $lessons = $lessons->orderBy($col, $orderBy);
        }

        if (isset($params['limit'])) {
            $lessons = $lessons->paginate($params['limit']);
        } else {
            $lessons = $lessons->get();
        }

        return $lessons;
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

    public function getTimeSlotsByIntervalId($intervalId, $params)
    {
        $slots =  InstitutionScheduleTimeslots::with('instituteInterval.shift')->where('institution_schedule_interval_id', $intervalId);
        
        if(isset($params['order'])){
            $orderBy = $params['order_by']??"ASC";
            $col = $params['order'];
            $slots = $slots->orderBy($col, $orderBy);
        }
    
        if (isset($params['limit'])) {
            $slots = $slots->paginate($params['limit']);
        } else {
            $slots = $slots->get();
        }

        return $slots;
    }

    public function addLesson($data)
    {
        DB::beginTransaction();
        try {
            $lessonType = $data['lesson_type'];
            //POCOR-9858: Room is optional - an empty selection means "no room", not a room id
            $roomId = $data['schedule_lesson_room']['institution_room_id'] ?? null;
            $hasRoom = !empty($roomId);

            if ($hasRoom) {
                //POCOR-9858: the room must be free at this day/time in every timetable, not only in this one
                $conflict = $this->findRoomConflict($data, $roomId);

                if ($conflict) {
                    DB::rollback();
                    return [
                        'status' => false,
                        'msg' => $conflict
                    ];
                }
            }

            $lessonData =  [
                "day_of_week" => $data['day_of_week'],
                "institution_schedule_timeslot_id" => $data['institution_schedule_timeslot_id'],
                "institution_schedule_timetable_id" => $data['institution_schedule_timetable_id'],
                "created_user_id" => JWTAuth::user()->id,
                "created" => Carbon::now()->toDateTimeString()
            ];

            $lesson = InstitutionScheduleLessons::where('day_of_week', $data['day_of_week'])
                ->where('institution_schedule_timetable_id', $data['institution_schedule_timetable_id'])
                ->where('institution_schedule_timeslot_id', $data['institution_schedule_timeslot_id'])
                ->first();

            if ($lesson) {
                InstitutionScheduleLessons::where('id', $lesson->id)
                    ->update(
                        [
                            'modified' => Carbon::now()->toDateTimeString(),
                            'modified_user_id' => JWTAuth::user()->id
                    ]);
            } else {
                InstitutionScheduleLessons::insert($lessonData);
            }

            $lessonData['lesson_type'] = $data['lesson_type'];
            $lessonId = InstitutionScheduleLessonDetails::insertGetId($lessonData);

            if ($lessonType == 1) {
                $curriculum = [
                    'code_only' => $data['schedule_curriculum_lesson']['code_only'],
                    'institution_schedule_lesson_detail_id' => $lessonId,
                    'institution_subject_id' => $data['schedule_curriculum_lesson']['institution_subject_id'],
                ];
                InstitutionScheduleCurriculumLessons::insert($curriculum);
            } else {
                $nonCurriculum = [
                    'name' => $data['schedule_non_curriculum_lesson']['name'],
                    'institution_schedule_lesson_detail_id' => $lessonId
                ];
                InstitutionScheduleNonCurriculumLessons::insert($nonCurriculum);
            }

            if ($hasRoom) { //POCOR-9858: no room row when no room was selected (institution_room_id is NOT NULL)
                $room = [
                    "institution_room_id" => $roomId,
                    'institution_schedule_lesson_detail_id' => $lessonId
                ];

                InstitutionScheduleLessonRooms::insert($room);
            }

            DB::commit();

            return [
                'status' => true,
                'msg' => 'Lesson successfully added.'
            ];
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    //POCOR-9858: start - room clash check across timetables
    /**
     * Returns an error message when the room is already used at an overlapping time on the same
     * day by any timetable in the same academic period whose term overlaps this timetable's term.
     * Times are compared (not timeslot ids) because each timetable can use a different interval.
     */
    public function findRoomConflict($data, $roomId)
    {
        $timetableId = $data['institution_schedule_timetable_id'];
        $timetable = DB::table('institution_schedule_timetables as t')
            ->leftJoin('institution_schedule_terms as term', 'term.id', '=', 't.institution_schedule_term_id')
            ->where('t.id', $timetableId)
            ->select('t.academic_period_id', 'term.start_date', 'term.end_date')
            ->first();
        if (!$timetable) {
            return null;
        }

        $window = $this->getTimeslotWindow($data['institution_schedule_timeslot_id']);
        if (!$window) {
            return null;
        }

        $candidates = DB::table('institution_schedule_lesson_details as d')
            ->join('institution_schedule_lesson_rooms as r', 'r.institution_schedule_lesson_detail_id', '=', 'd.id')
            ->join('institution_schedule_timetables as t', 't.id', '=', 'd.institution_schedule_timetable_id')
            ->leftJoin('institution_schedule_terms as term', 'term.id', '=', 't.institution_schedule_term_id')
            ->leftJoin('institution_classes as c', 'c.id', '=', 't.institution_class_id')
            ->where('r.institution_room_id', $roomId)
            ->where('d.day_of_week', $data['day_of_week'])
            ->where('t.academic_period_id', $timetable->academic_period_id)
            ->select(
                'd.institution_schedule_timeslot_id',
                't.id as timetable_id',
                't.name as timetable_name',
                'c.name as class_name',
                'term.start_date',
                'term.end_date'
            )
            ->get();

        foreach ($candidates as $candidate) {
            if (!$this->datesOverlap($timetable->start_date, $timetable->end_date, $candidate->start_date, $candidate->end_date)) {
                continue;
            }
            $otherWindow = $this->getTimeslotWindow($candidate->institution_schedule_timeslot_id);
            if (!$otherWindow) {
                continue;
            }
            if ($window['start'] < $otherWindow['end'] && $otherWindow['start'] < $window['end']) {
                if ($candidate->timetable_id == $timetableId) {
                    return __('Selected Room already occupied by another subject.');
                }
                return __('Selected Room is already used at this time by timetable') . ' "' . $candidate->timetable_name . '"'
                    . (!empty($candidate->class_name) ? ' (' . __('Class') . ': ' . $candidate->class_name . ')' : '') . '.';
            }
        }

        return null;
    }

    /**
     * Start/end time (H:i:s) of a timeslot: shift option start time plus the earlier slots of the
     * same interval, ordered by 'order' - same calculation as TimetableOverviewService (POCOR-9594).
     */
    private function getTimeslotWindow($timeslotId)
    {
        static $cache = [];
        if (array_key_exists($timeslotId, $cache)) {
            return $cache[$timeslotId];
        }

        $slot = DB::table('institution_schedule_timeslots')->where('id', $timeslotId)->first();
        if (!$slot) {
            return $cache[$timeslotId] = null;
        }

        $startTime = DB::table('institution_schedule_intervals as i')
            ->join('institution_shifts as s', 's.id', '=', 'i.institution_shift_id')
            ->join('shift_options as so', 'so.id', '=', 's.shift_option_id')
            ->where('i.id', $slot->institution_schedule_interval_id)
            ->value('so.start_time');
        if (empty($startTime)) {
            return $cache[$timeslotId] = null;
        }

        $slots = DB::table('institution_schedule_timeslots')
            ->where('institution_schedule_interval_id', $slot->institution_schedule_interval_id)
            ->orderBy('order', 'asc')
            ->get();

        $current = new \DateTime($startTime);
        foreach ($slots as $item) {
            $end = clone $current;
            $end->add(new \DateInterval('PT' . max(1, (int)$item->interval) . 'M'));
            $cache[$item->id] = ['start' => $current->format('H:i:s'), 'end' => $end->format('H:i:s')];
            $current = $end;
        }

        return $cache[$timeslotId] ?? null;
    }

    private function datesOverlap($startA, $endA, $startB, $endB)
    {
        // A timetable without term dates is treated as covering the whole academic period
        if (empty($startA) || empty($endA) || empty($startB) || empty($endB)) {
            return true;
        }
        return $startA <= $endB && $startB <= $endA;
    }
    //POCOR-9858: end



    //POCOR-8295 start...
    public function getScheduleTimetables($params)
    {
        try {
            $scheduleTimetables = InstitutionScheduleTimetables::with(
                'academicPeriod:id,name',
                'institutionClass:id,name',
                'institution:id,name',
                'institution:id,name,code',
                'scheduleInterval:id,name',
                'scheduleTerm:id,name'
            );

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $scheduleTimetables = $scheduleTimetables->orderBy($col, $orderBy);
            }

            if(isset($params['limit'])){
                $limit = $params['limit'];
                $list = $scheduleTimetables->paginate($limit)->toArray();
            } else {
                $list['data'] = $scheduleTimetables->get()->toArray();
            }

            return $list;

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch schedule timetables.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Failed to fetch schedule timetables.',[], 500);
        }

    }


    public function getScheduleTimetablesViaInstitutionId($params, $institutionId)
    {
        try {
            $scheduleTimetables = InstitutionScheduleTimetables::with(
                'academicPeriod:id,name',
                'institutionClass:id,name',
                'institution:id,name',
                'institution:id,name,code',
                'scheduleInterval:id,name',
                'scheduleTerm:id,name'
            )
            ->where('institution_id', $institutionId);

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $scheduleTimetables = $scheduleTimetables->orderBy($col, $orderBy);
            }

            if(isset($params['limit'])){
                $limit = $params['limit'];
                $list = $scheduleTimetables->paginate($limit)->toArray();
            } else {
                $list['data'] = $scheduleTimetables->get()->toArray();
            }

            return $list;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch schedule timetables.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Failed to fetch schedule timetables.',[], 500);
        }

    }


    public function getScheduleTimetableData($scheduleTimetableId)
    {
        try {
            $scheduleTimetable = InstitutionScheduleTimetables::with(
                'academicPeriod:id,name',
                'institutionClass:id,name',
                'institution:id,name',
                'institution:id,name,code',
                'scheduleInterval:id,name',
                'scheduleTerm:id,name'
            )
            ->where('id', $scheduleTimetableId)
            ->first();
            
            return $scheduleTimetable;
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