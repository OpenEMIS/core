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


    public function getInstitutionStudentTransferIn(Request $request)
    {
        try {
            $data = $this->workbenchService->getInstitutionStudentTransferIn($request);
            
            return $this->sendSuccessResponse("Student Transfer In List Found", $data);
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


    public function getStaffRelease(Request $request)
    {
        try {
            $data = $this->workbenchService->getStaffRelease($request);
            
            return $this->sendSuccessResponse("Staff Release List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }


    public function getStaffTransferOut(Request $request)
    {
        try {
            $data = $this->workbenchService->getStaffTransferOut($request);
            
            return $this->sendSuccessResponse("Staff Transfer Out List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }


    public function getStaffTransferIn(Request $request)
    {
        try {
            $data = $this->workbenchService->getStaffTransferIn($request);
            
            return $this->sendSuccessResponse("Staff Transfer In List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }


    public function getChangeInAssignment(Request $request)
    {
        try {
            $data = $this->workbenchService->getChangeInAssignment($request);
            
            return $this->sendSuccessResponse("Staff Change In Assignmen List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }


    public function getStaffTrainingNeeds(Request $request)
    {
        try {
            $data = $this->workbenchService->getStaffTrainingNeeds($request);
            
            return $this->sendSuccessResponse("Staff Training Needs List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }


    public function getStaffLicenses(Request $request)
    {
        try {
            $data = $this->workbenchService->getStaffLicenses($request);
            
            return $this->sendSuccessResponse("Staff Licenses List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }


    public function getTrainingCourses(Request $request)
    {
        try {
            $data = $this->workbenchService->getTrainingCourses($request);
            
            return $this->sendSuccessResponse("Training Courses List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }


    public function getTrainingSessions(Request $request)
    {
        try {
            $data = $this->workbenchService->getTrainingSessions($request);
            
            return $this->sendSuccessResponse("Training Sessions List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }


    public function getTrainingResults(Request $request)
    {
        try {
            $data = $this->workbenchService->getTrainingResults($request);
            
            return $this->sendSuccessResponse("Training Results List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }


    public function getVisitRequests(Request $request)
    {
        try {
            $data = $this->workbenchService->getVisitRequests($request);
            
            return $this->sendSuccessResponse("Visit Request List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }


    public function getTrainingApplications(Request $request)
    {
        try {
            $data = $this->workbenchService->getTrainingApplications($request);
            
            return $this->sendSuccessResponse("Training Applications List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }



    public function getScholarshipApplications(Request $request)
    {
        try {
            $data = $this->workbenchService->getScholarshipApplications($request);
            
            return $this->sendSuccessResponse("Scholarship Applications List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }


    public function getInstitutionCases(Request $request)
    {
        try {
            $data = $this->workbenchService->getInstitutionCases($request);
            
            return $this->sendSuccessResponse("Institution Cases List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }


    public function getInstitutionPositions(Request $request)
    {
        try {
            $data = $this->workbenchService->getInstitutionPositions($request);
            
            return $this->sendSuccessResponse("Institution Positions List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }


    public function getMinidashboardData(Request $request)
    {
        try {
            $data = $this->workbenchService->getMinidashboardData($request);
            
            return $this->sendSuccessResponse("Dashboard Data Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }
}
