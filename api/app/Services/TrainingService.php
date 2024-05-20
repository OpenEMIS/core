<?php

namespace App\Services;

use App\Http\Controllers\Controller;
use App\Repositories\TrainingRepository;
use JWTAuth;
use Illuminate\Support\Facades\Log;

class TrainingService extends Controller
{

    protected $trainingRepository;

    public function __construct(TrainingRepository $trainingRepository)
    {
        $this->trainingRepository = $trainingRepository;
    }


    //POCOR-8100 start...
    public function getAllTrainingCourses($params)
    {
        try {
            $data = $this->trainingRepository->getAllTrainingCourses($params);
            
            $resp = [];

            foreach ($data['data'] as $k => $d) {
                $resp[$k]['id'] = $d['id'];
                $resp[$k]['code'] = $d['code'];
                $resp[$k]['name'] = $d['name'];
                $resp[$k]['description'] = $d['description'];
                $resp[$k]['objective'] = $d['objective'];
                $resp[$k]['credit_hours'] = $d['credit_hours'];
                $resp[$k]['duration'] = $d['duration'];
                $resp[$k]['number_of_months'] = $d['number_of_months'];
                $resp[$k]['special_education_needs'] = $d['special_education_needs'];
                $resp[$k]['file_name'] = $d['file_name'];
                $resp[$k]['file_content'] = "";
                if(isset($d['file_content'])){
                    $resp[$k]['file_content'] = json_encode($d['file_content'], true);
                }
                $resp[$k]['training_field_of_study_id'] = $d['training_field_of_study_id'];
                $resp[$k]['training_field_of_study_name'] = $d['training_field_of_study_name'];
                $resp[$k]['training_course_type_id'] = $d['training_course_type_id'];
                $resp[$k]['training_course_type_name'] = $d['training_course_type_name'];
                $resp[$k]['training_course_category_id'] = $d['training_course_category_id'];
                $resp[$k]['training_course_category_name'] = $d['training_course_category_name'];
                $resp[$k]['training_mode_of_delivery_id'] = $d['training_mode_of_delivery_id'];
                $resp[$k]['training_mode_of_delivery_name'] = $d['training_mode_of_delivery_name'];
                $resp[$k]['training_requirement_id'] = $d['training_requirement_id'];
                $resp[$k]['training_requirement_name'] = $d['training_requirement_name'];
                $resp[$k]['training_level_id'] = $d['training_level_id'];
                $resp[$k]['training_level_name'] = $d['training_level_name'];
                $resp[$k]['assignee_id'] = $d['assignee_id'];
                $resp[$k]['assignee_name'] = $d['first_name']." ".$d['last_name'];
                $resp[$k]['status_id'] = $d['status_id'];
                $resp[$k]['modified_user_id'] = $d['modified_user_id'];
                $resp[$k]['modified'] = $d['modified'];
                $resp[$k]['created_user_id'] = $d['created_user_id'];
                $resp[$k]['created'] = $d['created'];
                
            }


            /*if(isset($params['limit'])){
                $data['data'] = $resp;
                return $data; 
            } else {
                return $resp;
            }*/

            $data['data'] = $resp;
            return $data;

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Training Courses List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Training Courses List Not Found.');
        }
    }



