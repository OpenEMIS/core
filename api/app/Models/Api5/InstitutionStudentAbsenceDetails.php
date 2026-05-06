<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;
use App\Services\AlertTriggerService;
use Illuminate\Support\Facades\Log;

class InstitutionStudentAbsenceDetails extends Model
{
    use HasFactory;
    use InstitutionScope;

    // ✅ Allow mass assignment
    protected $fillable = ['student_id', 'institution_id', 'academic_period_id', 'institution_class_id', 'education_grade_id', 'date', 'period', 'comment', 'absence_type_id', 'student_absence_reason_id', 'subject_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'student_id', 'institution_id', 'academic_period_id', 'institution_class_id', 'education_grade_id', 'absence_type_id', 'student_absence_reason_id', 'subject_id', 'modified_user_id', 'created_user_id'];

    protected $table = "institution_student_absence_details";
    public $timestamps = false;

    // ✅ Allow mass assignment
    public $incrementing = false;

    // ✅ Define the primary key
    protected $dates = ['modified', 'created'];
    protected $primaryKey = ["student_id","institution_id","academic_period_id","institution_class_id","date","period","subject_id"];

    /**
     * POCOR-9509: Trigger absence alerts only when a new absence record is created.
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function (self $attendance) {
            if (!$attendance->wasRecentlyCreated) {
                return;
            }

            if (!$attendance->student_id || !$attendance->institution_id || !$attendance->academic_period_id) {
                // POCOR-9509: Keep production resilient when required event data is missing.
                return;
            }

            try {
                $attendance->processAbsenceAlert();
            } catch (\Throwable $e) {
                Log::error('[POCOR-9509] Absence alert processing failed in saved event', [
                    'student_id' => $attendance->student_id,
                    'institution_id' => $attendance->institution_id,
                    'exception' => $e->getMessage(),
                ]);
            }
        });
    }

    /**
     * POCOR-9509: Dispatch the Laravel alert command for student absence events.
     */
    protected function processAbsenceAlert(): bool
    {
        $alertRule = AlertTriggerService::getActiveAlertRule('StudentAttendance', (int) $this->institution_id);

        if (!$alertRule) {
            return false;
        }

        $result = AlertTriggerService::triggerAlert(
            processName: 'AlertStudentAbsence',
            featureName: 'StudentAttendance',
            userId: (int) ($this->created_user_id ?: 1),
            ruleId: (int) $alertRule->id,
            entityId: null,
            context: [
                'student_id' => (int) $this->student_id,
                'institution_id' => (int) $this->institution_id,
                'academic_period_id' => (int) $this->academic_period_id,
                'institution_class_id' => $this->institution_class_id ? (int) $this->institution_class_id : null,
                'period' => $this->period,
                'subject_id' => $this->subject_id,
                'date' => $this->date,
            ],
            entityType: 'StudentAttendance',
            triggerType: 'threshold_exceeded'
        );

        return (bool) ($result['success'] ?? false) || (bool) ($result['duplicate'] ?? false);
    }


/**
 * @OA\PathItem(
 *     path="/api/v5/institution-student-absence-details"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/institution-student-absence-details",
 *     summary="Get list of InstitutionStudentAbsenceDetails",
 *     tags={"InstitutionStudentAbsenceDetails"},
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
                          @OA\Property(property="student_id", type="integer", example=null),
                          @OA\Property(property="institution_id", type="integer", example=null),
                          @OA\Property(property="academic_period_id", type="integer", example=null),
                          @OA\Property(property="institution_class_id", type="integer", example=null),
                          @OA\Property(property="education_grade_id", type="integer", example=null),
                          @OA\Property(property="date", type="string", format="date", example=null),
                          @OA\Property(property="period", type="integer", example=null),
                          @OA\Property(property="comment", type="string", example=null),
                          @OA\Property(property="absence_type_id", type="integer", example=null),
                          @OA\Property(property="student_absence_reason_id", type="integer", example=null),
                          @OA\Property(property="subject_id", type="integer", example=null),
                          @OA\Property(property="modified_user_id", type="integer", example=null),
                          @OA\Property(property="modified", type="string", format="date-time", example=null),
                          @OA\Property(property="created_user_id", type="integer", example=null),
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
 *     path="/api/v5/institution-student-absence-details",
 *     summary="Create a new InstitutionStudentAbsenceDetails",
 *     tags={"InstitutionStudentAbsenceDetails"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="student_id", type="integer", example=null),
                     @OA\Property(property="institution_id", type="integer", example=null),
                     @OA\Property(property="academic_period_id", type="integer", example=null),
                     @OA\Property(property="institution_class_id", type="integer", example=null),
                     @OA\Property(property="education_grade_id", type="integer", example=null),
                     @OA\Property(property="date", type="string", format="date", example=null),
                     @OA\Property(property="period", type="integer", example=null),
                     @OA\Property(property="comment", type="string", example=null),
                     @OA\Property(property="absence_type_id", type="integer", example=null),
                     @OA\Property(property="student_absence_reason_id", type="integer", example=null),
                     @OA\Property(property="subject_id", type="integer", example=null),
                     @OA\Property(property="modified_user_id", type="integer", example=null),
                     @OA\Property(property="modified", type="string", format="date-time", example=null),
                     @OA\Property(property="created_user_id", type="integer", example=null),
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
 *     path="/api/v5/institution-student-absence-details/student_id/{student_id}/institution_id/{institution_id}/academic_period_id/{academic_period_id}/institution_class_id/{institution_class_id}/date/{date}/period/{period}/subject_id/{subject_id}",
 *     summary="Get InstitutionStudentAbsenceDetails record by composite key",
 *     tags={"InstitutionStudentAbsenceDetails"},
 *     @OA\Parameter(
 *         name="student_id",
 *         in="path",
 *         required=true,
 *         description="student_id",
 *         @OA\Schema(type="string")
 *     ),
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
 *     path="/api/v5/institution-student-absence-details/student_id/{student_id}/institution_id/{institution_id}/academic_period_id/{academic_period_id}/institution_class_id/{institution_class_id}/date/{date}/period/{period}/subject_id/{subject_id}",
 *     summary="Update InstitutionStudentAbsenceDetails record by composite key",
 *     tags={"InstitutionStudentAbsenceDetails"},
 *     @OA\Parameter(
 *         name="student_id",
 *         in="path",
 *         required=true,
 *         description="student_id",
 *         @OA\Schema(type="string")
 *     ),
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
 *     path="/api/v5/institution-student-absence-details/student_id/{student_id}/institution_id/{institution_id}/academic_period_id/{academic_period_id}/institution_class_id/{institution_class_id}/date/{date}/period/{period}/subject_id/{subject_id}",
 *     summary="Delete InstitutionStudentAbsenceDetails record by composite key",
 *     tags={"InstitutionStudentAbsenceDetails"},
 *     @OA\Parameter(
 *         name="student_id",
 *         in="path",
 *         required=true,
 *         description="student_id",
 *         @OA\Schema(type="string")
 *     ),
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

    public function securityUser()
    {
        return $this->belongsTo(SecurityUsers::class, 'student_id', 'id');
    }


    public function educationGrade()
    {
        return $this->belongsTo(EducationGrades::class, 'education_grade_id', 'id');
    }


    public function institutionClass()
    {
        return $this->belongsTo(InstitutionClasses::class, 'institution_class_id', 'id');
    }


    public function academicPeriod()
    {
        return $this->belongsTo(AcademicPeriod::class, 'academic_period_id', 'id');
    }


    public function institution()
    {
        return $this->belongsTo(Institutions::class, 'institution_id', 'id');
    }


    public function absenceType()
    {
        return $this->belongsTo(AbsenceTypes::class, 'absence_type_id', 'id');
    }


    public function studentAbsenceReason()
    {
        return $this->belongsTo(StudentAbsenceReason::class, 'student_absence_reason_id', 'id');
    }


    public function period()
    {
        return $this->belongsTo(StudentAttendancePerDayPeriod::class, 'period', 'id');
    }


    public function subject()
    {
        return $this->belongsTo(InstitutionSubjects::class, 'subject_id', 'id');
    }

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
