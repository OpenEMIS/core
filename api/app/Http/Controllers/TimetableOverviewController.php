<?php

namespace App\Http\Controllers;

use App\Models\ConfigItem;
use App\Repositories\TimetableOverviewRepository;
use App\Services\TimetableOverviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Exports\ScheduleTimeTableExport;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

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
                'Failed to fetch timetables Overview Data.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Failed to fetch Timetables Overview Data.',[], 500);
        }
    }

    public function scheduleTimeTableExport(Request $request)
    {
        try {
            $params = $request->all();
            $data = $this->timetableOverviewService->getScheduleTimeTableExportData($params);
            
            /*$str = time();
            $fileName = 'ScheduleTimetable_'.$str.'.xlsx';
            return Excel::download(new ScheduleTimeTableExport($data), $fileName);*/
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to export Schedule TimeTable from DB.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to export Schedule TimeTable from DB.');
        }
    }

}