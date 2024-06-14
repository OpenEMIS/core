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



    //POCOR-8295 start...
    public function getScheduleTimetables($params)
    {
        try {
            $data = $this->scheduleRepository->getScheduleTimetables($params);
            $resp = [];

            if(isset($data['data'])){
                foreach ($data['data'] as $k => $d) {
                    $resp[$k]['id'] = $d['id'];
                    $resp[$k]['name'] = $d['name'];
                    $resp[$k]['status'] = $d['status'];
                    $resp[$k]['academic_period_id'] = $d['academic_period_id'];
                    $resp[$k]['academic_period_name'] = $d['academic_period']['name']??"";
                    $resp[$k]['institution_class_id'] = $d['institution_class_id'];
                    $resp[$k]['institution_class_name'] = $d['institution_class']['name']??"";
                    $resp[$k]['institution_id'] = $d['institution_id'];
                    $resp[$k]['institution_code'] = $d['institution']['code']??"";
                    $resp[$k]['institution_name'] = $d['institution']['name']??"";
                    $resp[$k]['institution_schedule_interval_id'] = $d['institution_schedule_interval_id'];
                    $resp[$k]['institution_schedule_interval_name'] = $d['schedule_interval']['name']??"";
                    $resp[$k]['institution_schedule_term_id'] = $d['institution_schedule_term_id'];
                    $resp[$k]['institution_schedule_term_name'] = $d['schedule_term']['name']??"";
                    $resp[$k]['modified_user_id'] = $d['modified_user_id'];
                    $resp[$k]['modified'] = $d['modified'];
                    $resp[$k]['created_user_id'] = $d['created_user_id'];
                    $resp[$k]['created'] = $d['created'];
                }
            }


            $data['data'] = $resp;
            return $data;
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
            $data = $this->scheduleRepository->getScheduleTimetablesViaInstitutionId($params, $institutionId);

            $resp = [];

            if(isset($data['data'])){
                foreach ($data['data'] as $k => $d) {
                    $resp[$k]['id'] = $d['id'];
                    $resp[$k]['name'] = $d['name'];
                    $resp[$k]['status'] = $d['status'];
                    $resp[$k]['academic_period_id'] = $d['academic_period_id'];
                    $resp[$k]['academic_period_name'] = $d['academic_period']['name']??"";
                    $resp[$k]['institution_class_id'] = $d['institution_class_id'];
                    $resp[$k]['institution_class_name'] = $d['institution_class']['name']??"";
                    $resp[$k]['institution_id'] = $d['institution_id'];
                    $resp[$k]['institution_code'] = $d['institution']['code']??"";
                    $resp[$k]['institution_name'] = $d['institution']['name']??"";
                    $resp[$k]['institution_schedule_interval_id'] = $d['institution_schedule_interval_id'];
                    $resp[$k]['institution_schedule_interval_name'] = $d['schedule_interval']['name']??"";
                    $resp[$k]['institution_schedule_term_id'] = $d['institution_schedule_term_id'];
                    $resp[$k]['institution_schedule_term_name'] = $d['schedule_term']['name']??"";
                    $resp[$k]['modified_user_id'] = $d['modified_user_id'];
                    $resp[$k]['modified'] = $d['modified'];
                    $resp[$k]['created_user_id'] = $d['created_user_id'];
                    $resp[$k]['created'] = $d['created'];
                }
            }


            $data['data'] = $resp;
            return $data;
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
            $data = $this->scheduleRepository->getScheduleTimetableData($scheduleTimetableId);

            $resp = [];

            if(isset($data)){
                $resp['id'] = $data['id'];
                $resp['name'] = $data['name'];
                $resp['status'] = $data['status'];
                $resp['academic_period_id'] = $data['academic_period_id'];
                $resp['academic_period_name'] = $data['academicPeriod']['name']??"";
                $resp['institution_class_id'] = $data['institution_class_id'];
                $resp['institution_class_name'] = $data['institutionClass']['name']??"";
                $resp['institution_id'] = $data['institution_id'];
                $resp['institution_code'] = $data['institution']['code']??"";
                $resp['institution_name'] = $data['institution']['name']??"";
                $resp['institution_schedule_interval_id'] = $data['institution_schedule_interval_id'];
                $resp['institution_schedule_interval_name'] = $data['scheduleInterval']['name']??"";
                $resp['institution_schedule_term_id'] = $data['institution_schedule_term_id'];
                $resp['institution_schedule_term_name'] = $data['scheduleTerm']['name']??"";
                $resp['modified_user_id'] = $data['modified_user_id'];
                $resp['modified'] = $data['modified'];
                $resp['created_user_id'] = $data['created_user_id'];
                $resp['created'] = $data['created'];
            }
            
            return $resp;
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