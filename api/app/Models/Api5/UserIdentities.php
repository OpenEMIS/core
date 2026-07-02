<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserIdentities extends Model
{
    use HasFactory;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'identity_type_id', 'number', 'issue_date', 'expiry_date', 'issue_location', 'nationality_id', 'comments', 'preferred', 'security_user_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'identity_type_id', 'nationality_id', 'security_user_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    public $timestamps = false;
    protected $table = "user_identities";

    //POCOR-9590: Block updates to `number` or `identity_type_id` on rows whose identity_type
    //is registered as an external-source lookup key. Mirrors IdentitiesTable::beforeSave on the
    //CakePHP side. Returning false from `updating` aborts the save silently — the attacker
    //sees a successful 200/201 from CrudApiController but the row stays untouched, matching
    //the "no fingerprinting" policy in feedback_silent_security_rejections.md.
    protected static function booted(): void
    {
        static::updating(function (self $row) {
            if (!$row->isDirty('number') && !$row->isDirty('identity_type_id')) {
                return;
            }
            $typeId = (int) $row->getOriginal('identity_type_id');
            if (!self::isExternalLookupIdentityType($typeId)) {
                return;
            }
            Log::warning('POCOR-9590: v5 blocked update to external-lookup identity row', [
                'row_id'  => $row->id,
                'type_id' => $typeId,
                'fields'  => array_keys($row->getDirty()),
            ]);
            return false;
        });
    }

    //POCOR-9590: true when this identity_type_id is the lookup key for any external data
    //source. Kept on the model so v4/v5 read the same source of truth.
    public static function isExternalLookupIdentityType(int $identityTypeId): bool
    {
        if ($identityTypeId <= 0) {
            return false;
        }
        return DB::table('external_data_source_attributes')
            ->where('attribute_field', 'identity_type_id')
            ->where('value', (string) $identityTypeId)
            ->exists();
    }


/**
 * @OA\PathItem(
 *     path="/api/v5/user-identities"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/user-identities",
 *     summary="Get list of UserIdentities",
 *     tags={"UserIdentities"},
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
                          @OA\Property(property="identity_type_id", type="integer", example=null),
                          @OA\Property(property="number", type="string", example=null),
                          @OA\Property(property="issue_date", type="string", format="date", example=null),
                          @OA\Property(property="expiry_date", type="string", format="date", example=null),
                          @OA\Property(property="issue_location", type="string", example=null),
                          @OA\Property(property="nationality_id", type="integer", example=null),
                          @OA\Property(property="comments", type="string", example=null),
                          @OA\Property(property="preferred", type="integer", example=null),
                          @OA\Property(property="security_user_id", type="integer", example=null),
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
 *     path="/api/v5/user-identities",
 *     summary="Create a new UserIdentities",
 *     tags={"UserIdentities"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="identity_type_id", type="integer", example=null),
                     @OA\Property(property="number", type="string", example=null),
                     @OA\Property(property="issue_date", type="string", format="date", example=null),
                     @OA\Property(property="expiry_date", type="string", format="date", example=null),
                     @OA\Property(property="issue_location", type="string", example=null),
                     @OA\Property(property="nationality_id", type="integer", example=null),
                     @OA\Property(property="comments", type="string", example=null),
                     @OA\Property(property="preferred", type="integer", example=null),
                     @OA\Property(property="security_user_id", type="integer", example=null),
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
 *     path="/api/v5/user-identities/{id}",
 *     summary="Get UserIdentities by ID",
 *     tags={"UserIdentities"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the UserIdentities",
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
 *     path="/api/v5/user-identities/{id}",
 *     summary="Update UserIdentities",
 *     tags={"UserIdentities"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the UserIdentities",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="identity_type_id", type="integer", example=null),
                     @OA\Property(property="number", type="string", example=null),
                     @OA\Property(property="issue_date", type="string", format="date", example=null),
                     @OA\Property(property="expiry_date", type="string", format="date", example=null),
                     @OA\Property(property="issue_location", type="string", example=null),
                     @OA\Property(property="nationality_id", type="integer", example=null),
                     @OA\Property(property="comments", type="string", example=null),
                     @OA\Property(property="preferred", type="integer", example=null),
                     @OA\Property(property="security_user_id", type="integer", example=null),
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
 *     path="/api/v5/user-identities/{id}",
 *     summary="Delete UserIdentities",
 *     tags={"UserIdentities"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the UserIdentities",
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
    public function user()
    {
        return $this->belongsTo(SecurityUsers::class, 'security_user_id', 'id');
    }
}
