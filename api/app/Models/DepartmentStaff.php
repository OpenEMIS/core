<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepartmentStaff extends Model
{
    use HasFactory;

    protected $table = 'department_staff';

    // ✅ Allow mass assignment
    protected $fillable = ['id', 'department_id', 'staff_id'];

    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;

    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    // ✅ Define the primary key



     // Override getKeyForSaveQuery to handle composite keys
/**
 * @OA\PathItem(
 *     path="/api/v5/department-staff"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/department-staff",
 *     summary="Get list of DepartmentStaff",
 *     tags={"DepartmentStaff"},
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
                          @OA\Property(property="department_id", type="integer", example=null),
                          @OA\Property(property="staff_id", type="integer", example=null)
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
 *     path="/api/v5/department-staff/{id}",
 *     summary="Get DepartmentStaff by ID",
 *     tags={"DepartmentStaff"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the DepartmentStaff",
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
public function _swaggerView() {}

/**
 * @OA\Post(
 *     path="/api/v5/department-staff",
 *     summary="Create a new DepartmentStaff",
 *     tags={"DepartmentStaff"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="department_id", type="integer", example=null),
                     @OA\Property(property="staff_id", type="integer", example=null)
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
 * @OA\Put(
 *     path="/api/v5/department-staff/{id}",
 *     summary="Update DepartmentStaff",
 *     tags={"DepartmentStaff"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the DepartmentStaff",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="department_id", type="integer", example=null),
                     @OA\Property(property="staff_id", type="integer", example=null)
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
 *     path="/api/v5/department-staff/{id}",
 *     summary="Delete DepartmentStaff",
 *     tags={"DepartmentStaff"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the DepartmentStaff",
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


    public function _swaggerHelper() {
        return;
    }

    // Define scopes for common queries
    public function scopeForDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function department()
    {
        return $this->belongsTo(InstitutionDepartments::class, 'department_id');
    }

    public function staff()
    {
        return $this->belongsTo(SecurityUsers::class, 'staff_id');
    }

    public function scopeWithStaff($query)
        {
            return $query->with(['staff' => function ($query) {
                $query->select('security_users.id',
                    'security_users.first_name',
                    'security_users.last_name',
                    'security_users.openemis_no',
                    'genders.name as gender_name',
                )
                    ->join('genders', 'security_users.gender_id', '=', 'genders.id')
                    ->groupBy('security_users.id');
            }, 'department']);
        }


    // Add validation rules
    public static function getValidationRules(): array
    {
        return [
            'department_id' => 'required|exists:institution_departments,id',
            'staff_id' => 'required|exists:security_users,id',
        ];
    }
}
