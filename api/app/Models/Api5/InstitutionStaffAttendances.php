<?php

namespace App\Models\Api5;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;
use App\Traits\ThresholdAlertTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InstitutionStaffAttendances extends Model
{
    /**
     * POCOR-9509: Staff Absence Details Model
     *
     * Demonstrates how to reuse ThresholdAlertTrait for staff absences
     * Same pattern as InstitutionStudentAbsenceDetails but for staff
     */
    use HasFactory;
    use InstitutionScope;
    use ThresholdAlertTrait;

    // ✅ Allow mass assignment
    protected $fillable = ['id', 'staff_id', 'institution_id', 'academic_period_id', 'date', 'time_in', 'time_out', 'comment', 'modified_user_id', 'modified', 'created_user_id', 'created', 'absence_type_id', 'staff_id', 'institution_id', 'academic_period_id', 'modified_user_id', 'created_user_id', 'absence_type_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    protected $primaryKey = 'id';
    public $incrementing = false;
    public $timestamps = false;
    protected $table = "institution_staff_attendances";
    protected $alertType = 'StaffAttendance';
    protected $institutionClassId = null;

    /**
     * POCOR-9509: Boot method to register model events
     */
    protected static function boot()
    {
        parent::boot();

        // POCOR-9509: Trigger alert processing after save (only for new records)
        static::saved(function ($absence) {
            if (!$absence->wasRecentlyCreated) {
                return;
            }

            if (!$absence->staff_id || !$absence->institution_id || !$absence->academic_period_id) {
                // Log::warning('[POCOR-9509] Skipping staff alert - missing required fields', [
//                    'staff_id' => $absence->staff_id,
//                    'institution_id' => $absence->institution_id,
//                ]);
                return;
            }

            try {
                $absence->processStaffAbsenceAlert();
            } catch (\Throwable $e) {
                Log::error('[POCOR-9509] Alert processing failed in staff saved event', [
                    'staff_id' => $absence->staff_id,
                    'exception' => $e->getMessage(),
                ]);
            }
        });
    }

    /**
     * POCOR-9509: Process staff absence alert
     *
     * NOTE: Uses ThresholdAlertTrait directly because there's no
     * AlertStaffAttendance command (unlike StudentAbsence which has a command).
     */
    protected function processStaffAbsenceAlert(): bool
    {
        if (!$this->staff_id || !$this->institution_id || !$this->academic_period_id) {
            throw new \Exception('Missing required attributes for staff alert processing');
        }

        try {
            // POCOR-9509: Process threshold alert using inherited trait
            // This handles: threshold checking, placeholder building, queueing
            $result = $this->processThresholdAlert((int) $this->institution_id, [
                'staff_id' => (int) $this->staff_id,
                'academic_period_id' => (int) $this->academic_period_id,
                'institution_id' => (int) $this->institution_id,
            ]);

            // Log::info('[POCOR-9509] Staff absence alert processed via trait', [
            //     'staff_id' => $this->staff_id,
            //     'alert_sent' => $result['sent'],
            //     'threshold_met' => $result['threshold_met'] ?? null,
            // ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('[POCOR-9509] Failed to process staff absence alert', [
                'staff_id' => $this->staff_id,
                'exception' => $e->getMessage(),
            ]);

            throw new \Exception('Failed to process staff absence alert: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * POCOR-9509: Implement trait requirement - audit label
     */
    protected function getAuditLabel(): string
    {
        return 'StaffAttendance';
    }

    /**
     * POCOR-9509: Implement trait requirement - get absence threshold data
     */
    protected function getThresholdData(array $context): array
    {
        $staffId = $context['staff_id'] ?? 0;
        $academicPeriodId = $context['academic_period_id'] ?? 0;

        $absences = DB::table('institution_staff_attendances')
            ->where('staff_id', $staffId)
            ->where('academic_period_id', $academicPeriodId)
            ->whereIn('absence_type_id', [2])
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
        $staffId = $context['staff_id'] ?? 0;
        $institutionId = $context['institution_id'] ?? 0;

        $staff = DB::table('security_users')
            ->where('id', $staffId)
            ->select(['id', 'openemis_no', 'first_name', 'middle_name', 'last_name', 'email', 'address', 'postal_code'])
            ->first();

        $institution = DB::table('institutions')
            ->where('id', $institutionId)
            ->select(['id', 'name', 'code', 'address', 'postal_code', 'telephone', 'email', 'website'])
            ->first();

        if (!$staff || !$institution) {
            return [];
        }

        return [
            '${staff.name}' => trim($staff->first_name . ' ' . $staff->last_name),
            '${staff.openemis_no}' => $staff->openemis_no,
            '${staff.first_name}' => $staff->first_name,
            '${staff.middle_name}' => $staff->middle_name ?? '',
            '${staff.last_name}' => $staff->last_name,
            '${staff.email}' => $staff->email ?? '',
            '${staff.address}' => $staff->address ?? '',
            '${staff.postal_code}' => $staff->postal_code ?? '',
            '${institution.name}' => $institution->name,
            '${institution.code}' => $institution->code,
            '${institution.address}' => $institution->address ?? '',
            '${institution.email}' => $institution->email ?? '',
            '${institution.telephone}' => $institution->telephone ?? '',
            '${institution.website}' => $institution->website ?? '',
        ];
    }
    /**
     * @OA\PathItem(
     *     path="/api/v5/institution-staff-attendances"
     * )
     */
    public function _swaggerPath()
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v5/institution-staff-attendances",
     *     summary="Get list of InstitutionStaffAttendances",
     *     tags={"InstitutionStaffAttendances"},
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
    @OA\Property(property="id", type="string", example=null),
    @OA\Property(property="staff_id", type="integer", example=null),
    @OA\Property(property="institution_id", type="integer", example=null),
    @OA\Property(property="academic_period_id", type="integer", example=null),
    @OA\Property(property="date", type="string", format="date", example=null),
    @OA\Property(property="time_in", type="string", example=null),
    @OA\Property(property="time_out", type="string", example=null),
    @OA\Property(property="comment", type="string", example=null),
    @OA\Property(property="modified_user_id", type="integer", example=null),
    @OA\Property(property="modified", type="string", format="date-time", example=null),
    @OA\Property(property="created_user_id", type="integer", example=null),
    @OA\Property(property="created", type="string", format="date-time", example=null),
    @OA\Property(property="absence_type_id", type="integer", example=null)
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
    public function _swaggerList()
    {
    }

    /**
     * @OA\Post(
     *     path="/api/v5/institution-staff-attendances",
     *     summary="Create a new InstitutionStaffAttendances",
     *     tags={"InstitutionStaffAttendances"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
    @OA\Property(property="id", type="string", example=null),
    @OA\Property(property="staff_id", type="integer", example=null),
    @OA\Property(property="institution_id", type="integer", example=null),
    @OA\Property(property="academic_period_id", type="integer", example=null),
    @OA\Property(property="date", type="string", format="date", example=null),
    @OA\Property(property="time_in", type="string", example=null),
    @OA\Property(property="time_out", type="string", example=null),
    @OA\Property(property="comment", type="string", example=null),
    @OA\Property(property="modified_user_id", type="integer", example=null),
    @OA\Property(property="modified", type="string", format="date-time", example=null),
    @OA\Property(property="created_user_id", type="integer", example=null),
    @OA\Property(property="created", type="string", format="date-time", example=null),
    @OA\Property(property="absence_type_id", type="integer", example=null)
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
    public function _swaggerCreate()
    {
    }


    /**
     * @OA\Get(
     *     path="/api/v5/institution-staff-attendances/{id}",
     *     summary="Get InstitutionStaffAttendances by ID",
     *     tags={"InstitutionStaffAttendances"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the InstitutionStaffAttendances",
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
    public function _swaggerView()
    {
    }

    /**
     * @OA\Put(
     *     path="/api/v5/institution-staff-attendances/{id}",
     *     summary="Update InstitutionStaffAttendances",
     *     tags={"InstitutionStaffAttendances"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the InstitutionStaffAttendances",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
    @OA\Property(property="id", type="string", example=null),
    @OA\Property(property="staff_id", type="integer", example=null),
    @OA\Property(property="institution_id", type="integer", example=null),
    @OA\Property(property="academic_period_id", type="integer", example=null),
    @OA\Property(property="date", type="string", format="date", example=null),
    @OA\Property(property="time_in", type="string", example=null),
    @OA\Property(property="time_out", type="string", example=null),
    @OA\Property(property="comment", type="string", example=null),
    @OA\Property(property="modified_user_id", type="integer", example=null),
    @OA\Property(property="modified", type="string", format="date-time", example=null),
    @OA\Property(property="created_user_id", type="integer", example=null),
    @OA\Property(property="created", type="string", format="date-time", example=null),
    @OA\Property(property="absence_type_id", type="integer", example=null)
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
    public function _swaggerUpdate()
    {
    }

    /**
     * @OA\Delete(
     *     path="/api/v5/institution-staff-attendances/{id}",
     *     summary="Delete InstitutionStaffAttendances",
     *     tags={"InstitutionStaffAttendances"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the InstitutionStaffAttendances",
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
    public function _swaggerDelete()
    {
    }

    private function emptyFunction()
    {
        return;
    }
}
