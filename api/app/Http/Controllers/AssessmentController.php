<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AssessmentService;
use Illuminate\Support\Facades\Log;

class AssessmentController extends Controller
{
    protected $assessmentService;

    public function __construct(AssessmentService $assessmentService) 
    {
        $this->assessmentService = $assessmentService;
    }

    public function getEducationGradeList(Request $request)
    {
        try {
            
            $data = $this->assessmentService->getEducationGradeList($request);
            return $this->sendSuccessResponse("Assessment Education Grade List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Assessment Education Grade List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
             dd($e);
            return $this->sendErrorResponse('Assessment Education Grade List Not Found');
        }
    }

    public function getAssessmentItemList(Request $request)
    {
        try {
            
            $data = $this->assessmentService->getAssessmentItemList($request);
            return $this->sendSuccessResponse("Assessment Item List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Assessment Item List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
             dd($e);
            return $this->sendErrorResponse('Assessment Item List Not Found');
        }
    }

    public function getAssessmentPeriodList(Request $request)
    {
        try {
            
            $data = $this->assessmentService->getAssessmentPeriodList($request);
            return $this->sendSuccessResponse("Assessment Period List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Assessment Period List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
             dd($e);
            return $this->sendErrorResponse('Assessment Period List Not Found');
        }
    }
}
