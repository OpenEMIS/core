<?php

namespace App\Services;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\EducationGrades;
use Exception;
use Illuminate\Support\Facades\DB;
use JWTAuth;
use Illuminate\Support\Facades\Log;
use App\Repositories\DepartmentRepository;
use App\Http\Requests\ScannedAttendanceRequest;
use Illuminate\Http\Request;

/**
 * POCOR-8030
 * DepartmentService is responsible for handling the business logic related to scanned data.
 * It interacts with the repository layer to fetch, update, and manage scanned records.
 */
class DepartmentService extends Controller
{

    protected $departmentRepository;

    /**
     * ScannedService constructor.
     *
     * @param DepartmentRepository $departmentRepository The repository responsible for scanning data operations.
     */
    public function __construct(
    DepartmentRepository $departmentRepository) {
        $this->departmentRepository = $departmentRepository;
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

    public function getDepartmentListing($params, $institutionId)
    {
        try {
            $data = $this->departmentRepository->getDepartmentList($params, $institutionId);
            $list = [];
            if(count($data['data']) > 0){
                foreach($data['data'] as $k => $d){
                    $list[$k]['id'] = $d['id'];
                    $list[$k]['Department Name'] = $d['name'];
                    $list[$k]['code'] = $d['code'];
                    $list[$k]['manager_id'] = $d['manager_id'];
                    $list[$k]['manager_name'] = $d['department_manager']['first_name'].' '. $d['department_manager']['last_name'];
                    $list[$k]['institution_id'] = $d['institution_id'];
                    $list[$k]['institution_name'] = $d['institution']['name'];
                    $list[$k]['institution_code'] = $d['institution']['code'];
                }
            }
            $data['data'] = $list;
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