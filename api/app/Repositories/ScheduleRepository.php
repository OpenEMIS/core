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

    public function getLessonsByTimeTableId($id)
    {
        return InstitutionScheduleLessons::with('scheduleLessonDetails','timeslots.instituteInterval.shift')->where('institution_schedule_timetable_id', $id)->get();
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
        return InstitutionScheduleTimeslots::with('instituteInterval.shift')->where('institution_schedule_interval_id', $intervalId)->get();
    }

    public function addLesson($data)
    {
        DB::beginTransaction();
        try {
            $lessonType = $data['lesson_type'];

            if ($lessonType == 1 && isset($data['schedule_lesson_room'])) {
                $record = $this->checkSubjectExistSameTimeslot($data);

                if ($record) {
                    // room already exist for subject
                    return [
                        'status' => false,
                        'msg' => 'Selected Room already occupied by another subject.'
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

            if (isset($data['schedule_lesson_room'])) {
                $room = [
                    "institution_room_id" => $data['schedule_lesson_room']['institution_room_id'],
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

    public function checkSubjectExistSameTimeslot($data)
    {
        $query = InstitutionScheduleLessonDetails::join('institution_schedule_lesson_rooms', 'institution_schedule_lesson_details.id',   '=', 'institution_schedule_lesson_rooms.institution_schedule_lesson_detail_id')
            ->where('institution_schedule_lesson_details.day_of_week', '=', $data['day_of_week'])
            ->where('institution_schedule_lesson_details.institution_schedule_timeslot_id', '=', $data['institution_schedule_timeslot_id'])
            ->where('institution_schedule_lesson_details.institution_schedule_timetable_id', '=', $data['institution_schedule_timetable_id'])
            ->where('institution_schedule_lesson_details.lesson_type', '=', $data['lesson_type'])
            ->where('institution_schedule_lesson_rooms.institution_room_id', '=', $data['schedule_lesson_room']['institution_room_id'])
            ->first();

            return $query;
    }



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