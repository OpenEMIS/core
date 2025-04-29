<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecurityGroupAreas extends Model
{
    use HasFactory;

    // ✅ Allow mass assignment
    public $timestamps = false;
    public $incrementing = false;
    protected $fillable = ['security_group_id', 'area_id',
        'created_user_id', 'created',
    ];
    protected $table = "security_group_areas";
    protected $primaryKey = ["security_group_id", "area_id"];


/**
 * @OA\PathItem(
 *     path="/api/v5/security-group-areas"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/security-group-areas",
 *     summary="Get list of SecurityGroupAreas",
 *     tags={"SecurityGroupAreas"},
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
                          @OA\Property(property="security_group_id", type="integer", example=null),
                          @OA\Property(property="area_id", type="integer", example=null),
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
 *     path="/api/v5/security-group-areas",
 *     summary="Create a new SecurityGroupAreas",
 *     tags={"SecurityGroupAreas"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="security_group_id", type="integer", example=null),
                     @OA\Property(property="area_id", type="integer", example=null),
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
 *     path="/api/v5/security-group-areas/security_group_id/{security_group_id}/area_id/{area_id}",
 *     summary="Get SecurityGroupAreas record by composite key",
 *     tags={"SecurityGroupAreas"},
 *     @OA\Parameter(
 *         name="security_group_id",
 *         in="path",
 *         required=true,
 *         description="security_group_id",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="area_id",
 *         in="path",
 *         required=true,
 *         description="area_id",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Record found"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Record not found"
 *     )
 * )
 */
public function _swaggerView() {}

/**
 * @OA\Put(
 *     path="/api/v5/security-group-areas/security_group_id/{security_group_id}/area_id/{area_id}",
 *     summary="Update SecurityGroupAreas record by composite key",
 *     tags={"SecurityGroupAreas"},
 *     @OA\Parameter(
 *         name="security_group_id",
 *         in="path",
 *         required=true,
 *         description="security_group_id",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="area_id",
 *         in="path",
 *         required=true,
 *         description="area_id",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
 *              *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Record updated successfully"
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Invalid data provided"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Record not found"
 *     )
 * )
 */
public function _swaggerUpdate() {}

/**
 * @OA\Delete(
 *     path="/api/v5/security-group-areas/security_group_id/{security_group_id}/area_id/{area_id}",
 *     summary="Delete SecurityGroupAreas record by composite key",
 *     tags={"SecurityGroupAreas"},
 *     @OA\Parameter(
 *         name="security_group_id",
 *         in="path",
 *         required=true,
 *         description="security_group_id",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="area_id",
 *         in="path",
 *         required=true,
 *         description="area_id",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response=204,
 *         description="Record deleted successfully"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Record not found"
 *     )
 * )
 */
public function _swaggerDelete() {}

    protected function getKeyForSaveQuery()
    {
        $query = $this->newQueryWithoutScopes();
        $keyName = $this->getKeyName();
        if (!is_array($keyName)) {
            $keyName = [$keyName];;
        }
        foreach ($keyName as $key) {
            $query->where($key, '=', $this->getAttribute($key));
        }

        return $query;
    }

    protected function setKeysForSaveQuery($query)
    {
        $keyName = $this->getKeyName();
        if (!is_array($keyName)) {
            $keyName = [$keyName];;
        }
        foreach ($keyName as $key) {
            $query->where($key, '=', $this->getAttribute($key));
        }

        return $query;
    }


}
