<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use App\Models\SecurityUsers;
use App\Models\Institutions;


class InstitutionDepartments extends Model
{
    use HasFactory;

    // ✅ Treat 'modified' and 'created' as timestamps
    public $timestamps = false;
    protected $dates = ['modified', 'created'];
    protected $fillable = [
        'code',
        'name',
        'institution_id',
        'staff_id',
        'manager_id',
        'created_user_id',
        'created',
        'modified_user_id',
        'modified'
    ];
    protected $primaryKey = 'id';
    protected $table = "institution_departments";

    public static function getValidationRules(): array
    {
        if (request()->isMethod('post')) {
            $required = 'required';
        } else {
            $required = 'sometimes';
        }
        return [

            'name' => [$required, 'string', 'max:255',
                Rule::unique('institution_departments', 'name')
                    ->ignore(request()->get('id'))],
            'code' => [$required, 'string', 'max:50',
                Rule::unique('institution_departments', 'code')
                    ->ignore(request()->get('id'))],
            'institution_id' => $required . '|exists:institutions,id',
            'manager_id' => 'nullable|exists:security_users,id',
            'created_user_id' => $required . '|exists:security_users,id',
            'modified_user_id' => 'nullable|exists:security_users,id',
        ];
    }

    /**
     * @OA\PathItem(
     *     path="/api/v5/institution-departments"
     * )
     */
    public function _swaggerPath()
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v5/institution-departments",
     *     summary="Get list of InstitutionDepartments",
     *     tags={"InstitutionDepartments"},
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
    @OA\Property(property="name", type="string", example=null),
    @OA\Property(property="code", type="string", example=null),
    @OA\Property(property="institution_id", type="integer", example=null),
    @OA\Property(property="manager_id", type="integer", example=null),
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
     * @OA\Get(
     *     path="/api/v5/institution-departments/{id}",
     *     summary="Get InstitutionDepartments by ID",
     *     tags={"InstitutionDepartments"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the InstitutionDepartments",
     *         @OA\Schema(type="integer")
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
     * @OA\Post(
     *     path="/api/v5/institution-departments",
     *     summary="Create a new InstitutionDepartments",
     *     tags={"InstitutionDepartments"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
    @OA\Property(property="id", type="integer", example=null),
    @OA\Property(property="name", type="string", example=null),
    @OA\Property(property="code", type="string", example=null),
    @OA\Property(property="institution_id", type="integer", example=null),
    @OA\Property(property="manager_id", type="integer", example=null),
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
     * @OA\Put(
     *     path="/api/v5/institution-departments/{id}",
     *     summary="Update InstitutionDepartments",
     *     tags={"InstitutionDepartments"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the InstitutionDepartments",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
    @OA\Property(property="id", type="integer", example=null),
    @OA\Property(property="name", type="string", example=null),
    @OA\Property(property="code", type="string", example=null),
    @OA\Property(property="institution_id", type="integer", example=null),
    @OA\Property(property="manager_id", type="integer", example=null),
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

    // Define scopes for common queries

    /**
     * @OA\Delete(
     *     path="/api/v5/institution-departments/{id}",
     *     summary="Delete InstitutionDepartments",
     *     tags={"InstitutionDepartments"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the InstitutionDepartments",
     *         @OA\Schema(type="integer")
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


    public function scopeForInstitution($query, $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    // Scope to include manager details
    public function scopeWithManager($query)
    {
        return $query->with(['manager' => function ($query) {
            $query->select('id', 'first_name', 'last_name', 'openemis_no');
        }]);
    }

    public function manager()
    {
        return $this->belongsTo(SecurityUsers::class, 'manager_id');
    }

    public function staff()
    {
        return $this->belongsToMany(InstitutionStaff::class, 'department_staff', 'institution_department_id', 'institution_staff_id');
    }

    // Add validation rules

    public function institution()
    {
        return $this->belongsTo(Institutions::class, 'institution_id');
    }


}
