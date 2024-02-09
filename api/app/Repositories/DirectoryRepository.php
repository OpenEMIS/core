<?php

namespace App\Repositories;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\ConfigItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use JWTAuth;
use Illuminate\Support\Facades\DB;

class DirectoryRepository extends Controller
{

    public function getUserTypeList($request)
    {
        try {
            
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch User Type List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('User Type List Not Found');
        }
    }

}


        
