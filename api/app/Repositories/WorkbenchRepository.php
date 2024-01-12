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
use App\Models\StaffBehaviour;
use App\Models\InstitutionStaffAppraisal;
use App\Models\InstitutionStaffRelease;
use App\Models\InstitutionStaffTransfers;
use App\Models\InstitutionStaffPositionProfile;
use App\Models\StaffTrainingNeed;
use App\Models\StaffLicense;
use App\Models\TrainingCourse;
use App\Models\TrainingSession;
use App\Models\TrainingSessionResult;
use App\Models\InstitutionVisitRequest;
use App\Models\StaffTrainingApplication;
use App\Models\ScholarshipApplicaton;
use App\Models\InstitutionCase;
use App\Models\InstitutionPositions;
use App\Models\SecurityUsers;
use App\Models\UserDemographic;
use App\Models\UserIdentities;
use App\Models\UserNationalities;
use App\Models\UserContacts;
use App\Models\UserLanguage;
use App\Models\ConfigItem;
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
                        'institution:id,name,code', 
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

            /*$list = DB::table('institution_student_transfers')->with(
                        'institution:id,name,code',
                        'previousInstitution:id,name,code',
                        'assignee:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name',
                        'securityUser:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name',
                        'status:id,name,workflow_id',
                        'user:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name'
                    )*/
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
                            $q->where('category', '!=', 3)
                            //->where('workflow_id', 18)//For transfer out.
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

            /*$list = DB::table('institution_student_transfers')->with(
                        'institution:id,name,code',
                        'previousInstitution:id,name,code',
                        'assignee:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name',
                        'securityUser:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name',
                        'status:id,name,workflow_id',
                        'user:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name'
                    )*/
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
                            $q->where('category', '!=', 3)
                            //->where('workflow_id', 17)//For transfer in.
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
                            $q->where('workflow_id', 24)//For student behaviour.
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


    public function getInstitutionStaffBehaviour($request)
    {
        try {
            $params = $request->all();
            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $userId = JWTAuth::user()->id;
            
            $list = StaffBehaviour::with(
                        'institution:id,name,code',
                        'assignee:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name',
                        'securityUser:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name',
                        'status:id,name,workflow_id',
                        'user:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name'
                    )
                    ->whereHas(
                        'status', function ($q) {
                            $q->where('workflow_id', 25)//For staff behaviour.
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


    public function getStaffAppraisals($request)
    {
        try {
            $params = $request->all();
            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $userId = JWTAuth::user()->id;

            $list = InstitutionStaffAppraisal::with(
                        'institution:id,name,code',
                        'assignee:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name',
                        'securityUser:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name',
                        'status:id,name,workflow_id',
                        'user:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name',
                        'appraisalType:id,name',
                        'appraisalPeriod:id,name',
                        'appraisalForm:id,name,code',
                    )
                    ->whereHas(
                        'status', function ($q) {
                            $q->where('workflow_id', 19)//For staff appraisal.
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


    public function getStaffRelease($request)
    {
        try {
            $params = $request->all();
            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $userId = JWTAuth::user()->id;


            $list = InstitutionStaffRelease::with(
                        'newInstitution:id,name,code',
                        'previousInstitution:id,name,code',
                        'assignee:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name',
                        'securityUser:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name',
                        'status:id,name,workflow_id',
                        'user:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name'
                    )
                    ->whereHas(
                        'status', function ($q) {
                            $q->where('workflow_id', 23)//For staff release.
                            ->where('category', '!=', 3)
                            ->whereHas(
                                'workflowStepParam', function($query){
                                    $query->where('name', 'institution_owner')
                                        ->where('value', 2);
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



    public function getStaffTransferOut($request)
    {
        try {
            $params = $request->all();
            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $userId = JWTAuth::user()->id;


            $list = InstitutionStaffTransfers::with(
                        'newInstitution:id,name,code',
                        'previousInstitution:id,name,code',
                        'assignee:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name',
                        'securityUser:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name',
                        'status:id,name,workflow_id',
                        'user:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name'
                    )
                    ->whereHas(
                        'status', function ($q) {
                            $q->where('workflow_id', 14)//For staff transfer out.
                            ->where('category', '!=', 3)
                            ->whereHas(
                                'workflowStepParam', function($query){
                                    $query->where('name', 'institution_owner')
                                        ->where('value', 2);
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



    public function getStaffTransferIn($request)
    {
        try {
            $params = $request->all();
            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $userId = JWTAuth::user()->id;


            $list = InstitutionStaffTransfers::with(
                        'newInstitution:id,name,code',
                        'previousInstitution:id,name,code',
                        'assignee:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name',
                        'securityUser:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name',
                        'status:id,name,workflow_id',
                        'user:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name'
                    )
                    ->whereHas(
                        'status', function ($q) {
                            $q->where('workflow_id', 13)//For staff transfer out.
                            ->where('category', '!=', 3)
                            ->whereHas(
                                'workflowStepParam', function($query){
                                    $query->where('name', 'institution_owner')
                                        ->where('value', 1);
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


    public function getChangeInAssignment($request)
    {
        try {
            $params = $request->all();
            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $userId = JWTAuth::user()->id;

            $list = InstitutionStaffPositionProfile::with(
                        'institution:id,name,code',
                        'assignee:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name',
                        'securityUser:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name',
                        'status:id,name,workflow_id',
                        'user:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name'
                    )
                    ->whereHas(
                        'status', function ($q) {
                            $q->where('workflow_id', 7)//For change in assignment.
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


    public function getStaffTrainingNeeds($request)
    {
        try {
            $params = $request->all();
            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $userId = JWTAuth::user()->id;

            $list = StaffTrainingNeed::with(
                        'assignee:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name',
                        'securityUser:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name',
                        'status:id,name,workflow_id',
                        'user:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name',
                        'trainingCourse:id,name,code',
                        'trainingNeedCategory:id,name'
                    )
                    ->whereHas(
                        'status', function ($q) {
                            $q->where('workflow_id', 5)//For staff training needs.
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


    public function getStaffLicenses($request)
    {
        try {
            $params = $request->all();
            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $userId = JWTAuth::user()->id;

            $list = StaffLicense::with(
                        'assignee:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name',
                        'securityUser:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name',
                        'status:id,name,workflow_id',
                        'user:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name',
                        'licenseType:id,name'
                    )
                    ->whereHas(
                        'status', function ($q) {
                            $q->where('workflow_id', 11)//For staff license.
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



    public function getTrainingCourses($request)
    {
        try {
            $params = $request->all();
            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $userId = JWTAuth::user()->id;

            $list = TrainingCourse::with(
                        'assignee:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name',
                        'securityUser:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name',
                        'status:id,name,workflow_id'
                    )
                    ->whereHas(
                        'status', function ($q) {
                            $q->where('workflow_id', 2)//For training courses.
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


    public function getTrainingSessions($request)
    {
        try {
            $params = $request->all();
            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $userId = JWTAuth::user()->id;

            $list = TrainingSession::with(
                        'assignee:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name',
                        'securityUser:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name',
                        'status:id,name,workflow_id'
                    )
                    ->whereHas(
                        'status', function ($q) {
                            $q->where('workflow_id', 3)//For training sessions.
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


    public function getTrainingResults($request)
    {
        try {
            $params = $request->all();
            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $userId = JWTAuth::user()->id;

            $list = TrainingSessionResult::with(
                        'assignee:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name',
                        'securityUser:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name',
                        'status:id,name,workflow_id',
                        'trainingSession:id,code,name'
                    )
                    ->whereHas(
                        'status', function ($q) {
                            $q->where('workflow_id', 4)//For training results.
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


    public function getVisitRequests($request)
    {
        try {
            $params = $request->all();
            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $userId = JWTAuth::user()->id;

            $list = InstitutionVisitRequest::with(
                        'institution:id,name,code',
                        'assignee:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name',
                        'securityUser:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name',
                        'status:id,name,workflow_id',
                        'qualityVisitType:id,name',
                        'academicPeriod:id,name'
                    )
                    ->whereHas(
                        'status', function ($q) {
                            $q->where('workflow_id', 9)//For visit request.
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



    public function getTrainingApplications($request)
    {
        try {
            $params = $request->all();
            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $userId = JWTAuth::user()->id;

            $list = StaffTrainingApplication::with(
                        'institution:id,name,code',
                        'assignee:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name',
                        'securityUser:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name',
                        'staff:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name',
                        'status:id,name,workflow_id',
                        'session:id,code,name,training_course_id',
                        'session.course:id,code,name'
                    )
                    ->whereHas(
                        'status', function ($q) {
                            $q->where('workflow_id', 8)//For training application.
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



    public function getScholarshipApplications($request)
    {
        try {
            $params = $request->all();
            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $userId = JWTAuth::user()->id;

            $list = ScholarshipApplicaton::with(
                        'institution:id,name,code',
                        'assignee:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name',
                        'securityUser:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name',
                        'applicant:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name',
                        'status:id,name,workflow_id',
                        'scholarship:id,name,code',
                    )
                    ->whereHas(
                        'status', function ($q) {
                            $q->where('workflow_id', 21)//For scholarship application.
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


    public function getInstitutionCases($request)
    {
        try {
            $params = $request->all();
            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $userId = JWTAuth::user()->id;

            $list = InstitutionCase::with(
                        'institution:id,name,code',
                        'assignee:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name',
                        'securityUser:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name',
                        'status:id,name,workflow_id',
                    )
                    ->whereHas(
                        'status', function ($q) {
                            $q->where('workflow_id', 12)//For institution case.
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



    public function getInstitutionPositions($request)
    {
        try {
            $params = $request->all();
            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $userId = JWTAuth::user()->id;

            $list = InstitutionPositions::with(
                        'institution:id,name,code',
                        'assignee:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name',
                        'securityUser:id,openemis_no,first_name,middle_name,third_name,last_name,preferred_name',
                        'status:id,name,workflow_id',
                        'staffPositionTitle:id,name'
                    )
                    ->whereHas(
                        'status', function ($q) {
                            $q->where('workflow_id', 6)//For institution positions.
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


    public function getMinidashboardData($request)
    {
        try {
            $userId = JWTAuth::user()->id;
            $data = [];
            $profileComplete = 0;

            $securityUsersData = SecurityUsers::select('id', 'created', 'modified')->where('id', $userId)->first();


            $userDemographicsData = UserDemographic::select('id', 'created', 'modified')->where('security_user_id', $userId)->orderBy('modified', 'DESC')->first();


            $userIdentitiesData = UserIdentities::select('id', 'created', 'modified')->where('security_user_id', $userId)->orderBy('modified', 'DESC')->first();


            $userNationalitiesData = UserNationalities::select('id', 'created', 'modified')->where('security_user_id', $userId)->orderBy('modified', 'DESC')->first();


            $userContactsData = UserContacts::select('id', 'created', 'modified')->where('security_user_id', $userId)->orderBy('modified', 'DESC')->first();


            $userLanguagesData = UserLanguage::select('id', 'created', 'modified')->where('security_user_id', $userId)->orderBy('modified', 'DESC')->first();


            $configItems = ConfigItem::where('visible', 1)->where('value', 1)->where('type', 'User Data Completeness')->get()->toArray();

            

            foreach ($configItems as $key => $configItem) {
                $data[$key]['feature'] = $configItem['name'];

                if($configItem['name'] == 'Overview'){
                    if (!empty($securityUsersData)) {
                        $profileComplete = $profileComplete + 1;
                        
                        $data[$key]['complete'] = 'Yes';
                        $data[$key]['modifiedDate'] = ($securityUsersData->modified) ? date("F j,Y", strtotime($securityUsersData->modified)) : date("F j,Y", strtotime($securityUsersData->created));

                    } else {
                        $data[$key]['complete'] = 'No';
                        $data[$key]['modifiedDate'] = 'Not Updated';
                    }
                }


                if ($configItem['name'] == 'Demographic') {
                    if (!empty($userDemographicsData)) {
                        $profileComplete = $profileComplete + 1;

                        $data[$key]['complete'] = 'Yes';
                        $data[$key]['modifiedDate'] = ($userDemographicsData->modified) ? date("F j,Y", strtotime($userDemographicsData->modified)) : date("F j,Y", strtotime($userDemographicsData->created));
                    } else {
                        $data[$key]['complete'] = 'No';
                        $data[$key]['modifiedDate'] = 'Not Updated';
                    }
                }


                if ($configItem['name'] == 'Identities') {
                    if (!empty($userIdentitiesData)) {
                        $profileComplete = $profileComplete + 1;

                        $data[$key]['complete'] = 'Yes';
                        $data[$key]['modifiedDate'] = ($userIdentitiesData->modified) ? date("F j,Y", strtotime($userIdentitiesData->modified)) : date("F j,Y", strtotime($userIdentitiesData->created));
                    } else {
                        $data[$key]['complete'] = 'No';
                        $data[$key]['modifiedDate'] = 'Not Updated';
                    }
                }


                if ($configItem['name'] == 'Nationalities') {
                    if (!empty($userNationalitiesData)) {
                        $profileComplete = $profileComplete + 1;

                        $data[$key]['complete'] = 'Yes';
                        $data[$key]['modifiedDate'] = ($userNationalitiesData->modified) ? date("F j,Y", strtotime($userNationalitiesData->modified)) : date("F j,Y", strtotime($userNationalitiesData->created));
                    } else {
                        $data[$key]['complete'] = 'No';
                        $data[$key]['modifiedDate'] = 'Not Updated';
                    }
                }

                if ($configItem['name'] == 'Contacts') {
                    if (!empty($userContactsData)) {
                        $profileComplete = $profileComplete + 1;

                        $data[$key]['complete'] = 'Yes';
                        $data[$key]['modifiedDate'] = ($userContactsData->modified) ? date("F j,Y", strtotime($userContactsData->modified)) : date("F j,Y", strtotime($userContactsData->created));
                    } else {
                        $data[$key]['complete'] = 'No';
                        $data[$key]['modifiedDate'] = 'Not Updated';
                    }
                }


                if ($configItem['name'] == 'Languages') {
                    if (!empty($userLanguagesData)) {
                        $profileComplete = $profileComplete + 1;
                        $data[$key]['complete'] = 'Yes';
                        $data[$key]['modifiedDate'] = ($userLanguagesData->modified) ? date("F j,Y", strtotime($userLanguagesData->modified)) : date("F j,Y", strtotime($userLanguagesData->created));
                    } else {
                        $data[$key]['complete'] = 'No';
                        $data[$key]['modifiedDate'] = 'Not Updated';
                    }
                }


            }

            $totalProfileComplete = count($data);
            $profilePercentage = 100 / $totalProfileComplete * $profileComplete;
            $profilePercentage = round($profilePercentage);
            $data['percentage'] = $profilePercentage;

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

