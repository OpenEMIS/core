<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionStaffTransfers extends Model
{
    use HasFactory;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'staff_id', 'new_institution_id', 'previous_institution_id', 'status_id', 'assignee_id', 'new_institution_position_id', 'new_staff_type_id', 'new_FTE', 'new_start_date', 'new_end_date', 'previous_institution_staff_id', 'previous_staff_type_id', 'previous_FTE', 'previous_end_date', 'previous_effective_date', 'comment', 'transfer_type', 'all_visible', 'is_homeroom', 'modified_user_id', 'modified', 'created_user_id', 'created', 'staff_id', 'new_institution_id', 'previous_institution_id', 'status_id', 'assignee_id', 'new_institution_position_id', 'new_staff_type_id', 'previous_institution_staff_id', 'previous_staff_type_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    public $timestamps = false;
    protected $table = "institution_staff_transfers";


/**
 * @OA\PathItem(
 *     path="/api/v5/institution-staff-transfers"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/institution-staff-transfers",
 *     summary="Get list of InstitutionStaffTransfers",
 *     tags={"InstitutionStaffTransfers"},
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
                          @OA\Property(property="new_institution_id", type="integer", example=null),
                          @OA\Property(property="previous_institution_id", type="integer", example=null),
                          @OA\Property(property="status_id", type="integer", example=null),
                          @OA\Property(property="assignee_id", type="integer", example=null),
                          @OA\Property(property="new_institution_position_id", type="integer", example=null),
                          @OA\Property(property="new_staff_type_id", type="integer", example=null),
                          @OA\Property(property="new_FTE", type="number", example=null),
                          @OA\Property(property="new_start_date", type="string", format="date", example=null),
                          @OA\Property(property="new_end_date", type="string", format="date", example=null),
                          @OA\Property(property="previous_institution_staff_id", type="integer", example=null),
                          @OA\Property(property="previous_staff_type_id", type="integer", example=null),
                          @OA\Property(property="previous_FTE", type="number", example=null),
                          @OA\Property(property="previous_end_date", type="string", format="date", example=null),
                          @OA\Property(property="previous_effective_date", type="string", format="date", example=null),
                          @OA\Property(property="comment", type="string", example=null),
                          @OA\Property(property="transfer_type", type="integer", example=null),
                          @OA\Property(property="all_visible", type="integer", example=null),
                          @OA\Property(property="is_homeroom", type="integer", example=null),
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
 *     path="/api/v5/institution-staff-transfers",
 *     summary="Create a new InstitutionStaffTransfers",
 *     tags={"InstitutionStaffTransfers"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="staff_id", type="integer", example=null),
                     @OA\Property(property="new_institution_id", type="integer", example=null),
                     @OA\Property(property="previous_institution_id", type="integer", example=null),
                     @OA\Property(property="status_id", type="integer", example=null),
                     @OA\Property(property="assignee_id", type="integer", example=null),
                     @OA\Property(property="new_institution_position_id", type="integer", example=null),
                     @OA\Property(property="new_staff_type_id", type="integer", example=null),
                     @OA\Property(property="new_FTE", type="number", example=null),
                     @OA\Property(property="new_start_date", type="string", format="date", example=null),
                     @OA\Property(property="new_end_date", type="string", format="date", example=null),
                     @OA\Property(property="previous_institution_staff_id", type="integer", example=null),
                     @OA\Property(property="previous_staff_type_id", type="integer", example=null),
                     @OA\Property(property="previous_FTE", type="number", example=null),
                     @OA\Property(property="previous_end_date", type="string", format="date", example=null),
                     @OA\Property(property="previous_effective_date", type="string", format="date", example=null),
                     @OA\Property(property="comment", type="string", example=null),
                     @OA\Property(property="transfer_type", type="integer", example=null),
                     @OA\Property(property="all_visible", type="integer", example=null),
                     @OA\Property(property="is_homeroom", type="integer", example=null),
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
 *     path="/api/v5/institution-staff-transfers/{id}",
 *     summary="Get InstitutionStaffTransfers by ID",
 *     tags={"InstitutionStaffTransfers"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the InstitutionStaffTransfers",
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
 *     path="/api/v5/institution-staff-transfers/{id}",
 *     summary="Update InstitutionStaffTransfers",
 *     tags={"InstitutionStaffTransfers"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the InstitutionStaffTransfers",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="staff_id", type="integer", example=null),
                     @OA\Property(property="new_institution_id", type="integer", example=null),
                     @OA\Property(property="previous_institution_id", type="integer", example=null),
                     @OA\Property(property="status_id", type="integer", example=null),
                     @OA\Property(property="assignee_id", type="integer", example=null),
                     @OA\Property(property="new_institution_position_id", type="integer", example=null),
                     @OA\Property(property="new_staff_type_id", type="integer", example=null),
                     @OA\Property(property="new_FTE", type="number", example=null),
                     @OA\Property(property="new_start_date", type="string", format="date", example=null),
                     @OA\Property(property="new_end_date", type="string", format="date", example=null),
                     @OA\Property(property="previous_institution_staff_id", type="integer", example=null),
                     @OA\Property(property="previous_staff_type_id", type="integer", example=null),
                     @OA\Property(property="previous_FTE", type="number", example=null),
                     @OA\Property(property="previous_end_date", type="string", format="date", example=null),
                     @OA\Property(property="previous_effective_date", type="string", format="date", example=null),
                     @OA\Property(property="comment", type="string", example=null),
                     @OA\Property(property="transfer_type", type="integer", example=null),
                     @OA\Property(property="all_visible", type="integer", example=null),
                     @OA\Property(property="is_homeroom", type="integer", example=null),
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
 *     path="/api/v5/institution-staff-transfers/{id}",
 *     summary="Delete InstitutionStaffTransfers",
 *     tags={"InstitutionStaffTransfers"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the InstitutionStaffTransfers",
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
    public function newInstitution()
    {
        return $this->belongsTo(Institutions::class, 'new_institution_id', 'id');
    }

    public function previousInstitution()
    {
        return $this->belongsTo(Institutions::class, 'previous_institution_id', 'id');
    }

    public function assignee()
    {
        return $this->belongsTo(SecurityUsers::class, 'assignee_id', 'id');
    }

    public function securityUser()
    {
        return $this->belongsTo(SecurityUsers::class, 'created_user_id', 'id');
    }


    public function user()
    {
        return $this->belongsTo(SecurityUsers::class, 'staff_id', 'id');
    }


    public function status()
    {
        return $this->belongsTo(WorkflowSteps::class, 'status_id', 'id');
    }
}
