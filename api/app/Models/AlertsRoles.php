<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlertsRoles extends Model
{
    use HasFactory;

    protected $table = 'alerts_roles';

    // ✅ Allow mass assignment
    protected $fillable = ['id', 'alert_rule_id', 'security_role_id'];

    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;

    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    // ✅ Define the primary key
    protected $primaryKey = ['alert_rule_id', 'security_role_id'];
    public $incrementing = false;

     // Override getKeyForSaveQuery to handle composite keys





/**
 * @OA\PathItem(
 *     path="/api/v5/alerts-roles"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/alerts-roles",
 *     summary="Get list of AlertsRoles",
 *     tags={"AlertsRoles"},
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
                          @OA\Property(property="alert_rule_id", type="integer", example=null),
                          @OA\Property(property="security_role_id", type="integer", example=null)
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
     *     path="/api/v5/alerts-roles/alert_rule_id/{alert_rule_id}/security_role_id/{security_role_id}",
     *     summary="Get AlertsRoles record by composite key",
     *     tags={"AlertsRoles"},
     *     @OA\Parameter(
     *         name="alert_rule_id",
     *         in="path",
     *         required=true,
     *         description="ID of the alert rule",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="security_role_id",
     *         in="path",
     *         required=true,
     *         description="ID of the security role",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="AlertsRoles record found"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="AlertsRoles record not found"
     *     )
     * )
     */
    public function _swaggerView() {}


    /**
     * @OA\Post(
     *     path="/api/v5/alerts-roles",
     *     summary="Create a new AlertsRoles record",
     *     tags={"AlertsRoles"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="alert_rule_id", type="integer", example=10),
     *             @OA\Property(property="security_role_id", type="integer", example=20)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="AlertsRoles record created successfully"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid data provided"
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
     *     path="/api/v5/alerts-roles/alert_rule_id/{alert_rule_id}/security_role_id/{security_role_id}",
     *     summary="Update an AlertsRoles record by composite key",
     *     tags={"AlertsRoles"},
     *     @OA\Parameter(
     *         name="alert_rule_id",
     *         in="path",
     *         required=true,
     *         description="ID of the alert rule",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="security_role_id",
     *         in="path",
     *         required=true,
     *         description="ID of the security role",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="alert_rule_id", type="integer", example=10),
     *             @OA\Property(property="security_role_id", type="integer", example=20)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="AlertsRoles record updated successfully"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid data provided"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="AlertsRoles record not found"
     *     )
     * )
     */
    public function _swaggerUpdate() {}

    /**
     * @OA\Delete(
     *     path="/api/v5/alerts-roles/alert_rule_id/{alert_rule_id}/security_role_id/{security_role_id}",
     *     summary="Delete an AlertsRoles record by composite key",
     *     tags={"AlertsRoles"},
     *     @OA\Parameter(
     *         name="alert_rule_id",
     *         in="path",
     *         required=true,
     *         description="ID of the alert rule",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="security_role_id",
     *         in="path",
     *         required=true,
     *         description="ID of the security role",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="AlertsRoles record deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="AlertsRoles record not found"
     *     )
     * )
     */
    public function _swaggerDelete() {}

    protected function getKeyForSaveQuery()
    {
        $query = $this->newQueryWithoutScopes();
        $keyName = $this->getKeyName();
        if(!is_array($keyName)){
            $keyName = [$keyName];
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
