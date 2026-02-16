<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MealProgrammes extends Model
{
    use HasFactory;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'academic_period_id', 'name', 'code', 'type', 'targeting', 'start_date', 'end_date', 'amount', 'implementer', 'modified_user_id', 'modified', 'created_user_id', 'created', 'academic_period_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];
    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;
    protected $table = "meal_programmes";


/**
 * @OA\PathItem(
 *     path="/api/v5/meal-programmes"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/meal-programmes",
 *     summary="Get list of MealProgrammes",
 *     tags={"MealProgrammes"},
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
                          @OA\Property(property="academic_period_id", type="integer", example=null),
                          @OA\Property(property="name", type="string", example=null),
                          @OA\Property(property="code", type="string", example=null),
                          @OA\Property(property="type", type="integer", example=null),
                          @OA\Property(property="targeting", type="integer", example=null),
                          @OA\Property(property="start_date", type="string", format="date", example=null),
                          @OA\Property(property="end_date", type="string", format="date", example=null),
                          @OA\Property(property="amount", type="number", example=null),
                          @OA\Property(property="implementer", type="integer", example=null),
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
 *     path="/api/v5/meal-programmes",
 *     summary="Create a new MealProgrammes",
 *     tags={"MealProgrammes"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="academic_period_id", type="integer", example=null),
                     @OA\Property(property="name", type="string", example=null),
                     @OA\Property(property="code", type="string", example=null),
                     @OA\Property(property="type", type="integer", example=null),
                     @OA\Property(property="targeting", type="integer", example=null),
                     @OA\Property(property="start_date", type="string", format="date", example=null),
                     @OA\Property(property="end_date", type="string", format="date", example=null),
                     @OA\Property(property="amount", type="number", example=null),
                     @OA\Property(property="implementer", type="integer", example=null),
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
 *     path="/api/v5/meal-programmes/{id}",
 *     summary="Get MealProgrammes by ID",
 *     tags={"MealProgrammes"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the MealProgrammes",
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
 *     path="/api/v5/meal-programmes/{id}",
 *     summary="Update MealProgrammes",
 *     tags={"MealProgrammes"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the MealProgrammes",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="academic_period_id", type="integer", example=null),
                     @OA\Property(property="name", type="string", example=null),
                     @OA\Property(property="code", type="string", example=null),
                     @OA\Property(property="type", type="integer", example=null),
                     @OA\Property(property="targeting", type="integer", example=null),
                     @OA\Property(property="start_date", type="string", format="date", example=null),
                     @OA\Property(property="end_date", type="string", format="date", example=null),
                     @OA\Property(property="amount", type="number", example=null),
                     @OA\Property(property="implementer", type="integer", example=null),
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
 *     path="/api/v5/meal-programmes/{id}",
 *     summary="Delete MealProgrammes",
 *     tags={"MealProgrammes"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the MealProgrammes",
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
private function emptyFunction() { return; }
}
