<?php

namespace App\Repositories;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use JWTAuth;
use App\Models\InstitutionStudent;
use App\Models\InstitutionStudentAbsenceDetails;
use App\Models\AbsenceReasons;
use App\Models\InstitutionClasses;
use App\Models\StudentAttendanceMarkedRecords;
use App\Models\InstitutionStaffAttendances;
use App\Models\SecurityUsers;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;


class StudentRepository extends Controller
{

    public function getStudents($request)
    {
        try {
            $params = $request->all();

            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $list = InstitutionStudent::with('institution:id,code,name', 'educationGrade:id,name', 'securityUser', 'securityUser.gender:id,name', 'studentStatus', 'academicPeriod:id,name');

            if(isset($params['academic_period_id'])){
                $academic_period_id = $params['academic_period_id'];

                $list = $list->where('academic_period_id', $academic_period_id);
            }

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $list = $list->orderBy($col, $orderBy);
            }
            $list = $list->paginate($limit)->toArray();
            return $list;

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution Students List Not Found');
        }
    }



    public function getInstitutionStudents($request, $institutionId)
    {
        try {
            $params = $request->all();

            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $list = InstitutionStudent::with('institution:id,code,name', 'educationGrade:id,name', 'securityUser', 'securityUser.gender:id,name', 'studentStatus', 'academicPeriod:id,name')->where('institution_id', $institutionId);

            if(isset($params['academic_period_id'])){
                $academic_period_id = $params['academic_period_id'];

                $list = $list->where('academic_period_id', $academic_period_id);
            }

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $list = $list->orderBy($col, $orderBy);
            }
            $list = $list->paginate($limit)->toArray();
            return $list;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution Students List Not Found');
        }
    }


    public function getInstitutionStudentData($request, $institutionId, $studentId)
    {
        try {
            $data = InstitutionStudent::with(
                    'institution:id,code,name', 
                    'educationGrade:id,name', 
                    'securityUser', 
                    'securityUser.gender:id,name', 
                    'studentStatus', 
                    'academicPeriod:id,name'
                )
                ->where('institution_id', $institutionId)
                ->where('student_id', $studentId)
                ->first();

            if($data){
                $data = $data->toArray();
            } else {
                $data = [];
            }
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution Student Data Not Found');
        }
    }


    public function getStudentAbsences($request)
    {
        try {
            $params = $request->all();

            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $getStudents = InstitutionStudentAbsenceDetails::with(
                        'securityUser',
                        'securityUser.gender:id,name',
                        'educationGrade:id,name',
                        'institutionClass:id,name',
                        'academicPeriod:id,name',
                        'institution:id,code,name'
                    );

            if(isset($params['academic_period_id'])){
                $academic_period_id = $params['academic_period_id'];

                $getStudents = $getStudents->where('academic_period_id', $academic_period_id);
            }

            $getStudents = $getStudents->select('student_id', 'institution_id', 'academic_period_id', 'institution_class_id', 'education_grade_id', 'modified_user_id', 'modified', 'created_user_id', 'created')->groupby('institution_id', 'student_id');

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $getStudents = $getStudents->orderBy($col, $orderBy);
            }

            $getStudents = $getStudents->paginate($limit)->toArray();
            //dd("getStudents", $getStudents);

            $data = [];

            if(count($getStudents['data']) > 0){
                foreach($getStudents['data'] as $k => $d){
                    
                    $data[$k] = $d;
                    $dateData = InstitutionStudentAbsenceDetails::with('absenceType:id,name', 'studentAbsenceReason:id,name', 'period:id,name', 'subject:id,name')->where('student_id', $d['student_id'])->where('institution_id', $d['institution_id'])->get()->toArray();
                    $arr = [];
                    foreach($dateData as $key => $dd){
                        $arr[$key]['period_id'] = $dd['period']['id']??Null;
                        $arr[$key]['period_name'] = $dd['period']['name']??Null;
                        $arr[$key]['subject_id'] = $dd['subject']['id']??Null;
                        $arr[$key]['subject_name'] = $dd['subject']['name']??Null;
                        $arr[$key]['absence_type_id'] = $dd['absence_type']['id']??Null;
                        $arr[$key]['absence_type_name'] = $dd['absence_type']['name']??Null;
                        $arr[$key]['student_absence_reason_id'] = $dd['student_absence_reason']['id']??Null;
                        $arr[$key]['student_absence_reason_name'] = $dd['student_absence_reason']['name']??Null;
                        $arr[$key]['comment'] = $dd['comment']??Null;
                        $arr[$key]['date'] = $dd['date']??Null;
                        
                    }

                    $data[$k]['date_data'] = $arr;
                }
            }
            
            $getStudents['data'] = $data;

            return $getStudents;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution Student Absences List Not Found');
        }
    }



    public function getInstitutionStudentAbsences($request, $institutionId)
    {
        try {
            $params = $request->all();

            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $getStudents = InstitutionStudentAbsenceDetails::with(
                        'securityUser',
                        'securityUser.gender:id,name',
                        'educationGrade:id,name',
                        'institutionClass:id,name',
                        'academicPeriod:id,name',
                        'institution:id,code,name'
                    )->where('institution_id', $institutionId);

            if(isset($params['academic_period_id'])){
                $academic_period_id = $params['academic_period_id'];

                $getStudents = $getStudents->where('academic_period_id', $academic_period_id);
            }

            $getStudents = $getStudents->select('student_id', 'institution_id', 'academic_period_id', 'institution_class_id', 'education_grade_id', 'modified_user_id', 'modified', 'created_user_id', 'created')->groupby('institution_id', 'student_id');

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $getStudents = $getStudents->orderBy($col, $orderBy);
            }

            $getStudents = $getStudents->paginate($limit)->toArray();
            //dd("getStudents", $getStudents);

            $data = [];

            if(count($getStudents['data']) > 0){
                foreach($getStudents['data'] as $k => $d){
                    
                    $data[$k] = $d;
                    $dateData = InstitutionStudentAbsenceDetails::with('absenceType:id,name', 'studentAbsenceReason:id,name', 'period:id,name', 'subject:id,name')->where('student_id', $d['student_id'])->where('institution_id', $d['institution_id'])->get()->toArray();
                    $arr = [];
                    foreach($dateData as $key => $dd){
                        $arr[$key]['period_id'] = $dd['period']['id']??Null;
                        $arr[$key]['period_name'] = $dd['period']['name']??Null;
                        $arr[$key]['subject_id'] = $dd['subject']['id']??Null;
                        $arr[$key]['subject_name'] = $dd['subject']['name']??Null;
                        $arr[$key]['absence_type_id'] = $dd['absence_type']['id']??Null;
                        $arr[$key]['absence_type_name'] = $dd['absence_type']['name']??Null;
                        $arr[$key]['student_absence_reason_id'] = $dd['student_absence_reason']['id']??Null;
                        $arr[$key]['student_absence_reason_name'] = $dd['student_absence_reason']['name']??Null;
                        $arr[$key]['comment'] = $dd['comment']??Null;
                        $arr[$key]['date'] = $dd['date']??Null;
                        
                    }

                    $data[$k]['date_data'] = $arr;
                }
            }
            
            $getStudents['data'] = $data;
            
            return $getStudents;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution Student Absences List Not Found');
        }
    }


    public function getInstitutionStudentAbsencesData($request, $institutionId, $studentId)
    {
        try {
            $getStudent = InstitutionStudentAbsenceDetails::with(
                        'securityUser',
                        'securityUser.gender:id,name',
                        'educationGrade:id,name',
                        'institutionClass:id,name',
                        'academicPeriod:id,name',
                        'institution:id,code,name'
                    )
                    ->where('institution_id', $institutionId)
                    ->where('student_id', $studentId)
                    ->first();

            if($getStudent){
                $getStudent = $getStudent->toArray();

                $dateData = InstitutionStudentAbsenceDetails::with('absenceType:id,name', 'studentAbsenceReason:id,name', 'period:id,name', 'subject:id,name')->where('student_id', $getStudent['student_id'])->where('institution_id', $getStudent['institution_id'])->get()->toArray();

                $arr = [];
                foreach($dateData as $key => $dd){
                    $arr[$key]['period_id'] = $dd['period']['id']??Null;
                    $arr[$key]['period_name'] = $dd['period']['name']??Null;
                    $arr[$key]['subject_id'] = $dd['subject']['id']??Null;
                    $arr[$key]['subject_name'] = $dd['subject']['name']??Null;
                    $arr[$key]['absence_type_id'] = $dd['absence_type']['id']??Null;
                    $arr[$key]['absence_type_name'] = $dd['absence_type']['name']??Null;
                    $arr[$key]['student_absence_reason_id'] = $dd['student_absence_reason']['id']??Null;
                    $arr[$key]['student_absence_reason_name'] = $dd['student_absence_reason']['name']??Null;
                    $arr[$key]['comment'] = $dd['comment']??Null;
                    $arr[$key]['date'] = $dd['date']??Null;
                    
                }
                $getStudent['date_data'] = $arr;

            } else {
                $getStudent = [];
            }
            return $getStudent;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution Student Absences Data Not Found');
        }
    }



    //POCOR-7547 Starts...
    public function getEducationGrades($request)
    {
        try {
            $sql = 'SELECT
                academic_periods.name as academic_period_name
                ,student_mark_type_statuses.academic_period_id
                ,education_grades.name as education_grade_name
                ,student_mark_type_status_grades.education_grade_id
                ,student_attendance_types.code attendance_by
                ,IF(student_attendance_per_day_periods.id is NULL AND student_attendance_types.code = "DAY", "Period 1", student_attendance_per_day_periods.name) AS "period_name"
                ,attendance_per_day
                ,date_enabled
                ,date_disabled
                ,config_items.value
                ,IF(config_items.value = 1,"Mark absent if one or more records absent","Mark present if one or more records present") day_configuration
                FROM student_mark_type_status_grades
                INNER JOIN education_grades ON education_grades.id = student_mark_type_status_grades.education_grade_id
                INNER JOIN student_mark_type_statuses ON student_mark_type_statuses.id = student_mark_type_status_grades.student_mark_type_status_id
                INNER JOIN student_attendance_mark_types ON student_attendance_mark_types.id = student_mark_type_statuses.student_attendance_mark_type_id
                INNER JOIN academic_periods ON academic_periods.id = student_mark_type_statuses.academic_period_id
                INNER JOIN student_attendance_types ON student_attendance_types.id = student_attendance_mark_types.student_attendance_type_id
                INNER JOIN config_items ON config_items.code = "calculate_daily_attendance"
                LEFT JOIN student_attendance_per_day_periods ON student_attendance_per_day_periods.student_attendance_mark_type_id = student_attendance_mark_types.id
                ORDER BY education_grades.id ASC,student_attendance_per_day_periods.id ASC';

            $list = DB::select(DB::raw($sql));
            return $list;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Education Grade List Not Found');
        }
    }


    public function getClassesSubjects($request, $institutionId)
    {
        try {
            $data = InstitutionClasses::select(
                    'institution_classes.academic_period_id',
                    'institution_classes.institution_id',
                    'institution_classes.id as institution_class_id',
                    'institution_classes.name as institution_class_name',
                    'institution_subjects.id as institution_subject_id',
                    'institution_subjects.name as institution_subject_name',
                    'institution_subjects.education_subject_id',
                    'institution_subjects.education_grade_id',
                    'institution_classes.total_male_students',
                    'institution_classes.total_female_students',
                    'institution_classes.modified_user_id',
                    'institution_classes.modified',
                    'institution_classes.created_user_id',
                    'institution_classes.created',
                    )
                    ->join('institution_class_subjects', 'institution_class_subjects.institution_class_id', '=', 'institution_classes.id')
                    ->join('institution_subjects', 'institution_subjects.id', '=', 'institution_class_subjects.institution_subject_id')
                    ->where('institution_classes.institution_id', $institutionId)
                    ->get();

            
            return $data;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Class Subjects List Not Found');
        }
    }


    public function addClassAttendances($request)
    {
        DB::beginTransaction();
        try {
            $params = $request->all();

            $check = StudentAttendanceMarkedRecords::where([
                'academic_period_id' => $params['academic_period_id'],
                'education_grade_id' => $params['education_grade_id'],
                'institution_id' => $params['institution_id'],
                'institution_class_id' => $params['institution_class_id'],
                'date' => $params['date'],
                'period' => $params['period']??0,
                'subject_id' => $params['subject_id']??0,
            ])->first();

            if($check){ 
                $updateArr['academic_period_id'] = $params['academic_period_id'];
                $updateArr['education_grade_id'] = $params['education_grade_id'];
                $updateArr['institution_id'] = $params['institution_id'];
                $updateArr['institution_class_id'] = $params['institution_class_id'];
                $updateArr['date'] = $params['date'];
                $updateArr['period'] = $params['period']??0;
                $updateArr['subject_id'] = $params['subject_id']??0;
                $updateArr['no_scheduled_class'] = $params['no_scheduled_class']??0;

                $update = StudentAttendanceMarkedRecords::where([
                    'academic_period_id' => $params['academic_period_id'],
                    'education_grade_id' => $params['education_grade_id'],
                    'institution_id' => $params['institution_id'],
                    'institution_class_id' => $params['institution_class_id'],
                    'date' => $params['date'],
                    'period' => $params['period']??0,
                    'subject_id' => $params['subject_id']??0,
                ])->update($updateArr);

                $resp = 2;
            } else {
                $addArr['academic_period_id'] = $params['academic_period_id'];
                $addArr['education_grade_id'] = $params['education_grade_id'];
                $addArr['institution_id'] = $params['institution_id'];
                $addArr['institution_class_id'] = $params['institution_class_id'];
                $addArr['date'] = $params['date'];
                $addArr['period'] = $params['period']??0;
                $addArr['subject_id'] = $params['subject_id']??0;
                $addArr['no_scheduled_class'] = $params['no_scheduled_class']??0;

                $store = StudentAttendanceMarkedRecords::insert($addArr);
                $resp = 1;
            }

            DB::commit();
            return $resp;
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::error(
                'Failed to add data in DB.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Class Attendances Not Added');
        }
    }



    public function addStudentAbsences($request)
    {
        DB::beginTransaction();
        try {
            $param = $request->all();
            $check = InstitutionStudentAbsenceDetails::where([
                'student_id' => $param['student_id'],
                'institution_id' => $param['institution_id'],
                'academic_period_id' => $param['academic_period_id'],
                'institution_class_id' => $param['institution_class_id'],
                'date' => $param['date'],
                'period' => $param['period'],
                'subject_id' => $param['subject_id']
            ])->first();

            if($check){
                $updateArr['academic_period_id'] = $param['academic_period_id'];
                $updateArr['education_grade_id'] = $param['education_grade_id'];
                $updateArr['institution_id'] = $param['institution_id'];
                $updateArr['institution_class_id'] = $param['institution_class_id'];
                $updateArr['date'] = $param['date'];
                $updateArr['period'] = $param['period'];
                $updateArr['subject_id'] = $param['subject_id'];
                $updateArr['student_id'] = $param['student_id'];
                $updateArr['absence_type_id'] = $param['absence_type_id'];
                $updateArr['student_absence_reason_id'] = $param['student_absence_reason_id']??Null;
                $updateArr['comment'] = $param['comment']??Null;
                $updateArr['modified_user_id'] = JWTAuth::user()->id;
                $updateArr['modified'] = Carbon::now()->toDateTimeString();

                $update = InstitutionStudentAbsenceDetails::where([
                    'student_id' => $param['student_id'],
                    'institution_id' => $param['institution_id'],
                    'academic_period_id' => $param['academic_period_id'],
                    'institution_class_id' => $param['institution_class_id'],
                    'date' => $param['date'],
                    'period' => $param['period'],
                    'subject_id' => $param['subject_id']
                ])->update($updateArr);

                $resp = 2;
            } else {
                $addArr['academic_period_id'] = $param['academic_period_id'];
                $addArr['education_grade_id'] = $param['education_grade_id'];
                $addArr['institution_id'] = $param['institution_id'];
                $addArr['institution_class_id'] = $param['institution_class_id'];
                $addArr['date'] = $param['date'];
                $addArr['period'] = $param['period'];
                $addArr['subject_id'] = $param['subject_id'];
                $addArr['student_id'] = $param['student_id'];
                $addArr['absence_type_id'] = $param['absence_type_id'];
                $addArr['student_absence_reason_id'] = $param['student_absence_reason_id']??Null;
                $addArr['comment'] = $param['comment']??Null;
                $addArr['created_user_id'] = JWTAuth::user()->id;
                $addArr['created'] = Carbon::now()->toDateTimeString();

                $store = InstitutionStudentAbsenceDetails::insert($addArr);

                $resp = 1;
            }


            $checkMarkedRecord = StudentAttendanceMarkedRecords::where([
                    'institution_id' => $param['institution_id'],
                    'academic_period_id' => $param['academic_period_id'],
                    'institution_class_id' => $param['institution_class_id'],
                    'education_grade_id' => $param['education_grade_id'],
                    'date' => $param['date'],
                    'period' => $param['period'],
                    'subject_id' => $param['subject_id']
                ])
                ->first();

            if(!$checkMarkedRecord){
                $storeArr['institution_id'] = $param['institution_id'];
                $storeArr['academic_period_id'] = $param['academic_period_id'];
                $storeArr['institution_class_id'] = $param['institution_class_id'];
                $storeArr['education_grade_id'] = $param['education_grade_id'];
                $storeArr['date'] = $param['date'];
                $storeArr['period'] = $param['period'];
                $storeArr['subject_id'] = $param['subject_id'];

                $insert = StudentAttendanceMarkedRecords::insert($storeArr);
            }
                
            DB::commit();
            return $resp;
        } catch (\Exception $e) {
            DB::rollback();
            Log::error(
                'Failed to add data in DB.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Student absences Not Added');
        }
    }


    public function addStaffAttendances($request)
    {
        DB::beginTransaction();
        try {
            $param = $request->all();

            $check = InstitutionStaffAttendances::where([
                'staff_id' => $param['staff_id'],
                'institution_id' => $param['institution_id'],
                'academic_period_id' => $param['academic_period_id'],
                'date' => $param['date']
            ])->first();

            if($check){
                $param['modified_user_id'] = JWTAuth::user()->id;
                $param['modified'] = Carbon::now()->toDateTimeString();
                
                $update = InstitutionStaffAttendances::where([
                    'staff_id' => $param['staff_id'],
                    'institution_id' => $param['institution_id'],
                    'academic_period_id' => $param['academic_period_id'],
                    'date' => $param['date']
                ])->update($param);

                $resp = 2;

            } else {
                $param['id'] = Str::Uuid();
                $param['created_user_id'] = JWTAuth::user()->id;
                $param['created'] = Carbon::now()->toDateTimeString();
                
                
                $store = InstitutionStaffAttendances::insert($param);
                $resp = 1;
            }

            
            DB::commit();
            return $resp;
        } catch (\Exception $e) {
            DB::rollback();
            Log::error(
                'Failed to add data in DB.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Staff attendances Not Added');
        }
    }


    public function updateStaffDetails($request)
    {
        DB::beginTransaction();
        try {
            $param = $request->all();
            $check = SecurityUsers::where('id', $param['id'])->first();
            if($check){
                $id = $param['id'];
                unset($param['id']);
                $update = SecurityUsers::where('id', $id)->update($param);
                $resp = 1;
            } else {
                $resp = 0;
            }
            DB::commit();
            return $resp;
        } catch (\Exception $e) {
            DB::rollback();
            Log::error(
                'Failed to update data in DB.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Staff data not updated');
        }
    }

    //POCOR-7547 End...
}

