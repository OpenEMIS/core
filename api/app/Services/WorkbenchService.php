<?php

namespace App\Services;

use App\Http\Controllers\Controller;
use App\Repositories\WorkbenchRepository;
use JWTAuth;
use Illuminate\Support\Facades\Log;

class WorkbenchService extends Controller
{

    protected $workbenchRepository;

    public function __construct(
    WorkbenchRepository $workbenchRepository) {
        $this->workbenchRepository = $workbenchRepository;
    }


    public function getNoticesList($request)
    {
        try {
            $data = $this->workbenchRepository->getNoticesList($request);
            
            return $data;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }



    public function getWorkbenchList($request)
    {
        try {
            $data = $this->workbenchRepository->getWorkbenchList($request);
            
            return $data;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }
}
