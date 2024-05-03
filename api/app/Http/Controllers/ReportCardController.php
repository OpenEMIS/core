<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ReportCardService;
use Illuminate\Support\Facades\Log;
use Exception;
use JWTAuth;

class ReportCardController extends Controller
{
    protected $reportCardService;

    public function __construct(
        ReportCardService $reportCardService
    ) {
        $this->reportCardService = $reportCardService;
    }

    //pocor-7856 starts
    public function getReportCardStudents(Request $request)
    {
        try {
            $data = $this->reportCardService->getReportCardStudents($request);
            
            return $this->sendSuccessResponse("Report card student list found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list.');
        }
    }



    public function getReportCardSubjects(Request $request)
    {
        try {
            $data = $this->reportCardService->getReportCardSubjects($request);
            
            return $this->sendSuccessResponse("Report card subject list found", $data);
            
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
    public function getReportCardCommentCodes(Request $request)
    {
        try {
            $params = $request->all();
            $data = $this->reportCardService->getReportCardCommentCodes($params);
            
            return $this->sendSuccessResponse("Report card comment codes list found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list.');
        }
    }
    //For pocor-8260 end...
}
