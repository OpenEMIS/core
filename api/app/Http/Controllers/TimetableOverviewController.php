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

/**
 * @OA\Get(
 *      path="/api/v4/schedule/timetable-overview",
 *      summary="Get timetable overview by various filters",
 *      description="Retrieve a paginated list of timetables filtered by academic period, institution, class, and term.",
 *      tags={"Schedule"},
 *      @OA\Parameter(
 *          name="limit",
 *          in="query",
 *          required=false,
 *          description="Number of records per page",
 *          @OA\Schema(type="integer", example=10)
 *      ),
 *      @OA\Parameter(
 *          name="page",
 *          in="query",
 *          required=false,
 *          description="Page number",
 *          @OA\Schema(type="integer", example=1)
 *      ),
 *      @OA\Parameter(
 *          name="academic_period_id",
 *          in="query",
 *          required=true,
 *          description="ID of the academic period",
 *          @OA\Schema(type="integer", example=33)
 *      ),
 *      @OA\Parameter(
 *          name="institution_id",
 *          in="query",
 *          required=true,
 *          description="ID of the institution",
 *          @OA\Schema(type="integer", example=6)
 *      ),
 *      @OA\Parameter(
 *          name="institution_class_id",
 *          in="query",
 *          required=true,
 *          description="ID of the institution class",
 *          @OA\Schema(type="integer", example=591)
 *      ),
 *      @OA\Parameter(
 *          name="institution_schedule_term_id",
 *          in="query",
 *          required=true,
 *          description="ID of the schedule term",
 *          @OA\Schema(type="integer", example=3)
 *      ),
 *      @OA\Response(
 *          response=200,
 *          description="Successful.",
 *          @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Retrieved successfully."),
 *             @OA\Property(property="data", type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="timetable_id", type="integer", example=1),
 *                     @OA\Property(property="institution_id", type="integer", example=6),
 *                     @OA\Property(property="institution_class_id", type="integer", example=591),
 *                     @OA\Property(property="academic_period_id", type="integer", example=33),
 *                     @OA\Property(property="term_id", type="integer", example=3),
 *                     @OA\Property(property="timetable_details", type="string", example="Overview of timetable details here.")
 *                 )
 *             )
 *          )
 *      ),
 *      @OA\Response(
 *          response=404,
 *          description="Timetable overview not found."
 *      )
 * )
 */
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
/**
 * @OA\Get(
 *      path="/api/v4/schedule/timetable-download",
 *      summary="Download timetable by timetable ID",
 *      description="Retrieve timetable based on the provided timetable ID",
 *      tags={"Schedule"},
 *      @OA\Parameter(
 *         name="timetable_id",
 *         in="query",
 *         required=true,
 *         description="ID of the timetable to download",
 *         @OA\Schema(type="integer", example=6)
 *      ),
 *      @OA\Response(
 *          response=200,
 *          description="Successful.",
 *          @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Download successful."),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="url", type="string", example="https://example.com/timetable/6/download")
 *             )
 *          )
 *      ),
 *      @OA\Response(
 *          response=404,
 *          description="Timetable export not found."
 *      )
 * )
 */
    public function scheduleTimeTableExport(Request $request)
    {
        try {
            $params = $request->all();
            $str = time();
            $fileName = 'StudentTimeTable_'.$str.'.xlsx';
            return Excel::download(new ScheduleTimeTableExport($params), $fileName);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to export Schedule TimeTable from DB.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to export Schedule TimeTable from DB.');
        }
    }

}