    public function getTrainingCourseData($courseId)
    {
        try {
            $data = $this->trainingRepository->getTrainingCourseData($courseId);
            $resp = [];
            if(!empty($data)){
                $resp['id'] = $data['id'];
                $resp['code'] = $data['code'];
                $resp['name'] = $data['name'];
                $resp['description'] = $data['description'];
                $resp['objective'] = $data['objective'];
                $resp['credit_hours'] = $data['credit_hours'];
                $resp['duration'] = $data['duration'];
                $resp['number_of_months'] = $data['number_of_months'];
                $resp['special_education_needs'] = $data['special_education_needs'];
                $resp['file_name'] = $data['file_name'];
                $resp['file_content'] = "";
                if(isset($data['file_content'])){
                    $resp['file_content'] = json_encode($data['file_content'], true);
                }
                $resp['training_field_of_study_id'] = $data['training_field_of_study_id'];
                $resp['training_field_of_study_name'] = $data['training_field_of_study_name'];
                $resp['training_course_type_id'] = $data['training_course_type_id'];
                $resp['training_course_type_name'] = $data['training_course_type_name'];
                $resp['training_course_category_id'] = $data['training_course_category_id'];
                $resp['training_course_category_name'] = $data['training_course_category_name'];
                $resp['training_mode_of_delivery_id'] = $data['training_mode_of_delivery_id'];
                $resp['training_mode_of_delivery_name'] = $data['training_mode_of_delivery_name'];
                $resp['training_requirement_id'] = $data['training_requirement_id'];
                $resp['training_requirement_name'] = $data['training_requirement_name'];
                $resp['training_level_id'] = $data['training_level_id'];
                $resp['training_level_name'] = $data['training_level_name'];
                $resp['assignee_id'] = $data['assignee_id'];
                $resp['assignee_name'] = $data['first_name']." ".$data['last_name'];
                $resp['status_id'] = $data['status_id'];
                $resp['modified_user_id'] = $data['modified_user_id'];
                $resp['modified'] = $data['modified'];
                $resp['created_user_id'] = $data['created_user_id'];
                $resp['created'] = $data['created'];
            }
            return $resp;

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Training Courses Data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Training Courses Data Not Found.');
        }
    }


    public function getTrainingProviders($params)
    {
        try {
            $data = $this->trainingRepository->getTrainingProviders($params);

            return $data;

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Training Providers List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Training Providers List Not Found.');
        }
    }


    public function getTrainingProvidersData($providerId)
    {
        try {
            $data = $this->trainingRepository->getTrainingProvidersData($providerId);

            return $data;

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Training Provider Data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Training Provider Data Not Found.');
        }
    }


    public function getTrainingSessions($params)
    {
        try {
            $data = $this->trainingRepository->getTrainingSessions($params);

            $resp = [];

            if(count($data) > 0){
                foreach ($data['data'] as $k => $d) {
                    $resp[$k]['id'] = $d['id'];
                    $resp[$k]['code'] = $d['code'];
                    $resp[$k]['name'] = $d['name'];
                    $resp[$k]['start_date'] = $d['start_date'];
                    $resp[$k]['end_date'] = $d['end_date'];
                    $resp[$k]['comment'] = $d['comment'];
                    $resp[$k]['training_course_id'] = $d['training_course_id'];
                    $resp[$k]['training_provider_id'] = $d['training_provider_id'];
                    $resp[$k]['assignee_id'] = $d['assignee_id'];
                    $resp[$k]['status_id'] = $d['status_id'];
                    $resp[$k]['area_id'] = $d['area_id'];
                    $resp[$k]['trainers'] = [];
                    $trainees = [];
                    foreach ($d['training_session_trainee'] as $t => $trainee) {
                        $trainees[$t]['id'] = $trainee['id'];
                        $trainees[$t]['first_name'] = $trainee['first_name'];
                        $trainees[$t]['middle_name'] = $trainee['middle_name'];
                        $trainees[$t]['third_name'] = $trainee['third_name'];
                        $trainees[$t]['last_name'] = $trainee['last_name'];
                        $trainees[$t]['openemis_no'] = $trainee['openemis_no'];
                        $trainees[$t]['full_name'] = $trainee['full_name'];
                        $trainees[$t]['name_with_id'] = $trainee['name_with_id'];
                    }
                    $resp[$k]['trainers'] = $trainees;

                    $resp[$k]['evaluators'] = [];
                    $evaluators = [];
                    foreach ($d['training_session_evaluator'] as $e => $evaluator) {
                        $evaluators[$e]['id'] = $evaluator['id'];
                        $evaluators[$e]['first_name'] = $evaluator['first_name'];
                        $evaluators[$e]['middle_name'] = $evaluator['middle_name'];
                        $evaluators[$e]['third_name'] = $evaluator['third_name'];
                        $evaluators[$e]['last_name'] = $evaluator['last_name'];
                        $evaluators[$e]['openemis_no'] = $evaluator['openemis_no'];
                        $evaluators[$e]['full_name'] = $evaluator['full_name'];
                        $evaluators[$e]['name_with_id'] = $evaluator['name_with_id'];
                    }
                    $resp[$k]['evaluators'] = $evaluators;
                    $resp[$k]['modified_user_id'] = $d['modified_user_id'];
                    $resp[$k]['modified'] = $d['modified'];
                    $resp[$k]['created_user_id'] = $d['created_user_id'];
                    $resp[$k]['created'] = $d['created'];
                }
            }

            return $resp;

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Training Sessions List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Training Sessions List Not Found.');
        }
    }


