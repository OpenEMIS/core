<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;
use App\Traits\ThresholdAlertTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\AlertTriggerService; // POCOR-9509 Phase 3

class InstitutionStudentAbsenceDetails extends Model
{
    use HasFactory;
    use InstitutionScope;
    use ThresholdAlertTrait;

    // ✅ Allow mass assignment
    protected $fillable = ['student_id', 'institution_id', 'academic_period_id', 'institution_class_id', 'education_grade_id', 'date', 'period', 'comment', 'absence_type_id', 'student_absence_reason_id', 'subject_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'student_id', 'institution_id', 'academic_period_id', 'institution_class_id', 'education_grade_id', 'absence_type_id', 'student_absence_reason_id', 'subject_id', 'modified_user_id', 'created_user_id'];

    protected $table = "institution_student_absence_details";
    public $timestamps = false;

    // ✅ Allow mass assignment
    public $incrementing = false;

    // ✅ Define the primary key
    protected $dates = ['modified', 'created'];
    protected $primaryKey = ["student_id","institution_id","academic_period_id","institution_class_id","date","period","subject_id"];

//POCOR:9509-start
    // POCOR-9509: Alert type configuration
    protected $alertType = 'StudentAttendance';
    protected $institutionClassId = null;

    /**
     * POCOR-9509: Boot method to register model events
     */
    protected static function boot()
    {
        parent::boot();

        // POCOR-9509: Trigger alert processing after save (only for new records)
        static::saved(function ($attendance) {
            if (!$attendance->wasRecentlyCreated) {
                return;
            }

            if (!$attendance->student_id || !$attendance->institution_id || !$attendance->academic_period_id) {
                // Log::warning('[POCOR-9509] Skipping alert - missing required fields', [
//                    'student_id' => $attendance->student_id,
//                    'institution_id' => $attendance->institution_id,
//                ]);
                return;
            }

            try {
                $attendance->processAbsenceAlert();
            } catch (\Throwable $e) {
                Log::error('[POCOR-9509] Alert processing failed in saved event', [
                    'student_id' => $attendance->student_id,
                    'exception' => $e->getMessage(),
                ]);
            }
        });
    }

    /**
     * POCOR-9509 Phase 3: Process absence alert after attendance is marked
     *
     * Updated to use AlertTriggerService for consistent architecture with CakePHP.
     */
    protected function processAbsenceAlert(): bool
    {
        if (!$this->student_id || !$this->institution_id || !$this->academic_period_id) {
            throw new \Exception('Missing required attributes for alert processing');
        }

        $processId = null; // Track for error handling

        try {
            // POCOR-9509 Phase 3: Get alert rule first
            $alertRule = AlertTriggerService::getActiveAlertRule(
                'StudentAttendance',
                $this->institution_id
            );

            if (!$alertRule) {
                // Log::debug('[POCOR-9509 Phase 3] No active alert rule for StudentAttendance', [
                //     'institution_id' => $this->institution_id,
                // ]);
                return false;
            }

            // POCOR-9509 Phase 3: Trigger alert with consistent architecture
            // This creates system_processes record with same format as CakePHP
            $triggerResult = AlertTriggerService::triggerAlert(
                processName: 'AlertStudentAbsence',  // Consistent with CakePHP
                featureName: 'StudentAttendance',    // Feature name from rule
                userId: $this->created_user_id ?? 1,
                ruleId: $alertRule->id,
                entityId: null, // No specific entity for threshold alerts
                context: [
                    'student_id' => $this->student_id,
                    'institution_id' => $this->institution_id,
                    'academic_period_id' => $this->academic_period_id,
                    'institution_class_id' => $this->institution_class_id,
                    'date' => $this->date,
                    'period' => $this->period,
                    'subject_id' => $this->subject_id,
                ],
                entityType: 'StudentAbsence',
                triggerType: 'threshold_exceeded'
            );

            if (!$triggerResult['success']) {
                if ($triggerResult['duplicate']) {
                    // Log::debug('[POCOR-9509 Phase 3] Duplicate alert skipped', [
                    //     'process_id' => $triggerResult['process_id'],
                    //     'checksum' => 'matched',
                    // ]);
                    return true; // Not an error, just a duplicate
                }

                Log::error('[POCOR-9509 Phase 3] Failed to trigger alert', [
                    'message' => $triggerResult['message'],
                ]);
                return false;
            }

            $processId = $triggerResult['process_id'];

            // POCOR-9509 Phase 3: Alert process created and command triggered!
            // The command (AlertStudentAbsenceCommand) does all the work:
            // - Queries absences and counts unique dates
            // - Checks against threshold
            // - Builds placeholders (${student.name}, ${total_days}, etc.)
            // - Resolves recipients (student + guardians)
            // - Sends email/SMS via alerts_queue
            // - Marks process as completed (status=3) or failed (status=-2)
            //
            // No duplicate logic in model! Command handles everything.
            // Log::info('[POCOR-9509 Phase 3] Alert process created and command dispatched', [
            //     'process_id' => $processId,
            //     'student_id' => $this->student_id,
            //     'command' => 'alerts:student-absence',
            //     'note' => 'Command will handle threshold checking and sending',
            // ]);

            return true;
        } catch (\Throwable $e) {
            // POCOR-9509 Phase 3: Log error (command will handle status updates)
            Log::error('[POCOR-9509 Phase 3] Failed to trigger absence alert', [
                'student_id' => $this->student_id,
                'process_id' => $processId,
                'exception' => $e->getMessage(),
            ]);

            // Don't throw - allow normal processing to continue
            return false;
        }
    }

    /**
     * POCOR-9509: Implement trait requirement - audit label
     */
    protected function getAuditLabel(): string
    {
        return 'StudentAbsence';
    }

    /**
     * POCOR-9509: Implement trait requirement - get absence threshold data
     */
    protected function getThresholdData(array $context): array
    {
        $studentId = $context['student_id'] ?? 0;
        $academicPeriodId = $context['academic_period_id'] ?? 0;

        $absences = DB::table('institution_student_absence_details')
            ->where('student_id', $studentId)
            ->where('academic_period_id', $academicPeriodId)
            ->whereIn('absence_type_id', [1, 2])
            ->orderBy('date', 'ASC')
            ->select('date')
            ->get();

        // Count unique dates
        $uniqueDates = [];
        foreach ($absences as $absence) {
            if (!empty($absence->date)) {
                $uniqueDates[$absence->date] = true;
            }
        }

        return [
            'current' => count($uniqueDates),
            'total_days' => count($uniqueDates),
            'total_times' => $absences->count(),
            'latest_date' => $absences->last()?->date,
        ];
    }

    /**
     * POCOR-9509: Implement trait requirement - get placeholder data
     */
    protected function getSubjectPlaceholders(array $context): array
    {
        $studentId = $context['student_id'] ?? 0;
        $institutionId = $context['institution_id'] ?? 0;

        $student = DB::table('security_users')
            ->leftJoin('genders', 'genders.id', '=', 'security_users.gender_id')
            ->leftJoin('nationalities', 'nationalities.id', '=', 'security_users.nationality_id')
            ->leftJoin('identity_types', 'identity_types.id', '=', 'security_users.identity_type_id')
            ->where('security_users.id', $studentId)
            ->select([
                'security_users.id', 'security_users.openemis_no', 'security_users.first_name',
                'security_users.middle_name', 'security_users.third_name', 'security_users.last_name',
                'security_users.preferred_name', 'security_users.email', 'security_users.address',
                'security_users.postal_code', 'security_users.date_of_birth', 'security_users.identity_number',
                'genders.name as gender_name', 'nationalities.name as nationality_name',
                'identity_types.name as identity_type_name',
            ])
            ->first();

        $institution = DB::table('institutions')
            ->where('id', $institutionId)
            ->select(['id', 'name', 'code', 'address', 'postal_code', 'contact_person', 'telephone', 'email', 'website'])
            ->first();

        if (!$student || !$institution) {
            return [];
        }

        return [
            '${student.name}' => trim($student->first_name . ' ' . $student->last_name),
            '${student.openemis_no}' => $student->openemis_no,
            '${student.first_name}' => $student->first_name,
            '${student.middle_name}' => $student->middle_name ?? '',
            '${student.third_name}' => $student->third_name ?? '',
            '${student.last_name}' => $student->last_name,
            '${student.preferred_name}' => $student->preferred_name ?? '',
            '${student.email}' => $student->email ?? '',
            '${student.address}' => $student->address ?? '',
            '${student.postal_code}' => $student->postal_code ?? '',
            '${student.date_of_birth}' => $student->date_of_birth ?? '',
            '${student.gender}' => $student->gender_name ?? '',
            '${student.identity_number}' => $student->identity_number ?? '',
            '${student.nationality}' => $student->nationality_name ?? '',
            '${student.identity_type}' => $student->identity_type_name ?? '',
            '${institution.name}' => $institution->name,
            '${institution.code}' => $institution->code,
            '${institution.address}' => $institution->address ?? '',
            '${institution.postal_code}' => $institution->postal_code ?? '',
            '${institution.contact_person}' => $institution->contact_person ?? '',
            '${institution.telephone}' => $institution->telephone ?? '',
            '${institution.email}' => $institution->email ?? '',
            '${institution.website}' => $institution->website ?? '',
        ];
    }
//POCOR:9509-end
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
