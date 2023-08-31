<?php

namespace App\Repositories;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use JWTAuth;
use App\Models\InstitutionStudent;
use App\Models\InstitutionStudentAbsenceDetails;


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
}

