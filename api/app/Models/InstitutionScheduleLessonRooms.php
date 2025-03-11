<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionScheduleLessonRooms extends Model
{
    use HasFactory;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'institution_schedule_lesson_detail_id', 'institution_room_id', 'institution_schedule_lesson_detail_id', 'institution_room_id'];
    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;

/**
 * @OA\PathItem(
 *     path="/api/v5/institution-schedule-lesson-rooms"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/institution-schedule-lesson-rooms",
 *     summary="Get list of InstitutionScheduleLessonRooms",
 *     tags={"InstitutionScheduleLessonRooms"},
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
                          @OA\Property(property="institution_schedule_lesson_detail_id", type="integer", example=null),
                          @OA\Property(property="institution_room_id", type="integer", example=null)
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
 * @OA\Get(
 *     path="/api/v5/institution-schedule-lesson-rooms/{id}",
 *     summary="Get InstitutionScheduleLessonRooms by ID",
 *     tags={"InstitutionScheduleLessonRooms"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the InstitutionScheduleLessonRooms",
 *         @OA\Schema(type="integer")
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
 * @OA\Post(
 *     path="/api/v5/institution-schedule-lesson-rooms",
 *     summary="Create a new InstitutionScheduleLessonRooms",
 *     tags={"InstitutionScheduleLessonRooms"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="institution_schedule_lesson_detail_id", type="integer", example=null),
                     @OA\Property(property="institution_room_id", type="integer", example=null)
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
 * @OA\Put(
 *     path="/api/v5/institution-schedule-lesson-rooms/{id}",
 *     summary="Update InstitutionScheduleLessonRooms",
 *     tags={"InstitutionScheduleLessonRooms"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the InstitutionScheduleLessonRooms",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="institution_schedule_lesson_detail_id", type="integer", example=null),
                     @OA\Property(property="institution_room_id", type="integer", example=null)
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
 *     path="/api/v5/institution-schedule-lesson-rooms/{id}",
 *     summary="Delete InstitutionScheduleLessonRooms",
 *     tags={"InstitutionScheduleLessonRooms"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the InstitutionScheduleLessonRooms",
 *         @OA\Schema(type="integer")
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
    public function lesson()
    {
        return $this->belongsTo(InstitutionScheduleLessonDetails::class, 'institution_schedule_lesson_detail_id', 'id');
    }

    public function institution_room()
    {
        return $this->belongsTo(InstitutionRooms::class, 'institution_room_id', 'id');
    }
}