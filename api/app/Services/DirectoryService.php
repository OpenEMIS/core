<?php

namespace App\Services;

use App\Http\Controllers\Controller;
use App\Repositories\DirectoryRepository;
use JWTAuth;
use Illuminate\Support\Facades\Log;

class DirectoryService extends Controller
{

    protected $directoryRepository;

    public function __construct(DirectoryRepository $directoryRepository) 
    {
        $this->directoryRepository = $directoryRepository;
    }

    
    public function getUserTypeList($request)
    {
        try {
            $data = $this->directoryRepository->getUserTypeList($request);
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch User Type List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('User Type List Not Found');
        }
    }

}