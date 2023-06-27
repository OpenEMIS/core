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
}
