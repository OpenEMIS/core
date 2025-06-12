<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UuidId;

class CustomFieldValues extends Model
{
    use HasFactory;
use UuidId;

    protected $table = 'custom_field_values';

    // ✅ Allow mass assignment
    protected $fillable = ['id', 'text_value', 'number_value', 'decimal_value', 'textarea_value', 'date_value', 'time_value', 'file', 'custom_field_id', 'custom_record_id', 'modified_user_id', 'modified', 'created_user_id', 'created'];

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
 *     path="/api/v5/custom-field-values"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/custom-field-values",
 *     summary="Get list of CustomFieldValues",
 *     tags={"CustomFieldValues"},
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
                          @OA\Property(property="text_value", type="string", example=null),
                          @OA\Property(property="number_value", type="integer", example=null),
                          @OA\Property(property="decimal_value", type="string", example=null),
                          @OA\Property(property="textarea_value", type="string", example=null),
                          @OA\Property(property="date_value", type="string", format="date", example=null),
                          @OA\Property(property="time_value", type="string", example=null),
                          @OA\Property(property="file", type="string", example=null),
                          @OA\Property(property="custom_field_id", type="integer", example=null),
                          @OA\Property(property="custom_record_id", type="integer", example=null),
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
 *     path="/api/v5/custom-field-values",
 *     summary="Create a new CustomFieldValues",
 *     tags={"CustomFieldValues"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="string", example=null),
                     @OA\Property(property="text_value", type="string", example=null),
                     @OA\Property(property="number_value", type="integer", example=null),
                     @OA\Property(property="decimal_value", type="string", example=null),
                     @OA\Property(property="textarea_value", type="string", example=null),
                     @OA\Property(property="date_value", type="string", format="date", example=null),
                     @OA\Property(property="time_value", type="string", example=null),
                     @OA\Property(property="file", type="string", example=null),
                     @OA\Property(property="custom_field_id", type="integer", example=null),
                     @OA\Property(property="custom_record_id", type="integer", example=null),
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
 *     path="/api/v5/custom-field-values/{id}",
 *     summary="Get CustomFieldValues by ID",
 *     tags={"CustomFieldValues"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the CustomFieldValues",
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
 *     path="/api/v5/custom-field-values/{id}",
 *     summary="Update CustomFieldValues",
 *     tags={"CustomFieldValues"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the CustomFieldValues",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="string", example=null),
                     @OA\Property(property="text_value", type="string", example=null),
                     @OA\Property(property="number_value", type="integer", example=null),
                     @OA\Property(property="decimal_value", type="string", example=null),
                     @OA\Property(property="textarea_value", type="string", example=null),
                     @OA\Property(property="date_value", type="string", format="date", example=null),
                     @OA\Property(property="time_value", type="string", example=null),
                     @OA\Property(property="file", type="string", example=null),
                     @OA\Property(property="custom_field_id", type="integer", example=null),
                     @OA\Property(property="custom_record_id", type="integer", example=null),
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
 *     path="/api/v5/custom-field-values/{id}",
 *     summary="Delete CustomFieldValues",
 *     tags={"CustomFieldValues"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the CustomFieldValues",
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
