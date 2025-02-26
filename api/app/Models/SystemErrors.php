<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemErrors extends Model
{
    use HasFactory;

    protected $table = 'system_errors';

    // ✅ Allow mass assignment
    protected $fillable = ['id', 'code', 'error_message', 'request_method', 'request_url', 'referrer_url', 'client_ip', 'client_browser', 'triggered_from', 'stack_trace', 'server_info', 'created_user_id', 'created'];

    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;

    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    // ✅ Define the primary key
    
    

     // Override getKeyForSaveQuery to handle composite keys
/**
 * @OA\PathItem(
 *     path="/api/v5/system-errors"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/system-errors",
 *     summary="Get list of SystemErrors",
 *     tags={"SystemErrors"},
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
                          @OA\Property(property="code", type="string", example=null),
                          @OA\Property(property="error_message", type="string", example=null),
                          @OA\Property(property="request_method", type="string", example=null),
                          @OA\Property(property="request_url", type="string", example=null),
                          @OA\Property(property="referrer_url", type="string", example=null),
                          @OA\Property(property="client_ip", type="string", example=null),
                          @OA\Property(property="client_browser", type="string", example=null),
                          @OA\Property(property="triggered_from", type="string", example=null),
                          @OA\Property(property="stack_trace", type="string", example=null),
                          @OA\Property(property="server_info", type="string", example=null),
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
 *     path="/api/v5/system-errors/{id}",
 *     summary="Get SystemErrors by ID",
 *     tags={"SystemErrors"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the SystemErrors",
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
 *     path="/api/v5/system-errors",
 *     summary="Create a new SystemErrors",
 *     tags={"SystemErrors"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="string", example=null),
                     @OA\Property(property="code", type="string", example=null),
                     @OA\Property(property="error_message", type="string", example=null),
                     @OA\Property(property="request_method", type="string", example=null),
                     @OA\Property(property="request_url", type="string", example=null),
                     @OA\Property(property="referrer_url", type="string", example=null),
                     @OA\Property(property="client_ip", type="string", example=null),
                     @OA\Property(property="client_browser", type="string", example=null),
                     @OA\Property(property="triggered_from", type="string", example=null),
                     @OA\Property(property="stack_trace", type="string", example=null),
                     @OA\Property(property="server_info", type="string", example=null),
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
 *     path="/api/v5/system-errors/{id}",
 *     summary="Update SystemErrors",
 *     tags={"SystemErrors"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the SystemErrors",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="string", example=null),
                     @OA\Property(property="code", type="string", example=null),
                     @OA\Property(property="error_message", type="string", example=null),
                     @OA\Property(property="request_method", type="string", example=null),
                     @OA\Property(property="request_url", type="string", example=null),
                     @OA\Property(property="referrer_url", type="string", example=null),
                     @OA\Property(property="client_ip", type="string", example=null),
                     @OA\Property(property="client_browser", type="string", example=null),
                     @OA\Property(property="triggered_from", type="string", example=null),
                     @OA\Property(property="stack_trace", type="string", example=null),
                     @OA\Property(property="server_info", type="string", example=null),
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
 *     path="/api/v5/system-errors/{id}",
 *     summary="Delete SystemErrors",
 *     tags={"SystemErrors"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the SystemErrors",
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