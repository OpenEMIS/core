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
use App\Models\InstitutionClassStudents;
use App\Models\InstitutionStudentTransfers;
use App\Models\WorkflowSteps;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;


class StudentRepository extends Controller
{

    public function getStudents($request)
    {
        try {

            //For POCOR-7772 Start
            $permissions = checkAccess();

            if(isset($permissions)){
                if($permissions['super_admin'] != 1){
                    //For POCOR-8077 Start...
                    if($permissions['allowAllInstitutions'] != 1){
                        $institution_Ids = $permissions['institutionIds'];
                    }
                    //For POCOR-8077 End...
                }
            }
            //For POCOR-7772 End

            $params = $request->all();
            //For POCOR-8491 Start...
            $list = InstitutionStudent::with('institution:id,code,name', 'educationGrade:id,name', 'securityUser', 'securityUser.gender:id,name', 'studentStatus', 'academicPeriod:id,name',
                'studentCustomFieldValue:id,text_value,number_value,decimal_value,textarea_value,date_value,time_value,file,student_custom_field_id,student_id',
                'studentCustomFieldValue.studentCustomField:id,name');
            //For POCOR-8491 End...

            if(isset($params['academic_period_id'])){
                $academic_period_id = $params['academic_period_id'];

                $list = $list->where('academic_period_id', $academic_period_id);
            }

            //For POCOR-7772 Start
            if(isset($institution_Ids)){
                $list = $list->whereIn('institution_students.institution_id', $institution_Ids);
            }
            //For POCOR-7772 End

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $list = $list->orderBy($col, $orderBy);
            }

            $resp = [];
            if(isset($params['limit'])){
                $limit = $params['limit'];
                $resp = $list->paginate($limit)->toArray();
            } else{
                $list = $list->get()->toArray();
                $resp['data'] = $list;
            }

            return $resp;

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

            //For POCOR-7772 Start
            $permissions = checkAccess();

            if(isset($permissions)){
                if($permissions['super_admin'] != 1){
                    //For POCOR-8077 Start...
                    if($permissions['allowAllInstitutions'] != 1){
                        $institution_Ids = $permissions['institutionIds'];
                    }
                    //For POCOR-8077 End...
                }
            }
            //For POCOR-7772 End

            $params = $request->all();

            //For POCOR-8491 Start...
            $list = InstitutionStudent::with('institution:id,code,name', 'educationGrade:id,name', 'securityUser', 'securityUser.gender:id,name', 'studentStatus', 'academicPeriod:id,name',
                'studentCustomFieldValue:id,text_value,number_value,decimal_value,textarea_value,date_value,time_value,file,student_custom_field_id,student_id',
                'studentCustomFieldValue.studentCustomField:id,name')->where('institution_id', $institutionId);
            //For POCOR-8491 End...

            if(isset($params['academic_period_id'])){
                $academic_period_id = $params['academic_period_id'];

                $list = $list->where('academic_period_id', $academic_period_id);
            }

            //For POCOR-7772 Start
            if(isset($institution_Ids)){
                $list = $list->whereIn('institution_students.institution_id', $institution_Ids);
            }
            //For POCOR-7772 End

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $list = $list->orderBy($col, $orderBy);
            }

            $resp = [];
            if(isset($params['limit'])){
                $limit = $params['limit'];
                $resp = $list->paginate($limit)->toArray();
            } else{
                $list = $list->get()->toArray();
                $resp['data'] = $list;
            }

            return $resp;
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

            //For POCOR-7772 Start
            $permissions = checkAccess();

            if(isset($permissions)){
                if($permissions['super_admin'] != 1){
                    //For POCOR-8077 Start...
                    if($permissions['allowAllInstitutions'] != 1){
                        $institution_Ids = $permissions['institutionIds'];
                    }
                    //For POCOR-8077 End...
                }
            }
            //For POCOR-7772 End


            //For POCOR-8491 Start...
            $data = InstitutionStudent::with(
                    'institution:id,code,name',
                    'educationGrade:id,name',
                    'securityUser',
                    'securityUser.gender:id,name',
                    'studentStatus',
                    'academicPeriod:id,name',
                    'studentCustomFieldValue:id,text_value,number_value,decimal_value,textarea_value,date_value,time_value,file,student_custom_field_id,student_id',
                    'studentCustomFieldValue.studentCustomField:id,name'
                )
                ->where('institution_id', $institutionId)
                ->where('student_id', $studentId);
            //For POCOR-8491 End...

            //For POCOR-7772 Start
            if(isset($institution_Ids)){
                $data = $data->whereIn('institution_students.institution_id', $institution_Ids);
            }
            //For POCOR-7772 End

