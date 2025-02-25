<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SummaryInstitutionNationalities extends Model
{
    use HasFactory;
    // ✅ Allow mass assignment
    protected $fillable = ['academic_period_id', 'academic_period_name', 'institution_id', 'institution_code', 'nationality_id', 'nationality_name', 'total_students', 'total_students_female', 'total_students_male', 'academic_period_id', 'institution_id', 'nationality_id'];

    public $timestamps = false;
    protected $table = "summary_institution_nationalities";


/**
 * @OA\PathItem(
 *     path="/api/v5/summary-institution-nationalities"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/summary-institution-nationalities",
 *     summary="Get list of SummaryInstitutionNationalities",
 *     tags={"SummaryInstitutionNationalities"},
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
                          @OA\Property(property="institution_id", type="integer", example=null),
                          @OA\Property(property="institution_code", type="string", example=null),
                          @OA\Property(property="nationality_id", type="integer", example=null),
                          @OA\Property(property="nationality_name", type="string", example=null),
                          @OA\Property(property="total_students", type="integer", example=null),
                          @OA\Property(property="total_students_female", type="integer", example=null),
                          @OA\Property(property="total_students_male", type="integer", example=null)
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
 *     path="/api/v5/summary-institution-nationalities/{id}",
 *     summary="Get SummaryInstitutionNationalities by ID",
 *     tags={"SummaryInstitutionNationalities"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the SummaryInstitutionNationalities",
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
 *     path="/api/v5/summary-institution-nationalities",
 *     summary="Create a new SummaryInstitutionNationalities",
 *     tags={"SummaryInstitutionNationalities"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="academic_period_id", type="integer", example=null),
                     @OA\Property(property="academic_period_name", type="string", example=null),
                     @OA\Property(property="institution_id", type="integer", example=null),
                     @OA\Property(property="institution_code", type="string", example=null),
                     @OA\Property(property="nationality_id", type="integer", example=null),
                     @OA\Property(property="nationality_name", type="string", example=null),
                     @OA\Property(property="total_students", type="integer", example=null),
                     @OA\Property(property="total_students_female", type="integer", example=null),
                     @OA\Property(property="total_students_male", type="integer", example=null)
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
 *     path="/api/v5/summary-institution-nationalities/{id}",
 *     summary="Update SummaryInstitutionNationalities",
 *     tags={"SummaryInstitutionNationalities"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the SummaryInstitutionNationalities",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="academic_period_id", type="integer", example=null),
                     @OA\Property(property="academic_period_name", type="string", example=null),
                     @OA\Property(property="institution_id", type="integer", example=null),
                     @OA\Property(property="institution_code", type="string", example=null),
                     @OA\Property(property="nationality_id", type="integer", example=null),
                     @OA\Property(property="nationality_name", type="string", example=null),
                     @OA\Property(property="total_students", type="integer", example=null),
                     @OA\Property(property="total_students_female", type="integer", example=null),
                     @OA\Property(property="total_students_male", type="integer", example=null)
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
 *     path="/api/v5/summary-institution-nationalities/{id}",
 *     summary="Delete SummaryInstitutionNationalities",
 *     tags={"SummaryInstitutionNationalities"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the SummaryInstitutionNationalities",
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
    public function _swaggerHelper() {
        return;
    }
}