<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;

class SummaryStudentAssessments extends Model
{
    use HasFactory;
use InstitutionScope;

    protected $table = 'summary_student_assessments';

    // ✅ Allow mass assignment
    protected $fillable = ['academic_period_id', 'academic_period_code', 'academic_period_name', 'area_id', 'area_code', 'area_name', 'institution_id', 'institution_code', 'institution_name', 'grade_id', 'grade_code', 'grade_name', 'institution_classes_id', 'institution_classes_name', 'homeroom_teacher_id', 'homeroom_teacher_name', 'subject_id', 'subject_code', 'subject_name', 'subject_weight', 'assessment_id', 'assessment_code', 'assessment_name', 'period_id', 'period_code', 'period_name', 'academic_term', 'period_weight', 'student_id', 'student_name', 'latest_mark', 'total_mark', 'average_mark', 'institution_average_mark', 'area_average_mark', 'created'];

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
 *     path="/api/v5/summary-student-assessments"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/summary-student-assessments",
 *     summary="Get list of SummaryStudentAssessments",
 *     tags={"SummaryStudentAssessments"},
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
                          @OA\Property(property="academic_period_code", type="string", example=null),
                          @OA\Property(property="academic_period_name", type="string", example=null),
                          @OA\Property(property="area_id", type="integer", example=null),
                          @OA\Property(property="area_code", type="string", example=null),
                          @OA\Property(property="area_name", type="string", example=null),
                          @OA\Property(property="institution_id", type="integer", example=null),
                          @OA\Property(property="institution_code", type="string", example=null),
                          @OA\Property(property="institution_name", type="string", example=null),
                          @OA\Property(property="grade_id", type="integer", example=null),
                          @OA\Property(property="grade_code", type="string", example=null),
                          @OA\Property(property="grade_name", type="string", example=null),
                          @OA\Property(property="institution_classes_id", type="integer", example=null),
                          @OA\Property(property="institution_classes_name", type="string", example=null),
                          @OA\Property(property="homeroom_teacher_id", type="integer", example=null),
                          @OA\Property(property="homeroom_teacher_name", type="string", example=null),
                          @OA\Property(property="subject_id", type="integer", example=null),
                          @OA\Property(property="subject_code", type="string", example=null),
                          @OA\Property(property="subject_name", type="string", example=null),
                          @OA\Property(property="subject_weight", type="number", example=null),
                          @OA\Property(property="assessment_id", type="integer", example=null),
                          @OA\Property(property="assessment_code", type="string", example=null),
                          @OA\Property(property="assessment_name", type="string", example=null),
                          @OA\Property(property="period_id", type="integer", example=null),
                          @OA\Property(property="period_code", type="string", example=null),
                          @OA\Property(property="period_name", type="string", example=null),
                          @OA\Property(property="academic_term", type="string", example=null),
                          @OA\Property(property="period_weight", type="number", example=null),
                          @OA\Property(property="student_id", type="integer", example=null),
                          @OA\Property(property="student_name", type="string", example=null),
                          @OA\Property(property="latest_mark", type="number", example=null),
                          @OA\Property(property="total_mark", type="number", example=null),
                          @OA\Property(property="average_mark", type="number", example=null),
                          @OA\Property(property="institution_average_mark", type="number", example=null),
                          @OA\Property(property="area_average_mark", type="number", example=null),
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
 *     path="/api/v5/summary-student-assessments",
 *     summary="Create a new SummaryStudentAssessments",
 *     tags={"SummaryStudentAssessments"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="academic_period_id", type="integer", example=null),
                     @OA\Property(property="academic_period_code", type="string", example=null),
                     @OA\Property(property="academic_period_name", type="string", example=null),
                     @OA\Property(property="area_id", type="integer", example=null),
                     @OA\Property(property="area_code", type="string", example=null),
                     @OA\Property(property="area_name", type="string", example=null),
                     @OA\Property(property="institution_id", type="integer", example=null),
                     @OA\Property(property="institution_code", type="string", example=null),
                     @OA\Property(property="institution_name", type="string", example=null),
                     @OA\Property(property="grade_id", type="integer", example=null),
                     @OA\Property(property="grade_code", type="string", example=null),
                     @OA\Property(property="grade_name", type="string", example=null),
                     @OA\Property(property="institution_classes_id", type="integer", example=null),
                     @OA\Property(property="institution_classes_name", type="string", example=null),
                     @OA\Property(property="homeroom_teacher_id", type="integer", example=null),
                     @OA\Property(property="homeroom_teacher_name", type="string", example=null),
                     @OA\Property(property="subject_id", type="integer", example=null),
                     @OA\Property(property="subject_code", type="string", example=null),
                     @OA\Property(property="subject_name", type="string", example=null),
                     @OA\Property(property="subject_weight", type="number", example=null),
                     @OA\Property(property="assessment_id", type="integer", example=null),
                     @OA\Property(property="assessment_code", type="string", example=null),
                     @OA\Property(property="assessment_name", type="string", example=null),
                     @OA\Property(property="period_id", type="integer", example=null),
                     @OA\Property(property="period_code", type="string", example=null),
                     @OA\Property(property="period_name", type="string", example=null),
                     @OA\Property(property="academic_term", type="string", example=null),
                     @OA\Property(property="period_weight", type="number", example=null),
                     @OA\Property(property="student_id", type="integer", example=null),
                     @OA\Property(property="student_name", type="string", example=null),
                     @OA\Property(property="latest_mark", type="number", example=null),
                     @OA\Property(property="total_mark", type="number", example=null),
                     @OA\Property(property="average_mark", type="number", example=null),
                     @OA\Property(property="institution_average_mark", type="number", example=null),
                     @OA\Property(property="area_average_mark", type="number", example=null),
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
 *     path="/api/v5/summary-student-assessments/{id}",
 *     summary="Get SummaryStudentAssessments by ID",
 *     tags={"SummaryStudentAssessments"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the SummaryStudentAssessments",
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
 *     path="/api/v5/summary-student-assessments/{id}",
 *     summary="Update SummaryStudentAssessments",
 *     tags={"SummaryStudentAssessments"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the SummaryStudentAssessments",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="academic_period_id", type="integer", example=null),
                     @OA\Property(property="academic_period_code", type="string", example=null),
                     @OA\Property(property="academic_period_name", type="string", example=null),
                     @OA\Property(property="area_id", type="integer", example=null),
                     @OA\Property(property="area_code", type="string", example=null),
                     @OA\Property(property="area_name", type="string", example=null),
                     @OA\Property(property="institution_id", type="integer", example=null),
                     @OA\Property(property="institution_code", type="string", example=null),
                     @OA\Property(property="institution_name", type="string", example=null),
                     @OA\Property(property="grade_id", type="integer", example=null),
                     @OA\Property(property="grade_code", type="string", example=null),
                     @OA\Property(property="grade_name", type="string", example=null),
                     @OA\Property(property="institution_classes_id", type="integer", example=null),
                     @OA\Property(property="institution_classes_name", type="string", example=null),
                     @OA\Property(property="homeroom_teacher_id", type="integer", example=null),
                     @OA\Property(property="homeroom_teacher_name", type="string", example=null),
                     @OA\Property(property="subject_id", type="integer", example=null),
                     @OA\Property(property="subject_code", type="string", example=null),
                     @OA\Property(property="subject_name", type="string", example=null),
                     @OA\Property(property="subject_weight", type="number", example=null),
                     @OA\Property(property="assessment_id", type="integer", example=null),
                     @OA\Property(property="assessment_code", type="string", example=null),
                     @OA\Property(property="assessment_name", type="string", example=null),
                     @OA\Property(property="period_id", type="integer", example=null),
                     @OA\Property(property="period_code", type="string", example=null),
                     @OA\Property(property="period_name", type="string", example=null),
                     @OA\Property(property="academic_term", type="string", example=null),
                     @OA\Property(property="period_weight", type="number", example=null),
                     @OA\Property(property="student_id", type="integer", example=null),
                     @OA\Property(property="student_name", type="string", example=null),
                     @OA\Property(property="latest_mark", type="number", example=null),
                     @OA\Property(property="total_mark", type="number", example=null),
                     @OA\Property(property="average_mark", type="number", example=null),
                     @OA\Property(property="institution_average_mark", type="number", example=null),
                     @OA\Property(property="area_average_mark", type="number", example=null),
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
 *     path="/api/v5/summary-student-assessments/{id}",
 *     summary="Delete SummaryStudentAssessments",
 *     tags={"SummaryStudentAssessments"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the SummaryStudentAssessments",
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
