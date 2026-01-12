<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\TrainingService;
use Illuminate\Support\Facades\Log;

class TrainingController extends Controller
{
    protected $trainingService;

    public function __construct(TrainingService $trainingService)
    {
        $this->trainingService = $trainingService;
    }

    //POCOR-8100 start...

    /**
     * @OA\Get(
     *     path="/api/v4/training-courses",
     *     tags={"Training"},
     *     summary="Get list of training courses",
     *     description="Returns list of training courses",
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Number of items per page",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="code", type="string", example="NST"),
     *                         @OA\Property(property="name", type="string", example="National Standard Training for Teachers"),
     *                         @OA\Property(property="description", type="string", example="Minimum training requirements for licensed teacher."),
     *                         @OA\Property(property="objective", type="string", example="To ensure teachers meet the minimum training requirements for trained teacher status to receiving and renewing license."),
     *                         @OA\Property(property="credit_hours", type="integer", example=2),
     *                         @OA\Property(property="duration", type="integer", example=2),
     *                         @OA\Property(property="number_of_months", type="integer", example=0),
     *                         @OA\Property(property="special_education_needs", type="integer", example=0),
     *                         @OA\Property(property="file_name", type="string", nullable=true, example=null),
     *                         @OA\Property(property="file_content", type="string", example=""),
     *                         @OA\Property(property="training_field_of_study_id", type="integer", example=682),
     *                         @OA\Property(property="training_field_of_study_name", type="string", example="General"),
     *                         @OA\Property(property="training_course_type_id", type="integer", example=676),
     *                         @OA\Property(property="training_course_type_name", type="string", example="National Examination"),
     *                         @OA\Property(property="training_course_category_id", type="integer", example=0),
     *                         @OA\Property(property="training_course_category_name", type="string", nullable=true, example=null),
     *                         @OA\Property(property="training_mode_of_delivery_id", type="integer", example=582),
     *                         @OA\Property(property="training_mode_of_delivery_name", type="string", example="Seated Exam"),
     *                         @OA\Property(property="training_requirement_id", type="integer", example=682),
     *                         @OA\Property(property="training_requirement_name", type="string", example="Compulsary"),
     *                         @OA\Property(property="training_level_id", type="integer", example=681),
     *                         @OA\Property(property="training_level_name", type="string", example="General"),
     *                         @OA\Property(property="assignee_id", type="integer", example=8805),
     *                         @OA\Property(property="assignee_name", type="string", example="John Doe"),
     *                         @OA\Property(property="status_id", type="integer", example=9),
     *                         @OA\Property(property="modified_user_id", type="integer", example=2),
     *                         @OA\Property(property="modified", type="string", format="date-time", example="2018-04-03 17:37:56"),
     *                         @OA\Property(property="created_user_id", type="integer", example=2),
     *                         @OA\Property(property="created", type="string", format="date-time", example="2018-04-03 17:29:04")
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function getAllTrainingCourses(Request $request)
    {
        try {
            $params = $request->all();
            $data = $this->trainingService->getAllTrainingCourses($params);
            
            if (empty($data)) {
                return $this->sendErrorResponse("Training Courses List Not Found.");
            }

            return $this->sendSuccessResponse("Training Courses List Found.", $data);

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Training Courses List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Training Courses List Not Found.');
        }
    }


    /**
     * @OA\Get(
     *     path="/api/v4/training-courses/{courseId}",
     *     tags={"Training"},
     *     summary="Get details of training course by id",
     *     description="Returns details of training course by id",
     *     @OA\Parameter(
     *         name="courseId",
     *         in="path",
     *         required=true,
     *         description="Id of training course",
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="code", type="string", example="NST"),
     *                         @OA\Property(property="name", type="string", example="National Standard Training for Teachers"),
     *                         @OA\Property(property="description", type="string", example="Minimum training requirements for licensed teacher."),
     *                         @OA\Property(property="objective", type="string", example="To ensure teachers meet the minimum training requirements for trained teacher status to receiving and renewing license."),
     *                         @OA\Property(property="credit_hours", type="integer", example=2),
     *                         @OA\Property(property="duration", type="integer", example=2),
     *                         @OA\Property(property="number_of_months", type="integer", example=0),
     *                         @OA\Property(property="special_education_needs", type="integer", example=0),
     *                         @OA\Property(property="file_name", type="string", nullable=true, example=null),
     *                         @OA\Property(property="file_content", type="string", example=""),
     *                         @OA\Property(property="training_field_of_study_id", type="integer", example=682),
     *                         @OA\Property(property="training_field_of_study_name", type="string", example="General"),
     *                         @OA\Property(property="training_course_type_id", type="integer", example=676),
     *                         @OA\Property(property="training_course_type_name", type="string", example="National Examination"),
     *                         @OA\Property(property="training_course_category_id", type="integer", example=0),
     *                         @OA\Property(property="training_course_category_name", type="string", nullable=true, example=null),
     *                         @OA\Property(property="training_mode_of_delivery_id", type="integer", example=582),
     *                         @OA\Property(property="training_mode_of_delivery_name", type="string", example="Seated Exam"),
     *                         @OA\Property(property="training_requirement_id", type="integer", example=682),
     *                         @OA\Property(property="training_requirement_name", type="string", example="Compulsary"),
     *                         @OA\Property(property="training_level_id", type="integer", example=681),
     *                         @OA\Property(property="training_level_name", type="string", example="General"),
     *                         @OA\Property(property="assignee_id", type="integer", example=8805),
     *                         @OA\Property(property="assignee_name", type="string", example="John Doe"),
     *                         @OA\Property(property="status_id", type="integer", example=9),
     *                         @OA\Property(property="modified_user_id", type="integer", example=2),
     *                         @OA\Property(property="modified", type="string", format="date-time", example="2018-04-03 17:37:56"),
     *                         @OA\Property(property="created_user_id", type="integer", example=2),
     *                         @OA\Property(property="created", type="string", format="date-time", example="2018-04-03 17:29:04")
     *             )
     *         )
     *     )
     * )
     */
    public function getTrainingCourseData($courseId)
    {
        try {
            $data = $this->trainingService->getTrainingCourseData($courseId);
            
            if (empty($data)) {
                return $this->sendErrorResponse("Training Courses Data Not Found.");
            }

            return $this->sendSuccessResponse("Training Courses Data Found.", $data);

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Training Courses Data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Training Courses Data Not Found.');
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v4/training-providers",
     *     tags={"Training"},
     *     summary="Get list of training providers",
     *     description="Returns list of training providers",
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Number of items per page",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Ministry of Education"),
     *                         @OA\Property(property="order", type="integer", example=1),
     *                         @OA\Property(property="visible", type="boolean", example=true),
     *                         @OA\Property(property="editable", type="boolean", example=true),
     *                         @OA\Property(property="default", type="boolean", example=false),
     *                         @OA\Property(property="international_code", type="string", example=""),
     *                         @OA\Property(property="national_code", type="string", example=""),
     *                         @OA\Property(property="modified_user_id", type="integer", nullable=true, example=null),
     *                         @OA\Property(property="modified", type="string", format="date-time", nullable=true, example=null),
     *                         @OA\Property(property="created_user_id", type="integer", example=2),
     *                         @OA\Property(property="created", type="string", format="date-time", example="2018-04-03 17:26:26")
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function getTrainingProviders(Request $request)
    {
        try {
            $params = $request->all();
            $data = $this->trainingService->getTrainingProviders($params);
            
            if (empty($data)) {
                return $this->sendErrorResponse("Training Providers List Not Found.");
            }

            return $this->sendSuccessResponse("Training Providers List Found.", $data);

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Training Providers List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Training Providers List Not Found.');
        }
    }


    /**
     * @OA\Get(
     *     path="/api/v4/training-providers/{providerId}",
     *     tags={"Training"},
     *     summary="Get details of training provider by id",
     *     description="Returns details of training provider by id",
     *     @OA\Parameter(
     *         name="providerId",
     *         in="path",
     *         required=true,
     *         description="session provider id",
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Ministry of Education"),
     *                 @OA\Property(property="order", type="integer", example=1),
     *                 @OA\Property(property="visible", type="boolean", example=true),
     *                 @OA\Property(property="editable", type="boolean", example=true),
     *                 @OA\Property(property="default", type="boolean", example=false),
     *                 @OA\Property(property="international_code", type="string", example=""),
     *                 @OA\Property(property="national_code", type="string", example=""),
     *                 @OA\Property(property="modified_user_id", type="integer", nullable=true, example=null),
     *                 @OA\Property(property="modified", type="string", format="date-time", nullable=true, example=null),
     *                 @OA\Property(property="created_user_id", type="integer", example=2),
     *                 @OA\Property(property="created", type="string", format="date-time", example="2018-04-03 17:26:26")
     *             )
     *         )
     *     )
     * )
     */
    public function getTrainingProvidersData($providerId)
    {
        try {
            $data = $this->trainingService->getTrainingProvidersData($providerId);
            
            if (empty($data)) {
                return $this->sendErrorResponse("Training Provider Data Not Found.");
            }

            return $this->sendSuccessResponse("Training Provider Data Found.", $data);

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Training Provider Data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Training Provider Data Not Found.');
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v4/training-sessions",
     *     tags={"Training"},
     *     summary="Get list of training sessions",
     *     description="Returns details of training sessions",
     *     @OA\Parameter(
     *         name="training_course_id",
     *         in="query",
     *         required=false,
     *         description="Filter by training course id.",
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Number of items per page",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Successful."),
     *              @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="code", type="string", example="NST2018"),
     *                     @OA\Property(property="name", type="string", example="National Standard Training for Teachers 2018"),
     *                     @OA\Property(property="start_date", type="string", format="date", example="2018-04-01"),
     *                     @OA\Property(property="end_date", type="string", format="date", example="2018-04-30"),
     *                     @OA\Property(property="comment", type="string", example=""),
     *                     @OA\Property(property="training_course_id", type="integer", example=1),
     *                     @OA\Property(property="training_provider_id", type="integer", example=1),
     *                     @OA\Property(property="assignee_id", type="integer", example=8805),
     *                     @OA\Property(property="status_id", type="integer", example=15),
     *                     @OA\Property(property="area_id", type="integer", example=1),
     *                     @OA\Property(property="trainees", type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=505),
     *                             @OA\Property(property="first_name", type="string", example="Stephanie"),
     *                             @OA\Property(property="middle_name", type="string", nullable=true, example=null),
     *                             @OA\Property(property="third_name", type="string", nullable=true, example=null),
     *                             @OA\Property(property="last_name", type="string", example="Andrews"),
     *                             @OA\Property(property="openemis_no", type="string", example="1522277180"),
     *                             @OA\Property(property="full_name", type="string", example="Stephanie  Andrews"),
     *                             @OA\Property(property="name_with_id", type="string", example="1522277180 - Stephanie  Andrews")
     *                         )
     *                     ),
     *                     @OA\Property(property="evaluators", type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=8815),
     *                             @OA\Property(property="first_name", type="string", example="Amanda"),
     *                             @OA\Property(property="middle_name", type="string", nullable=true, example=null),
     *                             @OA\Property(property="third_name", type="string", nullable=true, example=null),
     *                             @OA\Property(property="last_name", type="string", example="Wells"),
     *                             @OA\Property(property="openemis_no", type="string", example="1522284820"),
     *                             @OA\Property(property="full_name", type="string", example="Amanda  Wells"),
     *                             @OA\Property(property="name_with_id", type="string", example="1522284820 - Amanda  Wells")
     *                         )
     *                     ),
     *                     @OA\Property(property="trainers", type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=680),
     *                             @OA\Property(property="first_name", type="string", example="Amanda"),
     *                             @OA\Property(property="middle_name", type="string", nullable=true, example=null),
     *                             @OA\Property(property="third_name", type="string", nullable=true, example=null),
     *                             @OA\Property(property="last_name", type="string", example="Foster"),
     *                             @OA\Property(property="openemis_no", type="string", example="1522277355"),
     *                             @OA\Property(property="full_name", type="string", example="Amanda  Foster"),
     *                             @OA\Property(property="name_with_id", type="string", example="1522277355 - Amanda  Foster")
     *                         )
     *                     ),
     *                     @OA\Property(property="modified_user_id", type="integer", example=2),
     *                     @OA\Property(property="modified", type="string", example="2018-04-03 17:44:07"),
     *                     @OA\Property(property="created_user_id", type="integer", example=2),
     *                     @OA\Property(property="created", type="string", example="2018-04-03 17:39:16")
     *                  )
     *              )
     *             )
     *         )
     *     )
     * )
     */
    public function getTrainingSessions(Request $request)
    {
        try {
            $params = $request->all();
            $data = $this->trainingService->getTrainingSessions($params);
            
            if (empty($data)) {
                return $this->sendErrorResponse("Training Sessions List Not Found.");
            }

            return $this->sendSuccessResponse("Training Sessions List Found.", $data);

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Training Sessions List from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Training Sessions List Not Found.');
        }
    }


    /**
     * @OA\Get(
     *     path="/api/v4/training-sessions/{sessionId}",
     *     tags={"Training"},
     *     summary="Get detail of training session",
     *     description="Returns detail of training session",
     *     @OA\Parameter(
     *         name="sessionId",
     *         in="path",
     *         required=true,
     *         description="Id of training session",
     *         @OA\Schema(type="integer", example="2")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Successful."),
     *              @OA\Property(property="data", type="object",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="code", type="string", example="NST2018"),
     *                     @OA\Property(property="name", type="string", example="National Standard Training for Teachers 2018"),
     *                     @OA\Property(property="start_date", type="string", format="date", example="2018-04-01"),
     *                     @OA\Property(property="end_date", type="string", format="date", example="2018-04-30"),
     *                     @OA\Property(property="comment", type="string", example=""),
     *                     @OA\Property(property="training_course_id", type="integer", example=1),
     *                     @OA\Property(property="training_provider_id", type="integer", example=1),
     *                     @OA\Property(property="assignee_id", type="integer", example=8805),
     *                     @OA\Property(property="status_id", type="integer", example=15),
     *                     @OA\Property(property="area_id", type="integer", example=1),
     *                     @OA\Property(property="trainees", type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=505),
     *                             @OA\Property(property="first_name", type="string", example="Stephanie"),
     *                             @OA\Property(property="middle_name", type="string", nullable=true, example=null),
     *                             @OA\Property(property="third_name", type="string", nullable=true, example=null),
     *                             @OA\Property(property="last_name", type="string", example="Andrews"),
     *                             @OA\Property(property="openemis_no", type="string", example="1522277180"),
     *                             @OA\Property(property="full_name", type="string", example="Stephanie  Andrews"),
     *                             @OA\Property(property="name_with_id", type="string", example="1522277180 - Stephanie  Andrews")
     *                         )
     *                     ),
     *                     @OA\Property(property="evaluators", type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=8815),
     *                             @OA\Property(property="first_name", type="string", example="Amanda"),
     *                             @OA\Property(property="middle_name", type="string", nullable=true, example=null),
     *                             @OA\Property(property="third_name", type="string", nullable=true, example=null),
     *                             @OA\Property(property="last_name", type="string", example="Wells"),
     *                             @OA\Property(property="openemis_no", type="string", example="1522284820"),
     *                             @OA\Property(property="full_name", type="string", example="Amanda  Wells"),
     *                             @OA\Property(property="name_with_id", type="string", example="1522284820 - Amanda  Wells")
     *                         )
     *                     ),
     *                     @OA\Property(property="trainers", type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=680),
     *                             @OA\Property(property="first_name", type="string", example="Amanda"),
     *                             @OA\Property(property="middle_name", type="string", nullable=true, example=null),
     *                             @OA\Property(property="third_name", type="string", nullable=true, example=null),
     *                             @OA\Property(property="last_name", type="string", example="Foster"),
     *                             @OA\Property(property="openemis_no", type="string", example="1522277355"),
     *                             @OA\Property(property="full_name", type="string", example="Amanda  Wells"),
     *                             @OA\Property(property="name_with_id", type="string", example="1522277355 - Amanda  Foster")
     *                         )
     *                     ),
     *                     @OA\Property(property="modified_user_id", type="integer", example=2),
     *                     @OA\Property(property="modified", type="string", example="2018-04-03 17:44:07"),
     *                     @OA\Property(property="created_user_id", type="integer", example=2),
     *                     @OA\Property(property="created", type="string", example="2018-04-03 17:39:16")
     *                  )
     *         )
     *     )
     * )
     */
    public function getTrainingSessionData($sessionId)
    {
        try {
            $data = $this->trainingService->getTrainingSessionData($sessionId);
            
            if (empty($data)) {
                return $this->sendErrorResponse("Training Session Data Not Found.");
            }

            return $this->sendSuccessResponse("Training Session Data Found.", $data);

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Training Session Data from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            
            return $this->sendErrorResponse('Training Session Data Not Found.');
        }
    }


    /**
     * @OA\Get(
     *     path="/api/v4/training-sessions/{sessionId}/results",
     *     summary="Get training session results",
     *     tags={"Training"},
     *     description="Retrieves training session results data.",
     *     @OA\Parameter(
     *         name="sessionId",
     *         in="path",
     *         required=true,
     *         description="Id of training session",
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Number of items per page",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response containing training result data",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="string", example="00346c45-fcbe-4f05-b27b-a0b4d49744cd"),
     *                         @OA\Property(property="result", type="string", example="Not Passed"),
     *                         @OA\Property(property="attendance_days", type="string", example=""),
     *                         @OA\Property(property="certificate_number", type="string", example=""),
     *                         @OA\Property(property="practical", type="string", example=""),
     *                         @OA\Property(property="training_result_type_id", type="integer", example=650),
     *                         @OA\Property(property="trainee_id", type="integer", example=577),
     *                         @OA\Property(property="training_session_id", type="integer", example=1),
     *                         @OA\Property(property="modified_user_id", type="integer", example=2),
     *                         @OA\Property(property="modified", type="string", example="2018-04-05 19:04:42"),
     *                         @OA\Property(property="created_user_id", type="integer", example=2),
     *                         @OA\Property(property="created", type="string", example="2018-04-03 17:48:49"),
     *                         @OA\Property(property="training_session", type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="name", type="string", example="National Standard Training for Teachers 2018"),
     *                             @OA\Property(property="code", type="string", example="NST2018"),
     *                             @OA\Property(property="training_course_id", type="integer", example=1),
     *                             @OA\Property(property="training_provider_id", type="integer", example=1),
     *                             @OA\Property(property="start_date", type="string", format="date", example="2018-04-01"),
     *                             @OA\Property(property="end_date", type="string", format="date", example="2018-04-30"),
     *                             @OA\Property(property="code_name", type="string", example="NST2018 - National Standard Training for Teachers 2018"),
     *                             @OA\Property(property="course", type="object",
     *                                 @OA\Property(property="id", type="integer", example=1),
     *                                 @OA\Property(property="code", type="string", example="NST"),
     *                                 @OA\Property(property="name", type="string", example="National Standard Training for Teachers"),
     *                                 @OA\Property(property="code_name", type="string", example="NST - National Standard Training for Teachers")
     *                             ),
     *                             @OA\Property(property="training_provider", type="object",
     *                                 @OA\Property(property="id", type="integer", example=1),
     *                                 @OA\Property(property="name", type="string", example="Ministry of Education")
     *                             )
     *                         ),
     *                         @OA\Property(property="training_result_type", type="object",
     *                             @OA\Property(property="id", type="integer", example=650),
     *                             @OA\Property(property="name", type="string", example="Exam")
     *                         ),
     *                         @OA\Property(property="user", type="object",
     *                             @OA\Property(property="id", type="integer", example=577),
     *                             @OA\Property(property="first_name", type="string", example="Phyllis"),
     *                             @OA\Property(property="middle_name", type="string", nullable=true, example=null),
     *                             @OA\Property(property="third_name", type="string", nullable=true, example=null),
     *                             @OA\Property(property="last_name", type="string", example="Fowler"),
     *                             @OA\Property(property="openemis_no", type="string", example="1522277252"),
     *                             @OA\Property(property="full_name", type="string", example="Phyllis  Fowler"),
     *                             @OA\Property(property="name_with_id", type="string", example="1522277252 - Phyllis  Fowler")
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function getTrainingSessionResults(Request $request, $sessionId)
    {
        try {
            $params = $request->all();
            $data = $this->trainingService->getTrainingSessionResults($params, $sessionId);
            
            if (empty($data)) {
                return $this->sendErrorResponse("Training Session Results Not Found.");
            }

            return $this->sendSuccessResponse("Training Session Results Found.", $data);

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Training Session Results from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Training Session Results Not Found.');
        }
    }


    /**
     * @OA\Get(
     *     path="/api/v4/training-sessions/{sessionId}/results/{userId}",
     *     summary="Get training result for a specific trainee in a specific session",
     *     tags={"Training"},
     *     description="Retrieves training result data for a specific trainee in a specific training session.",
     *     @OA\Parameter(
     *         name="sessionId",
     *         in="path",
     *         description="ID of the training session",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         description="ID of the trainee",
     *         required=true,
     *         @OA\Schema(type="integer", example=8815)
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="integer", example="id")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Number of items per page",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response containing training result data",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="string", example="4c5bc882-5918-4d71-b5c0-3f56872aefc9"),
     *                         @OA\Property(property="result", type="string", example="Passed"),
     *                         @OA\Property(property="attendance_days", type="string", example=""),
     *                         @OA\Property(property="certificate_number", type="string", example=""),
     *                         @OA\Property(property="practical", type="string", example=""),
     *                         @OA\Property(property="training_result_type_id", type="integer", example=650),
     *                         @OA\Property(property="trainee_id", type="integer", example=8815),
     *                         @OA\Property(property="training_session_id", type="integer", example=1),
     *                         @OA\Property(property="modified_user_id", type="integer", example=2),
     *                         @OA\Property(property="modified", type="string", example="2018-04-05 19:04:42"),
     *                         @OA\Property(property="created_user_id", type="integer", example=2),
     *                         @OA\Property(property="created", type="string", example="2018-04-05 19:04:16"),
     *                         @OA\Property(property="training_session", type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="name", type="string", example="National Standard Training for Teachers 2018"),
     *                             @OA\Property(property="code", type="string", example="NST2018"),
     *                             @OA\Property(property="training_course_id", type="integer", example=1),
     *                             @OA\Property(property="training_provider_id", type="integer", example=1),
     *                             @OA\Property(property="start_date", type="string", format="date", example="2018-04-01"),
     *                             @OA\Property(property="end_date", type="string", format="date", example="2018-04-30"),
     *                             @OA\Property(property="code_name", type="string", example="NST2018 - National Standard Training for Teachers 2018"),
     *                             @OA\Property(property="course", type="object",
     *                                 @OA\Property(property="id", type="integer", example=1),
     *                                 @OA\Property(property="code", type="string", example="NST"),
     *                                 @OA\Property(property="name", type="string", example="National Standard Training for Teachers"),
     *                                 @OA\Property(property="code_name", type="string", example="NST - National Standard Training for Teachers")
     *                             ),
     *                             @OA\Property(property="training_provider", type="object",
     *                                 @OA\Property(property="id", type="integer", example=1),
     *                                 @OA\Property(property="name", type="string", example="Ministry of Education")
     *                             )
     *                         ),
     *                         @OA\Property(property="training_result_type", type="object",
     *                             @OA\Property(property="id", type="integer", example=650),
     *                             @OA\Property(property="name", type="string", example="Exam")
     *                         ),
     *                         @OA\Property(property="user", type="object",
     *                             @OA\Property(property="id", type="integer", example=8815),
     *                             @OA\Property(property="first_name", type="string", example="Amanda"),
     *                             @OA\Property(property="middle_name", type="string", nullable=true, example=null),
     *                             @OA\Property(property="third_name", type="string", nullable=true, example=null),
     *                             @OA\Property(property="last_name", type="string", example="Wells"),
     *                             @OA\Property(property="openemis_no", type="string", example="1522952436"),
     *                             @OA\Property(property="full_name", type="string", example="Amanda  Wells"),
     *                             @OA\Property(property="name_with_id", type="string", example="1522952436 - Amanda  Wells")
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function getTrainingSessionResultsViaUserId(Request $request, $sessionId, $userId)
    {
        try {
            $params = $request->all();
            $data = $this->trainingService->getTrainingSessionResultsViaUserId($params, $sessionId, $userId);
            
            if (empty($data)) {
                return $this->sendErrorResponse("Training Session Results Not Found.");
            }

            return $this->sendSuccessResponse("Training Session Results Found.", $data);

        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch Training Session Results from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            dd($e);
            return $this->sendErrorResponse('Training Session Results Not Found.');
        }
    }

    //POCOR-8100 end...

}