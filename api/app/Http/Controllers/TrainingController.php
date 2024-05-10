<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\TrainingService;
use Illuminate\Support\Facades\Log;

class TrainingController extends Controller
{
    protected $trainingService;

    public function __construct(TrainingService $trainingService)
    {
        $this->trainingService = $trainingService;
    }

    //POCOR-8100 start...
    public function getAllTrainingCourses(Request $request)
    {
        try {
            $params = $request->all();
            $data = $this->trainingService->getAllTrainingCourses($params);
            
            if (empty($data)) {
                return $this->sendErrorResponse("Training Courses List Not Found.");
            }

            return $this->sendSuccessResponse("Training Courses List Found.", $data);

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
            $data = $this->trainingService->getTrainingCourseData($courseId);
            
            if (empty($data)) {
                return $this->sendErrorResponse("Training Courses Data Not Found.");
            }

            return $this->sendSuccessResponse("Training Courses Data Found.", $data);

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Training Courses Data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Training Courses Data Not Found.');
        }
    }


    public function getTrainingProviders(Request $request)
    {
        try {
            $params = $request->all();
            $data = $this->trainingService->getTrainingProviders($params);
            
            if (empty($data)) {
                return $this->sendErrorResponse("Training Providers List Not Found.");
            }

            return $this->sendSuccessResponse("Training Providers List Found.", $data);

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
            $data = $this->trainingService->getTrainingProvidersData($providerId);
            
            if (empty($data)) {
                return $this->sendErrorResponse("Training Provider Data Not Found.");
            }

            return $this->sendSuccessResponse("Training Provider Data Found.", $data);

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Training Provider Data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Training Provider Data Not Found.');
        }
    }


    public function getTrainingSessions(Request $request)
    {
        try {
            $params = $request->all();
            $data = $this->trainingService->getTrainingSessions($params);
            
            if (empty($data)) {
                return $this->sendErrorResponse("Training Sessions List Not Found.");
            }

            return $this->sendSuccessResponse("Training Sessions List Found.", $data);

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
            $data = $this->trainingService->getTrainingSessionData($sessionId);
            
            if (empty($data)) {
                return $this->sendErrorResponse("Training Session Data Not Found.");
            }

            return $this->sendSuccessResponse("Training Session Data Found.", $data);

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Training Session Data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            
            return $this->sendErrorResponse('Training Session Data Not Found.');
        }
    }



    public function getTrainingSessionResults(Request $request, $sessionId)
    {
        try {
            $params = $request->all();
            $data = $this->trainingService->getTrainingSessionResults($params, $sessionId);
            
            if (empty($data)) {
                return $this->sendErrorResponse("Training Session Results Not Found.");
            }

            return $this->sendSuccessResponse("Training Session Results Found.", $data);

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Training Session Results from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            dd($e);
            return $this->sendErrorResponse('Training Session Results Not Found.');
        }
    }



    public function getTrainingSessionResultsViaUserId(Request $request, $sessionId, $userId)
    {
        try {
            $params = $request->all();
            $data = $this->trainingService->getTrainingSessionResultsViaUserId($params, $sessionId, $userId);
            
            if (empty($data)) {
                return $this->sendErrorResponse("Training Session Results Not Found.");
            }

            return $this->sendSuccessResponse("Training Session Results Found.", $data);

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Training Session Results from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            dd($e);
            return $this->sendErrorResponse('Training Session Results Not Found.');
        }
    }

    //POCOR-8100 end...

}
