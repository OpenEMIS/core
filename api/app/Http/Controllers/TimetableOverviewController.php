<?php

namespace App\Http\Controllers;

use App\Models\ConfigItem;
use App\Repositories\TimetableOverviewRepository;
use App\Services\TimetableOverviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;


//POCOR-8616
class TimetableOverviewController extends Controller
{

    protected $timetableOverviewService;

    public function __construct(TimetableOverviewService $timetableOverviewService) {
        $this->timetableOverviewService = $timetableOverviewService;
    }
    
    public function timetableOverview(Request $request)
    {
        try {
            $params = $request->all();

            $data = $this->timetableOverviewService->getTimetableOverview($params);
            if (!empty($data)) {
                return $this->sendSuccessResponse("Timetables Overview found.", $data);
            } else {
                return $this->sendErrorResponse("Timetables Overview not found.");
            }
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch timetables Overview.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Failed to fetch timetables Overview.',[], 500);
        }

    }

    

}