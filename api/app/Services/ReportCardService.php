<?php

namespace App\Services;

use App\Http\Controllers\Controller;
use App\Repositories\ReportCardRepository;
use JWTAuth;
use Illuminate\Support\Facades\Log;

class ReportCardService extends Controller
{

    protected $reportCardRepository;

    public function __construct(
    ReportCardRepository $reportCardRepository) {
        $this->reportCardRepository = $reportCardRepository;
    }

    //pocor-7856 starts
    public function getReportCardStudents($request)
    {
        try {
            $data = $this->reportCardRepository->getReportCardStudents($request);
            
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list.');
        }
    }



    public function getReportCardSubjects($request)
    {
        try {
            $data = $this->reportCardRepository->getReportCardSubjects($request);
            return $data;   
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Failed to fetch list.');
        }
    }
    //pocor-7856 ends



    //For pocor-8260 start...
    public function getReportCardCommentCodes($params)
    {
        try {
            $data = $this->reportCardRepository->getReportCardCommentCodes($params);
            
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list.');
        }
    }
    //For pocor-8260 end...


    //For pocor-8270 start...
    public function getSecurityRoleData($params, $roleId)
    {
        try {
            $data = $this->reportCardRepository->getSecurityRoleData($params, $roleId);
            
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch data.');
        }
    }


    public function getReportCardData($params, $reportCardId)
    {
        try {
            $data = $this->reportCardRepository->getReportCardData($params, $reportCardId);

            if(!empty($data)){
                unset($data['excel_template']);
                //$data['excel_template'] = base64_encode($data['excel_template']);
            }
            
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Failed to fetch data.');
        }
    }
    //For pocor-8270 end...

}
