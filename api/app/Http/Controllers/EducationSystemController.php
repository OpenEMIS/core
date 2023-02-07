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
}
