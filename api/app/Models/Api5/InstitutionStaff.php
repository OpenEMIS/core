<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;

use App\Models\Concerns\WebhookQueueTrait;
class InstitutionStaff extends Model
{
    use HasFactory;
    use InstitutionScope;
    // POCOR-9257: Configure webhook events
    use WebhookQueueTrait;
    protected $webhookEvents = ['created', 'updated', 'deleted'];

    // ✅ Allow mass assignment
    public $timestamps = false;
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $fillable = ['id', 'FTE', 'start_date', 'start_year', 'end_date', 'end_year', 'staff_id', 'staff_type_id', 'staff_status_id', 'institution_id', 'is_homeroom', 'institution_position_id', 'security_group_user_id', 'staff_position_grade_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'staff_id', 'staff_type_id', 'staff_status_id', 'institution_id', 'institution_position_id', 'security_group_user_id', 'staff_position_grade_id', 'modified_user_id', 'created_user_id'];
    protected $dates = ['modified', 'created'];
    protected $table = "institution_staff";


    /**
     * @OA\PathItem(
     *     path="/api/v5/institution-staff"
     * )
     */
    public function _swaggerPath()
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v5/institution-staff",
     *     summary="Get list of InstitutionStaff",
     *     tags={"InstitutionStaff"},
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
    @OA\Property(property="FTE", type="number", example=null),
    @OA\Property(property="start_date", type="string", format="date", example=null),
    @OA\Property(property="start_year", type="integer", example=null),
    @OA\Property(property="end_date", type="string", format="date", example=null),
    @OA\Property(property="end_year", type="integer", example=null),
    @OA\Property(property="staff_id", type="integer", example=null),
    @OA\Property(property="staff_type_id", type="integer", example=null),
    @OA\Property(property="staff_status_id", type="integer", example=null),
    @OA\Property(property="institution_id", type="integer", example=null),
    @OA\Property(property="is_homeroom", type="integer", example=null),
    @OA\Property(property="institution_position_id", type="integer", example=null),
    @OA\Property(property="security_group_user_id", type="string", example=null),
    @OA\Property(property="staff_position_grade_id", type="integer", example=null),
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
     *     path="/api/v5/institution-staff",
     *     summary="Create a new InstitutionStaff",
     *     tags={"InstitutionStaff"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
    @OA\Property(property="id", type="integer", example=null),
    @OA\Property(property="FTE", type="number", example=null),
    @OA\Property(property="start_date", type="string", format="date", example=null),
    @OA\Property(property="start_year", type="integer", example=null),
    @OA\Property(property="end_date", type="string", format="date", example=null),
    @OA\Property(property="end_year", type="integer", example=null),
    @OA\Property(property="staff_id", type="integer", example=null),
    @OA\Property(property="staff_type_id", type="integer", example=null),
    @OA\Property(property="staff_status_id", type="integer", example=null),
    @OA\Property(property="institution_id", type="integer", example=null),
    @OA\Property(property="is_homeroom", type="integer", example=null),
    @OA\Property(property="institution_position_id", type="integer", example=null),
    @OA\Property(property="security_group_user_id", type="string", example=null),
    @OA\Property(property="staff_position_grade_id", type="integer", example=null),
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
     *     path="/api/v5/institution-staff/{id}",
     *     summary="Get InstitutionStaff by ID",
     *     tags={"InstitutionStaff"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the InstitutionStaff",
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
     *     path="/api/v5/institution-staff/{id}",
     *     summary="Update InstitutionStaff",
     *     tags={"InstitutionStaff"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the InstitutionStaff",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
    @OA\Property(property="id", type="integer", example=null),
    @OA\Property(property="FTE", type="number", example=null),
    @OA\Property(property="start_date", type="string", format="date", example=null),
    @OA\Property(property="start_year", type="integer", example=null),
    @OA\Property(property="end_date", type="string", format="date", example=null),
    @OA\Property(property="end_year", type="integer", example=null),
    @OA\Property(property="staff_id", type="integer", example=null),
    @OA\Property(property="staff_type_id", type="integer", example=null),
    @OA\Property(property="staff_status_id", type="integer", example=null),
    @OA\Property(property="institution_id", type="integer", example=null),
    @OA\Property(property="is_homeroom", type="integer", example=null),
    @OA\Property(property="institution_position_id", type="integer", example=null),
    @OA\Property(property="security_group_user_id", type="string", example=null),
    @OA\Property(property="staff_position_grade_id", type="integer", example=null),
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
     *     path="/api/v5/institution-staff/{id}",
     *     summary="Delete InstitutionStaff",
     *     tags={"InstitutionStaff"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the InstitutionStaff",
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

    public function institution()
    {
        return $this->belongsTo(Institutions::class, 'institution_id', 'id');
    }

    public function staffStatus()
    {
        return $this->belongsTo(StaffStatuses::class, 'staff_status_id', 'id');
    }

    public function scopeWithStatus($query)
    {
        return $query->with('staffStatus:id,code,name');
    }

    public function staffType()
    {
        return $this->belongsTo(StaffTypes::class, 'staff_type_id', 'id');
    }


    public function institutionPosition()
    {
        return $this->belongsTo(InstitutionPositions::class, 'institution_position_id', 'id');
    }

    public function scopeWithPosition($query)
    {
        return $query->with([
            'institutionPosition:id,position_no,staff_position_title_id',
            'institutionPosition.staffPositionTitle:id,name'
        ]);
    }


    public function user()
    {
        return $this->belongsTo(SecurityUsers::class, 'staff_id', 'id');
    }

    public function scopeWithUser($query)
    {
        return $query->with('user:id,openemis_no,first_name,last_name');
    }


    public function classes()
    {
        return $this->hasMany(InstitutionClasses::class, 'staff_id', 'staff_id');
    }


    public function staffPositionGrade()
    {
        return $this->belongsTo(StaffPositionGrades::class, 'staff_position_grade_id', 'id');
    }


    //For POCOR-8491 Start...
    public function staffCustomFieldValue()
    {
        return $this->hasMany(StaffCustomFieldValues::class, 'staff_id', 'staff_id');
    }
    //For POCOR-8491 End...
}
