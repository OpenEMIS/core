<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;

class SummaryInstitutionStudentAbsences extends Model
{
    use HasFactory;
use InstitutionScope;

    protected $table = 'summary_institution_student_absences';

    // ✅ Allow mass assignment
    protected $fillable = ['institution_id', 'institution_code', 'institution_name', 'area_id', 'area_code', 'area_name', 'area_administrative_id', 'area_administrative_code', 'area_administrative_name', 'student_id', 'openemis_no', 'default_identity_number', 'student_name', 'enrol_start_date', 'enrol_end_date', 'academic_period_id', 'academic_period_code', 'academic_period_name', 'education_grade_id', 'education_grade_code', 'education_grade_name', 'absent_date', 'absent_days', 'absence_subject_period', 'absence_type_id', 'absence_type', 'student_absence_reason_id', 'student_absence_reasons', 'student_status_id', 'student_status', 'created'];

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
 *     path="/api/v5/summary-institution-student-absences"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/summary-institution-student-absences",
 *     summary="Get list of SummaryInstitutionStudentAbsences",
 *     tags={"SummaryInstitutionStudentAbsences"},
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
                          @OA\Property(property="institution_id", type="integer", example=null),
                          @OA\Property(property="institution_code", type="string", example=null),
                          @OA\Property(property="institution_name", type="string", example=null),
                          @OA\Property(property="area_id", type="integer", example=null),
                          @OA\Property(property="area_code", type="string", example=null),
                          @OA\Property(property="area_name", type="string", example=null),
                          @OA\Property(property="area_administrative_id", type="integer", example=null),
                          @OA\Property(property="area_administrative_code", type="string", example=null),
                          @OA\Property(property="area_administrative_name", type="string", example=null),
                          @OA\Property(property="student_id", type="integer", example=null),
                          @OA\Property(property="openemis_no", type="string", example=null),
                          @OA\Property(property="default_identity_number", type="string", example=null),
                          @OA\Property(property="student_name", type="string", example=null),
                          @OA\Property(property="enrol_start_date", type="string", example=null),
                          @OA\Property(property="enrol_end_date", type="string", example=null),
                          @OA\Property(property="academic_period_id", type="integer", example=null),
                          @OA\Property(property="academic_period_code", type="string", example=null),
                          @OA\Property(property="academic_period_name", type="string", example=null),
                          @OA\Property(property="education_grade_id", type="integer", example=null),
                          @OA\Property(property="education_grade_code", type="string", example=null),
                          @OA\Property(property="education_grade_name", type="string", example=null),
                          @OA\Property(property="absent_date", type="string", example=null),
                          @OA\Property(property="absent_days", type="string", example=null),
                          @OA\Property(property="absence_subject_period", type="string", example=null),
                          @OA\Property(property="absence_type_id", type="integer", example=null),
                          @OA\Property(property="absence_type", type="string", example=null),
                          @OA\Property(property="student_absence_reason_id", type="integer", example=null),
                          @OA\Property(property="student_absence_reasons", type="string", example=null),
                          @OA\Property(property="student_status_id", type="integer", example=null),
                          @OA\Property(property="student_status", type="string", example=null),
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
 *     path="/api/v5/summary-institution-student-absences",
 *     summary="Create a new SummaryInstitutionStudentAbsences",
 *     tags={"SummaryInstitutionStudentAbsences"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="institution_id", type="integer", example=null),
                     @OA\Property(property="institution_code", type="string", example=null),
                     @OA\Property(property="institution_name", type="string", example=null),
                     @OA\Property(property="area_id", type="integer", example=null),
                     @OA\Property(property="area_code", type="string", example=null),
                     @OA\Property(property="area_name", type="string", example=null),
                     @OA\Property(property="area_administrative_id", type="integer", example=null),
                     @OA\Property(property="area_administrative_code", type="string", example=null),
                     @OA\Property(property="area_administrative_name", type="string", example=null),
                     @OA\Property(property="student_id", type="integer", example=null),
                     @OA\Property(property="openemis_no", type="string", example=null),
                     @OA\Property(property="default_identity_number", type="string", example=null),
                     @OA\Property(property="student_name", type="string", example=null),
                     @OA\Property(property="enrol_start_date", type="string", example=null),
                     @OA\Property(property="enrol_end_date", type="string", example=null),
                     @OA\Property(property="academic_period_id", type="integer", example=null),
                     @OA\Property(property="academic_period_code", type="string", example=null),
                     @OA\Property(property="academic_period_name", type="string", example=null),
                     @OA\Property(property="education_grade_id", type="integer", example=null),
                     @OA\Property(property="education_grade_code", type="string", example=null),
                     @OA\Property(property="education_grade_name", type="string", example=null),
                     @OA\Property(property="absent_date", type="string", example=null),
                     @OA\Property(property="absent_days", type="string", example=null),
                     @OA\Property(property="absence_subject_period", type="string", example=null),
                     @OA\Property(property="absence_type_id", type="integer", example=null),
                     @OA\Property(property="absence_type", type="string", example=null),
                     @OA\Property(property="student_absence_reason_id", type="integer", example=null),
                     @OA\Property(property="student_absence_reasons", type="string", example=null),
                     @OA\Property(property="student_status_id", type="integer", example=null),
                     @OA\Property(property="student_status", type="string", example=null),
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
 *     path="/api/v5/summary-institution-student-absences/{id}",
 *     summary="Get SummaryInstitutionStudentAbsences by ID",
 *     tags={"SummaryInstitutionStudentAbsences"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the SummaryInstitutionStudentAbsences",
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
 *     path="/api/v5/summary-institution-student-absences/{id}",
 *     summary="Update SummaryInstitutionStudentAbsences",
 *     tags={"SummaryInstitutionStudentAbsences"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the SummaryInstitutionStudentAbsences",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="institution_id", type="integer", example=null),
                     @OA\Property(property="institution_code", type="string", example=null),
                     @OA\Property(property="institution_name", type="string", example=null),
                     @OA\Property(property="area_id", type="integer", example=null),
                     @OA\Property(property="area_code", type="string", example=null),
                     @OA\Property(property="area_name", type="string", example=null),
                     @OA\Property(property="area_administrative_id", type="integer", example=null),
                     @OA\Property(property="area_administrative_code", type="string", example=null),
                     @OA\Property(property="area_administrative_name", type="string", example=null),
                     @OA\Property(property="student_id", type="integer", example=null),
                     @OA\Property(property="openemis_no", type="string", example=null),
                     @OA\Property(property="default_identity_number", type="string", example=null),
                     @OA\Property(property="student_name", type="string", example=null),
                     @OA\Property(property="enrol_start_date", type="string", example=null),
                     @OA\Property(property="enrol_end_date", type="string", example=null),
                     @OA\Property(property="academic_period_id", type="integer", example=null),
                     @OA\Property(property="academic_period_code", type="string", example=null),
                     @OA\Property(property="academic_period_name", type="string", example=null),
                     @OA\Property(property="education_grade_id", type="integer", example=null),
                     @OA\Property(property="education_grade_code", type="string", example=null),
                     @OA\Property(property="education_grade_name", type="string", example=null),
                     @OA\Property(property="absent_date", type="string", example=null),
                     @OA\Property(property="absent_days", type="string", example=null),
                     @OA\Property(property="absence_subject_period", type="string", example=null),
                     @OA\Property(property="absence_type_id", type="integer", example=null),
                     @OA\Property(property="absence_type", type="string", example=null),
                     @OA\Property(property="student_absence_reason_id", type="integer", example=null),
                     @OA\Property(property="student_absence_reasons", type="string", example=null),
                     @OA\Property(property="student_status_id", type="integer", example=null),
                     @OA\Property(property="student_status", type="string", example=null),
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
 *     path="/api/v5/summary-institution-student-absences/{id}",
 *     summary="Delete SummaryInstitutionStudentAbsences",
 *     tags={"SummaryInstitutionStudentAbsences"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the SummaryInstitutionStudentAbsences",
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
