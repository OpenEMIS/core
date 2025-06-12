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

    /**
     * POCOR-8793
     * export institution scan data xlsx
     * * This method fetches the scanned attendance data for a specific OpenEMIS number, including related user information (created by and modified by users).
     * The data is retrieved from the `ScannedAttendance` model with relationships to the 'createdUser' and 'modifiedUser'.
     * 
     * @param array $params An associative array containing the `openemis_no` (OpenEMIS number) to filter the scanned data.
     * 
     */
    public function institutionScannedDataExport($params)
    {
        try {
            $data = $this->scannedRepository->institutionScannedDataExport($params);
            $resp = [];
            foreach($data as $key => $value){
                $resp[$key]['Openemis Id'] = $value['openemis_no'];
                $resp[$key]['DateTime'] = $value['datetime'];
                $resp[$key]['Latitude'] = $value['latitude'];
                $resp[$key]['Longitude'] = $value['longitude'];
                $resp[$key]['Access'] = $value['access'];
                $resp[$key]['Location'] = $value['location'];
                $resp[$key]['Modified User'] = isset($value['modifiedUser']) ? $value['modifiedUser']['first_name'] . ' ' . $value['modifiedUser']['last_name'] : 'N/A';
                $resp[$key]['Modified'] = $value['modified'];
                $resp[$key]['Created User'] = isset($value['createdUser']) ? $value['createdUser']['first_name'] . ' ' . $value['createdUser']['last_name'] : 'N/A';
                $resp[$key]['Created '] = $value['created'];
            }
            return $resp;

        } catch (\Exception $e) {
            Log::error(
                'Failed to export students Scanned data from DB.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to export students Scanned data from DB.');
        }
    }

    public function scannedUserListing(Request $request)
    {
        try {
            $data = $this->scannedRepository->scannedListing($request);
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Scanned User Data from db',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Failed to fetch Scanned User Data from db.');
        }
    }

    public function scannedUserDetails($scannedId)
    {
        try {
            $details = $this->scannedRepository->scanUserDetails($scannedId);
            return $details;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Scanned User Data from db',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch Scanned User Data from db');
        }
    }

}