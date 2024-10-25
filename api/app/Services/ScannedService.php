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

class ScannedService extends Controller
{

    protected $scannedRepository;

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

    public function updateScannedUser($scannedId, ScannedAttendanceRequest $request)
    {
        try {
            $data = $this->scannedRepository->updateScannedUserData($scannedId, $request);
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to Update Scanned User data in db',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to Update Scanned User data in db.');
        }
    }

    public function scannedUserListing(Request $request)
    {
        try {
            $data = $this->scannedRepository->scannedListing($request);
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to Update Scanned User data in db',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to Update Scanned User data in db.');
        }

    }


}