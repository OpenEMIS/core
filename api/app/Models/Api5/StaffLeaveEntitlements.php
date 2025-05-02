<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffLeaveEntitlements extends Model
{
    use HasFactory;

    public $timestamps = false;

    // ✅ Allow mass assignment
    protected $table = 'staff_leave_entitlements';

    // ✅ Disable Laravel's default timestamps
    protected $fillable = ['id', 'staff_id', 'staff_leave_type_id', 'adjustment', 'modified_user_id', 'modified', 'created_user_id', 'created'];

    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    // ✅ Define the primary key


    // Override getKeyForSaveQuery to handle composite keys

    public static function getValidationRules(): array
    {
        return [
            // Add validation rules here
        ];
    }

    /**
     * @OA\PathItem(
     *     path="/api/v5/staff-leave-entitlements"
     * )
     */
    public function _swaggerPath()
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v5/staff-leave-entitlements",
     *     summary="Get list of StaffLeaveEntitlements",
     *     tags={"StaffLeaveEntitlements"},
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
    @OA\Property(property="staff_id", type="integer", example=null),
    @OA\Property(property="staff_leave_type_id", type="integer", example=null),
    @OA\Property(property="adjustment", type="integer", example=null),
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
    public function _swaggerList()
    {
    }

    /**
     * @OA\Post(
     *     path="/api/v5/staff-leave-entitlements",
     *     summary="Create a new StaffLeaveEntitlements",
     *     tags={"StaffLeaveEntitlements"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
    @OA\Property(property="id", type="integer", example=null),
    @OA\Property(property="staff_id", type="integer", example=null),
    @OA\Property(property="staff_leave_type_id", type="integer", example=null),
    @OA\Property(property="adjustment", type="integer", example=null),
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
    public function _swaggerCreate()
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v5/staff-leave-entitlements/{id}",
     *     summary="Get StaffLeaveEntitlements by ID",
     *     tags={"StaffLeaveEntitlements"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the StaffLeaveEntitlements",
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
    public function _swaggerView()
    {
    }

    /**
     * @OA\Put(
     *     path="/api/v5/staff-leave-entitlements/{id}",
     *     summary="Update StaffLeaveEntitlements",
     *     tags={"StaffLeaveEntitlements"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the StaffLeaveEntitlements",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
    @OA\Property(property="id", type="integer", example=null),
    @OA\Property(property="staff_id", type="integer", example=null),
    @OA\Property(property="staff_leave_type_id", type="integer", example=null),
    @OA\Property(property="adjustment", type="integer", example=null),
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
    public function _swaggerUpdate()
    {
    }

    /**
     * @OA\Delete(
     *     path="/api/v5/staff-leave-entitlements/{id}",
     *     summary="Delete StaffLeaveEntitlements",
     *     tags={"StaffLeaveEntitlements"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the StaffLeaveEntitlements",
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
    public function _swaggerDelete()
    {
    }

    // Override setKeysForSaveQuery to handle composite keys

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
