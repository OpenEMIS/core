<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;

class SummaryAssessmentItemResults extends Model
{
    use HasFactory;
use InstitutionScope;

    protected $table = 'summary_assessment_item_results';

    // ✅ Allow mass assignment
    protected $fillable = ['academic_period_id', 'academic_period_name', 'assessment_id', 'assessment_code', 'assessment_name', 'assessment_period_id', 'assessment_period_name', 'academic_term', 'subject_id', 'subject_name', 'education_grade_id', 'education_grade', 'institution_id', 'institution_code', 'institution_name', 'institution_provider_id', 'institution_provider', 'area_id', 'area_name', 'institution_class_id', 'institution_class_name', 'count_students', 'count_marked_students', 'missing_marks', 'created'];

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
 *     path="/api/v5/summary-assessment-item-results"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/summary-assessment-item-results",
 *     summary="Get list of SummaryAssessmentItemResults",
 *     tags={"SummaryAssessmentItemResults"},
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
                          @OA\Property(property="assessment_id", type="integer", example=null),
                          @OA\Property(property="assessment_code", type="string", example=null),
                          @OA\Property(property="assessment_name", type="string", example=null),
                          @OA\Property(property="assessment_period_id", type="integer", example=null),
                          @OA\Property(property="assessment_period_name", type="string", example=null),
                          @OA\Property(property="academic_term", type="string", example=null),
                          @OA\Property(property="subject_id", type="integer", example=null),
                          @OA\Property(property="subject_name", type="string", example=null),
                          @OA\Property(property="education_grade_id", type="integer", example=null),
                          @OA\Property(property="education_grade", type="string", example=null),
                          @OA\Property(property="institution_id", type="integer", example=null),
                          @OA\Property(property="institution_code", type="string", example=null),
                          @OA\Property(property="institution_name", type="string", example=null),
                          @OA\Property(property="institution_provider_id", type="integer", example=null),
                          @OA\Property(property="institution_provider", type="string", example=null),
                          @OA\Property(property="area_id", type="integer", example=null),
                          @OA\Property(property="area_name", type="string", example=null),
                          @OA\Property(property="institution_class_id", type="integer", example=null),
                          @OA\Property(property="institution_class_name", type="string", example=null),
                          @OA\Property(property="count_students", type="integer", example=null),
                          @OA\Property(property="count_marked_students", type="integer", example=null),
                          @OA\Property(property="missing_marks", type="integer", example=null),
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
 *     path="/api/v5/summary-assessment-item-results",
 *     summary="Create a new SummaryAssessmentItemResults",
 *     tags={"SummaryAssessmentItemResults"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="academic_period_id", type="integer", example=null),
                     @OA\Property(property="academic_period_name", type="string", example=null),
                     @OA\Property(property="assessment_id", type="integer", example=null),
                     @OA\Property(property="assessment_code", type="string", example=null),
                     @OA\Property(property="assessment_name", type="string", example=null),
                     @OA\Property(property="assessment_period_id", type="integer", example=null),
                     @OA\Property(property="assessment_period_name", type="string", example=null),
                     @OA\Property(property="academic_term", type="string", example=null),
                     @OA\Property(property="subject_id", type="integer", example=null),
                     @OA\Property(property="subject_name", type="string", example=null),
                     @OA\Property(property="education_grade_id", type="integer", example=null),
                     @OA\Property(property="education_grade", type="string", example=null),
                     @OA\Property(property="institution_id", type="integer", example=null),
                     @OA\Property(property="institution_code", type="string", example=null),
                     @OA\Property(property="institution_name", type="string", example=null),
                     @OA\Property(property="institution_provider_id", type="integer", example=null),
                     @OA\Property(property="institution_provider", type="string", example=null),
                     @OA\Property(property="area_id", type="integer", example=null),
                     @OA\Property(property="area_name", type="string", example=null),
                     @OA\Property(property="institution_class_id", type="integer", example=null),
                     @OA\Property(property="institution_class_name", type="string", example=null),
                     @OA\Property(property="count_students", type="integer", example=null),
                     @OA\Property(property="count_marked_students", type="integer", example=null),
                     @OA\Property(property="missing_marks", type="integer", example=null),
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
