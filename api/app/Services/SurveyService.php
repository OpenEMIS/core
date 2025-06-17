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
                $resp[$k]['id'] = $data['id'];
                $resp[$k]['code'] = $data['code'];
                $resp[$k]['name'] = $data['name'];
                $resp[$k]['description'] = $data['description'];
                $resp[$k]['custom_module_id'] = $data['custom_module_id'];
                $resp[$k]['custom_module'] = $data['custom_module']['name'];
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


    public function uploadXform($request)
    {
        try {
            $data = $this->surveyRepository->uploadXform($request);

            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to upload survey xform.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to upload survey xform.');
        }
    }



    public function checkInsXform($params, $surveyFormId, $insCode, $academicPeriod)
    {
        try {
            $data = $this->surveyRepository->checkInsXform($params, $surveyFormId, $insCode, $academicPeriod);
            
            return $data;            
        } catch (\Exception $e) {
            Log::error(
                'Failed to check survey form.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Failed to check survey form.');
        }
    }



    public function getStudentListForSurvey($params, $surveyFormId, $insCode, $academicPeriod)
    {
        try {
            $data = $this->surveyRepository->getStudentListForSurvey($params, $surveyFormId, $insCode, $academicPeriod);
            
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to find student list.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to find student list.');
        }
    }
}
