<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SummaryProgrammeSectorGenders extends Model
{
    use HasFactory;

    protected $table = 'summary_programme_sector_genders';

    // ✅ Allow mass assignment
    protected $fillable = ['academic_period_id', 'academic_period_name', 'institution_sector_id', 'institution_sector_name', 'education_system_id', 'education_system_name', 'education_level_isced_id', 'education_level_isced_name', 'education_level_isced_level', 'education_level_id', 'education_level_name', 'education_cycle_id', 'education_cycle_name', 'education_programme_id', 'education_programme_code', 'education_programme_name', 'gender_id', 'gender_name', 'total_students', 'total_staff_teaching', 'total_staff_teaching_newly_recruited'];

    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;

    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    // ✅ Define the primary key

    public $incrementing = false;
    protected $primaryKey = null;


    // Override getKeyForSaveQuery to handle composite keys
/**
 * @OA\PathItem(
 *     path="/api/v5/summary-programme-sector-genders"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/summary-programme-sector-genders",
 *     summary="Get list of SummaryProgrammeSectorGenders",
 *     tags={"SummaryProgrammeSectorGenders"},
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
                          @OA\Property(property="academic_period_id", type="integer", example=null),
                          @OA\Property(property="academic_period_name", type="string", example=null),
                          @OA\Property(property="institution_sector_id", type="integer", example=null),
                          @OA\Property(property="institution_sector_name", type="string", example=null),
                          @OA\Property(property="education_system_id", type="integer", example=null),
                          @OA\Property(property="education_system_name", type="string", example=null),
                          @OA\Property(property="education_level_isced_id", type="integer", example=null),
                          @OA\Property(property="education_level_isced_name", type="string", example=null),
                          @OA\Property(property="education_level_isced_level", type="integer", example=null),
                          @OA\Property(property="education_level_id", type="integer", example=null),
                          @OA\Property(property="education_level_name", type="string", example=null),
                          @OA\Property(property="education_cycle_id", type="integer", example=null),
                          @OA\Property(property="education_cycle_name", type="string", example=null),
                          @OA\Property(property="education_programme_id", type="integer", example=null),
                          @OA\Property(property="education_programme_code", type="string", example=null),
                          @OA\Property(property="education_programme_name", type="string", example=null),
                          @OA\Property(property="gender_id", type="integer", example=null),
                          @OA\Property(property="gender_name", type="string", example=null),
                          @OA\Property(property="total_students", type="integer", example=null),
                          @OA\Property(property="total_staff_teaching", type="integer", example=null),
                          @OA\Property(property="total_staff_teaching_newly_recruited", type="integer", example=null)
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
 *     path="/api/v5/summary-programme-sector-genders/{id}",
 *     summary="Get SummaryProgrammeSectorGenders by ID",
 *     tags={"SummaryProgrammeSectorGenders"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the SummaryProgrammeSectorGenders",
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
 *     path="/api/v5/summary-programme-sector-genders",
 *     summary="Create a new SummaryProgrammeSectorGenders",
 *     tags={"SummaryProgrammeSectorGenders"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="academic_period_id", type="integer", example=null),
                     @OA\Property(property="academic_period_name", type="string", example=null),
                     @OA\Property(property="institution_sector_id", type="integer", example=null),
                     @OA\Property(property="institution_sector_name", type="string", example=null),
                     @OA\Property(property="education_system_id", type="integer", example=null),
                     @OA\Property(property="education_system_name", type="string", example=null),
                     @OA\Property(property="education_level_isced_id", type="integer", example=null),
                     @OA\Property(property="education_level_isced_name", type="string", example=null),
                     @OA\Property(property="education_level_isced_level", type="integer", example=null),
                     @OA\Property(property="education_level_id", type="integer", example=null),
                     @OA\Property(property="education_level_name", type="string", example=null),
                     @OA\Property(property="education_cycle_id", type="integer", example=null),
                     @OA\Property(property="education_cycle_name", type="string", example=null),
                     @OA\Property(property="education_programme_id", type="integer", example=null),
                     @OA\Property(property="education_programme_code", type="string", example=null),
                     @OA\Property(property="education_programme_name", type="string", example=null),
                     @OA\Property(property="gender_id", type="integer", example=null),
                     @OA\Property(property="gender_name", type="string", example=null),
                     @OA\Property(property="total_students", type="integer", example=null),
                     @OA\Property(property="total_staff_teaching", type="integer", example=null),
                     @OA\Property(property="total_staff_teaching_newly_recruited", type="integer", example=null)
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
 *     path="/api/v5/summary-programme-sector-genders/{id}",
 *     summary="Update SummaryProgrammeSectorGenders",
 *     tags={"SummaryProgrammeSectorGenders"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the SummaryProgrammeSectorGenders",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="academic_period_id", type="integer", example=null),
                     @OA\Property(property="academic_period_name", type="string", example=null),
                     @OA\Property(property="institution_sector_id", type="integer", example=null),
                     @OA\Property(property="institution_sector_name", type="string", example=null),
                     @OA\Property(property="education_system_id", type="integer", example=null),
                     @OA\Property(property="education_system_name", type="string", example=null),
                     @OA\Property(property="education_level_isced_id", type="integer", example=null),
                     @OA\Property(property="education_level_isced_name", type="string", example=null),
                     @OA\Property(property="education_level_isced_level", type="integer", example=null),
                     @OA\Property(property="education_level_id", type="integer", example=null),
                     @OA\Property(property="education_level_name", type="string", example=null),
                     @OA\Property(property="education_cycle_id", type="integer", example=null),
                     @OA\Property(property="education_cycle_name", type="string", example=null),
                     @OA\Property(property="education_programme_id", type="integer", example=null),
                     @OA\Property(property="education_programme_code", type="string", example=null),
                     @OA\Property(property="education_programme_name", type="string", example=null),
                     @OA\Property(property="gender_id", type="integer", example=null),
                     @OA\Property(property="gender_name", type="string", example=null),
                     @OA\Property(property="total_students", type="integer", example=null),
                     @OA\Property(property="total_staff_teaching", type="integer", example=null),
                     @OA\Property(property="total_staff_teaching_newly_recruited", type="integer", example=null)
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
 *     path="/api/v5/summary-programme-sector-genders/{id}",
 *     summary="Delete SummaryProgrammeSectorGenders",
 *     tags={"SummaryProgrammeSectorGenders"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the SummaryProgrammeSectorGenders",
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

    public static function getValidationRules(): array
    {
        return [
            // Add validation rules here
        ];
    }




    public function _swaggerHelper() {
        return;
    }
}
