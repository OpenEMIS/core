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
use App\Http\Requests\InstitutionDepartmentRequest;
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

    public function institutionDepartmentDetails($institutionId,$departmentId,Request $request)
    {
        try {
            $data = $this->departmentRepository->institutionDepartmentViewDetails($institutionId,$departmentId,$request);
            $list = [];
            if(!empty($data)){
                foreach($data as $k => $d){
                    $list[$k]['id'] = $d['id'];
                    $list[$k]['Department Name'] = $d['name'];
                    $list[$k]['code'] = $d['code'];
                    $list[$k]['manager_id'] = $d['manager_id'];
                    $list[$k]['manager_name'] = isset($d['departmentManager']) && $d['departmentManager'] ? $d['departmentManager']['first_name'] . ' ' . $d['departmentManager']['last_name']
                                : 'N/A'; 
                    $list[$k]['staff_id'] = $d['staff_id'];
                    $list[$k]['staff_name'] = isset($d['securityUser']) && $d['securityUser'] ? $d['securityUser']['first_name'] . ' ' . $d['securityUser']['last_name']: 'N/A'; 
                    $list[$k]['institution_id'] = $d['institution_id'];
                    $list[$k]['institution_name'] = $d['institution']['name'];
                    $list[$k]['institution_code'] = $d['institution']['code'];
                }
            }
            return $list;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Scanned User Data',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch Scanned User Data.');
        }
    }

    public function addInstitutionDepartment($request)
    {
        try {
            $data = $this->departmentRepository->saveInstitutionDepartment($request);
            return $data;
        } catch (\Exception $e) {
            Log::error(
                'Failed to save Department data in db',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Failed to save Department data in db.');
        }
    }

    public function updateInstitutionDepartment($departmentId,$request)
    {
        try {
            $data = $this->departmentRepository->institutionDepartmentUpdate($departmentId, $request);
            return $data;
        } catch (\Exception $e) {
            Log::error(
                'Failed to update Department data in db',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Failed to update Department data in db.');
        }
    }
    public function departmentEditMessage()
    {
        try {
            $data = $this->departmentRepository->institutionDepartmentEditMessage();
           if($data->value == 'Enable'){
                $status = $data->value;
                return ['Status' => $status, 'message' => ''];
           }else{
                $status = $data->value;
                return ['Status' => $status, 'message' => 'Staff is already assigned to another department'];
           }
        } catch (\Exception $e) {
            Log::error(
                'Failed to update Department data in db',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Failed to update Department data in db.');
        }
    }

}