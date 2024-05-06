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

}
