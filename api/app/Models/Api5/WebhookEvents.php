<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookEvents extends Model
{
    use HasFactory;

    // ✅ Allow mass assignment
    public $timestamps = false;
    // ✅ Disable Laravel's default timestamps
    public $incrementing = false;
    protected $fillable = ['webhook_id', 'event_key'];
    protected $table = 'webhook_events';

    protected $primaryKey = ["webhook_id", "event_key"];


/**
 * @OA\PathItem(
 *     path="/api/v5/webhook-events"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/webhook-events",
 *     summary="Get list of WebhookEvents",
 *     tags={"WebhookEvents"},
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
                          @OA\Property(property="webhook_id", type="integer", example=null),
                          @OA\Property(property="event_key", type="string", example=null)
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
 *     path="/api/v5/webhook-events",
 *     summary="Create a new WebhookEvents",
 *     tags={"WebhookEvents"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="webhook_id", type="integer", example=null),
                     @OA\Property(property="event_key", type="string", example=null)
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
 *     path="/api/v5/webhook-events/webhook_id/{webhook_id}/event_key/{event_key}",
 *     summary="Get WebhookEvents record by composite key",
 *     tags={"WebhookEvents"},
 *     @OA\Parameter(
 *         name="webhook_id",
 *         in="path",
 *         required=true,
 *         description="webhook_id",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="event_key",
 *         in="path",
 *         required=true,
 *         description="event_key",
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
 *     path="/api/v5/webhook-events/webhook_id/{webhook_id}/event_key/{event_key}",
 *     summary="Update WebhookEvents record by composite key",
 *     tags={"WebhookEvents"},
 *     @OA\Parameter(
 *         name="webhook_id",
 *         in="path",
 *         required=true,
 *         description="webhook_id",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="event_key",
 *         in="path",
 *         required=true,
 *         description="event_key",
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
 *     path="/api/v5/webhook-events/webhook_id/{webhook_id}/event_key/{event_key}",
 *     summary="Delete WebhookEvents record by composite key",
 *     tags={"WebhookEvents"},
 *     @OA\Parameter(
 *         name="webhook_id",
 *         in="path",
 *         required=true,
 *         description="webhook_id",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="event_key",
 *         in="path",
 *         required=true,
 *         description="event_key",
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
