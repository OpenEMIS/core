<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\SurveyService;
use App\Http\Requests\UploadXformRequest;

class SurveyController extends Controller
{
    protected $surveyService;

    public function __construct(SurveyService $surveyService) 
    {
        $this->surveyService = $surveyService;
    }


    public function getSurveys(Request $request)
    {
        try {
            $data = $this->surveyService->getSurveys($request);
            return $this->sendSuccessResponse("Surveys List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Surveys List Not Found');
        }
    }


    public function downloadXform(Request $request, $surveyFormId)
    {
        try {
            $data = $this->surveyService->downloadXform($request, $surveyFormId);
            
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to download survey xform.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to download survey xform.');
        }
    }


    public function uploadXform(UploadXformRequest $request)
    {
        try {
            $data = $this->surveyService->uploadXform($request);
            
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to upload survey xform.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to upload survey xform.');
        }
    }
}