    public function getTrainingSessionData($sessionId)
    {
        try {
            $data = $this->trainingRepository->getTrainingSessionData($sessionId);
            $resp = [];

            if(!empty($data)){
                $resp['id'] = $data['id'];
                $resp['code'] = $data['code'];
                $resp['name'] = $data['name'];
                $resp['start_date'] = $data['start_date'];
                $resp['end_date'] = $data['end_date'];
                $resp['comment'] = $data['comment'];
                $resp['training_course_id'] = $data['training_course_id'];
                $resp['training_provider_id'] = $data['training_provider_id'];
                $resp['assignee_id'] = $data['assignee_id'];
                $resp['status_id'] = $data['status_id'];
                $resp['area_id'] = $data['area_id'];
                $resp['trainers'] = [];
                $trainees = [];
                foreach ($data['training_session_trainee'] as $t => $trainee) {
                    $trainees[$t]['id'] = $trainee['id'];
                    $trainees[$t]['first_name'] = $trainee['first_name'];
                    $trainees[$t]['middle_name'] = $trainee['middle_name'];
                    $trainees[$t]['third_name'] = $trainee['third_name'];
                    $trainees[$t]['last_name'] = $trainee['last_name'];
                    $trainees[$t]['openemis_no'] = $trainee['openemis_no'];
                    $trainees[$t]['full_name'] = $trainee['full_name'];
                    $trainees[$t]['name_with_id'] = $trainee['name_with_id'];
                }
                $resp['trainers'] = $trainees;

                $resp['evaluators'] = [];
                $evaluators = [];
                foreach ($data['training_session_evaluator'] as $e => $evaluator) {
                    $evaluators[$e]['id'] = $evaluator['id'];
                    $evaluators[$e]['first_name'] = $evaluator['first_name'];
                    $evaluators[$e]['middle_name'] = $evaluator['middle_name'];
                    $evaluators[$e]['third_name'] = $evaluator['third_name'];
                    $evaluators[$e]['last_name'] = $evaluator['last_name'];
                    $evaluators[$e]['openemis_no'] = $evaluator['openemis_no'];
                    $evaluators[$e]['full_name'] = $evaluator['full_name'];
                    $evaluators[$e]['name_with_id'] = $evaluator['name_with_id'];
                }
                $resp['evaluators'] = $evaluators;
                $resp['modified_user_id'] = $data['modified_user_id'];
                $resp['modified'] = $data['modified'];
                $resp['created_user_id'] = $data['created_user_id'];
                $resp['created'] = $data['created'];
            }

            return $resp;

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Training Session Data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Training Session Data Not Found.');
        }
    }



    public function getTrainingSessionResults($params, $sessionId)
    {
        try {
            $data = $this->trainingRepository->getTrainingSessionResults($params, $sessionId);
            

            return $data;

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Training Session Results from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            
            return $this->sendErrorResponse('Training Session Results Not Found.');
        }
    }



    public function getTrainingSessionResultsViaUserId($params, $sessionId, $userId)
    {
        try {
            $data = $this->trainingRepository->getTrainingSessionResultsViaUserId($params, $sessionId, $userId);

            return $data;

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Training Session Results from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            
            return $this->sendErrorResponse('Training Session Results Not Found.');
        }
    }


    //POCOR-8100 end...

}