<?php

namespace App\Services;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\EducationGrades;
use Exception;
use Illuminate\Support\Facades\DB;
use JWTAuth;
use Illuminate\Support\Facades\Log;
use App\Repositories\ScannedRepository;
use App\Http\Requests\ScannedAttendanceRequest;
use Illuminate\Http\Request;

/**
 * POCOR-8666
 * ScannedService is responsible for handling the business logic related to scanned data.
 * It interacts with the repository layer to fetch, update, and manage scanned records.
 */
class ScannedService extends Controller
{

    protected $scannedRepository;

    /**
     * ScannedService constructor.
     *
     * @param ScannedRepository $scannedRepository The repository responsible for scanning data operations.
     */
    public function __construct(
    ScannedRepository $scannedRepository) {
        $this->scannedRepository = $scannedRepository;
    }

    public function addScannedUser(ScannedAttendanceRequest $request)
    {
        try {
            $data = $this->scannedRepository->saveScannedUserData($request);
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to save Scanned User data in db',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to save Scanned User data in db.');
        }
    }

    public function scannedOpenemisNo($params, Request $request)
    {
        try {
            $data = $this->scannedRepository->scannedOpenemisNo($params, $request);
            return $data;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Scanned User Data',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch Scanned User Data.');
        }
    }


}