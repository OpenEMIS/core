<?php

namespace App\Repositories;

use App\Http\Controllers\Controller;
use App\Models\TrainingCourse;
use App\Models\TrainingProvider;
use App\Models\TrainingSession;
use App\Models\TrainingSessionTraineeResult;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use JWTAuth;

class TrainingRepository
{

    //POCOR-8100 start...
    public function getAllTrainingCourses($params)
    {
        try {
            $limit = config('constantvalues.defaultPaginateLimit');
            $data = [];
            $list = TrainingCourse::select('training_courses.*', 'training_field_of_studies.name as field_of_study')
                    ->join('training_field_of_studies', 'training_field_of_studies.id', '=', 'training_courses.training_field_of_study_id');

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $list = $list->orderBy($col, $orderBy);
            }

            if(isset($params['limit'])){
                $limit = $params['limit'];
                $data = $list->paginate($limit)->toArray();
            } else {
                $data['data'] = $list->get()->toArray();
            }

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
            $data = TrainingCourse::select('training_courses.*', 'training_field_of_studies.name as field_of_study')
                    ->join('training_field_of_studies', 'training_field_of_studies.id', '=', 'training_courses.training_field_of_study_id')
                    ->where('training_courses.id', $courseId)
                    ->first();

            return $data;
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
            $limit = config('constantvalues.defaultPaginateLimit');
            $data = [];
            $list = new TrainingProvider();

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $list = $list->orderBy($col, $orderBy);
            }

            if(isset($params['limit'])){
                $limit = $params['limit'];
                $data = $list->paginate($limit)->toArray();
            } else {
                $data['data'] = $list->get()->toArray();
            }

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
            $data = TrainingProvider::where('id', $providerId)->first();

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
            $limit = config('constantvalues.defaultPaginateLimit');
            $data = [];
            $list = TrainingSession::select('training_sessions.*', 'training_courses.name as training_course_name', 'training_providers.name as training_provider_name')
                    ->join('training_courses', 'training_sessions.training_course_id', '=', 'training_courses.id')
                    ->join('training_providers', 'training_sessions.training_provider_id', '=', 'training_providers.id');

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $list = $list->orderBy($col, $orderBy);
            }

            if(isset($params['limit'])){
                $limit = $params['limit'];
                $data = $list->paginate($limit)->toArray();
            } else {
                $data['data'] = $list->get()->toArray();
            }

            return $data;

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
            $data = TrainingSession::select('training_sessions.*', 'training_courses.name as training_course_name', 'training_providers.name as training_provider_name')
                    ->join('training_courses', 'training_sessions.training_course_id', '=', 'training_courses.id')
                    ->join('training_providers', 'training_sessions.training_provider_id', '=', 'training_providers.id')
                    ->where('training_sessions.id', $sessionId)
                    ->first();

            return $data;
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

            $list = TrainingSessionTraineeResult::with(
                        'trainingSession:id,name,code,training_course_id,training_provider_id,start_date,end_date', 
                        'trainingSession.course:id,code,name', 
                        'trainingSession.trainingProvider:id,name',
                        'trainingResultType:id,name',
                        'user:id,first_name,middle_name,third_name,last_name,openemis_no'
                    )
                    ->where('training_session_id', $sessionId);

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $list = $list->orderBy($col, $orderBy);
            }

            if(isset($params['limit'])){
                $limit = $params['limit'];
                $data = $list->paginate($limit)->toArray();
            } else {
                $data['data'] = $list->get()->toArray();
            }

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

            $list = TrainingSessionTraineeResult::with(
                        'trainingSession:id,name,code,training_course_id,training_provider_id,start_date,end_date', 
                        'trainingSession.course:id,code,name', 
                        'trainingSession.trainingProvider:id,name',
                        'trainingResultType:id,name',
                        'user:id,first_name,middle_name,third_name,last_name,openemis_no'
                    )
                    ->where('training_session_id', $sessionId)
                    ->where('trainee_id', $userId);

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $list = $list->orderBy($col, $orderBy);
            }

            if(isset($params['limit'])){
                $limit = $params['limit'];
                $data = $list->paginate($limit)->toArray();
            } else {
                $data['data'] = $list->get()->toArray();
            }

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