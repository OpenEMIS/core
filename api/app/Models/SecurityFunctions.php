<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecurityFunctions extends Model
{
    use HasFactory;

    protected $table = 'security_functions';

    // ✅ Allow mass assignment
    protected $fillable = ['id', 'name', 'controller', 'module', 'category', 'parent_id', '_view', '_edit', '_add', '_delete', '_execute', 'order', 'visible', 'description', 'modified_user_id', 'modified', 'created_user_id', 'created'];

    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;

    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    // ✅ Define the primary key
    
    

     // Override getKeyForSaveQuery to handle composite keys
/**
 * @OA\PathItem(
 *     path="/api/v5/security-functions"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/security-functions",
 *     summary="Get list of SecurityFunctions",
 *     tags={"SecurityFunctions"},
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
                          @OA\Property(property="name", type="string", example=null),
                          @OA\Property(property="controller", type="string", example=null),
                          @OA\Property(property="module", type="string", example=null),
                          @OA\Property(property="category", type="string", example=null),
                          @OA\Property(property="parent_id", type="integer", example=null),
                          @OA\Property(property="_view", type="string", example=null),
                          @OA\Property(property="_edit", type="string", example=null),
                          @OA\Property(property="_add", type="string", example=null),
                          @OA\Property(property="_delete", type="string", example=null),
                          @OA\Property(property="_execute", type="string", example=null),
                          @OA\Property(property="order", type="integer", example=null),
                          @OA\Property(property="visible", type="integer", example=null),
                          @OA\Property(property="description", type="string", example=null),
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
 *     path="/api/v5/security-functions/{id}",
 *     summary="Get SecurityFunctions by ID",
 *     tags={"SecurityFunctions"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the SecurityFunctions",
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
 *     path="/api/v5/security-functions",
 *     summary="Create a new SecurityFunctions",
 *     tags={"SecurityFunctions"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="name", type="string", example=null),
                     @OA\Property(property="controller", type="string", example=null),
                     @OA\Property(property="module", type="string", example=null),
                     @OA\Property(property="category", type="string", example=null),
                     @OA\Property(property="parent_id", type="integer", example=null),
                     @OA\Property(property="_view", type="string", example=null),
                     @OA\Property(property="_edit", type="string", example=null),
                     @OA\Property(property="_add", type="string", example=null),
                     @OA\Property(property="_delete", type="string", example=null),
                     @OA\Property(property="_execute", type="string", example=null),
                     @OA\Property(property="order", type="integer", example=null),
                     @OA\Property(property="visible", type="integer", example=null),
                     @OA\Property(property="description", type="string", example=null),
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
 *     path="/api/v5/security-functions/{id}",
 *     summary="Update SecurityFunctions",
 *     tags={"SecurityFunctions"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the SecurityFunctions",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="name", type="string", example=null),
                     @OA\Property(property="controller", type="string", example=null),
                     @OA\Property(property="module", type="string", example=null),
                     @OA\Property(property="category", type="string", example=null),
                     @OA\Property(property="parent_id", type="integer", example=null),
                     @OA\Property(property="_view", type="string", example=null),
                     @OA\Property(property="_edit", type="string", example=null),
                     @OA\Property(property="_add", type="string", example=null),
                     @OA\Property(property="_delete", type="string", example=null),
                     @OA\Property(property="_execute", type="string", example=null),
                     @OA\Property(property="order", type="integer", example=null),
                     @OA\Property(property="visible", type="integer", example=null),
                     @OA\Property(property="description", type="string", example=null),
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
 *     path="/api/v5/security-functions/{id}",
 *     summary="Delete SecurityFunctions",
 *     tags={"SecurityFunctions"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the SecurityFunctions",
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
    protected function getKeyForSaveQuery()
    {
        $query = $this->newQueryWithoutScopes();
        $keyName = $this->getKeyName();
        if(!is_array($keyName)){
            $keyName = [$keyName];;
        }
        foreach ($keyName as $key) {
            $query->where($key, '=', $this->getAttribute($key));
        }

        return $query;
    }

    // Override setKeysForSaveQuery to handle composite keys
    protected function setKeysForSaveQuery($query)
    {
        $keyName = $this->getKeyName();
        if(!is_array($keyName)){
            $keyName = [$keyName];;
        }
        foreach ($keyName as $key) {
            $query->where($key, '=', $this->getAttribute($key));
        }

        return $query;
    }

    public static function getValidationRules(): array
    {
        return [
            // Add validation rules here
        ];
    }




    public function _swaggerHelper() {
        return;
    }
}