<?php

namespace App\Services;

use App\Http\Controllers\Controller;
use App\Repositories\AssessmentRepository;
use App\Repositories\SystemConfigurationRepository;
use JWTAuth;
use Illuminate\Support\Facades\Log;

class SystemConfigurationService extends Controller
{

    protected $configRepository;
    const DROP_DOWN_FIELD_TYPE = 'Dropdown';

    public function __construct(SystemConfigurationRepository $configRepository)
    {
        $this->configRepository = $configRepository;
    }

    public function getAllConfigurationItems()
    {
        $data = $this->configRepository->getAllConfigurationItems();

        return $data;

    }

    public function getConfigurationItemById($configId)
    {
        $data = $this->configRepository->getConfigurationItemById($configId);
        return $data;
    }
}