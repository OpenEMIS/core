<?php

namespace App\Services;

use App\Http\Controllers\Controller;
use App\Repositories\WorkbenchRepository;
use JWTAuth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class WorkbenchService extends Controller
{

    protected $workbenchRepository;

    public function __construct(
    WorkbenchRepository $workbenchRepository) {
        $this->workbenchRepository = $workbenchRepository;
    }


    public function getNoticesList($request)
    {
        try {
            $data = $this->workbenchRepository->getNoticesList($request);
            
            return $data;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }


    public function getInstitutionStaffLeave($request)
    {
        try {
            $data = $this->workbenchRepository->getInstitutionStaffLeave($request);
            $resp = [];

            foreach($data['data'] as $k=> $d){
                $resp[$k]['id'] = $d['id'];
                $resp[$k]['institution_id'] = $d['institution_id'];
                $resp[$k]['institution'] = $d['institution']['name'];
                $resp[$k]['request_title'] = $d['staff_leave_type']['name']. ' of ' .$d['staff']['name_with_id'];
                
                if(!is_null($d['modified'])){
                    $date = $d['modified'];
                } else {
                    $date = $d['created'];
                }
                $resp[$k]['received_date'] = Carbon::create($date)->toFormattedDateString();

                $resp[$k]['requester'] = $d['security_user']['name_with_id'];
                $resp[$k]['staff_id'] = $d['staff_id'];
                $resp[$k]['status_id'] = $d['status_id'];
                $resp[$k]['status'] = $d['status']['name'];
                $resp[$k]['staff_leave_type'] = $d['staff_leave_type'];
                $resp[$k]['user'] = $d['staff'];
                $resp[$k]['created_user'] = $d['security_user'];
            }

            $data['data'] = $resp; 
            
            return $data;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }


    public function getInstitutionStaffSurveys($request)
    {
        try {
            $data = $this->workbenchRepository->getInstitutionStaffSurveys($request);
            
            $resp = [];

            foreach($data['data'] as $k=> $d){
                $resp[$k]['id'] = $d['id'];
                $resp[$k]['institution_id'] = $d['institution_id'];
                $resp[$k]['institution'] = $d['institution']['name'];
                $resp[$k]['request_title'] = $d['survey_form']['name']. ' of ' .$d['academic_period']['name'];
                    
                if(!is_null($d['modified'])){
                    $date = $d['modified'];
                } else {
                    $date = $d['created'];
                }
                $resp[$k]['received_date'] = Carbon::create($date)->toFormattedDateString();

                $resp[$k]['requester'] = $d['security_user']['name_with_id'];
                $resp[$k]['status_id'] = $d['status_id'];
                $resp[$k]['status'] = $d['status']['name'];
                $resp[$k]['survey_form'] = $d['survey_form'];
                $resp[$k]['academic_period'] = $d['academic_period'];
                $resp[$k]['created_user'] = $d['security_user'];

            }

            $data['data'] = $resp; 
            
            return $data;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }


    public function getInstitutionStudentWithdraw($request)
    {
        try {
            $data = $this->workbenchRepository->getInstitutionStudentWithdraw($request);
            
            $resp = [];

            foreach($data['data'] as $k=> $d){
                $resp[$k]['id'] = $d['id'];
                $resp[$k]['institution_id'] = $d['institution_id'];
                $resp[$k]['institution'] = $d['institution']['name'];
                $resp[$k]['request_title'] = 'Withdraw request of ' .$d['user']['name_with_id'];
                
                if(!is_null($d['modified'])){
                    $date = $d['modified'];
                } else {
                    $date = $d['created'];
                }
                $resp[$k]['received_date'] = Carbon::create($date)->toFormattedDateString();

                $resp[$k]['requester'] = $d['security_user']['name_with_id'];
                $resp[$k]['status_id'] = $d['status_id'];
                $resp[$k]['status'] = $d['status']['name'];
                $resp[$k]['user'] = $d['user'];
                $resp[$k]['created_user'] = $d['security_user'];

            }

            $data['data'] = $resp; 
            
            return $data;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }


    public function getInstitutionStudentAdmission($request)
    {
        try {
            $data = $this->workbenchRepository->getInstitutionStudentAdmission($request);
            
            $resp = [];

            foreach($data['data'] as $k=> $d){
                $resp[$k]['id'] = $d['id'];
                $resp[$k]['institution_id'] = $d['institution_id'];
                $resp[$k]['institution'] = $d['institution']['name'];
                $resp[$k]['request_title'] = 'Admission of student ' .$d['user']['name_with_id'];
                
                if(!is_null($d['modified'])){
                    $date = $d['modified'];
                } else {
                    $date = $d['created'];
                }
                $resp[$k]['received_date'] = Carbon::create($date)->toFormattedDateString();

                $resp[$k]['requester'] = $d['security_user']['name_with_id'];
                $resp[$k]['status_id'] = $d['status_id'];
                $resp[$k]['status'] = $d['status']['name'];
                $resp[$k]['user'] = $d['user'];
                $resp[$k]['created_user'] = $d['security_user'];

            }

            $data['data'] = $resp; 
            
            return $data;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }



    public function getInstitutionStudentTransferOut($request)
    {
        try {
            $data = $this->workbenchRepository->getInstitutionStudentTransferOut($request);
            
            $resp = [];

            foreach($data['data'] as $k=> $d){
                $resp[$k]['id'] = $d['id'];
                $resp[$k]['institution_id'] = $d['institution_id'];
                $resp[$k]['institution'] = $d['previous_institution']['code_name'];
                $resp[$k]['previous_institution'] = $d['previous_institution'];
                $resp[$k]['previous_institution_id'] = $d['previous_institution']['id'];
                $resp[$k]['request_title'] = 'Transfer of student ' .$d['user']['name_with_id']. ' to '.$d['institution']['code_name'];
                
                if(!is_null($d['modified'])){
                    $date = $d['modified'];
                } else {
                    $date = $d['created'];
                }
                $resp[$k]['received_date'] = Carbon::create($date)->toFormattedDateString();

                $resp[$k]['requester'] = $d['security_user']['name_with_id'];
                $resp[$k]['status_id'] = $d['status_id'];
                $resp[$k]['status'] = $d['status']['name'];
                $resp[$k]['user'] = $d['user'];
                $resp[$k]['created_user'] = $d['security_user'];

            }

            $data['data'] = $resp; 
            
            return $data;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }


    public function getInstitutionStudentTransferIn($request)
    {
        try {
            $data = $this->workbenchRepository->getInstitutionStudentTransferIn($request);
            
            $resp = [];

            foreach($data['data'] as $k=> $d){
                $resp[$k]['id'] = $d['id'];
                $resp[$k]['institution_id'] = $d['institution_id'];
                $resp[$k]['institution'] = $d['institution']['code_name'];
                $resp[$k]['previous_institution'] = $d['previous_institution'];
                $resp[$k]['previous_institution_id'] = $d['previous_institution']['id'];
                $resp[$k]['request_title'] = 'Transfer of student ' .$d['user']['name_with_id']. ' from '.$d['previous_institution']['code_name'];

                if(!is_null($d['modified'])){
                    $date = $d['modified'];
                } else {
                    $date = $d['created'];
                }
                $resp[$k]['received_date'] = Carbon::create($date)->toFormattedDateString();
                $resp[$k]['requester'] = $d['security_user']['name_with_id'];
                $resp[$k]['status_id'] = $d['status_id'];
                $resp[$k]['status'] = $d['status']['name'];
                $resp[$k]['user'] = $d['user'];
                $resp[$k]['created_user'] = $d['security_user'];

            }

            $data['data'] = $resp; 
            
            return $data;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }



    public function getInstitutionStudentBehaviour($request)
    {
        try {
            $data = $this->workbenchRepository->getInstitutionStudentBehaviour($request);
            
            $resp = [];

            foreach($data['data'] as $k=> $d){
                $resp[$k]['id'] = $d['id'];
                $resp[$k]['institution_id'] = $d['institution_id'];
                $resp[$k]['institution'] = $d['institution']['code_name'];
                $resp[$k]['request_title'] = 'Behavour request of ' .$d['user']['name_with_id'];

                if(!is_null($d['modified'])){
                    $date = $d['modified'];
                } else {
                    $date = $d['created'];
                }
                $resp[$k]['received_date'] = Carbon::create($date)->toFormattedDateString();
                $resp[$k]['requester'] = $d['security_user']['name_with_id'];
                $resp[$k]['status_id'] = $d['status_id'];
                $resp[$k]['status'] = $d['status']['name'];
                $resp[$k]['student'] = $d['user'];
                $resp[$k]['created_user'] = $d['security_user'];

            }

            $data['data'] = $resp; 
            
            return $data;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }



    public function getInstitutionStaffBehaviour($request)
    {
        try {
            $data = $this->workbenchRepository->getInstitutionStaffBehaviour($request);
            
            $resp = [];

            foreach($data['data'] as $k=> $d){
                $resp[$k]['id'] = $d['id'];
                $resp[$k]['institution_id'] = $d['institution_id'];
                $resp[$k]['institution'] = $d['institution']['code_name'];
                $resp[$k]['request_title'] = 'Behavour request of ' .$d['user']['name_with_id'];

                if(!is_null($d['modified'])){
                    $date = $d['modified'];
                } else {
                    $date = $d['created'];
                }
                $resp[$k]['received_date'] = Carbon::create($date)->toFormattedDateString();
                $resp[$k]['requester'] = $d['security_user']['name_with_id'];
                $resp[$k]['status_id'] = $d['status_id'];
                $resp[$k]['status'] = $d['status']['name'];
                $resp[$k]['staff'] = $d['user'];
                $resp[$k]['created_user'] = $d['security_user'];

            }

            $data['data'] = $resp; 
            
            return $data;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }


    public function getStaffAppraisals($request)
    {
        try {
            $data = $this->workbenchRepository->getStaffAppraisals($request);
            
            $resp = [];

            foreach($data['data'] as $k=> $d){
                $resp[$k]['id'] = $d['id'];
                $resp[$k]['institution_id'] = $d['institution_id'];
                $resp[$k]['institution'] = $d['institution']['code_name'];

                $resp[$k]['request_title'] = $d['appraisal_form']['name']. '('.$d['appraisal_type']['name'].') for '.$d['user']['name_with_id']. ' in '.$d['appraisal_period']['name'];

                if(!is_null($d['modified'])){
                    $date = $d['modified'];
                } else {
                    $date = $d['created'];
                }
                $resp[$k]['received_date'] = Carbon::create($date)->toFormattedDateString();
                $resp[$k]['requester'] = $d['security_user']['name_with_id'];
                $resp[$k]['status_id'] = $d['status_id'];
                $resp[$k]['status'] = $d['status']['name'];
                $resp[$k]['user'] = $d['user'];
                $resp[$k]['created_user'] = $d['security_user'];
                $resp[$k]['appraisal_form'] = $d['appraisal_form'];
                $resp[$k]['appraisal_type'] = $d['appraisal_type'];
                $resp[$k]['appraisal_period'] = $d['appraisal_period'];

            }
            
            $data['data'] = $resp; 
            
            return $data;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }


    public function getStaffRelease($request)
    {
        try {
            $data = $this->workbenchRepository->getStaffRelease($request);
            
            $resp = [];

            foreach($data['data'] as $k=> $d){
                $resp[$k]['id'] = $d['id'];
                $resp[$k]['institution_id'] = $d['previous_institution_id'];
                $resp[$k]['institution'] = $d['previous_institution']['code_name'];

                $resp[$k]['new_institution'] = $d['new_institution'];
                $resp[$k]['new_institution_id'] = $d['new_institution_id'];

                $resp[$k]['previous_institution'] = $d['previous_institution'];
                $resp[$k]['previous_institution_id'] = $d['previous_institution_id'];

                $resp[$k]['request_title'] = $d['user']['name_with_id'].' to '.$d['new_institution']['code_name'];

                if(!is_null($d['modified'])){
                    $date = $d['modified'];
                } else {
                    $date = $d['created'];
                }
                $resp[$k]['received_date'] = Carbon::create($date)->toFormattedDateString();
                $resp[$k]['requester'] = $d['security_user']['name_with_id'];
                $resp[$k]['status_id'] = $d['status_id'];
                $resp[$k]['status'] = $d['status']['name'];
                $resp[$k]['user'] = $d['user'];
                $resp[$k]['created_user'] = $d['security_user'];

            }
            
            $data['data'] = $resp; 
            
            return $data;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }


    public function getStaffTransferOut($request)
    {
        try {
            $data = $this->workbenchRepository->getStaffTransferOut($request);
            
            $resp = [];

            foreach($data['data'] as $k=> $d){
                $resp[$k]['id'] = $d['id'];
                $resp[$k]['institution_id'] = $d['previous_institution_id'];
                $resp[$k]['institution'] = $d['previous_institution']['code_name'];

                $resp[$k]['new_institution'] = $d['new_institution'];
                $resp[$k]['new_institution_id'] = $d['new_institution_id'];

                $resp[$k]['previous_institution'] = $d['previous_institution'];
                $resp[$k]['previous_institution_id'] = $d['previous_institution_id'];

                $resp[$k]['request_title'] = $d['user']['name_with_id'].' to '.$d['new_institution']['code_name'];

                if(!is_null($d['modified'])){
                    $date = $d['modified'];
                } else {
                    $date = $d['created'];
                }
                $resp[$k]['received_date'] = Carbon::create($date)->toFormattedDateString();
                $resp[$k]['requester'] = $d['security_user']['name_with_id'];
                $resp[$k]['status_id'] = $d['status_id'];
                $resp[$k]['status'] = $d['status']['name'];
                $resp[$k]['user'] = $d['user'];
                $resp[$k]['created_user'] = $d['security_user'];

            }
            
            $data['data'] = $resp; 
            
            return $data;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }



    public function getStaffTransferIn($request)
    {
        try {
            $data = $this->workbenchRepository->getStaffTransferIn($request);
            
            $resp = [];

            foreach($data['data'] as $k=> $d){
                $resp[$k]['id'] = $d['id'];
                $resp[$k]['institution_id'] = $d['new_institution_id'];
                $resp[$k]['institution'] = $d['new_institution']['code_name'];

                $resp[$k]['new_institution'] = $d['new_institution'];
                $resp[$k]['new_institution_id'] = $d['new_institution_id'];

                $resp[$k]['previous_institution'] = $d['previous_institution'];
                $resp[$k]['previous_institution_id'] = $d['previous_institution_id'];

                $resp[$k]['request_title'] = $d['user']['name_with_id'].' from '.$d['previous_institution']['code_name'];

                if(!is_null($d['modified'])){
                    $date = $d['modified'];
                } else {
                    $date = $d['created'];
                }
                $resp[$k]['received_date'] = Carbon::create($date)->toFormattedDateString();
                $resp[$k]['requester'] = $d['security_user']['name_with_id'];
                $resp[$k]['status_id'] = $d['status_id'];
                $resp[$k]['status'] = $d['status']['name'];
                $resp[$k]['user'] = $d['user'];
                $resp[$k]['created_user'] = $d['security_user'];

            }
            
            $data['data'] = $resp; 
            
            return $data;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }



    public function getChangeInAssignment($request)
    {
        try {
            $data = $this->workbenchRepository->getChangeInAssignment($request);
            
            $resp = [];

            foreach($data['data'] as $k=> $d){
                $resp[$k]['id'] = $d['id'];
                $resp[$k]['institution_id'] = $d['institution_id'];
                $resp[$k]['institution'] = $d['institution']['code_name'];

                $resp[$k]['request_title'] = $d['user']['name_with_id'];

                if(!is_null($d['modified'])){
                    $date = $d['modified'];
                } else {
                    $date = $d['created'];
                }
                $resp[$k]['received_date'] = Carbon::create($date)->toFormattedDateString();
                $resp[$k]['requester'] = $d['user']['name_with_id'];
                $resp[$k]['status_id'] = $d['status_id'];
                $resp[$k]['status'] = $d['status']['name'];
                $resp[$k]['user'] = $d['user'];
                $resp[$k]['created_user'] = $d['security_user'];

            }
            
            $data['data'] = $resp; 
            
            return $data;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }



    public function getStaffTrainingNeeds($request)
    {
        try {
            $data = $this->workbenchRepository->getStaffTrainingNeeds($request);
            
            $resp = [];

            foreach($data['data'] as $k=> $d){
                $preTitle = '';
                $type = '';

                if($d['type'] == 'CATALOGUE'){
                    $type = 'Course '. ucfirst($d['type']);
                    $preTitle = $d['training_course']['code_name']??"";
                } elseif($d['type'] == 'NEED'){
                    $type = ucfirst($d['type']). " Category";
                    $preTitle = $d['training_need_category']['name']??"";
                }
                
                $resp[$k]['id'] = $d['id'];

                $resp[$k]['request_title'] = $preTitle.' from '. $type. ' of '.$d['user']['name_with_id'];

                if(!is_null($d['modified'])){
                    $date = $d['modified'];
                } else {
                    $date = $d['created'];
                }
                $resp[$k]['received_date'] = Carbon::create($date)->toFormattedDateString();

                $resp[$k]['requester'] = $d['user']['name_with_id'];

                $resp[$k]['training_course'] = $d['training_course'];
                $resp[$k]['training_course_id'] = $d['training_course_id'];

                $resp[$k]['training_need_category'] = $d['training_need_category'];
                $resp[$k]['training_need_category_id'] = $d['training_need_category_id'];

                $resp[$k]['type'] = $d['type'];
                $resp[$k]['status_id'] = $d['status_id'];
                $resp[$k]['status'] = $d['status']['name'];
                $resp[$k]['staff'] = $d['user'];
                $resp[$k]['created_user'] = $d['security_user'];

            }
            
            $data['data'] = $resp; 
            
            return $data;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }
}
