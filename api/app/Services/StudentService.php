<?php

namespace App\Services;

use App\Http\Controllers\Controller;
use App\Repositories\StudentRepository;
use JWTAuth;
use Illuminate\Support\Facades\Log;

class StudentService extends Controller
{

    protected $studentRepository;

    public function __construct(
    StudentRepository $studentRepository) {
        $this->studentRepository = $studentRepository;
    }

    
    public function getStudents($request)
    {
        try {
            $data = $this->studentRepository->getStudents($request);
            $list = [];
            if(count($data['data']) > 0){
                foreach($data['data'] as $k => $d){
                    $list[$k]['student_id'] = $d['student_id'];
                    $list[$k]['first_name'] = $d['security_user']['first_name'];
                    $list[$k]['middle_name'] = $d['security_user']['middle_name'];
                    $list[$k]['third_name'] = $d['security_user']['third_name'];
                    $list[$k]['last_name'] = $d['security_user']['last_name'];
                    $list[$k]['openemis_no'] = $d['security_user']['openemis_no'];
                    $list[$k]['gender_id'] = $d['security_user']['gender']['id'];
                    $list[$k]['gender_name'] = $d['security_user']['gender']['name'];
                    $list[$k]['start_date'] = $d['start_date'];
                    $list[$k]['start_year'] = $d['start_year'];
                    $list[$k]['end_date'] = $d['end_date'];
                    $list[$k]['end_year'] = $d['end_year'];
                    $list[$k]['institution_id'] = $d['institution_id'];
                    $list[$k]['institution_code'] = $d['institution']['code'];
                    $list[$k]['institution_name'] = $d['institution']['name'];
                    $list[$k]['student_status_id'] = $d['student_status_id'];
                    $list[$k]['student_status_name'] = $d['student_status']['name'];
                    $list[$k]['student_status_code'] = $d['student_status']['code'];
                    $list[$k]['education_grade_id'] = $d['education_grade_id'];
                    $list[$k]['education_grade_name'] = $d['education_grade']['name'];
                    $list[$k]['academic_period_id'] = $d['academic_period_id'];
                    $list[$k]['academic_period_name'] = $d['academic_period']['name'];
                    $list[$k]['previous_institution_student_id'] = $d['previous_institution_student_id'];
                    $list[$k]['modified_user_id'] = $d['modified_user_id'];
                    $list[$k]['modified'] = $d['modified'];
                    $list[$k]['created_user_id'] = $d['created_user_id'];
                    $list[$k]['created'] = $d['created'];
                }
            }


            $data['data'] = $list;

            return $data;
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
            $data = $this->studentRepository->getInstitutionStudents($request, $institutionId);
            $list = [];
            if(count($data['data']) > 0){
                foreach($data['data'] as $k => $d){
                    $list[$k]['student_id'] = $d['student_id'];
                    $list[$k]['first_name'] = $d['security_user']['first_name'];
                    $list[$k]['middle_name'] = $d['security_user']['middle_name'];
                    $list[$k]['third_name'] = $d['security_user']['third_name'];
                    $list[$k]['last_name'] = $d['security_user']['last_name'];
                    $list[$k]['openemis_no'] = $d['security_user']['openemis_no'];
                    $list[$k]['gender_id'] = $d['security_user']['gender']['id'];
                    $list[$k]['gender_name'] = $d['security_user']['gender']['name'];
                    $list[$k]['start_date'] = $d['start_date'];
                    $list[$k]['start_year'] = $d['start_year'];
                    $list[$k]['end_date'] = $d['end_date'];
                    $list[$k]['end_year'] = $d['end_year'];
                    $list[$k]['institution_id'] = $d['institution_id'];
                    $list[$k]['institution_code'] = $d['institution']['code'];
                    $list[$k]['institution_name'] = $d['institution']['name'];
                    $list[$k]['student_status_id'] = $d['student_status_id'];
                    $list[$k]['student_status_name'] = $d['student_status']['name'];
                    $list[$k]['student_status_code'] = $d['student_status']['code'];
                    $list[$k]['education_grade_id'] = $d['education_grade_id'];
                    $list[$k]['education_grade_name'] = $d['education_grade']['name'];
                    $list[$k]['academic_period_id'] = $d['academic_period_id'];
                    $list[$k]['academic_period_name'] = $d['academic_period']['name'];
                    $list[$k]['previous_institution_student_id'] = $d['previous_institution_student_id'];
                    $list[$k]['modified_user_id'] = $d['modified_user_id'];
                    $list[$k]['modified'] = $d['modified'];
                    $list[$k]['created_user_id'] = $d['created_user_id'];
                    $list[$k]['created'] = $d['created'];
                }
            }


            $data['data'] = $list;

            return $data;
            
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
            $data = $this->studentRepository->getInstitutionStudentData($request, $institutionId, $studentId);
            
            $resp = [];
            if($data){
                $resp['student_id'] = $data['student_id'];
                $resp['first_name'] = $data['security_user']['first_name'];
                $resp['middle_name'] = $data['security_user']['middle_name'];
                $resp['third_name'] = $data['security_user']['third_name'];
                $resp['last_name'] = $data['security_user']['last_name'];
                $resp['openemis_no'] = $data['security_user']['openemis_no'];
                $resp['gender_id'] = $data['security_user']['gender']['id'];
                $resp['gender_name'] = $data['security_user']['gender']['name'];
                $resp['start_date'] = $data['start_date'];
                $resp['start_year'] = $data['start_year'];
                $resp['end_date'] = $data['end_date'];
                $resp['end_year'] = $data['end_year'];
                $resp['institution_id'] = $data['institution_id'];
                $resp['institution_code'] = $data['institution']['code'];
                $resp['institution_name'] = $data['institution']['name'];
                $resp['student_status_id'] = $data['student_status_id'];
                $resp['student_status_name'] = $data['student_status']['name'];
                $resp['student_status_code'] = $data['student_status']['code'];
                $resp['education_grade_id'] = $data['education_grade_id'];
                $resp['education_grade_name'] = $data['education_grade']['name'];
                $resp['academic_period_id'] = $data['academic_period_id'];
                $resp['academic_period_name'] = $data['academic_period']['name'];
                $resp['previous_institution_student_id'] = $data['previous_institution_student_id'];
                $resp['modified_user_id'] = $data['modified_user_id'];
                $resp['modified'] = $data['modified'];
                $resp['created_user_id'] = $data['created_user_id'];
                $resp['created'] = $data['created'];
                
            }
            return $resp;
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
            $data = $this->studentRepository->getStudentAbsences($request);


            $list = [];
            if(count($data['data']) > 0){
                foreach($data['data'] as $k => $d){
                    $list[$k]['student_id'] = $d['student_id']??NULL;
                    $list[$k]['first_name'] = $d['security_user']['first_name']??NULL;
                    $list[$k]['middle_name'] = $d['security_user']['middle_name']??NULL;
                    $list[$k]['third_name'] = $d['security_user']['third_name']??NULL;
                    $list[$k]['last_name'] = $d['security_user']['last_name']??NULL;
                    $list[$k]['openemis_no'] = $d['security_user']['openemis_no']??NULL;
                    $list[$k]['gender_id'] = $d['security_user']['gender']['id']??NULL;
                    $list[$k]['gender_name'] = $d['security_user']['gender']['name']??NULL;
                    $list[$k]['institution_id'] = $d['institution_id']??NULL;
                    $list[$k]['institution_code'] = $d['institution']['code']??NULL;
                    $list[$k]['institution_name'] = $d['institution']['name']??NULL;
                    $list[$k]['education_grade_id'] = $d['education_grade_id']??NULL;
                    $list[$k]['education_grade_name'] = $d['education_grade']['name']??NULL;
                    $list[$k]['academic_period_id'] = $d['academic_period_id']??NULL;
                    $list[$k]['academic_period_name'] = $d['academic_period']['name']??NULL;
                    $list[$k]['institution_class_id'] = $d['institution_class_id']??NULL;
                    $list[$k]['institution_class_name'] = $d['institution_class']['name']??NULL;
                    $list[$k]['date'] = $d['date_data']??NULL;
                    $list[$k]['modified_user_id'] = $d['modified_user_id'];
                    $list[$k]['modified'] = $d['modified'];
                    $list[$k]['created_user_id'] = $d['created_user_id'];
                    $list[$k]['created'] = $d['created'];
                }
            }

            $data['data'] = $list;

            return $data;
            
        } catch (\Exception $e) {
            dd($e);
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
            $data = $this->studentRepository->getInstitutionStudentAbsences($request, $institutionId);
            $list = [];
            if(count($data['data']) > 0){
                foreach($data['data'] as $k => $d){
                    $list[$k]['student_id'] = $d['student_id']??NULL;
                    $list[$k]['first_name'] = $d['security_user']['first_name']??NULL;
                    $list[$k]['middle_name'] = $d['security_user']['middle_name']??NULL;
                    $list[$k]['third_name'] = $d['security_user']['third_name']??NULL;
                    $list[$k]['last_name'] = $d['security_user']['last_name']??NULL;
                    $list[$k]['openemis_no'] = $d['security_user']['openemis_no']??NULL;
                    $list[$k]['gender_id'] = $d['security_user']['gender']['id']??NULL;
                    $list[$k]['gender_name'] = $d['security_user']['gender']['name']??NULL;
                    $list[$k]['institution_id'] = $d['institution_id']??NULL;
                    $list[$k]['institution_code'] = $d['institution']['code']??NULL;
                    $list[$k]['institution_name'] = $d['institution']['name']??NULL;
                    $list[$k]['education_grade_id'] = $d['education_grade_id']??NULL;
                    $list[$k]['education_grade_name'] = $d['education_grade']['name']??NULL;
                    $list[$k]['academic_period_id'] = $d['academic_period_id']??NULL;
                    $list[$k]['academic_period_name'] = $d['academic_period']['name']??NULL;
                    $list[$k]['institution_class_id'] = $d['institution_class_id']??NULL;
                    $list[$k]['institution_class_name'] = $d['institution_class']['name']??NULL;
                    $list[$k]['date'] = $d['date_data']??NULL;
                    $list[$k]['modified_user_id'] = $d['modified_user_id'];
                    $list[$k]['modified'] = $d['modified'];
                    $list[$k]['created_user_id'] = $d['created_user_id'];
                    $list[$k]['created'] = $d['created'];
                }
            }

            $data['data'] = $list;

            return $data;
            
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
            $data = $this->studentRepository->getInstitutionStudentAbsencesData($request, $institutionId, $studentId);
            
            $resp = [];
            if($data){
                $resp['student_id'] = $data['student_id']??NULL;
                $resp['first_name'] = $data['security_user']['first_name']??NULL;
                $resp['middle_name'] = $data['security_user']['middle_name']??NULL;
                $resp['third_name'] = $data['security_user']['third_name']??NULL;
                $resp['last_name'] = $data['security_user']['last_name']??NULL;
                $resp['openemis_no'] = $data['security_user']['openemis_no']??NULL;
                $resp['gender_id'] = $data['security_user']['gender']['id']??NULL;
                $resp['gender_name'] = $data['security_user']['gender']['name']??NULL;
                $resp['institution_id'] = $data['institution_id']??NULL;
                $resp['institution_code'] = $data['institution']['code']??NULL;
                $resp['institution_name'] = $data['institution']['name']??NULL;
                $resp['education_grade_id'] = $data['education_grade_id']??NULL;
                $resp['education_grade_name'] = $data['education_grade']['name']??NULL;
                $resp['academic_period_id'] = $data['academic_period_id']??NULL;
                $resp['academic_period_name'] = $data['academic_period']['name']??NULL;
                $resp['institution_class_id'] = $data['institution_class_id']??NULL;
                $resp['institution_class_name'] = $data['institution_class']['name']??NULL;
                $resp['date'] = $data['date_data']??NULL;
                $resp['modified_user_id'] = $data['modified_user_id'];
                $resp['modified'] = $data['modified'];
                $resp['created_user_id'] = $data['created_user_id'];
                $resp['created'] = $data['created'];
            }
            return $resp;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution Student Absences Data Not Found');
        }
    }
}
