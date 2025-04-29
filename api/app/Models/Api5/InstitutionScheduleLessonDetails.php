<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionScheduleLessonDetails extends Model
{
    use HasFactory;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'lesson_type', 'day_of_week', 'institution_schedule_timeslot_id', 'institution_schedule_timetable_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'institution_schedule_timeslot_id', 'institution_schedule_timetable_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];
    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;

    use \Awobaz\Compoships\Compoships;


/**
 * @OA\PathItem(
 *     path="/api/v5/institution-schedule-lesson-details"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/institution-schedule-lesson-details",
 *     summary="Get list of InstitutionScheduleLessonDetails",
 *     tags={"InstitutionScheduleLessonDetails"},
 *     @OA\Parameter(
 *         name="limit",
 *         in="query",
 *         required=false,
 *         description="Maximum number of results to return",
 *         @OA\Schema(type="number")
 *     ),
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         required=false,
 *         description="Page number for paginated results",
 *         @OA\Schema(type="number")
 *     ),
 *     @OA\Parameter(
 *         name="orderby",
 *         in="query",
 *         required=false,
 *         description="Field to order results by",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="order",
 *         in="query",
 *         required=false,
 *         description="Order direction: asc or desc",
 *         @OA\Schema(type="string", enum={"asc", "desc"})
 *     ),
 *     @OA\Parameter(
 *         name="_fields",
 *         in="query",
 *         required=false,
 *         description="Comma-separated list of fields to include in response",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 example="Successful."
 *             ),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
                          @OA\Property(property="id", type="integer", example=null),
                          @OA\Property(property="lesson_type", type="integer", example=null),
                          @OA\Property(property="day_of_week", type="integer", example=null),
                          @OA\Property(property="institution_schedule_timeslot_id", type="integer", example=null),
                          @OA\Property(property="institution_schedule_timetable_id", type="integer", example=null),
                          @OA\Property(property="modified_user_id", type="integer", example=null),
                          @OA\Property(property="modified", type="string", format="date-time", example=null),
                          @OA\Property(property="created_user_id", type="integer", example=null),
                          @OA\Property(property="created", type="string", format="date-time", example=null)
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized"
 *     )
 * )
 */
public function _swaggerList() {}

/**
 * @OA\Post(
 *     path="/api/v5/institution-schedule-lesson-details",
 *     summary="Create a new InstitutionScheduleLessonDetails",
 *     tags={"InstitutionScheduleLessonDetails"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="lesson_type", type="integer", example=null),
                     @OA\Property(property="day_of_week", type="integer", example=null),
                     @OA\Property(property="institution_schedule_timeslot_id", type="integer", example=null),
                     @OA\Property(property="institution_schedule_timetable_id", type="integer", example=null),
                     @OA\Property(property="modified_user_id", type="integer", example=null),
                     @OA\Property(property="modified", type="string", format="date-time", example=null),
                     @OA\Property(property="created_user_id", type="integer", example=null),
                     @OA\Property(property="created", type="string", format="date-time", example=null)
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Created successfully"
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Invalid data"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized"
 *     )
 * )
 */
public function _swaggerCreate() {}


/**
 * @OA\Get(
 *     path="/api/v5/institution-schedule-lesson-details/{id}",
 *     summary="Get InstitutionScheduleLessonDetails by ID",
 *     tags={"InstitutionScheduleLessonDetails"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the InstitutionScheduleLessonDetails",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Not found"
 *     )
 * )
 */
public function _swaggerView() {}

/**
 * @OA\Put(
 *     path="/api/v5/institution-schedule-lesson-details/{id}",
 *     summary="Update InstitutionScheduleLessonDetails",
 *     tags={"InstitutionScheduleLessonDetails"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the InstitutionScheduleLessonDetails",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="lesson_type", type="integer", example=null),
                     @OA\Property(property="day_of_week", type="integer", example=null),
                     @OA\Property(property="institution_schedule_timeslot_id", type="integer", example=null),
                     @OA\Property(property="institution_schedule_timetable_id", type="integer", example=null),
                     @OA\Property(property="modified_user_id", type="integer", example=null),
                     @OA\Property(property="modified", type="string", format="date-time", example=null),
                     @OA\Property(property="created_user_id", type="integer", example=null),
                     @OA\Property(property="created", type="string", format="date-time", example=null)
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Updated successfully"
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Invalid data"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Not found"
 *     )
 * )
 */
public function _swaggerUpdate() {}

/**
 * @OA\Delete(
 *     path="/api/v5/institution-schedule-lesson-details/{id}",
 *     summary="Delete InstitutionScheduleLessonDetails",
 *     tags={"InstitutionScheduleLessonDetails"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the InstitutionScheduleLessonDetails",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response=204,
 *         description="Deleted successfully"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Not found"
 *     )
 * )
 */
public function _swaggerDelete() {}
    public function schedule_curriculum_lesson()
    {
        return $this->hasOne(InstitutionScheduleCurriculumLessons::class, 'institution_schedule_lesson_detail_id', 'id');
    }

    public function schedule_non_curriculum_lesson()

    {
        return $this->hasOne(InstitutionScheduleNonCurriculumLessons::class, 'institution_schedule_lesson_detail_id', 'id');
    }

    public function schedule_lesson_room()
    {
        return $this->hasOne(InstitutionScheduleLessonRooms::class, 'institution_schedule_lesson_detail_id', 'id');
    }

}
