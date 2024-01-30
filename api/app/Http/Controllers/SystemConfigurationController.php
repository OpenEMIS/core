<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SystemConfigurationService;
use Illuminate\Support\Facades\Log;

class SystemConfigurationController extends Controller
{
    protected $configService;

    public function __construct(SystemConfigurationService $configService)
    {
        $this->configService = $configService;
    }

    public function allConfigurationItems(Request $request)
    {
        try {
            $data = $this->configService->getAllConfigurationItems($request);
            return $this->sendSuccessResponse("System Configuration List Found", $data);

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch System Configuration List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('System Configuration List Not Found');
        }
    }

    public function configurationItemById($configId)
    {
        try {
            $data = $this->configService->getConfigurationItemById($configId);
            return $this->sendSuccessResponse("System Configuration Found", $data);

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch System Configuration List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('System Configuration Not Found');
        }
    }
}