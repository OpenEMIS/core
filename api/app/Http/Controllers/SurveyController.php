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


    public function downloadXform(Request $request, $surveyFormId, $action=1)
    {
        try {
            $data = $this->surveyService->downloadXform($request, $surveyFormId);
            
            if(!empty($data)){
                if($action == 1){
                    return response($data, 200,['Content-Type' => 'application/xml']);
                } else {
                    $fileName = 'xform_' . date('Ymdhis');
                    $fileWithExt = $fileName.".xml";

                    return response($data)
                        ->header('Content-type', 'application/xml')
                        ->header('Content-Disposition', 'attachment; filename='.$fileWithExt);
                }
            } else {
                return $this->sendErrorResponse('Failed to download survey xformqqq.');
            }
            
            
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
            
            if($data == 1){
                return $this->sendSuccessResponse('Survey xfrom uploaded successfully.');
            } elseif($data == 2){
                return $this->sendErrorResponse('No record found for institution for the form for the period.');
            } elseif($data == 3){
                return $this->sendErrorResponse('Survey is already expired.');
            } elseif($data == 4){
                return $this->sendErrorResponse('Survey is already completed.');
            } elseif($data == 5){
                return $this->sendErrorResponse("You're not allowed to upload survey for this institution.");
            } elseif($data == 6){
                return $this->sendErrorResponse('Invalid institution code.');
            } else {
                return $this->sendErrorResponse('Failed to upload survey xform.');
            }
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to upload survey xform.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to upload survey xform.');
        }
    }
}
