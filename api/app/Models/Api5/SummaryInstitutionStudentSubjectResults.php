<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;

class SummaryInstitutionStudentSubjectResults extends Model
{
    use HasFactory;
use InstitutionScope;

    protected $table = 'summary_institution_student_subject_results';

    // ✅ Allow mass assignment
    protected $fillable = ['academic_period_id', 'academic_period_name', 'area_id', 'area_code', 'area_name', 'area_administrative_id', 'area_administrative_code', 'area_administrative_name', 'institution_provider_id', 'institution_provider_name', 'institution_ownership_id', 'institution_ownership_name', 'institution_gender_id', 'institution_gender_name', 'institution_id', 'institution_code', 'institution_name', 'education_grade_id', 'education_grade_code', 'education_grade_name', 'student_id', 'student_openemis_no', 'student_first_name', 'student_middle_name', 'student_third_name', 'student_last_name', 'student_gender_id', 'student_gender_name', 'student_default_identity_id', 'student_default_identity_type', 'student_default_identity_number', 'student_default_nationality_id', 'student_default_nationality_name', 'education_subject_id', 'education_subject_code', 'education_subject_name', 'total_avg_results', 'male_avg_results', 'female_avg_results', 'created'];

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
 *     path="/api/v5/summary-institution-student-subject-results"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/summary-institution-student-subject-results",
 *     summary="Get list of SummaryInstitutionStudentSubjectResults",
 *     tags={"SummaryInstitutionStudentSubjectResults"},
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
                          @OA\Property(property="academic_period_id", type="integer", example=null),
                          @OA\Property(property="academic_period_name", type="string", example=null),
                          @OA\Property(property="area_id", type="integer", example=null),
                          @OA\Property(property="area_code", type="string", example=null),
                          @OA\Property(property="area_name", type="string", example=null),
                          @OA\Property(property="area_administrative_id", type="integer", example=null),
                          @OA\Property(property="area_administrative_code", type="string", example=null),
                          @OA\Property(property="area_administrative_name", type="string", example=null),
                          @OA\Property(property="institution_provider_id", type="integer", example=null),
                          @OA\Property(property="institution_provider_name", type="string", example=null),
                          @OA\Property(property="institution_ownership_id", type="integer", example=null),
                          @OA\Property(property="institution_ownership_name", type="string", example=null),
                          @OA\Property(property="institution_gender_id", type="integer", example=null),
                          @OA\Property(property="institution_gender_name", type="string", example=null),
                          @OA\Property(property="institution_id", type="integer", example=null),
                          @OA\Property(property="institution_code", type="string", example=null),
                          @OA\Property(property="institution_name", type="string", example=null),
                          @OA\Property(property="education_grade_id", type="integer", example=null),
                          @OA\Property(property="education_grade_code", type="string", example=null),
                          @OA\Property(property="education_grade_name", type="string", example=null),
                          @OA\Property(property="student_id", type="integer", example=null),
                          @OA\Property(property="student_openemis_no", type="string", example=null),
                          @OA\Property(property="student_first_name", type="string", example=null),
                          @OA\Property(property="student_middle_name", type="string", example=null),
                          @OA\Property(property="student_third_name", type="string", example=null),
                          @OA\Property(property="student_last_name", type="string", example=null),
                          @OA\Property(property="student_gender_id", type="integer", example=null),
                          @OA\Property(property="student_gender_name", type="string", example=null),
                          @OA\Property(property="student_default_identity_id", type="integer", example=null),
                          @OA\Property(property="student_default_identity_type", type="string", example=null),
                          @OA\Property(property="student_default_identity_number", type="string", example=null),
                          @OA\Property(property="student_default_nationality_id", type="integer", example=null),
                          @OA\Property(property="student_default_nationality_name", type="string", example=null),
                          @OA\Property(property="education_subject_id", type="integer", example=null),
                          @OA\Property(property="education_subject_code", type="string", example=null),
                          @OA\Property(property="education_subject_name", type="string", example=null),
                          @OA\Property(property="total_avg_results", type="string", example=null),
                          @OA\Property(property="male_avg_results", type="string", example=null),
                          @OA\Property(property="female_avg_results", type="string", example=null),
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
 *     path="/api/v5/summary-institution-student-subject-results",
 *     summary="Create a new SummaryInstitutionStudentSubjectResults",
 *     tags={"SummaryInstitutionStudentSubjectResults"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="academic_period_id", type="integer", example=null),
                     @OA\Property(property="academic_period_name", type="string", example=null),
                     @OA\Property(property="area_id", type="integer", example=null),
                     @OA\Property(property="area_code", type="string", example=null),
                     @OA\Property(property="area_name", type="string", example=null),
                     @OA\Property(property="area_administrative_id", type="integer", example=null),
                     @OA\Property(property="area_administrative_code", type="string", example=null),
                     @OA\Property(property="area_administrative_name", type="string", example=null),
                     @OA\Property(property="institution_provider_id", type="integer", example=null),
                     @OA\Property(property="institution_provider_name", type="string", example=null),
                     @OA\Property(property="institution_ownership_id", type="integer", example=null),
                     @OA\Property(property="institution_ownership_name", type="string", example=null),
                     @OA\Property(property="institution_gender_id", type="integer", example=null),
                     @OA\Property(property="institution_gender_name", type="string", example=null),
                     @OA\Property(property="institution_id", type="integer", example=null),
                     @OA\Property(property="institution_code", type="string", example=null),
                     @OA\Property(property="institution_name", type="string", example=null),
                     @OA\Property(property="education_grade_id", type="integer", example=null),
                     @OA\Property(property="education_grade_code", type="string", example=null),
                     @OA\Property(property="education_grade_name", type="string", example=null),
                     @OA\Property(property="student_id", type="integer", example=null),
                     @OA\Property(property="student_openemis_no", type="string", example=null),
                     @OA\Property(property="student_first_name", type="string", example=null),
                     @OA\Property(property="student_middle_name", type="string", example=null),
                     @OA\Property(property="student_third_name", type="string", example=null),
                     @OA\Property(property="student_last_name", type="string", example=null),
                     @OA\Property(property="student_gender_id", type="integer", example=null),
                     @OA\Property(property="student_gender_name", type="string", example=null),
                     @OA\Property(property="student_default_identity_id", type="integer", example=null),
                     @OA\Property(property="student_default_identity_type", type="string", example=null),
                     @OA\Property(property="student_default_identity_number", type="string", example=null),
                     @OA\Property(property="student_default_nationality_id", type="integer", example=null),
                     @OA\Property(property="student_default_nationality_name", type="string", example=null),
                     @OA\Property(property="education_subject_id", type="integer", example=null),
                     @OA\Property(property="education_subject_code", type="string", example=null),
                     @OA\Property(property="education_subject_name", type="string", example=null),
                     @OA\Property(property="total_avg_results", type="string", example=null),
                     @OA\Property(property="male_avg_results", type="string", example=null),
                     @OA\Property(property="female_avg_results", type="string", example=null),
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
 *     path="/api/v5/summary-institution-student-subject-results/{id}",
 *     summary="Get SummaryInstitutionStudentSubjectResults by ID",
 *     tags={"SummaryInstitutionStudentSubjectResults"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the SummaryInstitutionStudentSubjectResults",
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
 *     path="/api/v5/summary-institution-student-subject-results/{id}",
 *     summary="Update SummaryInstitutionStudentSubjectResults",
 *     tags={"SummaryInstitutionStudentSubjectResults"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the SummaryInstitutionStudentSubjectResults",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="academic_period_id", type="integer", example=null),
                     @OA\Property(property="academic_period_name", type="string", example=null),
                     @OA\Property(property="area_id", type="integer", example=null),
                     @OA\Property(property="area_code", type="string", example=null),
                     @OA\Property(property="area_name", type="string", example=null),
                     @OA\Property(property="area_administrative_id", type="integer", example=null),
                     @OA\Property(property="area_administrative_code", type="string", example=null),
                     @OA\Property(property="area_administrative_name", type="string", example=null),
                     @OA\Property(property="institution_provider_id", type="integer", example=null),
                     @OA\Property(property="institution_provider_name", type="string", example=null),
                     @OA\Property(property="institution_ownership_id", type="integer", example=null),
                     @OA\Property(property="institution_ownership_name", type="string", example=null),
                     @OA\Property(property="institution_gender_id", type="integer", example=null),
                     @OA\Property(property="institution_gender_name", type="string", example=null),
                     @OA\Property(property="institution_id", type="integer", example=null),
                     @OA\Property(property="institution_code", type="string", example=null),
                     @OA\Property(property="institution_name", type="string", example=null),
                     @OA\Property(property="education_grade_id", type="integer", example=null),
                     @OA\Property(property="education_grade_code", type="string", example=null),
                     @OA\Property(property="education_grade_name", type="string", example=null),
                     @OA\Property(property="student_id", type="integer", example=null),
                     @OA\Property(property="student_openemis_no", type="string", example=null),
                     @OA\Property(property="student_first_name", type="string", example=null),
                     @OA\Property(property="student_middle_name", type="string", example=null),
                     @OA\Property(property="student_third_name", type="string", example=null),
                     @OA\Property(property="student_last_name", type="string", example=null),
                     @OA\Property(property="student_gender_id", type="integer", example=null),
                     @OA\Property(property="student_gender_name", type="string", example=null),
                     @OA\Property(property="student_default_identity_id", type="integer", example=null),
                     @OA\Property(property="student_default_identity_type", type="string", example=null),
                     @OA\Property(property="student_default_identity_number", type="string", example=null),
                     @OA\Property(property="student_default_nationality_id", type="integer", example=null),
                     @OA\Property(property="student_default_nationality_name", type="string", example=null),
                     @OA\Property(property="education_subject_id", type="integer", example=null),
                     @OA\Property(property="education_subject_code", type="string", example=null),
                     @OA\Property(property="education_subject_name", type="string", example=null),
                     @OA\Property(property="total_avg_results", type="string", example=null),
                     @OA\Property(property="male_avg_results", type="string", example=null),
                     @OA\Property(property="female_avg_results", type="string", example=null),
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
 *     path="/api/v5/summary-institution-student-subject-results/{id}",
 *     summary="Delete SummaryInstitutionStudentSubjectResults",
 *     tags={"SummaryInstitutionStudentSubjectResults"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the SummaryInstitutionStudentSubjectResults",
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
