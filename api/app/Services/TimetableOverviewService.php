<?php

namespace App\Services;

use App\Http\Controllers\Controller;
use App\Repositories\TimetableOverviewRepository;
use Carbon\Carbon;
use JWTAuth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

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

            if(isset($data['data'])){
                foreach ($data['data'] as $key => $value) {
                    $response[$key]['id'] = $value['id'];
                    $response[$key]['name'] = $value['name'];
                    $response[$key]['status'] = $value['status'];
                    $response[$key]['academic_period_id'] = $value['academic_period_id'];
                    $response[$key]['academic_period_name'] = $value['academic_period']['name']??"";
                    $response[$key]['institution_class_id'] = $value['institution_class_id'];
                    $response[$key]['institution_class_name'] = $value['institution_class']['name']??"";
                    $response[$key]['institution_id'] = $value['institution_id'];
                    $response[$key]['institution_code'] = $value['institution']['code']??"";
                    $response[$key]['institution_name'] = $value['institution']['name']??"";
                    $response[$key]['institution_schedule_interval_id'] = $value['institution_schedule_interval_id'];
                    $response[$key]['institution_schedule_interval_name'] = $value['schedule_interval']['name']??"";
                    $response[$key]['institution_schedule_term_id'] = $value['institution_schedule_term_id'];
                    $response[$key]['institution_schedule_term_name'] = $value['schedule_term']['name']??"";
                    $response[$key]['modified_user_id'] = $value['modified_user_id'];
                    $response[$key]['modified'] = $value['modified'];
                    $response[$key]['created_user_id'] = $value['created_user_id'];
                    $response[$key]['created'] = $value['created'];
                }
            }


            $data['data'] = $response;
            return $data;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch schedule overview data.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Failed to fetch schedule overview data.',[], 500);
        }

    }

}

    