            $data = $data->first();

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


//    public function getStudentAbsences($request)
//    {
//        try {
//
//            //For POCOR-7772 Start
//            $permissions = checkAccess();
//
//            if(isset($permissions)){
//                if($permissions['super_admin'] != 1){
//                    //For POCOR-8077 Start...
//                    if($permissions['allowAllInstitutions'] != 1){
//                        $institution_Ids = $permissions['institutionIds'];
//                    }
//                    //For POCOR-8077 End...
//                }
//            }
//            //For POCOR-7772 End
//
//            $params = $request->all();
//
//            $getStudents = InstitutionStudentAbsenceDetails::with(
//                        'securityUser',
//                        'securityUser.gender:id,name',
//                        'educationGrade:id,name',
//                        'institutionClass:id,name',
//                        'academicPeriod:id,name',
//                        'institution:id,code,name'
//                    );
//
//            if(isset($params['academic_period_id'])){
//                $academic_period_id = $params['academic_period_id'];
//
//                $getStudents = $getStudents->where('academic_period_id', $academic_period_id);
//            }
//
//            //For POCOR-7772 Start
//            if(isset($institution_Ids)){
//                $getStudents = $getStudents->whereIn('institution_student_absence_details.institution_id', $institution_Ids);
//            }
//            //For POCOR-7772 End
//
//            $getStudents = $getStudents->select('student_id', 'institution_id', 'academic_period_id', 'institution_class_id', 'education_grade_id', 'modified_user_id', 'modified', 'created_user_id', 'created')->groupby('institution_id', 'student_id');
//
//            if(isset($params['order'])){
//                $orderBy = $params['order_by']??"ASC";
//                $col = $params['order'];
//                $getStudents = $getStudents->orderBy($col, $orderBy);
//            }
//
//            //$getStudents = $getStudents->paginate($limit)->toArray();
//            //dd("getStudents", $getStudents);
//
//
//            //For POCOR-8215/8216 start...
//            if(isset($params['limit'])){
//                $limit = $params['limit'];
//                $list = $getStudents->paginate($limit)->toArray();
//
//            } else {
//                $list['data'] = $getStudents->get()->toArray();
//            }
//            //For POCOR-8215/8216 end...
//
//            $data = [];
//
//            if(count($list['data']) > 0){
//                foreach($list['data'] as $k => $d){
//
//                    $data[$k] = $d;
//                    $dateData = InstitutionStudentAbsenceDetails::with('absenceType:id,name', 'studentAbsenceReason:id,name', 'period:id,name', 'subject:id,name')->where('student_id', $d['student_id'])->where('institution_id', $d['institution_id'])->get()->toArray();
//                    $arr = [];
//                    foreach($dateData as $key => $dd){
//                        $arr[$key]['period_id'] = $dd['period']['id']??Null;
//                        $arr[$key]['period_name'] = $dd['period']['name']??Null;
//                        $arr[$key]['subject_id'] = $dd['subject']['id']??Null;
//                        $arr[$key]['subject_name'] = $dd['subject']['name']??Null;
//                        $arr[$key]['absence_type_id'] = $dd['absence_type']['id']??Null;
//                        $arr[$key]['absence_type_name'] = $dd['absence_type']['name']??Null;
//                        $arr[$key]['student_absence_reason_id'] = $dd['student_absence_reason']['id']??Null;
//                        $arr[$key]['student_absence_reason_name'] = $dd['student_absence_reason']['name']??Null;
//                        $arr[$key]['comment'] = $dd['comment']??Null;
//                        $arr[$key]['date'] = $dd['date']??Null;
//
//                    }
//
//                    $data[$k]['date_data'] = $arr;
//                }
//            }
//
//            $list['data'] = $data;
//
//            return $list;
//
//        } catch (\Exception $e) {
//            Log::error(
//                'Failed to fetch list from DB',
//                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
//            );
//
//            return $this->sendErrorResponse('Institution Student Absences List Not Found');
//        }
//    }
//
//
//
//    public function getInstitutionStudentAbsences($request, $institutionId)
//    {
//        try {
//            $params = $request->all();
//
//
//            //For POCOR-7772 Start
//            $permissions = checkAccess();
//
//            if(isset($permissions)){
//                if($permissions['super_admin'] != 1){
//                    //For POCOR-8077 Start...
//                    if($permissions['allowAllInstitutions'] != 1){
//                        $institution_Ids = $permissions['institutionIds'];
//                    }
//                    //For POCOR-8077 End...
//                }
//            }
//            //For POCOR-7772 End
//
//            $getStudents = InstitutionStudentAbsenceDetails::with(
//                        'securityUser',
//                        'securityUser.gender:id,name',
//                        'educationGrade:id,name',
//                        'institutionClass:id,name',
//                        'academicPeriod:id,name',
//                        'institution:id,code,name'
//                    )->where('institution_id', $institutionId);
//
//            if(isset($params['academic_period_id'])){
//                $academic_period_id = $params['academic_period_id'];
//
//                $getStudents = $getStudents->where('academic_period_id', $academic_period_id);
//            }
//
//            $getStudents = $getStudents->select('student_id', 'institution_id', 'academic_period_id', 'institution_class_id', 'education_grade_id', 'modified_user_id', 'modified', 'created_user_id', 'created')->groupby('institution_id', 'student_id');
//
//            if(isset($params['order'])){
//                $orderBy = $params['order_by']??"ASC";
//                $col = $params['order'];
//                $getStudents = $getStudents->orderBy($col, $orderBy);
//            }
//
//
//            //For POCOR-7772 Start
//            if(isset($institution_Ids)){
//                $getStudents = $getStudents->whereIn('institution_student_absence_details.institution_id', $institution_Ids);
//            }
//            //For POCOR-7772 End
//
//
//
//            //$getStudents = $getStudents->paginate($limit)->toArray();
//            //dd("getStudents", $getStudents);
//
//
//            //For POCOR-8215/8216 start...
//            if(isset($params['limit'])){
//                $limit = $params['limit'];
//                $list = $getStudents->paginate($limit)->toArray();
//
//            } else {
//                $list['data'] = $getStudents->get()->toArray();
//            }
//            //For POCOR-8215/8216 end...
//
//
//            $data = [];
//
//            if(count($list['data']) > 0){
//                foreach($list['data'] as $k => $d){
//
//                    $data[$k] = $d;
//                    $dateData = InstitutionStudentAbsenceDetails::with('absenceType:id,name', 'studentAbsenceReason:id,name', 'period:id,name', 'subject:id,name')->where('student_id', $d['student_id'])->where('institution_id', $d['institution_id'])->get()->toArray();
//                    $arr = [];
//                    foreach($dateData as $key => $dd){
//                        $arr[$key]['period_id'] = $dd['period']['id']??Null;
//                        $arr[$key]['period_name'] = $dd['period']['name']??Null;
//                        $arr[$key]['subject_id'] = $dd['subject']['id']??Null;
//                        $arr[$key]['subject_name'] = $dd['subject']['name']??Null;
//                        $arr[$key]['absence_type_id'] = $dd['absence_type']['id']??Null;
//                        $arr[$key]['absence_type_name'] = $dd['absence_type']['name']??Null;
//                        $arr[$key]['student_absence_reason_id'] = $dd['student_absence_reason']['id']??Null;
//                        $arr[$key]['student_absence_reason_name'] = $dd['student_absence_reason']['name']??Null;
//                        $arr[$key]['comment'] = $dd['comment']??Null;
//                        $arr[$key]['date'] = $dd['date']??Null;
//
//                    }
//
//                    $data[$k]['date_data'] = $arr;
//                }
//            }
//
//            $list['data'] = $data;
//
//            return $list;
//
//        } catch (\Exception $e) {
//            Log::error(
//                'Failed to fetch list from DB',
//                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
//            );
//
//            return $this->sendErrorResponse('Institution Student Absences List Not Found');
//        }
//    }
//
//
//    public function getInstitutionStudentAbsencesData($request, $institutionId, $studentId)
//    {
//        try {
//
//            //For POCOR-7772 Start
//            $permissions = checkAccess();
//
//            if(isset($permissions)){
//                if($permissions['super_admin'] != 1){
//                    //For POCOR-8077 Start...
//                    if($permissions['allowAllInstitutions'] != 1){
//                        $institution_Ids = $permissions['institutionIds'];
//                    }
//                    //For POCOR-8077 End...
//                }
//            }
//            //For POCOR-7772 End
//
//            $getStudent = InstitutionStudentAbsenceDetails::with(
//                        'securityUser',
//                        'securityUser.gender:id,name',
//                        'educationGrade:id,name',
//                        'institutionClass:id,name',
//                        'academicPeriod:id,name',
//                        'institution:id,code,name'
//                    )
//                    ->where('institution_id', $institutionId)
//                    ->where('student_id', $studentId);
//
//            //For POCOR-7772 Start
//            if(isset($institution_Ids)){
//                $getStudent = $getStudent->whereIn('institution_student_absence_details.institution_id', $institution_Ids);
//            }
//            //For POCOR-7772 End
//
//            $getStudent = $getStudent->first();
//
//            if($getStudent){
//                $getStudent = $getStudent->toArray();
//
//                $dateData = InstitutionStudentAbsenceDetails::with('absenceType:id,name', 'studentAbsenceReason:id,name', 'period:id,name', 'subject:id,name')->where('student_id', $getStudent['student_id'])->where('institution_id', $getStudent['institution_id'])->get()->toArray();
//
//                $arr = [];
//                foreach($dateData as $key => $dd){
//                    $arr[$key]['period_id'] = $dd['period']['id']??Null;
//                    $arr[$key]['period_name'] = $dd['period']['name']??Null;
//                    $arr[$key]['subject_id'] = $dd['subject']['id']??Null;
//                    $arr[$key]['subject_name'] = $dd['subject']['name']??Null;
//                    $arr[$key]['absence_type_id'] = $dd['absence_type']['id']??Null;
//                    $arr[$key]['absence_type_name'] = $dd['absence_type']['name']??Null;
//                    $arr[$key]['student_absence_reason_id'] = $dd['student_absence_reason']['id']??Null;
//                    $arr[$key]['student_absence_reason_name'] = $dd['student_absence_reason']['name']??Null;
//                    $arr[$key]['comment'] = $dd['comment']??Null;
//                    $arr[$key]['date'] = $dd['date']??Null;
//
//                }
//                $getStudent['date_data'] = $arr;
//
//            } else {
//                $getStudent = [];
//            }
//            return $getStudent;
//        } catch (\Exception $e) {
//            Log::error(
//                'Failed to fetch data from DB',
//                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
//            );
//
//            return $this->sendErrorResponse('Institution Student Absences Data Not Found');
//        }
//    }
//
 // POCOR-9530 start
    public function getInstitutionStudentAbsences($request, $institutionId)
    {
        $request->merge(['institution_id' => $institutionId]);
        return $this->getStudentAbsences($request);
    }

