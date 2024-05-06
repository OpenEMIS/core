<?php

namespace App\Repositories;

use App\Http\Controllers\Controller;
use App\Models\TrainingCourse;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use JWTAuth;

class TrainingRepository
{

    public function getAllTrainingCourses($params)
    {
        try {
            $limit = config('constantvalues.defaultPaginateLimit');
            

            if(isset($params['limit'])){
                $limit = $params['limit'];
                $list = TrainingCourse::paginate($limit)->toArray();
            } else {
                $list['data'] = TrainingCourse::get()->toArray();
            }

            return $list;

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Training Courses List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Training Courses List Not Found.');
        }
    }

}