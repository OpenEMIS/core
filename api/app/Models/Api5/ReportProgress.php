<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UuidId;

class ReportProgress extends Model
{
    use HasFactory;
use UuidId;

    protected $table = 'report_progress';

    // ✅ Allow mass assignment
    protected $fillable = ['id', 'name', 'module', 'params', 'sql', 'expiry_date', 'file_path', 'current_records', 'total_records', 'pid', 'status', 'error_message', 'modified_user_id', 'modified', 'created_user_id', 'created'];

    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;

    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    // ✅ Define the primary key

    public $incrementing = false;

    public $casts = [
        'id' => 'string',
    ];

    protected static function boot()
    {
        parent::boot();
        self::bootUuidId();
    }


     // Override getKeyForSaveQuery to handle composite keys


/**
 * @OA\PathItem(
 *     path="/api/v5/report-progress"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/report-progress",
 *     summary="Get list of ReportProgress",
 *     tags={"ReportProgress"},
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
                          @OA\Property(property="id", type="string", example=null),
                          @OA\Property(property="name", type="string", example=null),
                          @OA\Property(property="module", type="string", example=null),
                          @OA\Property(property="params", type="string", example=null),
                          @OA\Property(property="sql", type="string", example=null),
                          @OA\Property(property="expiry_date", type="string", format="date-time", example=null),
                          @OA\Property(property="file_path", type="string", example=null),
                          @OA\Property(property="current_records", type="integer", example=null),
                          @OA\Property(property="total_records", type="integer", example=null),
                          @OA\Property(property="pid", type="integer", example=null),
                          @OA\Property(property="status", type="integer", example=null),
                          @OA\Property(property="error_message", type="string", example=null),
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
 *     path="/api/v5/report-progress",
 *     summary="Create a new ReportProgress",
 *     tags={"ReportProgress"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="string", example=null),
                     @OA\Property(property="name", type="string", example=null),
                     @OA\Property(property="module", type="string", example=null),
                     @OA\Property(property="params", type="string", example=null),
                     @OA\Property(property="sql", type="string", example=null),
                     @OA\Property(property="expiry_date", type="string", format="date-time", example=null),
                     @OA\Property(property="file_path", type="string", example=null),
                     @OA\Property(property="current_records", type="integer", example=null),
                     @OA\Property(property="total_records", type="integer", example=null),
                     @OA\Property(property="pid", type="integer", example=null),
                     @OA\Property(property="status", type="integer", example=null),
                     @OA\Property(property="error_message", type="string", example=null),
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
 *     path="/api/v5/report-progress/{id}",
 *     summary="Get ReportProgress by ID",
 *     tags={"ReportProgress"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the ReportProgress",
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
 *     path="/api/v5/report-progress/{id}",
 *     summary="Update ReportProgress",
 *     tags={"ReportProgress"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the ReportProgress",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="string", example=null),
                     @OA\Property(property="name", type="string", example=null),
                     @OA\Property(property="module", type="string", example=null),
                     @OA\Property(property="params", type="string", example=null),
                     @OA\Property(property="sql", type="string", example=null),
                     @OA\Property(property="expiry_date", type="string", format="date-time", example=null),
                     @OA\Property(property="file_path", type="string", example=null),
                     @OA\Property(property="current_records", type="integer", example=null),
                     @OA\Property(property="total_records", type="integer", example=null),
                     @OA\Property(property="pid", type="integer", example=null),
                     @OA\Property(property="status", type="integer", example=null),
                     @OA\Property(property="error_message", type="string", example=null),
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
 *     path="/api/v5/report-progress/{id}",
 *     summary="Delete ReportProgress",
 *     tags={"ReportProgress"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the ReportProgress",
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


}