    public function getInstitutionStudentAbsencesData($request, $institutionId, $studentId)
    {
        $request->merge([
            'institution_id' => $institutionId,
            'student_id' => $studentId,
            'limit' => 1
        ]);

        $data = $this->getStudentAbsences($request);
        return $data['data'][0] ?? [];
    }

    public function getStudentAbsences($request)
    {
        try {
            $params = $request->all();
            $institutionIds = $this->getAccessibleInstitutionIds();

//            Log::debug('Student Absences - Incoming Filters', $params);
            $query = $this->getBaseQuery();
            $params = $this->cleanFilterParams($params); // 🧼 clean up 0 values
            $query = $this->applyFilters($query, $params, $institutionIds);

            $query->select(
                'student_id', 'institution_id', 'academic_period_id',
                'institution_class_id', 'education_grade_id',
                'modified_user_id', 'modified', 'created_user_id', 'created'
            )->groupBy('institution_id', 'student_id');

            if (!empty($params['order'])) {
                $query->orderBy($params['order'], $params['order_by'] ?? 'ASC');
            }
//            $sqlPreview = $query->toSql();
//            $bindings = $query->getBindings();
//
//            Log::debug('Student Absences - SQL Query', ['sql' => $sqlPreview, 'bindings' => $bindings]);


            $limit = isset($params['limit']) ? (int) $params['limit'] : 50; // Default to 50

            $list = $query->paginate($limit)->toArray();

            foreach ($list['data'] as $k => $student) {
                $list['data'][$k] = $student;
                $list['data'][$k]['date_data'] = $this->loadAbsenceDateData($student['institution_id'], $student['student_id'], $params);
            }

            return $list;
        } catch (\Exception $e) {
            Log::error('Failed to fetch absences', ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return $this->sendErrorResponse('Student absences could not be retrieved');
        }
    }

    private function cleanFilterParams(array $params): array
    {
        foreach (['period', 'subject_id'] as $field) {
            if (isset($params[$field]) && (int)$params[$field] === 0) {
                unset($params[$field]);
            }
        }
        return $params;
    }
    private function getAccessibleInstitutionIds()
    {
        $permissions = checkAccess();

        if ($permissions['super_admin'] ?? false) {
            return null;
        }

        if (!empty($permissions['allowAllInstitutions'])) {
            return null;
        }

        return $permissions['institutionIds'] ?? null;
    }
    private function getBaseQuery()
    {
        return InstitutionStudentAbsenceDetails::with([
            'securityUser',
            'securityUser.gender:id,name',
            'educationGrade:id,name',
            'institutionClass:id,name',
            'academicPeriod:id,name',
            'institution:id,code,name'
        ]);
    }
    private function applyFilters($query, $params, $institutionIds = null)
    {
        $filterableFields = [
            'institution_id',
            'institution_class_id',
            'education_grade_id',
            'academic_period_id',
            'subject_id',
            'student_id',
            'date',
            'period'
        ];

        foreach ($filterableFields as $field) {
            if (!empty($params[$field])) {
                $query->where("institution_student_absence_details.$field", $params[$field]);
            }
        }

        if ($institutionIds !== null) {
            $query->whereIn('institution_student_absence_details.institution_id', $institutionIds);
        }

        return $query;
    }// POCOR-9530 end

    private function loadAbsenceDateData($institutionId, $studentId, $params = [])
    {
        $absencePeriod = $this->attendancePeriod($params, $institutionId, $studentId); //POCOR-9570
        $query = InstitutionStudentAbsenceDetails::with([
            'absenceType:id,name',
            'studentAbsenceReason:id,name',
           // 'attendancePeriod:id,name',
            'subject:id,name'
        ])
        ->where('student_id', $studentId)
        ->where('institution_id', $institutionId);

        if (!empty($params['date'])) {
            $query->whereDate('date', $params['date']);
        }

        if (!empty($params['period'])) {
            $query->where('period', $params['period']);
        }

        if (!empty($params['subject_id'])) {
            $query->where('subject_id', $params['subject_id']);
        }
        $records = $query->get();
       

        return $records->map(function ($record) use ($absencePeriod) {
            // Default values
            $periodId = null;
            $periodName = null;
            // If attendance type has periods
            if ($absencePeriod['type'] === 'period' || $absencePeriod['type'] === 'both') {
                $matchedPeriod = collect($absencePeriod['periods'])
                    ->firstWhere('period', $record->period);
                if ($matchedPeriod) {
                    $periodId   = $matchedPeriod->id;
                    $periodName = $matchedPeriod->name;
                }
            }
            return [
                'type' => $absencePeriod['type'],

                'period_id'   => $periodId,
                'period_name' => $periodName,

                'subject_id'   => $record->subject->id ?? null,
                'subject_name' => $record->subject->name ?? null,

                'absence_type_id'   => $record->absenceType->id ?? null,
                'absence_type_name' => $record->absenceType->name ?? null,

                'student_absence_reason_id'   => $record->studentAbsenceReason->id ?? null,
                'student_absence_reason_name' => $record->studentAbsenceReason->name ?? null,

                'comment' => $record->comment,
                'date'    => $record->date,
            ];
        })->toArray();
    }

    //POCOR-7547 Starts...
    public function getEducationGrades($request)
    {
        try {

            $params = $request->all();

            /*$sql = 'SELECT
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

            $list = DB::select(DB::raw($sql));*/



            $lists = DB::table('student_mark_type_status_grades')
                ->join('education_grades', 'education_grades.id', '=', 'student_mark_type_status_grades.education_grade_id')
                ->join('student_mark_type_statuses', 'student_mark_type_statuses.id', '=', 'student_mark_type_status_grades.student_mark_type_status_id')
                ->join('student_attendance_mark_types', 'student_attendance_mark_types.id', '=', 'student_mark_type_statuses.student_attendance_mark_type_id')
                ->join('academic_periods', 'academic_periods.id', '=', 'student_mark_type_statuses.academic_period_id')
                ->join('student_attendance_types', 'student_attendance_types.id', '=', 'student_attendance_mark_types.student_attendance_type_id')
                ->join('config_items', function ($q){
                    $q->where('config_items.code', '=', 'calculate_daily_attendance');
                })
                ->leftjoin('student_attendance_per_day_periods', 'student_attendance_per_day_periods.student_attendance_mark_type_id', '=', 'student_attendance_mark_types.id')
                ->select(
                    'academic_periods.name as academic_period_name',
                    'student_mark_type_statuses.academic_period_id',
                    'education_grades.name as education_grade_name',
                    'student_mark_type_status_grades.education_grade_id',
                    'student_attendance_types.code as attendance_by',
                    'student_attendance_per_day_periods.id',
                    'student_attendance_types.code',
                    'student_attendance_per_day_periods.name',
                    'attendance_per_day',
                    'date_enabled',
                    'date_disabled',
                    'config_items.value'
                )
                ->orderBy('education_grades.id', 'ASC')
                ->orderBy('student_attendance_per_day_periods.id', 'ASC');

            $resp = [];
            if(isset($params['limit'])){
                $limit = $params['limit'];
                $resp = $lists->paginate($limit)->toArray();
            } else{
                $lists = $lists->get()->toArray();
                $resp['data'] = $lists;
            }

            return $resp;

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

            //For POCOR-7772 Start
            $permissions = checkAccess();

            if(isset($permissions)){
                if($permissions['super_admin'] != 1){
                    //For POCOR-8077 Start...
                    if($permissions['allowAllInstitutions'] != 1){
                        $institution_Ids = $permissions['institutionIds'];
                    }
                    //For POCOR-8077 End...
                }
            }
            //For POCOR-7772 End
            $params = $request->all();

            $limit = config('constantvalues.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }


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
                    ->where('institution_classes.institution_id', $institutionId);

            //For POCOR-7772 Start
            if(isset($institution_Ids)){
                $data = $data->whereIn('institution_classes.institution_id', $institution_Ids);
            }
            //For POCOR-7772 End


            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $data = $data->orderBy($col, $orderBy);
            }

            $resp = [];
            if(isset($params['limit'])){
                $limit = $params['limit'];
                $resp = $data->paginate($limit)
                ->toArray();
            } else{
                $data = $data->get()->toArray();
                $resp['data'] = $data;
            }

            return $resp;
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
            $isLinked = $this->checkIfStudentLinked($param);

            if(!$isLinked){
                return 3;
            }

            //Removing Student Absence Record when absence_type_id = 0...

            if($param['absence_type_id'] == 0){
                $this->deleteStudentAbsenceRecord($param);

                DB::commit();
                return 2;
            }

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
                $storeArr['no_scheduled_class'] = 0;//1 is for No Scheduled Class

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
                $param['xyz'] = 1234;

                //This function removes the unnecessary columns...
                $values = removeNonColumnFields($param, 'institution_staff_attendances');

                $update = InstitutionStaffAttendances::where([
                    'staff_id' => $param['staff_id'],
                    'institution_id' => $param['institution_id'],
                    'academic_period_id' => $param['academic_period_id'],
                    'date' => $param['date']
                ])->update($values);

                $resp = 2;

            } else {
                $param['id'] = Str::Uuid();
                $param['created_user_id'] = JWTAuth::user()->id;
                $param['created'] = Carbon::now()->toDateTimeString();

                //This function removes the unnecessary columns...
                $values = removeNonColumnFields($param, 'institution_staff_attendances');

                $store = InstitutionStaffAttendances::insert($values);
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
    }//POCOR-7547 End...

    public function checkIfStudentLinked($param)
    {
        try {
            $check = InstitutionClassStudents::where([
                    'academic_period_id' => $param['academic_period_id'],
                    'education_grade_id' => $param['education_grade_id'],
                    'institution_id' => $param['institution_id'],
                    'institution_class_id' => $param['institution_class_id'],
                    'student_id' => $param['student_id'],
                ])
                ->first();

            if($check){
                return true;
            } else {
                return false;
            }
        }catch(\Exception $e) {
            return false;
        }
    }

    //For POCOR-8505 Start...
    public function deleteStudentAbsenceRecord($param)
    {
        try {
            $delete = InstitutionStudentAbsenceDetails::where([
                    'student_id' => $param['student_id'],
                    'institution_id' => $param['institution_id'],
                    'academic_period_id' => $param['academic_period_id'],
                    'institution_class_id' => $param['institution_class_id'],
                    'date' => $param['date'],
                    'period' => $param['period'],
                    'subject_id' => $param['subject_id']
                ])->delete();
            return true;
        } catch (\Exception $e) {

        }
    }
    //For POCOR-8505 End...

    //For POCOR-8491 Start...
    public function getStudentClasses($institutionId, $studentId)
    {
        try {
            $studentClasses = InstitutionClassStudents::with('institutionClass', 'institutionClass.subjects.institutionSubject')
                    ->where('institution_id', $institutionId)
                    ->where('student_id', $studentId)
                    ->get()
                    ->toArray();

            $list = [];

            foreach ($studentClasses as $key => $studentClass) {
                $list[$key]['id'] = $studentClass['institution_class']['id'];
                $list[$key]['name'] = $studentClass['institution_class']['name'];
                $subjects = [];
                foreach ($studentClass['institution_class']['subjects'] as $s => $subject) {
                    $subjects[$s]['id'] = $subject['institution_subject']['id'];
                    $subjects[$s]['name'] = $subject['institution_subject']['name'];
                }
                $list[$key]['subjects'] = $subjects;

            }

            return $list;
        } catch (\Exception $e) {
            Log::error(
                'Failed in getStudentClasses method.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return false;
        }
    }
    //For POCOR-8491 End...


    //POCOR-8221 Starts...
    public function getStudentTransferData($params, $institutionId, $studentId)
    {
        try {
            $list = InstitutionStudentTransfers::with('status')
                        ->where('student_id', $studentId)
                        ->where('institution_id', $institutionId);

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $list = $list->orderBy($col, $orderBy);
            }

            $resp = [];
            if(isset($params['limit'])){
                $limit = $params['limit'];
                $resp = $list->paginate($limit)->toArray();
            } else{
                $list = $list->get()->toArray();
                $resp['data'] = $list;
            }

            return $resp;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Student Transfer List Not Found');
        }
    }


    public function addStudentTransferData($params, $institutionId)
    {
        try {
            $requested_date = date('Y-m-d');

            $isExists = InstitutionStudentTransfers::where([
                            'student_id' => $params['student_id'],
                            'academic_period_id' => $params['academic_period_id'],
                            'education_grade_id' => $params['education_grade_id'],
                            'previous_academic_period_id' => $params['previous_academic_period_id'],
                            'previous_education_grade_id' => $params['previous_education_grade_id'],
                            'institution_id' => $params['institution_id'],
                            'previous_institution_id' => $params['previous_institution_id'],
                        ])
                        ->first();

            if($institutionId == $params['institution_id']){
                //Transfer In
                $workflow = WorkflowSteps::select("workflow_steps.id as status_id")
                            ->join('workflows', 'workflows.id', '=', 'workflow_steps.workflow_id')
                                ->where('workflows.code', 'STUDENT-TRANSFER-1001')
                                ->where('workflow_steps.name', 'Open')
                                ->first();

            } elseif($institutionId == $params['previous_institution_id']){
                //Transfer Out
                $workflow = WorkflowSteps::select("workflow_steps.id as status_id")
                            ->join('workflows', 'workflows.id', '=', 'workflow_steps.workflow_id')
                                ->where('workflows.code', 'STUDENT-TRANSFER-2001')
                                ->where('workflow_steps.name', 'Open')
                                ->first();

            } else {
                return 0;
            }

            if(!empty($isExists)){
                $updateArr['student_id'] = $params['student_id'];
                $updateArr['start_date'] = $params['enrolment_start_date']??NULL;
                $updateArr['end_date'] = $params['enrolment_end_date ']??Null;
                $updateArr['institution_id'] = $params['institution_id'];
                $updateArr['academic_period_id'] = $params['academic_period_id'];
                $updateArr['education_grade_id'] = $params['education_grade_id'];
                $updateArr['institution_class_id'] = $params['institution_class_id']??Null;
                $updateArr['previous_institution_id'] = $params['previous_institution_id'];
                $updateArr['previous_academic_period_id'] = $params['previous_academic_period_id'];
                $updateArr['previous_education_grade_id'] = $params['previous_education_grade_id'];
                $updateArr['student_transfer_reason_id'] = $params['student_transfer_reason_id'];
                $updateArr['comment'] = $params['comment']??"";
                $updateArr['all_visible'] = $params['all_visible'];
                $updateArr['status_id'] = $workflow->status_id??0;
                $updateArr['requested_date'] = $requested_date;
                $updateArr['assignee_id'] = JWTAuth::user()->id;
                $updateArr['modified_user_id'] = JWTAuth::user()->id;
                $updateArr['modified'] = Carbon::now()->toDateTimeString();

                $update = InstitutionStudentTransfers::where([
                            'student_id' => $params['student_id'],
                            'institution_id' => $params['institution_id'],
                            'academic_period_id' => $params['academic_period_id'],
                            'education_grade_id' => $params['education_grade_id'],
                            'previous_academic_period_id' => $params['previous_academic_period_id'],
                            'previous_education_grade_id' => $params['previous_education_grade_id'],
                        ])
                        ->update($updateArr);

            } else {
                $insertArr['student_id'] = $params['student_id'];
                $insertArr['start_date'] = $params['enrolment_start_date']??NULL;
                $insertArr['end_date'] = $params['enrolment_end_date ']??Null;
                $insertArr['institution_id'] = $params['institution_id'];
                $insertArr['academic_period_id'] = $params['academic_period_id'];
                $insertArr['education_grade_id'] = $params['education_grade_id'];
                $insertArr['institution_class_id'] = $params['institution_class_id']??Null;
                $insertArr['previous_institution_id'] = $params['previous_institution_id'];
                $insertArr['previous_academic_period_id'] = $params['previous_academic_period_id'];
                $insertArr['previous_education_grade_id'] = $params['previous_education_grade_id'];
                $insertArr['student_transfer_reason_id'] = $params['student_transfer_reason_id'];
                $insertArr['comment'] = $params['comment']??"";
                $insertArr['all_visible'] = $params['all_visible'];
                $insertArr['status_id'] = $workflow->status_id??0;
                $insertArr['requested_date'] = $requested_date;
                $insertArr['assignee_id'] = JWTAuth::user()->id;
                $insertArr['created_user_id'] = JWTAuth::user()->id;
                $insertArr['created'] = Carbon::now()->toDateTimeString();

                $insert = InstitutionStudentTransfers::insert($insertArr);
            }

            return 1;

        } catch (\Exception $e) {
            Log::error(
                'Failed to add student tranfer data.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Failed to add student tranfer data.');
        }
    }
    //POCOR-8221 Ends...

    public function getStudentAbsencesDetails($request, $openemis_no)
    {
        try {
            $absencesDetailsData = InstitutionStudentAbsenceDetails::with('securityUser','absenceType:id,name', 'studentAbsenceReason:id,name', 'subject','institution','institutionClass')
                ->whereHas('securityUser', function ($query) use ($openemis_no) {
                    $query->where('openemis_no', $openemis_no);
                })
                ->get()
                ->toArray();
            return $absencesDetailsData;

        } catch (\Exception $e) {
            Log::error(
                'Student Absences Data Not Found.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Student Absences Data Not Found.');
        }
    }

    //POCOR-9570
    public function attendancePeriod($params, $institutionId, $studentId)
    {

        $classId          = $params['institution_class_id'] ?? null;
        $academicPeriodId = $params['academic_period_id'] ?? null;
        $educationGradeId = $params['education_grade_id'] ?? null;

        // Base Mark Type Query
        $baseQuery = DB::table('student_attendance_mark_types as samt')
            ->join('student_attendance_types as sat', 'sat.id', '=', 'samt.student_attendance_type_id')
            ->select(
                'samt.id',
                'samt.code',
                'samt.attendance_per_day',
                'sat.code as attendance_type_code'
            );
        
        // Apply Filters ONLY If Params Exist
        if ($academicPeriodId && $educationGradeId) {

            $baseQuery->join('student_mark_type_statuses as smts', 'smts.student_attendance_mark_type_id', '=', 'samt.id')
                ->join('student_mark_type_status_grades as smtsg', 'smtsg.student_mark_type_status_id', '=', 'smts.id')
                ->where('smts.academic_period_id', $academicPeriodId)
                ->where('smtsg.education_grade_id', $educationGradeId);

            if ($classId) {
                $baseQuery->join('institution_class_grades as icg', 'icg.education_grade_id', '=', 'smtsg.education_grade_id')
                    ->where('icg.institution_class_id', $classId);
            }
        }
        

        $markType = (clone $baseQuery)
            ->where('sat.code', 'DAY_AND_SUBJECT')
            ->first();

        if (!$markType) {
            $markType = $baseQuery->first();
        }

        if (!$markType && $markType !=null) {
            return [
                'type'      => 'day',
                'subject'   => false,
                'periods'   => [],
                'mark_type' => null
            ];
        }
        $periods = DB::table('student_attendance_per_day_periods')
        ->orderBy('order')
        ->get();
       
        if ($periods->isEmpty()) {

            $absenceRecord = DB::table('institution_student_absence_details')
                ->where('student_id', $studentId)
                ->where('institution_id', $institutionId)
                ->first();
            if ($absenceRecord && $absenceRecord->period) {

                $periodNumber = (int) $absenceRecord->period;

                $periods = collect([
                    (object)[
                        'id' => $periodNumber,
                        'name' => 'Period ' . $periodNumber,
                        'period' => $periodNumber, 
                        'modified_user_id' => null,
                        'modified' => null,
                        'created_user_id' => null,
                        'created' => null,
                        'order' => $periodNumber
                    ]
                ]);
               
            }
        }
        $hasPeriod  = $periods->isNotEmpty();
        $hasSubject  = $markType && $markType->attendance_type_code === 'DAY_AND_SUBJECT';

        if ($hasSubject && $hasPeriod) {
            $type = 'both';
        } elseif ($hasSubject) {
            $type = 'subject';
        } elseif ($hasPeriod) {
            $type = 'period';
        } else {
            $type = 'day';
        }
        return [
            'type'      => $type,
            'subject'   => $hasSubject,
            'periods'   => $periods->values()->toArray(),
            'mark_type' => $markType?->code
        ];
    }

    /*private function loadAbsenceDateDatabkp($institutionId, $studentId, $params = [])
    {
        $query = InstitutionStudentAbsenceDetails::with([
            'absenceType:id,name',
            'studentAbsenceReason:id,name',
            'attendancePeriod:id,name',
            'subject:id,name'
        ])->where('student_id', $studentId)
            ->where('institution_id', $institutionId);

        if (!empty($params['date'])) {
            $query->whereDate('date', $params['date']);
        }

        if (!empty($params['period'])) {
            $query->where('period', $params['period']);
        }

        if (!empty($params['subject_id'])) {
            $query->where('subject_id', $params['subject_id']);
        }

        $records = $query->get();

        return $records->map(function ($record) {
            return [
                'period_id' => $record->attendancePeriod->id ?? null,
                'period_name' => $record->attendancePeriod->name ?? null,
                'subject_id' => $record->subject->id ?? null,
                'subject_name' => $record->subject->name ?? null,
                'absence_type_id' => $record->absenceType->id ?? null,
                'absence_type_name' => $record->absenceType->name ?? null,
                'student_absence_reason_id' => $record->studentAbsenceReason->id ?? null,
                'student_absence_reason_name' => $record->studentAbsenceReason->name ?? null,
                'comment' => $record->comment,
                'date' => $record->date,
            ];
        })->toArray();
    }*/
}
