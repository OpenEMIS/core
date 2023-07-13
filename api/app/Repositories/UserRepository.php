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
use App\Models\SecurityGroupUsers;
use App\Models\InstitutionStaffShifts;
use App\Models\InstitutionStaffTransfers;

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



    public function saveStaffData($request)
    {
        try {
            $requestData = $request->all();
            if (!empty($requestData)) {
                $openemisNo = (array_key_exists('openemis_no', $requestData)) ? $requestData['openemis_no'] : null;
                $firstName = (array_key_exists('first_name', $requestData)) ? $requestData['first_name'] : null;
                $middleName = (array_key_exists('middle_name', $requestData)) ? $requestData['middle_name'] : null;
                $thirdName = (array_key_exists('third_name', $requestData)) ? $requestData['third_name'] : null;
                $lastName = (array_key_exists('last_name', $requestData)) ? $requestData['last_name'] : null;
                $preferredName = (array_key_exists('preferred_name', $requestData)) ? $requestData['preferred_name'] : null;
                $genderId = (array_key_exists('gender_id', $requestData)) ? $requestData['gender_id'] : null;
                $dateOfBirth = (array_key_exists('date_of_birth', $requestData)) ? date('Y-m-d', strtotime($requestData['date_of_birth'])) : null;
                $identityNumber = (array_key_exists('identity_number', $requestData)) ? $requestData['identity_number'] : null;
                $nationality_id = (array_key_exists('nationality_id', $requestData)) ? $requestData['nationality_id'] : null;
                $nationalityName = (array_key_exists('nationality_name', $requestData)) ? $requestData['nationality_name'] : null;
                $username = (array_key_exists('username', $requestData)) ? $requestData['username'] : null;
                $password = (array_key_exists('password', $requestData)) ? Hash::make($requestData['password']) : null;
                $address = (array_key_exists('address', $requestData)) ? $requestData['address'] : null;
                $postalCode = (array_key_exists('postal_code', $requestData)) ? $requestData['postal_code'] : null;
                $birthplaceAreaId = (array_key_exists('birthplace_area_id', $requestData)) ? $requestData['birthplace_area_id'] : null;
                $addressAreaId = (array_key_exists('address_area_id', $requestData)) ? $requestData['address_area_id'] : null;
                $identityTypeId = (array_key_exists('identity_type_id', $requestData)) ? $requestData['identity_type_id'] : null;
                $identityTypeName = (array_key_exists('identity_type_name', $requestData)) ? $requestData['identity_type_name'] : null;

                $institutionPositionId = (array_key_exists('institution_position_id', $requestData)) ? $requestData['institution_position_id'] : null;
                $fte = (array_key_exists('fte', $requestData)) ? $requestData['fte'] : null;
                $startDate = (array_key_exists('start_date', $requestData)) ? date('Y-m-d', strtotime($requestData['start_date'])) : NULL;
                $endDate = (array_key_exists('end_date', $requestData) && !empty($requestData['end_date'])) ? date('Y-m-d', strtotime($requestData['end_date'])) : '';

                $is_homeroom = (array_key_exists('is_homeroom', $requestData)) ? $requestData['is_homeroom'] : 0; //POCOR-5070
                //$institutionId = $this->request->session()->read('Institution.Institutions.id');
                $institutionId = (array_key_exists('institution_id', $requestData)) ? $requestData['institution_id'] : null;
                $staffTypeId = (array_key_exists('staff_type_id', $requestData)) ? $requestData['staff_type_id'] : null;
                $userId =  JWTAuth::user()->id??1;
                $photoContent = (array_key_exists('photo_base_64', $requestData)) ? $requestData['photo_base_64'] : null;
                $photoName = (array_key_exists('photo_name', $requestData)) ? $requestData['photo_name'] : null;
                $custom = (array_key_exists('custom', $requestData)) ? $requestData['custom'] : "";
                $shiftIds = (array_key_exists('shift_ids', $requestData)) ? $requestData['shift_ids'] : "";

                //when staff transfer in other institution starts
                $isSameSchool = (array_key_exists('is_same_school', $requestData)) ? $requestData['is_same_school'] : 0;
                $isDiffSchool = (array_key_exists('is_diff_school', $requestData)) ? $requestData['is_diff_school'] : 0;
                $staffId = (array_key_exists('staff_id', $requestData)) ? $requestData['staff_id'] : 0;
                $previousInstitutionId = (array_key_exists('previous_institution_id', $requestData)) ? $requestData['previous_institution_id'] : 0;
                $comment = (array_key_exists('comment', $requestData)) ? $requestData['comment'] : '';
                $staff_position_grade_id = (array_key_exists('staff_position_grade_id', $requestData)) ? $requestData['staff_position_grade_id'] : '';
                //when staff transfer in other institution end


                //get academic period data
                $periods = AcademicPeriod::where('current', 1)->first();
                

                $startYear = $endYear = '';
                if (!empty($periods)) {
                    $startYear = $periods->start_year;
                    if ($endDate == NULL || $endDate == '') {
                        $endYear = NULL;
                    } else {
                        $endYear = $periods->end_year;
                    }
                }


                //get prefered language
                $pref_lang = ConfigItem::where(['code' => 'language','type' => 'System'
                    ])->first();


                //get Student Status List
                $statuses = StudentStatuses::pluck('id', 'code')->toArray();
                

                //get nationality data
                $nationalities = '';
                if (!empty($nationalityName)) {
                    $nationalities = Nationalities::where([
                            'name' => $nationalityName,
                        ])->first();

                    if (empty($nationalities)) {
                        //Adding new nationality...
                        $orderNationalities = Nationalities::orderBy('order', 'DESC')->first();

                        $entityNationality = [
                            'name' => $nationalityName,
                            'order' => !empty($orderNationalities->order) ? $orderNationalities->order + 1 : 0,
                            'visible' => 1,
                            'editable' => 1,
                            'identity_type_id' => null,
                            'default' => 0,
                            'international_code' => '',
                            'national_code' => '',
                            'external_validation' => 0,
                            'created_user_id' => $userId,
                            'created' => date('Y-m-d H:i:s')
                        ];

                        $nationalityId = Nationalities::insertGetId($entityNationality);
                    } else {
                        $nationalityId = $nationalities->id;
                    }
                }


                if ($isSameSchool == 1) {
                    $CheckStaffExist = SecurityUsers::where(['openemis_no' => $openemisNo
                        ])->first();

                    if (!empty($CheckStaffExist)) {
                        $existStaffId = $CheckStaffExist->id;
                        $entityData = [
                            'id' => $existStaffId,
                            'openemis_no' => $openemisNo,
                            'first_name' => $firstName,
                            'middle_name' => $middleName,
                            'third_name' => $thirdName,
                            'last_name' => $lastName,
                            'preferred_name' => $preferredName,
                            'gender_id' => $genderId,
                            'date_of_birth' => $dateOfBirth,
                            'nationality_id' => !empty($nationalityId) ? $nationalityId : '',
                            'preferred_language' => $pref_lang->value,
                            'username' => $username,
                            'password' => $password,
                            'address' => $address,
                            'address_area_id' => $addressAreaId,
                            'birthplace_area_id' => $birthplaceAreaId,
                            'postal_code' => $postalCode,
                            'photo_name' => $photoName,
                            'photo_content' => !empty($photoContent) ? file_get_contents($photoContent) : '',
                            'is_staff' => 1,
                            'created_user_id' => $userId,
                            'created' => date('Y-m-d H:i:s'),
                        ];


                        if($CheckStaffExist){
                            $SecurityUserResult = $CheckStaffExist;
                            $securityUserUpdate = SecurityUsers::where('id', $CheckStaffExist->id)->update($entityData);
                        } else {
                            $securityUserId = SecurityUsers::insertGetId($entityData);
                            $SecurityUserResult = SecurityUsers::where('id', $securityUserId)->first();
                        }


                        if ($SecurityUserResult) {
                            $user_record_id = $SecurityUserResult->id;
                            if (!empty($nationality_id) || !empty($nationalityName)) {
                                
                                if (!empty($nationalityId)) {
                                    $checkexistingNationalities = UserNationalities::where('nationality_id', $nationalityId)
                                        ->where('security_user_id', $user_record_id)
                                        ->first();

                                    if (empty($checkexistingNationalities)) {
                                        $entityNationalData = [
                                            'id' => Str::uuid(),
                                            'preferred' => 1,
                                            'nationality_id' => $nationalityId,
                                            'security_user_id' => $user_record_id,
                                            'created_user_id' => $userId,
                                            'created' => date('Y-m-d H:i:s')
                                        ];

                                        $UserNationalitiesResult = UserNationalities::insert($entityNationalData);
                                    }
                                }
                            }


                            if (!empty($nationalityId) && !empty($identityTypeId) && !empty($identityNumber)) {
                                $identityTypes = IdentityTypes::where('name', $identityTypeName)->first();

                                if (!empty($identityTypes)) {
                                    $checkexistingIdentities = UserIdentities::where('nationality_id', $nationalityId)->where('identity_type_id', $identityTypeId)->where('number', $identityNumber)->first();

                                    if (empty($checkexistingIdentities)){
                                        $entityIdentitiesData = [
                                            'identity_type_id' => $identityTypes->id,
                                            'number' => $identityNumber,
                                            'nationality_id' => $nationalityId,
                                            'security_user_id' => $user_record_id,
                                            'created_user_id' => $userId,
                                            'created' => date('Y-m-d H:i:s')
                                        ];

                                        $store = UserIdentities::insert($entityIdentitiesData);
                                    }
                                }
                            }

                        }
                    }

                    if (!empty($institutionId)) {
                        //get id from `institution_positions` table
                        $InstitutionPositionsTbl = InstitutionPositions::where('id', $institutionPositionId)->first();

                        $staffPositionTitlesTbl = StaffPositionTitles::where('id', $InstitutionPositionsTbl->staff_position_title_id??0)->first();

                        if (!empty($InstitutionPositionsTbl)) {
                            

                            $SecurityRolesTbl = SecurityRoles::where('id', $staffPositionTitlesTbl->security_role_id??0)->first();

                            if ($is_homeroom == 1) {
                                $roleArr = ['HOMEROOM_TEACHER', $SecurityRolesTbl->code??""];
                            } else {
                                $roleArr = [$SecurityRolesTbl->code??""];
                            }


                            $SecurityRolesTbl = SecurityRoles::whereIn('code', $roleArr)->get()->toArray();

                            $institutionsSecurityGroupId = Institutions::where('id', $institutionId)->first();

                            if (!empty($SecurityRolesTbl)) {
                                foreach ($SecurityRolesTbl as $rolekey => $roleval) {
                                    $countSecurityGroupUsers = SecurityGroupUsers::leftjoin('security_group_institutions', function ($j) use($institutionsSecurityGroupId){
                                            $j->on('security_group_institutions.security_group_id', '=', 'security_group_users.security_group_id')
                                            ->where('security_group_institutions.institution_id', $institutionsSecurityGroupId->security_group_id??0);
                                        })
                                        ->where('security_group_institutions.security_group_id', $institutionsSecurityGroupId->security_group_id??0)
                                        ->where('security_group_users.security_user_id', $staffId)
                                        ->where('security_group_users.security_role_id', $roleval->id)
                                        ->count();

                                    if (empty($countSecurityGroupUsers)) {
                                        $entityGroupData = [
                                            'id' => Str::uuid(),
                                            'security_group_id' => $institutionsSecurityGroupId->security_group_id, 
                                            'security_user_id' => $staffId,
                                            'security_role_id' => $roleval->id, 
                                            'created_user_id' => $userId,
                                            'created' => date('Y-m-d H:i:s')
                                        ];

                                        $store = SecurityGroupUsers::insert($entityGroupData);
                                    }
                                }
                            }
                        }

                        //get id from `security_group_users` table
                        $SecurityGroupUsersTbl = SecurityGroupUsers::join('security_roles', 'security_roles.id', '=', 'security_group_users.security_role_id')
                            ->leftjoin('security_group_institutions', function($j) use($institutionId){
                                $j->on('security_group_institutions.security_group_id', '=', 'security_group_users.security_group_id')
                                ->where('security_group_institutions.institution_id', $institutionId);
                            })
                            ->where('security_group_institutions.institution_id', $institutionId)
                            ->where('security_group_users.security_user_id', $staffId)
                            ->where('security_group_users.security_role_id', $staffPositionTitlesTbl->security_role_id??0)
                            ->where('security_roles.code', '!=', 'HOMEROOM_TEACHER')
                            ->first();


                        $entityStaffsData = [
                            'FTE' => $fte,
                            'start_date' => $startDate,
                            'start_year' => $startYear,
                            'end_date' => $endDate,
                            'end_year' => $endYear,
                            'staff_id' => $staffId,
                            'staff_type_id' => $staffTypeId,
                            'staff_status_id' => $statuses['ASSIGNED'],
                            'is_homeroom' => $is_homeroom, //POCOR-5070
                            'institution_id' => $institutionId,
                            'institution_position_id' => $institutionPositionId,
                            'security_group_user_id' => (!empty($SecurityGroupUsersTbl)) ? $SecurityGroupUsersTbl->id : null,
                            'staff_position_grade_id' => $staff_position_grade_id,//POCOR-7238
                            'created_user_id' => $userId,
                            'created' => date('Y-m-d H:i:s')
                        ];

                        $store = InstitutionStaff::insert($entityStaffsData);  
                    }

                    if (!empty($shiftIds)) {
                        foreach ($shiftIds as $shkey => $shval) {
                            $entityShiftData = [
                                'staff_id' => $staffId,
                                'shift_id' => $shval,
                                'created' => date('Y-m-d H:i:s')
                            ];

                            $store = InstitutionStaffShifts::insert($entityShiftData);
                        }
                    }


                    if (!empty($custom)) {
                        //if staff custom field values already exist in `staff_custom_field_values` table then delete the old values and insert the new ones.

                        $StaffCustomFieldValuesCount = StudentCustomFieldValues::where('staff_id', $staffId)->count();

                        if ($StaffCustomFieldValuesCount > 0) {
                            $delete = StudentCustomFieldValues::where(['staff_id', $staffId])->delete();
                        }

                        foreach ($custom as $skey => $sval) {
                            $entityCustomData = [
                                'id' => Str::uuid(),
                                'text_value' => $sval['text_value']??Null,
                                'number_value' => $sval['number_value']??Null,
                                'decimal_value' => $sval['decimal_value']??Null,
                                'textarea_value' => $sval['textarea_value']??Null,
                                'date_value' => $sval['date_value']??Null,
                                'time_value' => $sval['time_value']??Null,
                                'file' => !empty($sval['file']) ? file_get_contents($sval['file']) : '',
                                'staff_custom_field_id' => $sval['staff_custom_field_id'],
                                'staff_id' => $staffId,
                                'created_user_id' => $userId,
                                'created' => date('Y-m-d H:i:s')
                            ];

                            $store = StudentCustomFieldValues::insert($entityCustomData);
                        }
                    }

                } elseif($isDiffSchool == 1) {
                    $workflowResults = Workflows::join('workflow_steps', 'workflow_steps.workflow_id', '=', 'workflows.id')
                        ->where('workflow_steps.name', 'Open')
                        ->where('workflows.name', 'Staff Transfer - Receiving')
                        ->select('workflow_steps.id as workflowSteps_id')
                        ->first();


                    $entityTransferData = [
                        'staff_id' => $staffId,
                        'new_institution_id' => $institutionId,
                        'previous_institution_id' => $previousInstitutionId,
                        'status_id' => $workflowResults->workflowSteps_id,
                        'assignee_id' => $userId, //POCOR-7080
                        'new_institution_position_id' => $institutionPositionId,
                        'new_staff_type_id' => $staffTypeId,
                        'new_FTE' => $fte,
                        'new_start_date' => $startDate,
                        'new_end_date' => $endDate,
                        'previous_institution_staff_id' => '',
                        'previous_staff_type_id' => '',
                        'previous_FTE' => '',
                        'previous_end_date' => '',
                        'previous_effective_date' => '',
                        'comment' => $comment,
                        'transfer_type' => 0,
                        'all_visible' => 0,
                        'modified_user_id' => '',
                        'modified' => '',
                        'created_user_id' => $userId,
                        'created' => date('Y-m-d H:i:s'),
                    ];

                    $store = InstitutionStaffTransfers::insert($entityTransferData);
                } else {
                    $CheckStaffExist = SecurityUsers::where(['openemis_no' => $openemisNo
                        ])->first();

                    if (!empty($CheckStaffExist)) {
                        $existStaffId = $CheckStaffExist->id;
                        $entityData = [
                            'id' => $existStaffId,
                            'openemis_no' => $openemisNo,
                            'first_name' => $firstName,
                            'middle_name' => $middleName,
                            'third_name' => $thirdName,
                            'last_name' => $lastName,
                            'preferred_name' => $preferredName,
                            'gender_id' => $genderId,
                            'date_of_birth' => $dateOfBirth,
                            'nationality_id' => !empty($nationalityId) ? $nationalityId : '',
                            'preferred_language' => $pref_lang->value,
                            'username' => $username,
                            'password' => $password,
                            'address' => $address,
                            'address_area_id' => $addressAreaId,
                            'birthplace_area_id' => $birthplaceAreaId,
                            'postal_code' => $postalCode,
                            'photo_name' => $photoName,
                            'photo_content' => !empty($photoContent) ? file_get_contents($photoContent) : '',
                            'is_staff' => 1,
                            'created_user_id' => $userId,
                            'created' => date('Y-m-d H:i:s'),
                        ];

                        $SecurityUserResult = $CheckStaffExist;
                        $securityUserUpdate = SecurityUsers::where('id', $CheckStaffExist->id)->update($entityData);
                    } else {
                        $entityData = [
                            'openemis_no' => $openemisNo,
                            'first_name' => $firstName,
                            'middle_name' => $middleName,
                            'third_name' => $thirdName,
                            'last_name' => $lastName,
                            'preferred_name' => $preferredName,
                            'gender_id' => $genderId,
                            'date_of_birth' => $dateOfBirth,
                            'nationality_id' => !empty($nationalityId) ? $nationalityId : '',
                            'preferred_language' => $pref_lang->value,
                            'username' => $username,
                            'password' => $password,
                            'address' => $address,
                            'address_area_id' => $addressAreaId,
                            'birthplace_area_id' => $birthplaceAreaId,
                            'postal_code' => $postalCode,
                            'photo_name' => $photoName,
                            'photo_content' => !empty($photoContent) ? file_get_contents($photoContent) : '',
                            'is_staff' => 1,
                            'created_user_id' => $userId,
                            'created' => date('Y-m-d H:i:s'),
                        ];

                        $securityUserId = SecurityUsers::insertGetId($entityData);
                        $SecurityUserResult = SecurityUsers::where('id', $securityUserId)->first();
                    }

                    if ($SecurityUserResult) {
                        $user_record_id = $SecurityUserResult->id;
                        if (!empty($nationality_id) || !empty($nationalityName)) {
                                
                            if (!empty($nationalityId)) {
                                $checkexistingNationalities = UserNationalities::where('nationality_id', $nationalityId)
                                    ->where('security_user_id', $user_record_id)
                                    ->first();

                                if (empty($checkexistingNationalities)) {
                                    $entityNationalData = [
                                        'id' => Str::uuid(),
                                        'preferred' => 1,
                                        'nationality_id' => $nationalityId,
                                        'security_user_id' => $user_record_id,
                                        'created_user_id' => $userId,
                                        'created' => date('Y-m-d H:i:s')
                                    ];

                                    $UserNationalitiesResult = UserNationalities::insert($entityNationalData);
                                }
                            }
                        }

                        if (!empty($nationalityId) && !empty($identityTypeId) && !empty($identityNumber)) {
                            $identityTypes = IdentityTypes::where('name', $identityTypeName)->first();

                            if (!empty($identityTypes)) {
                                $checkexistingIdentities = UserIdentities::where('nationality_id', $nationalityId)->where('identity_type_id', $identityTypeId)->where('number', $identityNumber)->first();

                                if (empty($checkexistingIdentities)){
                                    $entityIdentitiesData = [
                                        'identity_type_id' => $identityTypes->id,
                                        'number' => $identityNumber,
                                        'nationality_id' => $nationalityId,
                                        'security_user_id' => $user_record_id,
                                        'created_user_id' => $userId,
                                        'created' => date('Y-m-d H:i:s')
                                    ];

                                    $store = UserIdentities::insert($entityIdentitiesData);
                                }
                            }
                        }


                        if (!empty($institutionId)) {
                            //get id from `institution_positions` table
                            $InstitutionPositionsTbl = InstitutionPositions::where('id', $institutionPositionId)->first();

                            $staffPositionTitlesTbl = StaffPositionTitles::where('id', $InstitutionPositionsTbl->staff_position_title_id??0)->first();

                            $staffId = $user_record_id;
                            if (!empty($InstitutionPositionsTbl)) {
                                $SecurityRolesTbl = SecurityRoles::where('id', $staffPositionTitlesTbl->security_role_id??0)->first();


                                if ($is_homeroom == 1) {
                                    $roleArr = ['HOMEROOM_TEACHER', $SecurityRolesTbl->code??""];
                                } else {
                                    $roleArr = [$SecurityRolesTbl->code??""];
                                }


                                $SecurityRolesTbl = SecurityRoles::whereIn('code', $roleArr)->get()->toArray();

                                $institutionsSecurityGroupId = Institutions::where('id', $institutionId)->first();

                                if (!empty($SecurityRolesTbl)) {
                                    foreach ($SecurityRolesTbl as $rolekey => $roleval) 
                                    {
                                        $countSecurityGroupUsers = SecurityGroupUsers::leftjoin('security_group_institutions', function ($j) use($institutionsSecurityGroupId){
                                                $j->on('security_group_institutions.security_group_id', '=', 'security_group_users.security_group_id')
                                                ->where('security_group_institutions.institution_id', $institutionsSecurityGroupId->security_group_id??0);
                                            })
                                            ->where('security_group_institutions.security_group_id', $institutionsSecurityGroupId->security_group_id??0)
                                            ->where('security_group_users.security_user_id', $staffId)
                                            ->where('security_group_users.security_role_id', $roleval->id)
                                            ->count();

                                        if (empty($countSecurityGroupUsers)) {
                                            $entityGroupData = [
                                                'id' => Str::uuid(),
                                                'security_group_id' => $institutionsSecurityGroupId->security_group_id, 
                                                'security_user_id' => $staffId,
                                                'security_role_id' => $roleval->id, 
                                                'created_user_id' => $userId,
                                                'created' => date('Y-m-d H:i:s')
                                            ];

                                            $store = SecurityGroupUsers::insert($entityGroupData);
                                        }
                                    }
                                }
                            }

                            //get id from `security_group_users` table
                            $SecurityGroupUsersTbl = SecurityGroupUsers::join('security_roles', 'security_roles.id', '=', 'security_group_users.security_role_id')
                                ->leftjoin('security_group_institutions', function($j) use($institutionId){
                                    $j->on('security_group_institutions.security_group_id', '=', 'security_group_users.security_group_id')
                                    ->where('security_group_institutions.institution_id', $institutionId);
                                })
                                ->where('security_group_institutions.institution_id', $institutionId)
                                ->where('security_group_users.security_user_id', $staffId)
                                ->where('security_group_users.security_role_id', $staffPositionTitlesTbl->security_role_id??0)
                                ->where('security_roles.code', '!=', 'HOMEROOM_TEACHER')
                                ->first();

                            $entityStaffsData = [
                                'FTE' => $fte,
                                'start_date' => $startDate,
                                'start_year' => $startYear,
                                'end_date' => $endDate,
                                'end_year' => $endYear,
                                'staff_id' => $staffId,
                                'staff_type_id' => $staffTypeId,
                                'staff_status_id' => $statuses['ASSIGNED'],
                                'is_homeroom' => $is_homeroom, //POCOR-5070
                                'institution_id' => $institutionId,
                                'institution_position_id' => $institutionPositionId,
                                'security_group_user_id' => (!empty($SecurityGroupUsersTbl)) ? $SecurityGroupUsersTbl->id : null,
                                'staff_position_grade_id' => $staff_position_grade_id,//POCOR-7238
                                'created_user_id' => $userId,
                                'created' => date('Y-m-d H:i:s')
                            ];

                            $store = InstitutionStaff::insert($entityStaffsData);
                        }

                        if (!empty($shiftIds)) {
                            foreach ($shiftIds as $shkey => $shval) {
                                $entityShiftData = [
                                    'staff_id' => $user_record_id,
                                    'shift_id' => $shval,
                                    'created' => date('Y-m-d H:i:s')
                                ];

                                $store = InstitutionStaffShifts::insert($entityShiftData);
                            }
                        }


                        if (!empty($custom)) {
                            //if staff custom field values already exist in `staff_custom_field_values` table then delete the old values and insert the new ones.

                            $StaffCustomFieldValuesCount = StudentCustomFieldValues::where('staff_id', $user_record_id)->count();

                            if ($StaffCustomFieldValuesCount > 0) {
                                $delete = StudentCustomFieldValues::where(['staff_id', $user_record_id])->delete();
                            }

                            foreach ($custom as $skey => $sval) {
                                $entityCustomData = [
                                    'id' => Str::uuid(),
                                    'text_value' => $sval['text_value']??Null,
                                    'number_value' => $sval['number_value']??Null,
                                    'decimal_value' => $sval['decimal_value']??Null,
                                    'textarea_value' => $sval['textarea_value']??Null,
                                    'date_value' => $sval['date_value']??Null,
                                    'time_value' => $sval['time_value']??Null,
                                    'file' => !empty($sval['file']) ? file_get_contents($sval['file']) : '',
                                    'staff_custom_field_id' => $sval['staff_custom_field_id'],
                                    'staff_id' => $user_record_id,
                                    'created_user_id' => $userId,
                                    'created' => date('Y-m-d H:i:s')
                                ];

                                $store = StudentCustomFieldValues::insert($entityCustomData);
                            }
                        }

                    } else {
                        DB::commit();
                        return 0;
                    }
                }

            }
            DB::commit();
            return 1;
        } catch(\Exception $e) {
            Log::error(
                'Failed to store staff data.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to store staff data.');
        }
    }
}

