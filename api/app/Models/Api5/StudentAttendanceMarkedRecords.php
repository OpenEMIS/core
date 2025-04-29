<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;

class StudentAttendanceMarkedRecords extends Model
{
    use HasFactory;
use InstitutionScope;

    // ✅ Allow mass assignment
    public $timestamps = false;
    public $incrementing = false;
    protected $fillable = ['institution_id', 'academic_period_id', 'institution_class_id', 'education_grade_id', 'date', 'period', 'subject_id', 'no_scheduled_class', 'institution_id', 'academic_period_id', 'institution_class_id', 'education_grade_id', 'subject_id'];
    protected $table = "student_attendance_marked_records";
    protected $primaryKey = ["institution_id", "academic_period_id", "institution_class_id", "education_grade_id", "date", "period", "subject_id"];


    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];


/**
 * @OA\PathItem(
 *     path="/api/v5/student-attendance-marked-records"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/student-attendance-marked-records",
 *     summary="Get list of StudentAttendanceMarkedRecords",
 *     tags={"StudentAttendanceMarkedRecords"},
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
                          @OA\Property(property="academic_period_id", type="integer", example=null),
                          @OA\Property(property="institution_class_id", type="integer", example=null),
                          @OA\Property(property="education_grade_id", type="integer", example=null),
                          @OA\Property(property="date", type="string", format="date", example=null),
                          @OA\Property(property="period", type="integer", example=null),
                          @OA\Property(property="subject_id", type="integer", example=null),
                          @OA\Property(property="no_scheduled_class", type="integer", example=null)
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
 *     path="/api/v5/student-attendance-marked-records",
 *     summary="Create a new StudentAttendanceMarkedRecords",
 *     tags={"StudentAttendanceMarkedRecords"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="institution_id", type="integer", example=null),
                     @OA\Property(property="academic_period_id", type="integer", example=null),
                     @OA\Property(property="institution_class_id", type="integer", example=null),
                     @OA\Property(property="education_grade_id", type="integer", example=null),
                     @OA\Property(property="date", type="string", format="date", example=null),
                     @OA\Property(property="period", type="integer", example=null),
                     @OA\Property(property="subject_id", type="integer", example=null),
                     @OA\Property(property="no_scheduled_class", type="integer", example=null)
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
 *     path="/api/v5/student-attendance-marked-records/institution_id/{institution_id}/academic_period_id/{academic_period_id}/institution_class_id/{institution_class_id}/education_grade_id/{education_grade_id}/date/{date}/period/{period}/subject_id/{subject_id}",
 *     summary="Get StudentAttendanceMarkedRecords record by composite key",
 *     tags={"StudentAttendanceMarkedRecords"},
 *     @OA\Parameter(
 *         name="institution_id",
 *         in="path",
 *         required=true,
 *         description="institution_id",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="academic_period_id",
 *         in="path",
 *         required=true,
 *         description="academic_period_id",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="institution_class_id",
 *         in="path",
 *         required=true,
 *         description="institution_class_id",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="education_grade_id",
 *         in="path",
 *         required=true,
 *         description="education_grade_id",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="date",
 *         in="path",
 *         required=true,
 *         description="date",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="period",
 *         in="path",
 *         required=true,
 *         description="period",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="subject_id",
 *         in="path",
 *         required=true,
 *         description="subject_id",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Record found"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Record not found"
 *     )
 * )
 */
public function _swaggerView() {}

/**
 * @OA\Put(
 *     path="/api/v5/student-attendance-marked-records/institution_id/{institution_id}/academic_period_id/{academic_period_id}/institution_class_id/{institution_class_id}/education_grade_id/{education_grade_id}/date/{date}/period/{period}/subject_id/{subject_id}",
 *     summary="Update StudentAttendanceMarkedRecords record by composite key",
 *     tags={"StudentAttendanceMarkedRecords"},
 *     @OA\Parameter(
 *         name="institution_id",
 *         in="path",
 *         required=true,
 *         description="institution_id",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="academic_period_id",
 *         in="path",
 *         required=true,
 *         description="academic_period_id",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="institution_class_id",
 *         in="path",
 *         required=true,
 *         description="institution_class_id",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="education_grade_id",
 *         in="path",
 *         required=true,
 *         description="education_grade_id",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="date",
 *         in="path",
 *         required=true,
 *         description="date",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="period",
 *         in="path",
 *         required=true,
 *         description="period",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="subject_id",
 *         in="path",
 *         required=true,
 *         description="subject_id",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
 *              *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Record updated successfully"
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Invalid data provided"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Record not found"
 *     )
 * )
 */
public function _swaggerUpdate() {}

/**
 * @OA\Delete(
 *     path="/api/v5/student-attendance-marked-records/institution_id/{institution_id}/academic_period_id/{academic_period_id}/institution_class_id/{institution_class_id}/education_grade_id/{education_grade_id}/date/{date}/period/{period}/subject_id/{subject_id}",
 *     summary="Delete StudentAttendanceMarkedRecords record by composite key",
 *     tags={"StudentAttendanceMarkedRecords"},
 *     @OA\Parameter(
 *         name="institution_id",
 *         in="path",
 *         required=true,
 *         description="institution_id",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="academic_period_id",
 *         in="path",
 *         required=true,
 *         description="academic_period_id",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="institution_class_id",
 *         in="path",
 *         required=true,
 *         description="institution_class_id",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="education_grade_id",
 *         in="path",
 *         required=true,
 *         description="education_grade_id",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="date",
 *         in="path",
 *         required=true,
 *         description="date",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="period",
 *         in="path",
 *         required=true,
 *         description="period",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="subject_id",
 *         in="path",
 *         required=true,
 *         description="subject_id",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response=204,
 *         description="Record deleted successfully"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Record not found"
 *     )
 * )
 */
public function _swaggerDelete() {}

    protected function getKeyForSaveQuery()
    {
        $query = $this->newQueryWithoutScopes();
        $keyName = $this->getKeyName();
        if (!is_array($keyName)) {
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
        if (!is_array($keyName)) {
            $keyName = [$keyName];;
        }
        foreach ($keyName as $key) {
            $query->where($key, '=', $this->getAttribute($key));
        }

        return $query;
    }


}
