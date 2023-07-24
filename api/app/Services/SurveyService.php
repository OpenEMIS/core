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
            $list = $this->surveyRepository->getSurveys($request);

            $resp = [];
            foreach($list['data'] as $k => $data){
                $resp[$k]['institution_survey_id'] = $data['id'];
                $resp[$k]['institution_id'] = $data['institution_id'];
                $resp[$k]['assignee_id'] = $data['assignee_id'];
                $resp[$k]['id'] = $data['survey_forms']['id'];
                $resp[$k]['code'] = $data['survey_forms']['code'];
                $resp[$k]['name'] = $data['survey_forms']['name'];
                $resp[$k]['description'] = $data['survey_forms']['description'];
                $resp[$k]['custom_module_id'] = $data['survey_forms']['custom_module_id'];
                $resp[$k]['custom_module'] = $data['survey_forms']['custom_module']['name'];
                $resp[$k]['modified_user_id'] = $data['modified_user_id'];
                $resp[$k]['modified'] = $data['modified'];
                $resp[$k]['created_user_id'] = $data['created_user_id'];
                $resp[$k]['created'] = $data['created'];
            }
            $list['data'] = $resp;
            return $list;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Surveys List Not Found');
        }
    }



    public function downloadXform($request, $surveyFormId)
    {
        try {
            $data = $this->surveyRepository->downloadXform($request, $surveyFormId);

            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to download survey xform.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to download survey xform.');
        }
    }
}
