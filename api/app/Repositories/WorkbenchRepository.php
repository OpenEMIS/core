<?php

namespace App\Repositories;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use JWTAuth;
use App\Models\AcademicPeriod;
use App\Models\Notice;
use App\Models\Workflows;
use App\Models\InstitutionStaffLeave;
use App\Models\SecurityGroupUsers;
use App\Models\InstitutionSurvey;
use App\Models\InstitutionStudentWithdraw;
use App\Models\InstitutionStudentAdmission;
use App\Models\InstitutionStudentTransfers;
use App\Models\StudentBehaviours;
use Illuminate\Support\Facades\DB;
use Mail;
use Illuminate\Support\Str;

class WorkbenchRepository extends Controller
{
    

    public function getNoticesList($request)
    {
        try {
            $params = $request->all();

            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $list = Notice::paginate($limit)->toArray();
            return $list;
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
            $param = $request->all();
            $assigneeId = JWTAuth::user()->id;

            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $list = InstitutionStaffLeave::with(
                        'institution:id,name', 
                        'staff',
                        'assignee',
                        'securityUser',
                        'status:id,name',
                        'staffLeaveType:id,name'
                    )
                    ->whereHas(
                        'status', function ($q) {
                            $q->where('workflow_id', 20) // For staff leave
                            ->where('category', '!=', 3); //For done status
                        }        
                    )
                    ->where('assignee_id', $assigneeId)
                    ->paginate($limit)
                    ->toArray();

            return $list;

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
            $params = $request->all();
            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }


            $userId = JWTAuth::user()->id;
            $roles = SecurityGroupUsers::where('security_user_id', $userId)->pluck('security_role_id');

            //dd($userId, $roles);
            
            $list = InstitutionSurvey::with(
                        'institution:id,name,code',
                        'assignee:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name',
                        'securityUser:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name',
                        'status:id,name,workflow_id',
                        'surveyForm:id,name',
                        'academicPeriod:id,name'
                    )
                    ->whereHas(
                        'status', function ($q) use($roles) {
                            $q->where('workflow_id', 1) //For institution survey
                            ->where('category', '!=', 3) //For done status
                            ->whereHas(
                                'workflowStepRole', function($query) use($roles) {
                                    $query->whereIn('security_role_id', $roles);
                                }
                            );
                        }        
                    )
                    ->where('assignee_id', $userId)
                    ->paginate($limit)
                    ->toArray();

            return $list;

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
            $params = $request->all();
            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $userId = JWTAuth::user()->id;

            $list = InstitutionStudentWithdraw::with(
                        'institution:id,name,code',
                        'assignee:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name',
                        'securityUser:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name',
                        'status:id,name,workflow_id',
                        'user:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name'
                    )
                    ->whereHas(
                        'status', function ($q) {
                            $q->where('workflow_id', 15) //For student withdraw.
                            ->where('category', '!=', 3);
                        }        
                    )
                    ->where('assignee_id', $userId)
                    ->paginate($limit)
                    ->toArray();

            
            return $list;

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
            $params = $request->all();
            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $userId = JWTAuth::user()->id;

            $list = InstitutionStudentAdmission::with(
                        'institution:id,name,code',
                        'assignee:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name',
                        'securityUser:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name',
                        'status:id,name,workflow_id',
                        'user:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name'
                    )
                    ->whereHas(
                        'status', function ($q) {
                            $q->where('workflow_id', 16) //For student admission.
                            ->where('category', '!=', 3);
                        }        
                    )
                    ->where('assignee_id', $userId)
                    ->paginate($limit)
                    ->toArray();

            
            return $list;

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
            $params = $request->all();
            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $userId = JWTAuth::user()->id;

            $list = InstitutionStudentTransfers::with(
                        'institution:id,name,code',
                        'previousInstitution:id,name,code',
                        'assignee:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name',
                        'securityUser:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name',
                        'status:id,name,workflow_id',
                        'user:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name'
                    )
                    ->whereHas(
                        'status', function ($q) {
                            $q->where('workflow_id', 18)//For transfer out.
                            ->where('category', '!=', 3)
                            ->whereHas(
                                'workflowStepParam', function($query){
                                    $query->where('name', 'institution_owner')
                                        ->where('value', 2); //for transfer out.
                                }
                            );
                        }        
                    )
                    ->where('assignee_id', $userId)
                    ->paginate($limit)
                    ->toArray();

            return $list;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }    


    public function getInstitutionStudentTransferIn(Request $request)
    {
        try {
            $params = $request->all();
            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $userId = JWTAuth::user()->id;

            $list = InstitutionStudentTransfers::with(
                        'institution:id,name,code',
                        'previousInstitution:id,name,code',
                        'assignee:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name',
                        'securityUser:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name',
                        'status:id,name,workflow_id',
                        'user:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name'
                    )
                    ->whereHas(
                        'status', function ($q) {
                            $q->where('workflow_id', 17)//For transfer out.
                            ->where('category', '!=', 3)
                            ->whereHas(
                                'workflowStepParam', function($query){
                                    $query->where('name', 'institution_owner')
                                        ->where('value', 1); //for transfer in.
                                }
                            );
                        }        
                    )
                    ->where('assignee_id', $userId)
                    ->paginate($limit)
                    ->toArray();

            return $list;
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
            $params = $request->all();
            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $userId = JWTAuth::user()->id;
            
            $list = StudentBehaviours::with(
                        'institution:id,name,code',
                        'assignee:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name',
                        'securityUser:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name',
                        'status:id,name,workflow_id',
                        'user:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name'
                    )
                    ->whereHas(
                        'status', function ($q) {
                            $q->where('workflow_id', 24)//For transfer out.
                            ->where('category', '!=', 3);
                        }        
                    )
                    ->where('assignee_id', $userId)
                    ->paginate($limit)
                    ->toArray();
            dd($list);
            return $list;

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to fetch list from DB');
        }
    }

}

