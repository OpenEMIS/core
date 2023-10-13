<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WorkbenchService;
use Illuminate\Support\Facades\Log;

class WorkbenchController extends Controller
{
    protected $workbenchService;

    public function __construct(
        WorkbenchService $workbenchService
    ) {
        $this->workbenchService = $workbenchService;
    }


    public function getNoticesList(Request $request)
    {
        try {
            $data = $this->workbenchService->getNoticesList($request);
            
            return $this->sendSuccessResponse("Notice List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }

    public function getInstitutionStaffLeave(Request $request)
    {
        try {
            $data = $this->workbenchService->getInstitutionStaffLeave($request);
            
            return $this->sendSuccessResponse("Staff Leave List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }


    public function getInstitutionStaffSurveys(Request $request)
    {
        try {
            $data = $this->workbenchService->getInstitutionStaffSurveys($request);
            
            return $this->sendSuccessResponse("Staff Survey List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }


    public function getInstitutionStudentWithdraw(Request $request)
    {
        try {
            $data = $this->workbenchService->getInstitutionStudentWithdraw($request);
            
            return $this->sendSuccessResponse("Student Withdraw List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }


    public function getInstitutionStudentAdmission(Request $request)
    {
        try {
            $data = $this->workbenchService->getInstitutionStudentAdmission($request);
            
            return $this->sendSuccessResponse("Student Admission List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }



    public function getInstitutionStudentTransferOut(Request $request)
    {
        try {
            $data = $this->workbenchService->getInstitutionStudentTransferOut($request);
            
            return $this->sendSuccessResponse("Student Transfer Out List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }


    public function getInstitutionStudentBehaviour(Request $request)
    {
        try {
            $data = $this->workbenchService->getInstitutionStudentBehaviour($request);
            
            return $this->sendSuccessResponse("Student Behaviour List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }



    public function getInstitutionStaffBehaviour(Request $request)
    {
        try {
            $data = $this->workbenchService->getInstitutionStaffBehaviour($request);
            
            return $this->sendSuccessResponse("Staff Behaviour List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }


    public function getStaffAppraisals(Request $request)
    {
        try {
            $data = $this->workbenchService->getStaffAppraisals($request);
            
            return $this->sendSuccessResponse("Staff Appraisals List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }
}
