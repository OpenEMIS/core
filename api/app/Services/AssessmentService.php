<?php

namespace App\Services;

use App\Http\Controllers\Controller;
use App\Repositories\AssessmentRepository;
use JWTAuth;
use Illuminate\Support\Facades\Log;

class AssessmentService extends Controller
{

    protected $assessmentRepository;

    public function __construct(AssessmentRepository $assessmentRepository) 
    {
        $this->assessmentRepository = $assessmentRepository;
    }

    public function getEducationGradeList($request)
    {
        try {
            $data = $this->assessmentRepository->getEducationGradeList($request);
            $list = [];
            if(count($data) > 0){
                foreach($data as $k => $d){
                    $list[$k]['id'] = $d['id'];
                    $list[$k]['code'] = $d['code'];
                    $list[$k]['name'] = $d['name'];
                    $list[$k]['description'] = $d['description'];
                    $list[$k]['academic_period_id'] = $d['academic_period_id'];
                    $list[$k]['education_grade_id'] = $d['education_grade_id'];
                    $list[$k]['modified_user_id'] = $d['modified_user_id'];
                    $list[$k]['modified'] = $d['modified'];
                    $list[$k]['created_user_id'] = $d['created_user_id'];
                    $list[$k]['created'] = $d['created'];
                }
            }

            return $list;

        } catch (\Exception $e) {
            Log::error(
                'Failed to get Assessment Education Grade List.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            
            return $this->sendErrorResponse('Failed to get Assessment Education Grade List.');
        }
    }

    public function getAssessmentItemList($request)
    {
        try {
            $data = $this->assessmentRepository->getAssessmentItemList($request);
            $list = [];
            if(count($data['data']) > 0){
                foreach($data['data'] as $k => $d){
                    $list[$k]['id'] = $d['id'];
                    $list[$k]['weight'] = $d['weight'];
                    $list[$k]['classification'] = $d['classification'];
                    $list[$k]['assessment_id'] = $d['assessment_id'];
                    $list[$k]['education_subject_id'] = $d['education_subject_id'];
                    $list[$k]['modified_user_id'] = $d['modified_user_id'];
                    $list[$k]['modified'] = $d['modified'];
                    $list[$k]['created_user_id'] = $d['created_user_id'];
                    $list[$k]['created'] = $d['created'];
                    $list[$k]['education_subject_name'] = $d['education_subjects']['name'];
                }
            }

            $data['data'] = $list;
            return $data;

        } catch (\Exception $e) {
            Log::error(
                'Failed to get Assessment Item List.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get Assessment Item List.');
        }
    }

    public function getAssessmentPeriodList($request)
    {
        try {
            $data = $this->assessmentRepository->getAssessmentPeriodList($request);
            $list = [];
            if(count($data['data']) > 0){
                foreach($data['data'] as $k => $d){
                    $list[$k]['id'] = $d['id'];
                    $list[$k]['code'] = $d['code'];
                    $list[$k]['name'] = $d['name'];
                    $list[$k]['start_date'] = $d['start_date'];
                    $list[$k]['end_date'] = $d['end_date'];
                    $list[$k]['date_enabled'] = $d['date_enabled'];
                    $list[$k]['date_disabled'] = $d['date_disabled'];
                    $list[$k]['weight'] = $d['weight'];
                    $list[$k]['academic_term'] = $d['academic_term'];
                    $list[$k]['assessment_id'] = $d['assessment_id'];
                    $list[$k]['education_grade_id'] = $d['assessments']['education_grade_id'];
                    $list[$k]['education_grade_code'] = $d['assessments']['code'];
                    $list[$k]['education_grade_name'] = $d['assessments']['name'];
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
                'Failed to get Assessment Period List.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get Assessment Period List.');
        }
    }

    public function getAssessmentItemGradingTypeList($request)
    {
        try {
            $data = $this->assessmentRepository->getAssessmentItemGradingTypeList($request);
            $list = [];
            if(count($data['data']) > 0){
                foreach($data['data'] as $k => $d){
                    $list[$k]['id'] = $d['id'];
                    $list[$k]['code'] = $d['code'];
                    $list[$k]['name'] = $d['name'];
                    $list[$k]['pass_mark'] = $d['pass_mark'];
                    $list[$k]['max'] = $d['max'];
                    $list[$k]['result_type'] = $d['result_type'];
                    // $list[$k]['date_disabled'] = $d['date_disabled'];
                    $list[$k]['assessment_grading_options'] = $d['assessment_grading_options'];
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
                'Failed to get Assessment Item Grading Type List.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get Assessment Item Grading Type List.');
        }
    }

    public function getAssessmentGradingOptionList($request)
    {
        try {
            $data = $this->assessmentRepository->getAssessmentGradingOptionList($request);
            $list = [];
            if(count($data) > 0){
                foreach($data as $k => $d){
                    $list[$k]['id'] = $d['id'];
                    $list[$k]['code'] = $d['code'];
                    $list[$k]['name'] = $d['name'];
                    $list[$k]['min'] = $d['min'];
                    $list[$k]['max'] = $d['max'];
                    $list[$k]['order'] = $d['order'];
                    $list[$k]['visible'] = $d['visible'];
                    $list[$k]['modified_user_id'] = $d['modified_user_id'];
                    $list[$k]['modified'] = $d['modified'];
                    $list[$k]['created_user_id'] = $d['created_user_id'];
                    $list[$k]['created'] = $d['created'];
                }
            }

            return $list;

        } catch (\Exception $e) {
            Log::error(
                'Failed to get Assessment Grading Option List.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            
            return $this->sendErrorResponse('Failed to get Assessment Grading Option List.');
        }
    }
    

}