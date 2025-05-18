<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScholarshipRecipients extends Model
{
    use HasFactory;

    protected $table = 'scholarship_recipients';

    // ✅ Allow mass assignment
    protected $fillable = ['recipient_id', 'scholarship_id', 'approved_amount', 'scholarship_recipient_activity_status_id', 'modified_user_id', 'modified', 'created_user_id', 'created'];

    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;

    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    // ✅ Define the primary key
    protected $primaryKey = ['recipient_id', 'scholarship_id'];
    public $incrementing = false;

     // Override getKeyForSaveQuery to handle composite keys


/**
 * @OA\PathItem(
 *     path="/api/v5/scholarship-recipients"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/scholarship-recipients",
 *     summary="Get list of ScholarshipRecipients",
 *     tags={"ScholarshipRecipients"},
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
                          @OA\Property(property="recipient_id", type="integer", example=null),
                          @OA\Property(property="scholarship_id", type="integer", example=null),
                          @OA\Property(property="approved_amount", type="number", example=null),
                          @OA\Property(property="scholarship_recipient_activity_status_id", type="integer", example=null),
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
 *     path="/api/v5/scholarship-recipients",
 *     summary="Create a new ScholarshipRecipients",
 *     tags={"ScholarshipRecipients"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="recipient_id", type="integer", example=null),
                     @OA\Property(property="scholarship_id", type="integer", example=null),
                     @OA\Property(property="approved_amount", type="number", example=null),
                     @OA\Property(property="scholarship_recipient_activity_status_id", type="integer", example=null),
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
 *     path="/api/v5/scholarship-recipients/recipient_id/{recipient_id}/scholarship_id/{scholarship_id}",
 *     summary="Get ScholarshipRecipients record by composite key",
 *     tags={"ScholarshipRecipients"},
 *     @OA\Parameter(
 *         name="recipient_id",
 *         in="path",
 *         required=true,
 *         description="recipient_id",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="scholarship_id",
 *         in="path",
 *         required=true,
 *         description="scholarship_id",
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
 *     path="/api/v5/scholarship-recipients/recipient_id/{recipient_id}/scholarship_id/{scholarship_id}",
 *     summary="Update ScholarshipRecipients record by composite key",
 *     tags={"ScholarshipRecipients"},
 *     @OA\Parameter(
 *         name="recipient_id",
 *         in="path",
 *         required=true,
 *         description="recipient_id",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="scholarship_id",
 *         in="path",
 *         required=true,
 *         description="scholarship_id",
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
 *     path="/api/v5/scholarship-recipients/recipient_id/{recipient_id}/scholarship_id/{scholarship_id}",
 *     summary="Delete ScholarshipRecipients record by composite key",
 *     tags={"ScholarshipRecipients"},
 *     @OA\Parameter(
 *         name="recipient_id",
 *         in="path",
 *         required=true,
 *         description="recipient_id",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="scholarship_id",
 *         in="path",
 *         required=true,
 *         description="scholarship_id",
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
