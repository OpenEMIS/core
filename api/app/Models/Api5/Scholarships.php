<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Scholarships extends Model
{
    use HasFactory;

    protected $table = 'scholarships';

    // ✅ Allow mass assignment
    protected $fillable = ['id', 'code', 'name', 'description', 'application_open_date', 'application_close_date', 'maximum_award_amount', 'total_amount', 'duration', 'bonded_organisation', 'bond', 'requirements', 'instructions', 'scholarship_financial_assistance_type_id', 'scholarship_financial_assistance_id', 'scholarship_funding_source_id', 'academic_period_id', 'modified_user_id', 'modified', 'created_user_id', 'created'];

    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;

    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    // ✅ Define the primary key


     // Override getKeyForSaveQuery to handle composite keys


/**
 * @OA\PathItem(
 *     path="/api/v5/scholarships"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/scholarships",
 *     summary="Get list of Scholarships",
 *     tags={"Scholarships"},
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
                          @OA\Property(property="code", type="string", example=null),
                          @OA\Property(property="name", type="string", example=null),
                          @OA\Property(property="description", type="string", example=null),
                          @OA\Property(property="application_open_date", type="string", format="date", example=null),
                          @OA\Property(property="application_close_date", type="string", format="date", example=null),
                          @OA\Property(property="maximum_award_amount", type="number", example=null),
                          @OA\Property(property="total_amount", type="number", example=null),
                          @OA\Property(property="duration", type="integer", example=null),
                          @OA\Property(property="bonded_organisation", type="string", example=null),
                          @OA\Property(property="bond", type="integer", example=null),
                          @OA\Property(property="requirements", type="string", example=null),
                          @OA\Property(property="instructions", type="string", example=null),
                          @OA\Property(property="scholarship_financial_assistance_type_id", type="integer", example=null),
                          @OA\Property(property="scholarship_financial_assistance_id", type="integer", example=null),
                          @OA\Property(property="scholarship_funding_source_id", type="integer", example=null),
                          @OA\Property(property="academic_period_id", type="integer", example=null),
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
 *     path="/api/v5/scholarships",
 *     summary="Create a new Scholarships",
 *     tags={"Scholarships"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="code", type="string", example=null),
                     @OA\Property(property="name", type="string", example=null),
                     @OA\Property(property="description", type="string", example=null),
                     @OA\Property(property="application_open_date", type="string", format="date", example=null),
                     @OA\Property(property="application_close_date", type="string", format="date", example=null),
                     @OA\Property(property="maximum_award_amount", type="number", example=null),
                     @OA\Property(property="total_amount", type="number", example=null),
                     @OA\Property(property="duration", type="integer", example=null),
                     @OA\Property(property="bonded_organisation", type="string", example=null),
                     @OA\Property(property="bond", type="integer", example=null),
                     @OA\Property(property="requirements", type="string", example=null),
                     @OA\Property(property="instructions", type="string", example=null),
                     @OA\Property(property="scholarship_financial_assistance_type_id", type="integer", example=null),
                     @OA\Property(property="scholarship_financial_assistance_id", type="integer", example=null),
                     @OA\Property(property="scholarship_funding_source_id", type="integer", example=null),
                     @OA\Property(property="academic_period_id", type="integer", example=null),
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
 *     path="/api/v5/scholarships/{id}",
 *     summary="Get Scholarships by ID",
 *     tags={"Scholarships"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the Scholarships",
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
 *     path="/api/v5/scholarships/{id}",
 *     summary="Update Scholarships",
 *     tags={"Scholarships"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the Scholarships",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="code", type="string", example=null),
                     @OA\Property(property="name", type="string", example=null),
                     @OA\Property(property="description", type="string", example=null),
                     @OA\Property(property="application_open_date", type="string", format="date", example=null),
                     @OA\Property(property="application_close_date", type="string", format="date", example=null),
                     @OA\Property(property="maximum_award_amount", type="number", example=null),
                     @OA\Property(property="total_amount", type="number", example=null),
                     @OA\Property(property="duration", type="integer", example=null),
                     @OA\Property(property="bonded_organisation", type="string", example=null),
                     @OA\Property(property="bond", type="integer", example=null),
                     @OA\Property(property="requirements", type="string", example=null),
                     @OA\Property(property="instructions", type="string", example=null),
                     @OA\Property(property="scholarship_financial_assistance_type_id", type="integer", example=null),
                     @OA\Property(property="scholarship_financial_assistance_id", type="integer", example=null),
                     @OA\Property(property="scholarship_funding_source_id", type="integer", example=null),
                     @OA\Property(property="academic_period_id", type="integer", example=null),
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
 *     path="/api/v5/scholarships/{id}",
 *     summary="Delete Scholarships",
 *     tags={"Scholarships"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the Scholarships",
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
