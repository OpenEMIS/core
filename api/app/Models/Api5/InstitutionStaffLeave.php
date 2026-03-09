<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;
use App\Traits\ThresholdAlertTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InstitutionStaffLeave extends Model
{
    use HasFactory;
    use InstitutionScope;
    use ThresholdAlertTrait;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'date_from', 'date_to', 'start_time', 'end_time', 'full_day', 'comments', 'staff_id', 'staff_leave_type_id', 'institution_id', 'assignee_id', 'academic_period_id', 'status_id', 'number_of_days', 'file_name', 'file_content', 'modified_user_id', 'modified', 'created_user_id', 'created', 'staff_id', 'staff_leave_type_id', 'institution_id', 'assignee_id', 'academic_period_id', 'status_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    public $timestamps = false;
    protected $table = "institution_staff_leave";

    // POCOR-9509: Alert configuration
    protected $alertType = 'StaffLeave';

    /**
     * POCOR-9509: Boot method to register model events
     */
    protected static function boot()
    {
        parent::boot();

        // POCOR-9509: Trigger alert processing after save (only for new records)
        static::saved(function ($leave) {
            if (!$leave->wasRecentlyCreated) {
                return;
            }

            if (!$leave->institution_id || !$leave->staff_id || !$leave->date_to) {
                // Log::warning('[POCOR-9509] Skipping leave alert - missing required fields', [
//                    'institution_id' => $leave->institution_id,
//                    'staff_id' => $leave->staff_id,
//                    'date_to' => $leave->date_to,
//                ]);
                return;
            }

            try {
                $leave->processLeaveAlert();
            } catch (\Throwable $e) {
                Log::error('[POCOR-9509] Leave alert processing failed in saved event', [
                    'staff_id' => $leave->staff_id,
                    'exception' => $e->getMessage(),
                ]);
            }
        });
    }

    /**
     * POCOR-9509: Process leave alert after record is saved
     */
    protected function processLeaveAlert(): bool
    {
        $result = $this->processThresholdAlert((int) $this->institution_id, [
            'staff_id' => (int) $this->staff_id,
            'leave_date_to' => $this->date_to,
        ]);

        // Log::info('[POCOR-9509] Leave alert processed', [
        //     'staff_id' => $this->staff_id,
        //     'alert_sent' => $result['sent'],
        // ]);

        return $result['sent'];
    }

    /**
     * POCOR-9509: Implement trait requirement - audit label
     */
    protected function getAuditLabel(): string
    {
        return 'StaffLeave';
    }

    /**
     * POCOR-9509: Implement trait requirement - get days until leave end date
     */
    protected function getThresholdData(array $context): array
    {
        // Get leave end date
        $dateToStr = $context['leave_date_to'] ?? null;

        if (!$dateToStr) {
            return ['current' => 0];
        }

        try {
            $dateTo = new \DateTime($dateToStr);
            $today = new \DateTime();
            $diff = $today->diff($dateTo);

            $daysDiff = $diff->days;
            if ($diff->invert) {
                $daysDiff = -$daysDiff; // negative if in past
            }

            return [
                'current' => max(0, $daysDiff), // 0 if already passed
                'days_until_leave_end' => $daysDiff,
            ];
        } catch (\Throwable $e) {
            Log::error('[POCOR-9509] Failed to calculate leave threshold data', [
                'date_to' => $dateToStr,
                'exception' => $e->getMessage(),
            ]);
            return ['current' => 0];
        }
    }

    /**
     * POCOR-9509: Implement trait requirement - get placeholder data for alert message
     */
    protected function getSubjectPlaceholders(array $context): array
    {
        $staffId = $context['staff_id'] ?? 0;
        $institutionId = $context['institution_id'] ?? 0;

        $staff = DB::table('security_users')
            ->where('id', $staffId)
            ->select([
                'id', 'openemis_no', 'first_name', 'middle_name', 'last_name',
                'preferred_name', 'email', 'address', 'postal_code', 'date_of_birth'
            ])
            ->first();

        $institution = DB::table('institutions')
            ->where('id', $institutionId)
            ->select(['id', 'name', 'code', 'address', 'postal_code', 'contact_person', 'telephone', 'email', 'website'])
            ->first();

        if (!$staff || !$institution) {
            return [];
        }

        // Get latest leave details
        $leave = DB::table('institution_staff_leave')
            ->where('staff_id', $staffId)
            ->where('institution_id', $institutionId)
            ->orderBy('date_from', 'desc')
            ->select(['id', 'date_from', 'date_to', 'staff_leave_type_id', 'comments'])
            ->first();

        $leaveType = '';
        if ($leave) {
            $leaveTypeRecord = DB::table('staff_leave_types')
                ->where('id', $leave->staff_leave_type_id)
                ->value('name');
            $leaveType = $leaveTypeRecord ?? '';
        }

        return [
            '${user.openemis_no}' => $staff->openemis_no,
            '${user.first_name}' => $staff->first_name,
            '${user.middle_name}' => $staff->middle_name ?? '',
            '${user.last_name}' => $staff->last_name,
            '${user.preferred_name}' => $staff->preferred_name ?? '',
            '${user.email}' => $staff->email ?? '',
            '${user.address}' => $staff->address ?? '',
            '${user.postal_code}' => $staff->postal_code ?? '',
            '${user.date_of_birth}' => $staff->date_of_birth ?? '',
            '${staff_leave_type.name}' => $leaveType,
            '${date_from}' => ($leave ? $leave->date_from : ''),
            '${date_to}' => ($leave ? $leave->date_to : ''),
            '${comments}' => ($leave ? $leave->comments : ''),
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

/**
 * @OA\PathItem(
 *     path="/api/v5/institution-staff-leave"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/institution-staff-leave",
 *     summary="Get list of InstitutionStaffLeave",
 *     tags={"InstitutionStaffLeave"},
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
                          @OA\Property(property="id", type="integer", example=null),
                          @OA\Property(property="date_from", type="string", format="date", example=null),
                          @OA\Property(property="date_to", type="string", format="date", example=null),
                          @OA\Property(property="start_time", type="string", example=null),
                          @OA\Property(property="end_time", type="string", example=null),
                          @OA\Property(property="full_day", type="integer", example=null),
                          @OA\Property(property="comments", type="string", example=null),
                          @OA\Property(property="staff_id", type="integer", example=null),
                          @OA\Property(property="staff_leave_type_id", type="integer", example=null),
                          @OA\Property(property="institution_id", type="integer", example=null),
                          @OA\Property(property="assignee_id", type="integer", example=null),
                          @OA\Property(property="academic_period_id", type="integer", example=null),
                          @OA\Property(property="status_id", type="integer", example=null),
                          @OA\Property(property="number_of_days", type="number", example=null),
                          @OA\Property(property="file_name", type="string", example=null),
                          @OA\Property(property="file_content", type="string", example=null),
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
 *     path="/api/v5/institution-staff-leave",
 *     summary="Create a new InstitutionStaffLeave",
 *     tags={"InstitutionStaffLeave"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="date_from", type="string", format="date", example=null),
                     @OA\Property(property="date_to", type="string", format="date", example=null),
                     @OA\Property(property="start_time", type="string", example=null),
                     @OA\Property(property="end_time", type="string", example=null),
                     @OA\Property(property="full_day", type="integer", example=null),
                     @OA\Property(property="comments", type="string", example=null),
                     @OA\Property(property="staff_id", type="integer", example=null),
                     @OA\Property(property="staff_leave_type_id", type="integer", example=null),
                     @OA\Property(property="institution_id", type="integer", example=null),
                     @OA\Property(property="assignee_id", type="integer", example=null),
                     @OA\Property(property="academic_period_id", type="integer", example=null),
                     @OA\Property(property="status_id", type="integer", example=null),
                     @OA\Property(property="number_of_days", type="number", example=null),
                     @OA\Property(property="file_name", type="string", example=null),
                     @OA\Property(property="file_content", type="string", example=null),
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
 *     path="/api/v5/institution-staff-leave/{id}",
 *     summary="Get InstitutionStaffLeave by ID",
 *     tags={"InstitutionStaffLeave"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the InstitutionStaffLeave",
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
 *     path="/api/v5/institution-staff-leave/{id}",
 *     summary="Update InstitutionStaffLeave",
 *     tags={"InstitutionStaffLeave"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the InstitutionStaffLeave",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="date_from", type="string", format="date", example=null),
                     @OA\Property(property="date_to", type="string", format="date", example=null),
                     @OA\Property(property="start_time", type="string", example=null),
                     @OA\Property(property="end_time", type="string", example=null),
                     @OA\Property(property="full_day", type="integer", example=null),
                     @OA\Property(property="comments", type="string", example=null),
                     @OA\Property(property="staff_id", type="integer", example=null),
                     @OA\Property(property="staff_leave_type_id", type="integer", example=null),
                     @OA\Property(property="institution_id", type="integer", example=null),
                     @OA\Property(property="assignee_id", type="integer", example=null),
                     @OA\Property(property="academic_period_id", type="integer", example=null),
                     @OA\Property(property="status_id", type="integer", example=null),
                     @OA\Property(property="number_of_days", type="number", example=null),
                     @OA\Property(property="file_name", type="string", example=null),
                     @OA\Property(property="file_content", type="string", example=null),
                     @OA\Property(property="modified_user_id", type="integer", example=null),
                     @OA\Property(property="modified", type="string", format="date-time", example=null),
                     @OA\Property(property="created_user_id", type="integer", example=null),
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
 *     path="/api/v5/institution-staff-leave/{id}",
 *     summary="Delete InstitutionStaffLeave",
 *     tags={"InstitutionStaffLeave"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the InstitutionStaffLeave",
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
    public function institution()
    {
        return $this->belongsTo(Institutions::class, 'institution_id', 'id');
    }


    public function staff()
    {
        return $this->belongsTo(SecurityUsers::class, 'staff_id', 'id');
    }


    public function assignee()
    {
        return $this->belongsTo(SecurityUsers::class, 'assignee_id', 'id');
    }

    public function securityUser()
    {
        return $this->belongsTo(SecurityUsers::class, 'created_user_id', 'id');
    }

    public function status()
    {
        return $this->belongsTo(WorkflowSteps::class, 'status_id', 'id');
    }


    public function staffLeaveType()
    {
        return $this->belongsTo(StaffLeaveType::class, 'staff_leave_type_id', 'id');
    }
}
