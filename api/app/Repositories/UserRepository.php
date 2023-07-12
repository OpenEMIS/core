<?php

namespace App\Repositories;

use App\Http\Controllers\Controller;
use App\Models\Gender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use JWTAuth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\SecurityUsers;
use App\Models\Institutions;
use App\Models\InstitutionGrades;
use App\Models\EducationGrades;
use App\Models\InstitutionClasses;
use App\Models\InstitutionSubjects;
use App\Models\EducationSubjects;
use App\Models\InstitutionShifts;
use App\Models\AreaAdministratives;
use App\Models\SummaryInstitutions;
use App\Models\SummaryInstitutionGrades;
use App\Models\SummaryInstitutionNationalities;
use App\Models\SummaryInstitutionGradeNationalities;
use App\Models\InstitutionStaff;
use App\Models\StaffStatuses;
use App\Models\InstitutionPositions;
use App\Models\LocaleContentTranslations;
use App\Models\SummaryInstitutionRoomTypes;
use App\Models\ReportCard;
use App\Models\InstitutionStudentReportCardComment;
use App\Models\InstitutionStudentReportCard;
use App\Models\InstitutionClassStudents;
use App\Models\InstitutionStudent;
use App\Models\InstitutionCompetencyResults;
use App\Models\InstitutionCompetencyItemComments;
use App\Models\InstitutionCompetencyPeriodComments;
use App\Models\StaffTypes;
use App\Models\ConfigItem;
use App\Models\InstitutionSubjectStaff;
use App\Models\AcademicPeriod;
use App\Models\StudentStatuses;
use App\Models\Nationalities;
use App\Models\Workflows;
use App\Models\InstitutionStudentTransfers;
use App\Models\UserNationalities;
use App\Models\IdentityTypes;
use App\Models\UserIdentities;
use App\Models\StaffPositionTitles;
use App\Models\SecurityRoles;
use App\Models\InstitutionStudentAdmission;
use App\Models\InstitutionClassSubjects;
use App\Models\InstitutionSubjectStudents;
use App\Models\StudentCustomFieldValues;

class UserRepository extends Controller
{
    public function getUsersList($request)
    {
        try {
            $params = $request->all();

            $limit = config('constants.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }
            
            $users = SecurityUsers::with('identityType', 'nationalities', 'identities');
            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $users = $users->orderBy($col, $orderBy);
            }
            $list = $users->paginate($limit)->toArray();
            
            return $list;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Users List Not Found');
        }
    }


    public function getUsersData(int $userId)
    {
        try {
            
            $users = SecurityUsers::with(
                    'gender',
                    'nationalities',
                    'institutionStudent',
                    'institutionStudent.institution',
                    'institutionStudent.educationGrade',
                    'institutionStudent.studentStatus',
                    'identities',
                    'nationality',
                    'identityType'
                )
                    ->where('id', $userId)
                    ->get();
            
            return $users;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Users Data Not Found');
        }
    }



