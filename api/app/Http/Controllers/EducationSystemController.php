<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\EducationSystemService;
use Illuminate\Support\Facades\Log;

class EducationSystemController extends Controller
{
    protected $educationSystemService;

    public function __construct(
        EducationSystemService $educationSystemService
    ) {
        $this->educationSystemService = $educationSystemService;
    }


    

    /**
     * @OA\Get(
     *     path="/api/v4/systems/levels/cycles/programmes/grades/subjects",
     *     summary="Education Structures",
     *     description="Returns subjects for the specified academic period",
     *     tags={"Education Structure"},
     *     @OA\Parameter(
     *         name="academic_period_id",
     *         in="query",
     *         required=true,
     *         description="ID of the academic period",
     *         @OA\Schema(type="integer", example="33")
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="string", example="id")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Limit",
     *         @OA\Schema(type="integer", example="10")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example="2"),
     *                         @OA\Property(property="name", type="string", example="National Education System"),
     *                         @OA\Property(property="academic_period_id", type="integer", example="30"),
     *                         @OA\Property(property="order", type="integer", example="1"),
     *                         @OA\Property(property="visible", type="integer", example="1"),
     *                         @OA\Property(property="modified_user_id", type="integer", example=null),
     *                         @OA\Property(property="modified", type="string", format="date-time", example=null),
     *                         @OA\Property(property="created_user_id", type="integer", example="2"),
     *                         @OA\Property(property="created", type="string", format="date-time", example="2014-09-20 22:07:18"),
     *                         @OA\Property(property="levels", type="array",
     *                             @OA\Items(
     *                                 type="object",
     *                                 @OA\Property(property="id", type="integer", example="4"),
     *                                 @OA\Property(property="name", type="string", example="Early Childhood Education"),
     *                                 @OA\Property(property="order", type="integer", example="1"),
     *                                 @OA\Property(property="visible", type="integer", example="1"),
     *                                 @OA\Property(property="education_system_id", type="integer", example="2"),
     *                                 @OA\Property(property="education_level_isced_id", type="integer", example="1"),
     *                                 @OA\Property(property="modified_user_id", type="integer", example=null),
     *                                 @OA\Property(property="modified", type="string", format="date-time", example=null),
     *                                 @OA\Property(property="created_user_id", type="integer", example="2"),
     *                                 @OA\Property(property="created", type="string", format="date-time", example="2014-09-20 22:07:18"),
     *                                 @OA\Property(property="cycles", type="array",
     *                                     @OA\Items(
     *                                         type="object",
     *                                         @OA\Property(property="id", type="integer", example="11"),
     *                                         @OA\Property(property="name", type="string", example="Pre-primary"),
     *                                         @OA\Property(property="admission_age", type="integer", example="5"),
     *                                         @OA\Property(property="order", type="integer", example="1"),
     *                                         @OA\Property(property="visible", type="integer", example="1"),
     *                                         @OA\Property(property="education_level_id", type="integer", example="4"),
     *                                         @OA\Property(property="modified_user_id", type="integer", example=null),
     *                                         @OA\Property(property="modified", type="string", format="date-time", example=null),
     *                                         @OA\Property(property="created_user_id", type="integer", example="2"),
     *                                         @OA\Property(property="created", type="string", format="date-time", example="2018-03-28 15:15:33"),
     *                                         @OA\Property(property="programmes", type="array",
     *                                             @OA\Items(
     *                                                 type="object",
     *                                                 @OA\Property(property="id", type="integer", example="8"),
     *                                                 @OA\Property(property="code", type="string", example="Kindergarten"),
     *                                                 @OA\Property(property="name", type="string", example="Kindergarten"),
     *                                                 @OA\Property(property="duration", type="integer", example="2"),
     *                                                 @OA\Property(property="order", type="integer", example="2"),
     *                                                 @OA\Property(property="visible", type="integer", example="1"),
     *                                                 @OA\Property(property="education_field_of_study_id", type="integer", example="1"),
     *                                                 @OA\Property(property="education_cycle_id", type="integer", example="11"),
     *                                                 @OA\Property(property="education_certification_id", type="integer", example="2"),
     *                                                 @OA\Property(property="same_grade_promotion", type="integer", example="0"),
     *                                                 @OA\Property(property="modified_user_id", type="integer", example=null),
     *                                                 @OA\Property(property="modified", type="string", format="date-time", example=null),
     *                                                 @OA\Property(property="created_user_id", type="integer", example="2"),
     *                                                 @OA\Property(property="created", type="string", format="date-time", example="2014-09-20 22:21:26"),
     *                                                 @OA\Property(property="grades", type="array",
     *                                                     @OA\Items(
     *                                                         type="object",
     *                                                         @OA\Property(property="id", type="integer", example="76"),
     *                                                         @OA\Property(property="code", type="string", example="Kindergarten 1"),
     *                                                         @OA\Property(property="name", type="string", example="Kindergarten 1"),
     *                                                         @OA\Property(property="admission_age", type="integer", example="5"),
     *                                                         @OA\Property(property="order", type="integer", example="1"),
     *                                                         @OA\Property(property="visible", type="integer", example="1"),
     *                                                         @OA\Property(property="education_stage_id", type="integer", example="14"),
     *                                                         @OA\Property(property="education_programme_id", type="integer", example="8"),
     *                                                         @OA\Property(property="modified_user_id", type="integer", example=null),
     *                                                         @OA\Property(property="modified", type="string", format="date-time", example=null),
     *                                                         @OA\Property(property="created_user_id", type="integer", example="2"),
     *                                                         @OA\Property(property="created", type="string", format="date-time", example="2014-09-20 22:21:26"),
     *                                                         @OA\Property(property="subjects", type="array",
     *                                                             @OA\Items(
     *                                                                 type="object",
     *                                                                 @OA\Property(property="id", type="integer", example="6"),
     *                                                                 @OA\Property(property="name", type="string", example="Language Arts Content Standards and Learning Outcomes"),
     *                                                                 @OA\Property(property="code", type="string", example="LAC"),
     *                                                                 @OA\Property(property="order", type="integer", example="1"),
     *                                                                 @OA\Property(property="visible", type="integer", example="1"),
     *                                                                 @OA\Property(property="hours_required", type="integer", nullable=true, example=null),
     *                                                                 @OA\Property(property="auto_allocation", type="integer", example="1"),
     *                                                                 @OA\Property(property="modified_user_id", type="integer", example=null),
     *                                                                 @OA\Property(property="modified", type="string", format="date-time", example=null),
     *                                                                 @OA\Property(property="created_user_id", type="integer", example="2"),
     *                                                                 @OA\Property(property="created", type="string", format="date-time", example="2019-10-12 00:09:00"),
     *                                                             )
     *                                                         )
     *                                                     )
     *                                                 )
     *                                             )
     *                                         )
     *                                     )
     *                                 )
     *                             )
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
    public function getAllEducationSystems(Request $request)
    {
        try {
            $data = $this->educationSystemService->getAllEducationSystems($request);
            return $this->sendSuccessResponse("Education System List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Education System List Not Found');
        }
    }


    
    /**
     * @OA\Get(
     *     path="/api/v4/systems/{systemId}/levels/cycles/programmes/grades/subjects",
     *     summary="Education Structures",
     *     description="Returns subjects for the specified academic period and system id.",
     *     tags={"Education Structure"},
     *     @OA\Parameter(
     *         name="systemId",
     *         in="path",
     *         required=true,
     *         description="ID of the system id",
     *         @OA\Schema(type="integer", example="2")
     *     ),
     *     @OA\Parameter(
     *         name="academic_period_id",
     *         in="query",
     *         required=false,
     *         description="ID of the academic period",
     *         @OA\Schema(type="integer", example="33")
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="string", example="id")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Limit",
     *         @OA\Schema(type="integer", example="10")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example="2"),
     *                         @OA\Property(property="name", type="string", example="National Education System"),
     *                         @OA\Property(property="academic_period_id", type="integer", example="30"),
     *                         @OA\Property(property="order", type="integer", example="1"),
     *                         @OA\Property(property="visible", type="integer", example="1"),
     *                         @OA\Property(property="modified_user_id", type="integer", example=null),
     *                         @OA\Property(property="modified", type="string", format="date-time", example=null),
     *                         @OA\Property(property="created_user_id", type="integer", example="2"),
     *                         @OA\Property(property="created", type="string", format="date-time", example="2014-09-20 22:07:18"),
     *                         @OA\Property(property="levels", type="array",
     *                             @OA\Items(
     *                                 type="object",
     *                                 @OA\Property(property="id", type="integer", example="4"),
     *                                 @OA\Property(property="name", type="string", example="Early Childhood Education"),
     *                                 @OA\Property(property="order", type="integer", example="1"),
     *                                 @OA\Property(property="visible", type="integer", example="1"),
     *                                 @OA\Property(property="education_system_id", type="integer", example="2"),
     *                                 @OA\Property(property="education_level_isced_id", type="integer", example="1"),
     *                                 @OA\Property(property="modified_user_id", type="integer", example=null),
     *                                 @OA\Property(property="modified", type="string", format="date-time", example=null),
     *                                 @OA\Property(property="created_user_id", type="integer", example="2"),
     *                                 @OA\Property(property="created", type="string", format="date-time", example="2014-09-20 22:07:18"),
     *                                 @OA\Property(property="cycles", type="array",
     *                                     @OA\Items(
     *                                         type="object",
     *                                         @OA\Property(property="id", type="integer", example="11"),
     *                                         @OA\Property(property="name", type="string", example="Pre-primary"),
     *                                         @OA\Property(property="admission_age", type="integer", example="5"),
     *                                         @OA\Property(property="order", type="integer", example="1"),
     *                                         @OA\Property(property="visible", type="integer", example="1"),
     *                                         @OA\Property(property="education_level_id", type="integer", example="4"),
     *                                         @OA\Property(property="modified_user_id", type="integer", example=null),
     *                                         @OA\Property(property="modified", type="string", format="date-time", example=null),
     *                                         @OA\Property(property="created_user_id", type="integer", example="2"),
     *                                         @OA\Property(property="created", type="string", format="date-time", example="2018-03-28 15:15:33"),
     *                                         @OA\Property(property="programmes", type="array",
     *                                             @OA\Items(
     *                                                 type="object",
     *                                                 @OA\Property(property="id", type="integer", example="8"),
     *                                                 @OA\Property(property="code", type="string", example="Kindergarten"),
     *                                                 @OA\Property(property="name", type="string", example="Kindergarten"),
     *                                                 @OA\Property(property="duration", type="integer", example="2"),
     *                                                 @OA\Property(property="order", type="integer", example="2"),
     *                                                 @OA\Property(property="visible", type="integer", example="1"),
     *                                                 @OA\Property(property="education_field_of_study_id", type="integer", example="1"),
     *                                                 @OA\Property(property="education_cycle_id", type="integer", example="11"),
     *                                                 @OA\Property(property="education_certification_id", type="integer", example="2"),
     *                                                 @OA\Property(property="same_grade_promotion", type="integer", example="0"),
     *                                                 @OA\Property(property="modified_user_id", type="integer", example=null),
     *                                                 @OA\Property(property="modified", type="string", format="date-time", example=null),
     *                                                 @OA\Property(property="created_user_id", type="integer", example="2"),
     *                                                 @OA\Property(property="created", type="string", format="date-time", example="2014-09-20 22:21:26"),
     *                                                 @OA\Property(property="grades", type="array",
     *                                                     @OA\Items(
     *                                                         type="object",
     *                                                         @OA\Property(property="id", type="integer", example="76"),
     *                                                         @OA\Property(property="code", type="string", example="Kindergarten 1"),
     *                                                         @OA\Property(property="name", type="string", example="Kindergarten 1"),
     *                                                         @OA\Property(property="admission_age", type="integer", example="5"),
     *                                                         @OA\Property(property="order", type="integer", example="1"),
     *                                                         @OA\Property(property="visible", type="integer", example="1"),
     *                                                         @OA\Property(property="education_stage_id", type="integer", example="14"),
     *                                                         @OA\Property(property="education_programme_id", type="integer", example="8"),
     *                                                         @OA\Property(property="modified_user_id", type="integer", example=null),
     *                                                         @OA\Property(property="modified", type="string", format="date-time", example=null),
     *                                                         @OA\Property(property="created_user_id", type="integer", example="2"),
     *                                                         @OA\Property(property="created", type="string", format="date-time", example="2014-09-20 22:21:26"),
     *                                                         @OA\Property(property="subjects", type="array",
     *                                                             @OA\Items(
     *                                                                 type="object",
     *                                                                 @OA\Property(property="id", type="integer", example="6"),
     *                                                                 @OA\Property(property="name", type="string", example="Language Arts Content Standards and Learning Outcomes"),
     *                                                                 @OA\Property(property="code", type="string", example="LAC"),
     *                                                                 @OA\Property(property="order", type="integer", example="1"),
     *                                                                 @OA\Property(property="visible", type="integer", example="1"),
     *                                                                 @OA\Property(property="hours_required", type="integer", nullable=true, example=null),
     *                                                                 @OA\Property(property="auto_allocation", type="integer", example="1"),
     *                                                                 @OA\Property(property="modified_user_id", type="integer", example=null),
     *                                                                 @OA\Property(property="modified", type="string", format="date-time", example=null),
     *                                                                 @OA\Property(property="created_user_id", type="integer", example="2"),
     *                                                                 @OA\Property(property="created", type="string", format="date-time", example="2019-10-12 00:09:00"),
     *                                                             )
     *                                                         )
     *                                                     )
     *                                                 )
     *                                             )
     *                                         )
     *                                     )
     *                                 )
     *                             )
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
    public function getEducationStructureSystems($systemId, Request $request)
    {
        try {
            $data = $this->educationSystemService->getEducationStructureSystems($systemId, $request);
            return $this->sendSuccessResponse("Education System List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Education System List Not Found');
        }
    }


    
    /**
     * @OA\Get(
     *     path="/api/v4/systems/{systemId}/levels/{levelId}/cycles/programmes/grades/subjects",
     *     summary="Education Structures",
     *     description="Returns subjects for the specified academic period, system id and level id.",
     *     tags={"Education Structure"},
     *     @OA\Parameter(
     *         name="systemId",
     *         in="path",
     *         required=true,
     *         description="ID of the system id",
     *         @OA\Schema(type="integer", example="2")
     *     ),
     *     @OA\Parameter(
     *         name="levelId",
     *         in="path",
     *         required=true,
     *         description="ID of the level id",
     *         @OA\Schema(type="integer", example="4")
     *     ),
     *     @OA\Parameter(
     *         name="academic_period_id",
     *         in="query",
     *         required=false,
     *         description="ID of the academic period",
     *         @OA\Schema(type="integer", example="33")
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="string", example="id")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Limit",
     *         @OA\Schema(type="integer", example="10")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example="2"),
     *                         @OA\Property(property="name", type="string", example="National Education System"),
     *                         @OA\Property(property="academic_period_id", type="integer", example="30"),
     *                         @OA\Property(property="order", type="integer", example="1"),
     *                         @OA\Property(property="visible", type="integer", example="1"),
     *                         @OA\Property(property="modified_user_id", type="integer", example=null),
     *                         @OA\Property(property="modified", type="string", format="date-time", example=null),
     *                         @OA\Property(property="created_user_id", type="integer", example="2"),
     *                         @OA\Property(property="created", type="string", format="date-time", example="2014-09-20 22:07:18"),
     *                         @OA\Property(property="levels", type="array",
     *                             @OA\Items(
     *                                 type="object",
     *                                 @OA\Property(property="id", type="integer", example="4"),
     *                                 @OA\Property(property="name", type="string", example="Early Childhood Education"),
     *                                 @OA\Property(property="order", type="integer", example="1"),
     *                                 @OA\Property(property="visible", type="integer", example="1"),
     *                                 @OA\Property(property="education_system_id", type="integer", example="2"),
     *                                 @OA\Property(property="education_level_isced_id", type="integer", example="1"),
     *                                 @OA\Property(property="modified_user_id", type="integer", example=null),
     *                                 @OA\Property(property="modified", type="string", format="date-time", example=null),
     *                                 @OA\Property(property="created_user_id", type="integer", example="2"),
     *                                 @OA\Property(property="created", type="string", format="date-time", example="2014-09-20 22:07:18"),
     *                                 @OA\Property(property="cycles", type="array",
     *                                     @OA\Items(
     *                                         type="object",
     *                                         @OA\Property(property="id", type="integer", example="11"),
     *                                         @OA\Property(property="name", type="string", example="Pre-primary"),
     *                                         @OA\Property(property="admission_age", type="integer", example="5"),
     *                                         @OA\Property(property="order", type="integer", example="1"),
     *                                         @OA\Property(property="visible", type="integer", example="1"),
     *                                         @OA\Property(property="education_level_id", type="integer", example="4"),
     *                                         @OA\Property(property="modified_user_id", type="integer", example=null),
     *                                         @OA\Property(property="modified", type="string", format="date-time", example=null),
     *                                         @OA\Property(property="created_user_id", type="integer", example="2"),
     *                                         @OA\Property(property="created", type="string", format="date-time", example="2018-03-28 15:15:33"),
     *                                         @OA\Property(property="programmes", type="array",
     *                                             @OA\Items(
     *                                                 type="object",
     *                                                 @OA\Property(property="id", type="integer", example="8"),
     *                                                 @OA\Property(property="code", type="string", example="Kindergarten"),
     *                                                 @OA\Property(property="name", type="string", example="Kindergarten"),
     *                                                 @OA\Property(property="duration", type="integer", example="2"),
     *                                                 @OA\Property(property="order", type="integer", example="2"),
     *                                                 @OA\Property(property="visible", type="integer", example="1"),
     *                                                 @OA\Property(property="education_field_of_study_id", type="integer", example="1"),
     *                                                 @OA\Property(property="education_cycle_id", type="integer", example="11"),
     *                                                 @OA\Property(property="education_certification_id", type="integer", example="2"),
     *                                                 @OA\Property(property="same_grade_promotion", type="integer", example="0"),
     *                                                 @OA\Property(property="modified_user_id", type="integer", example=null),
     *                                                 @OA\Property(property="modified", type="string", format="date-time", example=null),
     *                                                 @OA\Property(property="created_user_id", type="integer", example="2"),
     *                                                 @OA\Property(property="created", type="string", format="date-time", example="2014-09-20 22:21:26"),
     *                                                 @OA\Property(property="grades", type="array",
     *                                                     @OA\Items(
     *                                                         type="object",
     *                                                         @OA\Property(property="id", type="integer", example="76"),
     *                                                         @OA\Property(property="code", type="string", example="Kindergarten 1"),
     *                                                         @OA\Property(property="name", type="string", example="Kindergarten 1"),
     *                                                         @OA\Property(property="admission_age", type="integer", example="5"),
     *                                                         @OA\Property(property="order", type="integer", example="1"),
     *                                                         @OA\Property(property="visible", type="integer", example="1"),
     *                                                         @OA\Property(property="education_stage_id", type="integer", example="14"),
     *                                                         @OA\Property(property="education_programme_id", type="integer", example="8"),
     *                                                         @OA\Property(property="modified_user_id", type="integer", example=null),
     *                                                         @OA\Property(property="modified", type="string", format="date-time", example=null),
     *                                                         @OA\Property(property="created_user_id", type="integer", example="2"),
     *                                                         @OA\Property(property="created", type="string", format="date-time", example="2014-09-20 22:21:26"),
     *                                                         @OA\Property(property="subjects", type="array",
     *                                                             @OA\Items(
     *                                                                 type="object",
     *                                                                 @OA\Property(property="id", type="integer", example="6"),
     *                                                                 @OA\Property(property="name", type="string", example="Language Arts Content Standards and Learning Outcomes"),
     *                                                                 @OA\Property(property="code", type="string", example="LAC"),
     *                                                                 @OA\Property(property="order", type="integer", example="1"),
     *                                                                 @OA\Property(property="visible", type="integer", example="1"),
     *                                                                 @OA\Property(property="hours_required", type="integer", nullable=true, example=null),
     *                                                                 @OA\Property(property="auto_allocation", type="integer", example="1"),
     *                                                                 @OA\Property(property="modified_user_id", type="integer", example=null),
     *                                                                 @OA\Property(property="modified", type="string", format="date-time", example=null),
     *                                                                 @OA\Property(property="created_user_id", type="integer", example="2"),
     *                                                                 @OA\Property(property="created", type="string", format="date-time", example="2019-10-12 00:09:00"),
     *                                                             )
     *                                                         )
     *                                                     )
     *                                                 )
     *                                             )
     *                                         )
     *                                     )
     *                                 )
     *                             )
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
    public function getEducationStructureLevel($systemId, $levelId, Request $request)
    {
        try {
            $data = $this->educationSystemService->getEducationStructureLevel($systemId, $levelId, $request);
            return $this->sendSuccessResponse("Education System List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Education System List Not Found');
        }
    }


    /**
     * @OA\Get(
     *     path="/api/v4/systems/{systemId}/levels/{levelId}/cycles/{cycleId}/programmes/grades/subjects",
     *     summary="Education Structures",
     *     description="Returns subjects for the specified academic period, system id, level id and cycle id.",
     *     tags={"Education Structure"},
     *     @OA\Parameter(
     *         name="systemId",
     *         in="path",
     *         required=true,
     *         description="ID of the system id",
     *         @OA\Schema(type="integer", example="2")
     *     ),
     *     @OA\Parameter(
     *         name="levelId",
     *         in="path",
     *         required=true,
     *         description="ID of the level id",
     *         @OA\Schema(type="integer", example="4")
     *     ),
     *     @OA\Parameter(
     *         name="cycleId",
     *         in="path",
     *         required=true,
     *         description="ID of the cycle id",
     *         @OA\Schema(type="integer", example="11")
     *     ),
     *     @OA\Parameter(
     *         name="academic_period_id",
     *         in="query",
     *         required=false,
     *         description="ID of the academic period",
     *         @OA\Schema(type="integer", example="33")
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="string", example="id")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Limit",
     *         @OA\Schema(type="integer", example="10")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example="2"),
     *                         @OA\Property(property="name", type="string", example="National Education System"),
     *                         @OA\Property(property="academic_period_id", type="integer", example="30"),
     *                         @OA\Property(property="order", type="integer", example="1"),
     *                         @OA\Property(property="visible", type="integer", example="1"),
     *                         @OA\Property(property="modified_user_id", type="integer", example=null),
     *                         @OA\Property(property="modified", type="string", format="date-time", example=null),
     *                         @OA\Property(property="created_user_id", type="integer", example="2"),
     *                         @OA\Property(property="created", type="string", format="date-time", example="2014-09-20 22:07:18"),
     *                         @OA\Property(property="levels", type="array",
     *                             @OA\Items(
     *                                 type="object",
     *                                 @OA\Property(property="id", type="integer", example="4"),
     *                                 @OA\Property(property="name", type="string", example="Early Childhood Education"),
     *                                 @OA\Property(property="order", type="integer", example="1"),
     *                                 @OA\Property(property="visible", type="integer", example="1"),
     *                                 @OA\Property(property="education_system_id", type="integer", example="2"),
     *                                 @OA\Property(property="education_level_isced_id", type="integer", example="1"),
     *                                 @OA\Property(property="modified_user_id", type="integer", example=null),
     *                                 @OA\Property(property="modified", type="string", format="date-time", example=null),
     *                                 @OA\Property(property="created_user_id", type="integer", example="2"),
     *                                 @OA\Property(property="created", type="string", format="date-time", example="2014-09-20 22:07:18"),
     *                                 @OA\Property(property="cycles", type="array",
     *                                     @OA\Items(
     *                                         type="object",
     *                                         @OA\Property(property="id", type="integer", example="11"),
     *                                         @OA\Property(property="name", type="string", example="Pre-primary"),
     *                                         @OA\Property(property="admission_age", type="integer", example="5"),
     *                                         @OA\Property(property="order", type="integer", example="1"),
     *                                         @OA\Property(property="visible", type="integer", example="1"),
     *                                         @OA\Property(property="education_level_id", type="integer", example="4"),
     *                                         @OA\Property(property="modified_user_id", type="integer", example=null),
     *                                         @OA\Property(property="modified", type="string", format="date-time", example=null),
     *                                         @OA\Property(property="created_user_id", type="integer", example="2"),
     *                                         @OA\Property(property="created", type="string", format="date-time", example="2018-03-28 15:15:33"),
     *                                         @OA\Property(property="programmes", type="array",
     *                                             @OA\Items(
     *                                                 type="object",
     *                                                 @OA\Property(property="id", type="integer", example="8"),
     *                                                 @OA\Property(property="code", type="string", example="Kindergarten"),
     *                                                 @OA\Property(property="name", type="string", example="Kindergarten"),
     *                                                 @OA\Property(property="duration", type="integer", example="2"),
     *                                                 @OA\Property(property="order", type="integer", example="2"),
     *                                                 @OA\Property(property="visible", type="integer", example="1"),
     *                                                 @OA\Property(property="education_field_of_study_id", type="integer", example="1"),
     *                                                 @OA\Property(property="education_cycle_id", type="integer", example="11"),
     *                                                 @OA\Property(property="education_certification_id", type="integer", example="2"),
     *                                                 @OA\Property(property="same_grade_promotion", type="integer", example="0"),
     *                                                 @OA\Property(property="modified_user_id", type="integer", example=null),
     *                                                 @OA\Property(property="modified", type="string", format="date-time", example=null),
     *                                                 @OA\Property(property="created_user_id", type="integer", example="2"),
     *                                                 @OA\Property(property="created", type="string", format="date-time", example="2014-09-20 22:21:26"),
     *                                                 @OA\Property(property="grades", type="array",
     *                                                     @OA\Items(
     *                                                         type="object",
     *                                                         @OA\Property(property="id", type="integer", example="76"),
     *                                                         @OA\Property(property="code", type="string", example="Kindergarten 1"),
     *                                                         @OA\Property(property="name", type="string", example="Kindergarten 1"),
     *                                                         @OA\Property(property="admission_age", type="integer", example="5"),
     *                                                         @OA\Property(property="order", type="integer", example="1"),
     *                                                         @OA\Property(property="visible", type="integer", example="1"),
     *                                                         @OA\Property(property="education_stage_id", type="integer", example="14"),
     *                                                         @OA\Property(property="education_programme_id", type="integer", example="8"),
     *                                                         @OA\Property(property="modified_user_id", type="integer", example=null),
     *                                                         @OA\Property(property="modified", type="string", format="date-time", example=null),
     *                                                         @OA\Property(property="created_user_id", type="integer", example="2"),
     *                                                         @OA\Property(property="created", type="string", format="date-time", example="2014-09-20 22:21:26"),
     *                                                         @OA\Property(property="subjects", type="array",
     *                                                             @OA\Items(
     *                                                                 type="object",
     *                                                                 @OA\Property(property="id", type="integer", example="6"),
     *                                                                 @OA\Property(property="name", type="string", example="Language Arts Content Standards and Learning Outcomes"),
     *                                                                 @OA\Property(property="code", type="string", example="LAC"),
     *                                                                 @OA\Property(property="order", type="integer", example="1"),
     *                                                                 @OA\Property(property="visible", type="integer", example="1"),
     *                                                                 @OA\Property(property="hours_required", type="integer", nullable=true, example=null),
     *                                                                 @OA\Property(property="auto_allocation", type="integer", example="1"),
     *                                                                 @OA\Property(property="modified_user_id", type="integer", example=null),
     *                                                                 @OA\Property(property="modified", type="string", format="date-time", example=null),
     *                                                                 @OA\Property(property="created_user_id", type="integer", example="2"),
     *                                                                 @OA\Property(property="created", type="string", format="date-time", example="2019-10-12 00:09:00"),
     *                                                             )
     *                                                         )
     *                                                     )
     *                                                 )
     *                                             )
     *                                         )
     *                                     )
     *                                 )
     *                             )
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
    public function getEducationStructureCycle($systemId, $levelId, $cycleId, Request $request)
    {
        try {
            $data = $this->educationSystemService->getEducationStructureCycle($systemId, $levelId, $cycleId, $request);
            return $this->sendSuccessResponse("Education System List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Education System List Not Found');
        }
    }


    /**
     * @OA\Get(
     *     path="/api/v4/systems/{systemId}/levels/{levelId}/cycles/{cycleId}/programmes/{programmeId}/grades/subjects",
     *     summary="Education Structures",
     *     description="Returns subjects for the specified academic period, system id, level id, cycle id and programme Id.",
     *     tags={"Education Structure"},
     *     @OA\Parameter(
     *         name="systemId",
     *         in="path",
     *         required=true,
     *         description="ID of the system id",
     *         @OA\Schema(type="integer", example="2")
     *     ),
     *     @OA\Parameter(
     *         name="levelId",
     *         in="path",
     *         required=true,
     *         description="ID of the level id",
     *         @OA\Schema(type="integer", example="4")
     *     ),
     *     @OA\Parameter(
     *         name="cycleId",
     *         in="path",
     *         required=true,
     *         description="ID of the cycle id",
     *         @OA\Schema(type="integer", example="11")
     *     ),
     *     @OA\Parameter(
     *         name="programmeId",
     *         in="path",
     *         required=true,
     *         description="ID of the programme id",
     *         @OA\Schema(type="integer", example="8")
     *     ),
     *     @OA\Parameter(
     *         name="academic_period_id",
     *         in="query",
     *         required=false,
     *         description="ID of the academic period",
     *         @OA\Schema(type="integer", example="33")
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="string", example="id")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Limit",
     *         @OA\Schema(type="integer", example="10")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example="2"),
     *                         @OA\Property(property="name", type="string", example="National Education System"),
     *                         @OA\Property(property="academic_period_id", type="integer", example="30"),
     *                         @OA\Property(property="order", type="integer", example="1"),
     *                         @OA\Property(property="visible", type="integer", example="1"),
     *                         @OA\Property(property="modified_user_id", type="integer", example=null),
     *                         @OA\Property(property="modified", type="string", format="date-time", example=null),
     *                         @OA\Property(property="created_user_id", type="integer", example="2"),
     *                         @OA\Property(property="created", type="string", format="date-time", example="2014-09-20 22:07:18"),
     *                         @OA\Property(property="levels", type="array",
     *                             @OA\Items(
     *                                 type="object",
     *                                 @OA\Property(property="id", type="integer", example="4"),
     *                                 @OA\Property(property="name", type="string", example="Early Childhood Education"),
     *                                 @OA\Property(property="order", type="integer", example="1"),
     *                                 @OA\Property(property="visible", type="integer", example="1"),
     *                                 @OA\Property(property="education_system_id", type="integer", example="2"),
     *                                 @OA\Property(property="education_level_isced_id", type="integer", example="1"),
     *                                 @OA\Property(property="modified_user_id", type="integer", example=null),
     *                                 @OA\Property(property="modified", type="string", format="date-time", example=null),
     *                                 @OA\Property(property="created_user_id", type="integer", example="2"),
     *                                 @OA\Property(property="created", type="string", format="date-time", example="2014-09-20 22:07:18"),
     *                                 @OA\Property(property="cycles", type="array",
     *                                     @OA\Items(
     *                                         type="object",
     *                                         @OA\Property(property="id", type="integer", example="11"),
     *                                         @OA\Property(property="name", type="string", example="Pre-primary"),
     *                                         @OA\Property(property="admission_age", type="integer", example="5"),
     *                                         @OA\Property(property="order", type="integer", example="1"),
     *                                         @OA\Property(property="visible", type="integer", example="1"),
     *                                         @OA\Property(property="education_level_id", type="integer", example="4"),
     *                                         @OA\Property(property="modified_user_id", type="integer", example=null),
     *                                         @OA\Property(property="modified", type="string", format="date-time", example=null),
     *                                         @OA\Property(property="created_user_id", type="integer", example="2"),
     *                                         @OA\Property(property="created", type="string", format="date-time", example="2018-03-28 15:15:33"),
     *                                         @OA\Property(property="programmes", type="array",
     *                                             @OA\Items(
     *                                                 type="object",
     *                                                 @OA\Property(property="id", type="integer", example="8"),
     *                                                 @OA\Property(property="code", type="string", example="Kindergarten"),
     *                                                 @OA\Property(property="name", type="string", example="Kindergarten"),
     *                                                 @OA\Property(property="duration", type="integer", example="2"),
     *                                                 @OA\Property(property="order", type="integer", example="2"),
     *                                                 @OA\Property(property="visible", type="integer", example="1"),
     *                                                 @OA\Property(property="education_field_of_study_id", type="integer", example="1"),
     *                                                 @OA\Property(property="education_cycle_id", type="integer", example="11"),
     *                                                 @OA\Property(property="education_certification_id", type="integer", example="2"),
     *                                                 @OA\Property(property="same_grade_promotion", type="integer", example="0"),
     *                                                 @OA\Property(property="modified_user_id", type="integer", example=null),
     *                                                 @OA\Property(property="modified", type="string", format="date-time", example=null),
     *                                                 @OA\Property(property="created_user_id", type="integer", example="2"),
     *                                                 @OA\Property(property="created", type="string", format="date-time", example="2014-09-20 22:21:26"),
     *                                                 @OA\Property(property="grades", type="array",
     *                                                     @OA\Items(
     *                                                         type="object",
     *                                                         @OA\Property(property="id", type="integer", example="76"),
     *                                                         @OA\Property(property="code", type="string", example="Kindergarten 1"),
     *                                                         @OA\Property(property="name", type="string", example="Kindergarten 1"),
     *                                                         @OA\Property(property="admission_age", type="integer", example="5"),
     *                                                         @OA\Property(property="order", type="integer", example="1"),
     *                                                         @OA\Property(property="visible", type="integer", example="1"),
     *                                                         @OA\Property(property="education_stage_id", type="integer", example="14"),
     *                                                         @OA\Property(property="education_programme_id", type="integer", example="8"),
     *                                                         @OA\Property(property="modified_user_id", type="integer", example=null),
     *                                                         @OA\Property(property="modified", type="string", format="date-time", example=null),
     *                                                         @OA\Property(property="created_user_id", type="integer", example="2"),
     *                                                         @OA\Property(property="created", type="string", format="date-time", example="2014-09-20 22:21:26"),
     *                                                         @OA\Property(property="subjects", type="array",
     *                                                             @OA\Items(
     *                                                                 type="object",
     *                                                                 @OA\Property(property="id", type="integer", example="6"),
     *                                                                 @OA\Property(property="name", type="string", example="Language Arts Content Standards and Learning Outcomes"),
     *                                                                 @OA\Property(property="code", type="string", example="LAC"),
     *                                                                 @OA\Property(property="order", type="integer", example="1"),
     *                                                                 @OA\Property(property="visible", type="integer", example="1"),
     *                                                                 @OA\Property(property="hours_required", type="integer", nullable=true, example=null),
     *                                                                 @OA\Property(property="auto_allocation", type="integer", example="1"),
     *                                                                 @OA\Property(property="modified_user_id", type="integer", example=null),
     *                                                                 @OA\Property(property="modified", type="string", format="date-time", example=null),
     *                                                                 @OA\Property(property="created_user_id", type="integer", example="2"),
     *                                                                 @OA\Property(property="created", type="string", format="date-time", example="2019-10-12 00:09:00"),
     *                                                             )
     *                                                         )
     *                                                     )
     *                                                 )
     *                                             )
     *                                         )
     *                                     )
     *                                 )
     *                             )
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
    public function getEducationStructureProgramme($systemId, $levelId, $cycleId, $programmeId,  Request $request)
    {
        try {
            $data = $this->educationSystemService->getEducationStructureProgramme($systemId, $levelId, $cycleId, $programmeId, $request);
            return $this->sendSuccessResponse("Education System List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Education System List Not Found');
        }
    }


    /**
     * @OA\Get(
     *     path="/api/v4/systems/{systemId}/levels/{levelId}/cycles/{cycleId}/programmes/{programmeId}/grades/{gradeId}/subjects",
     *     summary="Education Structures",
     *     description="Returns subjects for the specified academic period, system id, level id, cycle id, programme id and grade id.",
     *     tags={"Education Structure"},
     *     @OA\Parameter(
     *         name="systemId",
     *         in="path",
     *         required=true,
     *         description="ID of the system id",
     *         @OA\Schema(type="integer", example="2")
     *     ),
     *     @OA\Parameter(
     *         name="levelId",
     *         in="path",
     *         required=true,
     *         description="ID of the level id",
     *         @OA\Schema(type="integer", example="4")
     *     ),
     *     @OA\Parameter(
     *         name="cycleId",
     *         in="path",
     *         required=true,
     *         description="ID of the cycle id",
     *         @OA\Schema(type="integer", example="11")
     *     ),
     *     @OA\Parameter(
     *         name="programmeId",
     *         in="path",
     *         required=true,
     *         description="ID of the programme id",
     *         @OA\Schema(type="integer", example="8")
     *     ),
     *     @OA\Parameter(
     *         name="gradeId",
     *         in="path",
     *         required=true,
     *         description="ID of the grade id",
     *         @OA\Schema(type="integer", example="8")
     *     ),
     *     @OA\Parameter(
     *         name="academic_period_id",
     *         in="query",
     *         required=false,
     *         description="ID of the academic period",
     *         @OA\Schema(type="integer", example="33")
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="string", example="id")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Limit",
     *         @OA\Schema(type="integer", example="10")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example="2"),
     *                         @OA\Property(property="name", type="string", example="National Education System"),
     *                         @OA\Property(property="academic_period_id", type="integer", example="30"),
     *                         @OA\Property(property="order", type="integer", example="1"),
     *                         @OA\Property(property="visible", type="integer", example="1"),
     *                         @OA\Property(property="modified_user_id", type="integer", example=null),
     *                         @OA\Property(property="modified", type="string", format="date-time", example=null),
     *                         @OA\Property(property="created_user_id", type="integer", example="2"),
     *                         @OA\Property(property="created", type="string", format="date-time", example="2014-09-20 22:07:18"),
     *                         @OA\Property(property="levels", type="array",
     *                             @OA\Items(
     *                                 type="object",
     *                                 @OA\Property(property="id", type="integer", example="4"),
     *                                 @OA\Property(property="name", type="string", example="Early Childhood Education"),
     *                                 @OA\Property(property="order", type="integer", example="1"),
     *                                 @OA\Property(property="visible", type="integer", example="1"),
     *                                 @OA\Property(property="education_system_id", type="integer", example="2"),
     *                                 @OA\Property(property="education_level_isced_id", type="integer", example="1"),
     *                                 @OA\Property(property="modified_user_id", type="integer", example=null),
     *                                 @OA\Property(property="modified", type="string", format="date-time", example=null),
     *                                 @OA\Property(property="created_user_id", type="integer", example="2"),
     *                                 @OA\Property(property="created", type="string", format="date-time", example="2014-09-20 22:07:18"),
     *                                 @OA\Property(property="cycles", type="array",
     *                                     @OA\Items(
     *                                         type="object",
     *                                         @OA\Property(property="id", type="integer", example="11"),
     *                                         @OA\Property(property="name", type="string", example="Pre-primary"),
     *                                         @OA\Property(property="admission_age", type="integer", example="5"),
     *                                         @OA\Property(property="order", type="integer", example="1"),
     *                                         @OA\Property(property="visible", type="integer", example="1"),
     *                                         @OA\Property(property="education_level_id", type="integer", example="4"),
     *                                         @OA\Property(property="modified_user_id", type="integer", example=null),
     *                                         @OA\Property(property="modified", type="string", format="date-time", example=null),
     *                                         @OA\Property(property="created_user_id", type="integer", example="2"),
     *                                         @OA\Property(property="created", type="string", format="date-time", example="2018-03-28 15:15:33"),
     *                                         @OA\Property(property="programmes", type="array",
     *                                             @OA\Items(
     *                                                 type="object",
     *                                                 @OA\Property(property="id", type="integer", example="8"),
     *                                                 @OA\Property(property="code", type="string", example="Kindergarten"),
     *                                                 @OA\Property(property="name", type="string", example="Kindergarten"),
     *                                                 @OA\Property(property="duration", type="integer", example="2"),
     *                                                 @OA\Property(property="order", type="integer", example="2"),
     *                                                 @OA\Property(property="visible", type="integer", example="1"),
     *                                                 @OA\Property(property="education_field_of_study_id", type="integer", example="1"),
     *                                                 @OA\Property(property="education_cycle_id", type="integer", example="11"),
     *                                                 @OA\Property(property="education_certification_id", type="integer", example="2"),
     *                                                 @OA\Property(property="same_grade_promotion", type="integer", example="0"),
     *                                                 @OA\Property(property="modified_user_id", type="integer", example=null),
     *                                                 @OA\Property(property="modified", type="string", format="date-time", example=null),
     *                                                 @OA\Property(property="created_user_id", type="integer", example="2"),
     *                                                 @OA\Property(property="created", type="string", format="date-time", example="2014-09-20 22:21:26"),
     *                                                 @OA\Property(property="grades", type="array",
     *                                                     @OA\Items(
     *                                                         type="object",
     *                                                         @OA\Property(property="id", type="integer", example="76"),
     *                                                         @OA\Property(property="code", type="string", example="Kindergarten 1"),
     *                                                         @OA\Property(property="name", type="string", example="Kindergarten 1"),
     *                                                         @OA\Property(property="admission_age", type="integer", example="5"),
     *                                                         @OA\Property(property="order", type="integer", example="1"),
     *                                                         @OA\Property(property="visible", type="integer", example="1"),
     *                                                         @OA\Property(property="education_stage_id", type="integer", example="14"),
     *                                                         @OA\Property(property="education_programme_id", type="integer", example="8"),
     *                                                         @OA\Property(property="modified_user_id", type="integer", example=null),
     *                                                         @OA\Property(property="modified", type="string", format="date-time", example=null),
     *                                                         @OA\Property(property="created_user_id", type="integer", example="2"),
     *                                                         @OA\Property(property="created", type="string", format="date-time", example="2014-09-20 22:21:26"),
     *                                                         @OA\Property(property="subjects", type="array",
     *                                                             @OA\Items(
     *                                                                 type="object",
     *                                                                 @OA\Property(property="id", type="integer", example="6"),
     *                                                                 @OA\Property(property="name", type="string", example="Language Arts Content Standards and Learning Outcomes"),
     *                                                                 @OA\Property(property="code", type="string", example="LAC"),
     *                                                                 @OA\Property(property="order", type="integer", example="1"),
     *                                                                 @OA\Property(property="visible", type="integer", example="1"),
     *                                                                 @OA\Property(property="hours_required", type="integer", nullable=true, example=null),
     *                                                                 @OA\Property(property="auto_allocation", type="integer", example="1"),
     *                                                                 @OA\Property(property="modified_user_id", type="integer", example=null),
     *                                                                 @OA\Property(property="modified", type="string", format="date-time", example=null),
     *                                                                 @OA\Property(property="created_user_id", type="integer", example="2"),
     *                                                                 @OA\Property(property="created", type="string", format="date-time", example="2019-10-12 00:09:00"),
     *                                                             )
     *                                                         )
     *                                                     )
     *                                                 )
     *                                             )
     *                                         )
     *                                     )
     *                                 )
     *                             )
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
    public function getEducationStructureGrade($systemId, $levelId, $cycleId, $programmeId, $gradeId, Request $request)
    {
        try {
            $data = $this->educationSystemService->getEducationStructureGrade($systemId, $levelId, $cycleId, $programmeId, $gradeId, $request);
            return $this->sendSuccessResponse("Education System List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Education System List Not Found');
        }
    }


    
    /**
     * @OA\Get(
     *     path="/api/v4/systems/{systemId}/levels/{levelId}/cycles/{cycleId}/programmes/{programmeId}/grades/{gradeId}/subjects/{subjectId}",
     *     summary="Education Structures",
     *     description="Returns subjects for the specified academic period, system id, level id, cycle id, programme id, grade id and subject id.",
     *     tags={"Education Structure"},
     *     @OA\Parameter(
     *         name="systemId",
     *         in="path",
     *         required=true,
     *         description="ID of the system id",
     *         @OA\Schema(type="integer", example="2")
     *     ),
     *     @OA\Parameter(
     *         name="levelId",
     *         in="path",
     *         required=true,
     *         description="ID of the level id",
     *         @OA\Schema(type="integer", example="4")
     *     ),
     *     @OA\Parameter(
     *         name="cycleId",
     *         in="path",
     *         required=true,
     *         description="ID of the cycle id",
     *         @OA\Schema(type="integer", example="11")
     *     ),
     *     @OA\Parameter(
     *         name="programmeId",
     *         in="path",
     *         required=true,
     *         description="ID of the programme id",
     *         @OA\Schema(type="integer", example="8")
     *     ),
     *     @OA\Parameter(
     *         name="gradeId",
     *         in="path",
     *         required=true,
     *         description="ID of the grade id",
     *         @OA\Schema(type="integer", example="77")
     *     ),
     *     @OA\Parameter(
     *         name="subjectId",
     *         in="path",
     *         required=true,
     *         description="ID of the subject id",
     *         @OA\Schema(type="integer", example="74")
     *     ),
     *     @OA\Parameter(
     *         name="academic_period_id",
     *         in="query",
     *         required=false,
     *         description="ID of the academic period",
     *         @OA\Schema(type="integer", example="33")
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="string", example="id")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Limit",
     *         @OA\Schema(type="integer", example="10")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example="2"),
     *                         @OA\Property(property="name", type="string", example="National Education System"),
     *                         @OA\Property(property="academic_period_id", type="integer", example="30"),
     *                         @OA\Property(property="order", type="integer", example="1"),
     *                         @OA\Property(property="visible", type="integer", example="1"),
     *                         @OA\Property(property="modified_user_id", type="integer", example=null),
     *                         @OA\Property(property="modified", type="string", format="date-time", example=null),
     *                         @OA\Property(property="created_user_id", type="integer", example="2"),
     *                         @OA\Property(property="created", type="string", format="date-time", example="2014-09-20 22:07:18"),
     *                         @OA\Property(property="levels", type="array",
     *                             @OA\Items(
     *                                 type="object",
     *                                 @OA\Property(property="id", type="integer", example="4"),
     *                                 @OA\Property(property="name", type="string", example="Early Childhood Education"),
     *                                 @OA\Property(property="order", type="integer", example="1"),
     *                                 @OA\Property(property="visible", type="integer", example="1"),
     *                                 @OA\Property(property="education_system_id", type="integer", example="2"),
     *                                 @OA\Property(property="education_level_isced_id", type="integer", example="1"),
     *                                 @OA\Property(property="modified_user_id", type="integer", example=null),
     *                                 @OA\Property(property="modified", type="string", format="date-time", example=null),
     *                                 @OA\Property(property="created_user_id", type="integer", example="2"),
     *                                 @OA\Property(property="created", type="string", format="date-time", example="2014-09-20 22:07:18"),
     *                                 @OA\Property(property="cycles", type="array",
     *                                     @OA\Items(
     *                                         type="object",
     *                                         @OA\Property(property="id", type="integer", example="11"),
     *                                         @OA\Property(property="name", type="string", example="Pre-primary"),
     *                                         @OA\Property(property="admission_age", type="integer", example="5"),
     *                                         @OA\Property(property="order", type="integer", example="1"),
     *                                         @OA\Property(property="visible", type="integer", example="1"),
     *                                         @OA\Property(property="education_level_id", type="integer", example="4"),
     *                                         @OA\Property(property="modified_user_id", type="integer", example=null),
     *                                         @OA\Property(property="modified", type="string", format="date-time", example=null),
     *                                         @OA\Property(property="created_user_id", type="integer", example="2"),
     *                                         @OA\Property(property="created", type="string", format="date-time", example="2018-03-28 15:15:33"),
     *                                         @OA\Property(property="programmes", type="array",
     *                                             @OA\Items(
     *                                                 type="object",
     *                                                 @OA\Property(property="id", type="integer", example="8"),
     *                                                 @OA\Property(property="code", type="string", example="Kindergarten"),
     *                                                 @OA\Property(property="name", type="string", example="Kindergarten"),
     *                                                 @OA\Property(property="duration", type="integer", example="2"),
     *                                                 @OA\Property(property="order", type="integer", example="2"),
     *                                                 @OA\Property(property="visible", type="integer", example="1"),
     *                                                 @OA\Property(property="education_field_of_study_id", type="integer", example="1"),
     *                                                 @OA\Property(property="education_cycle_id", type="integer", example="11"),
     *                                                 @OA\Property(property="education_certification_id", type="integer", example="2"),
     *                                                 @OA\Property(property="same_grade_promotion", type="integer", example="0"),
     *                                                 @OA\Property(property="modified_user_id", type="integer", example=null),
     *                                                 @OA\Property(property="modified", type="string", format="date-time", example=null),
     *                                                 @OA\Property(property="created_user_id", type="integer", example="2"),
     *                                                 @OA\Property(property="created", type="string", format="date-time", example="2014-09-20 22:21:26"),
     *                                                 @OA\Property(property="grades", type="array",
     *                                                     @OA\Items(
     *                                                         type="object",
     *                                                         @OA\Property(property="id", type="integer", example="76"),
     *                                                         @OA\Property(property="code", type="string", example="Kindergarten 1"),
     *                                                         @OA\Property(property="name", type="string", example="Kindergarten 1"),
     *                                                         @OA\Property(property="admission_age", type="integer", example="5"),
     *                                                         @OA\Property(property="order", type="integer", example="1"),
     *                                                         @OA\Property(property="visible", type="integer", example="1"),
     *                                                         @OA\Property(property="education_stage_id", type="integer", example="14"),
     *                                                         @OA\Property(property="education_programme_id", type="integer", example="8"),
     *                                                         @OA\Property(property="modified_user_id", type="integer", example=null),
     *                                                         @OA\Property(property="modified", type="string", format="date-time", example=null),
     *                                                         @OA\Property(property="created_user_id", type="integer", example="2"),
     *                                                         @OA\Property(property="created", type="string", format="date-time", example="2014-09-20 22:21:26"),
     *                                                         @OA\Property(property="subjects", type="array",
     *                                                             @OA\Items(
     *                                                                 type="object",
     *                                                                 @OA\Property(property="id", type="integer", example="6"),
     *                                                                 @OA\Property(property="name", type="string", example="Language Arts Content Standards and Learning Outcomes"),
     *                                                                 @OA\Property(property="code", type="string", example="LAC"),
     *                                                                 @OA\Property(property="order", type="integer", example="1"),
     *                                                                 @OA\Property(property="visible", type="integer", example="1"),
     *                                                                 @OA\Property(property="hours_required", type="integer", nullable=true, example=null),
     *                                                                 @OA\Property(property="auto_allocation", type="integer", example="1"),
     *                                                                 @OA\Property(property="modified_user_id", type="integer", example=null),
     *                                                                 @OA\Property(property="modified", type="string", format="date-time", example=null),
     *                                                                 @OA\Property(property="created_user_id", type="integer", example="2"),
     *                                                                 @OA\Property(property="created", type="string", format="date-time", example="2019-10-12 00:09:00"),
     *                                                             )
     *                                                         )
     *                                                     )
     *                                                 )
     *                                             )
     *                                         )
     *                                     )
     *                                 )
     *                             )
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
    public function getEducationStructureSubject($systemId, $levelId, $cycleId, $programmeId, $gradeId, $subjectId, Request $request)
    {
        try {
            $data = $this->educationSystemService->getEducationStructureSubject($systemId, $levelId, $cycleId, $programmeId, $gradeId, $subjectId, $request);
            return $this->sendSuccessResponse("Education System List Found", $data);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Education System List Not Found');
        }
    }


    
    /**
     * @OA\Get(
     *     path="/api/v4/systems/{system_id}/levels/{level_id}/cycles/{cycle_id}/programmes/{programme_id}/grades/{grade_id}/reportcards",
     *     summary="Get report cards for a specific grade",
     *     description="Returns a list of report cards belonging to the specified grade",
     *     tags={"Education Structure"},
     *     @OA\Parameter(
     *         name="system_id",
     *         in="path",
     *         required=true,
     *         description="ID of the system",
     *         @OA\Schema(type="integer", example="2")
     *     ),
     *     @OA\Parameter(
     *         name="level_id",
     *         in="path",
     *         required=true,
     *         description="ID of the level",
     *         @OA\Schema(type="integer", example="4")
     *     ),
     *     @OA\Parameter(
     *         name="cycle_id",
     *         in="path",
     *         required=true,
     *         description="ID of the cycle",
     *         @OA\Schema(type="integer", example="11")
     *     ),
     *     @OA\Parameter(
     *         name="programme_id",
     *         in="path",
     *         required=true,
     *         description="ID of the programme",
     *         @OA\Schema(type="integer", example="8")
     *     ),
     *     @OA\Parameter(
     *         name="grade_id",
     *         in="path",
     *         required=true,
     *         description="ID of the grade",
     *         @OA\Schema(type="integer", example="76")
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order",
     *         @OA\Schema(type="string", example="id")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Limit",
     *         @OA\Schema(type="integer", example="10")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example="1"),
     *                     @OA\Property(property="name", type="string", example="2018-K1 - 2018 - Kindergarten 1")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
    public function reportCardLists(Request $request,$systemId, $levelId, $cycleId, $programmeId, $gradeId)
    {
        try {
            $params = $request->all();
            $data = $this->educationSystemService->reportCardLists($params, $systemId, $levelId, $cycleId, $programmeId, $gradeId);
            
            return $this->sendSuccessResponse("Report Cards List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Report Cards List Not Found');
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v4/systems/{systemId}/levels/{levelId}/cycles/{cycleId}/programmes/{programmeId}/grades/{gradeId}/competencies",
     *     summary="Get competencies for a specific grade",
     *     description="Returns competencies belonging to the specified grade",
     *     tags={"Education Structure"},
     *     @OA\Parameter(
     *         name="systemId",
     *         in="path",
     *         required=true,
     *         description="ID of the system",
     *         @OA\Schema(type="integer", example="12")
     *     ),
     *     @OA\Parameter(
     *         name="levelId",
     *         in="path",
     *         required=true,
     *         description="ID of the level",
     *         @OA\Schema(type="integer", example="30")
     *     ),
     *     @OA\Parameter(
     *         name="cycleId",
     *         in="path",
     *         required=true,
     *         description="ID of the cycle",
     *         @OA\Schema(type="integer", example="40")
     *     ),
     *     @OA\Parameter(
     *         name="programmeId",
     *         in="path",
     *         required=true,
     *         description="ID of the programme",
     *         @OA\Schema(type="integer", example="38")
     *     ),
     *     @OA\Parameter(
     *         name="gradeId",
     *         in="path",
     *         required=true,
     *         description="ID of the grade",
     *         @OA\Schema(type="integer", example="136")
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Order by",
     *         @OA\Schema(type="string", example="id")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", example="10")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successful."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="academic_period_id", type="integer", example="27"),
     *                         @OA\Property(property="competency_template_id", type="integer", example="1"),
     *                         @OA\Property(property="competency_template_code", type="string", example="C1"),
     *                         @OA\Property(property="competency_template_name", type="string", example="Competency - Kindergarten 1"),
     *                         @OA\Property(property="competency_criteria_id", type="integer", example="2"),
     *                         @OA\Property(property="competency_criteria_code", type="string", example="1"),
     *                         @OA\Property(property="competency_criteria_name", type="string", example="Identifying and recognising emotions"),
     *                         @OA\Property(property="competency_item_id", type="integer", example="1"),
     *                         @OA\Property(property="competency_item_code", type="string", nullable=true, example=null),
     *                         @OA\Property(property="competency_item_name", type="string", example="Self-awareness"),
     *                         @OA\Property(property="competency_criteria_grade_id", type="integer", example="1"),
     *                         @OA\Property(property="competency_criteria_grade_code", type="string", example="CGT"),
     *                         @OA\Property(property="competency_criteria_grade_name", type="string", example="Competencies Levels")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unsuccessful."
     *     )
     * )
     */
    public function getCompetencies($systemId, $levelId, $cycleId, $programmeId, $gradeId, Request $request)
    {
        try {
            $data = $this->educationSystemService->getCompetencies($systemId, $levelId, $cycleId, $programmeId, $gradeId, $request);
            
            return $this->sendSuccessResponse("Competencies List Found", $data);
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Competencies List Not Found');
        }
    }
}
