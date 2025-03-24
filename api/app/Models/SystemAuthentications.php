<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemAuthentications extends Model
{
    use HasFactory;

    protected $table = 'system_authentications';

    // ✅ Allow mass assignment
    protected $fillable = ['id', 'code', 'name', 'status', 'allow_create_user', 'mapped_username', 'mapped_first_name', 'mapped_last_name', 'mapped_date_of_birth', 'mapped_gender', 'mapped_role', 'mapped_email', 'authentication_type_id'];

    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;

    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    // ✅ Define the primary key
    
    

     // Override getKeyForSaveQuery to handle composite keys
/**
 * @OA\PathItem(
 *     path="/api/v5/system-authentications"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/system-authentications",
 *     summary="Get list of SystemAuthentications",
 *     tags={"SystemAuthentications"},
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
                          @OA\Property(property="status", type="integer", example=null),
                          @OA\Property(property="allow_create_user", type="integer", example=null),
                          @OA\Property(property="mapped_username", type="string", example=null),
                          @OA\Property(property="mapped_first_name", type="string", example=null),
                          @OA\Property(property="mapped_last_name", type="string", example=null),
                          @OA\Property(property="mapped_date_of_birth", type="string", example=null),
                          @OA\Property(property="mapped_gender", type="string", example=null),
                          @OA\Property(property="mapped_role", type="string", example=null),
                          @OA\Property(property="mapped_email", type="string", example=null),
                          @OA\Property(property="authentication_type_id", type="integer", example=null)
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
 *     path="/api/v5/system-authentications/{id}",
 *     summary="Get SystemAuthentications by ID",
 *     tags={"SystemAuthentications"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the SystemAuthentications",
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
 *     path="/api/v5/system-authentications",
 *     summary="Create a new SystemAuthentications",
 *     tags={"SystemAuthentications"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="code", type="string", example=null),
                     @OA\Property(property="name", type="string", example=null),
                     @OA\Property(property="status", type="integer", example=null),
                     @OA\Property(property="allow_create_user", type="integer", example=null),
                     @OA\Property(property="mapped_username", type="string", example=null),
                     @OA\Property(property="mapped_first_name", type="string", example=null),
                     @OA\Property(property="mapped_last_name", type="string", example=null),
                     @OA\Property(property="mapped_date_of_birth", type="string", example=null),
                     @OA\Property(property="mapped_gender", type="string", example=null),
                     @OA\Property(property="mapped_role", type="string", example=null),
                     @OA\Property(property="mapped_email", type="string", example=null),
                     @OA\Property(property="authentication_type_id", type="integer", example=null)
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
 *     path="/api/v5/system-authentications/{id}",
 *     summary="Update SystemAuthentications",
 *     tags={"SystemAuthentications"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the SystemAuthentications",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="code", type="string", example=null),
                     @OA\Property(property="name", type="string", example=null),
                     @OA\Property(property="status", type="integer", example=null),
                     @OA\Property(property="allow_create_user", type="integer", example=null),
                     @OA\Property(property="mapped_username", type="string", example=null),
                     @OA\Property(property="mapped_first_name", type="string", example=null),
                     @OA\Property(property="mapped_last_name", type="string", example=null),
                     @OA\Property(property="mapped_date_of_birth", type="string", example=null),
                     @OA\Property(property="mapped_gender", type="string", example=null),
                     @OA\Property(property="mapped_role", type="string", example=null),
                     @OA\Property(property="mapped_email", type="string", example=null),
                     @OA\Property(property="authentication_type_id", type="integer", example=null)
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
 *     path="/api/v5/system-authentications/{id}",
 *     summary="Delete SystemAuthentications",
 *     tags={"SystemAuthentications"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the SystemAuthentications",
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