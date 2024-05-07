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


    //POCOR-8100 start...
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


            /*if(isset($params['limit'])){
                $data['data'] = $resp;
                return $data; 
            } else {
                return $resp;
            }*/

            $data['data'] = $resp;
            return $data;

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Training Courses List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Training Courses List Not Found.');
        }
    }



    public function getTrainingCourseData($courseId)
    {
        try {
            $data = $this->trainingRepository->getTrainingCourseData($courseId);
            
            if(!empty($data)){
                $data['file_content'] = json_encode($data['file_content'], true);
            }
            return $data;

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Training Courses Data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Training Courses Data Not Found.');
        }
    }


    public function getTrainingProviders($params)
    {
        try {
            $data = $this->trainingRepository->getTrainingProviders($params);

            return $data;

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Training Providers List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Training Providers List Not Found.');
        }
    }


    public function getTrainingProvidersData($providerId)
    {
        try {
            $data = $this->trainingRepository->getTrainingProvidersData($providerId);

            return $data;

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Training Provider Data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Training Provider Data Not Found.');
        }
    }


    public function getTrainingSessions($params)
    {
        try {
            $data = $this->trainingRepository->getTrainingSessions($params);

            return $data;

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Training Sessions List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Training Sessions List Not Found.');
        }
    }


    public function getTrainingSessionData($sessionId)
    {
        try {
            $data = $this->trainingRepository->getTrainingSessionData($sessionId);

            return $data;

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Training Session Data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Training Session Data Not Found.');
        }
    }


    //POCOR-8100 end...

}