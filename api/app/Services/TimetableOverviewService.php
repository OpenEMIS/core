<?php

namespace App\Services;

use App\Http\Controllers\Controller;
use App\Repositories\TimetableOverviewRepository;
use Carbon\Carbon;
use JWTAuth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Models\InstitutionScheduleTimeslots;
use DateTime;
use DateInterval;

//POCOR-8616
class TimetableOverviewService extends Controller
{

    protected $timetableOverviewRepository;

    public function __construct(TimetableOverviewRepository $timetableOverviewRepository) {
        $this->timetableOverviewRepository = $timetableOverviewRepository;
    }

    public function getTimetableOverview($request)
    {
        try {
            $data = $this->timetableOverviewRepository->getTimetableOverview($request);
            $response = [];

            if (isset($data['data'])) {
                foreach ($data['data'] as $key => $value) {
                    $response[$key]['id'] = $value['id'];
                    $response[$key]['name'] = $value['name'];
                    $response[$key]['schedule_term'] = $value['schedule_term']['name'];
                    $response[$key]['schedule_term_id'] = $value['schedule_term']['id'];
                    $response[$key]['shift_option'] = $value['schedule_interval']['shift']['shift_option']['name'];
                    $response[$key]['shift_time'] = $value['schedule_interval']['shift']['shift_option']['start_time'];
                    $response[$key]['shift_option_id'] = $value['schedule_interval']['shift']['shift_option']['id'];

                    $response[$key]['academic_period_id'] = $value['academic_period_id'];
                    $response[$key]['academic_period_name'] = $value['academic_period']['name'] ?? "";
                    $response[$key]['institution_class_id'] = $value['institution_class_id'];
                    $response[$key]['institution_class_name'] = $value['institution_class']['name'] ?? "";
                    $response[$key]['institution_grade_id'] = $value['institution_class']['grades'][0]['education_grade_id'];
                    $response[$key]['institution_grade_name'] = $value['institution_class']['grades'][0]['education_grades']['name'];
                    $response[$key]['institution_id'] = $value['institution_id'];
                    $response[$key]['institution_code'] = $value['institution']['code'] ?? "";
                    $response[$key]['institution_name'] = $value['institution']['name'] ?? "";
                    $response[$key]['institution_schedule_interval_id'] = $value['institution_schedule_interval_id'];
                    $response[$key]['institution_schedule_interval_name'] = $value['schedule_interval']['name'] ?? "";
                    $response[$key]['institution_schedule_term_id'] = $value['institution_schedule_term_id'];
                    $response[$key]['institution_schedule_term_name'] = $value['schedule_term']['name'] ?? "";

                    if ($value['status'] == 1) {
                        $response[$key]['status'] = 'Draft';
                        $response[$key]['status_id'] = $value['status'];
                    } else {
                        $response[$key]['status'] = 'Published';
                        $response[$key]['status_id'] = $value['status'];
                    }

                    $response[$key]['modified_user_id'] = $value['modified_user_id'];
                    $response[$key]['modified'] = $value['modified'];
                    $response[$key]['created_user_id'] = $value['created_user_id'];
                    $response[$key]['created'] = $value['created'];

                    // Calculate time slots
                    $startTime = $value['schedule_interval']['shift']['shift_option']['start_time'];
                    $checkRecord = InstitutionScheduleTimeslots::where('institution_schedule_interval_id', $value['institution_schedule_interval_id'])->get()->toArray();

                    $results = [];
                    $currentStartTime = new \DateTime($startTime); // Initial start time

                    foreach ($checkRecord as $val) {
                        $intervalMinutes = $val['interval']; 
                        $interval = new \DateInterval('PT' . $intervalMinutes . 'M');
                        // Clone the current start time for the end time calculation
                        $endTime = clone $currentStartTime;
                        $endTime->add($interval);
                        $formattedStartTime = $currentStartTime->format('H:i:s');
                        $formattedEndTime = $endTime->format('H:i:s');
                        $results[] = [
                            'start_time' => $formattedStartTime,
                            'end_time' => $formattedEndTime,
                            'interval' => $intervalMinutes
                        ];
                        // Update the current start time to be the end time for the next iteration
                        $currentStartTime = $endTime;
                    }
                    $response[$key]['time_slots'] = $results;
                }
            }

            $data['data'] = $response;
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch schedule overview data.',
                ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Failed to fetch schedule overview data.', [], 500);
        }
    }

}

    