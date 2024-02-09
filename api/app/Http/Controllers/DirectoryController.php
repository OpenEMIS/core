<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DirectoryService;
use Illuminate\Support\Facades\Log;

class DirectoryController extends Controller
{
    protected $directoryService;

    public function __construct(DirectoryService $directoryService) 
    {
        $this->directoryService = $directoryService;
    }


    public function getUserTypeList(Request $request)
    {
        try {
            $data = $this->directoryService->getUserTypeList($request);
            return $this->sendSuccessResponse("User Type List Found.", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch User Type List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('User Type List Not Found');
        }
    }
}
