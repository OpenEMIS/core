<?php

namespace App\Services;

use App\Http\Controllers\Controller;
use App\Repositories\AssessmentRepository;
use JWTAuth;
use Illuminate\Support\Facades\Log;

class AssessmentService extends Controller
{

    protected $assessmentRepository;

    public function __construct(AssessmentRepository $assessmentRepository) 
    {
        $this->assessmentRepository = $assessmentRepository;
    }

    public function getAssessmentItemList($request)
    {
        try {
            $data = $this->assessmentRepository->getAssessmentItemList($request);
            
            return $data;
        } catch (\Exception $e) {
            Log::error(
                'Failed to get Assessment Item List.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            dd($e);

            return $this->sendErrorResponse('Failed to get Assessment Item List.');
        }
    }

}