    public function saveStudentData($request)
    {
        DB::beginTransaction();
        try {
            $param = $request->all();

            $start_date = Null;
            if(isset($param['start_date'])){
                $start_date = date("Y-m-d", strtotime($param['start_date']));
            }

            $end_date = Null;
            if(isset($param['end_date'])){
                $end_date = date("Y-m-d", strtotime($param['end_date']));
            }
            
            $academicPeriod = AcademicPeriod::where('id', $param['academic_period_id'])->first();

            if(!$academicPeriod){
                return 2;
            }

            $start_year = $academicPeriod->start_year;
            $end_year = $academicPeriod->end_year;

            //get prefered language
            $pref_lang = ConfigItem::where('code', 'language')->where('type', 'System')->first();


            //get Student Status List
            $studentStatus = StudentStatuses::pluck('id', 'code')->toArray();
            

            //get nationality data
            $nationalities = '';
            if(isset($param['nationality_name']) && ($param['nationality_name'] != "")){
                $nationality = Nationalities::where('name', $param['nationality_name'])->first();
                if(!$nationality){
                    //Adding new nationality...
                    $orderNationalities = Nationalities::orderBy('order', 'DESC')->first();
                    $storeArr = [
                        'name' => $param['nationality_name'],
                        'order' => !empty($orderNationalities->order) ? $orderNationalities->order + 1 : 0,
                        'visible' => 1,
                        'editable' => 1,
                        'identity_type_id' => null,
                        'default' => 0,
                        'international_code' => '',
                        'national_code' => '',
                        'external_validation' => 0,
                        'created_user_id' => JWTAuth::user()->id,
                        'created' => Carbon::now()->toDateTimeString()
                    ];

                    $nationalityId = Nationalities::insertGetId($storeArr);
                } else {
                    $nationalityId = $nationality->id;
                }
            }


            if(isset($param['is_diff_school']) && ($param['is_diff_school'] == 1)){
                $workflows = Workflows::join('workflow_steps', 'workflow_steps.workflow_id', '=', 'workflows.id')
                    ->where('workflow_steps.name', 'Open')
                    ->where('workflows.name', 'Student Transfer - Receiving')
                    ->select('workflow_steps.id as workflowSteps_id')
                    ->first();
                


                $entityTransferData = [
                    'start_date' => $start_date??null,
                    'end_date' => $end_date??null,
                    'requested_date' => null,
                    'student_id' => $param['student_id']??null,
                    'status_id' => $workflows->workflowSteps_id,
                    'assignee_id' => JWTAuth::user()->id, //POCOR-7080
                    'institution_id' => $param['institution_id']??null,
                    'academic_period_id' => $param['academic_period_id']??null,
                    'education_grade_id' => $param['education_grade_id']??null,
                    'institution_class_id' => $param['institution_class_id']??null,
                    'previous_institution_id' => $param['previous_institution_id']??null,
                    'previous_academic_period_id' => $param['previous_academic_period_id']??null,
                    'previous_education_grade_id' => $param['previous_education_grade_id']??null,
                    'student_transfer_reason_id' => $param['student_transfer_reason_id']??null,
                    'comment' => $param['comment']??null,
                    'all_visible' => 1,
                    'modified_user_id' => null,
                    'modified' => null,
                    'created_user_id' => JWTAuth::user()->id,
                    'created' => Carbon::now()->toDateTimeString()
                ];


                $storeIST = InstitutionStudentTransfers::insert($entityTransferData);

            } else {
                $openemis_no = $param['openemis_no']??0;

                $checkStudentExist = SecurityUsers::where('openemis_no', $openemis_no)->first();

                $entityData = [
                    'openemis_no' => $openemis_no,
                    'first_name' => $param['first_name'],
                    'middle_name' => $param['middle_name'],
                    'third_name' => $param['third_name'],
                    'last_name' => $param['last_name'],
                    'preferred_name' => $param['preferred_name'],
                    'gender_id' => $param['gender_id'],
                    'date_of_birth' => $param['date_of_birth'],
                    'nationality_id' => $nationalityId??"",
                    'preferred_language' => $pref_lang->value??"",
                    'username' => $param['username']??null,
                    'password' => Hash::make($param['password']),
                    'address' => $param['address']??null,
                    'address_area_id' => $param['address_area_id']??null,
                    'birthplace_area_id' => $param['birthplace_area_id']??null,
                    'postal_code' => $param['postal_code']??null,
                    
                    'is_student' => 1,
                    'created_user_id' => JWTAuth::user()->id,
                    'created' => Carbon::now()->toDateTimeString()
                ];
                
                if($checkStudentExist){
                    $securityUser = $checkStudentExist;
                    $securityUserResult = SecurityUsers::where('id', $checkStudentExist->id)->update($entityData);
                } else {
                    $securityUserId = SecurityUsers::insertGetId($entityData);
                    $securityUser = SecurityUsers::where('id', $securityUserId)->first();
                }

                if($securityUser){
                    $user_record_id = $securityUser->id;
                    if($param['nationality_id'] || $param['nationality_name']){
                        if(isset($nationality->id)){
                            $checkUserNationality = UserNationalities::where('nationality_id', $nationality->id)->where('security_user_id', $user_record_id)->first();

                            if(!$checkUserNationality){
                                $storeArr['id'] = Str::uuid();
                                $storeArr['preferred'] = 1;
                                $storeArr['nationality_id'] = $nationality->id;
                                $storeArr['security_user_id'] = $user_record_id;
                                $storeArr['created_user_id'] = JWTAuth::user()->id;
                                $storeArr['created'] = Carbon::now()->toDateTimeString();

                                $store = UserNationalities::insert($storeArr);
                            }
                        }
                    }

                    if(isset($nationality->id) && ($param['identity_type_id'] && $param['identity_type_id'] != '') && ($param['identity_number'] && $param['identity_number'] != '')){

                        $identityTypes = IdentityTypes::where('name', $param['identity_type_name']??"")->first();

                        if($identityTypes){
                            $userIdentity = UserIdentities::where('nationality_id', $nationality->id)->where('identity_type_id', $param['identity_type_id'])->where('number', $param['identity_number'])->first();
                            
                            if(!$userIdentity){
                                $storeArr['identity_type_id'] = $identityTypes->first();
                                $storeArr['nationality_id'] = $nationality->id;
                                $storeArr['number'] = $param['identity_number'];
                                $storeArr['security_user_id'] = $user_record_id;
                                $storeArr['created_user_id'] = JWTAuth::user()->id;
                                $storeArr['created'] = Carbon::now()->toDateTimeString();

                                $store = UserIdentities::insert($storeArr);
                            }
                        }
                    }


                    if($param['education_grade_id'] && $param['academic_period_id'] && $param['institution_id']){
                        $entityStudentsData = [
                            'id' => Str::uuid(),
                            'student_status_id' => $param['student_status_id']??"",
                            'student_id' => $user_record_id,
                            'education_grade_id' => $param['education_grade_id'],
                            'academic_period_id' => $param['academic_period_id'],
                            'start_date' => $start_date??null,
                            'start_year' => $start_year??null,
                            'end_date' => $end_date??null,
                            'end_year' => $end_year??null,
                            'institution_id' => $param['institution_id'],
                            'created_user_id' => JWTAuth::user()->id,
                            'created' => Carbon::now()->toDateTimeString()
                        ];

                        $store = InstitutionStudent::insert($entityStudentsData);
                    }



                    $workflows = Workflows::join('workflow_steps', 'workflow_steps.workflow_id', '=', 'workflows.id')
                    ->where('workflow_steps.name', 'Approved')
                    ->where('workflows.name', 'Student Admission')
                    ->select('workflow_steps.id as workflowSteps_id')
                    ->first();



                    if (!empty($param['education_grade_id']) && !empty($param['institution_id']) && !empty($param['academic_period_id']) && !empty($param['institution_class_id']) && !empty($workflows)) {
                        $entityAdmissionData = [
                            'start_date' => $start_date??null,
                            'end_date' => $end_date??null,
                            'student_id' => $user_record_id,
                            'status_id' => $workflows->workflowSteps_id,
                            'assignee_id' => JWTAuth::user()->id, //POCOR7080
                            'institution_id' => $param['institution_id']??"",
                            'academic_period_id' => $param['academic_period_id'],
                            'education_grade_id' => $param['education_grade_id'],
                            'institution_class_id' => $param['institution_class_id'],
                            'created_user_id' => JWTAuth::user()->id,
                            'created' => Carbon::now()->toDateTimeString()
                        ];

                        $store = InstitutionStudentAdmission::insert($entityAdmissionData);
                    }



                    if($param['education_grade_id'] && $param['academic_period_id'] && $param['institution_id'] && $param['institution_class_id']){
                        $entityAdmissionData = [
                            'id' => Str::uuid(),
                            'student_id' => $user_record_id,
                            'institution_class_id' => $param['institution_class_id'],
                            'education_grade_id' => $param['education_grade_id'],
                            'academic_period_id' => $param['academic_period_id'],
                            'institution_id' => $param['institution_id'],
                            'student_status_id' => $studentStatus['CURRENT'],
                            'created_user_id' => JWTAuth::user()->id,
                            'created' => Carbon::now()->toDateTimeString()
                        ];

                        $store = InstitutionClassStudents::insert($entityAdmissionData);
                    }


                    if($param['education_grade_id'] && $param['academic_period_id'] && $param['institution_id'] && $param['institution_class_id']){
                        $instClsSubjects = InstitutionClassSubjects::select(
                            'institution_class_id',
                            'institution_subject_id',
                            'institution_subjects.name',
                            'institution_subjects.education_grade_id',
                            'institution_subjects.education_subject_id',
                            'institution_subjects.academic_period_id'
                        )
                        ->leftjoin('institution_subjects', 'institution_subjects.id', '=', 'institution_class_subjects.institution_subject_id')
                        ->join('education_grades_subjects', function($join){
                            $join->on('education_grades_subjects.education_grade_id', '=', 'institution_subjects.education_grade_id')
                                ->on('education_grades_subjects.education_subject_id', '=', 'institution_subjects.education_subject_id');
                        })
                        ->where('institution_class_subjects.institution_class_id', '=', $param['institution_class_id'])
                        ->where('institution_subjects.academic_period_id', '=', $param['academic_period_id'])
                        ->where('education_grades_subjects.auto_allocation', '!=', 0)
                        ->get()
                        ->toArray();

                        if(count($instClsSubjects) > 0){
                            foreach ($instClsSubjects as $skey => $sval) {

                                $check = InstitutionSubjectStudents::where('student_id', $user_record_id)
                                    ->where('institution_class_id', $param['institution_class_id'])
                                    ->where('academic_period_id', $param['academic_period_id'])
                                    ->where('education_grade_id', $param['education_grade_id'])
                                    ->where('institution_id', $param['institution_id'])
                                    ->where('education_subject_id', $sval['education_subject_id'])
                                    ->exists();
                                if(!$check){
                                    $entitySubjectsData = [
                                        'id' => Str::uuid(),
                                        'student_id' => $user_record_id,
                                        'institution_subject_id' => $sval['institution_subject_id'],
                                        'institution_class_id' => $param['institution_class_id'],
                                        'institution_id' => $param['institution_id'],
                                        'academic_period_id' => $param['academic_period_id'],
                                        'education_subject_id' => $sval['education_subject_id'],
                                        'education_grade_id' => $param['education_grade_id'],
                                        'student_status_id' => $studentStatus['CURRENT'],
                                        'created_user_id' => JWTAuth::user()->id,
                                        'created' => Carbon::now()->toDateTimeString()
                                    ];

                                    $store = InstitutionSubjectStudents::insert($entitySubjectsData);
                                }
                            }
                        }
                    }


                    if(isset($param['custom']) && count($param['custom']) > 0){
                        //if student custom field values already exist in student_custom_field_values table the delete the old values and insert the new ones.

                        $stuCustomFieldValCount = StudentCustomFieldValues::where('student_id', $user_record_id)->get();
                        if(count($stuCustomFieldValCount) > 0){
                            $del = StudentCustomFieldValues::where('student_id', $user_record_id)->delete();
                        }

                        foreach ($param['custom'] as $skey => $sval) {
                            $entityCustomData = [
                                'id' => Str::uuid(),
                                'text_value' => $sval['text_value']??Null,
                                'number_value' => $sval['number_value']??Null,
                                'decimal_value' => $sval['decimal_value']??Null,
                                'textarea_value' => $sval['textarea_value']??Null,
                                'date_value' => $sval['date_value']??Null,
                                'time_value' => $sval['time_value']??Null,
                                'file' => !empty($sval['file']) ? file_get_contents($sval['file']) : '',
                                'student_custom_field_id' => $sval['student_custom_field_id']??Null,
                                'student_id' => $user_record_id,
                                'created_user_id' => JWTAuth::user()->id,
                                'created' => Carbon::now()->toDateTimeString()
                            ];

                            $store = StudentCustomFieldValues::insert($entityCustomData);
                        }
                    }

                }  else {
                    DB::commit();
                    return 0;
                }


            }
            DB::commit();
            return 1;
        } catch (\Exception $e) {
            DB::rollback();
            dd($e);
            Log::error(
                'Failed to store student data.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to store student data.');
        }
    }


    
    public function getUsersGender($request)
    {
        try {
            
            $usersGender = Gender::get();
            
            return $usersGender;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Users Gender list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Users Gender Data Not Found');
        }
    }
}

