<?php

namespace App\Services;

use App\Http\Controllers\Controller;
use App\Repositories\TrainingRepository;
use JWTAuth;
use Illuminate\Support\Facades\Log;

class TrainingService extends Controller
{

    protected $trainingRepository;

    public function __construct(TrainingRepository $trainingRepository)
    {
        $this->trainingRepository = $trainingRepository;
    }

    public function getAllTrainingCourses($params)
    {
        try {
            $data = $this->trainingRepository->getAllTrainingCourses($params);

            $resp = [];

            foreach ($data['data'] as $k => $d) {
                if(isset($d['file_content'])){
                    $d['file_content'] = json_encode($d['file_content'], true);
                }
                array_push($resp, $d);
            }


            if(isset($params['limit'])){
                $data['data'] = $resp;
                return $data; 
            } else {
                return $resp;
            }

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Training Courses List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Training Courses List Not Found.');
        }
    }

}