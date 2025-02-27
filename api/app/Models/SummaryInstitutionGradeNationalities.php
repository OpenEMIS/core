<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SummaryInstitutionGradeNationalities extends Model
{
    use HasFactory;
    // ✅ Allow mass assignment
    protected $fillable = ['academic_period_id', 'academic_period_name', 'institution_id', 'institution_code', 'grade_id', 'grade_name', 'nationality_id', 'nationality_name', 'total_students', 'total_students_female', 'total_students_male', 'academic_period_id', 'institution_id', 'grade_id', 'nationality_id'];

    public $timestamps = false;
    protected $table = "summary_institution_grade_nationalities";

    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    // ✅ Define the primary key

    // ✅ Define the primary key
    public $incrementing = false;
    protected $primaryKey = null;

/**
 * @OA\PathItem(
 *     path="/api/v5/summary-institution-grade-nationalities"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/summary-institution-grade-nationalities",
 *     summary="Get list of SummaryInstitutionGradeNationalities",
 *     tags={"SummaryInstitutionGradeNationalities"},
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
                          @OA\Property(property="grade_id", type="integer", example=null),
                          @OA\Property(property="grade_name", type="string", example=null),
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
 *     path="/api/v5/summary-institution-grade-nationalities/{id}",
 *     summary="Get SummaryInstitutionGradeNationalities by ID",
 *     tags={"SummaryInstitutionGradeNationalities"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the SummaryInstitutionGradeNationalities",
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
 *     path="/api/v5/summary-institution-grade-nationalities",
 *     summary="Create a new SummaryInstitutionGradeNationalities",
 *     tags={"SummaryInstitutionGradeNationalities"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="academic_period_id", type="integer", example=null),
                     @OA\Property(property="academic_period_name", type="string", example=null),
                     @OA\Property(property="institution_id", type="integer", example=null),
                     @OA\Property(property="institution_code", type="string", example=null),
                     @OA\Property(property="grade_id", type="integer", example=null),
                     @OA\Property(property="grade_name", type="string", example=null),
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
 *     path="/api/v5/summary-institution-grade-nationalities/{id}",
 *     summary="Update SummaryInstitutionGradeNationalities",
 *     tags={"SummaryInstitutionGradeNationalities"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the SummaryInstitutionGradeNationalities",
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
                     @OA\Property(property="grade_id", type="integer", example=null),
                     @OA\Property(property="grade_name", type="string", example=null),
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
 *     path="/api/v5/summary-institution-grade-nationalities/{id}",
 *     summary="Delete SummaryInstitutionGradeNationalities",
 *     tags={"SummaryInstitutionGradeNationalities"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the SummaryInstitutionGradeNationalities",
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
