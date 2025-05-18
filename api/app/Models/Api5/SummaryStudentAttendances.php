<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;

class SummaryStudentAttendances extends Model
{
    use HasFactory;
use InstitutionScope;

    protected $table = 'summary_student_attendances';

    // ✅ Allow mass assignment
    protected $fillable = ['academic_period_id', 'academic_period_name', 'institution_id', 'institution_code', 'institution_name', 'education_grade_id', 'education_grade_code', 'education_grade_name', 'class_id', 'class_name', 'attendance_date', 'period_id', 'period_name', 'subject_id', 'subject_name', 'female_count', 'male_count', 'total_count', 'marked_attendance', 'unmarked_attendance', 'present_female_count', 'present_male_count', 'present_total_count', 'absent_female_count', 'absent_male_count', 'absent_total_count', 'late_female_count', 'late_male_count', 'late_total_count', 'created'];

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
 *     path="/api/v5/summary-student-attendances"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/summary-student-attendances",
 *     summary="Get list of SummaryStudentAttendances",
 *     tags={"SummaryStudentAttendances"},
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
                          @OA\Property(property="institution_id", type="integer", example=null),
                          @OA\Property(property="institution_code", type="string", example=null),
                          @OA\Property(property="institution_name", type="string", example=null),
                          @OA\Property(property="education_grade_id", type="integer", example=null),
                          @OA\Property(property="education_grade_code", type="string", example=null),
                          @OA\Property(property="education_grade_name", type="string", example=null),
                          @OA\Property(property="class_id", type="integer", example=null),
                          @OA\Property(property="class_name", type="string", example=null),
                          @OA\Property(property="attendance_date", type="string", format="date", example=null),
                          @OA\Property(property="period_id", type="integer", example=null),
                          @OA\Property(property="period_name", type="string", example=null),
                          @OA\Property(property="subject_id", type="integer", example=null),
                          @OA\Property(property="subject_name", type="string", example=null),
                          @OA\Property(property="female_count", type="integer", example=null),
                          @OA\Property(property="male_count", type="integer", example=null),
                          @OA\Property(property="total_count", type="integer", example=null),
                          @OA\Property(property="marked_attendance", type="integer", example=null),
                          @OA\Property(property="unmarked_attendance", type="integer", example=null),
                          @OA\Property(property="present_female_count", type="integer", example=null),
                          @OA\Property(property="present_male_count", type="integer", example=null),
                          @OA\Property(property="present_total_count", type="integer", example=null),
                          @OA\Property(property="absent_female_count", type="integer", example=null),
                          @OA\Property(property="absent_male_count", type="integer", example=null),
                          @OA\Property(property="absent_total_count", type="integer", example=null),
                          @OA\Property(property="late_female_count", type="integer", example=null),
                          @OA\Property(property="late_male_count", type="integer", example=null),
                          @OA\Property(property="late_total_count", type="integer", example=null),
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
 *     path="/api/v5/summary-student-attendances",
 *     summary="Create a new SummaryStudentAttendances",
 *     tags={"SummaryStudentAttendances"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="academic_period_id", type="integer", example=null),
                     @OA\Property(property="academic_period_name", type="string", example=null),
                     @OA\Property(property="institution_id", type="integer", example=null),
                     @OA\Property(property="institution_code", type="string", example=null),
                     @OA\Property(property="institution_name", type="string", example=null),
                     @OA\Property(property="education_grade_id", type="integer", example=null),
                     @OA\Property(property="education_grade_code", type="string", example=null),
                     @OA\Property(property="education_grade_name", type="string", example=null),
                     @OA\Property(property="class_id", type="integer", example=null),
                     @OA\Property(property="class_name", type="string", example=null),
                     @OA\Property(property="attendance_date", type="string", format="date", example=null),
                     @OA\Property(property="period_id", type="integer", example=null),
                     @OA\Property(property="period_name", type="string", example=null),
                     @OA\Property(property="subject_id", type="integer", example=null),
                     @OA\Property(property="subject_name", type="string", example=null),
                     @OA\Property(property="female_count", type="integer", example=null),
                     @OA\Property(property="male_count", type="integer", example=null),
                     @OA\Property(property="total_count", type="integer", example=null),
                     @OA\Property(property="marked_attendance", type="integer", example=null),
                     @OA\Property(property="unmarked_attendance", type="integer", example=null),
                     @OA\Property(property="present_female_count", type="integer", example=null),
                     @OA\Property(property="present_male_count", type="integer", example=null),
                     @OA\Property(property="present_total_count", type="integer", example=null),
                     @OA\Property(property="absent_female_count", type="integer", example=null),
                     @OA\Property(property="absent_male_count", type="integer", example=null),
                     @OA\Property(property="absent_total_count", type="integer", example=null),
                     @OA\Property(property="late_female_count", type="integer", example=null),
                     @OA\Property(property="late_male_count", type="integer", example=null),
                     @OA\Property(property="late_total_count", type="integer", example=null),
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
 *     path="/api/v5/summary-student-attendances/{id}",
 *     summary="Get SummaryStudentAttendances by ID",
 *     tags={"SummaryStudentAttendances"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the SummaryStudentAttendances",
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
 *     path="/api/v5/summary-student-attendances/{id}",
 *     summary="Update SummaryStudentAttendances",
 *     tags={"SummaryStudentAttendances"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the SummaryStudentAttendances",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="academic_period_id", type="integer", example=null),
                     @OA\Property(property="academic_period_name", type="string", example=null),
                     @OA\Property(property="institution_id", type="integer", example=null),
                     @OA\Property(property="institution_code", type="string", example=null),
                     @OA\Property(property="institution_name", type="string", example=null),
                     @OA\Property(property="education_grade_id", type="integer", example=null),
                     @OA\Property(property="education_grade_code", type="string", example=null),
                     @OA\Property(property="education_grade_name", type="string", example=null),
                     @OA\Property(property="class_id", type="integer", example=null),
                     @OA\Property(property="class_name", type="string", example=null),
                     @OA\Property(property="attendance_date", type="string", format="date", example=null),
                     @OA\Property(property="period_id", type="integer", example=null),
                     @OA\Property(property="period_name", type="string", example=null),
                     @OA\Property(property="subject_id", type="integer", example=null),
                     @OA\Property(property="subject_name", type="string", example=null),
                     @OA\Property(property="female_count", type="integer", example=null),
                     @OA\Property(property="male_count", type="integer", example=null),
                     @OA\Property(property="total_count", type="integer", example=null),
                     @OA\Property(property="marked_attendance", type="integer", example=null),
                     @OA\Property(property="unmarked_attendance", type="integer", example=null),
                     @OA\Property(property="present_female_count", type="integer", example=null),
                     @OA\Property(property="present_male_count", type="integer", example=null),
                     @OA\Property(property="present_total_count", type="integer", example=null),
                     @OA\Property(property="absent_female_count", type="integer", example=null),
                     @OA\Property(property="absent_male_count", type="integer", example=null),
                     @OA\Property(property="absent_total_count", type="integer", example=null),
                     @OA\Property(property="late_female_count", type="integer", example=null),
                     @OA\Property(property="late_male_count", type="integer", example=null),
                     @OA\Property(property="late_total_count", type="integer", example=null),
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
 *     path="/api/v5/summary-student-attendances/{id}",
 *     summary="Delete SummaryStudentAttendances",
 *     tags={"SummaryStudentAttendances"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the SummaryStudentAttendances",
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
