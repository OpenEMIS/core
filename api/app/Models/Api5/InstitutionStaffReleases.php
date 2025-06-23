<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionStaffReleases extends Model
{
    use HasFactory;

    protected $table = 'institution_staff_releases';

    // ✅ Allow mass assignment
    protected $fillable = ['id', 'previous_FTE', 'previous_start_date', 'previous_end_date', 'new_FTE', 'new_start_date', 'new_end_date', 'comment', 'all_visible', 'modified_user_id', 'modified', 'created_user_id', 'created', 'status_id', 'assignee_id', 'staff_id', 'previous_institution_id', 'previous_institution_staff_id', 'previous_staff_type_id', 'new_institution_id', 'new_institution_position_id', 'new_staff_type_id'];

    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;

    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    // ✅ Define the primary key


     // Override getKeyForSaveQuery to handle composite keys


/**
 * @OA\PathItem(
 *     path="/api/v5/institution-staff-releases"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/institution-staff-releases",
 *     summary="Get list of InstitutionStaffReleases",
 *     tags={"InstitutionStaffReleases"},
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
                          @OA\Property(property="previous_FTE", type="number", example=null),
                          @OA\Property(property="previous_start_date", type="string", format="date", example=null),
                          @OA\Property(property="previous_end_date", type="string", format="date", example=null),
                          @OA\Property(property="new_FTE", type="number", example=null),
                          @OA\Property(property="new_start_date", type="string", format="date", example=null),
                          @OA\Property(property="new_end_date", type="string", format="date", example=null),
                          @OA\Property(property="comment", type="string", example=null),
                          @OA\Property(property="all_visible", type="integer", example=null),
                          @OA\Property(property="modified_user_id", type="integer", example=null),
                          @OA\Property(property="modified", type="string", format="date-time", example=null),
                          @OA\Property(property="created_user_id", type="integer", example=null),
                          @OA\Property(property="created", type="string", format="date-time", example=null),
                          @OA\Property(property="status_id", type="integer", example=null),
                          @OA\Property(property="assignee_id", type="integer", example=null),
                          @OA\Property(property="staff_id", type="integer", example=null),
                          @OA\Property(property="previous_institution_id", type="integer", example=null),
                          @OA\Property(property="previous_institution_staff_id", type="integer", example=null),
                          @OA\Property(property="previous_staff_type_id", type="integer", example=null),
                          @OA\Property(property="new_institution_id", type="integer", example=null),
                          @OA\Property(property="new_institution_position_id", type="integer", example=null),
                          @OA\Property(property="new_staff_type_id", type="integer", example=null)
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
 *     path="/api/v5/institution-staff-releases",
 *     summary="Create a new InstitutionStaffReleases",
 *     tags={"InstitutionStaffReleases"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="previous_FTE", type="number", example=null),
                     @OA\Property(property="previous_start_date", type="string", format="date", example=null),
                     @OA\Property(property="previous_end_date", type="string", format="date", example=null),
                     @OA\Property(property="new_FTE", type="number", example=null),
                     @OA\Property(property="new_start_date", type="string", format="date", example=null),
                     @OA\Property(property="new_end_date", type="string", format="date", example=null),
                     @OA\Property(property="comment", type="string", example=null),
                     @OA\Property(property="all_visible", type="integer", example=null),
                     @OA\Property(property="modified_user_id", type="integer", example=null),
                     @OA\Property(property="modified", type="string", format="date-time", example=null),
                     @OA\Property(property="created_user_id", type="integer", example=null),
                     @OA\Property(property="created", type="string", format="date-time", example=null),
                     @OA\Property(property="status_id", type="integer", example=null),
                     @OA\Property(property="assignee_id", type="integer", example=null),
                     @OA\Property(property="staff_id", type="integer", example=null),
                     @OA\Property(property="previous_institution_id", type="integer", example=null),
                     @OA\Property(property="previous_institution_staff_id", type="integer", example=null),
                     @OA\Property(property="previous_staff_type_id", type="integer", example=null),
                     @OA\Property(property="new_institution_id", type="integer", example=null),
                     @OA\Property(property="new_institution_position_id", type="integer", example=null),
                     @OA\Property(property="new_staff_type_id", type="integer", example=null)
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
 *     path="/api/v5/institution-staff-releases/{id}",
 *     summary="Get InstitutionStaffReleases by ID",
 *     tags={"InstitutionStaffReleases"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the InstitutionStaffReleases",
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
 *     path="/api/v5/institution-staff-releases/{id}",
 *     summary="Update InstitutionStaffReleases",
 *     tags={"InstitutionStaffReleases"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the InstitutionStaffReleases",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="previous_FTE", type="number", example=null),
                     @OA\Property(property="previous_start_date", type="string", format="date", example=null),
                     @OA\Property(property="previous_end_date", type="string", format="date", example=null),
                     @OA\Property(property="new_FTE", type="number", example=null),
                     @OA\Property(property="new_start_date", type="string", format="date", example=null),
                     @OA\Property(property="new_end_date", type="string", format="date", example=null),
                     @OA\Property(property="comment", type="string", example=null),
                     @OA\Property(property="all_visible", type="integer", example=null),
                     @OA\Property(property="modified_user_id", type="integer", example=null),
                     @OA\Property(property="modified", type="string", format="date-time", example=null),
                     @OA\Property(property="created_user_id", type="integer", example=null),
                     @OA\Property(property="created", type="string", format="date-time", example=null),
                     @OA\Property(property="status_id", type="integer", example=null),
                     @OA\Property(property="assignee_id", type="integer", example=null),
                     @OA\Property(property="staff_id", type="integer", example=null),
                     @OA\Property(property="previous_institution_id", type="integer", example=null),
                     @OA\Property(property="previous_institution_staff_id", type="integer", example=null),
                     @OA\Property(property="previous_staff_type_id", type="integer", example=null),
                     @OA\Property(property="new_institution_id", type="integer", example=null),
                     @OA\Property(property="new_institution_position_id", type="integer", example=null),
                     @OA\Property(property="new_staff_type_id", type="integer", example=null)
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
 *     path="/api/v5/institution-staff-releases/{id}",
 *     summary="Delete InstitutionStaffReleases",
 *     tags={"InstitutionStaffReleases"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the InstitutionStaffReleases",
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
