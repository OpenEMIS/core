<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;

class InstitutionRooms extends Model
{
    use HasFactory;
use InstitutionScope;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'code', 'name', 'start_date', 'start_year', 'end_date', 'end_year', 'accessibility', 'comment', 'room_type_id', 'room_status_id', 'institution_floor_id', 'institution_id', 'infrastructure_condition_id', 'area', 'previous_institution_room_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'room_type_id', 'room_status_id', 'institution_floor_id', 'institution_id', 'infrastructure_condition_id', 'previous_institution_room_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];
    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;


/**
 * @OA\PathItem(
 *     path="/api/v5/institution-rooms"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/institution-rooms",
 *     summary="Get list of InstitutionRooms",
 *     tags={"InstitutionRooms"},
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
                          @OA\Property(property="code", type="string", example=null),
                          @OA\Property(property="name", type="string", example=null),
                          @OA\Property(property="start_date", type="string", format="date", example=null),
                          @OA\Property(property="start_year", type="integer", example=null),
                          @OA\Property(property="end_date", type="string", format="date", example=null),
                          @OA\Property(property="end_year", type="integer", example=null),
                          @OA\Property(property="accessibility", type="integer", example=null),
                          @OA\Property(property="comment", type="string", example=null),
                          @OA\Property(property="room_type_id", type="integer", example=null),
                          @OA\Property(property="room_status_id", type="integer", example=null),
                          @OA\Property(property="institution_floor_id", type="integer", example=null),
                          @OA\Property(property="institution_id", type="integer", example=null),
                          @OA\Property(property="infrastructure_condition_id", type="integer", example=null),
                          @OA\Property(property="area", type="number", example=null),
                          @OA\Property(property="previous_institution_room_id", type="integer", example=null),
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
 * @OA\Get(
 *     path="/api/v5/institution-rooms/{id}",
 *     summary="Get InstitutionRooms by ID",
 *     tags={"InstitutionRooms"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the InstitutionRooms",
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
 *     path="/api/v5/institution-rooms",
 *     summary="Create a new InstitutionRooms",
 *     tags={"InstitutionRooms"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="code", type="string", example=null),
                     @OA\Property(property="name", type="string", example=null),
                     @OA\Property(property="start_date", type="string", format="date", example=null),
                     @OA\Property(property="start_year", type="integer", example=null),
                     @OA\Property(property="end_date", type="string", format="date", example=null),
                     @OA\Property(property="end_year", type="integer", example=null),
                     @OA\Property(property="accessibility", type="integer", example=null),
                     @OA\Property(property="comment", type="string", example=null),
                     @OA\Property(property="room_type_id", type="integer", example=null),
                     @OA\Property(property="room_status_id", type="integer", example=null),
                     @OA\Property(property="institution_floor_id", type="integer", example=null),
                     @OA\Property(property="institution_id", type="integer", example=null),
                     @OA\Property(property="infrastructure_condition_id", type="integer", example=null),
                     @OA\Property(property="area", type="number", example=null),
                     @OA\Property(property="previous_institution_room_id", type="integer", example=null),
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
 * @OA\Put(
 *     path="/api/v5/institution-rooms/{id}",
 *     summary="Update InstitutionRooms",
 *     tags={"InstitutionRooms"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the InstitutionRooms",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="code", type="string", example=null),
                     @OA\Property(property="name", type="string", example=null),
                     @OA\Property(property="start_date", type="string", format="date", example=null),
                     @OA\Property(property="start_year", type="integer", example=null),
                     @OA\Property(property="end_date", type="string", format="date", example=null),
                     @OA\Property(property="end_year", type="integer", example=null),
                     @OA\Property(property="accessibility", type="integer", example=null),
                     @OA\Property(property="comment", type="string", example=null),
                     @OA\Property(property="room_type_id", type="integer", example=null),
                     @OA\Property(property="room_status_id", type="integer", example=null),
                     @OA\Property(property="institution_floor_id", type="integer", example=null),
                     @OA\Property(property="institution_id", type="integer", example=null),
                     @OA\Property(property="infrastructure_condition_id", type="integer", example=null),
                     @OA\Property(property="area", type="number", example=null),
                     @OA\Property(property="previous_institution_room_id", type="integer", example=null),
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
 *     path="/api/v5/institution-rooms/{id}",
 *     summary="Delete InstitutionRooms",
 *     tags={"InstitutionRooms"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the InstitutionRooms",
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
    public function _swaggerHelper() {
        return;
    }
}