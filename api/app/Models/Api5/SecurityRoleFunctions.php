<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecurityRoleFunctions extends Model
{
    use HasFactory;

    // ✅ Allow mass assignment
    public $timestamps = false;
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $fillable = ['_view', '_edit', '_add', '_delete', '_execute',
        'security_role_id', 'security_function_id',
        'modified_user_id', 'modified',
        'created_user_id', 'created',
        ];
    protected $dates = ['modified', 'created'];
    protected $table = "security_role_functions";

    // ✅ Allow mass assignment
    public $incrementing = false;

    // ✅ Define the primary key
    protected $primaryKey = ["security_role_id","security_function_id"];


/**
 * @OA\PathItem(
 *     path="/api/v5/security-role-functions"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/security-role-functions",
 *     summary="Get list of SecurityRoleFunctions",
 *     tags={"SecurityRoleFunctions"},
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
                          @OA\Property(property="_view", type="integer", example=null),
                          @OA\Property(property="_edit", type="integer", example=null),
                          @OA\Property(property="_add", type="integer", example=null),
                          @OA\Property(property="_delete", type="integer", example=null),
                          @OA\Property(property="_execute", type="integer", example=null),
                          @OA\Property(property="security_role_id", type="integer", example=null),
                          @OA\Property(property="security_function_id", type="integer", example=null),
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
 *     path="/api/v5/security-role-functions",
 *     summary="Create a new SecurityRoleFunctions",
 *     tags={"SecurityRoleFunctions"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="_view", type="integer", example=null),
                     @OA\Property(property="_edit", type="integer", example=null),
                     @OA\Property(property="_add", type="integer", example=null),
                     @OA\Property(property="_delete", type="integer", example=null),
                     @OA\Property(property="_execute", type="integer", example=null),
                     @OA\Property(property="security_role_id", type="integer", example=null),
                     @OA\Property(property="security_function_id", type="integer", example=null),
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
 *     path="/api/v5/security-role-functions/security_role_id/{security_role_id}/security_function_id/{security_function_id}",
 *     summary="Get SecurityRoleFunctions record by composite key",
 *     tags={"SecurityRoleFunctions"},
 *     @OA\Parameter(
 *         name="security_role_id",
 *         in="path",
 *         required=true,
 *         description="security_role_id",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="security_function_id",
 *         in="path",
 *         required=true,
 *         description="security_function_id",
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
 *     path="/api/v5/security-role-functions/security_role_id/{security_role_id}/security_function_id/{security_function_id}",
 *     summary="Update SecurityRoleFunctions record by composite key",
 *     tags={"SecurityRoleFunctions"},
 *     @OA\Parameter(
 *         name="security_role_id",
 *         in="path",
 *         required=true,
 *         description="security_role_id",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="security_function_id",
 *         in="path",
 *         required=true,
 *         description="security_function_id",
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
 *     path="/api/v5/security-role-functions/security_role_id/{security_role_id}/security_function_id/{security_function_id}",
 *     summary="Delete SecurityRoleFunctions record by composite key",
 *     tags={"SecurityRoleFunctions"},
 *     @OA\Parameter(
 *         name="security_role_id",
 *         in="path",
 *         required=true,
 *         description="security_role_id",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="security_function_id",
 *         in="path",
 *         required=true,
 *         description="security_function_id",
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
