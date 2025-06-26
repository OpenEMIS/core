<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\NumericId;

class StaffPositionTitles extends Model
{
    use HasFactory;
    use NumericId;

    protected $table = 'staff_position_titles';

    // ✅ Allow mass assignment
    protected $fillable = ['id', 'name', 'type', 'staff_position_categories_id', 'security_role_id', 'staff_leave_policy_id', 'file_name', 'file_content', 'order', 'visible', 'editable', 'default', 'international_code', 'national_code', 'modified_user_id', 'modified', 'created_user_id', 'created'];

    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;

    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    // ✅ Define the primary key

    protected $primaryKey = 'id';
    public $incrementing = false;
    public static function getNextId()
    {
        return \DB::transaction(function () {
            $maxId = self::max('id');
            return (int) $maxId + 1;
        });
    }

    protected static function boot(): void
    {
        parent::boot();
        self::bootNumericId();
    }

     // Override getKeyForSaveQuery to handle composite keys


/**
 * @OA\PathItem(
 *     path="/api/v5/staff-position-titles"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/staff-position-titles",
 *     summary="Get list of StaffPositionTitles",
 *     tags={"StaffPositionTitles"},
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
                          @OA\Property(property="name", type="string", example=null),
                          @OA\Property(property="type", type="integer", example=null),
                          @OA\Property(property="staff_position_categories_id", type="integer", example=null),
                          @OA\Property(property="security_role_id", type="integer", example=null),
                          @OA\Property(property="staff_leave_policy_id", type="integer", example=null),
                          @OA\Property(property="file_name", type="string", example=null),
                          @OA\Property(property="file_content", type="string", example=null),
                          @OA\Property(property="order", type="integer", example=null),
                          @OA\Property(property="visible", type="integer", example=null),
                          @OA\Property(property="editable", type="integer", example=null),
                          @OA\Property(property="default", type="integer", example=null),
                          @OA\Property(property="international_code", type="string", example=null),
                          @OA\Property(property="national_code", type="string", example=null),
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
 *     path="/api/v5/staff-position-titles",
 *     summary="Create a new StaffPositionTitles",
 *     tags={"StaffPositionTitles"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="name", type="string", example=null),
                     @OA\Property(property="type", type="integer", example=null),
                     @OA\Property(property="staff_position_categories_id", type="integer", example=null),
                     @OA\Property(property="security_role_id", type="integer", example=null),
                     @OA\Property(property="staff_leave_policy_id", type="integer", example=null),
                     @OA\Property(property="file_name", type="string", example=null),
                     @OA\Property(property="file_content", type="string", example=null),
                     @OA\Property(property="order", type="integer", example=null),
                     @OA\Property(property="visible", type="integer", example=null),
                     @OA\Property(property="editable", type="integer", example=null),
                     @OA\Property(property="default", type="integer", example=null),
                     @OA\Property(property="international_code", type="string", example=null),
                     @OA\Property(property="national_code", type="string", example=null),
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
 *     path="/api/v5/staff-position-titles/{id}",
 *     summary="Get StaffPositionTitles by ID",
 *     tags={"StaffPositionTitles"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the StaffPositionTitles",
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
 *     path="/api/v5/staff-position-titles/{id}",
 *     summary="Update StaffPositionTitles",
 *     tags={"StaffPositionTitles"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the StaffPositionTitles",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="name", type="string", example=null),
                     @OA\Property(property="type", type="integer", example=null),
                     @OA\Property(property="staff_position_categories_id", type="integer", example=null),
                     @OA\Property(property="security_role_id", type="integer", example=null),
                     @OA\Property(property="staff_leave_policy_id", type="integer", example=null),
                     @OA\Property(property="file_name", type="string", example=null),
                     @OA\Property(property="file_content", type="string", example=null),
                     @OA\Property(property="order", type="integer", example=null),
                     @OA\Property(property="visible", type="integer", example=null),
                     @OA\Property(property="editable", type="integer", example=null),
                     @OA\Property(property="default", type="integer", example=null),
                     @OA\Property(property="international_code", type="string", example=null),
                     @OA\Property(property="national_code", type="string", example=null),
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
 *     path="/api/v5/staff-position-titles/{id}",
 *     summary="Delete StaffPositionTitles",
 *     tags={"StaffPositionTitles"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the StaffPositionTitles",
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
