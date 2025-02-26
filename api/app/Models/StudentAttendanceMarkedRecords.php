<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentAttendanceMarkedRecords extends Model
{
    use HasFactory;
    // ✅ Allow mass assignment
    protected $fillable = ['institution_id', 'academic_period_id', 'institution_class_id', 'education_grade_id', 'date', 'period', 'subject_id', 'no_scheduled_class', 'institution_id', 'academic_period_id', 'institution_class_id', 'education_grade_id', 'subject_id'];

    public $timestamps = false;
    protected $table = "student_attendance_marked_records";


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
 * @OA\Get(
 *     path="/api/v5/student-attendance-marked-records/{id}",
 *     summary="Get StudentAttendanceMarkedRecords by ID",
 *     tags={"StudentAttendanceMarkedRecords"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the StudentAttendanceMarkedRecords",
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
 * @OA\Put(
 *     path="/api/v5/student-attendance-marked-records/{id}",
 *     summary="Update StudentAttendanceMarkedRecords",
 *     tags={"StudentAttendanceMarkedRecords"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the StudentAttendanceMarkedRecords",
 *         @OA\Schema(type="integer")
 *     ),
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
 *     path="/api/v5/student-attendance-marked-records/{id}",
 *     summary="Delete StudentAttendanceMarkedRecords",
 *     tags={"StudentAttendanceMarkedRecords"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the StudentAttendanceMarkedRecords",
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