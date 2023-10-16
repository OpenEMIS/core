<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\EducationSystemService;
use Illuminate\Support\Facades\Log;

class EducationSystemController extends Controller
{
    protected $educationSystemService;

    public function __construct(
        EducationSystemService $educationSystemService
    ) {
        $this->educationSystemService = $educationSystemService;
    }


    public function getAllEducationSystems(Request $request)
    {
        try {
            $data = $this->educationSystemService->getAllEducationSystems($request);
            return $this->sendSuccessResponse("Education System List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Education System List Not Found');
        }
    }


    public function getEducationStructureSystems($systemId, Request $request)
    {
        try {
            $data = $this->educationSystemService->getEducationStructureSystems($systemId, $request);
            return $this->sendSuccessResponse("Education System List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Education System List Not Found');
        }
    }


    public function getEducationStructureLevel($systemId, $levelId, Request $request)
    {
        try {
            $data = $this->educationSystemService->getEducationStructureLevel($systemId, $levelId, $request);
            return $this->sendSuccessResponse("Education System List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Education System List Not Found');
        }
    }


    public function getEducationStructureCycle($systemId, $levelId, $cycleId, Request $request)
    {
        try {
            $data = $this->educationSystemService->getEducationStructureCycle($systemId, $levelId, $cycleId, $request);
            return $this->sendSuccessResponse("Education System List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Education System List Not Found');
        }
    }


    public function getEducationStructureProgramme($systemId, $levelId, $cycleId, $programmeId,  Request $request)
    {
        try {
            $data = $this->educationSystemService->getEducationStructureProgramme($systemId, $levelId, $cycleId, $programmeId, $request);
            return $this->sendSuccessResponse("Education System List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Education System List Not Found');
        }
    }


    public function getEducationStructureGrade($systemId, $levelId, $cycleId, $programmeId, $gradeId, Request $request)
    {
        try {
            $data = $this->educationSystemService->getEducationStructureGrade($systemId, $levelId, $cycleId, $programmeId, $gradeId, $request);
            return $this->sendSuccessResponse("Education System List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Education System List Not Found');
        }
    }


    public function getEducationStructureSubject($systemId, $levelId, $cycleId, $programmeId, $gradeId, $subjectId, Request $request)
    {
        try {
            $data = $this->educationSystemService->getEducationStructureSubject($systemId, $levelId, $cycleId, $programmeId, $gradeId, $subjectId, $request);
            return $this->sendSuccessResponse("Education System List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Education System List Not Found');
        }
    }


    public function reportCardLists($systemId, $levelId, $cycleId, $programmeId, $gradeId)
    {
        try {
            $data = $this->educationSystemService->reportCardLists($systemId, $levelId, $cycleId, $programmeId, $gradeId);
            
            return $this->sendSuccessResponse("Report Cards List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Report Cards List Not Found');
        }
    }



    public function getCompetencies($systemId, $levelId, $cycleId, $programmeId, $gradeId, Request $request)
    {
        try {
            $data = $this->educationSystemService->getCompetencies($systemId, $levelId, $cycleId, $programmeId, $gradeId, $request);
            
            return $this->sendSuccessResponse("Competencies List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Competencies List Not Found');
        }
    }
}
