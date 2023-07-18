<?php

namespace App\Services;

use App\Http\Controllers\Controller;
use App\Repositories\SurveyRepository;
use JWTAuth;
use Illuminate\Support\Facades\Log;

class SurveyService extends Controller
{

    protected $SurveyRepository;

    public function __construct(
    SurveyRepository $surveyRepository) {
        $this->surveyRepository = $surveyRepository;
    }
  
    public function getSurveys($request)
    {
        try {
            $data = $this->surveyRepository->getSurveys($request);
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Surveys List Not Found');
        }
    }
}
