<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;

class InstitutionBuildings extends Model
{
    use HasFactory;
use InstitutionScope;

    protected $table = 'institution_buildings';

    // ✅ Allow mass assignment
    protected $fillable = ['id', 'code', 'name', 'start_date', 'start_year', 'end_date', 'end_year', 'year_acquired', 'year_disposed', 'area', 'accessibility', 'comment', 'institution_land_id', 'institution_id', 'building_type_id', 'building_status_id', 'infrastructure_ownership_id', 'infrastructure_condition_id', 'previous_institution_building_id', 'modified_user_id', 'modified', 'created_user_id', 'created'];

    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;

    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    // ✅ Define the primary key


     // Override getKeyForSaveQuery to handle composite keys


/**
 * @OA\PathItem(
 *     path="/api/v5/institution-buildings"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/institution-buildings",
 *     summary="Get list of InstitutionBuildings",
 *     tags={"InstitutionBuildings"},
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
                          @OA\Property(property="start_date", type="string", format="date", example=null),
                          @OA\Property(property="start_year", type="integer", example=null),
                          @OA\Property(property="end_date", type="string", format="date", example=null),
                          @OA\Property(property="end_year", type="integer", example=null),
                          @OA\Property(property="year_acquired", type="integer", example=null),
                          @OA\Property(property="year_disposed", type="integer", example=null),
                          @OA\Property(property="area", type="number", example=null),
                          @OA\Property(property="accessibility", type="integer", example=null),
                          @OA\Property(property="comment", type="string", example=null),
                          @OA\Property(property="institution_land_id", type="integer", example=null),
                          @OA\Property(property="institution_id", type="integer", example=null),
                          @OA\Property(property="building_type_id", type="integer", example=null),
                          @OA\Property(property="building_status_id", type="integer", example=null),
                          @OA\Property(property="infrastructure_ownership_id", type="integer", example=null),
                          @OA\Property(property="infrastructure_condition_id", type="integer", example=null),
                          @OA\Property(property="previous_institution_building_id", type="integer", example=null),
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
 *     path="/api/v5/institution-buildings",
 *     summary="Create a new InstitutionBuildings",
 *     tags={"InstitutionBuildings"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="code", type="string", example=null),
                     @OA\Property(property="name", type="string", example=null),
                     @OA\Property(property="start_date", type="string", format="date", example=null),
                     @OA\Property(property="start_year", type="integer", example=null),
                     @OA\Property(property="end_date", type="string", format="date", example=null),
                     @OA\Property(property="end_year", type="integer", example=null),
                     @OA\Property(property="year_acquired", type="integer", example=null),
                     @OA\Property(property="year_disposed", type="integer", example=null),
                     @OA\Property(property="area", type="number", example=null),
                     @OA\Property(property="accessibility", type="integer", example=null),
                     @OA\Property(property="comment", type="string", example=null),
                     @OA\Property(property="institution_land_id", type="integer", example=null),
                     @OA\Property(property="institution_id", type="integer", example=null),
                     @OA\Property(property="building_type_id", type="integer", example=null),
                     @OA\Property(property="building_status_id", type="integer", example=null),
                     @OA\Property(property="infrastructure_ownership_id", type="integer", example=null),
                     @OA\Property(property="infrastructure_condition_id", type="integer", example=null),
                     @OA\Property(property="previous_institution_building_id", type="integer", example=null),
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
 *     path="/api/v5/institution-buildings/{id}",
 *     summary="Get InstitutionBuildings by ID",
 *     tags={"InstitutionBuildings"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the InstitutionBuildings",
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
 *     path="/api/v5/institution-buildings/{id}",
 *     summary="Update InstitutionBuildings",
 *     tags={"InstitutionBuildings"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the InstitutionBuildings",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="code", type="string", example=null),
                     @OA\Property(property="name", type="string", example=null),
                     @OA\Property(property="start_date", type="string", format="date", example=null),
                     @OA\Property(property="start_year", type="integer", example=null),
                     @OA\Property(property="end_date", type="string", format="date", example=null),
                     @OA\Property(property="end_year", type="integer", example=null),
                     @OA\Property(property="year_acquired", type="integer", example=null),
                     @OA\Property(property="year_disposed", type="integer", example=null),
                     @OA\Property(property="area", type="number", example=null),
                     @OA\Property(property="accessibility", type="integer", example=null),
                     @OA\Property(property="comment", type="string", example=null),
                     @OA\Property(property="institution_land_id", type="integer", example=null),
                     @OA\Property(property="institution_id", type="integer", example=null),
                     @OA\Property(property="building_type_id", type="integer", example=null),
                     @OA\Property(property="building_status_id", type="integer", example=null),
                     @OA\Property(property="infrastructure_ownership_id", type="integer", example=null),
                     @OA\Property(property="infrastructure_condition_id", type="integer", example=null),
                     @OA\Property(property="previous_institution_building_id", type="integer", example=null),
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
 *     path="/api/v5/institution-buildings/{id}",
 *     summary="Delete InstitutionBuildings",
 *     tags={"InstitutionBuildings"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the InstitutionBuildings",
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
