<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UuidId;

class StudentGuardians extends Model
{
    use HasFactory;
use UuidId;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'student_id', 'guardian_id', 'guardian_relation_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'student_id', 'guardian_id', 'guardian_relation_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];
    public $timestamps = false;
    protected $table = "student_guardians";
    protected $primaryKey = 'id';
    public $incrementing = false;

    // POCOR-8840 start
    /**
     * Define the relationship with the `SecurityUsers` model (Guardian).
     */
/**
 * @OA\PathItem(
 *     path="/api/v5/student-guardians"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/student-guardians",
 *     summary="Get list of StudentGuardians",
 *     tags={"StudentGuardians"},
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
                          @OA\Property(property="id", type="string", example=null),
                          @OA\Property(property="student_id", type="integer", example=null),
                          @OA\Property(property="guardian_id", type="integer", example=null),
                          @OA\Property(property="guardian_relation_id", type="integer", example=null),
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
 *     path="/api/v5/student-guardians/{id}",
 *     summary="Get StudentGuardians by ID",
 *     tags={"StudentGuardians"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the StudentGuardians",
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
 *     path="/api/v5/student-guardians",
 *     summary="Create a new StudentGuardians",
 *     tags={"StudentGuardians"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="string", example=null),
                     @OA\Property(property="student_id", type="integer", example=null),
                     @OA\Property(property="guardian_id", type="integer", example=null),
                     @OA\Property(property="guardian_relation_id", type="integer", example=null),
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
 *     path="/api/v5/student-guardians/{id}",
 *     summary="Update StudentGuardians",
 *     tags={"StudentGuardians"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the StudentGuardians",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="string", example=null),
                     @OA\Property(property="student_id", type="integer", example=null),
                     @OA\Property(property="guardian_id", type="integer", example=null),
                     @OA\Property(property="guardian_relation_id", type="integer", example=null),
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
 *     path="/api/v5/student-guardians/{id}",
 *     summary="Delete StudentGuardians",
 *     tags={"StudentGuardians"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the StudentGuardians",
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
    public function guardian(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SecurityUsers::class, 'guardian_id', 'id');
    }

    /**
     * Define the relationship with the `SecurityUsers` model (Student).
     */
    public function student(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SecurityUsers::class, 'student_id', 'id');
    }
    // POCOR-8840 end
}
